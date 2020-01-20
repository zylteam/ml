<?php


namespace app\api\model;


use think\Model;
class Order extends Model
{
    protected $name = 'order_info';
    protected $autoWriteTimestamp = "datetime";
    public function userInfo()
    {
        return $this->belongsTo('app\api\model\UserModel', 'user_id');
    }

    public function product()
    {
        return $this->belongsTo('app\admin\model\Product', 'product_id');
    }

    public function storeInfo()
    {
        return $this->belongsTo('app\admin\model\Store', 'store_id');
    }

    public function coupon()
    {
        return $this->belongsTo('UserCoupon', 'coupon_id');
    }

    public function scopeOrderStatus($query)
    {
        $query->where('order_status', '>', 0);
    }

    public function getOrderStatusAttr($value)
    {
        $status = [0 => '未确认', 1 => '已确认', 2 => '已取消'];
        return $status[$value];
    }

    public function getPayStatusAttr($value)
    {
        $status = [0 => '未支付', 1 => '已支付', 2 => '已退款'];
        return $status[$value];
    }

    public function getUseStatusAttr($value)
    {
        $status = [0 => '未购买', 1 => '待使用', 2 => '已使用'];
        return $status[$value];
    }

}