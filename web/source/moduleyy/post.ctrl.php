<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');
define('ROOT_PATH',str_replace('\\','/',realpath(dirname(__FILE__).'/../../../'))); //定义站点目录

load()->model('module');
load()->model('wxapp');

$dos = array('post');
$do = in_array($do, $dos) ? $do : 'post';

if ($do == 'post') {

    $_W['page']['title'] = '新增应用';
    if (checksubmit()) {
        $name = trim($_GPC['name']) ? trim($_GPC['name']) : itoast('标识不能为空', '', 'error');
        $title = trim($_GPC['title']) ? trim($_GPC['title']) : itoast('名称不能为空', '', 'error');
        $version = trim($_GPC['version']) ? trim($_GPC['version']) : itoast('版本号不能为空', '', 'error');
        $author = trim($_GPC['author']) ? trim($_GPC['author']) : itoast('作者不能为空', '', 'error');
        $description = trim($_GPC['description']) ? trim($_GPC['description']) : itoast('介绍不能为空', '', 'error');
        $data = array(
            'name' => $name,
            'title' => $title,
            'version' => $version,
            'type'  =>  $_GPC['type'],
            'ability'   =>  $description,
            'description'  =>  $description,
            'author' => $author,
            'url'   =>  trim($_GPC['url']) ? trim($_GPC['url']) : '',
            'settings'   =>  0,
            'subscribes'   =>  is_array($_GPC['subscribes']) ? serialize($_GPC['subscribes']) : serialize(array()),
            'handles'   =>  is_array($_GPC['handles']) ? serialize($_GPC['handles']) : serialize(array()),
            'isrulefields'  =>  0,
            'iscard'    =>  0,
            'app_support'   =>  2,
            'wxapp_support' =>  1,
            'permissions'   =>  is_array($_GPC['permissions']) ? serialize($_GPC['permissions']) : serialize(array()),
            'issystem'  =>  0,
            'title_initial'  =>  get_first_pinyin($title),
        );
        if (!empty($_GPC['thumb'])) {
            $data['thumb'] = $_GPC['thumb'];
        } else {
            $data['thumb'] = '';
        }


        $re = add_module($data);
        if(empty($re))itoast('创建应用模块文件失败-已存在或没有写入权限', '', 'error');
        //unset($data['thumb']);
        //pdo_insert('modules', $data);
        itoast('新增应用模块成功', url('module/manage-system/install',array('module_name'=>$data['name'])), 'success');
    }
    template('moduleyy/post');
}

function add_module($arr = array()){

    $dirname = ROOT_PATH."/addons/{$arr['name']}";
    if (file_exists($dirname))return false;
    if (@mkdir($dirname, 0777)) {

        copyDir('',$dirname);

        //@mkdir("{$dirname}/inc", 0777);//控制器文件
        //@mkdir("{$dirname}/template", 0777);//模板文件
        //@file_put_contents("{$dirname}/site.php", get_site($arr));//配置文件
        //@file_put_contents("{$dirname}/manifest.xml", get_xml($arr));//XML文件

        $site_file = file_get_contents("{$dirname}/site.php");
        if(empty($site_file))return false;
        $site_file = str_replace('__moduleyy__',$arr['name'],$site_file);
        @file_put_contents("{$dirname}/site.php", '<?php'.$site_file);//配置文件

        $module_file = file_get_contents("{$dirname}/module.php");
        $module_file = str_replace('__moduleyy__',$arr['name'],$module_file);
        @file_put_contents("{$dirname}/module.php", '<?php'.$module_file);//module文件

        @file_put_contents("{$dirname}/manifest.xml", get_xml($arr));//XML文件

        return true;
    }
    return false;
}

function copyDir($dirSrc,$dirTo)
{
    if(empty($dirSrc))$dirSrc = ROOT_PATH.'/web/themes/moduleyy';
    if(is_file($dirTo))
    {
        echo $dirTo . '这不是一个目录';
        return false;
    }
    if(!file_exists($dirTo))
    {
        mkdir($dirTo);
    }

    if($handle=opendir($dirSrc))
    {
        while($filename=readdir($handle))
        {
            if($filename!='.' && $filename!='..')
            {
                $subsrcfile=$dirSrc . '/' . $filename;
                $subtofile=$dirTo . '/' . $filename;
                if(is_dir($subsrcfile))
                {
                    copyDir($subsrcfile,$subtofile);//再次递归调用copydir
                }
                if(is_file($subsrcfile))
                {
                    copy($subsrcfile,$subtofile);
                }
            }
        }
        closedir($handle);
    }
}

function get_xml($arr = array()){
    $data = '<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns="" versionCode="">
	<application setting="false">
		<name><![CDATA['.$arr['title'].']]></name>
		<identifie><![CDATA['.$arr['name'].']]></identifie>
		<version><![CDATA['.$arr['version'].']]></version>
		<type><![CDATA['.$arr['type'].']]></type>
		<ability><![CDATA['.$arr['description'].']]></ability>
		<description><![CDATA['.$arr['description'].']]></description>
		<author><![CDATA['.$arr['author'].']]></author>
		<url></url>
	</application>
</manifest>
    ';

    return $data;
}