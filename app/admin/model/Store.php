<?php


namespace app\admin\model;


use think\Model;

class Store extends Model
{
    protected $name = 'store';

    public function product()
    {
        return $this->hasMany('Product', 'store_id');
    }

    public function orders()
    {
        return $this->hasMany('app\api\model\Order','store_id');
    }

    public function logs()
    {
        return $this->hasMany('app\user\model\SettlementLog','store_id');
    }

    public function coupon()
    {
        return $this->hasMany('Coupon','store_id');
    }
}