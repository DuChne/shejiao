<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Verifygoods_EweiShopV2Model 
{
	public function createverifygoods($orderid) 
	{
		global $_W;
		$verifygoods = pdo_fetchall('select  *   from  ' . tablename('ewei_shop_verifygoods') . ' where  orderid=:orderid ', array(':orderid' => $orderid));
		if (!(empty($verifygoods))) 
		{
			return false;
		}
		if (p('newstore')) 
		{
			$sql2 = ',o.storeid,o.isnewstore';
		}
		$ordergoods = pdo_fetchall('select o.openid,o.uniacid,o.id as orderid , og.id as ordergoodsid,g.verifygoodsdays,g.verifygoodsnum,og.total ' . $sql2 . ' from ' . tablename('ewei_shop_order_goods') . '   og inner join ' . tablename('ewei_shop_goods') . ' g on og.goodsid = g.id' . "\r\n" . '          inner join ' . tablename('ewei_shop_order') . ' o on og.orderid = o.id' . "\r\n" . '          where   og.orderid =:orderid and g.type = 5', array(':orderid' => $orderid));
		$time = time();
		$total = intval($ordergood['total']);
		$i = 0;
		$data = array('uniacid' => $ordergood['uniacid'], 'openid' => $ordergood['openid'], 'orderid' => $ordergood['orderid'], 'ordergoodsid' => $ordergood['ordergoodsid'], 'starttime' => $time, 'limitdays' => intval($ordergood['verifygoodsdays']), 'limitnum' => intval($ordergood['verifygoodsnum']), 'used' => 0, 'invalid' => 0);
		if (p('newstore')) 
		{
			if (!(empty($ordergoods['storeid'])) && !(empty($ordergoods['isnewstore']))) 
			{
				$data['storeid'] = intval($ordergoods['storeid']);
			}
		}
		pdo_insert('ewei_shop_verifygoods', $data);
		++$i;
		return true;
	}
}
?>