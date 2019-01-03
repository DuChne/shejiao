<?php
if (!(defined('IN_IA'))) {

	exit('Access Denied');
}





require EWEI_SHOPV2_PLUGIN . 'commission/core/page_login_mobile.php';
class Withdraw_EweiShopV2Page extends CommissionMobileLoginPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		$member = $this->model->getInfo($openid, array('total', 'ok', 'apply', 'check', 'lock', 'pay', 'wait', 'fail'));
		$cansettle = (1 <= $member['commission_ok']) && (floatval($this->set['withdraw']) <= $member['commission_ok']);
		$agentid = $member['id'];


		if (!(empty($agentid))) {

			$data = pdo_fetch('select sum(deductionmoney) as sumcharge from ' . tablename('ewei_shop_commission_log') . ' where mid=:mid and uniacid=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':mid' => $agentid));
			$commission_charge = $data['sumcharge'];
			$member['commission_charge'] = $commission_charge;
		}

		 else {

			$member['commission_charge'] = 0;
		}

        //-----------deng start appjson_data-----------
        //$parm = get_defined_vars();
        $info = array(
            'commission_total'  =>  $member['commission_total'],//累计积分
            'commission_ok'    =>  $member['commission_ok'],//可兑换积分
            'commission_apply'    =>  $member['commission_apply'],//已申请积分
            'commission_check'    =>  $member['commission_check'],//待打款积分
            'commission_fail'    =>  $member['commission_fail'],//无效积分
            'commission_pay'    =>  $member['commission_pay'],//成功兑换积分
            'commission_charge_text'    =>  $this->set['texts']['commission_charge'],//扣税积分说明
            'commission_charge'    =>  $member['commission_charge'],//扣税积分
            'commission_wait'    =>  $member['commission_wait'],//待收货积分
            'commission_lock'    =>  $member['commission_lock'],//未结算积分

        );
        $parm = array(
            '_W'                =>  $_W,
            'info'            =>  $info,//推广积分信息

        );
        //------------deng end appjson_data------------
        include $this->template('',$parm);

	}
}


?>