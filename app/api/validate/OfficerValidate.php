<?php

namespace app\api\validate;
use think\Validate;

class OfficerValidate extends Validate
{
    protected $rule = [
//        'nickname'       => 'require|unique:officer',
//        'sex'       => 'require',
//        'head_img'       => 'require',
        'real_name'       => 'require',
        'mobile'            => 'require',
        'age'       => 'require',
        'school'       => 'require',
        'subject'     => 'require',
        'grade'     => 'require',
    ];

    protected $message = [
//        'nickname.require'       => '昵称不能为空',
//        'nickname.unique'       => '昵称已被使用',
        'mobile.unique'       => '手机号码不能为空',
//        'sex.require' => '性别不能为空',
//        'head_img.require'     => '请上传头像',
        'real_name.require'     => '真实姓名不能为空',
        'age.require'     => '年龄不能为空',
        'school.require'     => '学校不能为空',
        'subject.require'     => '专业不能为空',
        'grade.require'     => '年级不能为空'
    ];
}