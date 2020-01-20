<?php


namespace app\admin\validate;


use think\Validate;

class CouponValidate extends Validate
{
    protected $rule = [
        'name'       => 'require',
        'value1'             => 'require|float',
        'value2'       => 'require|float',
    ];

    protected $message = [
        'name.require'       => '优惠券名称不能为空',
        'value1.require' => '消费金额不能为空',
        'value1.float'     => '消费金额必须是数字',
        'value2.unique'     => '减免金额不能为空',
        'value2.float'     => '减免金额必须是数字',
    ];
}