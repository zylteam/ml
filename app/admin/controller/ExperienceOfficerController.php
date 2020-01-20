<?php


namespace app\admin\controller;

use app\admin\model\Officer;
use cmf\controller\AdminBaseController;

class ExperienceOfficerController extends AdminBaseController
{
    public function officer_list()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $keyword = '';
            $num = 10;
            $page = 1;
            if (isset($data['page'])) {
                $page = $data['page'];
            }
            if (isset($data['keyword'])) {
                $keyword = $data['keyword'];
            }
            $officer = new Officer();
            $data = $officer->officer_list($page, $num, $keyword);
            $this->success('', null, [
                'list' => $data->items(),
                'count' => $data->total()
            ]);
        }
        return $this->fetch('officer_list');
    }

    public function update()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'Officer');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if (isset($data['id']) && $data['id']) {
                    $officer = Officer::get((int)$data['id']);
                } else {
                    $officer = new Officer();
                    $officer->is_check = 1;
                }
                $officer->nickname = $data['nickname'];
                $officer->sex = $data['sex'];
                $officer->head_img = $data['head_img'];
                $officer->real_name = $data['real_name'];
                $officer->age = $data['age'];
                $officer->school = $data['school'];
                $officer->subject = $data['subject'];
                $officer->grade = $data['grade'];
                $officer->mobile = $data['mobile'];
                $res = $officer->save();
                if ($res) {
                    $this->success('更新成功');
                } else {
                    $this->success('更新失败');
                }
            }
        }
    }

    public function change_status()
    {
        if ($this->request->isAjax()) {
            $data = $this->request->param();
            $officer = Officer::get($data['id']);
            if ($data['field'] == 'is_check') {
                if ($officer['check_time'] == 0 && $data['value'] == 1) {
                    $update['check_time'] = time();
                } else {
                    $update['check_time'] = 0;
                }

            }
            $update[$data['field']] = $data['value'];
            $res = Officer::get($data['id'])->save($update);
            if ($res) {
                $this->success('修改成功', null, ['res' => $res]);
            } else {
                $this->error('修改失败');
            }
        }
    }

    public function list()
    {
        return $this->fetch('officer_list');
    }

    public function delete()
    {
        if ($this->request->isAjax()) {
            $id = input("id");
            $officer = Officer::get($id);
            $officer->is_delete = 1;
            $res = $officer->save();
            if ($res) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败', $res);
            }

        }
    }
}