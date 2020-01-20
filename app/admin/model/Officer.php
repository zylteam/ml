<?php


namespace app\admin\model;


use think\Model;

class Officer extends Model
{
    protected $name = "officer";
    protected $autoWriteTimestamp = "datetime";

    public function officer_list($page = 1, $num = 10, $keyword = '')
    {
        $where = [];
        if ($keyword) {
            $where[] = ['o.nickname|o.real_name', 'like', "%" . $keyword . "%"];
        }
        $list = $this->alias('o')
            ->field('o.*,u.user_nickname')
            ->join('user u', "u.id = o.user_id", 'left')
            ->where($where)
            ->where('is_delete',0)
            ->order('is_recommend desc,is_check desc ,sort desc')
            ->paginate($num, false, ['page' => $page]);
        return $list;
    }

    public function photos()
    {
        return $this->hasMany('Photo', 'officer_id');
    }

    public function diaryList()
    {
        return $this->hasMany('Diary', 'officer_id');
    }

}