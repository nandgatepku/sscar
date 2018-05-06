<?php
/**
 * Created by PhpStorm.
 * User: PTcZn
 * Date: 2018/4/24
 * Time: 18:30
 */

namespace app\admin\controller;

use app\admin\common\Base;
use think\Db;

class Login extends Base
{
    public function login()
    {
        return $this->fetch('login');
    }

    public function denglu(){
        $kname=$_POST['user']; // 获取用户名
        $kpwd=$_POST['password'];   // 获取密码
        $where['user']=$kname;
        $where['password']=md5($kpwd);
        if (isset($_POST['sub'])) {
            if(1) {
                if (!empty($kname) && !empty($kpwd)) {//如果用户名和密码非空
                    $select = Db::table('admin')->where($where)->select(); // 执行查询
                    if ($select) {// 如果存在该用户
                        //将用户名和密码保存在session中
                        session_start();
                        $_SESSION['kname'] = $kname;
//                        $_SESSION['kpwd'] = $kpwd;
                        //跳转到用户中心
                        $this->redirect('Index/index', '', 0, '登录成功！前往管理后台!...页面跳转中...');
//                        echo "loading...";
//                        $this->redirect('Back/Index');
//                        exit('<script language="javascript">top.location.href="../Index/index.html"</script>');

                    } else {  // 如果用户不存在
                        $this->error('用户名或密码错误!...页面跳转中...', 'login', '', '2');
                    }
                } else { // 如果用户名或密码未填写
                    $this->error('请填写用户名或密码!...页面跳转中...', 'login', '','2');
                }
            }else{
                $this->error('验证码不正确!...页面跳转中...','index','','2');
            }
        }

        if(isset($_POST['re'])){
            $this->redirect('log','',0,'...刷新界面...');
        }
    }

}