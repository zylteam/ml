<?php


namespace app\admin\controller;


use app\admin\model\Store;
use cmf\controller\AdminBaseController;
use think\Db;

class StoreController extends AdminBaseController
{
    public function list()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $where = [];
            $page = 1;
            $num = 10;
            if (isset($data['page'])) {
                $page = $data['page'];
            }
            if (isset($data['store_name']) && $data['store_name']) {
                $where[] = ['store_name', 'like', '%' . $data['store_name'] . '%'];
            }
            $storeList = Store::withCount(['product' => function ($query) {
                $query->where('is_delete', 0);
            }])
                ->where($where)
                ->order('add_time desc')
                ->paginate($num, false, ['page' => $page]);
            $data = $storeList->items();
            $this->success('', null, [
                'list' => $data,
                'count' => $storeList->total(),
                'where' => $where,
            ]);
        }
        return $this->fetch();
    }

    public function storeSubmit()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $info['store_name'] = $data['store_name'];
            $info['address'] = $data['address'];
            $info['phone'] = $data['phone'];
            $info['logo'] = $data['logo'];
            $info['login_name'] = $data['login_name'];
            $info['mapx'] = round($data['mapx'], 8);
            $info['mapy'] = round($data['mapy'], 8);
            $info['business_time'] = $data['business_time'];
            $validate_result = $this->validate($data, 'Store');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($data['id']) {
                    //修改
                    $password = Db::name('store')->where('id', $data['id'])->value('login_pass');
                    if ($password != $data['login_pass']) {
                        $info['login_pass'] = cmf_password($data['login_pass']);
                    }
                    $res = Db::name('store')->where(['id' => $data['id']])->update($info);
                } else {
                    //新增
                    $info['login_pass'] = cmf_password($data['login_pass']);
                    $info['add_time'] = time();
                    $res = Db::name('store')->insert($info);
                }
                if ($res) {
                    $this->success('操作成功');
                } else {
                    $this->error('操作失败');
                }
            }
        }
    }

    public function change_status()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $res = Db::name('store')->where('id', $data['id'])->update([$data['field'] => $data['value']]);
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        }
    }

    public function update()
    {
        return $this->fetch();
    }

    public function delete()
    {
        if ($this->request->isAjax()) {
            $store_id = input('store_id') ? input('store_id') : 0;
            $store_info = Store::get($store_id, ['product', 'coupon']);
            if ($store_info) {
                $res = $store_info->together('product,coupon')->delete();
                if ($res) {
                    $this->success('删除', null, $store_info);
                } else {
                    $this->error('删除失败');
                }

            } else {
                $this->error('店铺信息异常');
            }
        }
    }

}