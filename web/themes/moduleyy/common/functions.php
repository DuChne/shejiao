<?php
use core\helpers\Json;
/**
 * 定义控制器模板文件处理方法
 */
if(!function_exists('tpl'))
{
    function tpl($name = '',$param = [],$isController = true,$flag = TEMPLATE_DISPLAY)
    {
        global  $_W;
        if($_W['is_api']){
            $data = ['code' => 1,'data' => $param];
            exit(json_encode($data));
        }

        if(!$name){
            $name = ACTION;
        }
        $array = explode('/',$name);

        if($isController){
            switch (count($array)){
                case 1:
                    $array[2] = MODULE;
                    $array[1] = CONTROLLER;
                    break;
                case 2:
                    $array[2] = MODULE;
                    break;
                default:
                    break;
            }
        }

        $module = array_shift($array);
        $name = implode(DN,$array);
        extract($param);

        $aplication = str_replace('\\',DN,NAMESPACE_NAME);
        $source =  ROOT . DN . $aplication . DN  . $module . DN .'view' .DN. $name .'.php';
        $compile = ROOT . DN .'runtime'. DN .'cache'. DN .'tpl'.  DN . $name .'.php';

        if(!is_file($source)) {

            trigger_error('模板文件不存在'.$source);
        }
        if(DEVELOPMENT || !is_file($compile) || filemtime($source) > filemtime($compile)) {
            template_compile($source, $compile);
        }
        switch ($flag) {
            case TEMPLATE_DISPLAY:
            default:
                extract($GLOBALS, EXTR_SKIP);
                include $compile;
                break;
            case TEMPLATE_FETCH:
                extract($GLOBALS, EXTR_SKIP);
                ob_flush();
                ob_clean();
                ob_start();
                include $compile;
                $contents = ob_get_contents();
                ob_clean();
                return $contents;
                break;
            case TEMPLATE_INCLUDEPATH:
                return $compile;
                break;
        }
    }
}

/**
 * 真确的json返回
 */
if (!function_exists('success')){
    function success($data = [],$exit = true)
    {
        $data = ['code' => 1,'data' => $data];
        $json = Json::encode($data);
        if($exit){
            exit($json);
        }

        return $json;
    }
}

/**
 * 错误的json返回
 */
if (!function_exists('error')){
    function error($data = '',$exit = true)
    {
        $data = ['code' => 0,'msg' => $data];
        $json = Json::encode($data);
        if($exit){
            exit($json);
        }

        return $json;
    }
}
