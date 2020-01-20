<?php


namespace app\admin\model;


use think\Model;

class Category extends Model
{
    protected $name = "category";
    protected $autoWriteTimestamp = "datetime";
}