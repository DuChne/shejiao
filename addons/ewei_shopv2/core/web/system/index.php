<?php

if (!defined('IN_IA')) {

	exit('Access Denied');
}



class Index_EweiShopV2Page extends SystemPage
{
	public function main()
	{
		header('Location:' . webUrl('system/plugin'));
		exit();
		include $this->template('',get_defined_vars());
	}
}


?>