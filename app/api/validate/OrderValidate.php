<?php


namespace app\api\validate;


use think\Validate;

class OrderValidate extends Validate
{
    protected $rule = [
        'real_name'       => 'require',
        'mobile'            => 'require',
        'age'       => 'require',
        'school'       => 'require',
        'subject'     => 'require',
        'grade'     => 'require',
    ];

    protected $message = [
        'mobile.unique'       => '手机号码不能为空',
        'real_name.require'     => '真实姓名不能为空',
        'age.require'     => '年龄不能为空',
        'school.require'     => '学校不能为空',
        'subject.require'     => '专业不能为空',
        'grade.require'     => '年级不能为空'
    ];
}