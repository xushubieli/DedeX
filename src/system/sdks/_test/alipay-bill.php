<?php
if (!defined('DEDEINC')) exit('dedebiz');
//手动加载入口文件
include "../include.php";
//准备公众号配置参数
$config = include "./alipay.php";
try {
    //实例支付对象
    //$pay = new \AliPay\Bill($config);
    //$pay = \We::AliPayBill($config);
    $pay = \AliPay\Bill::instance($config);
    //请参考（请求参数）：https://docs.open.alipay.com/api_15/alipay.data.dataservice.bill.downloadurl.query
    $result = $pay->apply([
        'bill_date' => '2020-07-03',//账单时间(日账单yyyy-MM-dd,月账单 yyyy-MM)
        'bill_type' => 'signcustomer',//账单类型(trade指商户基于支付宝交易收单的业务账单,signcustomer是指基于商户支付宝余额收入及支出等资金变动的帐务账单)
    ]);
    echo '<pre>';
    var_export($result);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>