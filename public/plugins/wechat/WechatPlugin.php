<?php


namespace plugins\wechat;


use cmf\lib\Plugin;
use EasyWeChat\Factory;

class WechatPlugin extends Plugin
{
    public $info = [
        'name' => 'Wechat', //Demo插件英文名，改成你的插件英文就行了
        'title' => '集成easywechat微信插件',
        'description' => '集成easywechat',
        'status' => 1,
        'author' => 'ZYL',
        'version' => '1.0',
        'demo_url' => '',
        'author_url' => '',
    ];

    public $hasAdmin = 1; //插件是否有后台管理界面

    // 插件安装
    public function install()
    {
        return true; //安装成功返回true，失败false
    }

    // 插件卸载
    public function uninstall()
    {
        return true; //卸载成功返回true，失败false
    }


    //实现的wechat_config钩子方法
    public function wechatConfig($param)
    {
        $config = $this->getConfig();
        $payment = [
            'app_id' => $config['xcx_appid'],
            'mch_id' => $config['mch_id'],
            'key' => $config['key'],   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['cert_path'], // XXX: 绝对路径！！！！
            'key_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['key_path'],      // XXX: 绝对路径！！！！
            'notify_url' => $config['notify_url'],     // 你也可以在下单时单独设置来想覆盖它
            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/logs/wechat.log',
            ],
        ];
//        $app = Factory::payment($payment);
        return $payment;
    }

    //实现的wechat_xcx_pay_order钩子方法
    public function wechatXcxPayOrder($param)
    {
        $config = $this->getConfig();
        $payment = [
            'app_id' => $config['xcx_appid'],
            'mch_id' => $config['mch_id'],
            'key' => $config['key'],   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['cert_path'], // XXX: 绝对路径！！！！
            'key_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['key_path'],      // XXX: 绝对路径！！！！
            'notify_url' => $config['notify_url'],     // 你也可以在下单时单独设置来想覆盖它
            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/logs/wechat.log',
            ],
        ];
        $app = Factory::payment($payment);
        $jssdk = $app->jssdk;
        $result = $app->order->unify([
            'body' => $param['body'],
            'out_trade_no' => $param['pay_sn'],
            'total_fee' => $param['pay_amount'] * 100,
            //'spbill_create_ip' => '', // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
            //'notify_url' => 'https://pay.weixin.qq.com/wxpay/pay.action', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $param['openid'],
        ]);
        $config = $jssdk->bridgeConfig($result['prepay_id'], false);
        return $config;
    }

    //实现的wechat_xcx_refund_order钩子方法
    public function wechatXcxRefundOrder($param)
    {
        //小程序退款
        $config = wechatConfig();
        $app = Factory::payment($config);
        $result = $app->refund->byOutTradeNumber($param['pay_sn'], $param['refund_sn'], $param['total_fee'], $param['refund_fee'], [
            // 可在此处传入其他参数，详细参数见微信支付文档
            'refund_desc' => '退款',
        ]);
        return $result;
    }

    //实现的wechat_auth_login钩子方法
    public function wechatAuthLogin()
    {
        $config = $this->getConfig();
        $wechat_config = [
            /**
             * 账号基本信息，请从微信公众平台/开放平台获取
             */
            'app_id' => $config['web_appid'],         // AppID
            'secret' => $config['web_secret'],     // AppSecret
            'token' => 'omJNpZEhZeHj1B5VFbk1HPZxFECKkP48',          // Token
            'aes_key' => 'WbWcudJ4JshmJGyRUIr61mfr2CmN0em0WnW9avFxrtG',                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！

            /**
             * 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
             * 使用自定义类名时，构造函数将会接收一个 `EasyWeChat\Kernel\Http\Response` 实例
             */
            'response_type' => 'array',
            /**
             * 日志配置
             *
             * level: 日志级别, 可选为：
             *         debug/info/notice/warning/error/critical/alert/emergency
             * path：日志文件位置(绝对路径!!!)，要求可写权限
             */
            'log' => [
                'default' => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
                'channels' => [
                    // 测试环境
                    'dev' => [
                        'driver' => 'single',
                        'path' => __DIR__ . '/logs/wechat.log',
                        'level' => 'debug',
                    ],
                    // 生产环境
                    'prod' => [
                        'driver' => 'daily',
                        'path' => __DIR__ . '/logs/wechat.log',
                        'level' => 'info',
                    ],
                ],
            ],

            /**
             * 接口请求相关配置，超时时间等，具体可用参数请参考：
             * http://docs.guzzlephp.org/en/stable/request-config.html
             *
             * - retries: 重试次数，默认 1，指定当 http 请求失败时重试的次数。
             * - retry_delay: 重试延迟间隔（单位：ms），默认 500
             * - log_template: 指定 HTTP 日志模板，请参考：https://github.com/guzzle/guzzle/blob/master/src/MessageFormatter.php
             */
            'http' => [
                'max_retries' => 1,
                'retry_delay' => 500,
                'timeout' => 5.0,
                // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
            ],

            /**
             * OAuth 配置
             *
             * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
             * callback：OAuth授权完成后的回调页地址
             */
            'oauth' => [
                'scopes' => ['snsapi_userinfo'],
                'callback' => '/user/login/oauth_callback',
            ],
        ];
        $app = Factory::officialAccount($wechat_config);
        return $app;
    }

    public function wechatSearchOrder($param)
    {
        $config = $this->getConfig();
        $payment = [
            'app_id' => $config['web_appid'],
            'mch_id' => $config['mch_id'],
            'key' => $config['key'],   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['cert_path'], // XXX: 绝对路径！！！！
            'key_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['key_path'],      // XXX: 绝对路径！！！！
            'notify_url' => $config['notify_url'],     // 你也可以在下单时单独设置来想覆盖它
            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/logs/wechat.log',
            ],
        ];
        $app = Factory::payment($payment);
        $res = $app->transfer->queryBalanceOrder($param['order_sn']);
        return $res;
    }

    //实现的wechat_enterprise_transfer钩子方法
    public function wechatEnterpriseTransfer($param)
    {
        $config = $this->getConfig();
        $payment = [
            'app_id' => $config['web_appid'],
            'mch_id' => $config['mch_id'],
            'key' => $config['key'],   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['cert_path'], // XXX: 绝对路径！！！！
            'key_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['key_path'],      // XXX: 绝对路径！！！！
            'notify_url' => $config['notify_url'],     // 你也可以在下单时单独设置来想覆盖它
            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/logs/wechat.log',
            ],
        ];
        $app = Factory::payment($payment);
        $res = $app->transfer->toBalance([
            'partner_trade_no' => $param['order_sn'], // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $param['openid'],
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => '', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => $param['money'] * 100, // 企业付款金额，单位为分
            'desc' => '商户结算', // 企业付款操作说明信息。必填
        ]);
        return $res;
    }

//    public function wechatQueryBalanceOrder($param)
//    {
//        $config = $this->getConfig();
//        $payment = [
//            'app_id' => $config['web_appid'],
//            'mch_id' => $config['mch_id'],
//            'key' => $config['key'],   // API 密钥
//            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
//            'cert_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['cert_path'], // XXX: 绝对路径！！！！
//            'key_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['key_path'],      // XXX: 绝对路径！！！！
//            'notify_url' => $config['notify_url'],     // 你也可以在下单时单独设置来想覆盖它
//            'log' => [
//                'level' => 'debug',
//                'file' => __DIR__ . '/logs/wechat.log',
//            ],
//        ];
//        $app = Factory::payment($payment);
//        $res = $app->transfer->queryBalanceOrder($payment['order_sn']);
//        return $res;
//    }

    public function wechatRedBag($param)
    {
        $config = $this->getConfig();
        $payment = [
            'app_id' => $config['web_appid'],
            'mch_id' => $config['mch_id'],
            'key' => $config['key'],   // API 密钥
            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['cert_path'], // XXX: 绝对路径！！！！
            'key_path' => __DIR__ . '/1546962141_20190803_cert/' . $config['key_path'],      // XXX: 绝对路径！！！！
            'notify_url' => $config['notify_url'],     // 你也可以在下单时单独设置来想覆盖它
            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/logs/wechat.log',
            ],
        ];
        $app = Factory::payment($payment);
        $redpack = $app->redpack;
        $redpackData = [
            'mch_billno' => $param['order_sn'],
            'send_name' => '测试红包',
            're_openid' => $param['openid'],
            'total_num' => 1,  //固定为1，可不传
            'total_amount' => $param['money'] * 100,  //单位为分，不小于100
            'wishing' => '祝福语',
            'client_ip' => '',  //可不传，不传则由 SDK 取当前客户端 IP
            'act_name' => '测试活动',
            'remark' => '测试备注',
            // ...
        ];
        $result = $redpack->sendNormal($redpackData);
        return $result;

    }

}