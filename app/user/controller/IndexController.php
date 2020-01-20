<?php


namespace app\user\controller;


use app\admin\model\Store;
use app\admin\model\UserModel;
use app\api\model\Order;
use app\user\model\SettlementLog;
use cmf\controller\HomeBaseController;
use think\Db;
use think\Exception;
use think\facade\Request;
use think\facade\Session;

class IndexController extends HomeBaseController
{
    protected $app = '';
    protected $user = '';

    protected function initialize()
    {
        $this->app = hook_one('wechat_auth_login');
        $oauth = $this->app->oauth;
        $wechat_user = Session::get('wechat_user');
        if ($wechat_user == "") {
            session::set('target_url', Request::url(true));
            $oauth->redirect()->send();
        }
        $user = UserModel::where('user_openid', $wechat_user['original']['openid'])->find();
        if (!$user) {
            $user = new UserModel();
            $user->create_time = time();
            $user->last_login_ip = get_client_ip();
            $user->last_login_time = time();
            $user->user_type = 3;
            $user->sex = $wechat_user['original']['sex'];
            $user->user_status = 1;
            $user->avatar = $wechat_user['original']['headimgurl'];
            $user->user_openid = $wechat_user['original']['openid'];
            $user->user_nickname = $wechat_user['original']['nickname'];
            $user->save();
        }
        $this->user = $user;
        if (Session::get('store_info') == "") {
            return $this->redirect('user/login/index');
        }
    }

    public function index()
    {
        $store_id = Session::get('store_info')['id'];
        $store_info = Store::withCount('orders')->get($store_id);
        $today_order_count = $store_info->orders()->whereTime('create_time', 'today')->where('pay_status', '=', '1')->count();
        $store_info['today_order_count'] = $today_order_count;
        $today_income = $store_info->logs()->whereTime('create_time', 'today')->sum('money');
        $service_money = $store_info->logs()->whereTime('create_time', 'today')->sum('service_money');
        $store_info['today_income'] = round($today_income - $service_money, 2);
        $js = $this->app->jssdk->buildConfig(array('scanQRCode'), false);
        $this->assign('js', $js);
        $this->assign('store_info', $store_info);
        return $this->fetch();
    }

    public function bind_wechat()
    {
        $store_info = Store::get(Session::get('store_info')['id']);
        if ($store_info['wx_openid']) {
            $result['code'] = 0;
            $result['msg'] = '已绑定用户微信';
            return $result;
        }
        $user = $this->user;
        $store_info->wx_openid = $user['user_openid'];
        $res = $store_info->save();
        if ($res) {
            Session::set('store_info', $store_info);
            $result['code'] = 1;
            $result['msg'] = '绑定成功';
            return $result;
        } else {
            $result['code'] = 0;
            $result['msg'] = '绑定失败';
            return $result;
        }
    }

    public function check_code()
    {
        $check_code = input('code') ? input('code') : '';
        $store_id = Session::get('store_info')['id'];
        if (!$check_code) {
            $this->error('未检测到可用核销码');
        }
        $order_info = Order::with('product')->where(['check_code' => $check_code, 'store_id' => $store_id])->find();
        if ($order_info) {
            if ($order_info['product']['product_img']) {
                $array = explode(',', $order_info['product']['product_img']);
                $temp = array();
                foreach ($array as $img) {
                    $temp[] = 'http://ml.0513app.cn/' . $img;
                }
                $order_info['product']['product_img'] = $temp;
            }
        }
        $this->assign('user_openid', $this->user['user_openid']);
        $this->assign('order_info', $order_info);
        return $this->fetch();
    }


    public function store()
    {
        $store_id = Session::get('store_info')['id'];
        $this->assign('store_info', Store::get($store_id));
        return $this->fetch();
    }

    public function order_list()
    {
        $store_id = Session::get('store_info')['id'];
        $this->assign('store_id', $store_id);
        return $this->fetch();
    }

    public function settlement_list()
    {
        $store_id = Session::get('store_info')['id'];
        $status1 = SettlementLog::where('status', 1)->sum('money');
        $status0 = SettlementLog::where('status', 0)->sum('money');
        $this->assign('status1', $status1);
        $this->assign('status0', $status0);
        $this->assign('store_id', $store_id);
        return $this->fetch();
    }
}