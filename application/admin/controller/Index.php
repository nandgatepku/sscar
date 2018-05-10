<?php
namespace app\admin\controller;

use app\admin\common\Base;
use think\Db;
use think\Loader;

class Index extends Base
{
    public function islog()
    {
        session_start();
        if(empty($_SESSION['kname'])) {
            return $this->redirect('Login/login');
        }
    }

    public function index()
    {
        $this->islog();
        $user = $_SESSION['kname'];
        $this->assign('user',$user);
        return $this->fetch('index');
    }

    public function add()
    {
        $this->islog();
        $user = $_SESSION['kname'];
        $this->assign('user',$user);
        return $this->fetch('add');
    }

    public function photo()
    {
        $this->islog();
        $apply_id = $_GET['id'];
        $where['id'] = $apply_id;
        $data = Db::table('photo')->where($where)->select();
        $user = $_SESSION['kname'];
        $this->assign('data',$data['0']);
        $this->assign('apply_id',$apply_id);
        $this->assign('user',$user);
        return $this->fetch('photo');
    }

    public function checkbutton()
    {
        $this->islog();
        $user = $_SESSION['kname'];
        $this->assign('user',$user);
        $apply_id = $_GET['id'];

        $where['id'] = $apply_id;

        $data = Db::table('photo')->where($where)->field('driver_name,studentid')->select();
        $this->assign('data',$data);
        $this->assign('apply_id',$apply_id);
        return $this->fetch('checkbutton');
    }

    function pass_api()
    {
        $apply_id = $_POST['apply_id'];
        $where['id'] = $apply_id;
        $update['status']=1;

        $res = Db::table('photo')->where($where)->update($update);
        if($res){
            $status=["status"=>"ok"];
            return json($status);
        }else{
            $status=["status"=>"fail"];
            return json($status);
        }
    }

    function dele_api()
    {
        $apply_id = $_POST['apply_id'];
        $where['id'] = $apply_id;
        $update['status']=4;

        $res = Db::table('photo')->where($where)->update($update);
        if($res){
            $status=["status"=>"ok"];
            return json($status);
        }else{
            $status=["status"=>"fail"];
            return json($status);
        }
    }

    function toback_api()
    {
        $apply_id = $_POST['apply_id'];
        $because = $_POST['because'];
        $where['id'] = $apply_id;
        $update['status']=2;
        $update['status_2_because']=$because;

        $res = Db::table('photo')->where($where)->update($update);
        if($res){
            $status=["status"=>"ok"];
            return json($status);
        }else{
            $status=["status"=>"fail"];
            return json($status);
        }
    }

    function update_api()
    {
        $apply_id = $_POST['apply_id'];

        $where['id']=$apply_id;
        $major_name = $_POST['major_name'];
        $driving_name = $_POST['driving_name'];
        $driver_name = $_POST['driver_name'];
        $studentid = $_POST['studentid'];
        $telephone = $_POST['telephone'];
        $car_number = $_POST['car_number'];

        $update['major_name'] = $major_name;
        $update['driving_name'] = $driving_name;
        $update['driver_name'] = $driver_name;
        $update['studentid'] = $studentid;
        $update['telephone'] = $telephone;
        $update['car_number'] = $car_number;
//        $update['status'] = 0;
//        $update['update_time'] = time();

        $res = Db::table('photo')->where($where)->update($update);
        if($res){
            $status=["status"=>"ok"];
            return json($status);
        }else{
            $status=["status"=>"fail"];
            return json($status);
        }
    }

    function add_api()
    {
        $major_name = $_POST['major_name'];
        $driving_name = $_POST['driving_name'];
        $driver_name = $_POST['driver_name'];
        $studentid = $_POST['studentid'];
        $telephone = $_POST['telephone'];
        $car_number = $_POST['car_number'];

        $insert['major_name'] = $major_name;
        $insert['driving_name'] = $driving_name;
        $insert['driver_name'] = $driver_name;
        $insert['studentid'] = $studentid;
        $insert['telephone'] = $telephone;
        $insert['car_number'] = $car_number;
        $insert['status'] = 0;
        $insert['update_time'] = time();

        $res = Db::table('photo')->insert($insert);
        if($res){
            $status=["status"=>"ok"];
            return json($status);
        }else{
            $status=["status"=>"fail"];
            return json($status);
        }
    }

    function logout()
    {
        session_start();
        session_unset();
        return $this->redirect('Login/login');
    }

    public function welcome()
    {
        $this->islog();
        $user = $_SESSION['kname'];
        $this->assign('user',$user);
        return $this->fetch('welcome');
    }

    public function check_wait()
    {
        $this->islog();
        $user = $_SESSION['kname'];
        $this->assign('user',$user);

//        $where['status'] = 0 or 2 or 3;
//      $list=Db::query("select id,title,abstract,cre_time,author from news order by id DESC") -> paginate(5);
        $list = Db::table('photo')->whereIn('status', [0, 2, 3])->field('id,openId,car_number,driver_name,driving_name,update_time,major_name,telephone,studentid,status')->order('id','desc')->paginate(6);

//      $page=new Fpage($list->currentPage(),$list->lastPage());
        $page = $list->render();
        $this->assign('page', $page);
        $this ->assign('list',$list);
        return $this->fetch('check_wait');
    }

    public function check_valid()
    {
        $this->islog();
        $user = $_SESSION['kname'];
        $this->assign('user',$user);

        $where['status'] = 1;
//      $list=Db::query("select id,title,abstract,cre_time,author from news order by id DESC") -> paginate(5);
        $list = Db::table('photo')->where($where)->field('id,openId,car_number,driver_name,driving_name,update_time,major_name,telephone,studentid')->order('id','desc')->paginate(6);

//      $page=new Fpage($list->currentPage(),$list->lastPage());
        $page = $list->render();
        $this->assign('page', $page);
        $this ->assign('list',$list);
        return $this->fetch('check_valid');
    }

    public function check_other()
    {
        $this->islog();
        $user = $_SESSION['kname'];
        $this->assign('user',$user);

//      $list=Db::query("select id,title,abstract,cre_time,author from news order by id DESC") -> paginate(5);
        $list = Db::table('photo')->field('id,openId,pku_id,car_number,driver_name,driving_name,update_time')->order('id','desc')->paginate(6);

//      $page=new Fpage($list->currentPage(),$list->lastPage());
        $page = $list->render();
        $this->assign('page', $page);
        $this ->assign('list',$list);
        return $this->fetch('check_other');
    }

    function topdf($apply_id=102){
        $this->islog();
        if(!empty($_GET['id'])){
            $apply_id = $_GET['id'];
        }
        $where['id'] = $apply_id;
        $data = Db::table('photo')->where($where)->field('id,openId,car_number,driver_name,driving_name,update_time,major_name,telephone')->select();
        $studentid = $data["0"]["telephone"];
        Loader::import('TCPDF.tcpdf');
        $html='<p><b style="text-align:center;">北京大学软件与微电子学院机动车入校通行证申请</b></p><p></p>
<p><b>信息登记表：</b></p>';
        $html = $html . '
<table border="1" cellpadding="6" style="text-align:center; font-size:16px;">
    <tr >
        <td style="line-height:3;">部门/系</td>
        <td style="line-height:1.5;">行驶证<br/>登记所有人</td>
        <td style="line-height:3;">驾驶员</td>
        <td style="line-height:3;">手机号</td>
        <td style="line-height:3;">车号</td>
    </tr>
    <tr>
        <td>' . $data["0"]["major_name"] . '</td>
        <td>' . $data["0"]["driving_name"] . '</td>
        <td>' . $data["0"]["driver_name"] . '</td>
        <td>' . $data["0"]["telephone"] . '</td>
        <td>' . $data["0"]["car_number"] . '</td>
    </tr>
</table>
<p></p>
<p></p>
';
        $html = $html . '<p></p><p></p><p></p><div style="text-align:center;"><b>申请人承诺条款</b></div>
<p>1、遵守北京大学软件与微电子学院校园交通管理规定，服从学院管理，接受并配合门卫人员和校园巡查人员的验证检查，服从指挥。</p>
  <p>2、进入校园后，文明行车，限速15公里/小时行驶，避让一切行人和非机动车，不鸣笛，不使用远光灯，不占道停车，不占消防通道。</p>
  <p>3、妥善保管该通行证，专车专用，不伪造车证，不为可能导致伪造的扫描、复印、拍照等行为提供便利。车证正向放置。</p>
  <p>4、不运送与学校无工作关系的人员入校，不为校园违规行为提供便利。</p>
  <p>5、如学校需要，及时挪走车辆。</p>
  <div style="text-indent: 2em;">本人郑重声明，已阅知并承诺履行上述条款，如有违反，自愿接受限制入校禁令。</div>
  <p></p>
  <p></p>
  <p></p>
  <p></p>
  <p></p>
  <p style="text-align:right;">驾驶员签字：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
  <p style="text-align:right;">日期：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>';

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT,true, 'UTF-8', false);

        // 设置打印模式
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('导出'.$studentid.'申请表');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
        // 是否显示页眉
        $pdf->setPrintHeader(false);
        // 设置页眉显示的内容
        $pdf->SetHeaderData('logo.png', 60, 'baijunyao.com', '空白', array(0,64,255), array(0,64,128));
        // 设置页眉字体
        $pdf->setHeaderFont(Array('dejavusans', '', '12'));
        // 页眉距离顶部的距离
        $pdf->SetHeaderMargin('5');
        // 是否显示页脚
        $pdf->setPrintFooter(false);
        // 设置页脚显示的内容
//        $pdf->setFooterData(array(0,64,0), array(0,64,128));
//        // 设置页脚的字体
//        $pdf->setFooterFont(Array('dejavusans', '', '10'));
        // 设置页脚距离底部的距离
        $pdf->SetFooterMargin('10');
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // 设置行高
        $pdf->setCellHeightRatio(1.1);
        // 设置左、上、右的间距
        $pdf->SetMargins('10', '10', '10');
        // 设置是否自动分页  距离底部多少距离时分页
        $pdf->SetAutoPageBreak(TRUE, '15');
        // 设置图像比例因子
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetMargins(17, 15, 17);//左、右、上
        $pdf->SetAutoPageBreak(TRUE, 15);//下
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        $pdf->setFontSubsetting(true);
        $pdf->AddPage();
        // 设置字体
        $pdf->SetFont('stsongstdlight', '', 14, '', true);
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->Output('apply_'.$apply_id.'_id_'.$studentid.'.pdf', 'I');
    }
}
