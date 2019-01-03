<?php
/**
 * deng start message
 * 消息通知
 * APP端消息推送在model下message的sendPush方法里
 */
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
class Message_EweiShopV2Page extends MobileLoginPage
{
    public function main()
    {
        global $_W;
        global $_GPC;
        include $this->template('',get_defined_vars());
    }
    public function get_list()
    {
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $condition = ' and uniacid = :uniacid and openid=:openid and deleted=0';

        $params = array(':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']);
        $sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_shop_member_message_push') . ' where 1 ' . $condition;
        $total = pdo_fetchcolumn($sql, $params);
        $list = array();
        if (!(empty($total)))
        {
            $sql = 'SELECT * FROM ' . tablename('ewei_shop_member_message_push') . ' where 1 ' . $condition . ' ORDER BY `id` DESC LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
            $list = pdo_fetchall($sql, $params);
            //$list = set_medias($list, 'thumb');
        }
        //全部标记为已读
        $sql = 'update ' . tablename('ewei_shop_member_message_push') . ' set status=1 where openid=:openid and uniacid = :uniacid';
        pdo_query($sql, array(':openid' => $_W['openid'], ':uniacid' => $_W['uniacid']));

        show_json(1, array('list' => $list, 'pagesize' => $psize, 'total' => $total, 'pindex' => $pindex, 'total_pages' => max(1,ceil($total/$psize))));
    }

    public function remove()
    {
        global $_W;
        global $_GPC;
        $ids = $_GPC['ids'];
        if (empty($ids) || !(is_array($ids)))
        {
            show_json(0, '参数错误');
        }
        $sql = 'update ' . tablename('ewei_shop_member_message_push') . ' set deleted=1 where openid=:openid and id in (' . implode(',', $ids) . ')';
        pdo_query($sql, array(':openid' => $_W['openid']));
        show_json(1);
    }
}
?>