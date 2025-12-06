<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
try {
    //手动加载入口文件
    include "../include.php";
    //准备公众号配置参数
    $config = include "./pay-v3-config.php";
    $payment = \WePayV3\Cert::instance($config);
    $payment->download();
} catch (\Exception $exception) {
    //出错啦，处理下吧
    echo $exception->getMessage().PHP_EOL;
}
?>