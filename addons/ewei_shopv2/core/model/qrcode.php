<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Qrcode_EweiShopV2Model 
{
	public function createShopQrcode($mid = 0, $posterid = 0) 
	{
		global $_W;
		global $_GPC;
		$path = IA_ROOT . '/addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/';
		if (!(is_dir($path))) 
		{
			load()->func('file');
			mkdirs($path);
		}
		$url = mobileUrl('', array('mid' => $mid), true);
		if (!(empty($posterid))) 
		{
			$url .= '&posterid=' . $posterid;
		}
		$file = 'shop_qrcode_' . $posterid . '_' . $mid . '.png';
		$qrcode_file = $path . $file;
		if (!(is_file($qrcode_file))) 
		{
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
		}
		return $_W['siteroot'] . 'addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/' . $file;
	}
	public function createGoodsQrcode($mid = 0, $goodsid = 0, $posterid = 0) 
	{
		global $_W;
		global $_GPC;
		$path = IA_ROOT . '/addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'];
		if (!(is_dir($path))) 
		{
			load()->func('file');
			mkdirs($path);
		}
		$url = mobileUrl('goods/detail', array('id' => $goodsid, 'mid' => $mid), true);
		if (!(empty($posterid))) 
		{
			$url .= '&posterid=' . $posterid;
		}
		$file = 'goods_qrcode_' . $posterid . '_' . $mid . '_' . $goodsid . '.png';
		$qrcode_file = $path . '/' . $file;
		if (!(is_file($qrcode_file))) 
		{
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
		}
		return $_W['siteroot'] . 'addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/' . $file;
	}
	public function createQrcode($url) 
	{
		global $_W;
		global $_GPC;
		$path = IA_ROOT . '/addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/';
		if (!(is_dir($path))) 
		{
			load()->func('file');
			mkdirs($path);
		}
		$file = md5(base64_encode($url)) . '.jpg';
		$qrcode_file = $path . $file;
		if (!(is_file($qrcode_file))) 
		{
			require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
			QRcode::png($url, $qrcode_file, QR_ECLEVEL_L, 4);
		}
		return $_W['siteroot'] . 'addons/ewei_shopv2/data/qrcode/' . $_W['uniacid'] . '/' . $file;
	}

    /**
     * @param $qrcode, erweima
     * @param $url , wangzhi
     * @param $bg ,  beijingtupian
     * @param $avatar, touxiang
     * @param $nickname, nicheng
     * @param $sitename, zhandianmingcheng
     */
	public function  createShareQrcode($qrcode,$url,$bg,$avatar,$nickname,$sitename)
    {
        global $_W;

        if (!(is_file($qrcode)))
        {
            include IA_ROOT . '/addons/ewei_shopv2/func.php';
            //shengcheng
            set_time_limit(0);
            @ini_set('memory_limit', '256M');
            $size = getimagesize(tomedia($bg));
            $target = imagecreatetruecolor($size[0], $size[1]);
            $bg = imagecreates(tomedia($bg));
            imagecopy($target, $bg, 0, 0, 0, 0,$size[0], $size[1]);
            imagedestroy($bg);
            //hebing touxiang
            $left = $size[0] / 2 + 80;
            $top = 350;
            $width = 100;
            $height = 100;
            $avatarimg = saveImage($avatar);
            mergeImage($target, $avatarimg, array('left' => $left, 'top' => $top, 'width' => $width, 'height' => $height));
            @unlink($avatarimg);
            //xiezi
            mergeText('', $target, $nickname, array('size' => '14', 'color' => '#F00', 'left' => $left, 'top' => 460));
            //erweima
            $img = IA_ROOT . "/addons/ewei_shopv2/data/qrcode/temp_qrcode.png";
            require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
            $errorCorrectionLevel = "L";
            $matrixPointSize      = "4";
            QRcode::png($url, $img, $errorCorrectionLevel, $matrixPointSize, 2);
            $qrcode_png = imagecreatefrompng($img);
            $left = $left - 20;
            imagecopyresized($target, $qrcode_png, $left, 550, 0, 0, 162, 162,162,162);
            @unlink($img);
            imagejpeg($target, $qrcode);
            imagedestroy($target);
            return $qrcode;
        }
        else
        {
            return $qrcode;
        }
    }
	
	public function  createShareQrcode1($qrcode,$url,$bg,$avatar,$nickname,$sitename)
    {
        global $_W;

        if (!(is_file($qrcode)))
        {
            include IA_ROOT . '/addons/ewei_shopv2/func.php';
            //shengcheng
            set_time_limit(0);
            @ini_set('memory_limit', '256M');
            $size = getimagesize(tomedia($bg));
            $target = imagecreatetruecolor($size[0], $size[1]);
            $bg = imagecreates(tomedia($bg));
            imagecopy($target, $bg, 0, 0, 0, 0,$size[0], $size[1]);
            imagedestroy($bg);
            //hebing touxiang
            $left = $size[0] / 2 + 80;
            $top = 250;
            $width = 100;
            $height = 100;
            //$avatarimg = saveImage($avatar);
            //mergeImage($target, $avatarimg, array('left' => $left, 'top' => $top, 'width' => $width, 'height' => $height));
            //@unlink($avatarimg);
			$leftnickname = $left - 155;
            //xiezi
            mergeText('', $target, $nickname, array('size' => '26', 'color' => '#FFF', 'left' => $leftnickname, 'top' => 460));
			//tuiguangma
			$leftnickname1 = $left - 48;
			mergeText('', $target, $sitename, array('size' => '26', 'color' => '#FFF', 'left' => $leftnickname1, 'top' => 537));
            //erweima
            $img = IA_ROOT . "/addons/ewei_shopv2/data/qrcode/temp_qrcode.png";
            require_once IA_ROOT . '/framework/library/qrcode/phpqrcode.php';
            $errorCorrectionLevel = "L";
            $matrixPointSize      = "5.5";
            QRcode::png($url, $img, $errorCorrectionLevel, $matrixPointSize, 2);
            $qrcode_png = imagecreatefrompng($img);
            $left = $left - 180;
            imagecopyresized($target, $qrcode_png, $left, 725, 0, 0, 220, 220,220,220);
            @unlink($img);
            imagejpeg($target, $qrcode);
            imagedestroy($target);
            return $qrcode;
        }
        else
        {
            return $qrcode;
        }
    }
}
?>