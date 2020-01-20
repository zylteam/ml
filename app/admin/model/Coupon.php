<?php


namespace app\admin\model;


use think\Model;

class Coupon extends Model
{
    protected $name = "coupon_info";
    

    public function product()
    {
        return $this->belongsTo('Product','product_id');
    }

    public function store()
    {
        return $this->belongsTo('Store','store_id');
    }

    public function userCoupon()
    {
        return $this->hasMany('app\api\model\UserCoupon','coupon_id');
    }
}