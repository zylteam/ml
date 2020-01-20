<?php


namespace app\api\model;


use think\Model;

class UserCoupon extends Model
{
    protected $name = 'user_coupon_info';

    protected $autoWriteTimestamp = "datetime";

    public function product()
    {
        return $this->belongsTo('app\admin\model\Product', 'product_id');
    }

    public function user()
    {
        return $this->belongsTo('UserModel', 'user_id');
    }

    public function order()
    {
        return $this->hasMany('Order', 'coupon_id');
    }
}