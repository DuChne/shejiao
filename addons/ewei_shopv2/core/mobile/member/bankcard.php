<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Bankcard_EweiShopV2Page extends MobileLoginPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		global $_S;
		$area_set = m('util')->get_area_config_set();
		$new_area = intval($area_set['new_area']);
		$address_street = intval($area_set['address_street']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = ' and openid=:openid and deleted=0 and  `uniacid` = :uniacid  ';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
		$sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_shop_member_bankcard') . ' where 1 ' . $condition;
		$total = pdo_fetchcolumn($sql, $params);
		$sql = 'SELECT * FROM ' . tablename('ewei_shop_member_bankcard') . ' where 1 ' . $condition . ' ORDER BY `id` DESC LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		$list = pdo_fetchall($sql, $params);
		include $this->template('',get_defined_vars());
	}
	public function post() 
	{
		global $_W;
		global $_GPC;
		$wapset = m('common')->getSysset('wap');
		$condition = ' and uniacid=:uniacid';
		$params = array(':uniacid' => $_W['uniacid']);
		$banklist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_commission_bank') . ' WHERE 1 ' . $condition . '  ORDER BY displayorder DESC', $params);
		
		$sendtime = $_SESSION['verifycodesendtime'];
		if (empty($sendtime) || (($sendtime + 60) < time())) {

			$endtime = 0;
		}

		 else {

			$endtime = 60 - time() - $sendtime;
		}
		include $this->template('',get_defined_vars());
	}
	public function setdefault() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$data = pdo_fetch('select id from ' . tablename('ewei_shop_member_bankcard') . ' where id=:id and deleted=0 and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $id));
		if (empty($data)) 
		{
			show_json(0, '银行卡信息未找到');
		}
		pdo_update('ewei_shop_member_bankcard', array('isdefault' => 0), array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid']));
		pdo_update('ewei_shop_member_bankcard', array('isdefault' => 1), array('id' => $id, 'uniacid' => $_W['uniacid'], 'openid' => $_W['openid']));
		show_json(1);
	}
	public function submit() 
	{
		global $_W;
		global $_GPC;
		
		$openid = $_W['openid'];
		$member = pdo_fetch('select id,openid,mobile,pwd,salt from ' . tablename('ewei_shop_member') . ' where openid=:openid and mobileverify=1 and uniacid=:uniacid limit 1', array(':openid' => $openid, ':uniacid' => $_W['uniacid']));
		if(empty($member))
		{
		    show_json(0, '用户信息未找到');
		}
		$mobile = trim($member['mobile']);
		
		$data = $_GPC['bankcarddata'];
		$verifycode = trim($data['verifycode']);
		$key = '__ewei_shopv2_member_verifycodesession_' . $_W['uniacid'] . '_' . $mobile;
		if (!(isset($_SESSION[$key])) || ($_SESSION[$key] !== $verifycode) || !(isset($_SESSION['verifycodesendtime'])) || (($_SESSION['verifycodesendtime'] + 600) < time())) {
			show_json(0, '验证码错误或已过期');
		}
		
		$data['bankid'] = trim($data['bankid']);
		//找寻一下银行信息
		$bankinfo = pdo_fetch('SELECT bankname FROM ' . tablename('ewei_shop_commission_bank') . ' WHERE id=' . $data['bankid'] . ' AND uniacid=' . $_W['uniacid']);
		if(empty($bankinfo))
		{
		    show_json(0, '银行信息未找到');
		}
		
		$data['bankname'] = $bankinfo['bankname'];
		$data['realname'] = trim($data['realname']);
		$data['cardnumber'] = trim($data['cardnumber']);
		unset($data['verifycode']);
		$data['openid'] = $openid;
		$data['uniacid'] = $_W['uniacid'];
		$addresscount = pdo_fetchcolumn('SELECT count(*) FROM ' . tablename('ewei_shop_member_bankcard') . ' where openid=:openid and deleted=0 and `uniacid` = :uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
		if ($addresscount <= 0) 
		{
			$data['isdefault'] = 1;
		}
		pdo_insert('ewei_shop_member_bankcard', $data);
		$id = pdo_insertid();
		
		show_json(1, array('bankid' => $id));
	}
	public function delete() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		$data = pdo_fetch('select id,isdefault from ' . tablename('ewei_shop_member_bankcard') . ' where  id=:id and openid=:openid and deleted=0 and uniacid=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid'], ':id' => $id));
		if (empty($data)) 
		{
			show_json(0, '银行卡信息未找到');
		}
		pdo_update('ewei_shop_member_bankcard', array('deleted' => 1), array('id' => $id));
		if ($data['isdefault'] == 1) 
		{
			pdo_update('ewei_shop_member_bankcard', array('isdefault' => 0), array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'id' => $id));
			$data2 = pdo_fetch('select id from ' . tablename('ewei_shop_member_bankcard') . ' where openid=:openid and deleted=0 and uniacid=:uniacid order by id desc limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
			if (!(empty($data2))) 
			{
				pdo_update('ewei_shop_member_bankcard', array('isdefault' => 1), array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'id' => $data2['id']));
				show_json(1, array('defaultid' => $data2['id']));
			}
		}
		show_json(1);
	}
	public function selector() 
	{
		global $_W;
		global $_GPC;
		$area_set = m('util')->get_area_config_set();
		$new_area = intval($area_set['new_area']);
		$address_street = intval($area_set['address_street']);
		$condition = ' and openid=:openid and deleted=0 and  `uniacid` = :uniacid  ';
		$params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
		$sql = 'SELECT * FROM ' . tablename('ewei_shop_member_bankcard') . ' where 1 ' . $condition . ' ORDER BY isdefault desc, id DESC ';
		$list = pdo_fetchall($sql, $params);
		include $this->template('',get_defined_vars());
		exit();
	}
	
	public function verifycode() 
	{
		global $_W;
		global $_GPC;
		@session_start();
		$set = m('common')->getSysset(array('shop', 'wap'));
		$member = pdo_fetch('select id,openid,mobile,pwd,salt from ' . tablename('ewei_shop_member') . ' where openid=:openid and mobileverify=1 and uniacid=:uniacid limit 1', array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));
		if(empty($member))
		{
		    show_json(0, '请勿非法操作');
		}
		$mobile = $member['mobile'];
		$temp = trim($_GPC['temp']);
		$imgcode = trim($_GPC['imgcode']);
		if (empty($mobile)) 
		{
			show_json(0, '请输入手机号');
		}
		if (empty($temp)) 
		{
			show_json(0, '参数错误');
		}
		if (!(empty($_SESSION['verifycodesendtime'])) && (time() < ($_SESSION['verifycodesendtime'] + 60))) 
		{
			show_json(0, '请求频繁请稍后重试');
		}
		if (!(empty($set['wap']['smsimgcode']))) 
		{
			if (empty($imgcode)) 
			{
				show_json(0, '请输入图形验证码');
			}
			$imgcodehash = md5(strtolower($imgcode) . $_W['config']['setting']['authkey']);
			if ($imgcodehash != trim($_GPC['__code'])) 
			{
				show_json(-1, '请输入正确的图形验证码');
			}
		}
		
		$sms_id = $set['wap'][$temp];
		if (empty($sms_id)) 
		{
			show_json(0, '短信发送失败(NOSMSID)');
		}
		$key = '__ewei_shopv2_member_verifycodesession_' . $_W['uniacid'] . '_' . $mobile;
		@session_start();
		$code = random(5, true);
		$shopname = $_W['shopset']['shop']['name'];
		$ret = com('sms')->send($mobile, $sms_id, array('验证码' => $code, '商城名称' => (!(empty($shopname)) ? $shopname : '商城名称')));
		if ($ret['status']) 
		{
			$_SESSION[$key] = $code;
			$_SESSION['verifycodesendtime'] = time();
			show_json(1, '短信发送成功');
		}
		show_json(0, $ret['message']);
	}
}
?>