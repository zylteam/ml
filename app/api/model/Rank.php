<?php


namespace app\api\model;


use think\Model;

class Rank extends Model
{
    protected $name = 'diary_rank';

    protected $autoWriteTimestamp = "datetime";
}