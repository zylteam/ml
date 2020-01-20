<?php


namespace app\admin\controller;


use app\admin\model\Category;
use cmf\controller\AdminBaseController;

class CategoryController extends AdminBaseController
{
    public function update()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'Category');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $category = new  Category();
                $category->name = $data['name'];
                $category->type = $data['type'];
                $res = $category->save();
                if ($res) {
                    $list = Category::where('type', $data['type'])->all();
                    $this->success('修改成功', null, ['list' => $list]);
                } else {
                    $this->error('修改失败');
                }
            }

        }
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $num = 15;
            $page = 1;
            if (isset($data['page'])) {
                $page = $data['page'];
            }
            $where = [];
            if (isset($data['category_id']) && $data['category_id']) {
                $where['type'] = $data['category_id'];
            }
            $list = Category::where($where)->order('sort desc')->paginate($num, false, ['page' => $page]);
            $this->success('', null, ['list' => $list->items(), 'count' => $list->total()]);
        }
        return $this->fetch();
    }

    public function change_status()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $category = new Category();
            $res = $category::get($data['id'])->save([$data['field'] => $data['value']]);
            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        }
    }

    public function delete()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $category = new Category();
            $res = $category::destroy($data['id']);
            if ($res) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }
    }

}