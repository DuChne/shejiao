<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Index_EweiShopV2Page extends MobilePage 
{
	protected function getWapSet() 
	{
		global $_W;
		global $_GPC;
		$set = m('common')->getSysset(array('shop', 'wap'));
		$set['wap']['color'] = ((empty($set['wap']['color']) ? '#fff' : $set['wap']['color']));
		$params = array();
		if (!(empty($_GPC['mid']))) 
		{
			$params['mid'] = $_GPC['mid'];
		}
		if (!(empty($_GPC['backurl']))) 
		{
			$params['backurl'] = $_GPC['backurl'];
		}
		$set['wap']['loginurl'] = mobileUrl('account/login', $params);
		$set['wap']['regurl'] = mobileUrl('account/register', $params);
		$set['wap']['forgeturl'] = mobileUrl('account/forget', $params);
		return $set;
	}
	public function login() 
	{
		global $_W;
		global $_GPC;
        //---------------deng start sns-------------------
        if (empty($_W['is_api']) && (is_weixin() || !(empty($_GPC['__ewei_shopv2_member_session_' . $_W['uniacid']])) ) )
        {
            header('location: ' . mobileUrl());
        }
        if ($_W['ispost'] || $_W['is_api'])
        //--------------------deng end login-------------------
		{
			$mobile = trim($_GPC['mobile']);
			$pwd = trim($_GPC['pwd']);
			$member = pdo_fetch('select id,openid,mobile,pwd,salt,token from ' . tablename('ewei_shop_member') . ' where mobile=:mobile and mobileverify=1 and uniacid=:uniacid limit 1', array(':mobile' => $mobile, ':uniacid' => $_W['uniacid']));
			if (empty($member)) 
			{
				show_json(0, '用户不存在');
			}
			if (md5($pwd . $member['salt']) !== $member['pwd']) 
			{
				show_json(0, '用户或密码错误');
			}
			m('account')->setLogin($member);

			$token = $this->update_token($member);
            if(!$token) show_json(0,'登录失败');
            //-------------------deng start login------------------
            $parm = array('message'=>'登录成功','token' => $token);
            $parm['member_info'] = json_decode(base64_decode($_GPC['__ewei_shopv2_member_session_' . $_W['uniacid']]), true);
            unset($parm['pwd']);
            unset($parm['salt']);
            show_json(1, $parm);
            //--------------------deng end login-------------------
		}
		$set = $this->getWapSet();
		$backurl = '';
		if (!(empty($_GPC['backurl']))) 
		{
			$backurl = $_W['siteroot'] . 'app/index.php?' . base64_decode(urldecode($_GPC['backurl']));
		}
		$wapset = $_W['shopset']['wap'];
		$sns = $wapset['sns'];
		include $this->template('login', get_defined_vars(), null, true);
	}

    /**
     * 更新token
     * @param $member
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
	protected function update_token($member)
    {
        if(!is_array($member)) return false;

        //分析token来源
        if(isset($_SERVER['HTTP_REQUEST_TYPE']) and in_array($_SERVER['HTTP_REQUEST_TYPE'],['wap','app','pc'])){
            $request_type = $_SERVER['HTTP_REQUEST_TYPE'];
        }else{
            $request_type = 'wap';
        }

        //新的验证方式token验证 TSX加
        $token = $request_type.'_'.$this->settoken();
        if (is_error(redis())) {
            show_json(0, '无法生成token请安装redis');
        }

        redis()->hSet('token_validate',$token,$member['openid'].':'.session_id().':'.(time()+259200));

        $tokens = explode(',',$member['token']);   //获取所有的token
        $current_token = '';
        $tokens = array_filter($tokens,function ($value,$key)use (&$current_token,$request_type){
            if(!$value or strpos($value,$request_type) === 0){
                $current_token =  $value;
                return false;
            }
            return true;

        },ARRAY_FILTER_USE_BOTH);
        if($current_token and redis()->hExists('token_validate',$current_token)){
            redis()->hDel('token_validate',$current_token);
        }
        $tokens[] = $token;
        db('ewei_shop_member')->where(['id' => $member['id']])->update(['token' => implode(',',$tokens)]);

        return $token;
    }

	//生成一个唯一不重复token
    public function settoken()
    {
        $str = md5(uniqid(md5(microtime(true)),true));  //生成一个不会重复的字符串
        $str = sha1($str);  //加密
        return $str;
    }


    public function register()
	{
		$this->rf(0);
	}
	public function forget() 
	{
		$this->rf(1);
	}
	protected function rf($type) 
	{
		global $_W;
		global $_GPC;
        //---------------deng start register-------------------
        if ($_GPC['rf_type'] && $_W['is_api'])$type = $_GPC['rf_type'];
        if (empty($_W['is_api']) && (is_weixin() || !(empty($_GPC['__ewei_shopv2_member_session_' . $_W['uniacid']])) ) )
        {
            header('location: ' . mobileUrl());
        }
        if ($_W['ispost'] || $_W['is_api'])
        //--------------------deng end register-------------------
        {
            $mobile = trim($_GPC['mobile']);
            $verifycode = trim($_GPC['verifycode']);
            $pwd = trim($_GPC['pwd']);
            //-------------------deng start register------------------
            $pwd1 = trim($_GPC['pwd1']);
            if(!empty($pwd1) && $pwd != $pwd1)show_json(0, '两次密码不一致');
            //-------------------deng start register------------------
			if (empty($mobile)) 
			{
				show_json(0, '请输入正确的手机号');
			}
			if (empty($verifycode)) 
			{
				show_json(0, '请输入验证码');
			}
			if (empty($pwd)) 
			{
				show_json(0, '请输入密码');
			}
			$key = '__ewei_shopv2_member_verifycodesession_' . $_W['uniacid'] . '_' . $mobile;
			if (!(isset($_SESSION[$key])) || ($_SESSION[$key] !== $verifycode) || !(isset($_SESSION['verifycodesendtime'])) || (($_SESSION['verifycodesendtime'] + 600) < time())) 
			{
				show_json(0, '验证码错误或已过期!');
			}
			$member = pdo_fetch('select id,openid,mobile,pwd,salt from ' . tablename('ewei_shop_member') . ' where mobile=:mobile and mobileverify=1 and uniacid=:uniacid limit 1', array(':mobile' => $mobile, ':uniacid' => $_W['uniacid']));
			if (empty($type)) 
			{
				if (!(empty($member))) 
				{
					show_json(0, '此手机号已注册, 请直接登录');
				}
				$salt = ((empty($member) ? '' : $member['salt']));
				if (empty($salt)) 
				{
					$salt = m('account')->getSalt();
				}
				$openid = ((empty($member) ? '' : $member['openid']));
				$nickname = ((empty($member) ? '' : $member['nickname']));
				if (empty($openid)) 
				{
					$openid = 'wap_user_' . $_W['uniacid'] . '_' . $mobile;
					$nickname = substr($mobile, 0, 3) . 'xxxx' . substr($mobile, 7, 4);
				}
				//-------------deng start register---------------
                $avatar = 'images/global/avatars/avatar_wap.png';
				$data = array('uniacid' => $_W['uniacid'], 'mobile' => $mobile, 'nickname' => $nickname, 'openid' => $openid, 'pwd' => md5($pwd . $salt), 'salt' => $salt, 'createtime' => time(), 'mobileverify' => 1, 'comefrom' => 'mobile', 'avatar' => $avatar);
                //--------------deng end register----------------
			}
			else 
			{
				if (empty($member)) 
				{
					show_json(0, '此手机号未注册');
				}
				$salt = m('account')->getSalt();
				$data = array('salt' => $salt, 'pwd' => md5($pwd . $salt));
			}
			if (empty($member)) 
			{
				pdo_insert('ewei_shop_member', $data);
			}
			else 
			{
				pdo_update('ewei_shop_member', $data, array('id' => $member['id']));
			}
			if (p('commission')) 
			{
				p('commission')->checkAgent($openid);
			}
			unset($_SESSION[$key]);
			show_json(1, (empty($type) ? '注册成功' : '密码重置成功'));
		}
		$sendtime = $_SESSION['verifycodesendtime'];
		if (empty($sendtime) || (($sendtime + 60) < time())) 
		{
			$endtime = 0;
		}
		else 
		{
			$endtime = 60 - time() - $sendtime;
		}
		$set = $this->getWapSet();
		include $this->template('rf', get_defined_vars(), null, true);
	}
	public function logout() 
	{
		global $_W;
		global $_GPC;
        if (!is_error(redis())) {
            if(!$_GPC['token']) show_json(0,'请输入正确的token');
            redis()->hDel('token_validate',$_GPC['token']);
        }

		$key = '__ewei_shopv2_member_session_' . $_W['uniacid'];
		isetcookie($key, false, -100);
        //-------------deng start logout---------------
        if($_W['is_api'])show_json(1,'退出登录');
        //--------------deng end logout----------------
		header('location: ' . mobileUrl());
		exit();
	}
	public function sns() 
	{
		global $_W;
		global $_GPC;
        //---------------deng start sns-------------------
        if (empty($_W['is_api']) && (is_weixin() || !(empty($_GPC['__ewei_shopv2_member_session_' . $_W['uniacid']])) ) )
        //----------------deng end sns--------------------
		{
			header('location: ' . mobileUrl());
		}
		$sns = trim($_GPC['sns']);
		if (($_W['ispost'] || is_h5app()) && !(empty($sns)) && !(empty($_GPC['openid'])))
		{
			m('member')->checkMemberSNS($sns);
		}
		if ($_GET['openid']) 
		{
			if ($sns == 'qq') 
			{
				$_GET['openid'] = 'sns_qq_' . $_GET['openid'];
			}
			if ($sns == 'wx') 
			{
				$_GET['openid'] = 'sns_wx_' . $_GET['openid'];
			}
			m('account')->setLogin($_GET['openid']);
		}
		$backurl = '';
		if (!(empty($_GPC['backurl']))) 
		{
			$backurl = $_W['siteroot'] . 'app/index.php?' . base64_decode(urldecode($_GPC['backurl']));
		}
		$backurl = ((empty($backurl) ? mobileUrl(NULL, NULL, true) : trim($backurl)));
        //if(is_h5app())show_json(1, '登录成功');
        if(is_h5app()){
            exit(app_success('登录成功',array('url'=>$backurl)));
        }
		else header('location: ' . $backurl);
	}
	public function verifycode() 
	{
		global $_W;
		global $_GPC;
		@session_start();
		$set = $this->getWapSet();
		$mobile = trim($_GPC['mobile']);
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
		$member = pdo_fetch('select id,openid,mobile,pwd,salt from ' . tablename('ewei_shop_member') . ' where mobile=:mobile and mobileverify=1 and uniacid=:uniacid limit 1', array(':mobile' => $mobile, ':uniacid' => $_W['uniacid']));
		if (($temp == 'sms_forget') && empty($member)) 
		{
			show_json(0, '此手机号未注册');
		}
		if (($temp == 'sms_reg') && !(empty($member))) 
		{
			show_json(0, '此手机号已注册，请直接登录');
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
			show_json(1, ['code' => $code,'message' => '成功']);
		}
		show_json(0, $ret['message']);
	}


}
?>