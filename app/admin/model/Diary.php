<?php


namespace app\admin\model;


use think\Model;

class Diary extends Model
{
    protected $name = "diary";
    protected $autoWriteTimestamp = "datetime";

    public function officer()
    {
        return $this->belongsTo('Officer', 'officer_id');
    }

    public function coupon()
    {
        return $this->belongsTo('Coupon', 'coupon_id');
    }

    public function usercollection()
    {
        return $this->hasMany('app\api\model\UserCollection');
    }
}