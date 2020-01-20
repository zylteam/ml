<?php


namespace app\api\controller;


use app\admin\model\Store;
use app\admin\model\UserModel;
use Overtrue\Socialite\User;

class LoginController extends BaseController
{

    public function getSessionkey()
    {
        $code = input('post.code') ? input('post.code') : '';
        if ($code) {
            $ml_app = $this->ml_app;
            $SessionKey = $ml_app->auth->session($code);
            $this->success('获取sessionkey', ['sessionKey' => $SessionKey]);
        } else {
            $this->error('获取sessionkey失败');
        }
    }

    public function getPhoneNumber()
    {
        $session_key = input('post.session_key') ? input('post.session_key') : '';
        $iv = input('post.iv') ? input('post.iv') : '';
        $encryptedData = input('post.encryptedData') ? input('post.encryptedData') : '';
        $openid = input('post.openid') ? input('post.openid') : '';
        $user = UserModel::where('user_openid', $openid)->find();
        if ($user) {
            $result = $this->ml_app->encryptor->decryptData($session_key, $iv, $encryptedData);
            if ($result) {
                $user->id = $user['id'];
                $user->mobile = $result['phoneNumber'];
                $user->save();
                $this->success('获取用户信息', ['userinfo' => UserModel::get($user->id)]);
            } else {
                $this->error('失败');
            }
        } else {
            $result = $this->ml_app->encryptor->decryptData($session_key, $iv, $encryptedData);
            $user = new  UserModel();
            $user->create_time = time();
            $user->openid = $openid;
            $user->mobile = $result['phoneNumber'];
            $user->save();
            $this->success('获取用户信息', ['userinfo' => UserModel::get($user->id)]);
        }

    }

    public function getUserInfo()
    {
        $session_key = input('post.session_key') ? input('post.session_key') : '';
        $iv = input('post.iv') ? input('post.iv') : '';
        $encryptedData = input('post.encryptedData') ? input('post.encryptedData') : '';
        $result = $this->ml_app->encryptor->decryptData($session_key, $iv, $encryptedData);
        if ($result) {
            $user = UserModel::where('user_openid', $result['openId'])->find();
            if (!$user) {
                $user = new UserModel();
                $user->create_time = time();
            } else {
                $user->id = $user['id'];
            }
            $user->last_login_ip = get_client_ip();
            $user->last_login_time = time();
            $user->user_type = 2;
            $user->sex = $result['gender'];
            $user->user_status = 1;
            $user->avatar = $result['avatarUrl'];
            $user->user_openid = $result['openId'];
            $user->user_nickname = $result['nickName'];
            $res = $user->save();
            if ($res) {
                $this->success('获取用户信息', ['userinfo' => $user]);
            } else {
                $this->error('数据异常');
            }

        } else {
            $this->error('失败');
        }
    }

    public function store_user_login()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $store_info = Store::where('login_name', $data['login_name'])->find();
            if ($store_info) {
                if (cmf_password($data['login_pass']) == $store_info['login_pass']) {
                    session('store_info', $store_info);
//                    $user_token = generate_user_token($store_info['id'], 'mobile', 'wx');
                    $this->success('登录成功');
                } else {
                    $this->error('用户名或密码错误');
                }
            } else {
                $this->error('用户名或密码错误');
            }
        }
    }

    public function login_out()
    {
        session('store_info', null);
        $this->success('退出');
    }

    public function test(){
        return cmf_url('portal/List/index',['id'=>1,'name'=>'cmf5']);
    }
}