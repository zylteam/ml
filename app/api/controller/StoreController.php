<?php


namespace app\api\controller;


use app\admin\model\Store;
use app\api\model\Order;
use app\api\model\UserModel;
use app\user\model\SettlementLog;
use cmf\controller\RestBaseController;
use think\Db;
use think\Exception;

class StoreController extends RestBaseController
{
    protected $store_id = '';

    protected function initialize()
    {
//        $this->store_id = $this->getUserId();
    }

    /**
     * 获取店铺信息
     */
    public function get_store_info()
    {
        $store_info = Store::get($this->store_id);
        $store_info['logo'] = $this->host . $store_info['logo'];
        $this->success('获取店铺信息', ['store_info' => $store_info]);
    }

    /**
     * 更新店铺信息
     */
    public function update_store_info()
    {
        $data = $this->request->param();
//        $store_info = Store::get($data['store_id']);
//        $store_info[$data['field']] = $data['value'];
        $res = Store::where(['id' => $data['store_id']])->update([$data['field'] => $data['value']]);
        if ($res) {
            $this->success('修改成功', $res);
        } else {
            $this->error('修改失败', $res);
        }

    }

    /**
     * 扫码核销  获取核销信息
     */
    public function check_code()
    {
        $check_code = input('check_code') ? input('check_code') : '';
        if (!$check_code) {
            $this->error('未检测到可用核销码');
        }
        $order_info = Order::with('product,coupon,user_info')->where(['check_code' => $check_code, 'store_id' => $this->store_id])->find();
        if ($order_info) {
            if ($order_info['use_status'] == '待使用' && $order_info['pay_status'] == '已支付') {
                if ($order_info['product']['product_img']) {
                    $array = explode(',', $order_info['product']['product_img']);
                    $temp = array();
                    foreach ($array as $img) {
                        $temp[] = 'http://ml.0513app.cn/' . $img;
                    }
                    $order_info['product']['product_img'] = $temp;
                }
                $this->success('核销码可用', $order_info);
            } else {
                $this->error('核销码已使用');
            }
        } else {
            $this->error('核销码错误');
        }
    }

    public function confirm_check_code()
    {
        if ($this->request->isPost()) {
            Db::startTrans();
            try {
                $data = $this->request->param();
                $order_info = Order::with('product')
                    ->where('check_code', $data['check_code'])
                    ->find();
                if ($order_info) {
                    if ($order_info['pay_status'] == '已退款') {
                        throw  new  Exception('订单已退款');
                    }
                    if ($order_info['use_status'] == '已使用') {
                        throw  new  Exception('该核销码已使用');
                    }
                    $order_info->use_status = 2;
                    $order_info->use_time = date('Y-m-d H:i:s');
                    $order_info->save();

                    //结算记录
                    $log = new  SettlementLog();
                    $log->store_id = $order_info['store_id'];
                    $log->order_id = $order_info['id'];
                    $log->money = $order_info['pay_amount'];
                    $log->service_money = $order_info['product']['service_price'];
                    $log->status = 0;
                    $log->openid = $data['openid'];
                    $log->user_id = UserModel::where('user_openid', $data['openid'])->value('id');
                    $log->order_sn = $order_info['order_sn'];
                    $res = $log->save();
                    if ($res) {
                        Db::commit();
                    } else {
                        Db::rollback();
                        throw new Exception('核销失败');
                    }
                } else {
                    throw new Exception('订单不存在');
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success('核销成功');
        }
    }

    public function order_list($page = 1)
    {
        $data = $this->request->param();
        $store_id = $data['store_id'];
        $where = [];
        if (isset($data['keyword']) && $data['keyword']) {
            $where[] = ['order_sn', 'like', '%' . $data['keyword'] . '%'];
        }
        if (isset($data['start_time']) && $data['start_time'] && $data['end_time'] && isset($data['end_time'])) {
            $where[] = ['create_time', 'between time', [$data['start_time'], $data['end_time']]];
        }
        $num = 10;
        $order_list = Order::with('product,user_info')
            ->where('store_id', $store_id)
            ->where('order_status', '>', 0)
            ->where('pay_status', '>', 0)
            ->where($where)
            ->order('create_time desc')
            ->paginate($num, false, ['page' => $page])
            ->each(function ($item) {
                if ($item['product']['product_img']) {
                    $temp = array();
                    $array = explode(',', $item['product']['product_img']);
                    foreach ($array as $img) {
                        $temp[] = 'http://ml.0513app.cn/' . $img;
                    }
                    $item['product']['product_img_1'] = $temp[0];
                }
                return $item;
            });
        $this->success('', ['data' => $order_list]);
    }

    public function settlement_list($page = 1)
    {
        $data = $this->request->param();
        $where = [];
        if (isset($data['keyword']) && $data['keyword']) {
            $where[] = ['order_sn', 'like', '%' . $data['keyword'] . '%'];
        }
        if (isset($data['start_time']) && $data['start_time'] && $data['end_time'] && isset($data['end_time'])) {
            $where[] = ['create_time', 'between time', [$data['start_time'], $data['end_time']]];
        }
        $store_id = $data['store_id'];
        $num = 10;
        $settlement_list = SettlementLog::with('order_info')
            ->where('store_id', $store_id)
            ->where($where)
            ->order('create_time desc')
            ->paginate($num, false, ['page' => $page]);
        $this->success('', ['data' => $settlement_list]);
    }

    public function do_settlement()
    {
        $list = SettlementLog::with(['order_info', 'store_info'])
            ->where('status', 0)
            ->whereTime('create_time', 'yesterday')
            ->all();
        $list->visible(['store_info' => ['wx_openid']])->toArray();
        foreach ($list as $k => $v) {
            if ($v['money'] - $v['service_money'] > 0.5) {
                $params['order_sn'] = "PAY" . $v['order_sn'];
                $params['openid'] = $v['store_info']['wx_openid'];
                $params['money'] = $v['money'] - $v['service_money'];
                $res = hook_one('wechat_enterprise_transfer', $params);
                if ($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
                    SettlementLog::where('id', $v['id'])->update(['status' => 1, 'settlement_time' => date('Y-m-d H:i:s')]);
                } else {
                    SettlementLog::where('id', $v['id'])->update(['status' => 2, 'reason' => $res['err_code_des']]);
                }
            } else {
                SettlementLog::where('id', $v['id'])->update(['status' => 1, 'reason' => '订单金额不满足条件']);
            }

        }
        $this->success('', $list);
    }

    public function search()
    {
        $params['order_sn'] = 'PAY2019081998102514';
        $res =hook_one('wechat_search_order', $params);
        $this->success('', $res);
    }

}