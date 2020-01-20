<?php


namespace app\admin\validate;


use think\Validate;

class ProductValidate extends Validate
{
    protected $rule = [
        'product_name'       => 'require|unique:mass_product_info',
        'product_img'          => 'require',
        'price'            => 'require|float',
        'sale_price'             => 'require|float',
        'service_price'       => 'require|float',
    ];

    protected $message = [
        'product_name.require'       => '团品名称不能为空',
        'product_name.unique'       => '团品名称已被占用',
        'price.require'        => '团品原价不能为空',
        'price.float'          => '团品原价必须是数字',
        'sale_price.require' => '团品售价不能为空',
        'sale_price.float'     => '团品售价必须是数字',
        'service_price.unique'     => '服务费不能为空',
        'service_price.float'     => '服务费必须是数字',
    ];
}