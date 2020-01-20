<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 五五 <15093565100@163.com>
// +----------------------------------------------------------------------
namespace plugins\rong_lian_sms;


use cmf\lib\Plugin;
use plugins\rong_lian_sms\libs\REST;

class RongLianSmsPlugin extends Plugin
{
    public $info = [
        'name' => 'RongLianSms',
        'title' => '容联-短信插件',
        'description' => '容联-短信插件',
        'status' => 1,
        'author' => '五五',
        'version' => '1.0'
    ];

    public $hasAdmin = 0;//插件是否有后台管理界面


    // 插件安装
    public function install()
    {
        return true;//安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true;//卸载成功返回true，失败false
    }

    //实现的send_mobile_verification_code钩子方法
    public function sendMobileVerificationCode($param)
    {
        $mobile = $param['mobile'];//手机号
        $code = $param['code'];//验证码
//        $config = $this->getConfig();
        return $param;
        $expire_minute = intval($config['expire_minute']);
        $expire_minute = empty($expire_minute) ? 30 : $expire_minute;
        $expire_time = time() + $expire_minute * 60;
        $result = false;
        $account_sid = $config['account_sid'];
        $auth_token = $config['auth_token'];
        $app_id = $config['app_id'];
        $temp_id = $config['template_id'];
        $to = $mobile;
        $datas = [$code, $expire_minute];
        $rest = new REST('app.cloopen.com', '8883', '2013-12-26');
        $rest->setAccount($account_sid, $auth_token);
        $rest->setAppId($app_id);
        $result = $rest->sendTemplateSMS($to, $datas, $temp_id);//发送验证码
        if ($result == NULL) {
            $result = [
                'error' => 1,
                'message' => 'result error!'
            ];

        }
        if ($result->statusCode != 0) {
            //TODO 添加错误处理逻辑
            $result = [
                'error' => 2,
                'message' => $result->statusMsg
            ];
        } else {
            //TODO 添加成功处理逻辑
            $result = [
                'error' => 0,
                'message' => '验证码已发送，请打开手机查看'
            ];
        }
        return $result;
    }
}