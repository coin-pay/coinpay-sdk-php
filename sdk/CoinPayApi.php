<?php

require_once "CoinPayConfigInterface.php";
require_once "CoinPayException.php";

class CoinPayApi
{
    const GATEWAY = 'https://openapi.coinpay.la';

    /**
     * 统一下单，CoinPayUnifiedOrder、total_amount、必填
     * @param CoinPayConfigInterface $config "配置对象"
     * @param CoinPayUnifiedOrder $inputObj
     * @param int $timeOut
     * @return string
     * @throws CoinPayException
     */
    public static function unifiedOrder($config, $inputObj, $timeOut = 6)
    {
        $url = self::GATEWAY . "/trade/create";

        //检测必填参数
        if (!$inputObj->IsSubjectSet()) {
            throw new CoinPayException("缺少统一支付接口必填参数subject！");
        } else if (!$inputObj->IsTimestampSet()) {
            throw new CoinPayException("缺少统一支付接口必填参数timestamp！");
        } else if (!$inputObj->IsOut_trade_noSet()) {
            throw new CoinPayException("缺少统一支付接口必填参数out_trade_no！");
        } else if (!$inputObj->IsTotal_amountSet()) {
            throw new CoinPayException("缺少统一支付接口必填参数total_amount！");
        }

        // 设置AppID和随机字符串
        $inputObj->SetAppid($config->GetAppId());
        $inputObj->SetNonce_str(self::getNonceStr());

        //异步通知url未设置，则使用配置文件中的url
        if (!$inputObj->IsNotify_urlSet() && $config->GetNotifyUrl() != "") {
            $inputObj->SetNotify_url($config->GetNotifyUrl());
        }
        if (!$inputObj->IsReturn_urlSet() && $config->GetReturnUrl() != "") {
            $inputObj->SetReturn_url($config->GetReturnUrl());
        }
        if (!$inputObj->IsAttachSet() && $config->GetAttach() != "") {
            $inputObj->SetAttach($config->GetAttach());
        }
        if (!$inputObj->IsBodySet() && $config->GetBody() != "") {
            $inputObj->SetBody($config->GetBody());
        }
        if (!$inputObj->IsTransCurrencySet() && $config->GetTransCurrency() != "") {
            $inputObj->SetTransCurrency($config->GetTransCurrency());
        }

        //签名
        $inputObj->SetSign($config->GetSecret());
        return self::buildRequestForm($inputObj->ReturnArray(), $url, $config);
    }

    /**
     * 查询订单，out_trade_no、transaction_id至少填一个
     * @param $config "配置对象"
     * @param $inputObj
     * @param int $timeOut
     * @return array
     * @throws CoinPayException
     */
    public static function orderQuery($config, $inputObj, $timeOut = 6)
    {
        $url = self::GATEWAY . "/trade/order-query";
        //检测必填参数
        if (!$inputObj->IsOut_trade_noSet() && !$inputObj->IsInvoice_idSet()) {
            throw new CoinPayException("订单查询接口中，out_trade_no、invoice_id至少填一个！");
        }
        $inputObj->SetSign($config->GetSecret());//签名
        return self::post($url, $inputObj->ReturnArray(), $timeOut);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp "请求参数数组"
     * @param $url
     * @param $config
     * @return string
     */
    private static function buildRequestForm($para_temp, $url, $config)
    {
        $sHtml = "<form id='bitcoin' name='bitcoin' action='" . $url . "?charset=" . trim($config->GetPostCharset()) . "' method='POST'>";
        while (list ($key, $val) = self::fun_adm_each($para_temp)) {
            if (false === self::checkEmpty($val)) {
                $val = str_replace("'", "&apos;", $val);
                $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
            }
        }
        $sHtml = $sHtml . "<input type='submit' value='ok' style='display:none;''></form>";
        $sHtml = $sHtml . "<script>document.forms['bitcoin'].submit();</script>";
        return $sHtml;
    }

    private static function fun_adm_each(&$array)
    {
        $res = array();
        $key = key($array);
        if ($key !== null) {
            next($array);
            $res[1] = $res['value'] = $array[$key];
            $res[0] = $res['key'] = $key;
        } else {
            $res = false;
        }
        return $res;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    private static function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    /**
     * @param $url
     * @param array $params
     * @param int $second
     * @return bool|string
     */
    private static function post($url, array $params = [], $second = 30)
    {
        $data_string = json_encode($params);
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')
        );
        //运行curl
        $data = curl_exec($ch);

        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new Exception("curl出错，错误码:$error");
        }
    }

    /**
     * 返回随机字符串
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}