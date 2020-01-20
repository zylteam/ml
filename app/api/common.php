<?php

use think\facade\Hook;
use think\Db;

function check_wx_user($openid)
{
    if (!$openid) {
        return false;
    }
    $user_info = \app\api\model\UserModel::where('user_openid', $openid)->find();
    if ($user_info) {
        return $user_info;
    } else {
        return false;
    }
}

function check_store_user($token, $device_type, $type)
{
    if (!$token) {
        return 0;
    }
    $user_token = Db::name('user_token')->where(['token' => $token, 'device_type' => $device_type, 'type' => $type])->find();
    if (time() > $user_token['expire_time']) {
        return 1;
    } else {
        $store_id = $user_token['user_id'];
        $store_info = \app\admin\model\Store::get($store_id);
        return $store_info;
    }
}


//生成唯一订单
function build_order_sn()
{
    $order_sn = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    //检测是否存在
    $count = \app\api\model\Order::where('order_sn', $order_sn)->count();
    ($count > 0) && $order_sn = build_order_sn();
    return $order_sn;
}

//生成唯一订单
function build_pay_sn()
{
    $pay_sn = "PAY-" . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    //检测是否存在
    $count = \app\api\model\Order::where('pay_sn', $pay_sn)->count();
    ($count > 0) && $pay_sn = build_pay_sn();
    return $pay_sn;
}

function check_order()
{
    $now = time();
    $order_list = \app\api\model\Order::where(['order_status' => 1, 'pay_status' => 0])->all();
    foreach ($order_list as $key => $value) {
        $timeout = $now - strtotime($value['confirm_time']);
        if ($timeout >= 1800) {
            \app\api\model\Order::get($value['id'])->save(['order_status' => 2]);
            if ($value['coupon_id']) {
                \app\api\model\UserCoupon::get($value['coupon_id'])->save(['use_status' => 0]);
            }
        }
    }
    return true;
}

function generate_user_token($userId, $deviceType, $type)
{
    $userTokenQuery = Db::name("user_token")
        ->where('user_id', $userId)
        ->where('type', $type)
        ->where('device_type', $deviceType);
    $findUserToken = $userTokenQuery->find();
    $currentTime = time();
    $expireTime = $currentTime + 24 * 3600 * 180;
    $token = md5(uniqid()) . md5(uniqid());
    if (empty($findUserToken)) {
        Db::name("user_token")->insert([
            'token' => $token,
            'user_id' => $userId,
            'type' => $type,
            'expire_time' => $expireTime,
            'create_time' => $currentTime,
            'device_type' => $deviceType
        ]);
    } else {
        if ($findUserToken['expire_time'] > time() && !empty($findUserToken['token'])) {
            $token = $findUserToken['token'];
        } else {
            Db::name("user_token")
                ->where('user_id', $userId)
                ->where('device_type', $deviceType)
                ->where('type', $type)
                ->update([
                    'token' => $token,
                    'expire_time' => $expireTime,
                    'create_time' => $currentTime
                ]);
        }

    }

    return $token;
}

function make_check_code()
{
    $check_code = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 4);;
    $count = \app\api\model\Order::where('check_code', $check_code)->count();
    ($count > 0) && $check_code = make_check_code();
    return $check_code;
}
