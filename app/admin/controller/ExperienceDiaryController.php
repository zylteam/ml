<?php


namespace app\admin\controller;


use app\admin\model\Attribute;
use app\admin\model\Category;
use app\admin\model\Diary;
use app\admin\model\Officer;
use app\admin\model\Store;
use cmf\controller\AdminBaseController;

class ExperienceDiaryController extends AdminBaseController
{
    public function index()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $keyword = '';
            $num = 10;
            $page = 1;
            $where = [];
            if (isset($data['page'])) {
                $page = $data['page'];
            }
            if (isset($data['keyword']) && $data['keyword']) {
                $keyword = $data['keyword'];
                $where[] = ['title|sub_title|store_name|store_address|cuisine', 'like', "%$keyword%"];
            }

            $diary = new Diary();
            $list = $diary->alias('d')
                ->field("d.*,o.nickname")
                ->join("officer o", "o.id = d.officer_id")
                ->where(['d.is_delete' => 0])
                ->where($where)
                ->order('d.is_recommend desc,d.sort desc,d.create_time desc')
                ->paginate($num, false, ['page' => $page])
                ->each(function ($item) {
                    if ($item['category']) {
                        $item['category'] = explode(',', $item['category']);
                    }
                    if ($item['attribute']) {
                        $item['attribute'] = explode(',', $item['attribute']);
                    }
                    if ($item['cuisine']) {
                        $item['cuisine'] = explode(',', $item['cuisine']);
                    }
                    if ($item['people']) {
                        $item['people'] = explode(',', $item['people']);
                    }
                    $item['menu'] = unserialize($item['menu']);
                    return $item;
                });
            $data = $list->items();
            $this->success('', null, [
                'list' => $data,
                'count' => $list->total()
            ]);
        }
        return $this->fetch();
    }

    public function update()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            if (isset($data['id']) && $data['id']) {
                $diary = Diary::get((int)$data['id']);
            } else {
                $diary = new Diary();
            }
            if (!empty($data['attribute'])) {
                $diary->attribute = implode(",", $data['attribute']);
            }
            if (!empty($data['category'])) {
                $diary->category = implode(",", $data['category']);
            }
            if (!empty($data['people'])) {
                $diary->people = implode(",", $data['people']);
            }
            if (!empty($data['cuisine'])) {
                $diary->cuisine = implode(",", $data['cuisine']);
            }
            $diary->sub_title = $data['sub_title'];
            $diary->menu = serialize($data['menu']);
            $diary->consume_amount = $data['consume_amount'];
            $diary->detail = $data['detail'];
            $diary->disagree = $data['disagree'] ? $data['disagree'] : 0;
            $diary->agree = $data['agree'] ? $data['agree'] : 0;
            $diary->eat_dishes = $data['eat_dishes'];
            $diary->store_address = $data['store_address'];
            $diary->store_id = $data['store_id'];
            $diary->store_name = $data['store_name'];
            $diary->title = $data['title'];
            $diary->video = $data['video'];
            $diary->cover_pic = $data['cover_pic'];
            $diary->has_welfare = isset($data['has_welfare']) ? $data['has_welfare'] : 0;
            if($diary->has_welfare == 0){
                $diary->coupon_id= 0;
            }else{
                $diary->coupon_id = isset($data['coupon_id']) ? $data['coupon_id'] : 0;
            }

            $diary->officer_id = $data['officer_id'];
            $res = $diary->save();
            if ($res) {
                $this->success('更新成功');
            } else {
                $this->success('更新失败');
            }
        }
    }

    public function get_select_data()
    {
        $store_list = Store::where(['is_delete' => 0, 'is_show' => 0])->select();
        $officer_list = Officer::where(['is_check' => 1])->select();
        $result['category_list'] = Category::where('type', 1)->all();
        $result['attribute_list'] = Category::where('type', 2)->all();
        $result['people_list'] = Category::where('type', 3)->all();
        $result['cuisine_list'] = Category::where('type', 4)->all();
        $result['store_list'] = $store_list;
        $result['officer_list'] = $officer_list;
        return $result;
    }


    public function change_status()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $diary = new Diary();
            $res = $diary::get($data['id'])->save([$data['field'] => $data['value']]);
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
            $id = input("id");
            $diary = Diary::get($id);
            $diary->is_delete = 1;
            $res = $diary->save();
            if ($res) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败', $res);
            }

        }
    }

}