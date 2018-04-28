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
        return $this->fetch('index');
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
        /**
         * 7.生成第三方3rd_session，用于第三方服务器和小程序之间做登录态校验。为了保证安全性，3rd_session应该满足：
         * a.长度足够长。建议有2^128种组合，即长度为16B
         * b.避免使用srand（当前时间）然后rand()的方法，而是采用操作系统提供的真正随机数机制，比如Linux下面读取/dev/urandom设备
         * c.设置一定有效时间，对于过期的3rd_session视为不合法
         *
         * 以 $session3rd 为key，sessionKey+openId为value，写入memcached
         */
//        $data = json_decode($data, true);
//        $session3rd = randomFromDev(16);

//        $data['session3rd'] = $session3rd;
//        cache($session3rd, $data['openId'] . $sessionKey);

        return json($data);
    }

    public function recog_driv_front(){
        $file = request()->file('add_image');
        $type = $_POST['type'];

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
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' );
            $infoadd = $info->getSaveName();
            $insert['openId'] = $openId;
            $insert['photo'] = $infoadd;
            $insert['upload_time']=time();
            $insert['which_one']= $which_one;
            $is_insert = Db::table('upload')->insert($insert);

            if($is_insert){
                $apiurl = 'https://recognition.image.myqcloud.com/ocr/plate';
                $auth = '0ROlCNpA8Re1f40vILPY/ZxPJClhPTEyNTQzOTg1NzYmYj1zc2NhciZrPUFLSURvUnB4cVRzeXVmRVpoY3RvNTl6YzFFRTFiMklMdm9GVCZlPTE1MzE0OTQ5MzEmdD0xNTI0MjM3MzMxJnI9Mjg1OTUmdT0wJmY9';

                $dataurl = 'https://sscar.ptczn.cn/uploads/'.$infoadd;
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
//                unlink('./uploads/'.$infoadd);
//                $p = date("Ymd");
//                rmdir('./uploads/'.$p);

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
        $store = $file->move(ROOT_PATH . 'photo' . DS . $openId);
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
        $sql_find = Db::table('photo')->where($where)->find();
        if($sql_find){
            $sql_photo = Db::table('photo')->where($where)->update($insert);
        }else{
            $insert['openId'] = $openId;
            $sql_photo = Db::table('photo')->insert($insert);
        }
        if($sql_photo){
            $res['res'] = 'ok';
            return json($res);
        }else{
            $res['res'] = 'fail';
            return json($res);
        }
    }

    public function signwechat(){
        $appid = "1254398576";
        $bucket = "sscar";
        $secret_id = "";
        $secret_key = "";
        $expired = time() + 7257600;
        $onceExpired = 0;
        $current = time();
        $rdm = rand();
        $userid = "0";
        $fileid = "tencentyunSignTest";

        $srcStr = 'a='.$appid.'&b='.$bucket.'&k='.$secret_id.'&e='.$expired.'&t='.$current.'&r='.$rdm.'&u='
            .$userid.'&f=';

        $signStr = base64_encode(hash_hmac('SHA1', $srcStr, $secret_key, true).$srcStr);
        echo $signStr."\n";
    }

}
