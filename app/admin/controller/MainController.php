<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\api\model\UserModel;
use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\Menu;

class MainController extends AdminBaseController
{

    /**
     *  后台欢迎页
     */
    public function index()
    {
        $dashboardWidgets = [];
        $widgets          = cmf_get_option('admin_dashboard_widgets');

        $defaultDashboardWidgets = [
            '_SystemCmfHub'           => ['name' => 'CmfHub', 'is_system' => 1],
            '_SystemCmfDocuments'     => ['name' => 'CmfDocuments', 'is_system' => 1],
            '_SystemMainContributors' => ['name' => 'MainContributors', 'is_system' => 1],
            '_SystemContributors'     => ['name' => 'Contributors', 'is_system' => 1],
            '_SystemCustom1'          => ['name' => 'Custom1', 'is_system' => 1],
            '_SystemCustom2'          => ['name' => 'Custom2', 'is_system' => 1],
            '_SystemCustom3'          => ['name' => 'Custom3', 'is_system' => 1],
            '_SystemCustom4'          => ['name' => 'Custom4', 'is_system' => 1],
            '_SystemCustom5'          => ['name' => 'Custom5', 'is_system' => 1],
        ];

        if (empty($widgets)) {
            $dashboardWidgets = $defaultDashboardWidgets;
        } else {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    $dashboardWidgets['_System' . $widget['name']] = ['name' => $widget['name'], 'is_system' => 1];
                } else {
                    $dashboardWidgets[$widget['name']] = ['name' => $widget['name'], 'is_system' => 0];
                }
            }

            foreach ($defaultDashboardWidgets as $widgetName => $widget) {
                $dashboardWidgets[$widgetName] = $widget;
            }
        }

        $dashboardWidgetPlugins = [];

        $hookResults = hook('admin_dashboard');

        if (!empty($hookResults)) {
            foreach ($hookResults as $hookResult) {
                if (isset($hookResult['width']) && isset($hookResult['view']) && isset($hookResult['plugin'])) { //验证插件返回合法性
                    $dashboardWidgetPlugins[$hookResult['plugin']] = $hookResult;
                    if (!isset($dashboardWidgets[$hookResult['plugin']])) {
                        $dashboardWidgets[$hookResult['plugin']] = ['name' => $hookResult['plugin'], 'is_system' => 0];
                    }
                }
            }
        }

        $smtpSetting = cmf_get_option('smtp_setting');

        $this->assign('dashboard_widgets', $dashboardWidgets);
        $this->assign('dashboard_widget_plugins', $dashboardWidgetPlugins);
        $this->assign('has_smtp_setting', empty($smtpSetting) ? false : true);

        $session_admin_id = session('ADMIN_ID');

        $this->assign('session_admin_id',json_encode($session_admin_id));

        //今日会员增长
        $all_user_count = UserModel::where('user_type',2)->count();
        $this->assign('all_user_count',$all_user_count);



        return $this->fetch();
    }

    public function user_count()
    {
        $data = $this->request->param();

        $year  = date("Y",time());//年 2019

        $month = date("m",time());//当前月

        $ymd = date('Y-m-d');


        if (isset($data['date']) && $data['date'] != '') {
            $year  = date("Y",$data['date']/1000);
            $month = date("m",$data['date']/1000);
            $ymd = date('Y-m-d',$data['date']/1000);
        }

        $dayList = get_day($ymd,'2');
        $new_arr = [];
        $m_list  = [];
        for ($i = 0;$i < count($dayList);$i++) {
            array_push($new_arr,0);
            $m_list[$i+1] = $dayList[$i];
        }

        $m_count = array_combine($dayList,$new_arr);

        $where = [];


        if ($data['user_type'] != '') {
            $where['user_type'] = array('eq',$data['user_type']);
        }

        if ($data['user_status'] != '') {
            $where['user_status'] = array('eq',$data['user_status']);
        }

        $result = Db::name('user')
            ->where('user_type','in','2,3,4')
            ->where($where)
            ->where("DATE_FORMAT(from_unixtime(create_time),'%Y') = $year")
            ->where("DATE_FORMAT(from_unixtime(create_time),'%m') = $month")
            ->field("count(id) as total,DATE_FORMAT(from_unixtime(create_time),'%d') as time")
            ->group("DATE_FORMAT(from_unixtime(create_time),'%d')")
            ->select()->all();

        //dump($result);die;

        foreach($m_count as $k=>&$v){
            foreach($result as $row){
                $len = strlen($row['time']);
                //dump($len);
                if ($len > 1) {
                    $font_top  = substr( $row['time'], 0, 1 );
                    $font_last = substr($row['time'],1,1);
//                    dump($font_top);
//                    dump($font_last);
                    if ($font_top == 0) {
                        if ($m_list[$font_last] == $k) {
                            $v = $row['total'];
                        }
                    } else {
                        if($m_list[$row['time']] == $k){
                            $v = $row['total'];
                        }
                    }
                }

            }
        }


        $label = array_keys($m_count);

        foreach ($label as $k =>$v) {
            $shuzu = explode('-',$v);
            //dump($shuzu);
            $label[$k] = $shuzu[1].'-'.$shuzu[2];
            //$label[$k] = $shuzu[2];
        }

        $value = array_values($m_count);

        $this->success('', null, [
            'label'  =>$label,
            'number' =>$value,
        ]);

    }


    public function order_count()
    {
        $data = $this->request->param();

        $year  = date("Y",time());//年 2019

        $month = date("m",time());//当前月

        $ymd = date('Y-m-d');

        if (isset($data['date']) && $data['date'] != '') {
            $year  = date("Y",$data['date']/1000);
            $month = date("m",$data['date']/1000);
            $ymd = date('Y-m-d',$data['date']/1000);
        }

        $dayList = get_day($ymd,'2');
        $new_arr = [];
        $m_list  = [];
        for ($i = 0;$i < count($dayList);$i++) {
            array_push($new_arr,0);
            $m_list[$i+1] = $dayList[$i];
        }

        $m_count = array_combine($dayList,$new_arr);



        //dump($m_count);die;

        $day = array_keys($m_count);

        foreach ($day as $k =>$v) {
            $shuzu = explode('-',$v);
            //dump($shuzu);
            $day[$k] = $shuzu[1].'-'.$shuzu[2];
            //$day[$k] = $shuzu[2];
        }
        $price = array_values($m_count);

        $this->success('', null, [
            'day'  =>$day,
            'price' =>$price,
        ]);


    }



    public function dashboardWidget()
    {
        $dashboardWidgets = [];
        $widgets          = $this->request->param('widgets/a');
        if (!empty($widgets)) {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 1]);
                } else {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 0]);
                }
            }
        }

        cmf_set_option('admin_dashboard_widgets', $dashboardWidgets, true);

        $this->success('更新成功!');

    }

}
