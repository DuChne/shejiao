<?php
if (!(defined('IN_IA'))) {

    exit('Access Denied');
}
use think\Db;
use think\Config;

class Home_EweiShopV2Page extends PluginMobilePage
{
    public $prefix;
    public function __construct()
    {
        $this->prefix = Config::get('database.prefix');
        parent::__construct();
    }

    public function store()
    {
        global $_W;
        global $_GPC;

        $name = Db::name('ewei_shop_member a')->field('a.nickname,a.avatar,a.identity,b.*')->leftJoin([$this->prefix.'ewei_shop_merch_user' => 'b'],'a.openid = b.openid','left')->where(['a.openid' => $_W['openid'],'b.status' => 1])->find();

        $name['logo'] = tomedia($name['logo']);
        show_json(1,$name);

    }
    public function updatestore(){
        global $_W;
        global $_GPC;
        $id = intval($_GPC['merchid']);
        $data['logo'] = $_GPC['logo'];
        $data['merchname'] = trim($_GPC['merchname']);
        $res = pdo_update('ewei_shop_merch_user',$data,['id'=>$id,'uniacid'=>$_W['uniacid']]);
        if($res){
            show_json(1,'修个成功');
        }else{
            show_json(0,"参数错误");
        }
    }
    /**
     * 根据merchid获取商户信息
     */
    public function storeDetail()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        if(!$id) show_json(0,'商户id不能为空');

        $name = Db::name('ewei_shop_merch_user')->field('*')->where(['id' => $id])->find();
        $name['nav'] = Db::name('ewei_shop_merch_nav')->field('navname,icon')->where(['merchid' => $name['id']])->select();
        foreach ($name['nav'] as &$item){
            $item['icon'] = tomedia($item['icon']);
        }

        $name['logo'] = tomedia($name['logo']);

        $Collection= Db::name('ewei_shop_member_favorite')->field('id')->where(['deleted' => 1,'openid' => $_W['openid'],'merchid' => $name['id'],'type' => 10])->find();
        if($Collection){
            $name['deleted'] = 1;
        }else{
            $name['deleted'] = 0;
        }

        show_json(1,$name);

    }

    /**
     * 判断商家入驻信息
     */
    public function isAdmission()
    {
        global $_W;
        global $_GPC;

        $prefix = Config::get('database.prefix');
        $name = Db::name('ewei_shop_member a')->field('b.id,b.status,b.reason')->leftJoin([$prefix.'ewei_shop_merch_reg' => 'b'],'a.openid = b.openid','left')->where(['a.openid' => $_W['openid']])->find();

        if($name['status'] === 1){
            show_json(1,['code' => 1]);
        }elseif ($name['status'] === 0) {
            show_json(1,['code' => 2]);
        }elseif ($name['status'] === -1){
            show_json(1,['code' => 3,'msg' => $name['reason']]);
        }elseif ($name['status'] === 10){
            $member = Db::name('ewei_shop_member_log')->field('id')->where(['relation' => $name['id'],'status' => 0])->find();
            show_json(1,['code' => 5,'msg' => '未支付','ewei_shop_member_log' => $member['id']]);
        }
        if(!$name['status']) show_json(1,['code' => 4]);


    }

    /**
     * 获取物流列表
     */
    public function getExpressList()
    {
        $express_list = m('express')->getExpressList();

        show_json(1,['list' => $express_list]);
    }

    /**
     * 发货
     */
    public function send()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        $name = Db::name('ewei_shop_member a')->field('b.id')->leftJoin([$this->prefix.'ewei_shop_merch_user' => 'b'],'a.openid = b.openid','left')->where(['a.openid' => $_W['openid'],'b.status' => 1])->find();
        $merchid = $name['id'];

        $item = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_order') . ' WHERE id = :id and uniacid=:uniacid and merchid = :merchid', array(':id' => $id, ':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
        if(intval($_GPC['yijuan_type'])==1){
            $item = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_order') . ' WHERE id = :id and uniacid=:uniacid', array(':id' => $id, ':uniacid' => $_W['uniacid']));
        }
        if (empty($item))
        {

            show_json(0,'订单未找到');
        }
        if (empty($item['addressid']))
        {
            show_json(0, '无收货地址，无法发货！');
        }
        if ($item['paytype'] != 3)
        {
            if ($item['status'] != 1)
            {
                show_json(0, '订单未付款，无法发货！');
            }
        }
        if ($_W['ispost'])
        {
            if (!(empty($_GPC['isexpress'])) && empty($_GPC['expresssn']))
            {
                show_json(0, '请输入快递单号！');
            }
            if (!(empty($item['transid'])))
            {
            }
            $time = time();
            pdo_update('ewei_shop_order', array('status' => 2, 'express' => trim($_GPC['express']), 'expresscom' => trim($_GPC['expresscom']), 'expresssn' => trim($_GPC['expresssn']), 'sendtime' => $time), array('id' => $item['id'], 'uniacid' => $_W['uniacid'], 'merchid' => $merchid));
            if(intval($_GPC['yijuan_type'])==1){
                pdo_update('ewei_shop_order', array('status' => 2, 'express' => trim($_GPC['express']), 'expresscom' => trim($_GPC['expresscom']), 'expresssn' => trim($_GPC['expresssn']), 'sendtime' => $time), array('id' => $item['id'], 'uniacid' => $_W['uniacid']));
            }
            if (!(empty($item['refundid'])))
            {
                $refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $item['refundid']));
                if (!(empty($refund)))
                {
                    pdo_update('ewei_shop_order_refund', array('status' => -1, 'endtime' => $time), array('id' => $item['refundid']));
                    pdo_update('ewei_shop_order', array('refundstate' => 0), array('id' => $item['id']));
                }
            }
            if ($item['paytype'] == 3)
            {
                m('order')->setStocksAndCredits($item['id'], 1);
            }
            m('notice')->sendOrderMessage($item['id']);
            plog('order.op.send', '订单发货 ID: ' . $item['id'] . ' 订单号: ' . $item['ordersn'] . ' <br/>快递公司: ' . $_GPC['expresscom'] . ' 快递单号: ' . $_GPC['expresssn']);
            show_json(1,'订单发货成功呢了呢');
        }
        $address = iunserializer($item['address']);
        if (!(is_array($address)))
        {
            $address = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_address') . ' WHERE id = :id and uniacid=:uniacid', array(':id' => $item['addressid'], ':uniacid' => $merchid));
        }
        $express_list = m('express')->getExpressList();
        include $this->template('',get_defined_vars());
    }

    /**
     * 获取上商品列表
     */
    public function getGoods()
    {
        global $_W;
        global $_GPC;
        $merch = Db::name('ewei_shop_member a')->field('b.id')->leftJoin([$this->prefix.'ewei_shop_merch_user' => 'b'],'a.openid = b.openid','left')->where(['a.openid' => $_W['openid']])->find();

        if(!$merch['id']){
            show_json(1,['list' => 0,'total' => 0]);
        }
        $type = intval($_GPC['use_shop']);
        $args['use_shop'] = $type;
        $args['merchid'] = $merch['id'];
        $goods = m('goods')->getList($args,true);

        show_json(1,$goods);

    }

    /**
     * 商品的上架下架
     */
    public function changeStatus()
    {
        global $_W;
        global $_GPC;
        $status = intval($_GPC['status']);
        if(!in_array($status,[0,1])) show_json(0,'请输入正确的状态');

        $id = intval($_GPC['id']);
        $merch = Db::name('ewei_shop_member a')->field('b.id')->leftJoin([$this->prefix.'ewei_shop_merch_user' => 'b'],'a.openid = b.openid','left')->where(['a.openid' => $_W['openid']])->find();

        if(!Db::name('ewei_shop_goods')->field('id')->where(['id' => $id,'merchid' => $merch['id']])->find()) show_json(0,'商品不存在');

        Db::name('ewei_shop_goods')->where(['id' => $id,'merchid' => $merch['id']])->update(['status' => $status]);

        show_json(1,'更改成功呢');

    }
    /**
     * 商家查看物流信息
     */
    public function express()
    {
        global $_W;
        global $_GPC;
        $merch = Db::name('ewei_shop_member a')->field('b.id')->leftJoin([$this->prefix.'ewei_shop_merch_user' => 'b'],'a.openid = b.openid','left')->where(['a.openid' => $_W['openid']])->find();
        $uniacid = $_W['uniacid'];
        $orderid = intval($_GPC['id']);
        $sendtype = intval($_GPC['sendtype']);
        $bundle = trim($_GPC['bundle']);
        if (empty($orderid))
        {
            header('location: ' . mobileUrl('order'));
            exit();
        }
        $order = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and merchid=:merchid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid, ':merchid' => $merch['id']));
        if($_GPC['yijuan_type']==1){
            $order = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid));
        }
        if (empty($order))
        {
            header('location: ' . mobileUrl('order'));
            exit();
        }
        $bundlelist = array();
        if ((0 < $order['sendtype']) && ($sendtype == 0))
        {
            $i = 1;
            while ($i <= intval($order['sendtype']))
            {
                $bundlelist[$i]['sendtype'] = $i;
                $bundlelist[$i]['orderid'] = $orderid;
                $bundlelist[$i]['goods'] = pdo_fetchall('select g.title,g.thumb,og.total,og.optionname as optiontitle,og.expresssn,og.express,' . "\r\n" . '                    og.sendtype,og.expresscom,og.sendtime from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid ' . ' where og.orderid=:orderid and og.sendtype = ' . $i . ' and og.uniacid=:uniacid ', array(':uniacid' => $uniacid, ':orderid' => $orderid));
                if (empty($bundlelist[$i]['goods']))
                {
                    unset($bundlelist[$i]);
                }
                ++$i;
            }
            $bundlelist = array_values($bundlelist);
        }
        if (empty($order['addressid']))
        {
            $this->message('订单非快递单，无法查看物流信息!');
        }
        if (!(2 <= $order['status']) && !((1 <= $order['status']) && (0 < $order['sendtype'])))
        {
            $this->message('订单未发货，无法查看物流信息!');
        }
        $condition = '';
        if (0 < $sendtype)
        {
            $condition = ' and og.sendtype = ' . $sendtype;
        }
        $goods = pdo_fetchall('select og.goodsid,og.price,g.title,g.thumb,og.total,g.credit,og.optionid,og.optionname as optiontitle,g.isverify,og.expresssn,og.express,' . "\r\n" . '            og.sendtype,og.expresscom,og.sendtime,g.storeids' . $diyformfields . "\r\n" . '            from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid ' . ' where og.orderid=:orderid ' . $condition . ' and og.uniacid=:uniacid ', array(':uniacid' => $uniacid, ':orderid' => $orderid));
        if (0 < $sendtype)
        {
            $order['express'] = $goods[0]['express'];
            $order['expresssn'] = $goods[0]['expresssn'];
            $order['expresscom'] = $goods[0]['expresscom'];
        }
        $expresslist = m('util')->getExpressList($order['express'], $order['expresssn']);
        include $this->template('',get_defined_vars());
    }


    /**
     * 获取商家订单列表
     */
    public function order()
    {
        global $_W;
        global $_GPC;
        $type = intval($_GPC['type']);
        switch ($type):
            case 0;
                $this->status0();
                break;
            case 1:
                $this->status1();
                break;
            case 2:
                $this->status2();
                break;
            case 3:
                $this->status3();
                break;
            case 4:
                $this->status4();
                break;
            case 5:
                $this->status5();
                break;
            case -1:
                $this->status_1();
                break;
            default:
                $this->main();
                break;
                endswitch;

    }
    public function main()
    {
        global $_W;
        global $_GPC;
        $orderData = $this->orderData('', 'main');
    }
    public function status0()
    {
        global $_W;
        global $_GPC;
        $orderData = $this->orderData(0, 'status0');
    }
    public function status1()
    {
        global $_W;
        global $_GPC;
        $orderData = $this->orderData(1, 'status1');
    }
    public function status2()
    {
        global $_W;
        global $_GPC;
        $orderData = $this->orderData(2, 'status2');
    }
    public function status3()
    {
        global $_W;
        global $_GPC;
        $orderData = $this->orderData(3, 'status3');
    }
    public function status4()
    {
        global $_W;
        global $_GPC;
        $orderData = $this->orderData(4, 'status4');
    }
    public function status5()
    {
        global $_W;
        global $_GPC;
        $orderData = $this->orderData(5, 'status5');
    }
    public function status_1()
    {
        global $_W;
        global $_GPC;
        $orderData = $this->orderData(-1, 'status_1');
    }
    public function ajaxgettotals()
    {
        $totals = $this->model->getOrderTotals();
        $result = ((empty($totals) ? array() : $totals));
        show_json(1, $result);
    }

    protected function orderData($status, $st)
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        if ($st == 'main')
        {
            $st = '';
        }
        else
        {
            $st = '.' . $st;
        }
        $sendtype = ((!(isset($_GPC['sendtype'])) ? 0 : $_GPC['sendtype']));
        $condition = ' o.uniacid = :uniacid and o.merchid = :merchid and o.deleted=0 and o.isparent=0';
        $uniacid = $_W['uniacid'];
        //$merchid = $_W['merchid'];
        $prefix = Config::get('database.prefix');
        $name = Db::name('ewei_shop_member a')->field('b.id,b.merchname')->leftJoin([$prefix.'ewei_shop_merch_user' => 'b'],'a.openid = b.openid','left')->where(['a.openid' => $_W['openid'],'b.status' => 1])->find();
        $merchid = $name['id'];
        $paras = $paras1 = array(':uniacid' => $uniacid, ':merchid' => $merchid);
        if (empty($starttime) || empty($endtime))
        {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $searchtime = trim($_GPC['searchtime']);
        if (!(empty($searchtime)) && is_array($_GPC['time']) && in_array($searchtime, array('create', 'pay', 'send', 'finish')))
        {
            $starttime = strtotime($_GPC['time']['start']);
            $endtime = strtotime($_GPC['time']['end']);
            $condition .= ' AND o.' . $searchtime . 'time >= :starttime AND o.' . $searchtime . 'time <= :endtime ';
            $paras[':starttime'] = $starttime;
            $paras[':endtime'] = $endtime;
        }
        if ($_GPC['paytype'] != '')
        {
            if ($_GPC['paytype'] == '2')
            {
                $condition .= ' AND ( o.paytype =21 or o.paytype=22 or o.paytype=23 )';
            }
            else
            {
                $condition .= ' AND o.paytype =' . intval($_GPC['paytype']);
            }
        }
        if (!(empty($_GPC['searchfield'])) && !(empty($_GPC['keyword'])))
        {
            $searchfield = trim(strtolower($_GPC['searchfield']));
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $paras[':keyword'] = $_GPC['keyword'];
            $sqlcondition = '';
            if ($searchfield == 'ordersn')
            {
                $condition .= ' AND locate(:keyword,o.ordersn)>0';
            }
            else if ($searchfield == 'member')
            {
                $condition .= ' AND (locate(:keyword,m.realname)>0 or locate(:keyword,m.mobile)>0 or locate(:keyword,m.nickname)>0)';
            }
            else if ($searchfield == 'address')
            {
                $condition .= ' AND ( locate(:keyword,a.realname)>0 or locate(:keyword,a.mobile)>0 or locate(:keyword,o.carrier)>0)';
            }
            else if ($searchfield == 'location')
            {
                $condition .= ' AND ( locate(:keyword,a.province)>0 or locate(:keyword,a.city)>0 or locate(:keyword,a.area)>0 or locate(:keyword,a.address)>0)';
            }
            else if ($searchfield == 'expresssn')
            {
                $condition .= ' AND locate(:keyword,o.expresssn)>0';
            }
            else if ($searchfield == 'saler')
            {
                $condition .= ' AND (locate(:keyword,sm.realname)>0 or locate(:keyword,sm.mobile)>0 or locate(:keyword,sm.nickname)>0 or locate(:keyword,s.salername)>0 )';
            }
            else if ($searchfield == 'store')
            {
                $condition .= ' AND (locate(:keyword,store.storename)>0)';
                $sqlcondition = ' left join ' . tablename('ewei_shop_merch_store') . ' store on store.id = o.verifystoreid and store.uniacid=o.uniacid';
            }
            else if ($searchfield == 'goodstitle')
            {
                $sqlcondition = ' inner join ( select DISTINCT(og.orderid) from ' . tablename('ewei_shop_order_goods') . ' og left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid where og.uniacid = \'' . $uniacid . '\' and (locate(:keyword,g.title)>0)) gs on gs.orderid=o.id';
            }
            else if ($searchfield == 'goodssn')
            {
                $sqlcondition = ' inner join ( select DISTINCT(og.orderid) from ' . tablename('ewei_shop_order_goods') . ' og left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid where og.uniacid = \'' . $uniacid . '\' and (((locate(:keyword,g.goodssn)>0)) or (locate(:keyword,og.goodssn)>0))) gs on gs.orderid=o.id';
            }
        }
        $statuscondition = '';
        if ($status !== '')
        {
            if ($status == '-1')
            {
                $statuscondition = ' AND o.status=-1 and o.refundtime=0';
            }
            else if ($status == '4')
            {
                $statuscondition = ' AND o.refundstate>0 and o.refundid<>0';
            }
            else if ($status == '5')
            {
                $statuscondition = ' AND o.refundtime<>0';
            }
            else if ($status == '1')
            {
                $statuscondition = ' AND ( o.status = 1 or (o.status=0 and o.paytype=3) )';
            }
            else if ($status == '0')
            {
                $statuscondition = ' AND o.status = 0 and o.paytype<>3';
            }
            else
            {
                $statuscondition = ' AND o.status = ' . intval($status);
            }
        }
        $agentid = intval($_GPC['agentid']);
        $p = p('commission');
        $level = 0;
        if ($p)
        {
            $cset = $p->getSet();
            $level = intval($cset['level']);
        }
        $olevel = intval($_GPC['olevel']);
        if (!(empty($agentid)) && (0 < $level))
        {
            $agent = $p->getInfo($agentid, array());
            if (!(empty($agent)))
            {
                $agentLevel = $p->getLevel($agentid);
            }
            if (empty($olevel))
            {
                if (1 <= $level)
                {
                    $condition .= ' and  ( o.agentid=' . intval($_GPC['agentid']);
                }
                if ((2 <= $level) && (0 < $agent['level2']))
                {
                    $condition .= ' or o.agentid in( ' . implode(',', array_keys($agent['level1_agentids'])) . ')';
                }
                if ((3 <= $level) && (0 < $agent['level3']))
                {
                    $condition .= ' or o.agentid in( ' . implode(',', array_keys($agent['level2_agentids'])) . ')';
                }
                if (1 <= $level)
                {
                    $condition .= ')';
                }
            }
            else if ($olevel == 1)
            {
                $condition .= ' and  o.agentid=' . intval($_GPC['agentid']);
            }
            else if ($olevel == 2)
            {
                if (0 < $agent['level2'])
                {
                    $condition .= ' and o.agentid in( ' . implode(',', array_keys($agent['level1_agentids'])) . ')';
                }
                else
                {
                    $condition .= ' and o.agentid in( 0 )';
                }
            }
            else if ($olevel == 3)
            {
                if (0 < $agent['level3'])
                {
                    $condition .= ' and o.agentid in( ' . implode(',', array_keys($agent['level2_agentids'])) . ')';
                }
                else
                {
                    $condition .= ' and o.agentid in( 0 )';
                }
            }
        }
        $sql = 'select o.* , a.realname as arealname,a.mobile as amobile,a.province as aprovince ,a.city as acity , a.area as aarea,a.address as aaddress, d.dispatchname,m.nickname,m.id as mid,m.realname as mrealname,m.mobile as mmobile,sm.id as salerid,sm.nickname as salernickname,s.salername,r.rtype,r.status as rstatus from ' . tablename('ewei_shop_order') . ' o' . ' left join ' . tablename('ewei_shop_order_refund') . ' r on r.id =o.refundid ' . ' left join ' . tablename('ewei_shop_member') . ' m on m.openid=o.openid and m.uniacid =  o.uniacid ' . ' left join ' . tablename('ewei_shop_member_address') . ' a on a.id=o.addressid ' . ' left join ' . tablename('ewei_shop_dispatch') . ' d on d.id = o.dispatchid ' . ' left join ' . tablename('ewei_shop_merch_saler') . ' s on s.openid = o.verifyopenid and s.uniacid=o.uniacid and s.merchid=o.merchid' . ' left join ' . tablename('ewei_shop_member') . ' sm on sm.openid = s.openid and sm.uniacid=s.uniacid' . ' ' . $sqlcondition . ' where ' . $condition . ' ' . $statuscondition . ' ORDER BY o.createtime DESC,o.status DESC  ';
        if (empty($_GPC['export']))
        {
            $sql .= 'LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
        }
        $list = pdo_fetchall($sql, $paras);
        $paytype = array( 0 => array('css' => 'default', 'name' => '未支付'), 1 => array('css' => 'danger', 'name' => '余额支付'), 11 => array('css' => 'default', 'name' => '后台付款'), 2 => array('css' => 'danger', 'name' => '在线支付'), 21 => array('css' => 'success', 'name' => '微信支付'), 22 => array('css' => 'warning', 'name' => '支付宝支付'), 23 => array('css' => 'warning', 'name' => '银联支付'), 3 => array('css' => 'primary', 'name' => '货到付款') );
        $orderstatus = array( -1 => array('css' => 'default', 'name' => '已关闭'), 0 => array('css' => 'danger', 'name' => '待付款'), 1 => array('css' => 'info', 'name' => '待发货'), 2 => array('css' => 'warning', 'name' => '待收货'), 3 => array('css' => 'success', 'name' => '已完成') );
        foreach ($list as &$value )
        {
            $s = $value['status'];
            $pt = $value['paytype'];
            $value['statusvalue'] = $s;
            $value['statuscss'] = $orderstatus[$value['status']]['css'];
           // $value['status'] = $orderstatus[$value['status']]['name'];
            $value['statusstr'] = $orderstatus[$value['status']]['name'];
            if (($pt == 3) && empty($value['statusvalue']))
            {
                $value['statuscss'] = $orderstatus[1]['css'];
                $value['status'] = $orderstatus[1]['name'];
            }
            if ($s == 1)
            {
                if ($value['isverify'] == 1)
                {
                    $value['statusstr'] = '待使用';
                }
                else if (empty($value['addressid']))
                {
                    $value['statusstr'] = '待取货';
                }
            }
            if ($s == -1)
            {
                if (!(empty($value['refundtime'])))
                {
                    $value['statusstr'] = '已退款';
                }
            }
            $value['paytypevalue'] = $pt;
            $value['css'] = $paytype[$pt]['css'];
            $value['paytype'] = $paytype[$pt]['name'];
            $value['dispatchname'] = ((empty($value['addressid']) ? '自提' : $value['dispatchname']));
            if (empty($value['dispatchname']))
            {
                $value['dispatchname'] = '快递';
            }
            if ($pt == 3)
            {
                $value['dispatchname'] = '货到付款';
            }
            else if ($value['isverify'] == 1)
            {
                $value['dispatchname'] = '线下核销';
            }
            else if ($value['isvirtual'] == 1)
            {
                $value['dispatchname'] = '虚拟物品';
            }
            else if (!(empty($value['virtual'])))
            {
                $value['dispatchname'] = '虚拟物品(卡密)<br/>自动发货';
            }
            if (($value['dispatchtype'] == 1) || !(empty($value['isverify'])) || !(empty($value['virtual'])) || !(empty($value['isvirtual'])))
            {
                $value['address'] = '';
                $carrier = iunserializer($value['carrier']);
                if (is_array($carrier))
                {
                    $value['addressdata']['realname'] = $value['realname'] = $carrier['carrier_realname'];
                    $value['addressdata']['mobile'] = $value['mobile'] = $carrier['carrier_mobile'];
                }
            }
            else
            {
                $address = iunserializer($value['address']);
                $isarray = is_array($address);
                $value['realname'] = (($isarray ? $address['realname'] : $value['arealname']));
                $value['mobile'] = (($isarray ? $address['mobile'] : $value['amobile']));
                $value['province'] = (($isarray ? $address['province'] : $value['aprovince']));
                $value['city'] = (($isarray ? $address['city'] : $value['acity']));
                $value['area'] = (($isarray ? $address['area'] : $value['aarea']));
                $value['address'] = (($isarray ? $address['address'] : $value['aaddress']));
                $value['address_province'] = $value['province'];
                $value['address_city'] = $value['city'];
                $value['address_area'] = $value['area'];
                $value['address_address'] = $value['address'];
                $value['address'] = $value['province'] . ' ' . $value['city'] . ' ' . $value['area'] . ' ' . $value['address'];
                $value['addressdata'] = array('realname' => $value['realname'], 'mobile' => $value['mobile'], 'address' => $value['address']);
            }
            $commission1 = -1;
            $commission2 = -1;
            $commission3 = -1;
            $m1 = false;
            $m2 = false;
            $m3 = false;
            if (!(empty($level)) && empty($agentid))
            {
                if (!(empty($value['agentid'])))
                {
                    $m1 = m('member')->getMember($value['agentid']);
                    $commission1 = 0;
                    if (!(empty($m1['agentid'])))
                    {
                        $m2 = m('member')->getMember($m1['agentid']);
                        $commission2 = 0;
                        if (!(empty($m2['agentid'])))
                        {
                            $m3 = m('member')->getMember($m2['agentid']);
                            $commission3 = 0;
                        }
                    }
                }
            }
            if (!(empty($agentid)))
            {
                $magent = m('member')->getMember($agentid);
            }
            $order_goods = pdo_fetchall('select g.id,g.title,g.thumb,g.goodssn,og.goodssn as option_goodssn, g.productsn,og.productsn as option_productsn, og.total,og.price,og.optionname as optiontitle, og.realprice,og.changeprice,og.oldprice,og.commission1,og.commission2,og.commission3,og.commissions,og.diyformdata,og.diyformfields,op.specs from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid ' . ' left join ' . tablename('ewei_shop_goods_option') . ' op on og.optionid = op.id ' . ' where og.uniacid=:uniacid and og.orderid=:orderid ', array(':uniacid' => $uniacid, ':orderid' => $value['id']));
            $goods = '';
            foreach ($order_goods as &$og )
            {
                if (!(empty($og['specs'])))
                {
                    $thumb = m('goods')->getSpecThumb($og['specs']);
                    if (!(empty($thumb)))
                    {
                        $og['thumb'] = $thumb;
                    }
                }
                if (!(empty($level)) && empty($agentid))
                {
                    $commissions = iunserializer($og['commissions']);
                    if (!(empty($m1)))
                    {
                        if (is_array($commissions))
                        {
                            $commission1 += ((isset($commissions['level1']) ? floatval($commissions['level1']) : 0));
                        }
                        else
                        {
                            $c1 = iunserializer($og['commission1']);
                            $l1 = $p->getLevel($m1['openid']);
                            $commission1 += ((isset($c1['level' . $l1['id']]) ? $c1['level' . $l1['id']] : $c1['default']));
                        }
                    }
                    if (!(empty($m2)))
                    {
                        if (is_array($commissions))
                        {
                            $commission2 += ((isset($commissions['level2']) ? floatval($commissions['level2']) : 0));
                        }
                        else
                        {
                            $c2 = iunserializer($og['commission2']);
                            $l2 = $p->getLevel($m2['openid']);
                            $commission2 += ((isset($c2['level' . $l2['id']]) ? $c2['level' . $l2['id']] : $c2['default']));
                        }
                    }
                    if (!(empty($m3)))
                    {
                        if (is_array($commissions))
                        {
                            $commission3 += ((isset($commissions['level3']) ? floatval($commissions['level3']) : 0));
                        }
                        else
                        {
                            $c3 = iunserializer($og['commission3']);
                            $l3 = $p->getLevel($m3['openid']);
                            $commission3 += ((isset($c3['level' . $l3['id']]) ? $c3['level' . $l3['id']] : $c3['default']));
                        }
                    }
                }
                $goods .= '' . $og['title'] . "\r\n";
                if (!(empty($og['optiontitle'])))
                {
                    $goods .= ' 规格: ' . $og['optiontitle'];
                }
                if (!(empty($og['option_goodssn'])))
                {
                    $og['goodssn'] = $og['option_goodssn'];
                }
                if (!(empty($og['option_productsn'])))
                {
                    $og['productsn'] = $og['option_productsn'];
                }
                if (!(empty($og['goodssn'])))
                {
                    $goods .= ' 商品编号: ' . $og['goodssn'];
                }
                if (!(empty($og['productsn'])))
                {
                    $goods .= ' 商品条码: ' . $og['productsn'];
                }
                $goods .= ' 单价: ' . ($og['price'] / $og['total']) . ' 折扣后: ' . ($og['realprice'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . ' 折扣后: ' . $og['realprice'] . "\r\n" . ' ';
                if (p('diyform') && !(empty($og['diyformfields'])) && !(empty($og['diyformdata'])))
                {
                    $diyformdata_array = p('diyform')->getDatas(iunserializer($og['diyformfields']), iunserializer($og['diyformdata']));
                    $diyformdata = '';
                    foreach ($diyformdata_array as $da )
                    {
                        $diyformdata .= $da['name'] . ': ' . $da['value'] . "\r\n";
                    }
                    $og['goods_diyformdata'] = $diyformdata;
                }
            }
            unset($og);
            if (!(empty($level)) && empty($agentid))
            {
                $value['commission1'] = $commission1;
                $value['commission2'] = $commission2;
                $value['commission3'] = $commission3;
            }
            $value['goods'] = set_medias($order_goods, 'thumb');
            $value['goods_str'] = $goods;
            if (!(empty($agentid)) && (0 < $level))
            {
                $commission_level = 0;
                if ($value['agentid'] == $agentid)
                {
                    $value['level'] = 1;
                    $level1_commissions = pdo_fetchall('select commission1,commissions  from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join  ' . tablename('ewei_shop_order') . ' o on o.id = og.orderid ' . ' where og.orderid=:orderid and o.agentid= ' . $agentid . '  and o.uniacid=:uniacid', array(':orderid' => $value['id'], ':uniacid' => $uniacid));
                    foreach ($level1_commissions as $c )
                    {
                        $commission = iunserializer($c['commission1']);
                        $commissions = iunserializer($c['commissions']);
                        if (empty($commissions))
                        {
                            $commission_level += ((isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default']));
                        }
                        else
                        {
                            $commission_level += ((isset($commissions['level1']) ? floatval($commissions['level1']) : 0));
                        }
                    }
                }
                else if (in_array($value['agentid'], array_keys($agent['level1_agentids'])))
                {
                    $value['level'] = 2;
                    if (0 < $agent['level2'])
                    {
                        $level2_commissions = pdo_fetchall('select commission2,commissions  from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join  ' . tablename('ewei_shop_order') . ' o on o.id = og.orderid ' . ' where og.orderid=:orderid and  o.agentid in ( ' . implode(',', array_keys($agent['level1_agentids'])) . ')  and o.uniacid=:uniacid', array(':orderid' => $value['id'], ':uniacid' => $uniacid));
                        foreach ($level2_commissions as $c )
                        {
                            $commission = iunserializer($c['commission2']);
                            $commissions = iunserializer($c['commissions']);
                            if (empty($commissions))
                            {
                                $commission_level += ((isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default']));
                            }
                            else
                            {
                                $commission_level += ((isset($commissions['level2']) ? floatval($commissions['level2']) : 0));
                            }
                        }
                    }
                }
                else if (in_array($value['agentid'], array_keys($agent['level2_agentids'])))
                {
                    $value['level'] = 3;
                    if (0 < $agent['level3'])
                    {
                        $level3_commissions = pdo_fetchall('select commission3,commissions from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join  ' . tablename('ewei_shop_order') . ' o on o.id = og.orderid ' . ' where og.orderid=:orderid and  o.agentid in ( ' . implode(',', array_keys($agent['level2_agentids'])) . ')  and o.uniacid=:uniacid', array(':orderid' => $value['id'], ':uniacid' => $uniacid));
                        foreach ($level3_commissions as $c )
                        {
                            $commission = iunserializer($c['commission3']);
                            $commissions = iunserializer($c['commissions']);
                            if (empty($commissions))
                            {
                                $commission_level += ((isset($commission['level' . $agentLevel['id']]) ? $commission['level' . $agentLevel['id']] : $commission['default']));
                            }
                            else
                            {
                                $commission_level += ((isset($commissions['level3']) ? floatval($commissions['level3']) : 0));
                            }
                        }
                    }
                }
                $value['commission'] = $commission_level;
            }
        }
        unset($value);
        if ($_GPC['export'] == 1)
        {
            plog('order.op.export', '导出订单');
            $columns = array( array('title' => '订单编号', 'field' => 'ordersn', 'width' => 24), array('title' => '粉丝昵称', 'field' => 'nickname', 'width' => 12), array('title' => '会员姓名', 'field' => 'mrealname', 'width' => 12), array('title' => 'openid', 'field' => 'openid', 'width' => 24), array('title' => '会员手机手机号', 'field' => 'mmobile', 'width' => 12), array('title' => '收货姓名(或自提人)', 'field' => 'realname', 'width' => 12), array('title' => '联系电话', 'field' => 'mobile', 'width' => 12), array('title' => '收货地址', 'field' => 'address_province', 'width' => 12), array('title' => '', 'field' => 'address_city', 'width' => 12), array('title' => '', 'field' => 'address_area', 'width' => 12), array('title' => '', 'field' => 'address_address', 'width' => 12), array('title' => '商品名称', 'field' => 'goods_title', 'width' => 24), array('title' => '商品编码', 'field' => 'goods_goodssn', 'width' => 12), array('title' => '商品规格', 'field' => 'goods_optiontitle', 'width' => 12), array('title' => '商品数量', 'field' => 'goods_total', 'width' => 12), array('title' => '商品单价(折扣前)', 'field' => 'goods_price1', 'width' => 12), array('title' => '商品单价(折扣后)', 'field' => 'goods_price2', 'width' => 12), array('title' => '商品价格(折扣后)', 'field' => 'goods_rprice1', 'width' => 12), array('title' => '商品价格(折扣后)', 'field' => 'goods_rprice2', 'width' => 12), array('title' => '支付方式', 'field' => 'paytype', 'width' => 12), array('title' => '配送方式', 'field' => 'dispatchname', 'width' => 12), array('title' => '商品小计', 'field' => 'goodsprice', 'width' => 12), array('title' => '运费', 'field' => 'dispatchprice', 'width' => 12), array('title' => '积分抵扣', 'field' => 'deductprice', 'width' => 12), array('title' => '余额抵扣', 'field' => 'deductcredit2', 'width' => 12), array('title' => '满额立减', 'field' => 'deductenough', 'width' => 12), array('title' => '优惠券优惠', 'field' => 'couponprice', 'width' => 12), array('title' => '订单改价', 'field' => 'changeprice', 'width' => 12), array('title' => '运费改价', 'field' => 'changedispatchprice', 'width' => 12), array('title' => '应收款', 'field' => 'price', 'width' => 12), array('title' => '状态', 'field' => 'status', 'width' => 12), array('title' => '下单时间', 'field' => 'createtime', 'width' => 24), array('title' => '付款时间', 'field' => 'paytime', 'width' => 24), array('title' => '发货时间', 'field' => 'sendtime', 'width' => 24), array('title' => '完成时间', 'field' => 'finishtime', 'width' => 24), array('title' => '快递公司', 'field' => 'expresscom', 'width' => 24), array('title' => '快递单号', 'field' => 'expresssn', 'width' => 24), array('title' => '订单备注', 'field' => 'remark', 'width' => 36), array('title' => '核销员', 'field' => 'salerinfo', 'width' => 24), array('title' => '核销门店', 'field' => 'storeinfo', 'width' => 36), array('title' => '订单自定义信息', 'field' => 'order_diyformdata', 'width' => 36), array('title' => '商品自定义信息', 'field' => 'goods_diyformdata', 'width' => 36) );
            if (!(empty($agentid)) && (0 < $level))
            {
                $columns[] = array('title' => '分销级别', 'field' => 'level', 'width' => 24);
                $columns[] = array('title' => '分销佣金', 'field' => 'commission', 'width' => 24);
            }
            foreach ($list as &$row )
            {
                $row['realname'] = str_replace('=', '', $row['realname']);
                $row['nickname'] = str_replace('=', '', $row['nickname']);
                $row['ordersn'] = $row['ordersn'] . ' ';
                if (0 < $row['deductprice'])
                {
                    $row['deductprice'] = '-' . $row['deductprice'];
                }
                if (0 < $row['deductcredit2'])
                {
                    $row['deductcredit2'] = '-' . $row['deductcredit2'];
                }
                if (0 < $row['deductenough'])
                {
                    $row['deductenough'] = '-' . $row['deductenough'];
                }
                if ($row['changeprice'] < 0)
                {
                    $row['changeprice'] = '-' . $row['changeprice'];
                }
                else if (0 < $row['changeprice'])
                {
                    $row['changeprice'] = '+' . $row['changeprice'];
                }
                if ($row['changedispatchprice'] < 0)
                {
                    $row['changedispatchprice'] = '-' . $row['changedispatchprice'];
                }
                else if (0 < $row['changedispatchprice'])
                {
                    $row['changedispatchprice'] = '+' . $row['changedispatchprice'];
                }
                if (0 < $row['couponprice'])
                {
                    $row['couponprice'] = '-' . $row['couponprice'];
                }
                $row['expresssn'] = $row['expresssn'] . ' ';
                $row['createtime'] = date('Y-m-d H:i:s', $row['createtime']);
                $row['paytime'] = ((!(empty($row['paytime'])) ? date('Y-m-d H:i:s', $row['paytime']) : ''));
                $row['sendtime'] = ((!(empty($row['sendtime'])) ? date('Y-m-d H:i:s', $row['sendtime']) : ''));
                $row['finishtime'] = ((!(empty($row['finishtime'])) ? date('Y-m-d H:i:s', $row['finishtime']) : ''));
                $row['salerinfo'] = '';
                $row['storeinfo'] = '';
                if (com('verify'))
                {
                    $verifyinfo = iunserializer($row['verifyinfo']);
                    if (!(empty($row['verifyopenid'])))
                    {
                        $saler = m('member')->getMember($row['verifyopenid']);
                        $merch_saler = pdo_fetch('select id,salername from ' . tablename('ewei_shop_merch_saler') . ' where openid=:openid and uniacid=:uniacid and merchid = :merchid limit 1 ', array(':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid'], ':openid' => $row['verifyopenid']));
                        $saler['salername'] = ((isset($merch_saler['salername']) ? $merch_saler['salername'] : ''));
                        $row['salerinfo'] = (('[' . isset($merch_saler['id']) ? $merch_saler['id'] : '' . ']' . $saler['salername'] . '(' . $row['nickname'] . ')'));
                    }
                    if (!(empty($row['verifystoreid'])))
                    {
                        $row['storeinfo'] = pdo_fetchcolumn('select storename from ' . tablename('ewei_shop_merch_store') . ' where id=:storeid limit 1 ', array(':storeid' => $row['verifystoreid']));
                    }
                    if ($row['isverify'])
                    {
                        if (is_array($verifyinfo))
                        {
                            if (empty($row['dispatchtype']))
                            {
                                $v = $verifyinfo[0];
                                if ($v['verified'] || ($row['verifytype'] == 1))
                                {
                                    $v['storename'] = pdo_fetchcolumn('select storename from ' . tablename('ewei_shop_merch_store') . ' where id=:id limit 1', array(':id' => $v['verifystoreid']));
                                    if (empty($v['storename']))
                                    {
                                        $v['storename'] = '总店';
                                    }
                                    $row['storeinfo'] = $v['storename'];
                                    $v['nickname'] = pdo_fetchcolumn('select nickname from ' . tablename('ewei_shop_member') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':openid' => $v['verifyopenid'], ':uniacid' => $_W['uniacid']));
                                    $v['salername'] = pdo_fetchcolumn('select salername from ' . tablename('ewei_shop_merch_saler') . ' where openid=:openid and uniacid=:uniacid and merchid = :merchid limit 1', array(':openid' => $v['verifyopenid'], ':uniacid' => $_W['uniacid'], ':merchid' => $_W['merchid']));
                                    $row['salerinfo'] = $v['salername'] . '(' . $v['nickname'] . ')';
                                }
                                unset($v);
                            }
                        }
                    }
                }
                if (p('diyform') && !(empty($row['diyformfields'])) && !(empty($row['diyformdata'])))
                {
                    $diyformdata_array = p('diyform')->getDatas(iunserializer($row['diyformfields']), iunserializer($row['diyformdata']));
                    $diyformdata = '';
                    foreach ($diyformdata_array as $da )
                    {
                        $diyformdata .= $da['name'] . ': ' . $da['value'] . "\r\n";
                    }
                    $row['order_diyformdata'] = $diyformdata;
                }
            }
            unset($row);
            $exportlist = array();
            foreach ($list as &$r )
            {
                $ogoods = $r['goods'];
                unset($r['goods']);
                foreach ($ogoods as $k => $g )
                {
                    if (0 < $k)
                    {
                        $r['ordersn'] = '';
                        $r['realname'] = '';
                        $r['mobile'] = '';
                        $r['openid'] = '';
                        $r['nickname'] = '';
                        $r['mrealname'] = '';
                        $r['mmobile'] = '';
                        $r['address'] = '';
                        $r['address_province'] = '';
                        $r['address_city'] = '';
                        $r['address_area'] = '';
                        $r['address_address'] = '';
                        $r['paytype'] = '';
                        $r['dispatchname'] = '';
                        $r['dispatchprice'] = '';
                        $r['goodsprice'] = '';
                        $r['status'] = '';
                        $r['createtime'] = '';
                        $r['sendtime'] = '';
                        $r['finishtime'] = '';
                        $r['expresscom'] = '';
                        $r['expresssn'] = '';
                        $r['deductprice'] = '';
                        $r['deductcredit2'] = '';
                        $r['deductenough'] = '';
                        $r['changeprice'] = '';
                        $r['changedispatchprice'] = '';
                        $r['price'] = '';
                        $r['order_diyformdata'] = '';
                    }
                    $r['goods_title'] = $g['title'];
                    $r['goods_goodssn'] = $g['goodssn'];
                    $r['goods_optiontitle'] = $g['optiontitle'];
                    $r['goods_total'] = $g['total'];
                    $r['goods_price1'] = $g['price'] / $g['total'];
                    $r['goods_price2'] = $g['realprice'] / $g['total'];
                    $r['goods_rprice1'] = $g['price'];
                    $r['goods_rprice2'] = $g['realprice'];
                    $r['goods_diyformdata'] = $g['goods_diyformdata'];
                    $exportlist[] = $r;
                }
            }
            unset($r);
            m('excel')->export($exportlist, array('title' => '订单数据-' . date('Y-m-d-H-i', time()), 'columns' => $columns));
        }
        $t = pdo_fetch('SELECT COUNT(*) as count, ifnull(sum(o.price),0) as sumprice   FROM ' . tablename('ewei_shop_order') . ' o ' . ' left join ' . tablename('ewei_shop_order_refund') . ' r on r.id =o.refundid ' . ' left join ' . tablename('ewei_shop_member') . ' m on m.openid=o.openid  and m.uniacid =  o.uniacid' . ' left join ' . tablename('ewei_shop_member_address') . ' a on o.addressid = a.id ' . ' left join ' . tablename('ewei_shop_merch_saler') . ' s on s.openid = o.verifyopenid and s.uniacid=o.uniacid and s.merchid=o.merchid' . ' left join ' . tablename('ewei_shop_member') . ' sm on sm.openid = s.openid and sm.uniacid=s.uniacid' . ' ' . $sqlcondition . ' WHERE ' . $condition . ' ' . $statuscondition, $paras);
        $total = $t['count'];
        $totalmoney = $t['sumprice'];
        $pager = pagination2($total, $pindex, $psize);
        $stores = pdo_fetchall('select id,storename from ' . tablename('ewei_shop_merch_store') . ' where uniacid=:uniacid and merchid = :merchid', array(':uniacid' => $uniacid, ':merchid' => $_W['merchid']));
        $r_type = array('退款', '退货退款', '换货');
        load()->func('tpl');
        include $this->template('order/list',get_defined_vars());
    }
    public function detail()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $p = p('commission');
        $prefix = Config::get('database.prefix');
        $name = Db::name('ewei_shop_member a')->field('b.id')->leftJoin([$prefix.'ewei_shop_merch_user' => 'b'],'a.openid = b.openid','left')->where(['a.openid' => $_W['openid'],'b.status' => 1])->find();
        $merchid = $name['id'];


        $orderid = intval($_GPC['id']);
        $ispeerpay = m('order')->checkpeerpay($orderid);
        if (empty($orderid))
        {
            /*header('location: ' . mobileUrl('order'));*/
            show_json(0,'订单id不能为空');
            exit();
        }
        $order = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and merchid=:merchid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid, ':merchid' => $merchid));
        if(intval($_GPC['yijuan_type'])==1){
            $order = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $orderid, ':uniacid' => $uniacid));
        }
        if (empty($order))
        {
            /*header('location: ' . mobileUrl('order'));*/
            show_json(0,'订单不存在');
            exit();
        }
        if ($order['merchshow'] == 1)
        {
            /*header('location: ' . mobileUrl('order'));*/
            show_json(0,'订单只能在商户显示');
            exit();
        }
        if ($order['userdeleted'] == 2)
        {
            $this->message('订单已经被删除!', '', 'error');
        }
        if (!(empty($order['istrade'])))
        {
            /*header('location: ' . mobileUrl('newstore/norder/detail', array('id' => $orderid)));*/
            show_json(0,'这是核销订单');
            exit();
        }
        $area_set = m('util')->get_area_config_set();
        $new_area = intval($area_set['new_area']);
        $address_street = intval($area_set['address_street']);
        $merchdata = $this->merchData();
        extract($merchdata);
        $merchid = $order['merchid'];
        $diyform_plugin = p('diyform');
        $diyformfields = '';
        if ($diyform_plugin)
        {
            $diyformfields = ',og.diyformfields,og.diyformdata';
        }
        $param = array();
        $param[':uniacid'] = $_W['uniacid'];
        if ($order['isparent'] == 1)
        {
            $scondition = ' og.parentorderid=:parentorderid';
            $param[':parentorderid'] = $orderid;
        }
        else
        {
            $scondition = ' og.orderid=:orderid';
            $param[':orderid'] = $orderid;
        }
        $condition1 = '';
        if (p('ccard'))
        {
            $condition1 .= ',g.ccardexplain,g.ccardtimeexplain';
        }
        $goodsid_array = array();
        $goods = pdo_fetchall('select og.goodsid,og.price,g.title,g.thumb,g.status, g.cannotrefund, og.total,g.credit,og.optionid,' . "\r\n" . '            og.optionname as optiontitle,g.isverify,g.storeids,og.seckill,g.isfullback,' . "\r\n" . '            og.seckill_taskid' . $diyformfields . $condition1 . ',og.prohibitrefund  from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid ' . ' where ' . $scondition . ' and og.uniacid=:uniacid ', $param);
        $prohibitrefund = false;
        foreach ($goods as &$g )
        {
            if ($g['isfullback'])
            {
                $fullbackgoods = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_fullback_goods') . ' WHERE uniacid = ' . $uniacid . ' and goodsid = ' . $g['goodsid'] . ' limit 1 ');
                if ($g['optionid'])
                {
                    $option = pdo_fetch('select `day`,allfullbackprice,fullbackprice,allfullbackratio,fullbackratio,isfullback' . "\r\n" . '                      from ' . tablename('ewei_shop_goods_option') . ' where id = ' . $g['optionid'] . ' and uniacid = ' . $uniacid . ' ');
                    $fullbackgoods['minallfullbackallprice'] = $option['allfullbackprice'];
                    $fullbackgoods['fullbackprice'] = $option['fullbackprice'];
                    $fullbackgoods['minallfullbackallratio'] = $option['allfullbackratio'];
                    $fullbackgoods['fullbackratio'] = $option['fullbackratio'];
                    $fullbackgoods['day'] = $option['day'];
                }
                $g['fullbackgoods'] = $fullbackgoods;
                unset($fullbackgoods, $option);
            }
            $g['seckill_task'] = false;
            if ($g['seckill'])
            {
                $g['seckill_task'] = plugin_run('seckill::getTaskInfo', $g['seckill_taskid']);
            }
            if (!(empty($g['prohibitrefund'])))
            {
                $prohibitrefund = true;
            }
        }
        unset($g);
        $goodsrefund = true;
        if (!(empty($goods)))
        {
            foreach ($goods as &$g )
            {
                $goodsid_array[] = $g['goodsid'];
                if (!(empty($g['optionid'])))
                {
                    $thumb = m('goods')->getOptionThumb($g['goodsid'], $g['optionid']);
                    if (!(empty($thumb)))
                    {
                        $g['thumb'] = $thumb;
                    }
                }
                if (!(empty($g['cannotrefund'])) && ($order['status'] == 2))
                {
                    $goodsrefund = false;
                }
            }
            unset($g);
        }
        $diyform_flag = 0;
        if ($diyform_plugin)
        {
            foreach ($goods as &$g )
            {
                $g['diyformfields'] = iunserializer($g['diyformfields']);
                $g['diyformdata'] = iunserializer($g['diyformdata']);
                unset($g);
            }
            if (!(empty($order['diyformfields'])) && !(empty($order['diyformdata'])))
            {
                $order_fields = iunserializer($order['diyformfields']);
                $order_data = iunserializer($order['diyformdata']);
            }
        }
        $address = false;
        if (!(empty($order['addressid'])))
        {
            $address = iunserializer($order['address']);
            if (!(is_array($address)))
            {
                $address = pdo_fetch('select * from  ' . tablename('ewei_shop_member_address') . ' where id=:id limit 1', array(':id' => $order['addressid']));
            }
        }
        $carrier = @iunserializer($order['carrier']);
        if (!(is_array($carrier)) || empty($carrier))
        {
            $carrier = false;
        }
        $store = false;
        if (!(empty($order['storeid'])))
        {
            if (0 < $merchid)
            {
                $store = pdo_fetch('select * from  ' . tablename('ewei_shop_merch_store') . ' where id=:id limit 1', array(':id' => $order['storeid']));
            }
            else
            {
                $store = pdo_fetch('select * from  ' . tablename('ewei_shop_store') . ' where id=:id limit 1', array(':id' => $order['storeid']));
            }
        }
        $stores = false;
        $showverify = false;
        $canverify = false;
        $verifyinfo = false;
        if (com('verify'))
        {
            $showverify = $order['dispatchtype'] || $order['isverify'];
            if ($order['isverify'])
            {
                if ((0 < $order['verifyendtime']) && ($order['verifyendtime'] < time()))
                {
                    $order['status'] = -1;
                }
                $storeids = array();
                foreach ($goods as $g )
                {
                    if (!(empty($g['storeids'])))
                    {
                        $storeids = array_merge(explode(',', $g['storeids']), $storeids);
                    }
                }
                if (empty($storeids))
                {
                    if (0 < $merchid)
                    {
                        $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where  uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
                    }
                    else
                    {
                        $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where  uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
                    }
                }
                else if (0 < $merchid)
                {
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_merch_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and merchid=:merchid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid'], ':merchid' => $merchid));
                }
                else
                {
                    $stores = pdo_fetchall('select * from ' . tablename('ewei_shop_store') . ' where id in (' . implode(',', $storeids) . ') and uniacid=:uniacid and status=1 and type in(2,3)', array(':uniacid' => $_W['uniacid']));
                }
                if (($order['verifytype'] == 0) || ($order['verifytype'] == 1) || ($order['verifytype'] == 3))
                {
                    $vs = iunserializer($order['verifyinfo']);
                    $verifyinfo = array( array('verifycode' => $order['verifycode'], 'verified' => (($order['verifytype'] == 0) || ($order['verifytype'] == 3) ? $order['verified'] : $goods[0]['total'] <= count($vs))) );
                    if (($order['verifytype'] == 0) || ($order['verifytype'] == 3))
                    {
                        $canverify = empty($order['verified']) && $showverify;
                    }
                    else if ($order['verifytype'] == 1)
                    {
                        $canverify = (count($vs) < $goods[0]['total']) && $showverify;
                    }
                }
                else
                {
                    $verifyinfo = iunserializer($order['verifyinfo']);
                    $last = 0;
                    foreach ($verifyinfo as $v )
                    {
                        if (!($v['verified']))
                        {
                            ++$last;
                        }
                    }
                    $canverify = (0 < $last) && $showverify;
                }
            }
            else if (!(empty($order['dispatchtype'])))
            {
                $verifyinfo = array( array('verifycode' => $order['verifycode'], 'verified' => $order['status'] == 3) );
                $canverify = ($order['status'] == 1) && $showverify;
            }
        }
        $order['canverify'] = $canverify;
        $order['showverify'] = $showverify;
        $order['virtual_str'] = str_replace("\n", '<br/>', $order['virtual_str']);
        if (($order['status'] == 1) || ($order['status'] == 2))
        {
            $canrefund = true;
            if (($order['status'] == 2) && ($order['price'] == $order['dispatchprice']))
            {
                if (0 < $order['refundstate'])
                {
                    $canrefund = true;
                }
                else
                {
                    $canrefund = false;
                }
            }
        }
        else if ($order['status'] == 3)
        {
            if (($order['isverify'] != 1) && empty($order['virtual']))
            {
                if (0 < $order['refundstate'])
                {
                    $canrefund = true;
                }
                else
                {
                    $tradeset = m('common')->getSysset('trade');
                    $refunddays = intval($tradeset['refunddays']);
                    if (0 < $refunddays)
                    {
                        $days = intval((time() - $order['finishtime']) / 3600 / 24);
                        if ($days <= $refunddays)
                        {
                            $canrefund = true;
                        }
                    }
                }
            }
        }
        if (!(empty($order['isnewstore'])) && (1 < $order['status']))
        {
            $canrefund = false;
        }
        if ($prohibitrefund)
        {
            $canrefund = false;
        }
        if (!($goodsrefund) && $canrefund)
        {
            $canrefund = false;
        }
        if (p('ccard'))
        {
            if (!(empty($order['ccard'])) && (1 < $order['status']))
            {
                $canrefund = false;
            }
            $comdata = m('common')->getPluginset('commission');
            if (!(empty($comdata['become_goodsid'])) && !(empty($goodsid_array)))
            {
                if (in_array($comdata['become_goodsid'], $goodsid_array))
                {
                    $canrefund = false;
                }
            }
        }
        $haveverifygoodlog = m('order')->checkhaveverifygoodlog($orderid);
        if ($haveverifygoodlog)
        {
            $canrefund = false;
        }
        $order['canrefund'] = $canrefund;
        $express = false;
        $order_goods = array();
        if ((2 <= $order['status']) && empty($order['isvirtual']) && empty($order['isverify']))
        {
            $expresslist = m('util')->getExpressList($order['express'], $order['expresssn']);
            if (0 < count($expresslist))
            {
                $express = $expresslist[0];
            }
        }
        if ((0 < $order['sendtype']) && (1 <= $order['status']))
        {
            $order_goods = pdo_fetchall('select orderid,goodsid,sendtype,expresscom,expresssn,express,sendtime from ' . tablename('ewei_shop_order_goods') . "\r\n" . '            where orderid = ' . $orderid . ' and uniacid = ' . $uniacid . ' and sendtype > 0 group by sendtype order by sendtime asc ');
            $expresslist = m('util')->getExpressList($order['express'], $order['expresssn']);
            if (0 < count($expresslist))
            {
                $express = $expresslist[0];
            }
            $order['sendtime'] = $order_goods[0]['sendtime'];
        }
        $shopname = $_W['shopset']['shop']['name'];
        $phone = $_W['shopset']['shop']['phone'];
        if (!(empty($order['merchid'])) && ($is_openmerch == 1))
        {
            $merch_user = $merch_plugin->getListUser($order['merchid']);
            $shopname = $merch_user['merchname'];
            $phone = $merch_user['mobile'];
            $shoplogo = tomedia($merch_user['logo']);
        }
        //-----------deng start appjson_data-----------
        //$parm = get_defined_vars();
        $parm = array(
            '_W'                =>  $_W,
            'ispeerpay'         =>  $ispeerpay,//是否代付
            'order'             =>  $order,//订单信息
            'new_area'          =>  $new_area,//是否采用的新版地址配置
            'address_street'    =>  $address_street,//是否启用街道地址
            'goods'             =>  $goods,//商品信息
            'address'           =>  $address,//地址信息
            'carrier'           =>  $carrier,//配送信息
            'store'             =>  $store,//配送门店信息
            'stores'            =>  $stores,//门店信息
            'verifyinfo'        =>  $verifyinfo,
            'express'           =>  $express,//快递信息
            'order_goods'       =>  $order_goods,//订单商品信息
            'shopname'          =>  $shopname,//店铺名称
            'phone'          =>  $phone,//店铺联系方式

        );
        //------------deng end appjson_data------------
        include $this->template('',$parm);
        }

    protected function merchData()
    {
        $merch_plugin = p('merch');
        $merch_data = m('common')->getPluginset('merch');
        if ($merch_plugin && $merch_data['is_openmerch'])
        {
            $is_openmerch = 1;
        }
        else
        {
            $is_openmerch = 0;
        }
        return array('is_openmerch' => $is_openmerch, 'merch_plugin' => $merch_plugin, 'merch_data' => $merch_data);
    }

    /**
     * 线下实体店列表
     */
    public function getStoreList()
    {
        global $_W;
        global $_GPC;

        $page = ((!(empty($_GPC['page'])) ? intval($_GPC['page']) : 1));
        $pagesize = ((!(empty($_GPC['pagesize'])) ? intval($_GPC['pagesize']) : 10));
        $prefix = Config::get('database.prefix');

        $data = Db::name('ewei_shop_merch_user a')->field('a.*')->join([$prefix.'ewei_shop_merch_code' => 'b'],'a.id = b.merchid and b.status =2')->limit(($page-1) * $pagesize,$pagesize)->select();

        $total = Db::name('ewei_shop_merch_user a')->field('a.*')->join([$prefix.'ewei_shop_merch_code' => 'b'],'a.id = b.merchid and b.status =2')->count();

        show_json(1,['list' => $data,'total' => $total]);
    }

    /**
     * 获取收款码列表
     */
    public function list_code()
    {
        global $_W;
        global $_GPC;
        $page = ((!(empty($_GPC['page'])) ? intval($_GPC['page']) : 1));
        $prefix = Config::get('database.prefix');
        $pagesize = ((!(empty($_GPC['pagesize'])) ? intval($_GPC['pagesize']) : 10));
        $data = Db::name('ewei_shop_merch_code a')->field('a.*,b.id as log_id')->join([$prefix.'ewei_shop_member_log' => 'b'],'a.ordersn = b.logno')->where(['a.openid' => $_W['openid']])->limit(($page-1) * $pagesize,$pagesize)->select();
        $total = Db::name('ewei_shop_merch_code a')->field('a.*,b.id as log_id')->join([$prefix.'ewei_shop_member_log' => 'b'],'a.ordersn = b.logno')->where(['a.openid' => $_W['openid']])->count();

        show_json(1,['list' => $data,'total' => $total]);
    }


}