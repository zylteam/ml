<?php


namespace app\admin\controller;


use app\admin\model\Officer;
use app\admin\model\Photo;
use cmf\controller\AdminBaseController;

class PhotoController extends AdminBaseController
{
    public function index()
    {
        return $this->fetch();
    }


    public function get_officer_by_id()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $num = 10;
            $page = 1;
            if (isset($data['page'])) {
                $page = $data['page'];
            }
            $officer = Officer::withCount('photos')->find($data['id']);
            $list = $officer->photos()->paginate($num, false, ['page' => $page]);
            $this->success('', null, ['list' => $list->items(), 'count' => $officer->photos_count]);
        }
    }

    public function get_all_officer()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $num = 10;
            $page = 1;
            if (isset($data['page'])) {
                $page = $data['page'];
            }
            $list = Officer::withCount('photos')
                ->where('is_delete', 0)
                ->paginate($num, false, ['page' => $page]);

            $this->success('', null, ['list' => $list->items(), 'count' => $list->total()]);
        }
    }

    public function update()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $photo = new Photo();
            $photo->img_src = $data['img_src'];
            $photo->officer_id = $data['officer_id'];
            $photo->remark = $data['remark'];
            $res = $photo->save();
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
            $photo = Photo::get($data['id']);
            if ($photo) {
                $res = Photo::destroy($data['id']);
                if ($res) {
                    $this->success('删除成功');
                }else{
                    $this->error('删除失败');
                }
            } else {
                $this->error('图片不存在');
            }
        }
    }
}