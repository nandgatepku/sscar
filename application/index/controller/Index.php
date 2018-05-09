<?php
namespace app\index\controller;

use app\index\common\Base;
use app\index\api\wxBizDataCrypt;
use app\index\api\errorCode;
use think\Db;

class Index extends Base
{
    public function index()
    {
        if(isset($_GET["rawdata"])){
            $weixiao = $_GET["rawdata"];
            $res = $this->weixiao_api($weixiao);
            $studentid = $this->res_find_studentid($res);
            $find['student_id'] = $studentid;
            $is_find = Db::table('info')->where($find)->select();

            session_start();
            $_SESSION['studentid'] = $studentid;

            if($is_find){
                return $this->redirect('Wechat/show_qrcode');
            }else{
                $have_major = $this->res_have_major($res);
                if($have_major){
                    $re_db =$this->res_to_db($res);
                    if($re_db){
                        return $this->redirect('Wechat/show_qrcode');
                    }else{
                        return $this->fetch('index');
                    }
                }else{
                    return $this->redirect('http://weixiao.qq.com/apps/school-auth/login?media_id=gh_c5c47de251c1&app_key=116BF40DF1AFB055&redirect_uri=https://icampus.ss.pku.edu.cn/iaaa/index.php/Home/Index/appredirect//appid/sspkuuwcychwknfwfc/detail/1.html');
                }

            }

        }else{
            return $this->fetch('index');
        }
    }

    public function wxLogin() {
        $code = input("code", '', 'htmlspecialchars_decode');
        $rawData = input("rawData", '', 'htmlspecialchars_decode');
        $signature = input("signature", '', 'htmlspecialchars_decode');
        $encryptedData = input("encryptedData", '', 'htmlspecialchars_decode');
        $iv = input("iv", '', 'htmlspecialchars_decode');

        $APPID = 'wx3dd12da36570cd80';
        $AppSecret = '7799fedb17fd1463543704571be52cb4';
        $wx_request_url = 'https://api.weixin.qq.com/sns/jscode2session';
        $params = [
            'appid' => $APPID,
            'secret' => $AppSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $res = makeRequest($wx_request_url, $params);

        if ($res['code'] !== 200 || !isset($res['result']) || !isset($res['result'])) {
            return json(ret_message('requestTokenFailed'));
//            return $res['result'];
        }
        $reqData = json_decode($res['result'], true);
        if (!isset($reqData['session_key'])) {
            return json(ret_message('requestTokenFailed'));
//            return $res['result'];
        }
        $sessionKey = $reqData['session_key'];

        $signature2 = sha1($rawData . $sessionKey);

        if ($signature2 !== $signature) return ret_message("signNotMatch");

        $pc = new WXBizDataCrypt($APPID, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);

        if ($errCode !== 0) {
            return json(ret_message("encryptDataNotMatch"));
//            print_r($errCode);
        }

        $wr = json_decode($data);
        $insert['openId'] = $wr -> openId;
        $insert['nickName'] = $wr -> nickName;
        $insert['gender'] = $wr -> gender;
        $insert['language'] = $wr -> language;
        $insert['city'] = $wr -> city;
        $insert['province'] = $wr -> province;
        $insert['country'] = $wr -> country;
        $insert['avatarUrl'] = $wr -> avatarUrl;
        $insert['login_time'] = time();

        Db::table('login')->insert($insert);

//	return json(ret_message("here"));

//        $data = json_decode($data, true);
//        $session3rd = randomFromDev(16);

//        $data['session3rd'] = $session3rd;
//        cache($session3rd, $data['openId'] . $sessionKey);

        return json($data);
    }

    public function recog_driv_front(){
        $file = request()->file('add_image');
        $type = $_POST['type'];
        $type = (int)$type;

        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' );
            $infoadd = $info->getSaveName();

            if($info){
                $apiurl = 'https://recognition.image.myqcloud.com/ocr/drivinglicence';
                $auth = '0ROlCNpA8Re1f40vILPY/ZxPJClhPTEyNTQzOTg1NzYmYj1zc2NhciZrPUFLSURvUnB4cVRzeXVmRVpoY3RvNTl6YzFFRTFiMklMdm9GVCZlPTE1MzE0OTQ5MzEmdD0xNTI0MjM3MzMxJnI9Mjg1OTUmdT0wJmY9';

                $dataurl = 'https://sscar.ptczn.cn/uploads/'.$infoadd;
                $opt = ["appid"=>'1254398576',"bucket"=>"sscar","type"=>$type, 'url'=>$dataurl];
                $opt_data = json_encode($opt);

                $header = array(
                    'Host:recognition.image.myqcloud.com',
                    'Content-Length:'.strlen($opt_data),
                    'Content-Type:application/json',
                      'Authorization:'.$auth
                );

                $curl = curl_init();  //初始化
                curl_setopt($curl,CURLOPT_URL,$apiurl);  //设置url
//                curl_setopt($curl,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);  //设置http验证方法
                curl_setopt($curl,CURLOPT_HEADER,0);  //设置头信息
                curl_setopt($curl,CURLOPT_HTTPHEADER,$header);  //设置头信息
                curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);  //设置curl_exec获取的信息的返回方式
                curl_setopt($curl,CURLOPT_POST,1);  //设置发送方式为post请求
                curl_setopt($curl,CURLOPT_POSTFIELDS,$opt_data);  //设置post的数据

                $result = curl_exec($curl);
                if($result === false){
                    echo curl_errno($curl);
                }
//                print_r($result);
                curl_close($curl);

                unlink('./uploads/'.$infoadd);
                $p = date("Ymd");
                rmdir('./uploads/'.$p);

                return json($result);

              }else{
                // 上传失败获取错误信息
                return json($file->getError());
            }
        }
    }

    public function recog_car_front(){
        $file = request()->file('add_image');
        $which_one = $_POST['which_one'];
        $openId = $_POST['openId'];

        if($file){
            $info = $file->move(ROOT_PATH . 'public' .DS . 'keyphoto' . DS .$openId );
            $infoadd = $info->getSaveName();
            $insert['openId'] = $openId;
            $insert['photo'] = $infoadd;
            $insert['upload_time']=time();
            $insert['which_one']= $which_one;
            $is_insert = Db::table('upload')->insert($insert);

            if($is_insert){
                $apiurl = 'https://recognition.image.myqcloud.com/ocr/plate';
                $auth = '0ROlCNpA8Re1f40vILPY/ZxPJClhPTEyNTQzOTg1NzYmYj1zc2NhciZrPUFLSURvUnB4cVRzeXVmRVpoY3RvNTl6YzFFRTFiMklMdm9GVCZlPTE1MzE0OTQ5MzEmdD0xNTI0MjM3MzMxJnI9Mjg1OTUmdT0wJmY9';

                $dataurl = 'https://sscar.ptczn.cn/keyphoto/'.$openId.'/'.$infoadd;
                $opt = ["appid"=>'1254398576','url'=>$dataurl];
                $opt_data = json_encode($opt);

                $header = array(
                    'Host:recognition.image.myqcloud.com',
                    'Content-Length:'.strlen($opt_data),
                    'Content-Type:application/json',
                    'Authorization:'.$auth
                );

                $curl = curl_init();  //初始化
                curl_setopt($curl,CURLOPT_URL,$apiurl);  //设置url
//                curl_setopt($curl,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);  //设置http验证方法
                curl_setopt($curl,CURLOPT_HEADER,0);  //设置头信息
                curl_setopt($curl,CURLOPT_HTTPHEADER,$header);  //设置头信息
                curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);  //设置curl_exec获取的信息的返回方式
                curl_setopt($curl,CURLOPT_POST,1);  //设置发送方式为post请求
                curl_setopt($curl,CURLOPT_POSTFIELDS,$opt_data);  //设置post的数据

                $result = curl_exec($curl);
                if($result === false){
                    echo curl_errno($curl);
                }
//                print_r($result);
                curl_close($curl);
//                $result['imgadd'] = $infoadd;

                $res_imgadd = array (
                    "res"  => $result,
                    "imgadd" => $infoadd,
                );

                return json($res_imgadd);

            }else{
                // 上传失败获取错误信息
                return json($file->getError());
            }
        }
    }


    public function upload()
    {
        $file = request()->file('add_image');
        $openId = $_POST['openId'];
        $which_one = $_POST['which_one'];
        $store = $file->move(ROOT_PATH . 'public' .DS . 'keyphoto' . DS . $openId);
        $infoadd = $store->getSaveName();
        $insert['openId'] = $openId;
        $insert['photo'] = $infoadd;
        $insert['upload_time']=time();
        $insert['which_one']= $which_one;
        Db::table('upload')->insert($insert);
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

    public function sql_photo(){
        $driver_front = $_POST['driver_front'];
        $driver_back = $_POST['driver_back'];
        $driving_front = $_POST['driving_front'];
        $driving_back = $_POST['driving_back'];
        $studentcard = $_POST['studentcard'];
        $car_front = $_POST['car_front'];
        $openId = $_POST['openId'];

        $insert['photo_driver_front'] = $driver_front;
        $insert['photo_driver_back'] = $driver_back;
        $insert['photo_driving_front'] = $driving_front;
        $insert['photo_driving_back'] = $driving_back;
        $insert['photo_studentcard'] = $studentcard;
        $insert['photo_car_front'] = $car_front;
        $insert['status'] = 0;
        $insert['update_time'] = time();

        $where['openId'] =$openId;
//        $sql_find = Db::table('photo')->where($where)->find();
        if(0){
            $sql_photo = Db::table('photo')->where($where)->update($insert);
        }else{
            $insert['openId'] = $openId;
            $sql_photo = Db::table('photo')->insert($insert);
        }
        $apply_id = Db::table('photo')->where($insert)->field('id')->select();

        if($sql_photo){
            $res['res'] = 'ok';
            $res['sql']=$sql_photo;
            $res['apply_id']=$apply_id['0']['id'];
            return json($res);
        }else{
            $res['res'] = 'fail';
            return json($res);
        }
    }

    public function get_data_api(){
        $studentid = $_POST['studentid'];
        $where['student_id'] = $studentid;
        $res = Db::table('info')->where($where)->field('name,major_name,telephone')->select();

        if($res){
            $data = array (
                "status"=>"ok",
                "name"  => $res['0']['name'],
                "major_name" => $res['0']['major_name'],
                "telephone"  => $res['0']['telephone'],
                "student_id"=>$studentid
            );
            return json($data);
        }else{
            $data=["status"=>"fail","student_id"=>$studentid];
            return json($data);
        }
    }

    public function get_apply_id_api(){
        $openId = $_POST['openId'];
        $where['openId'] = $openId;
        $res = Db::table('photo')->where($where)->whereIn('status', [0, 1, 2, 3])->field('id,status,status_2_because,driver_name,major_name,car_number,telephone')->select();

        if($res){
            $data = array (
                "status"=>"ok",
                "res"=>$res,
//                "apply_id"  => $res['0']['id'],
//                "apply_status" => $res['0']['status'],
                "openId"=>$openId
            );
            return json($data);
        }else{
            $data=["status"=>"fail","openId"=>$openId];
            return json($data);
        }
    }

    public function apply_txt(){
        $driver_name = $_POST['driver_name'];
        $car_number = $_POST['car_number'];
        $driving_name = $_POST['driving_name'];
        $telephone = $_POST['telephone'];
        $major_name = $_POST['major_name'];
        $studentid = $_POST['studentid'];
        $openId = $_POST['openId'];
        $apply_id = $_POST['apply_id'];

        $update['driver_name'] = $driver_name;
        $update['car_number'] = $car_number;
        $update['driving_name'] = $driving_name;
        $update['telephone'] = $telephone;
        $update['major_name'] = $major_name;
        $update['studentid'] = $studentid;

        $where['id'] = $apply_id;

        $sql_find = Db::table('photo')->where($where)->find();
        if($sql_find){
            $sql_photo = Db::table('photo')->where($where)->update($update);
        }
        if($sql_photo){
            $res['status'] = 'ok';
            $res['res']= $sql_photo;
            $res['apply_id'] = $apply_id;
            return json($res);
        }

    }

    function unicode_decode($name){
        $json = '{"str":"'.$name.'"}';
        $arr = json_decode($json,true);
        if(empty($arr)) return '';
        return $arr['str'];
    }

    public function weixiao_api($content){
        $url = 'https://icampus.ss.pku.edu.cn/iaaa/index.php/Home/OpenApi/decode';

        $data = ["appid"=>'sspkuuwcychwknfwfc','appsecret'=>'a19ff391407096a9ee3f79963a4c5bc0','content'=>$content];
//               $data = json_encode($data_tmp);
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    );

    /* 根据请求类型设置特定参数 */
    $opts[CURLOPT_URL] = $url ;


        $opts[CURLOPT_POST] = 1;
        $opts[CURLOPT_POSTFIELDS] = $data;

        if(is_string($data)){ //发送JSON数据
            $opts[CURLOPT_HTTPHEADER] = array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($data),
            );
        }

    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    //发生错误，抛出异常
    if($error) throw new \Exception('请求发生错误：' . $error);

//    $res =$this->res_to_db($data);
    return $data;

    }

    function res_find_studentid($data){
        $student_add = strpos($data,'studentid');
        $student_add =$student_add+12;
        $student_add_tmp =  substr($data,$student_add);
        $student_tmp2 = strpos($student_add_tmp,'"');
        $studentid = substr($data,$student_add,$student_tmp2);

        return $studentid;
    }

    function res_have_major($data){
        $add = strpos($data,'major_name');
        if($add === false){
            return false;
        }else{
            return true;
        }
    }

    function res_to_db($data){
        $uid_add = strpos($data,'uid');
        $uid_add =$uid_add+6;
        $uid_add_tmp =  substr($data,$uid_add);
        $uid_tmp2 = strpos($uid_add_tmp,'"');
        $uid = substr($data,$uid_add,$uid_tmp2);

        $student_add = strpos($data,'studentid');
        $student_add =$student_add+12;
        $student_add_tmp =  substr($data,$student_add);
        $student_tmp2 = strpos($student_add_tmp,'"');
        $studentid = substr($data,$student_add,$student_tmp2);

        $name_add = strpos($data,'name');
        $name_add =$name_add+7;
        $name_add_tmp =  substr($data,$name_add);
        $name_tmp2 = strpos($name_add_tmp,'"');
        $name = substr($data,$name_add,$name_tmp2);
        $name_zi = $this->unicode_decode($name);

        $major_name_add = strpos($data,'major_name');
        $major_name_add =$major_name_add+13;
        $major_name_add_tmp =  substr($data,$major_name_add);
        $major_name_tmp2 = strpos($major_name_add_tmp,'"');
        $major_name = substr($data,$major_name_add,$major_name_tmp2);
        $major_name_zi = $this->unicode_decode($major_name);

        $telephone_add = strpos($data,'telephone');
        $telephone_add =$telephone_add+12;
        $telephone_add_tmp =  substr($data,$telephone_add);
        $telephone_tmp2 = strpos($telephone_add_tmp,'"');
        $telephone = substr($data,$telephone_add,$telephone_tmp2);

        $grade_add = strpos($data,'grade');
        $grade_add =$grade_add+8;
        $grade_add_tmp =  substr($data,$grade_add);
        $grade_tmp2 = strpos($grade_add_tmp,'"');
        $grade = substr($data,$grade_add,$grade_tmp2);

        $where['uid'] = $uid;
        $select = Db::table('info')->where($where)->select();
        if($select){
            return true;
        }else{
            $insert['uid'] = $uid;
            $insert['name'] = $name_zi;
            $insert['student_id'] = $studentid;
            $insert['major_name'] = $major_name_zi;
            $insert['telephone'] = $telephone;
            $insert['grade'] = $grade;
            $to_db = Db::table('info')->insert($insert);
            if($to_db){
                return true;
            }else{
                return false;
            }
        }
    }


}
