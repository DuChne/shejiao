<?php
/**
 *
 * 双乾支付接口 编写
 * Created by PhpStorm.
 * User: tanshenxiao
 * Date: 2018/12/26
 * Time: 15:18
 */
use think\Validate;


class shuangQianPay
{
    protected $merNo = '';                                      //平台商户号
    protected $version = 1.1;                                   //版本号 默认1.1
    protected $notifyUrl = '';                                  //支付主动通知地址
    protected $timestamp = '';                                  //发送请求时间
    protected $apiContent = '';                                 //请求参数集合
    protected $signType = 'CFCA';                               //请求签名类型固定 cfca
    protected $sign = '';                                       //签名

    protected $md5_key = 1234;                                  //签名

    /**
     * 初始化公共参数
     * @param $data['merNo'] 平台商户号  $data['notifyUrl'] 平台主动通知地址
     * shuangQianPay constructor.
     */
    public function __construct($data = [])
    {
        $validate = new Validate(['merNo' => ['require'],'notifyUrl' => ['require','activeUrl']]);
        if(!$validate->check($data)){
            throw new \Error($validate->getError());
        }
        $this->merNo = $data['merNo'];
        $this->notifyUrl = $data['notifyUrl'];
        $this->timestamp = date('Y-m-d H:i:s',time());
    }

    /**
     * 统一下单接口
     */
    public function toPay($data = [])
    {
        $url = 'https://qyfapi.95epay.com/api/api/pay/toPay';

        //数据验证 必要数据
        $validate = new Validate([
            'sererNo' => 'require',
            'payChannels' => 'require',
            'payAmount' => 'require',
            'apiPayType' =>'require',
            'tradeType' => 'require|number',
            'merMerOrderNo' => 'require',
            'orderSubject' => 'require',
        ]);
        if(!$validate->check($data)){
            return ['code' => 0,'msg' => $validate->getError()];
        }

        //过滤不要的 数据
        $selective = [
            'sererNo' => 'require',
            'payChannels' => 'require',
            'payAmount' => 'require',
            'apiPayType' =>'require',
            'tradeType' => 'require|number',
            'merMerOrderNo' => 'require',
            'orderSubject' => 'require',
            'orderBody',                    //订单信息
            'buyerNo',                      //买家双乾商户号
            'undiscountableAmount',         //不参与优惠计算金额
            'tempCommissionList',           //三方分佣参数
            'tempRoutingList',              //集合
            'goodsDetail',                  //商品列表
            'ptUndertakeRat',               //平台承担手续费 百分比
         ];
        //转json格式数据
        $json = [
            'tempCommissionList',
            'tempRoutingList',
            'goodsDetail',

        ];

        $sign = '';
        ksort($data);
        foreach($data as $k => $val)
        {
               if (!in_array($val,$data) or empty($val)) {
                   unset($data[$k]);
               }

               if (in_array($val,$json)) {
                   $val  = json_encode($val,JSON_UNESCAPED_UNICODE);
               }

                if ($sign != '') $sign .= '&';
                $sign .= "{$k}={$val}";

            }

        $sign = strtoupper(md5($sign . "&key={$this->md5_key}"));

        $content = json_encode($data,JSON_UNESCAPED_UNICODE);

        $request = [
            'merNo' => $this->merNo,
            'signType' => $this->signType,
            'version' => $this->version,
            'notifyUrl' => $this->notifyUrl,
            'sign' => $sign,
            'timestamp' => $this->timestamp,
            'apiContent' => $content,
        ];

        $request = json_encode($request,JSON_UNESCAPED_UNICODE);

        t($this->request_post($url,$request));

    }

    /**
     * 模拟post进行url请求
     * @param string $url
     * @param string|array $param
     */
    public function request_post($url = '', $param = '')
    {

        if (empty($url) || empty($param)) {
            return false;
        }
        if (is_array($param)) {
            $o = "";
            foreach ($param as $k => $v) {
                $o .= "$k=" . urlencode($v) . "&";
            }
            $param = substr($o, 0, -1);
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }


}