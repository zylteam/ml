<?php


namespace app\api\model;


use think\Model;

class UserModel extends Model
{
    public function collection()
    {
        return $this->hasMany('UserCollection','user_id');
    }

    public function order_list()
    {
        return $this->hasMany('Order','user_id');
    }

    public function user_coupons()
    {
        return $this->hasMany('UserCoupon','user_id');
    }
}