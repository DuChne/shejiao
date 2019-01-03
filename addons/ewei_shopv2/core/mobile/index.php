<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}

use think\Db;

class Index_EweiShopV2Page extends MobilePage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$this->diypage('home');
		$uniacid = $_W['uniacid'];
		$mid = intval($_GPC['mid']);
		$index_cache = $this->getpage();
		if (!(empty($mid))) 
		{
			$index_cache = preg_replace_callback('/href=[\\\'"]?([^\\\'" ]+).*?[\\\'"]/', function($matches) use($mid) 
			{
				$preg = $matches[1];
				if (strexists($preg, 'mid=')) 
				{
					return 'href=\'' . $preg . '\'';
				}
				if (!(strexists($preg, 'javascript'))) 
				{
					$preg = preg_replace('/(&|\\?)mid=[\\d+]/', '', $preg);
					if (strexists($preg, '?')) 
					{
						$newpreg = $preg . '&mid=' . $mid;
					}
					else 
					{
						$newpreg = $preg . '?mid=' . $mid;
					}
					return 'href=\'' . $newpreg . '\'';
				}
			}
			, $index_cache);
		}

		$shop_data = m('common')->getSysset('shop');
		include $this->template('',get_defined_vars());
	}
	public function get_recommand() 
	{
		global $_W;
		global $_GPC;
		$args = array('page' => $_GPC['page'], 'pagesize' => 6, 'isrecommand' => 1, 'order' => 'displayorder desc,createtime desc', 'by' => '');
		$recommand = m('goods')->getList($args);
		show_json(1, array('list' => $recommand['list'], 'pagesize' => $args['pagesize'], 'total' => $recommand['total'], 'page' => intval($_GPC['page'])));
	}
	private function getcache() 
	{
		global $_W;
		global $_GPC;
		return m('common')->createStaticFile(mobileUrl('getpage', NULL, true));
	}
    public function getpage()
    {
        global $_W;
        global $_GPC;
        $uniacid = $_W['uniacid'];
        $defaults = array( 'adv' => array('text' => '幻灯片', 'visible' => 1), 'search' => array('text' => '搜索栏', 'visible' => 1), 'nav' => array('text' => '导航栏', 'visible' => 1), 'notice' => array('text' => '公告栏', 'visible' => 1), 'cube' => array('text' => '魔方栏', 'visible' => 1), 'banner' => array('text' => '广告栏', 'visible' => 1), 'goods' => array('text' => '推荐栏', 'visible' => 1) );
        $sorts = ((isset($_W['shopset']['shop']['indexsort']) ? $_W['shopset']['shop']['indexsort'] : $defaults));
        $sorts['recommand'] = array('text' => '系统推荐', 'visible' => 1);
        $advs = pdo_fetchall('select id,advname,link,thumb from ' . tablename('ewei_shop_adv') . ' where uniacid=:uniacid and iswxapp=0 and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
        $navs = pdo_fetchall('select id,navname,url,icon from ' . tablename('ewei_shop_nav') . ' where uniacid=:uniacid and iswxapp=0 and status=1 order by displayorder desc', array(':uniacid' => $uniacid));
        $cubes = ((is_array($_W['shopset']['shop']['cubes']) ? $_W['shopset']['shop']['cubes'] : array()));
        $banners = pdo_fetchall('select id,bannername,link,thumb from ' . tablename('ewei_shop_banner') . ' where uniacid=:uniacid and iswxapp=0 and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
        $bannerswipe = $_W['shopset']['shop']['bannerswipe'];
        if (!(empty($_W['shopset']['shop']['indexrecommands'])))
        {
            $goodids = implode(',', $_W['shopset']['shop']['indexrecommands']);
            if (!(empty($goodids)))
            {
                $indexrecommands = pdo_fetchall('select id, title, thumb, marketprice,ispresell,presellprice, productprice, minprice, total from ' . tablename('ewei_shop_goods') . ' where id in( ' . $goodids . ' ) and uniacid=:uniacid and status=1 order by instr(\'' . $goodids . '\',id),displayorder desc', array(':uniacid' => $uniacid));
                foreach ($indexrecommands as $key => $value )
                {
                    if (0 < $value['ispresell'])
                    {
                        $indexrecommands[$key]['minprice'] = $value['presellprice'];
                    }
                }
            }
        }
        $goodsstyle = $_W['shopset']['shop']['goodsstyle'];
        $notices = pdo_fetchall('select id, title, link, thumb from ' . tablename('ewei_shop_notice') . ' where uniacid=:uniacid and iswxapp=0 and status=1 order by displayorder desc limit 5', array(':uniacid' => $uniacid));
        $seckillinfo = plugin_run('seckill::getTaskSeckillInfo');
        ob_start();
        ob_implicit_flush(false);
        require $this->template('index_tpl');
        return ob_get_clean();
    }
	public function seckillinfo() 
	{
		$seckillinfo = plugin_run('seckill::getTaskSeckillInfo');
		include $this->template('shop/index/seckill_tpl');
		exit();
	}
	public function qr() 
	{
		global $_W;
		global $_GPC;
		$url = trim($_GPC['url']);
		require IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
		QRcode::png($url, false, QR_ECLEVEL_L, 16, 1);
	}

    /**
     * 获取轮播图
     */
    public function getCover()
    {
        global $_GET;
        global $_W;
        $data = Db::name('ewei_shop_adv')->where(['enabled' => 1])->select();
        foreach ($data as $key => $item){
            $data[$key]['thumb'] = tomedia($item['thumb']);
        }
        show_json(1,['list' => $data]);
    }

    /**
     * 获取服务器时间
     */
    public function get_time()
    {

        show_json(1,['time' => time()]);

    }

    /**
     * web获取商品详情
     */
    public function getAllgoods(){
        global $_W;
        global $_GPC;
        $sql=" select * from ". tablename('ewei_shop_category')." where enabled=1 and level=1";
        $res = pdo_fetchall($sql);
        $tempid='';
        $temp = [];
        $condition1 =' 1';

        foreach ($res as $k=>$v){

            $sqlc=" select * from ". tablename('ewei_shop_category')." where enabled=1 and level=2 and parentid=" . $v['id'];
            $result = pdo_fetchall($sqlc);

            $res[$k]['advimg'] = tomedia($v['advimg']);
            $res[$k]['child'] =$result;
            foreach ($result as $kk=>$vv){
                $sqlgoods="select * from " . tablename('ewei_shop_goods'). " where ".$condition1." and pcate=".$v['id']." and ccate=".$vv['id']." and checked=0 and status=1 limit 8";

                $list = pdo_fetchall($sqlgoods);
                foreach ($list as $kkk=>$item) {
                    if(!empty($item['thumb']) && strpos($item['thumb'],'http') === false ){
                        $list[$kkk]['thumb'] = tomedia($item['thumb']);
                   }

                }
//                $list = tomedia($list, 'thumb');
                $res[$k]['child'][$kk]['children'] =$list;
            }
        }
        show_json(1,['list' => $res]);

    }

    public function test()
    {
        global $_W;

        GLOBAL $_W;
        $dn = DIRECTORY_SEPARATOR;
        include_once(IA_ROOT.$dn.'addons'.$dn.'ewei_shopv2'.$dn.'sqpay'.$dn.'toPayHtml.php');

        $returnUrl = $_W['siteurl'];
        $ordersn = time()."021";
        $price = 0.01;
        $sqpay = new SqPay();

        $sqpay->pay($ordersn,$price,$returnUrl,'pc');
       // t($_W['siteroot']);
    }
    /**
     * 处理前端回调地址
     */
    public function returnPay()
    {
        if($_GET['url']){
            header("Location: {$_GET['url']}");
        }
        echo "无法获取地址";
    }

}
?>