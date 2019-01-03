<?php
if (!(defined('IN_IA'))) {

    exit('Access Denied');
}



class Notice_info_EweiShopV2Page extends MobileLoginPage
{
    //---------------deng start notice-------------
    public function main()
    {
        global $_W;
        global $_GPC;

        $id = $_GET['id'];
        $info = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_notice') . ' WHERE id = ' . $id . '  ORDER BY displayorder ASC,createtime DESC ');
        if(!empty($info))$info['createtime'] = date('Y-m-d H:i:s',$info['createtime']);
        include $this->template('',get_defined_vars());
    }
}


?>