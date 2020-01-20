<?php


namespace app\api\validate;


use think\Validate;

class StoreValidate extends Validate
{
    protected $rule = [
        'store_name' => 'require|unique:Store',
        'address' => 'require',
        'phone' => 'require',
        'logo' => 'require',
    ];

    protected $message = [
        'store_name.require' => '店铺不能为空',
        'store_name.unique' => '店铺已存在',
        'address.require' => '店铺地址不能为空',
        'phone.require' => '联系电话不能为空',
        'logo.require' => '请上传店铺logo',
    ];
}