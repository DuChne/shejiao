<?php
namespace think\handle\validate;

use think\Validate;

class Template extends Validate
{
    protected $rule = [
        'name|名字'  =>  'require|max:25',
        'email' =>  'email',
    ];
}

?>