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
        /**
         * 3.小程序调用server获取token接口, 传入code, rawData, signature, encryptData.
         */
        $code = input("code", '', 'htmlspecialchars_decode');
        $rawData = input("rawData", '', 'htmlspecialchars_decode');
        $signature = input("signature", '', 'htmlspecialchars_decode');
        $encryptedData = input("encryptedData", '', 'htmlspecialchars_decode');
        $iv = input("iv", '', 'htmlspecialchars_decode');

        /**
         * 4.server调用微信提供的jsoncode2session接口获取openid, session_key, 调用失败应给予客户端反馈
         * , 微信侧返回错误则可判断为恶意请求, 可以不返回. 微信文档链接
         * 这是一个 HTTP 接口，开发者服务器使用登录凭证 code 获取 session_key 和 openid。其中 session_key 是对用户数据进行加密签名的密钥。
         * 为了自身应用安全，session_key 不应该在网络上传输。
         * 接口地址："https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code"
         */
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

        /**
         * 5.server计算signature, 并与小程序传入的signature比较, 校验signature的合法性, 不匹配则返回signature不匹配的错误. 不匹配的场景可判断为恶意请求, 可以不返回.
         * 通过调用接口（如 wx.getUserInfo）获取敏感数据时，接口会同时返回 rawData、signature，其中 signature = sha1( rawData + session_key )
         *
         * 将 signature、rawData、以及用户登录态发送给开发者服务器，开发者在数据库中找到该用户对应的 session-key
         * ，使用相同的算法计算出签名 signature2 ，比对 signature 与 signature2 即可校验数据的可信度。
         */
        $signature2 = sha1($rawData . $sessionKey);

        if ($signature2 !== $signature) return ret_message("signNotMatch");

        /**
         *
         * 6.使用第4步返回的session_key解密encryptData, 将解得的信息与rawData中信息进行比较, 需要完全匹配,
         * 解得的信息中也包括openid, 也需要与第4步返回的openid匹配. 解密失败或不匹配应该返回客户相应错误.
         * （使用官方提供的方法即可）
         */
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
//        return ['result'=>11,'message'=>'ok'];
    }

    public function upload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('add_image');
        $openId = $_POST['openId'];

        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $openId);
            $infoadd = $info->getSaveName();
            $del = __PUBLIC__ . '/uploads/'.$openId.'/'.$infoadd;
            $store = __APP_PATH__ . '/idcard/'.$openId.'/'.$infoadd;
            copy($del,$store);

            $insert['openId'] = $openId;
            $insert['photo_car_front'] = $infoadd;
            Db::table('photo')->insert($insert);
            if($info){
                $apiurl = 'https://recognition.image.myqcloud.com/ocr/drivinglicence';
                $auth = '0ROlCNpA8Re1f40vILPY/ZxPJClhPTEyNTQzOTg1NzYmYj1zc2NhciZrPUFLSURvUnB4cVRzeXVmRVpoY3RvNTl6YzFFRTFiMklMdm9GVCZlPTE1MzE0OTQ5MzEmdD0xNTI0MjM3MzMxJnI9Mjg1OTUmdT0wJmY9';

                $dataurl = 'https://sscar.ptczn.cn/uploads/'.$openId.'/'.$infoadd;
                $opt = ["appid"=>'1254398576',"bucket"=>"sscar","type"=>1, 'url'=>$dataurl];
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

                if (!unlink($del))
                {
                    echo ("Error deleting $del");
                }
                else
                {
                    return json($result);
                }

                // 成功上传后 获取上传信息
                // 输出 jpg
//                echo $info->getExtension();
//                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getSaveName();
//                // 输出 42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getFilename();
//                return json($info->getExtension());
            }else{
                // 上传失败获取错误信息
                return json($file->getError());
            }
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
