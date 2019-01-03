

if (!defined('IN_IA')) {

	exit('Access Denied');
}

class __moduleyy__Module extends WeModule
{
	public function welcomeDisplay()
	{
	    global $_W,$_GPC;
		header('location: ' . murl('site/entry/web',array('m'=>$_GPC['m'])));
		exit();
	}
}

?>