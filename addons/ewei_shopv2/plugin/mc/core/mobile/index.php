<?php
class Index_EweiShopV2Page extends PluginMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		include $this->template('',get_defined_vars());
	}
}


?>