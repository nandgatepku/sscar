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

        }
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

    public function apply_txt(){
        $driver_name = $_POST['driver_name'];
        $car_number = $_POST['car_number'];
        $openId = $_POST['openId'];

        $update['driver_name'] = $driver_name;
        $update['car_number'] = $car_number;

        $where['openId'] =$openId;

        $sql_find = Db::table('photo')->where($where)->find();
        if($sql_find){
            $sql_photo = Db::table('photo')->where($where)->update($update);
        }
        if($sql_photo){
            $res['res'] = 'ok';
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

    public function weixiao(){
        $apiurl = 'https://icampus.ss.pku.edu.cn/iaaa/index.php/Home/OpenApi/decode';

//        $dataurl = 'https://sscar.ptczn.cn/uploads/'.$infoadd;
        $opt = ["appid"=>'sspkuuwcychwknfwfc','appsecret'=>'a19ff391407096a9ee3f79963a4c5bc0','content'=>'b10548eb856a95a7b1ac5719e3080bd5c2fa070219d07b371c5517e953d82b3a2b05bcc180ef6ee0e387829611ff0ad64520356e5dba64db11b7cc3e7ca5a099f1fc395450fe2bb44b48ad61e6dc1ab29e05681d60ab499908ac38957ede917f746d068d5477d17808e5554809a83bf938817627db542a0f06afd32279e9e516b7419aa2933b2ddc715bd57c94ba19d5f36c5650acb6c517c8a891f8039d27af5605b6ca8bc6b65e8a2d7fbcffc06ec60cbeb41163c182d4b2f46249da18556bf4a08fdc8f922688e4e7f497b83cf77a90aa2a3274ff337ea6e7aa8ac0da2099de4f8a5c880b35e60ec1dc60d2fcef702be3c62213bddef84ecc7f021d58fba938245f61c4eb7128a8fe68552eb1289ff211f41d640e3ccc3cd9eed5582b11c95795d4090f1cd2c3fcf7ce1019d223791797d57b15c3e9c9d4489ad5de933230b32602e4d038e76e96798b6bbfea4bb8dca478b96b60fb73e13ee7d10626d53aff28a7360460e4eb0bf1f19b0a17331b78b98d72e09c1a3f1a0f49ca615792b7a7b8d85ad0f636a978375173d6b1bdba439d8ec4e42ecb3f2cfbc9109c8bcb96e5e3a819d8925c1fe22f72655e8e37054b3715fc492c46c356096c58f95af72df8b8a4113993b4d45c2c51aedbdcff6617a8cad27017eecc5e2aca1040bf99cdbb73f2d7b78c18259abaca0e09cd1086cf5266b2f387fd4030bd3e6b5bed9fa65709fd3040862099ba63e26620e66b824ca0c1701b38d2ea242c70b251efa8e42d946436b69a272bfb4900b363a6eb31e433493d70d51819edfac6775fbae7b538075ab35559cea5dfe920a48f28e7cefb400d4803de813a86ad4ec34185661d0134f9298de6eb6f6f79d877fcd62341cf0d2e41f87de0af96d86584bddf4a16d2234c2205a805b9a004bd33dd5d54aadc9f3994d1364544051b0683e3fe79afc4155d85014da409d1c8ea6a0fbe2fcf8235325e019fbbd4f30f3be640f0fe955e28ca9727556a0d6f8a68b9ec8db5658650869d4d30f7b0003a582d584e51575d427e20c7539a3d13f11b94010a8baf8771cd24639d9ded429a1d0a5287e3e5cb54bdd50c4a430bf8f94c38416fa312'];
        $opt_data = json_encode($opt);

//        $header = array(
//            'Host:icampus.ss.pku.edu.cn',
//            'Content-Length:'.strlen($opt_data),
//            'Content-Type:application/json',
////            'Authorization:'.$auth
//        );

        $curl = curl_init();  //初始化
        curl_setopt($curl,CURLOPT_URL,$apiurl);  //设置url
//                curl_setopt($curl,CURLOPT_HTTPAUTH,CURLAUTH_BASIC);  //设置http验证方法
        curl_setopt($curl,CURLOPT_HEADER,1);  //设置头信息
//        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);  //设置头信息
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

//        $res_imgadd = array (
//            "res"  => $result,
//            "imgadd" => $infoadd,
//        );
        print_r($result);
//        return json($result);
    }

}
