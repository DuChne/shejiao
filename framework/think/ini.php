<?php
/**
 * tsx 初始化tp拓展 拓展包括 模型 debug 缓存 数据库查询
 */
//include_once IA_ROOT.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'think'.DIRECTORY_SEPARATOR.'ini.php';
use think\Config;
use think\Error;
use think\Container;

//tsx 注册自动加载函数
spl_autoload_register(function ($name){

    $name = str_replace('\\',DIRECTORY_SEPARATOR,$name);
    $file = dirname(__DIR__).DIRECTORY_SEPARATOR.$name.'.php';

    if(is_file($file)) include_once $file;
});

//定义常量
define('IS_CLI', preg_match("/cli/i", php_sapi_name()) ? true : false);
define('LOG_PATH', Config::get('cache.path'));
define('STARTMEM', memory_get_usage());
define('SITEURL', $_W['siteurl']);
defined('STARTTIMES')?'':define('STARTTIMES', microtime(true));

//注册自定义异常处理
Error::register();
//加载拓展配置文件
$configRoot = __DIR__.DIRECTORY_SEPARATOR.'thinkConfig';

if(is_dir($configRoot)){
    foreach (glob($configRoot.DIRECTORY_SEPARATOR.'*.php') as $name){
        $filename = pathinfo($name,PATHINFO_FILENAME);
        if($filename == 'config'){
            Config::load($name);
        }else{
            Config::load($name,$filename);
        }
    }
}else{
    exit('think配置文件目录不存在'.$configRoot);
}

//加载共用文件
include_once __DIR__.DIRECTORY_SEPARATOR.'function.php';

//**加载语言包
$lang_root = __DIR__.DIRECTORY_SEPARATOR.'lang';

if(is_dir($configRoot)){
    foreach (glob($lang_root.DIRECTORY_SEPARATOR.'*.php') as $name){
        $filename = pathinfo($name,PATHINFO_FILENAME);
        Container::get('lang')->load($name,$filename);
    }
}

