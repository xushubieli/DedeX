<?php
if (!defined('DEDEINC')) exit('dedebiz');
try {
    //手动加载入口文件
    include "../include.php";
    //准备公众号配置参数
    $config = include "./config.php";
    //创建接口实例
    //$wechat = new \WeChat\Pay($config);
    //$wechat = \We::WeChatPay($config);
    $wechat = \WeChat\Pay::instance($config);

    //4. 组装参数，可以参考官方商户文档
    $options = [
        'body'             => '测试商品',
        'out_trade_no'     => time(),
        'total_fee'        => '1',
        'openid'           => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo',
        'trade_type'       => 'JSAPI',
        'notify_url'       => 'http://a.com/text.html',
        'spbill_create_ip' => '127.0.0.1',
    ];
    //生成预支付码
    $result = $wechat->createOrder($options);
    //创建JSAPI参数签名
    $options = $wechat->createParamsForJsApi($result['prepay_id']);
    echo '<pre>';
    echo "\n--- 创建预支付码 ---\n";
    var_export($result);
    echo "\n\n--- JSAPI 及 H5 参数 ---\n";
    var_export($options);
} catch (Exception $e) {
    //出错啦，处理下吧
    echo $e->getMessage().PHP_EOL;
}
?>