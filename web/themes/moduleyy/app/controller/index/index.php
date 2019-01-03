<?php
namespace app\controller\index;

use think\Db;

class index{

    public function index()
    {

        tpl('',['title' =>'首页']);

    }
}