<?php
/**
 * Created by PhpStorm.
 * User: PTcZn
 * Date: 2018/4/24
 * Time: 18:30
 */

namespace app\admin\controller;

use app\admin\common\Base;

class Login extends Base
{
    public function login()
    {
        return $this->fetch('login');
    }

}