<?php


namespace app\admin\model;


use think\Model;

class Photo extends Model
{
    protected $name = "photo";
    protected $autoWriteTimestamp = "datetime";

    public function officer()
    {
        return $this->belongsTo('Officer');
    }
}