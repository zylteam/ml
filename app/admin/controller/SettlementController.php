<?php


namespace app\admin\controller;


use app\admin\model\Store;
use app\user\model\SettlementLog;
use cmf\controller\AdminBaseController;

class SettlementController extends AdminBaseController
{
    public function index($page = 1)
    {
        $store_list = Store::where(['is_delete' => 0, 'is_show' => 0])->all();
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $num = 10;
            $where = [];
            if (isset($data['store_id']) && $data['store_id']) {
                $where[] = ['store_id', '=', $data['store_id']];
            }

            if (isset($data['duration']) && $data['duration']) {
                $where[] = ['create_time', 'between', $data['duration']];
            }
            $list = SettlementLog::with(['store_info', 'order_info' => ['user_info', 'product', 'coupon'], 'user_info'])
                ->where($where)
                ->order('create_time desc')
                ->paginate($num, false, ['page' => $page]);
            $list->visible(['store_info' => ['store_name'], 'order_info' => ['user_info' => ['user_nickname', 'mobile']]])->toArray();
            $this->success('获取订单列表', null, [
                'list' => $list, 'where' => $where
            ]);
        }
        $this->assign('shopName', '脉鹿星选');
        $this->assign('store_list', $store_list);
        return $this->fetch();
    }

    public function do_settlement()
    {
        $id = input('get.id', 0);
        $info = SettlementLog::with(['order_info', 'store_info'])
            ->where(['status' => [0, 2], 'id' => $id])
            ->find();

        if (empty($info)) {
            $this->error('结算单异常');
        } else {
            if ($info['money'] - $info['service_money'] > 0.5) {
                $params['order_sn'] = "PAY" . $info['order_sn'];
                $params['openid'] = $info['store_info']['wx_openid'];
                $params['money'] = $info['money'] - $info['service_money'];
                $res = hook_one('wechat_enterprise_transfer', $params);
                if ($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
                    SettlementLog::where('id', $info['id'])->update(['status' => 1, 'settlement_time' => date('Y-m-d H:i:s')]);
                } else {
                    SettlementLog::where('id', $info['id'])->update(['status' => 2, 'reason' => $res['err_code_des']]);
                }
            } else {
                SettlementLog::where('id', $info['id'])->update(['status' => 1, 'reason' => '订单金额不满足条件']);
            }
            $this->success('结算成功');
        }
    }

    public function get_settlement_status()
    {
        $id = input('get.id', 0);
        $info = SettlementLog::with(['order_info', 'store_info'])
            ->where(['id' => $id])
            ->find();
        $info['order_sn'] = 'PAY' . $info['order_sn'];
        $res = hook('wechat_search_order', $info);
        $this->success('', '', $res);
    }
}