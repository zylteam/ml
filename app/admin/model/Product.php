<?php


namespace app\admin\model;


use think\Model;

class Product extends Model
{
    protected $name = 'mass_product_info';

    public function coupon()
    {
        return $this->hasMany('Coupon', 'product_id');
    }

    public function store()
    {
        return $this->belongsTo('Store','store_id','id');
    }

    public function user_coupon()
    {
        return $this->hasMany('UserCoupon', 'product_id');
    }

    public function order()
    {
        return $this->hasMany('app\api\model\Order', 'product_id');
    }


}