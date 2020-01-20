<?php


namespace app\admin\model;


use think\Model;

class ProductModel extends Model
{
    protected $name = 'mass_product_info';
    public function coupon()
    {
        return $this->hasMany('CouponInfoModel','product_id');
    }

    public function store()
    {
        return $this->belongsTo('store');
    }
}