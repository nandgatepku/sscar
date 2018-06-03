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

    public function upload_api(){
        // 获取上传文件
        $file = request() -> file('fig_studentcard');
        $apply_id = $_POST['apply_id'];
        $openId = $_POST['openId'];
        $studentid = $_POST['studentid'];
        $which_one = $_POST['which_one'];
        $user = $_POST['user'];
        // 验证图片,并移动图片到框架目录下。
        $store = $file ->  move(ROOT_PATH . 'public' .DS . 'keyphoto' . DS . $openId);;
        $infoadd = $store->getSaveName();
        $insert['openId'] = $openId;
        $insert['photo'] = $infoadd;
        $insert['pku_id'] = $studentid;
        $insert['upload_time']=time();
        $insert['which_one']= $which_one;
        $insert['upload_by']= $user;
        Db::table('upload')->insert($insert);
        $where['id'] = $apply_id;
        if($which_one == 5){
            $update['photo_studentcard'] = $infoadd;
        }
        if($which_one == 1){
            $update['photo_driver_front'] = $infoadd;
        }
        if($which_one == 2){
            $update['photo_driver_back'] = $infoadd;
        }
        if($which_one == 3){
            $update['photo_driving_front'] = $infoadd;
        }
        if($which_one == 4){
            $update['photo_driving_back'] = $infoadd;
        }
        $sql_photo = Db::table('photo')->where($where)->update($update);
        if ($file) {
            if ($store) {
                return $infoadd;
            } else {
                return 'can not upload';
            }
        } else {
            return json($file->getError());
        }
    }

    public function edit_w(){
        $this->islog();

        $apply_id = $_POST['apply_id'];
        $major_name = $_POST['major_name'];
        $driving_name = $_POST['driving_name'];
        $driver_name = $_POST['driver_name'];
        $studentid = $_POST['studentid'];
        $telephone = $_POST['telephone'];
        $car_number = $_POST['car_number'];

        $where['id'] = $apply_id;
        $update['major_name'] = $major_name;
        $update['driving_name'] = $driving_name;
        $update['driver_name'] = $driver_name;
        $update['studentid'] = $studentid;
        $update['telephone'] = $telephone;
        $update['car_number'] = $car_number;

        $res = Db::table('photo')->where($where)->update($update);
        if($res){
            $status=["status"=>"ok"];
            return '更新成功，请关闭本弹窗后刷新列表';
        }else{
            $status=["status"=>"fail"];
            return json($status);
        }
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
        $user = $_POST['user'];

        $insert['major_name'] = $major_name;
        $insert['driving_name'] = $driving_name;
        $insert['driver_name'] = $driver_name;
        $insert['studentid'] = $studentid;
        $insert['telephone'] = $telephone;
        $insert['car_number'] = $car_number;
        $insert['openId'] = $user;
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
        $num_wait = Db::table('photo')->whereIn('status', [0, 2, 3])->count();
        $num_valid = Db::table('photo')->where('status', 1)->count();

        $this ->assign('num_wait',$num_wait);
        $this ->assign('num_valid',$num_valid);
        return $this->fetch('welcome');
    }

    public function check_wait()
    {
        $this->islog();
        $search_view = isset($_POST['search_car']) ? $_POST['search_car'] : '';
        $user = $_SESSION['kname'];
        $this->assign('user',$user);

        if(isset($_POST['search_car'])){
            $search_car = $_POST['search_car'];
            $where['car_number'] = array('like','%'.$search_car.'%');
            $list = Db::table('photo')->whereIn('status', [0, 2, 3])->where($where)->field('id,openId,car_number,driver_name,driving_name,update_time,major_name,telephone,studentid,status')->order('id','desc')->paginate(6);
            $num_wait = Db::table('photo')->whereIn('status', [0, 2, 3])->where($where)->count();
        }else{
            $list = Db::table('photo')->whereIn('status', [0, 2, 3])->field('id,openId,car_number,driver_name,driving_name,update_time,major_name,telephone,studentid,status')->order('id','desc')->paginate(6);
            $num_wait = Db::table('photo')->whereIn('status', [0, 2, 3])->count();
    }
//        $where['status'] = 0 or 2 or 3;
//      $list=Db::query("select id,title,abstract,cre_time,author from news order by id DESC") -> paginate(5);

//      $page=new Fpage($list->currentPage(),$list->lastPage());
        $page = $list->render();
        $this->assign('page', $page);
        $this ->assign('list',$list);
        $this ->assign('num_wait',$num_wait);
        $this ->assign('search',$search_view);
        return $this->fetch('check_wait');
    }

    public function check_valid()
    {
        $this->islog();
        $search_view = isset($_POST['search_car']) ? $_POST['search_car'] : '';
        $user = $_SESSION['kname'];
        $this->assign('user',$user);

        $where['status'] = 1;

        if(isset($_POST['search_car'])){
            $search_car = $_POST['search_car'];
            $where['car_number'] = array('like','%'.$search_car.'%');
        }

//      $list=Db::query("select id,title,abstract,cre_time,author from news order by id DESC") -> paginate(5);
        $list = Db::table('photo')->where($where)->field('id,openId,car_number,driver_name,driving_name,update_time,major_name,telephone,studentid')->order('id','desc')->paginate(6);

        $num = Db::table('photo')->where($where)->count();
//      $page=new Fpage($list->currentPage(),$list->lastPage());
        $page = $list->render();
        $this->assign('page', $page);
        $this ->assign('list',$list);
        $this ->assign('num',$num);
        $this ->assign('search',$search_view);
        return $this->fetch('check_valid');
    }

    function setting(){
        $this->islog();
        $where['thing'] = 'most_number';
        $now_most_number = Db::table('setting')->where($where)->field('number')->find();
        $where2['thing'] = 'law';
        $now_law = Db::table('setting')->where($where2)->field('content')->find();
        $where3['thing'] = 'explain';
        $now_explain = Db::table('setting')->where($where3)->field('content')->find();
        $this->assign('now_most_number',$now_most_number['number']);
        $this->assign('now_law',$now_law['content']);
        $this->assign('now_explain',$now_explain['content']);
        return $this->fetch('setting');
    }

    function setting_api(){
        $most_number = $_POST['most_number'];
        $new_law = $_POST['new_law'];
        $new_explain = $_POST['new_explain'];

        $where['thing'] = 'most_number';
        $update['number'] = $most_number;
        $where2['thing'] = 'law';
        $update2['content'] = $new_law;
        $where3['thing'] = 'explain';
        $update3['content'] = $new_explain;

        $sql1 = Db::table('setting')->where($where)->update($update);
        $sql2 = Db::table('setting')->where($where2)->update($update2);
        $sql3 = Db::table('setting')->where($where3)->update($update3);
        if($sql1 || $sql2 || $sql3){
            $status=["status"=>"ok"];
        }else{
            $status=["status"=>"fail"];
        }
        return json($status);
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
        $data = Db::table('photo')->where($where)->field('id,openId,car_number,driver_name,driving_name,update_time,major_name,telephone,studentid')->select();
        $studentid = $data["0"]["studentid"];

        $where2['thing'] = 'law';
        $content_db = Db::table('setting')->where($where2)->field('content')->select();
        $content_db = $content_db['0']['content'];
//        settype($content_db, "string");

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
        $html = $html . '<p></p><p></p><p></p><div style="text-align:center;"><b>申请人承诺条款</b></div>';
        $html =$html.$content_db;
//        $html = $html .'        <p>1、遵守北京大学软件与微电子学院校园交通管理规定，服从学院管理，接受并配合门卫人员和校园巡查人员的验证检查，服从指挥。</p>
//  <p>2、进入校园后，文明行车，限速15公里/小时行驶，避让一切行人和非机动车，不鸣笛，不使用远光灯，不占道停车，不占消防通道。</p>
//  <p>3、妥善保管该通行证，专车专用，不伪造车证，不为可能导致伪造的扫描、复印、拍照等行为提供便利。车证正向放置。</p>
//  <p>4、不运送与学校无工作关系的人员入校，不为校园违规行为提供便利。</p>
//  <p>5、如学校需要，及时挪走车辆。</p>';
        $html = $html .'<p></p>
  <p></p>
  <p></p>
  <p></p>
  <p></p>
  <p style="text-align:right;">驾驶员签字：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>
  <p style="text-align:right;">日期：&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p>\';';

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
        $pdf->SetMargins(18, 15, 18);//左、右、上
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

    public function toxls($year='ALL'){
        $this->islog();
        $path = dirname(__FILE__); //找到当前脚本所在路径
        Loader::import('PHPExcel.PHPExcel');
        Loader::import('PHPExcel.PHPExcel.PHPExcel_IOFactory');
        Loader::import('PHPExcel.PHPExcel.PHPExcel_Cell');
        $path = dirname(__FILE__); //找到当前脚本所在路径
        Loader::import("PHPExcel.PHPExcel.PHPExcel");
        Loader::import("PHPExcel.PHPExcel.Writer.IWriter");
        Loader::import("PHPExcel.PHPExcel.Writer.Abstract");
        Loader::import("PHPExcel.PHPExcel.Writer.Excel5");
        Loader::import("PHPExcel.PHPExcel.Writer.Excel2007");
        Loader::import("PHPExcel.PHPExcel.IOFactory");

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        // 实例化完了之后就先把数据库里面的数据查出来
        $where['status'] = 1;
        if($year=='ALL'){
            $sql = Db::table('photo')->where($where)->select();
        }else{
            $start_time =  mktime(0,0,0,1,1,$year);
            $end_time =  mktime(23,59,59,12,31,$year);
            $where['update_time']  = array('between',array($start_time,$end_time));
            $sql = Db::table('photo')->where($where)->select();
        }

        // 设置表头信息
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '申请号')
            ->setCellValue('B1', '部门/系')
            ->setCellValue('C1', '机动车所有人')
            ->setCellValue('D1', '驾驶员')
            ->setCellValue('E1', '学号/工号')
            ->setCellValue('F1', '手机号')
            ->setCellValue('G1', '车号')
            ->setCellValue('H1', '申请时间');

        $i=2;  //定义一个i变量，目的是在循环输出数据是控制行数
        $count = count($sql);  //计算有多少条数据
        for ($i = 2; $i <= $count+1; $i++) {
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $sql[$i-2]['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $i, $sql[$i-2]['major_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $i, $sql[$i-2]['driving_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $i, $sql[$i-2]['driver_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $i, $sql[$i-2]['studentid']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $i, $sql[$i-2]['telephone']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $i, $sql[$i-2]['car_number']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $i, date('Y-m-d H:i:s',$sql[$i-2]['update_time']));
        }

        $objPHPExcel->getActiveSheet()->setTitle('有效车证');      //设置sheet的名称
        $objPHPExcel->setActiveSheetIndex(0);                   //设置sheet的起始位置
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');   //通过PHPExcel_IOFactory的写函数将上面数据写出来

        $PHPWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel,"Excel2007");

        header('Content-Disposition: attachment;filename="有效车证.xlsx"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
    }

    public function year_xls(){
//        $this->islog();
        $to_year = date('Y');
//        $to_year = 2017;
        $this->toxls($year=$to_year);
    }
}
