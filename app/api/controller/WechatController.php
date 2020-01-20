<?php


namespace app\api\controller;

use app\api\model\Order;
use app\api\model\UserCoupon;
use EasyWeChat\Factory;
use think\facade\Log;
use think\Db;

class WechatController extends BaseController
{

    public function wechat_notify()
    {
        $payment = hook_one('wechat_config');
        $app = Factory::payment($payment);
        $response = $app->handlePaidNotify(function ($message, $fail) {
            // 你的逻辑
            $order = Order::where('pay_sn', $message['out_trade_no'])->find();
            if (!$order || $order['pay_status'] > 0) { // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }
            if ($message['return_code'] === 'SUCCESS') {
                if ($message['result_code'] === 'SUCCESS') {
                    $order->pay_time = date('Y-m-d H:i:s');
                    $order->pay_status = 1;
                    $order->use_status = 1;
                    $order->money_paid = $message['total_fee'] / 100;
                    $order->check_code = make_check_code();
                    UserCoupon::get($order['coupon_id'])->save(['use_status' => 2, 'use_time' => date('Y-m-d H:i:s')]);
                } else if ($message['result_code'] === 'FAIL') {
                    $order->pay_status = 4;
                }
            } else {
                return $fail('通信失败，请稍后再通知我');
            }
            $order->save();
            return true;
        });
        $response->send();
    }


}