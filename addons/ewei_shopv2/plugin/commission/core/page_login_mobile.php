<?php

if (!defined('IN_IA')) {

	exit('Access Denied');
}



class CommissionMobileLoginPage extends PluginMobileLoginPage
{
	public function __construct()
	{
		parent::__construct();
		global $_W;
		global $_GPC;


		if (($_W['action'] != 'register') && ($_W['action'] != 'myshop') && ($_W['action'] != 'share')) {

			$member = m('member')->getMember($_W['openid']);
			if (($member['isagent'] != 1) || ($member['status'] != 1)) {

                //--------------deng start commission---------------
                if(!empty($_W['is_api']))show_json(0,'您还不是代理商');
                //---------------deng end commission----------------
				header('location:' . mobileUrl('commission/register'));
				exit();
			}



		}



	}
}


?>