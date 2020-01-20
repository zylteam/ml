<?php


namespace app\user\Login;


use cmf\controller\HomeBaseController;

class Login extends HomeBaseController
{
    public function index()
    {
        return $this->fetch();
    }
}