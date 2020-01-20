<?php


namespace app\admin\model;


use think\Model;

class CouponInfoModel extends Model
{
    protected $name = "coupon_info";
    public function product()
    {
        return $this->belongsTo('ProductModel','product_id');
    }
}