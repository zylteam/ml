<?php


namespace app\api\model;


use think\Model;

class UserCollection extends Model
{
    protected $name = 'user_collection';

    protected $autoWriteTimestamp = "datetime";

    public function diary()
    {
        return $this->belongsTo('app\admin\model\Diary','diary_id');
    }
    public function user()
    {
        return $this->belongsTo('UserModel','user_id');
    }

    public function officer()
    {
        return $this->belongsTo('app\admin\model\Officer','officer_id');
    }
}