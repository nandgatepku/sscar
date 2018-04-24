<?php
namespace app\admin\controller;

use app\admin\common\Base;
use think\Db;

class Index extends Base
{
    public function index()
    {
        return $this->fetch('index');
    }
}
