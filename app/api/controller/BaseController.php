<?php


namespace app\api\controller;


use cmf\controller\RestBaseController;

use EasyWeChat\Factory;
use think\facade\Config;

class BaseController extends RestBaseController
{
    public $ml_config = '';
    public $ml_app = '';
    public $host = "http://ml.0513app.cn";

    protected function initialize()
    {
        $this->ml_config = Config::get('ml_config');
        $this->ml_app = Factory::miniProgram($this->ml_config);
        //检测用户超时订单
        check_order();
    }

    public function base()
    {
        dump($this->ml_config);
    }
}