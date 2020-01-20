<?php


namespace app\user\controller;


use cmf\controller\HomeBaseController;
use think\facade\Session;

class LoginController extends HomeBaseController
{
    protected $app = '';
    protected function initialize()
    {
        $this->app = hook_one('wechat_auth_login');
    }

    public function index()
    {
        return $this->fetch();
    }

    public function oauth_callback()
    {
//          $response = $this->app->server->serve();
//         $response->send(); //微信回调设置
        if(Session::get('target_url')){
            $oauth = $this->app->oauth;
            $user = $oauth->user();
            Session::set('wechat_user',$user->toArray());
            return $this->redirect(Session::get('target_url'));
        }
    }
}