<?php


namespace app\user\model;


use think\Model;

class SettlementLog extends Model
{
    protected $name = 'store_settlement_log';

    protected $autoWriteTimestamp = "datetime";

    public function storeInfo()
    {
        return $this->belongsTo('app\admin\model\Store', 'store_id');
    }

    public function orderInfo()
    {
        return $this->belongsTo('app\api\model\Order', 'order_id');
    }

    public function product()
    {
        return $this->belongsTo('app\admin\model\Product', 'product_id');
    }

    public function userInfo()
    {
        return $this->belongsTo('app\admin\model\UserModel', 'user_id');
    }

    public function getStatusAttr($value)
    {
        $status = [0 => '待结算', 1 => '已结算', 2 => '待审核'];
        return $status[$value];
    }

}