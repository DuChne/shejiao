<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
class Agent_EweiShopV2Page extends MobilePage
{
    public function main()
    {
        global $_W;
        include $this->template('',get_defined_vars());
    }
}
?>