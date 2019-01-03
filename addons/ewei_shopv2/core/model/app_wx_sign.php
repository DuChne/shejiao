<?php
//---------------deng start wxpay create---------------
if (!(defined('IN_IA'))) {

    exit('Access Denied');
}

class App_wx_sign_EweiShopV2Model{

    private $private_key='';
    private $url='https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * app 支付签名
     * @param $total_fee 总金额
     * @param $notify_url 回调地址
     * @param $body 支付描述
     * @param $ordersn 订单号
     * @return array
     */
    public function wxSignature($conf,$total_fee,$notify_url,$body,$ordersn)
    {
//        $this->conf=array(
//            'appid'=>$conf['appid'],
//            'mch_id'=>$conf['mch_id'],
//            'notify_url'=>$conf['notify_url'],
//            'out_trade_no'=>$conf['out_trade_no'],
//            'total_fee'=>$conf['total_fee'],
//            'trade_type'=>$conf['trade_type'],
//            'body'=>$conf['body']
//        );
        $data=array(
            'appid'=>$conf['appid'],
            'mch_id'=>$conf['mch_id'],
            'notify_url'=>$notify_url,
            'out_trade_no'=>$ordersn,
            'total_fee'=>$total_fee*100,
            'trade_type'=>'APP',
            'body'=>$body,
            'attach'=>empty($conf['attach'])?'':$conf['attach']
        );
        $this->private_key = $conf['private_key'];

        $request=$this->getOrder($data,$this->private_key);
        if($request['code']==1)
        {
            $datas["appid"] = $request['data']['appid'];
            $datas["noncestr"] = $this->getNonceStr();
            $datas["package"] = "Sign=WXPay";
            $datas["partnerid"] = $request['data']['mch_id'];
            $datas["prepayid"] = $request['data']['prepay_id'];
            $datas["timestamp"] = time();
            $s = $this->wxSign($datas,$this->private_key);
            $datas["sign"] = $s;
            return json_encode(array('code'=>200,'msg'=>'成功','data'=>$datas),JSON_UNESCAPED_UNICODE);
        }
        else
        {
            return json_encode(array('code'=>403,'msg'=>'调起支付失败','data'=>$request['data']),JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 支付回调验签
     * @param array $params 参数
     * @return bool
     */
    function check($params){
        $sign = $params['sign'];
        unset($params['sign']);
        $s = $this->wxSign($params,$this->private_key);
        if($s == $sign)return true;
        return false;
    }

    //------------------------------------把微信的demo剃出来--------------------------------
    /**
     * 微信签名
     */
    public  function wxSign($data,$keys)
    {
        ksort($data);
        $str='';
        foreach($data as $key=>$val)
        {
            if($val)
            {
                if($str!='')$str.='&';
                $str.="{$key}={$val}";
            }
        }
        $str.='&key='.$keys;
        $sign=strtoupper(MD5($str));
        return $sign;
    }
    /**
     * 获取客户端ip
     * @return string
     */
    private function getIp()
    {
        $cip='';
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }
    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public  function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     *获取统一支付信息
     * @param $private_key 支付秘钥
     * @return array
     */
    public function getOrder($data,$private_key)
    {
        //$data=$this->conf;
        $data['nonce_str']=$this->getNonceStr();
        $data['spbill_create_ip']=$this->getIp();
        $data['sign']=$this->wxSign($data,$private_key);
        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val))
            {
                $xml.="<".$key.">".$val."</".$key.">";

            }
            else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.="</xml>";
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url); //设置访问路径
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); //设置可以返回字符串
        curl_setopt($ch, CURLOPT_POST,TRUE);//post请求
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);//设置传递的参数
        $request=curl_exec($ch);
        curl_close($ch);
        $request=json_decode(json_encode(simplexml_load_string($request, 'SimpleXMLElement', LIBXML_NOCDATA)),true);
        if($request['return_code']=='SUCCESS'&&$request['result_code']=='SUCCESS')
        {

            return array('code'=>1,'data'=>$request);
        }
        else
        {
            return array('code'=>0,'data'=>$request['return_msg']);
        }
    }


}

