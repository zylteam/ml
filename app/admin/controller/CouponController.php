<?php


namespace app\admin\controller;

use app\admin\model\Coupon;

use app\admin\model\Product;
use app\admin\model\Store;
use think\Db;
use cmf\controller\AdminBaseController;

/**
 * Class CouponController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   =>'优惠券管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 40,
 *     'icon'   =>'google-plus-official',
 *     'remark' =>'优惠券管理'
 * )
 */
class CouponController extends AdminBaseController
{
    /**
     * 优惠券列表
     * @adminMenu(
     *     'name'   => '优惠券列表',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 0,
     *     'icon'   => '',
     *     'remark' => '优惠券列表',
     *     'param'  => ''
     * )
     */
    public function list()
    {

        $product_id = input('product_id') ? input('product_id') : 0;
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $page = 1;
            $num = 10;
            $where[] = ['is_delete', '=', 0];
            if (isset($data['page'])) {
                $page = $data['page'];
            }
            if (isset($data['product_id']) && $data['product_id']) {
                $where[] = ['product_id', '=', $data['product_id']];
            }
            if (isset($data['store_id']) && $data['store_id']) {
                $where[] = ['store_id', '=', $data['store_id']];
            }
            if (isset($data['keyword']) && $data['keyword']) {
                $where[] = ['name', 'like', '%' . $data['keyword'] . '%'];
            }
            $list = Coupon::with(['store', 'product'])
                ->withCount('UserCoupon')
                ->where($where)
                ->order('add_time desc')
                ->paginate($num, false, ['page' => $page])
                ->each(function ($item) {
                    $item['use_time'] = [$item['start_time'], $item['end_time']];
                });
            $data = $list->items();
            $this->success('', null, [
                'list' => $data,
                'count' => $list->total(),
                'where' => $where
            ]);
        }
        $store_list = Store::where(['is_delete' => 0,'is_show'=>0])->all();
        $store_id = '';
        if ($product_id) {
            $store_id = Product::get($product_id)['store_id'];
        }
//        var_dump($store_list);
        $this->assign('store_list', $store_list);
        $this->assign('store_id', $store_id);
        $this->assign('product_id', $product_id);
        return $this->fetch();
    }

    public function update_coupon()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'Coupon');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if (isset($data['id']) && $data['id']) {
                    $coupon = Coupon::get($data['id']);
                } else {
                    $coupon = new Coupon;
                }
                $coupon->product_id = $data['product_id'];
                $coupon->store_id = $data['store_id'];
                $coupon->name = $data['name'];
                $coupon->detail = $data['detail'];
                $coupon->limit_count = $data['limit_count'];
                $coupon->value1 = $data['value1'];
                $coupon->value2 = $data['value2'];
                $coupon->add_time = time();
                $coupon->start_time = $data['use_time'][0];
                $coupon->end_time = $data['use_time'][1];
                $coupon->save();
                $res = $coupon->id;
                if (isset($data['id']) && $data['id']) {
                    $coupon->save();
                } else {

                    $coupon->save();

                }
                if ($res) {
                    $this->success('修改成功');
                } else {
                    $this->error('修改失败');
                }
            }
        }

    }

    public function change_status()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $res = Db::name('coupon_info')->where('id', $data['id'])->update([$data['field'] => $data['value']]);
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        }
    }

    public function get_all_store()
    {
        $store = new Store();
        $list = $store->select();
        return $list;
    }

    public function update()
    {
        $page = 1;
        $num = 10;
        $product = new Product();
        $entity = $product::with('coupon')->where(['is_delete' => 0])->paginate($num, false, ['page' => $page]);
        var_dump($entity);
    }

    public function get_all_product_by_id()
    {
        $id = input('id');
        $product = new Product();
        $list = $product->field('id,product_name')->where('store_id', $id)->select();
        return $list;
    }

    public function get_coupon_by_store_id()
    {
        $store_id = input('id');
        $list = Coupon::where(['store_id' => $store_id, 'is_delete' => 0, 'is_show' => 0])->all();
        return $list;
    }
}
