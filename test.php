<?php

header('Content-type:text/html; Charset=utf-8');

include_once './CoinPayConfig.php';
include_once './sdk/CoinPayApi.php';
include_once './sdk/CoinPayUnifiedOrder.php';
include_once './sdk/CoinPayOrderQuery.php';

date_default_timezone_set('Asia/Hong_Kong');

$input = new \CoinPayUnifiedOrder();
$input->SetSubject("测试订单");
$input->SetOut_trade_no('1000010000000');
$input->SetTotal_amount(100);
$input->SetReturn_url("xxxx");
$input->SetNotify_url("xxxxxxx");
$input->SetTimestamp(date('Y-m-d H:i:s', time()));
$input->SetBody("商品描述");
$input->SetTransCurrency("CNY"); // USD | CNY 可选，默认不传为 CNY
$config = new CoinPayConfig();
try {
    $dom =  \CoinPayApi::unifiedOrder($config, $input);
    print_r($dom);
    die();
} catch (\CoinPayException $e) {
    echo $e->getMessage();
}