<?php


namespace app\api\controller;


use app\admin\logic\HookLogic;
use app\admin\model\Advlist;
use app\admin\model\Category;
use app\admin\model\Coupon;
use app\admin\model\Diary;
use app\admin\model\Officer;

use app\admin\model\Product;
use app\api\model\Order;
use app\api\model\UserCoupon;
use app\api\model\UserModel;
use EasyWeChat\Factory;
use think\cache\driver\Redis;
use think\Db;

class IndexController extends BaseController
{

    public function index()
    {
        $adv_list = Advlist::where(['adv_id' => 1, 'status' => 1])
            ->order('sort desc')
            ->select()
            ->each(function ($item) {
                $item['img'] = $this->host . $item['img'];
                return $item;
            });
        $officer_list = Officer::where(['is_check' => 1, 'is_recommend' => 1]) //'is_recommend' => 1
        ->field('id,nickname,head_img')
            ->order('sort desc,is_recommend desc')
            ->select()
            ->each(function ($item) {
                if (!stristr($item['head_img'], 'https://')) {
                    $item['head_img'] = $this->host . $item['head_img'];
                }
                return $item;
            });
        $this->success('index获取', ['adv_list' => $adv_list, 'officer_list' => $officer_list]);
    }

    public function order_list()
    {
        $order_list = check_order();
        $this->success('index获取', ['order_list' => $order_list]);
    }

    public function get_diary_list()
    {
        $data = $this->request->param();
        $where = [];
        $keyword = isset($data['keyword']) && $data['keyword'] ? $data['keyword'] : '';
        if ($keyword) {
            $where[] = ['title|sub_title|store_name|store_address|cuisine', 'like', "%$keyword%"];
        }
        $category = isset($data['category']) && $data['category'] ? $data['category'] : '';
        if ($category) {
            $where[] = ['', 'exp', Db::raw("FIND_IN_SET('$category',category)")];
        }
        $is_recommend = isset($data['is_recommend']) && $data['is_recommend'] ? $data['is_recommend'] : 0;
        if ($is_recommend) {
            $where[] = ['is_recommend', '=', $is_recommend];
        }
        $page = isset($data['page']) && $data['page'] ? $data['page'] : 1;
        $num = isset($data['num']) && $data['num'] ? $data['num'] : 20;
        $list = Diary::with('officer')
            ->field('id,cover_pic,title,sub_title,officer_id,agree,disagree,attribute')
            ->order('is_recommend desc,sort desc,create_time desc')
            ->where($where)
            ->where('is_delete', '=', 0)
            ->paginate($num, false, ['page' => $page])
            ->each(function ($item) {
                if ($item['cover_pic']) {
                    $item['cover_pic'] = $this->host . $item['cover_pic'];
                }
                if (!stristr($item['officer']['head_img'], 'https://')) {
                    $item['officer']['head_img_1'] = $this->host . $item['officer']['head_img'];
                }
                if ($item['attribute']) {
                    $item['attribute'] = explode(',', $item['attribute']);
                }
                if ($item['disagree'] == $item['agree']) {
                    $item['agree'] = 50;
                    $item['disagree'] = 50;
                } else {
                    if ($item['agree'] > $item['disagree']) {
                        $item['agree'] = round($item['agree'] / ($item['agree'] + $item['disagree']), 2) * 100;
                        $item['disagree'] = 100 - $item['agree'];
                    } else {
                        $item['disagree'] = round($item['disagree'] / ($item['agree'] + $item['disagree']), 2) * 100;
                        $item['agree'] = 100 - $item['disagree'];
                    }
                }
                return $item;
            });
        $list->visible(['officer' => ['nickname', 'head_img_1']])->toArray();
        $this->success('日记列表', ['diary_list' => $list]);
    }

    public function get_diary_by_id($id)
    {
        $openid = input('openid') ? input('openid') : '';

        $diary_info = Diary::with(['officer', 'coupon' => ['product']])->get($id);
        if ($openid) {
            $user_id = UserModel::where('user_openid', $openid)->column('id');
            $list = $diary_info->usercollection()->where(['user_id' => $user_id])->select();
            if (count($list) > 0) {
                $diary_info['is_collection'] = true;
            } else {
                $diary_info['is_collection'] = false;
            }
        } else {
            $diary_info['is_collection'] = false;
        }
        $diary_info->visible(['officer' => ['nickname', 'head_img'], 'coupon' => ['name', 'id', 'end_time', 'value1', 'value2', 'product' => ['product_name', 'product_img']]])->toArray();
        $diary_info['category'] = explode(',', $diary_info['category']);
        $diary_info['attribute'] = explode(',', $diary_info['attribute']);
        $diary_info['cuisine'] = explode(',', $diary_info['cuisine']);
        $diary_info['people'] = explode(',', $diary_info['people']);
        $diary_info['detail'] = preg_replace_callback('/<[img|IMG].*?src=[\'| \"](?![http|https])(.*?(?:[\.gif|\.jpg]))[\'|\"].*?[\/]?>/', function ($r) {
            $str = $this->host . $r[1];
            return str_replace($r[1], $str, $r[0]);
        }, $diary_info['detail']);
        if ($diary_info['coupon']['product']['product_img']) {
            $array = explode(',', $diary_info['coupon']['product']['product_img']);
            $temp = array();
            foreach ($array as $img) {
                $temp[] = $this->host . $img;
            }
            $diary_info['coupon']['product']['product_img'] = $temp;
        }
        if ($diary_info['cover_pic']) {
            $diary_info['cover_pic'] = $this->host . $diary_info['cover_pic'];
        }
        if ($diary_info['video']) {
            $diary_info['video'] = $this->host . $diary_info['video'];
        }
        if (!stristr($diary_info['officer']['head_img'], 'https://')) {
            $diary_info['officer']['head_img'] = $this->host . $diary_info['officer']['head_img'];
        }
        $diary_info['menu'] = unserialize($diary_info['menu']);
        $list = Diary::with(['officer'])
            ->field('id,cover_pic,title,sub_title,officer_id,agree,disagree,attribute')
            ->where(["is_recommend" => 1, 'is_delete' => 0])
            ->orderRaw('rand()')
            ->limit(2)
            ->select()
            ->each(function ($item) {
                if ($item['cover_pic']) {
                    $item['cover_pic'] = $this->host . $item['cover_pic'];
                }
                if (!stristr($item['officer']['head_img'], 'https://')) {
                    $item['officer']['head_img_1'] = $this->host . $item['officer']['head_img'];
                }
                if ($item['attribute']) {
                    $item['attribute'] = explode(',', $item['attribute']);
                }
                if ($item['disagree'] == $item['agree']) {
                    $item['agree'] = 50;
                    $item['disagree'] = 50;
                } else {
                    if ($item['agree'] > $item['disagree']) {
                        $item['agree'] = round($item['agree'] / ($item['agree'] + $item['disagree']), 2) * 100;
                        $item['disagree'] = 100 - $item['agree'];
                    } else {
                        $item['disagree'] = round($item['disagree'] / ($item['agree'] + $item['disagree']), 2) * 100;
                        $item['agree'] = 100 - $item['disagree'];
                    }
                }
                return $item;
            });
        $list->visible(['officer' => ['nickname', 'head_img_1']])->toArray();
        $this->success('日记详细', ['diary_info' => $diary_info, 'recommend_list' => $list]);
    }

    public function get_category($id)
    {
        $list = Category::where('type', $id)->all();
        $this->success('获取标签', ['list' => $list]);
    }

    public function get_product_by_id($id)
    {
        $product_info = Product::get($id);
        if ($product_info) {
            $this->success('获取团品信息', ['product_info' => $product_info]);
        } else {
            $this->error('该团品不存在或已下架');
        }
    }

    public function get_couponInfo_by_id($id)
    {
        $coupon_info = Coupon::with(['store' => function ($query) {
            $query->field('id,address,logo,store_name,business_time,mapx,mapy');
        }, 'product' => function ($query) {
            $query->field('id,price,sale_price,detail,product_img,product_name')->where(['is_show' => 0, 'is_delete' => 0]);
        }])->where(['is_delete' => 0, 'is_show' => 0])->get($id);
        $coupon_info['detail'] = preg_replace_callback('/<[img|IMG].*?src=[\'| \"](?![http|https])(.*?(?:[\.gif|\.jpg]))[\'|\"].*?[\/]?>/', function ($r) {
            $str = $this->host . $r[1];
            return str_replace($r[1], $str, $r[0]);
        }, $coupon_info['detail']);
        $coupon_info['product']['detail'] = preg_replace_callback('/<[img|IMG].*?src=[\'| \"](?![http|https])(.*?(?:[\.gif|\.jpg]))[\'|\"].*?[\/]?>/', function ($r) {
            $str = $this->host . $r[1];
            return str_replace($r[1], $str, $r[0]);
        }, $coupon_info['product']['detail']);
        $coupon_info['store']['logo'] = $this->host . $coupon_info['store']['logo'];
        $img_array = explode(',', $coupon_info['product']['product_img']);
        $temp = array();
        foreach ($img_array as $img) {
            $temp[] = $this->host . $img;
        }
        $coupon_info['product']['product_img'] = $temp;
        if ($coupon_info) {
            $this->success('获取优惠券信息', ['coupon_info' => $coupon_info]);
        } else {
            $this->error('优惠券不存在或已下架');
        }

    }

    public function get_category_list()
    {
        $adv_info = Advlist::where('adv_id', 3)->find();
        $adv_info['img'] = $this->host . $adv_info['img'];
        $result['people'] = Category::where("type", 3)->all();
        $result['category'] = Category::where("type", 1)->order('sort desc')->all();
        $result['cuisine'] = Category::where("type", 4)->order('sort desc')->all();
        $this->success('获取所有标签', ['list' => $result, 'adv' => $adv_info]);
    }

    public function getConfig()
    {
        $config = hook_one('wechat_config');
        $this->success('config', ['config' => $config, 'aa' => 'aa']);
    }

    /**
     * 获取体验官日记
     */
    public function get_officer_diary_by_id()
    {
        $page = input('page') ? input('page') : 1;
        $num = input('num') ? input('num') : 20;
        $data = $this->request->param();
        $officer = Officer::withCount('photos')->get($data['id']);
        if (!$officer) {
            $this->error('体验官数据异常');
        }

        if (!stristr($officer['head_img'], 'https://')) {
            $officer['head_img'] = $this->host . $officer['head_img'];
        }
        $officer['diary_list'] = $officer->diaryList()->order('create_time desc')
            ->paginate($num, false, ['page' => $page])->each(function ($item) {
                if ($item['cover_pic']) {
                    $item['cover_pic'] = $this->host . $item['cover_pic'];
                }
                return $item;
            });
        $this->success('获取体验官信息', ['officer' => $officer]);
    }

    public function refund_money($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();

            $order_info = Order::where(['is_delete' => 0])->get($id);
            if (!$order_info) {
                $this->error('订单不存在');
            }

            if ($order_info['pay_status'] == '已退款' || $order_info['use_status'] == '已使用') {
                $this->error('订单异常');
            }
            $param['pay_sn'] = $order_info['pay_sn'];
            $param['refund_sn'] = $order_info['order_sn'] . rand(10, 99);
            $param['total_fee'] = $order_info['pay_amount'] * 100;
            $param['refund_fee'] = $order_info['pay_amount'] * 100;
            $payment = hook_one('wechat_config');
            $app = Factory::payment($payment);
            $result = $app->refund->byOutTradeNumber($param['pay_sn'], $param['refund_sn'], $param['total_fee'], $param['refund_fee'], [
                // 可在此处传入其他参数，详细参数见微信支付文档
                'refund_desc' => '退款',
            ]);
            if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                Order::get($id)->save(['pay_status' => 2]);
                if ($order_info['coupon_id']) {
                    UserCoupon::get($order_info['coupon_id'])->save(['use_status' => 0]);
                }
                $this->success('退款成功');
            } else {
                $this->error('退款失败',$result);
            }
        }
    }

    /**
     * 获取体验官相册
     */
    public function get_officer_photos_by_id()
    {
        $page = input('page') ? input('page') : 1;
        $num = input('num') ? input('num') : 20;
        $data = $this->request->param();
        $officer = Officer::withCount('photos')->get($data['id']);
        if (!$officer) {
            $this->error('体验官数据异常');
        }
        if (!stristr($officer['head_img'], 'https://')) {
            $officer['head_img'] = $this->host . $officer['head_img'];
        }
        $officer['photos'] = $officer->photos()->order('create_time desc')
            ->paginate($num, false, ['page' => $page])->each(function ($item) {
                if ($item['img_src']) {
                    $item['img_src'] = $this->host . $item['img_src'];
                }
                return $item;
            })->toArray();
        $this->success('获取体验官信息', ['officer' => $officer]);
    }

    public function redis_test()
    {
        $redis = new Redis();
        $redis->set('aa', 'gogogo');
        echo $redis->get('aa');
        $server = new swoole_server();
    }

    /**
     * 根据地址获取经纬度
     */
    public function get_address_to_lat_lag()
    {
        $address = input('address');
        $url = 'http://api.map.baidu.com/geocoder/v2/?address=' . $address . '&output=json&ak=9TDGYM2EmSqAUmcALik1tZ0fY170KkCi';
        if ($result = file_get_contents($url)) {
            $result = (array)json_decode($result);
            if ($result['status'] == 0) {
                $this->success('获取地址坐标', ['result' => $result['result']]);
            } else {
                $this->error($result['msg']);
            }

        }
    }
}
