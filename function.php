<?php

use think\Db;
use think\Config;
use think\Container;

/**
 * 获取客户端IP地址
 * @access public
 * @param  integer   $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param  boolean   $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
if(!function_exists('ip')){
    function ip($type = 0, $adv = true)
    {
        $type      = $type ? 1 : 0;
        static $ip = null;

        if (null !== $ip) {
            return $ip[$type];
        }

        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? [$ip, $long] : ['0.0.0.0', 0];

        return $ip[$type];
    }
}

/**
 * 是否是ajax请求
 */
if(!function_exists('isAjax')){
    function isAjax(){
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

}

if (!function_exists('db')) {
    /**
     * 实例化数据库类
     * @param string        $name 操作的数据表名称（不含前缀）
     * @param array|string  $config 数据库配置参数
     * @param bool          $force 是否强制重新连接
     * @return \think\db\Query
     */
    function db($name = '', $config = [], $force = false)
    {
        return Db::connect($config, $force)->name($name);
    }
}

if (!function_exists('config')) {
    /**
     * 获取和设置配置参数
     * @param string|array  $name 参数名
     * @param mixed         $value 参数值
     * @param string        $range 作用域
     * @return mixed
     */
    function config($name = '', $value = null, $range = '')
    {
        if (is_null($value) && is_string($name)) {
            return 0 === strpos($name, '?') ? Config::has(substr($name, 1), $range) : Config::get($name, $range);
        } else {
            return Config::set($name, $value, $range);
        }
    }
}

/**
 * 加载模型
 */

if (!function_exists('tsx')) {
    /**
     * @param string $name
     * @param array $vars
     * @param string $sub_class
     * @param string $base
     * @return object
     */
    function tsx($name = '',$vars = [], $sub_class = 'model',$base = 'think\\handle\\',$newInstance = false)
    {
        $namespace = $base.$sub_class.'\\'.$name;

        return Container::get($namespace,$vars,$newInstance);
    }
}



/**
 * 调试函数
 */
if(!function_exists('T')){
    function T($data,$if = true){
        echo '<pre>';
        print_r($data);
        if($if) exit;
    }
}

