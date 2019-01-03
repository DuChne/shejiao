<?php

$dn = DIRECTORY_SEPARATOR;
define('SQ_ROOT',IA_ROOT.$dn.'addons'.$dn.'ewei_shopv2'.$dn.'sqpay'.$dn);

require_once(SQ_ROOT."common/Common.php");
require_once(SQ_ROOT."common/HttpClient.php");
require_once(SQ_ROOT."common/CFCARAUtil.php");


class SqPay{

    protected $common_data = [];

    protected $post_data = [];

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
        $this->common_data["merNo"] = "0001565";
        $this->common_data["version"] = "1.1";
        $this->common_data["notifyUrl"] = "http://160.7.cqyanyu.com.cn/shejiao/addons/ewei_shopv2/payment/sq/notify.php";
        $this->common_data["timestamp"] = date('Y-m-d H:i:s');
        $this->common_data["apiContent"] = '';
        $this->common_data["signType"] = "CFCA";
    }

    /**
     * 支付
     */
    public function pay($ordersn,$price,$retrunUrl,$payType = 'pc',$orderSubject = '测试账号简称')
    {
        global $_W;

        $temp = array();
        $temp["fastpay"] = true;
        $temp["weChatPay"] = true;
        $temp["aliPay"] = true;
        //$temp["corpBank"] = true;
        //$temp["personalBank"] = true;

       // t($query);
        $postData = array();
        $postData["sellerNo"] = "0001565";

        if('pc' == $payType or 'wap' == $payType){
            $postData["payChannels"] = json_encode($temp);
        }

        $postData["orderBody"] = "com";
        $postData["payAmount"] = $price;
        $postData["apiPayType"] = "1";
        $postData["merMerOrderNo"] = $ordersn;
        $postData["buyerNo"] = "";
        $postData["undiscountableAmount"] = "";
        $postData["orderSubject"] = $orderSubject;
        $postData["goodsDetail"] = "";
        $postData["tradeType"] = "0";
        $postData["returnUrl"] = $retrunUrl;

        $this->common_data["apiContent"] = json_encode($postData);

        $dataStr = Common::joinMapValue($this->common_data);
        $this->common_data["sign"] = CFCARAUtil::signMessageByP1($dataStr);
        //CFCARAUtil::verifyMessageByP1($dataStr,$this->common_data["sign"]);



        if($payType == 'wap'){
            $postActionTest = "http://shoudan.95epay.com:9000/api/api/hPay/toPayHtml";
        }else{
            $postActionTest = "http://shoudan.95epay.com:9000/api/api/pay/toPayHtml";
        }

        if(!$_W['isajax']){
            HttpClient::htmlForm($postActionTest,$this->common_data);
            exit;
        }

        show_json(1,['url' => $postActionTest,'param' => $this->common_data,'type' => $payType]);
    }
}






