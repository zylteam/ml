<?php


namespace app\admin\model;


use think\Model;

class AdvPosition extends Model
{
    protected $name = "adv_position";

    public function advlists()
    {
        return $this->hasMany('Advlist', 'adv_id');
    }
}