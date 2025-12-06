<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
try {
    //手动加载入口文件
    include "../include.php";
    //准备公众号配置参数
    $config = include "./config.php";
    //创建接口实例
    //$wechat = new \WePay\Redpack($config);
    //$wechat = \We::WePayRedpack($config);
    $wechat = \WePay\Redpack::instance($config);
    //组装参数，可以参考官方商户文档
    $options = [
        'mch_billno'   => time(),
        're_openid'    => 'o38gps3vNdCqaggFfrBRCRikwlWY',
        'send_name'    => '商户名称😍',
        'act_name'     => '活动名称',
        'total_amount' => '100',
        'total_num'    => '1',
        'wishing'      => '感谢您参加猜灯谜活动，祝您元宵节快乐！',
        'remark'       => '猜越多得越多，快来抢！',
        'client_ip'    => '127.0.0.1',
    ];
    //发送红包记录
    $result = $wechat->create($options);
    echo '<pre>';
    var_export($result);
    //查询红包记录
    $result = $wechat->query($options['mch_billno']);
    var_export($result);
} catch (Exception $e) {
    //出错啦，处理下吧
    echo $e->getMessage().PHP_EOL;
}
?>