<?php


namespace app\api\model;


use think\Model;

class RecommendDiary extends Model
{
    protected $name = 'recommend_diary_log';
    
    protected $autoWriteTimestamp = "datetime";
}