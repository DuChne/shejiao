<?php
namespace app\index\validate;

use core\Validate;

class user extends Validate
{
    protected $rule = [
        'name' => 'require',
        ];
}