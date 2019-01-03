<?php
if (!(defined('IN_IA'))) {

    exit('Access Denied');
}



class Notice_list_EweiShopV2Page extends MobileLoginPage
{
    //---------------deng start notice-------------
    public function main()
    {
        global $_W;
        global $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $condition = ' and uniacid=:uniacid and status= 1';
        $params = array(':uniacid' => $_W['uniacid']);
        $sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_shop_notice') . ' where 1 ' . $condition;
        $total = pdo_fetchcolumn($sql, $params);
        //加入一个判断，WAP端无分页，APP端则分页处理
        $sql = 'SELECT * FROM ' . tablename('ewei_shop_notice') . ' WHERE 1 ' . $condition . '  ORDER BY displayorder ASC,createtime DESC ';
        if(!empty($_W['is_api']))$sql = 'SELECT * FROM ' . tablename('ewei_shop_notice') . ' where 1 ' . $condition . ' ORDER BY `id` DESC LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
        $list = pdo_fetchall($sql, $params);

        if(!$list)$data = array();
        else $data = $list;

        //-----------deng start appjson_data-----------
        //$parm = get_defined_vars();
        $parm = array(
            '_W'                =>  $_W,
            'pindex'            =>  $pindex,//当前页数
            'total_pages'       =>  max(1, ceil($total/$psize)),//总页数
            'total'             =>  $total,//总数
            'list'              =>  $list,//列表

        );
        //------------deng end appjson_data------------
        include $this->template('',$parm);
    }
}


?>