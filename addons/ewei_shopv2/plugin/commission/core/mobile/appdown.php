<?php
/**
 * deng start app_down
 */
if (!(defined('IN_IA'))) {
    exit('Access Denied');
}

require EWEI_SHOPV2_CORE . 'inc/plugin_model.php';
require EWEI_SHOPV2_PLUGIN . 'commission/core/model.php';

class Appdown_EweiShopV2Page extends MobileLoginPage
{
    public function main()
    {
        global $_W;
        global $_GPC;

        //判断是苹果还是安卓
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $is_ios = 0;
        $is_wx = 2;
        if (strpos($agent, 'iphone') || strpos($agent, 'ipad')) $is_ios = 1;
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger'))$is_wx = 1;

        $info = $_W['shopset']['shop'];
        include $this->template('',get_defined_vars());

    }

    public function qr(){

        global $_W;
        global $_GPC;

        $model = m('plugin')->loadModel($GLOBALS['_W']['plugin']);
        $member = m('member')->getMember($_W['openid']);
        $qr = $model->createMyShareDownQrcode($member['id']);

        show_json(0,array('qrcode'=>$qr));
    }

    //----------------deng start send_push test-------------
    public function test(){

        $openid = 'wx_13512393092';

        $member['nickname'] = '灯';
        $credittext = '金额';
        $pointtext = '0.01';
        $pointcolor = '#FFFFF';
        $_W['shopset']['shop']['name'] = '趣味荟';
        $remark = '123444';

        $message = array( 'first' => array('value' => '亲爱的' . $member['nickname'] . '，您的' . $credittext . '发生变动，具体如下:', 'color' => '#ff0000'), 'keyword1' => array('title' => '获得时间', 'value' => date('Y-m-d H:i', time()), 'color' => '#000000'), 'keyword2' => array('title' => '获得积分', 'value' => $pointtext, 'color' => $pointcolor), 'keyword3' => array('title' => '获得原因', 'value' => '管理员后台手动处理', 'color' => '#000000'), 'keyword4' => array('title' => '当前积分', 'value' => (double) $member['credit1'] . $credittext, 'color' => '#ff0000'), 'remark' => array('value' => "\n" . $_W['shopset']['shop']['name'] . '感谢您的支持，如有疑问请联系在线客服。', 'color' => '#000000') );
        $text = '亲爱的[粉丝昵称]， 您的' . $credittext . '发生变动，具体内容如下：' . "\n\n" . '积分变动：[积分变动]' . "\n" . '变动时间：[赠送时间]' . "\n" . '充值方式：管理员后台处理' . "\n" . '当前积分余额：[积分余额] ' . "\n" . $remark;
        $url = 'http"//www.baidu.com';
        $datas = array( array('name' => '商城名称', 'value' => $_W['shopset']['shop']['name']), array('name' => '粉丝昵称', 'value' => $member['nickname']), array('name' => '积分变动', 'value' => $pointtext), array('name' => '赠送时间', 'value' => date('Y-m-d H:i', time())), array('name' => '积分余额', 'value' => (double) $member['credit1'] . $credittext) );

        $arr = array('openid' => $openid, 'tag' => 'backpoint_ok', 'default' => $message, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas);

        $data = m('notice')->sendNotice($arr);
        dump($data);exit;
    }
    //-----------------deng end send_push test--------------

}


?>