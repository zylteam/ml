<?php
namespace plugins\wechat\controller;


use cmf\controller\PluginRestBaseController;

class ConfigController extends PluginRestBaseController
{
    
    public function get_wechat_config()
    {
        return $this->getPlugin()->getConfig();
    }
}