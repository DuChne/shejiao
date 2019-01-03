<?php
//---------------deng start alipay create---------------
if (!(defined('IN_IA'))) {

    exit('Access Denied');
}

class App_pay_sign_EweiShopV2Model{

    function __construct()
    {}

    /**
     * 支付宝RSA签名
     * @param $data 待签名数据
     * @param $private_key_path 商户私钥文件路径
     * return 签名结果
     */
    function rsaSign($data, $private_key_path,$sha='RSA') {

        //$priKey = file_get_contents($private_key_path);
        $priKey = $private_key_path;
        $res = openssl_get_privatekey($priKey);
        if($sha == 'RSA2')openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        else openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        $sign=urlencode($sign);
        return $sign;
    }

    /**
     * 支付宝app签名
     * @param $out_trade_no 订单编号
     * @param $body 描述
     * @param $notify_url 回调地址
     * @param $total_fee 金额
     * @return string
     */
    function paySignature($alipay,$ordersn,$body,$notify_url,$total_fee)
    {
        $data['partner']=$alipay['partner'];
        $data['seller_id']=$alipay['seller_id'];
        $data['out_trade_no']=$ordersn;
        $data['subject']=$ordersn;
        $data['body']=$body;
        $data['total_fee']=$total_fee;
        $data['notify_url']=$notify_url;
        $data['service']='mobile.securitypay.pay';
        $data['payment_type']='1';
        $data['_input_charset']='utf-8';
        $data['it_b_pay']='30m';
        ksort($data);
        $str='';
        foreach($data as $key=>$val)
        {
            if($val)
            {
                if($str!='')$str.='&';
                $str.="{$key}=\"{$val}\"";
            }

        }

        $sign=$this->rsaSign($str,$alipay['private_key_path']);
        $data['sign']=$sign;
        $data['sign_type']='RSA';
        $str.='&sign="'.$sign.'"&sign_type="RSA"';
        return $str;
    }
}

