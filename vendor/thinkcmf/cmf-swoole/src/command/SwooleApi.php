<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// | Author: 老猫 <catman@thinkcmf.com>
// +----------------------------------------------------------------------

namespace think\swoole\command;

use Swoole\Process;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Config;
use think\facade\Env;
use think\swoole\Http as HttpServer;
use think\Container;

/**
 * Swoole HTTP 命令行，支持操作：start|stop|restart|reload
 * 支持应用配置目录下的swoole_api.php文件进行参数配置
 */
class SwooleApi extends Command
{
    protected $config = [];

    public function configure()
    {
        $this->setName('swoole:api')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload", 'start')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'the host of swoole server.', null)
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'the port of swoole server.', null)
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the swoole server in daemon mode.')
            ->setDescription('Swoole HTTP API Server for ThinkCMF');
    }

    public function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');

        $this->init();

        if (in_array($action, ['start', 'stop', 'reload', 'restart'])) {
            $this->$action();
        } else {
            $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload .</error>");
        }
    }

    protected function init()
    {
        $this->config = [
            'host'                  => '0.0.0.0', // 监听地址
            'port'                  => 9502, // 监听端口
            'mode'                  => '', // 运行模式 默认为SWOOLE_PROCESS
            'sock_type'             => '', // sock type 默认为SWOOLE_SOCK_TCP
            'server_type'           => 'http', // 服务类型 支持 http websocket
            'app_path'              => '', // 应用地址 如果开启了 'daemonize'=>true 必须设置（使用绝对路径）
            'file_monitor'          => false, // 是否开启PHP文件更改监控（调试模式下自动开启）
            'file_monitor_interval' => 2, // 文件变化监控检测时间间隔（秒）
            'file_monitor_path'     => [], // 文件监控目录 默认监控application和config目录

            // 可以支持swoole的所有配置参数
            'pid_file'              => Env::get('runtime_path') . 'swoole_api.pid',
            'log_file'              => Env::get('runtime_path') . 'swoole_api.log',
            'document_root'         => Env::get('root_path') . 'public',
            'enable_static_handler' => true,
            'timer'                 => true,//是否开启系统定时器
            'interval'              => 500,//系统定时器 时间间隔
            'task_worker_num'       => 2,//swoole 任务工作进程数量
        ];

        $this->config = Config::pull('swoole_api');

        if (!empty($this->config)) {
            $this->config = array_merge($this->config, $config);
        }

        Config::set(['swoole' => $this->config]);

        if (empty($this->config['pid_file'])) {
            $this->config['pid_file'] = Env::get('runtime_path') . 'swoole_api.pid';
        }

        // 避免pid混乱
        $this->config['pid_file'] .= '_' . $this->getPort();
    }

    protected function getHost()
    {
        if ($this->input->hasOption('host')) {
            $host = $this->input->getOption('host');
        } else {
            $host = !empty($this->config['host']) ? $this->config['host'] : '0.0.0.0';
        }

        return $host;
    }

    protected function getPort()
    {
        if ($this->input->hasOption('port')) {
            $port = $this->input->getOption('port');
        } else {
            $port = !empty($this->config['port']) ? $this->config['port'] : 9502;
        }

        return $port;
    }

    /**
     * 启动server
     * @access protected
     * @return void
     */
    protected function start()
    {
        $pid = $this->getMasterPid();

        if ($this->isRunning($pid)) {
            $this->output->writeln('<error>swoole http api server process is already running.</error>');
            return false;
        }

        $this->output->writeln('Starting swoole http api server...');

        $host = $this->getHost();
        $port = $this->getPort();
        $mode = !empty($this->config['mode']) ? $this->config['mode'] : SWOOLE_PROCESS;
        $type = !empty($this->config['sock_type']) ? $this->config['sock_type'] : SWOOLE_SOCK_TCP;

        $ssl = !empty($this->config['ssl']) || !empty($this->config['open_http2_protocol']);
        if ($ssl) {
            $type = SWOOLE_SOCK_TCP | SWOOLE_SSL;
        }

        // 定义命名空间
        define('APP_NAMESPACE', 'api');

//        // 定义缓存目录
//        define('RUNTIME_PATH', CMF_ROOT . 'data/runtime_api/');

        // 定义路由目录
        define('ROUTE_PATH', CMF_ROOT . 'api/route.php');

        $swoole = new HttpServer($host, $port, $mode, $type);

        // 开启守护进程模式
        if ($this->input->hasOption('daemon')) {
            $this->config['daemonize'] = true;
        }

        // 设置应用目录
        $swoole->setAppPath(CMF_ROOT . 'api/');

        // 创建内存表
        if (!empty($this->config['table'])) {
            $swoole->table($this->config['table']);
            unset($this->config['table']);
        }

        $swoole->cachetable();

        // 设置文件监控 调试模式自动开启
        if (Env::get('app_debug') || !empty($this->config['file_monitor'])) {
            $interval = isset($this->config['file_monitor_interval']) ? $this->config['file_monitor_interval'] : 2;
            $paths    = isset($this->config['file_monitor_path']) ? $this->config['file_monitor_path'] : [];
            $swoole->setMonitor($interval, $paths);
            unset($this->config['file_monitor'], $this->config['file_monitor_interval'], $this->config['file_monitor_path']);
        }

        // 设置服务器参数
        if (isset($this->config['pid_file'])) {

        }
        $swoole->option($this->config);

        $this->output->writeln("Swoole http server started: <http://{$host}:{$port}>");
        $this->output->writeln('You can exit with <info>`CTRL-C`</info>');

        $hook = Container::get('hook');
        $hook->listen("swoole_server_start", $swoole);

        $swoole->start();
    }

    /**
     * 柔性重启server
     * @access protected
     * @return void
     */
    protected function reload()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<error>no swoole http api server process running.</error>');
            return false;
        }

        $this->output->writeln('Reloading swoole http api server...');
        Process::kill($pid, SIGUSR1);
        $this->output->writeln('> success');
    }

    /**
     * 停止server
     * @access protected
     * @return void
     */
    protected function stop()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<error>no swoole http api server process running.</error>');
            return false;
        }

        $this->output->writeln('Stopping swoole http api server...');

        Process::kill($pid, SIGTERM);
        $this->removePid();

        $this->output->writeln('> success');
    }

    /**
     * 重启server
     * @access protected
     * @return void
     */
    protected function restart()
    {
        $pid = $this->getMasterPid();

        if ($this->isRunning($pid)) {
            $this->stop();
        }

        $this->start();
    }

    /**
     * 获取主进程PID
     * @access protected
     * @return int
     */
    protected function getMasterPid()
    {
        $pidFile = $this->config['pid_file'];

        if (is_file($pidFile)) {
            $masterPid = (int)file_get_contents($pidFile);
        } else {
            $masterPid = 0;
        }

        return $masterPid;
    }

    /**
     * 删除PID文件
     * @access protected
     * @return void
     */
    protected function removePid()
    {
        $masterPid = $this->config['pid_file'];

        if (is_file($masterPid)) {
            unlink($masterPid);
        }
    }

    /**
     * 判断PID是否在运行
     * @access protected
     * @param  int $pid
     * @return bool
     */
    protected function isRunning($pid)
    {
        if (empty($pid)) {
            return false;
        }

        return Process::kill($pid, 0);
    }
}
