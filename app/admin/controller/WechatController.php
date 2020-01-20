<?php


namespace app\admin\controller;


use app\api\model\Order;
use app\api\model\UserCoupon;
use cmf\controller\AdminBaseController;
use EasyWeChat\Factory;

class WechatController extends AdminBaseController
{
    public function notify()
    {

        $payment = hook_one('wechat_config');

        $app = Factory::payment($payment);
        var_dump($app);
        die();
        $response = $app->handlePaidNotify(function ($message, $fail) {
            // 你的逻辑
            $order = Order::where('pay_sn', $message['out_trade_no'])->find();
            if (!$order || $order['pay_status'] > 0) { // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }
            if ($message['return_code'] === 'SUCCESS') {
                if (array_get($message, 'result_code') === 'SUCCESS') {
                    $order->pay_time = date('Y-m-d H:i:s');
                    $order->pay_status = 1;
                    $order->money_paid = $message['total_fee'] / 100;
                    UserCoupon::get($order['coupon_id'])->save(['use_staus' => 2]);
                } else if (array_get($message, 'result_code') === 'FAIL') {
                    $order->pay_status = 4;
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }
            $order->save();
            return true;
        });
        $response->send(); // Laravel 里请使用：return $response;
    }
}