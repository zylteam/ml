<?php

namespace app\admin\validate;
use think\Validate;

class StoreValidate extends Validate
{
    protected $rule = [
        'store_name'       => 'require|unique:Store',
        'address'          => 'require',
        'phone'            => 'require',
        'logo'             => 'require',
        'login_name'       => 'require|unique:Store',
        'login_pass'       => 'require',
    ];

    protected $message = [
        'store_name.require'       => '店铺不能为空',
        'store_name.unique'       => '店铺已存在',
        'address.require'        => '店铺地址不能为空',
        'phone.require'          => '联系电话不能为空',
        'logo.require' => '请上传店铺logo',
        'login_name.require'     => '用户名不能为空',
        'login_name.unique'     => '用户名已存在',
        'login_pass.require'     => '登录密码不能为空',
    ];
}