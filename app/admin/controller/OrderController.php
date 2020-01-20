<?php


namespace app\admin\controller;


use app\admin\model\Store;
use app\api\model\Order;
use cmf\controller\AdminBaseController;

class OrderController extends AdminBaseController
{
    public function order_list()
    {
        $store_list = Store::where(['is_delete' => 0, 'is_show' => 0])->order('id desc')->all();
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $num = 10;
            $page = isset($data['page']) && $data['page'] ? $data['page'] : 1;
            $where = [];
            if (isset($data['store_id']) && $data['store_id']) {
                $where[] = ['store_id', '=', $data['store_id']];
            }
            if (isset($data['duration']) && $data['duration']) {
                $where[] = ['confirm_time', 'between', $data['duration']];
            }
            $order_list = Order::with(['coupon', 'product', 'user_info', 'store_info'])
                ->where($where)
                ->order('create_time desc')
                ->paginate($num, false, ['page' => $page]);
            $order_list->visible(['store_info' => ['store_name']])->toArray();
            $this->success('获取订单列表', null, [
                'list' => $order_list, 'where' => $where
            ]);
        }
        $this->assign('shopName', '脉鹿星选');
        $this->assign('store_list', $store_list);
        return $this->fetch();
    }
}