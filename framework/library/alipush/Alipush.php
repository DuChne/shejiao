<?php
/**
 * 阿里云推送封装
 * Created by PhpStorm.
 * User: 邓成鸿
 * Date: 2017/11/14
 * Time: 17:57
 */
include_once 'aliyun-php-sdk-core/Config.php';

class Alipush
{
    //需在阿里云里创建一个能访问移动推送资源的RAM子账号，获取该子账号的accessKeyId和accessKeySecret
    //帮助手册https://help.aliyun.com/document_detail/48049.html?spm=5176.doc48038.6.607.DklHVz
    private $accessKeyId = 'LTAImg5mn0ogXvZt';
    private $accessKeySecret = '4WfKF8eHlJDSLemzvG1ku55csIlWEJ';
    private $androidAppKey = '24944735';//安卓端appKey
    private $iosAppKey = '';//苹果端appKey
    private $iosApnsEnv = 'DEV';//IOS应用环境信息。"DEV":表示开发环境   "PRODUCT":表示生产环境

    function __construct($accessKeyId='',$accessKeySecret='',$androidAppKey='',$iosAppKey='')
    {

        if($accessKeyId)$this->accessKeyId=$accessKeyId;
        if($accessKeySecret)$this->accessKeySecret=$accessKeySecret;
        if($androidAppKey)$this->androidAppKey=$androidAppKey;//安卓端appKey
        if($iosAppKey)$this->iosAppKey=$iosAppKey;//苹果端appKey
    }

    /**
     * 阿里云推送 公共的推送 android/ios
     * 注：android和ios一次只能推送一个端，这个方法里已做处理可同时推送两个端，原理就是成功推送一个端就再次推送另一个端
     * 注：$arr这里面的参数title/body是必填项,其余都是根据需求选填
     * @param array $arr = array(
            'target' => 'ALL',//推送目标: DEVICE:推送给设备; ACCOUNT:推送给指定帐号,TAG:推送给自定义标签; ALL: 推送给全部
            'targetValue' => '',//根据Target来设定，如Target=device, 则对应的值为 设备id1,设备id2. 多个值使用逗号分隔.(帐号与设备有一次最多100个的限制)
            'deviceType' => 'ALL',//设备类型 ANDROID iOS ALL
            'pushType' => 'MESSAGE',//消息类型 MESSAGE NOTICE
            'title' => 'Welcome To YanYu',//消息的标题
            'body' => '欢迎使用晏语科技制作的阿里云推送',//消息的内容
            'extra' => '',//扩展属性，即额外参数，可自行配置，数据类型数组
            'openType' => 3,//点击通知后动作(仅android有效,ios则加入extra默认配置) 1:打开应用 2:打开AndroidActivity 3:打开URL 4:无跳转
            'openBody' => 'http://www.aliyun.com',//点击通知后动作配置(仅android有效,ios则加入extra默认配置)
            'pushTime' => 1521358741,//定时推送,传递时间戳
        );
     * @param int $appKeyType 推送设备类型 1:安卓 2:苹果
     * @return string
     */
    public function aliPush($arr = array(),$appKeyType = 1){

        /*------------------推送所需的参数配置----------------------*/
        $openTypeArr = array(1=>'APPLICATION',2=>'ACTIVITY',3=>'URL',4=>'NONE');//1:打开应用  2:打开AndroidActivity  3:打开URL  4:无跳转
        $target = empty($arr['target'])?'ALL':$arr['target'];//推送目标
        $targetValue = empty($arr['targetValue'])?'':$arr['targetValue'];//根据Target来设定
        $deviceType = empty($arr['deviceType'])?'ALL':$arr['deviceType'];//设备类型
        $pushType = empty($arr['pushType'])?'MESSAGE':$arr['pushType'];//消息类型
        $title = empty($arr['title'])?'':$arr['title'];//消息的标题
        $body = empty($arr['body'])?'':$arr['body'];//消息的内容
        $openType = empty($arr['openType'])?$openTypeArr[4]:$openTypeArr[$arr['openType']];//点击通知后动作
        $openBody = empty($arr['openBody'])?'':$arr['openBody'];//点击通知后动作配置
        $arr['extra'][$openType] = $openBody;//这里加入默认配置(即点击通知后动作的配置,IOS没有点击通知后动作,所以加入默认配置)
        $extra = empty($arr['extra'])?'':json_encode($arr['extra']);//扩展属性
        $pushTime = empty($arr['pushTime'])?strtotime('+3 second'):$arr['pushTime'];//定时推送
        /*----------------推送所需的参数配置 END--------------------*/
        try{
            //引入SDK
            $iClientProfile = DefaultProfile::getProfile("cn-hangzhou", $this->accessKeyId, $this->accessKeySecret);
            $client = new DefaultAcsClient($iClientProfile);
            $request = new \Push\Request\V20160801\PushRequest();

            //判断appkey类型，默认为androidAppKay
            $appKey = $this->androidAppKey;
            if($deviceType == 'iOS'||$appKeyType == 2)$appKey = $this->iosAppKey;

            // 推送目标
            $request->setAppKey($appKey);
            $request->setTarget($target); //推送目标: DEVICE:推送给设备; ACCOUNT:推送给指定帐号,TAG:推送给自定义标签; ALL: 推送给全部
            $request->setTargetValue($targetValue); //根据Target来设定，如Target=device, 则对应的值为 设备id1,设备id2. 多个值使用逗号分隔.(帐号与设备有一次最多100个的限制)
            $request->setDeviceType($deviceType); //设备类型 ANDROID iOS ALL.
            $request->setPushType($pushType); //消息类型 MESSAGE NOTICE
            $request->setTitle($title); // 消息的标题
            $request->setBody($body); // 消息的内容

            // 推送配置: Android
            $request->setAndroidNotifyType("NONE");//通知的提醒方式 "VIBRATE" : 震动 "SOUND" : 声音 "BOTH" : 声音和震动 NONE : 静音
            $request->setAndroidNotificationBarType(1);//通知栏自定义样式0-100
            $request->setAndroidOpenType($openType);//点击通知后动作 1、"APPLICATION":打开应用 2、"ACTIVITY":打开AndroidActivity 3、"URL":打开URL 4、"NONE":无跳转
            $request->setAndroidOpenUrl($openBody);//Android收到推送后打开对应的url,仅当AndroidOpenType="URL"有效
            $request->setAndroidActivity($openBody);//设定通知打开的activity，仅当AndroidOpenType="Activity"有效
            $request->setAndroidMusic("default");//Android通知音乐
            $request->setAndroidPopupActivity("");//设置该参数后启动辅助托管弹窗功能, 此处指定通知点击后跳转的Activity（辅助弹窗的前提条件：1. 集成第三方辅助通道；2. StoreOffline参数设为true
            $request->setAndroidPopupTitle("这个标题是做什么用的");
            $request->setAndroidPopupBody("这个内容是做什么用的");
            $request->setAndroidExtParameters($extra);//设定android类型设备通知的扩展属性

            // 推送配置: iOS
            $request->setiOSBadge("5"); // iOS应用图标右上角角标
            $request->setiOSMusic("default"); // iOS通知声音
            $request->setiOSApnsEnv($this->iosApnsEnv);//iOS的通知是通过APNs中心来发送的，需要填写对应的环境信息。"DEV" : 表示开发环境 "PRODUCT" : 表示生产环境
            $request->setiOSRemind("true"); // 推送时设备不在线（既与移动推送的服务端的长连接通道不通），则这条推送会做为通知，通过苹果的APNs通道送达一次(发送通知时,Summary为通知的内容,Message不起作用)。注意：离线消息转通知仅适用于生产环境
            $request->setiOSRemindBody($body);//iOS消息转通知时使用的iOS通知内容，仅当iOSApnsEnv=PRODUCT && iOSRemind为true时有效 "IOS这个内容是做什么用的"
            $request->setiOSExtParameters($extra);//自定义的kv结构,开发者扩展用 针对iOS设备

            // 推送控制
            $pushTime = gmdate('Y-m-d\TH:i:s\Z', $pushTime);//延迟1秒发送
            $request->setPushTime($pushTime);
            $expireTime = gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 day'));//设置失效时间为1天
            $request->setExpireTime($expireTime);
            $request->setStoreOffline("true");//离线消息是否保存,若保存, 在推送时候，用户即使不在线，下一次上线则会收到

            //处理返回信息
            $response = $client->getAcsResponse($request);
            $response = json_decode(json_encode($response),true);
            //如果返回MessageId，则推送成功
            if(!empty($response['MessageId'])){
                //因为默认是推送android端，如果deviceType == 'ALL'，且appKeyType == 1，则还要再次推送IOS端
                if($deviceType == 'ALL'&&$appKeyType == 1)return $this->aliPush($arr,2);
                return array('code'=>200,'msg'=>'推送成功');
            }
            return array('code'=>403,'msg'=>'推送失败');

        }catch (\Exception $e){
            return array('code'=>403,'msg'=>$e->getMessage());
        }

    }

}