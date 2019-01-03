<?php
//error_reporting(0);

require dirname(__FILE__) . '/../../../../framework/bootstrap.inc.php';
require IA_ROOT . '/addons/ewei_shopv2/defines.php';
require IA_ROOT . '/addons/ewei_shopv2/core/inc/functions.php';
require IA_ROOT . '/addons/ewei_shopv2/core/inc/plugin_model.php';
require IA_ROOT . '/addons/ewei_shopv2/core/inc/com_model.php';

define('IN_MOBILE', true);
$dn = DIRECTORY_SEPARATOR;
define('SQ_ROOT',IA_ROOT.$dn.'addons'.$dn.'ewei_shopv2'.$dn.'sqpay'.$dn);

require_once(SQ_ROOT."common/Common.php");
require_once(SQ_ROOT."common/HttpClient.php");
require_once(SQ_ROOT."common/CFCARAUtil.php");

$CommonData = array();
$CommonData["code"] =$_POST["code"];
$CommonData["msg"] =$_POST["msg"];
$CommonData["responseType"] =$_POST["responseType"];
$CommonData["responseParameters"] =$_POST["responseParameters"];
$sign = $_POST["sign"];

/*$dataStr = Common::joinMapValue($CommonData);

if (!CFCARAUtil::verifyMessageByP1($dataStr,$sign)) {
    $this->fail();
};*/

$get = $_POST;
new EweiShopWechatPay($get);
exit('fail');
class EweiShopWechatPay
{
    public $get;
    public $type;
    public $total_fee;
    public $set;
    public $setting;
    public $sec;
    public $sign;
    public $isapp = false;
    public $is_jie = false;

    public function __construct($get)
    {
        global $_W;
        $this->get = $get;
        $this->get['responseParameters'] = json_decode($this->get['responseParameters'],true);
        //双乾支付不支持附加参数 订单尾号生成特殊意义
        if(!preg_match('/[^\d]*([\d]*)[^\d]*/i',$this->get['responseParameters']['merMerOrderNo'],$matches) or !isset($matches['1']) and !empty($matches['1'])){
            $this->fail();
        }

        $strs[] = intval(substr($matches[1],0,2));
        $strs[] = intval(substr($matches[1],2,2));
        $this->type = $strs[1];
        $this->total_fee = round($this->get['responseParameters']['payAmount'], 2);

        $this->get['out_trade_no'] = $this->get['responseParameters']['merMerOrderNo'];

        $GLOBALS['_W']['uniacid'] = $strs[0];
        $_W['uniacid'] = intval($strs[0]);
        $this->init();
    }

    public function success()
    {
        $result = [
            'code' => '000000',
            'msg' => '成功',
            ];
        echo  json_encode($result,JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function fail()
    {
        $result = array('return_code' => 'FAIL');
        echo array2xml($result);
        exit();
    }

    public function init()
    {
        if ($this->type == '0') {

            $this->order();
        }

        else if ($this->type == '1') {

            $this->recharge();
        }

        else if ($this->type == '2') {

            $this->creditShop();
        }

        else if ($this->type == '3') {

            $this->creditShopFreight();
        }

        else if ($this->type == '4') {

            $this->coupon();
        }

        else if ($this->type == '5') {

            $this->groups();
        }

        else if ($this->type == '6') {

            $this->threen();
        }

        else if ($this->type == '10') {

            $this->mr();
        }

        else if ($this->type == '11') {

            $this->pstoreCredit();
        }

        else if ($this->type == '12') {

            $this->pstore();
        }

        else if ($this->type == '13') {

            $this->cashier();
        }

        else if ($this->type == '14') {

            $this->wxapp_order();
        }

        else if ($this->type == '15') {

            $this->wxapp_recharge();
        }

        else if ($this->type == '16') {

            $this->wxapp_coupon();
        }

        else if ($this->type == '17') {

            $this->grant();
        }

        else if ($this->type == '18') {

            $this->plugingrant();
        }

        else if ($this->type == '19') {

            $this->article();
        }







        $this->success();
    }

    /**

     * 订单支付

     */
    public function order()
    {
        global $_W;

        /*if (!($this->publicMethod())) {

            exit('order');
        }*/
        $tid = trim($this->get['out_trade_no']);
        $isborrow = 0;
        $borrowopenid = '';


        if (strpos($tid, '_borrow') !== false) {

            $tid = str_replace('_borrow', '', $tid);
            $isborrow = 1;
            $borrowopenid = $this->get['openid'];
        }





        if (strpos($tid, '_B') !== false) {

            $tid = str_replace('_B', '', $tid);
            $isborrow = 1;
            $borrowopenid = $this->get['openid'];
        }





        if (strexists($tid, 'GJ')) {

            $tids = explode('GJ', $tid);
            $tid = $tids[0];
        }





        $ispeerpay = 0;
        $tid2 = 0;


        if (26 < strlen($tid)) {

            $tid2 = $tid;
            $ispeerpay = 1;
        }





        $paytype = 21;
        if (strexists($borrowopenid, '2088') || is_numeric($borrowopenid)) {

            $paytype = 22;
        }




        $tid = substr($tid, 0, 26);
        $order = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_order') . ' WHERE ordersn = :ordersn AND uniacid = :uniacid', array(':ordersn' => $tid, ':uniacid' => $_W['uniacid']));
        $sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `module`=:module AND `tid`=:tid  limit 1';
        $params = array();
        $params[':tid'] = $tid;
        $params[':module'] = 'ewei_shopv2';
        $log = pdo_fetch($sql, $params);

        $log['fee'] = 0.01;
        if (!(empty($log)) && (($log['status'] == '0') || $ispeerpay) && (($log['fee'] == $this->total_fee) || $ispeerpay)) {

            pdo_update('ewei_shop_order', array('paytype' => $paytype, 'isborrow' => $isborrow, 'borrowopenid' => $borrowopenid, 'apppay' => ($this->isapp ? 1 : 0)), array('ordersn' => $log['tid'], 'uniacid' => $log['uniacid']));
            $site = WeUtility::createModuleSite($log['module']);


            if (!(empty($ispeerpay))) {

                $ispeerpay = m('order')->checkpeerpay($order['id']);


                if (!(empty($ispeerpay))) {

                    m('order')->setOrderPayType($order['id'], $paytype);
                    $openid = $this->get['openid'];
                    $member = m('member')->getMember($openid);
                    m('order')->peerStatus(array('pid' => $ispeerpay['id'], 'uid' => $member['id'], 'uname' => $member['nickname'], 'usay' => '支持一下，么么哒!', 'price' => $this->total_fee, 'createtime' => time(), 'openid' => $openid, 'headimg' => $member['avatar'], 'tid' => $tid2));
                    unset($_SESSION['peerpaytid']);
                    $peerpay_info = (double) pdo_fetchcolumn('select SUM(price) from ' . tablename('ewei_shop_order_peerpay_payinfo') . ' where pid=:pid limit 1', array(':pid' => $ispeerpay['id']));


                    if ($peerpay_info < $ispeerpay['peerpay_realprice']) {

                        $this->success();
                    }



                }



            }





            if (!(is_error($site))) {

                $method = 'payResult';


                if (method_exists($site, $method)) {

                    $ret = array();
                    $ret['acid'] = $log['acid'];
                    $ret['uniacid'] = $log['uniacid'];
                    $ret['result'] = 'success';
                    $ret['type'] = $log['type'];
                    $ret['from'] = 'return';
                    $ret['tid'] = $log['tid'];
                    $ret['user'] = $log['openid'];
                    $ret['fee'] = $log['fee'];
                    $ret['tag'] = $log['tag'];
                    $result = $site->$method($ret);


                    if ($result) {

                        $log['tag'] = iunserializer($log['tag']);
                        $log['tag']['transaction_id'] = $this->get['transaction_id'];
                        $record = array();
                        $record['status'] = '1';
                        $record['tag'] = iserializer($log['tag']);
                        pdo_update('core_paylog', $record, array('plid' => $log['plid']));
                    }



                }



            }



        }

        else {

            $this->fail();
        }

    }

    /**

     * 会员充值

     */
    public function recharge()
    {
        global $_W;


       /* if (!($this->publicMethod())) {

            exit('recharge');
        }*/





        $logno = trim($this->get['out_trade_no']);
        $isborrow = 0;
        $borrowopenid = '';


        if (strpos($logno, '_borrow') !== false) {

            $logno = str_replace('_borrow', '', $logno);
            $isborrow = 1;
            $borrowopenid = $this->get['openid'];
        }





        if (empty($logno)) {

            $this->fail();
        }





        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_log') . ' WHERE `uniacid`=:uniacid and `logno`=:logno limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
        $OK = !(empty($log)) && empty($log['status']) && ($log['money'] == $this->total_fee);


        if ($OK) {

            pdo_update('ewei_shop_member_log', array('status' => 1, 'rechargetype' => 'wechat', 'isborrow' => $isborrow, 'borrowopenid' => $borrowopenid, 'apppay' => ($this->isapp ? 1 : 0)), array('id' => $log['id']));
            $shopset = m('common')->getSysset('shop');
            m('member')->setCredit($log['openid'], 'credit2', $log['money'], array(0, $shopset['name'] . '会员充值:wechatnotify:credit2:' . $log['money']));
            m('member')->setRechargeCredit($log['openid'], $log['money']);
            com_run('sale::setRechargeActivity', $log);
            com_run('coupon::useRechargeCoupon', $log);
            m('notice')->sendMemberLogMessage($log['id']);
        }



    }

    /**

     * 积分商城兑换

     */
    public function creditShop()
    {
        global $_W;


       /* if (!($this->publicMethod())) {

            exit('creditShop');
        }*/





        $logno = trim($this->get['out_trade_no']);


        if (empty($logno)) {

            exit();
        }





        $logno = str_replace('_borrow', '', $logno);


        if (p('creditshop')) {

            p('creditshop')->payResult($logno, 'wechat', $this->total_fee, ($this->isapp ? true : false));
        }



    }

    /**

     * 积分兑换运费问题

     */
    public function creditShopFreight()
    {
        global $_W;


     /*   if (!($this->publicMethod())) {

            exit('creditShopFreight');
        }*/





        $dispatchno = trim($this->get['out_trade_no']);
        $dispatchno = str_replace('_borrow', '', $dispatchno);


        if (empty($dispatchno)) {

            exit();
        }





        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_creditshop_log') . ' WHERE `dispatchno`=:dispatchno and `uniacid`=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':dispatchno' => $dispatchno));


        if (!(empty($log)) && ($log['dispatchstatus'] < 0)) {

            pdo_update('ewei_shop_creditshop_log', array('dispatchstatus' => 1), array('dispatchno' => $dispatchno));
        }



    }

    /**

     * 优惠券支付

     */
    public function coupon()
    {
        global $_W;


       /* if (!($this->publicMethod())) {

            exit('coupon');
        }*/





        $logno = str_replace('_borrow', '', $this->get['out_trade_no']);
        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_coupon_log') . ' WHERE `logno`=:logno and `uniacid`=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
        $coupon = pdo_fetchcolumn('select money from ' . tablename('ewei_shop_coupon') . ' where id=:id limit 1', array(':id' => $log['couponid']));


        if ($coupon == $this->total_fee) {

            com_run('coupon::payResult', $logno);
        }



    }

    /**

     * 优惠券支付

     */
    public function wxapp_coupon()
    {
        global $_W;
        $logno = str_replace('_borrow', '', $this->get['out_trade_no']);
        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_coupon_log') . ' WHERE `logno`=:logno and `uniacid`=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
        $coupon = pdo_fetchcolumn('select money from ' . tablename('ewei_shop_coupon') . ' where id=:id limit 1', array(':id' => $log['couponid']));


        if ($coupon == $this->total_fee) {

            com_run('coupon::payResult', $logno);
        }



    }

    /**

     * 拼团支付

     */
    public function groups()
    {
        global $_W;


      /*  if (!($this->publicMethod())) {

            exit('groups');
        }*/


        $orderno = trim($this->get['out_trade_no']);
        $orderno = str_replace('_borrow', '', $orderno);


        if (empty($orderno)) {

            exit();
        }





        if ($this->is_jie) {

            pdo_update('ewei_shop_groups_order', array('isborrow' => '1', 'borrowopenid' => $this->get['openid']), array('orderno' => $orderno, 'uniacid' => $_W['uniacid']));
        }





        if (p('groups')) {

            p('groups')->payResult($orderno, 'wechat', ($this->isapp ? true : false));
        }



    }

    /**

     * 3N营销支付

     */
    public function threen()
    {
        global $_W;


     /*   if (!($this->publicMethod())) {

            exit('threen');
        }*/





        $orderno = trim($this->get['out_trade_no']);
        $orderno = str_replace('_borrow', '', $orderno);


        if (empty($orderno)) {

            exit();
        }





        if ($this->is_jie) {

            pdo_update('ewei_shop_threen_log', array('isborrow' => '1', 'borrowopenid' => $this->get['openid']), array('logno' => $orderno, 'uniacid' => $_W['uniacid']));
        }





        if (p('threen')) {

            p('threen')->payResult($orderno, 'wechat', ($this->isapp ? true : false));
        }



    }

    /**

     * 应用授权中心（定制）

     */
    public function grant()
    {
        global $_W;
        $setting = pdo_fetch('select * from ' . tablename('ewei_shop_system_grant_setting') . ' where id = 1 limit 1 ');


        if (0 < $setting['weixin']) {

            ksort($this->get);
            $string1 = '';


            foreach ($this->get as $k => $v ) {

                if (($v != '') && ($k != 'sign')) {

                    $string1 .= $k . '=' . $v . '&';
                }



            }



            $this->sign = strtoupper(md5($string1 . 'key=' . $setting['apikey']));


            if ($this->sign == $this->get['sign']) {

                $order = pdo_fetch('select * from ' . tablename('ewei_shop_system_grant_order') . ' where logno = \'' . $this->get['out_trade_no'] . '\'');
                pdo_update('ewei_shop_system_grant_order', array('paytime' => time(), 'paystatus' => 1), array('logno' => $this->get['out_trade_no']));
                $plugind = explode(',', $order['pluginid']);
                $data = array('logno' => $order['logno'], 'uniacid' => $order['uniacid'], 'code' => $order['code'], 'type' => 'pay', 'month' => $order['month'], 'isagent' => $order['isagent'], 'createtime' => time());


                foreach ($plugind as $key => $value ) {

                    $plugin = pdo_fetch('select `identity` from ' . tablename('ewei_shop_plugin') . ' where id = ' . $value . ' ');
                    $data['identity'] = $plugin['identity'];
                    $data['pluginid'] = $value;
                    pdo_insert('ewei_shop_system_grant_log', $data);
                    $id = pdo_insertid();


                    if (m('grant')) {

                        m('grant')->pluginGrant($id);
                    }



                }

            }



        }



    }

    /**

     * 应用授权中心

     */
    public function plugingrant()
    {
        global $_W;
        $setting = pdo_fetch('select * from ' . tablename('ewei_shop_system_plugingrant_setting') . ' where 1 = 1 limit 1 ');


        if (0 < $setting['weixin']) {

            ksort($this->get);
            $string1 = '';


            foreach ($this->get as $k => $v ) {

                if (($v != '') && ($k != 'sign')) {

                    $string1 .= $k . '=' . $v . '&';
                }



            }



            $this->sign = strtoupper(md5($string1 . 'key=' . $setting['apikey']));


            if ($this->sign == $this->get['sign']) {

                $order = pdo_fetch('select * from ' . tablename('ewei_shop_system_plugingrant_order') . ' where logno = \'' . $this->get['out_trade_no'] . '\'');
                pdo_update('ewei_shop_system_plugingrant_order', array('paytime' => time(), 'paystatus' => 1), array('logno' => $this->get['out_trade_no']));
                $plugind = explode(',', $order['pluginid']);
                $data = array('logno' => $order['logno'], 'uniacid' => $order['uniacid'], 'type' => 'pay', 'month' => $order['month'], 'createtime' => time());


                foreach ($plugind as $key => $value ) {

                    $plugin = pdo_fetch('select `identity` from ' . tablename('ewei_shop_plugin') . ' where id = ' . $value . ' ');
                    $data['identity'] = $plugin['identity'];
                    $data['pluginid'] = $value;
                    pdo_query('update ' . tablename('ewei_shop_system_plugingrant_plugin') . ' set sales = sales + 1 where pluginid = ' . $value . ' ');
                    pdo_insert('ewei_shop_system_plugingrant_log', $data);
                    $id = pdo_insertid();


                    if (p('grant')) {

                        p('grant')->pluginGrant($id);
                    }



                }

            }



        }



    }

    /**

     * 话费充值

     */
    public function mr()
    {
        global $_W;


   /*     if (!($this->publicMethod())) {

            exit('mr');
        }*/





        $ordersn = trim($this->get['out_trade_no']);
        $isborrow = 0;
        $borrowopenid = '';


        if (strpos($ordersn, '_borrow') !== false) {

            $ordersn = str_replace('_borrow', '', $ordersn);
            $isborrow = 1;
            $borrowopenid = $this->get['openid'];
        }





        if (empty($ordersn)) {

            exit();
        }





        if (p('mr')) {

            $price = pdo_fetchcolumn('select payprice from ' . tablename('ewei_shop_mr_order') . ' where ordersn=:ordersn limit 1', array(':ordersn' => $ordersn));


            if ($price == $this->total_fee) {

                if ($isborrow == 1) {

                    pdo_update('ewei_shop_order', array('isborrow' => $isborrow, 'borrowopenid' => $borrowopenid), array('ordersn' => $ordersn));
                }





                p('mr')->payResult($ordersn, 'wechat');
            }



        }



    }

    /**

     * 门店积分充值

     */
    public function pstoreCredit()
    {
        global $_W;


       /* if (!($this->publicMethod())) {

            exit('pstoreCredit');
        }*/





        $ordersn = trim($this->get['out_trade_no']);
        $ordersn = str_replace('_borrow', '', $ordersn);


        if (empty($ordersn)) {

            exit();
        }





        if (p('pstore')) {

            p('pstore')->payResult($ordersn, $this->total_fee);
        }



    }

    /**

     * 门店支付

     */
    public function pstore()
    {
        global $_W;


      /*  if (!($this->publicMethod())) {

            exit('pstore');
        }*/





        $ordersn = trim($this->get['out_trade_no']);
        $ordersn = str_replace('_borrow', '', $ordersn);


        if (empty($ordersn)) {

            exit();
        }





        if (p('pstore')) {

            p('pstore')->wechat_complete($ordersn);
        }



    }

    /**

     * 收银台支付

     */
    public function cashier()
    {
        global $_W;
        $ordersn = trim($this->get['out_trade_no']);


        if (empty($ordersn)) {

            exit();
        }





        if (p('cashier')) {

            p('cashier')->payResult($ordersn);
        }



    }

    /**

     * 小程序 订单支付

     */
    public function wxapp_order()
    {
        $tid = $this->get['out_trade_no'];


        if (strexists($tid, 'GJ')) {

            $tids = explode('GJ', $tid);
            $tid = $tids[0];
        }





        $sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `module`=:module AND `tid`=:tid  limit 1';
        $params = array();
        $params[':tid'] = $tid;
        $params[':module'] = 'ewei_shopv2';
        $log = pdo_fetch($sql, $params);


        if (!(empty($log)) && ($log['status'] == '0') && ($log['fee'] == $this->total_fee)) {

            $site = WeUtility::createModuleSite($log['module']);


            if (!(is_error($site))) {

                $method = 'payResult';


                if (method_exists($site, $method)) {

                    $ret = array();
                    $ret['acid'] = $log['acid'];
                    $ret['uniacid'] = $log['uniacid'];
                    $ret['result'] = 'success';
                    $ret['type'] = $log['type'];
                    $ret['from'] = 'return';
                    $ret['tid'] = $log['tid'];
                    $ret['user'] = $log['openid'];
                    $ret['fee'] = $log['fee'];
                    $ret['tag'] = $log['tag'];
                    pdo_update('ewei_shop_order', array('paytype' => 21, 'apppay' => 2), array('ordersn' => $log['tid'], 'uniacid' => $log['uniacid']));
                    $result = $site->$method($ret);


                    if ($result) {

                        $log['tag'] = iunserializer($log['tag']);
                        $log['tag']['transaction_id'] = $this->get['transaction_id'];
                        $record = array();
                        $record['status'] = '1';
                        $record['tag'] = iserializer($log['tag']);
                        pdo_update('core_paylog', $record, array('plid' => $log['plid']));
                    }



                }



            }



        }

        else {

            $this->fail();
        }

    }

    /**

     * 小程序 会员充值

     */
    public function wxapp_recharge()
    {
        global $_W;
        $logno = trim($this->get['out_trade_no']);


        if (empty($logno)) {

            $this->fail();
        }





        $log = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_log') . ' WHERE `uniacid`=:uniacid and `logno`=:logno limit 1', array(':uniacid' => $_W['uniacid'], ':logno' => $logno));
        $OK = !(empty($log)) && empty($log['status']) && ($log['money'] == $this->total_fee);


        if ($OK) {

            pdo_update('ewei_shop_member_log', array('status' => 1, 'rechargetype' => 'wechat', 'apppay' => 2), array('id' => $log['id']));
            $shopset = m('common')->getSysset('shop');
            m('member')->setCredit($log['openid'], 'credit2', $log['money'], array(0, $shopset['name'] . '会员充值:wechatnotify:credit2:' . $log['money']));
            m('member')->setRechargeCredit($log['openid'], $log['money']);
            com_run('sale::setRechargeActivity', $log);
            com_run('coupon::useRechargeCoupon', $log);
            m('notice')->sendMemberLogMessage($log['id']);
        }

        else if ($log['money'] == $this->total_fee) {

            pdo_update('ewei_shop_member_log', array('rechargetype' => 'wechat', 'apppay' => 2), array('id' => $log['id']));
        }



    }

    /**

     * 使用商城自带支付 公用方法

     * @return bool

     */
    public function publicMethod()
    {
        global $_W;


        if (empty($_W['uniacid'])) {

            return false;
        }





        list($set, $payment) = m('common')->public_build();
        $this->set = $set;
        if (empty($payment['is_new']) || ($this->get['trade_type'] == 'APP')) {

            $this->setting = uni_setting($_W['uniacid'], array('payment'));
            if (is_array($this->setting['payment']) || ($this->set['weixin_jie'] == 1) || ($this->set['weixin_sub'] == 1) || ($this->set['weixin_jie_sub'] == 1) || ($this->get['trade_type'] == 'APP')) {

                $this->is_jie = (strpos($this->get['out_trade_no'], '_B') !== false) || (strpos($this->get['out_trade_no'], '_borrow') !== false);
                $sec_yuan = m('common')->getSec();
                $this->sec = iunserializer($sec_yuan['sec']);
                if ((($this->set['weixin_jie'] == 1) && $this->is_jie) || ($this->set['weixin_sub'] == 1) || (($this->set['weixin_jie_sub'] == 1) && $this->is_jie)) {

                    if ($this->set['weixin_sub'] == 1) {

                        $wechat = array('version' => 1, 'key' => $this->sec['apikey_sub'], 'apikey' => $this->sec['apikey_sub']);
                    }





                    if (($this->set['weixin_jie'] == 1) && $this->is_jie) {

                        $wechat = array('version' => 1, 'key' => $this->sec['apikey'], 'apikey' => $this->sec['apikey']);
                    }





                    if (($this->set['weixin_jie_sub'] == 1) && $this->is_jie) {

                        $wechat = array('version' => 1, 'key' => $this->sec['apikey_jie_sub'], 'apikey' => $this->sec['apikey_jie_sub']);
                    }



                }

                else if ($this->set['weixin'] == 1) {

                    $wechat = $this->setting['payment']['wechat'];


                    if (IMS_VERSION <= 0.80000000000000004) {

                        $wechat['apikey'] = $wechat['signkey'];
                    }



                }





                if (($this->get['trade_type'] == 'APP') && ($this->set['app_wechat'] == 1)) {

                    $this->isapp = true;
                    $wechat = array('version' => 1, 'key' => $this->sec['app_wechat']['apikey'], 'apikey' => $this->sec['app_wechat']['apikey'], 'appid' => $this->sec['app_wechat']['appid'], 'mchid' => $this->sec['app_wechat']['merchid']);
                }





                if (!(empty($wechat))) {

                    ksort($this->get);
                    $string1 = '';


                    foreach ($this->get as $k => $v ) {

                        if (($v != '') && ($k != 'sign')) {

                            $string1 .= $k . '=' . $v . '&';
                        }



                    }



                    $wechat['apikey'] = (($wechat['version'] == 1 ? $wechat['key'] : $wechat['apikey']));
                    $this->sign = strtoupper(md5($string1 . 'key=' . $wechat['apikey']));
                    $this->get['openid'] = ((isset($this->get['sub_openid']) ? $this->get['sub_openid'] : $this->get['openid']));


                    if ($this->sign == $this->get['sign']) {

                        return true;
                    }



                }



            }



        }

        else if (!(is_error($payment))) {

            ksort($this->get);
            $string1 = '';


            foreach ($this->get as $k => $v ) {

                if (($v != '') && ($k != 'sign')) {

                    $string1 .= $k . '=' . $v . '&';
                }



            }



            $this->sign = strtoupper(md5($string1 . 'key=' . $payment['apikey']));
            $this->get['openid'] = ((isset($this->get['sub_openid']) ? $this->get['sub_openid'] : $this->get['openid']));


            if ($this->sign == $this->get['sign']) {

                return true;
            }



        }





        return false;
    }

    /**
     * 文章支付回调
     */
    public function article()
    {
        $logno = trim($this->get['out_trade_no']);

        $article_order = db('article_order')->where(['order_sn' => $logno,'status' => 1])->find();
        $article = db('article')->field('id')->where(['id' => $article_order['article_id'],'is_pay' => 1])->find();
        if(!$article_order or !$article or  ($article_order['price'] != $this->total_fee)){
            $this->fail();
        }
        db()->startTrans();
        try{

            if(!db('article_order')->where(['id' => $article_order['id']])->update(['success_time' => time(),'status' => 2,'updated_time' => time(),'pay_type' => 1])) throw new PDOException('');
            if(!db('article')->where(['id' => $article['id']])->update(['is_pay' => 2])) throw new PDOException('');
            db()->commit();

        }catch (\PDOException $e){

            db()->rollback();
            $this->fail();

        }
        $this->success();

    }
}


?>