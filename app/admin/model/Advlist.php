<?php


namespace app\admin\model;


use think\Model;

class Advlist extends Model
{
    protected $name = "advlist";

    public function position()
    {
        return $this->belongsTo('AdvPosition','adv_id');
    }
}