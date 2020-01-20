<?php


namespace app\admin\controller;

use app\admin\model\Product;
use app\admin\model\Store;
use think\Db;
use cmf\controller\AdminBaseController;

class ProductController extends AdminBaseController
{
    /**
     *新增或者修改 团品
     *
     */
    public function update()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'Product');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $info['product_name'] = $data['product_name'];
                $info['product_img'] = implode(',', $data['product_img']);
                $info['price'] = $data['price'];
                $info['limits'] = $data['limits'];
                $info['sale_price'] = $data['sale_price'];
                $info['service_price'] = $data['service_price'];
                $info['detail'] = $data['detail'];
                $info['store_id'] = $data['store_id'];
                if (isset($data['id']) && $data['id']) {
                    //更新
                    $info['update_time'] = time();
                    $res = Db::name('mass_product_info')->where('id', $data['id'])->update($info);
                } else {
                    //新增
                    $info['add_time'] = time();
                    $res = Db::name('mass_product_info')->insertGetid($info);
                }
                if ($res) {
                    $this->success('操作成功');
                } else {
                    $this->error('操作失败');
                }
            }
        }
    }

    public function list()
    {
        $store_id = input('store_id') ? input('store_id') : '';
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $page = 1;
            $num = 10;
            $where = [];
            if (isset($data['page'])) {
                $page = $data['page'];
            }
            if (isset($data['store_id']) && $data['store_id']) {
                $where[] = ['store_id', '=', $data['store_id']];
            }
            if (isset($data['keyword']) && $data['keyword']) {
                $where[] = ['product_name', 'like', '%'.$data['keyword'].'%'];
            }

            $list = Product::with(['store' => function ($query) {
                    $query->withField('store_name,id');
                }])
                ->withCount(['order' => function ($query) {
                    $query->where('pay_status', '>', 0);
                }, 'coupon'=>function($query){
                    $query->where('is_delete', '=', 0);
                }])
                ->where($where)
                ->where('is_delete',0)
                ->order('add_time desc')
                ->paginate($num, false, ['page' => $page]);
            $data = $list->items();
            $this->success('', null, [
                'list' => $data,
                'count' => $list->total(),
            ]);
        }
        $store_list = Store::where('is_show',0)->all();
        $this->assign('store_list',$store_list);
        $this->assign('store_id', $store_id);
        return $this->fetch();
    }

    public function change_status()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            if ($data['field'] == 'is_delete') {
                $coupon_count = Db::name('coupon_info')->where(['product_id' => $data['id'], 'is_delete' => 0])->count();
                if ($coupon_count > 0) {
                    $this->error('该团品还有未删除的优惠券');
                }
            }
            $res = Db::name('mass_product_info')->where('id', $data['id'])->update([$data['field'] => $data['value']]);
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败',null,$res);
            }
        }
    }
}