<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/7
 * Time: 15:48
 */

return [
    'debug' => true,
    'app_trace' => true,
    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',
    // 异常页面的模板文件
    'exception_tmpl'         => dirname(__DIR__).DIRECTORY_SEPARATOR.'tpl'.DIRECTORY_SEPARATOR.'think_exception.tpl',
    //分页配置
    'paginate' => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],
];