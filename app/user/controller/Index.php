<?php


namespace app\user\Index;


use cmf\controller\HomeBaseController;

class Index extends HomeBaseController
{
    public function index()
    {
        return $this->fetch();
    }
}