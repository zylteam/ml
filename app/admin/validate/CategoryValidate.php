<?php


namespace app\admin\validate;


use think\Validate;

class CategoryValidate extends Validate
{
    protected $rule = [
        'name'       => 'require|unique:category',
    ];

    protected $message = [
        'name.require'       => '标签名不能为空',
        'name.unique' => '该标签名已存在'
    ];
}