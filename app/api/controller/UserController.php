<?php


namespace app\api\controller;


use app\admin\model\Advlist;
use app\admin\model\Coupon;
use app\admin\model\Diary;
use app\admin\model\Officer;
use app\admin\model\Product;
use app\admin\model\Store;
use app\api\model\Order;
use app\api\model\RecommendDiary;
use app\api\model\UserCoupon;
use app\api\model\UserModel;
use app\api\model\Rank;
use app\api\model\UserCollection;
use EasyWeChat\Factory;
use think\Db;
use think\Exception;

class UserController extends BaseController
{
    protected $openid = '';
    protected $user_info = '';
    protected $payment = '';
    protected $app = '';

    protected function initialize()
    {
        $this->openid = input('post.openid');
        $user_info = check_wx_user($this->openid);
        if ($user_info) {
            $this->user_info = $user_info;
        } else {
            $this->error('请登录');
        }
//        $this->payment = hook('wechat_config');
//        $this->app = Factory::payment($this->payment);
    }

    /**
     * 申请体验官
     */
    public function apply_officer()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate_result = $this->validate($data, 'Officer');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $officer = Officer::where('user_id', $this->user_info['id'])->find();
                if ($officer) {
                    if ($officer['is_check'] == 0) {
                        $this->error('您已申请体验官,请等待审核');
                    } else {
                        $this->error('您已是体验官,请勿重复提交');
                    }
                } else {
                    $officer = new Officer();
                    $officer->user_id = $this->user_info['id'];
                    $officer->nickname = $this->user_info['user_nickname'];
                    $officer->head_img = $this->user_info['avatar'];
                    $officer->real_name = $data['real_name'];
                    $officer->sex = $this->user_info['sex'];
                    $officer->age = $data['age'];
                    $officer->school = $data['school'];
                    $officer->grade = $data['grade'];
                    $officer->subject = $data['subject'];
                    $officer->mobile = $data['mobile'];
                    $officer->save();
                    if ($officer->id) {
                        $this->success('申请成功,请等待审核');
                    } else {
                        $this->error('申请失败');
                    }

                }
            }
        }
    }

    /**
     * 用户点赞
     */
    public function diray_rank_action()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $is_rank = Rank::where(['user_id' => $this->user_info['id'], 'diary_id' => $data['diary_id']])->find();
            if ($is_rank) {
                $this->error('您已评价');
            }
            $rank = new Rank();
            $rank->user_id = $this->user_info['id'];

            $diary = Diary::get($data['diary_id']);
            if (!$diary) {
                $this->error('日记数据异常');
            }
            $rank->diary_id = $data['diary_id'];
            $rank->is_agree = $data['is_agree'];
            if ($data['is_agree'] == 0) {
                //推荐
                Diary::get($data['diary_id'])->setInc('agree', 1);
            } else {
                //不推荐
                Diary::get($data['diary_id'])->setInc('disagree', 1);
            }
            $rank->save();
            if ($rank->id) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
    }

    /**
     * 用户收藏日记
     */
    public function user_collection_action()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $user_id = $this->user_info['id'];
            $diary = Diary::get($data['diary_id']);

            if (!Diary::get($data['diary_id'])) {
                $this->error('日记数据异常');
            }
            $user_collection = UserCollection::where(['user_id' => $user_id, 'diary_id' => $data['diary_id']])->find();
            if ($user_collection) {
                $res = $user_collection->delete($user_collection['id']);
                if ($res) {
                    $this->success('取消收藏成功');
                } else {
                    $this->error('取消收藏失败');
                }
            } else {
                $user_collection = new UserCollection();
                $user_collection->user_id = $user_id;
                $user_collection->diary_id = $data['diary_id'];
                $user_collection->title = $diary['title'];
                $user_collection->cover_pic = $diary['cover_pic'];
                $user_collection->save();
                if ($user_collection->id) {
                    $this->success('收藏成功');
                } else {
                    $this->error('收藏失败');
                }
            }
        }
    }

    /**
     * 收藏列表
     */
    public function get_user_collection()
    {
        $page = input('page') ? input('page') : 1;
        $num = input('num') ? input('num') : 20;
        $user_info = $this->user_info;
        $list = $user_info->collection()
            ->with(['diary' => ['officer' => function ($query) {
                $query->field('id,nickname');
            }]])
            ->order('create_time desc')
            ->paginate($num, false, ['page' => $page])
            ->each(function ($item) {
                if ($item['cover_pic']) {
                    $item['cover_pic'] = $this->host . $item['cover_pic'];
                }
                return $item;
            });
        $this->success('收藏列表', ['list' => $list]);
    }


    /**
     * 用户创建订单
     */
    public function user_create_order()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $user_info = $this->user_info;
            $now = date('Y-m-d');
            $product_info = Product::get($data['product_id']);
            if (!$product_info) {
                $this->error('该团品已被删除或者下架');
            }
            if ($product_info['limits'] > 0) {
                $order_count = Order::where(['user_id' => $user_info['id'], 'product_id' => $data['product_id']])->where('pay_status', '>=', 1)->count();
                if ($order_count >= $product_info['limits']) {
                    $this->error('该团品限制购买' . $product_info['limits'] . '份');
                }
            }
            $product_img = explode(',', $product_info['product_img']);
            $temp = array();
            foreach ($product_img as $img) {
                $temp[] = $this->host . $img;
            }
            $product_info['product_img'] = $temp;
            $coupon_info = UserCoupon::where(['user_id' => $user_info['id'], 'product_id' => $data['product_id'], 'use_status' => 0])
                ->whereTime("start_time", '<=', $now)
                ->whereTime("end_time", '>=', $now)
                ->order('id asc')
                ->limit(1)->select();
            $order = new Order();
            $order->order_sn = build_order_sn();
            $order->user_id = $user_info['id'];
            $order->order_amount = $product_info['sale_price'];
            $order->store_id = $product_info['store_id'];
            $order->product_id = $data['product_id'];
            $order->save();
            if ($order->id) {
                $this->success('创建订单成功', ['coupon_info' => $coupon_info, 'product_info' => $product_info, 'order_info' => Order::get($order->id)]);
            } else {
                $this->error('创建订单失败');
            }
        }
    }

    /**
     * 用户确认订单
     */
    public function user_confirm_order()
    {
        if ($this->request->isPost()) {
            Db::startTrans();
            try {
                $data = $this->request->param();
                $order = Order::where('is_delete', 0)->get($data['order_id']);
                if (!$order) {
                    throw new Exception('订单不存在或者被删除');
                }
                $now = date('Y-m-d');
                $discount_amount = 0;
                if (isset($data['coupon_id']) && $data['coupon_id']) {
                    $coupon_info = UserCoupon::where(['product_id' => $order['product_id'], 'use_status' => 0])
                        ->whereTime("start_time", '<=', $now)
                        ->whereTime("end_time", '>=', $now)->get($data['coupon_id']);
                    if (!$coupon_info) {
                        throw new Exception('该优惠券已过期或不存在');
                    }
                    $order->mobile = $data['mobile'];
                    $coupon_info->use_status = 1;
                    $coupon_info->save();
                    $order->coupon_id = $data['coupon_id'];
                    $discount_amount = $coupon_info['value2'];
                    $order->confirm_time = date('Y-m-d H:i:s');
                    $order->discount_amount = $coupon_info['value2'];
                    $order->order_status = 1;
                    $order->pay_amount = $order['order_amount'] - $discount_amount;
                }
                $order->pay_sn = build_pay_sn();
                $res = $order->save();
                $order = Order::get($order->id);
                $pay_info = '';
                if ($order['pay_amount'] == 0) {
                    $order->pay_time = date('Y-m-d H:i:s');
                    $order->pay_status = 1;
                    $order->use_status = 1;
                    $order->money_paid = 0;
                    $order->check_code = make_check_code();
                    $order->save();
                    UserCoupon::get($order['coupon_id'])->save(['use_status' => 2, 'use_time' => date('Y-m-d H:i:s')]);
                    Db::commit();
                } else {
                    $order['body'] = '脉鹿星选购买团品,订单号:' . $order['order_sn'];
                    $order['openid'] = $data['openid'];
                    $payment = hook_one('wechat_config');
                    $app = Factory::payment($payment);
                    $result = $app->order->unify([
                        'body' => '脉鹿星选购买团品,订单号:' . $order['order_sn'],
                        'out_trade_no' => $order['pay_sn'],
                        'total_fee' => $order['pay_amount'] * 100,
                        //'spbill_create_ip' => '', // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
                        //'notify_url' => 'https://pay.weixin.qq.com/wxpay/pay.action', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                        'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
                        'openid' => $data['openid'],
                    ]);
                    $jssdk = $app->jssdk;
                    $pay_info = $jssdk->bridgeConfig($result['prepay_id'], false);
                    if ($res) {
                        //支付
                        Db::commit();
                    } else {
                        throw new Exception('确认订单失败');
                    }
                }
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            };
            $this->success('确认订单成功', ['pay_info' => $pay_info]);
        }
    }

    /**
     * 用户订单列表
     */
    public function user_order_list()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $page = isset($data['page']) && $data['page'] ? $data['page'] : 1;
            $num = isset($data['num']) && $data['num'] ? $data['num'] : 20;
            $user_info = $this->user_info;
            $where = [];
            $status = isset($data['status']) && $data['status'] ? $data['status'] : 100;
            switch ($status) {
                case 101:
                    //代付款
                    $where = ['order_status' => 1, 'pay_status' => 0, 'use_status' => 0];
                    break;
                case 102:
                    $where = ['order_status' => 1, 'pay_status' => 1, 'use_status' => 1];
                    //待使用
                    break;
                case 103:
                    //已核销
                    $where = ['order_status' => 1, 'pay_status' => 1, 'use_status' => 2];
                    break;
                default:
//                    $where = ['order_status', '>', 0];
                    break;
            }
            $list = Order::scope('OrderStatus')
                ->with(['coupon', 'product'])
                ->where(['user_id' => $user_info['id'], 'is_delete' => 0])
                ->order('create_time desc')
                ->where($where)
                ->paginate($num, false, ['page' => $page])
                ->each(function ($item) {
                    if ($item['product']['product_img']) {
                        $array = explode(',', $item['product']['product_img']);
                        $temp = array();
                        foreach ($array as $img) {
                            $temp[] = $this->host . $img;
                        }
                        $item['product']['product_img_1'] = $temp;
                    }
                    return $item;
                });
            $this->success('我的订单', ['list' => $list, 'where' => $where]);
        }
    }

    /**
     * 用户订单详细
     */
    public function user_order_info($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $user_id = $this->user_info['id'];
            $info = Order::scope('OrderStatus')
                ->with(['coupon', 'product', 'store_info', 'user_info'])
                ->where(['user_id' => $user_id, 'is_delete' => 0])->get($data['id']);
            $info->visible(['store_info' => ['store_name', 'address', 'phone', 'mapx', 'mapy']])->toArray();

            if ($info['product']['product_img']) {
                $array = explode(',', $info['product']['product_img']);
                $temp = array();
                foreach ($array as $img) {
                    $temp[] = $this->host . $img;
                }
                $info['product']['product_img'] = $temp;
            }
            if ($info) {
                $this->success('订单详细', ['order_info' => $info]);
            } else {
                $this->error('订单不存在或已被删除');
            }
        }
    }

    /**
     * 用戶退款
     */
    public function user_refund_order($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $user_info = $this->user_info;
            $order_info = Order::where(['user_id' => $user_info['id'], 'is_delete' => 0])->get($id);
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
                $this->error('退款失败');
            }
        }
    }


    public function user_delete_order($id)
    {
        if ($this->request->isPost()) {
            $order_info = Order::where(['user_id' => $this->user_info['id'], 'is_delete' => 0])->get($id);
            if (!$order_info) {
                $this->error('订单不存在');
            }
            if ($order_info['pay_status'] == '已退款' || $order_info['use_status'] == '已使用' || $order_info['order_status'] == '已取消') {
                $order_info->is_delete = 1;
                $order_info->delete_time = time();
                $res = $order_info->save();
                if ($res) {
                    $this->success('删除成功');
                } else {
                    $this->error('删除失败');
                }
            } else {
                $this->error('订单状态异常');
            }
        }
    }

    /**
     * 用户领取优惠券
     */
    public function user_get_coupon()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $now = date('Y-m-d');
            $coupon_info = Coupon::where(['is_delete' => 0, 'is_show' => 0])->whereTime("end_time", '>=', $now)->get($data['coupon_id']);
            if (!$coupon_info) {
                $this->error('该优惠券已过期或已被下架');
            }
            if ($coupon_info['limit_count'] != 0) {
                $coupon_count = $this->user_info->user_coupons()->where('coupon_id', $data['coupon_id'])->count();
                if ($coupon_count >= $coupon_info['limit_count']) {
                    $this->error("该优惠券限领" . $coupon_info['limit_count'] . "张");
                }
            }
            $user_coupon = new  UserCoupon();
            $user_coupon->user_id = $this->user_info['id'];
            $user_coupon->coupon_id = $data['coupon_id'];
            $user_coupon->product_id = $coupon_info['product_id'];
            $user_coupon->name = $coupon_info['name'];
            $user_coupon->value1 = $coupon_info['value1'];
            $user_coupon->value2 = $coupon_info['value2'];
            $user_coupon->start_time = $coupon_info['start_time'];
            $user_coupon->end_time = $coupon_info['end_time'];
            $user_coupon->detail = $coupon_info['detail'];
            $user_coupon->save();
            if ($user_coupon->id) {
                $this->success('领取优惠券成功');
            } else {
                $this->error('领取失败');
            }

        }
    }

    /**
     * 用户优惠券列表
     */
    public function user_coupon_list()
    {
        $page = input('page') ? input('page') : 1;
        $num = input('num') ? input('num') : 20;
        $user_id = $this->user_info['id'];
        $list = UserCoupon::with('product')->where('user_id', $user_id)
            ->where('use_status', 0)
            ->order('create_time desc')
            ->paginate($num, false, ['page' => $page])
            ->each(function ($item) {
                if ($item['product']['product_img']) {
                    $array = explode(',', $item['product']['product_img']);
                    $temp = array();
                    foreach ($array as $img) {
                        $temp[] = $this->host . $img;
                    }
                    $item['product']['product_img_1'] = $temp;
                }
                return $item;
            });
        $this->success('我的优惠券列表', ['list' => $list]);
    }


    /**
     * 获取用户信息
     */
    public function get_user_info()
    {
        $adv_info = Advlist::where('adv_id', 2)->find();
        $adv_info['img'] = $this->host . $adv_info['img'];
        $adv_info2 = Advlist::where('adv_id', 4)->find();
        $adv_info2['img'] = $this->host . $adv_info2['img'];
        $this->success('获取用户信息', ['user_info' => $this->user_info, 'adv' => $adv_info, 'adv1' => $adv_info2]);
    }

    public function get_recommend_store()
    {
        if ($this->request->isPost()) {
            $count = RecommendDiary::where('user_id', $this->user_info['id'])->whereTime('create_time', 'today')->count();
            if ($count >= 2) {
                $this->error('每天只能推荐2次');
            }
            $data = $this->request->param();
            $where = [];
            if (isset($data['people']) && $data['people']) {
                $people = $data['people'];
                $where[] = ['', 'exp', Db::raw("FIND_IN_SET('$people', people)")];
            }
            if (isset($data['category']) && $data['category']) {
                $category = $data['category'];
                $where[] = ['', 'exp', Db::raw("FIND_IN_SET('$category', category)")];
            }
            if (isset($data['cuisine']) && $data['cuisine']) {
                $cuisine = $data['cuisine'];
                $where[] = ['', 'exp', Db::raw("FIND_IN_SET('$cuisine', cuisine)")];
            }
            $recommend_diary = Diary::whereOr($where)->orderRaw('rand()')->limit(1)->find();
            if (!$recommend_diary) {
                $recommend_diary = Diary::where('is_recommend', 1)->orderRaw('rand()')->limit(1)->find();
            }
            if ($recommend_diary['cover_pic']) {
                $recommend_diary['cover_pic'] = $this->host . $recommend_diary['cover_pic'];
            }
            $log = new  RecommendDiary();
            $log->user_id = $this->user_info['id'];
            $log->diary_id = $recommend_diary['id'];
            $log->save();
            $this->success('获取推荐', ['info' => $recommend_diary]);
        }
    }
}