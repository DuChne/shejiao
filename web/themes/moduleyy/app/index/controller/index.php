<?php
namespace app\index\controller;

use think\Db;
use app\index\validate\user;

class index{

    public function index()
    {

        $name = new user();
        $name->check(['name' => '']);
        tpl('',['title' =>'首页']);

    }
}