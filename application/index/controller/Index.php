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

    public function demo(){

//        include_once "wxBizDataCrypt.php";
        $appid = 'wx4f4bc4dec97d474b';
        $sessionKey = 'tiihtNczf5v6AKRyjwEUhQ==';

        $encryptedData="CiyLU1Aw2KjvrjMdj8YKliAjtP4gsMZM
                QmRzooG2xrDcvSnxIMXFufNstNGTyaGS
                9uT5geRa0W4oTOb1WT7fJlAC+oNPdbB+
                3hVbJSRgv+4lGOETKUQz6OYStslQ142d
                NCuabNPGBzlooOmB231qMM85d2/fV6Ch
                evvXvQP8Hkue1poOFtnEtpyxVLW1zAo6
                /1Xx1COxFvrc2d7UL/lmHInNlxuacJXw
                u0fjpXfz/YqYzBIBzD6WUfTIF9GRHpOn
                /Hz7saL8xz+W//FRAUid1OksQaQx4CMs
                8LOddcQhULW4ucetDf96JcR3g0gfRK4P
                C7E/r7Z6xNrXd2UIeorGj5Ef7b1pJAYB
                6Y5anaHqZ9J6nKEBvB4DnNLIVWSgARns
                /8wR2SiRS7MNACwTyrGvt9ts8p12PKFd
                lqYTopNHR1Vf7XjfhQlVsAJdNiKdYmYV
                oKlaRv85IfVunYzO0IKXsyl7JCUjCpoG
                20f0a04COwfneQAGGwd5oa+T8yO5hzuy
                Db/XcxxmK01EpqOyuxINew==";

        $iv = 'r7BXXKkLb8qrSNn05n0qiA==';

        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data );

        if ($errCode == 0) {
            print($data . "\n");
        } else {
            print($errCode . "\n");
        }
    }


    public  function user_login(){
        $APPID = 'wx3dd12da36570cd80';
        $AppSecret = '7799fedb17fd1463543704571be52cb4';
        $wx_request_url = 'https://api.weixin.qq.com/sns/jscode2session';
        $code = input("code");
        $param = array(
            'appid' => $APPID,
            'secret' => $AppSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        );
//        print_r($param);
        // 一个使用curl实现的get方法请求
        $arr = http_send($wx_request_url, $param, 'get');
//        $user_info_url = sprintf("%s?appid=%s&secret=%s&js_code=%s&grant_type=%",$wx_request_url,$param['appid'],$param['secret'],$param['js_code'],$param['grant_type']);
//        echo $user_info_url;
//        $weixin_user_data = json_decode(get_url($user_info_url));
//        $session_key = $weixin_user_data->session_key;
        $arr = json_decode($arr,true);
        if(isset($arr['errcode']) && !empty($arr['errcode'])){
            return json(['code'=>'2','message'=>$arr['errmsg'],"result"=>null]);
        }
        $openid = $arr['openid'];
        $session_key = $arr['session_key'];

        // 数据签名校验
        $signature = input("signature");
        $signature2 = sha1($_GET['rawData'].$session_key);  //别用框架自带的input,会过滤掉必要的数据
        if ($signature != $signature2) {
            $msg = "shibai 1";
            return json(['code'=>'2','message'=>'获取失败',"result"=>$msg]);
        }

        //开发者如需要获取敏感数据，需要对接口返回的加密数据( encryptedData )进行对称解密
        $encryptedData = $_GET['encryptedData'];
        $iv = $_GET['iv'];
        include_once '../api/wxBizDataCrypt.php';
        $pc = new \WXBizDataCrypt($APPID, $session_key);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);  //其中$data包含用户的所有数据
        if ($errCode != 0) {
            return json(['code'=>'2','message'=>'获取失败',"result"=>null]);
        }
        /****** */
        //写自己的逻辑： 操作数据库等操作
        /****** */
        //生成第三方3rd_session
        $session3rd  = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;
        for($i=0;$i<16;$i++){
            $session3rd .=$strPol[rand(0,$max)];
        }
        return json(['code'=>'1','message'=>'获取成功',"result"=>$session3rd]);

    }




    public function sendCode(){
        $APPID = 'wx3dd12da36570cd80';
        $AppSecret = '7799fedb17fd1463543704571be52cb4';
        $code = input('get.code');
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$APPID.'&secret='.$AppSecret.'&js_code='.$code.'&grant_type=authorization_code';
        $arr = $this -> vegt($url);

        $arr = json_decode($arr,true);
//        $openid = $arr['openid'];
        $session_key = $arr['session_key'];

        // 数字签名校验
        $signature = input('get.signature');
        $signature2 = sha1($_GET['rawData'].$session_key);
        if($signature != $signature2){
            echo "数字签名失败";
            die;
        }
        // 获取信息，对接口进行解密
        Vendor("PHP.wxBizDataCrypt");
        $encryptedData = $_GET['encryptedData'];
        $iv = $_GET['iv'];
        if(empty($signature) || empty($encryptedData) || empty($iv)){
            echo "传递信息不全";
        }
        include_once "../api/wxBizDataCrypt.php";

        $pc = new \WXBizDataCrypt($APPID,$session_key);
        $errCode = $pc->decryptData($encryptedData,$iv,$data);
        if($errCode != 0){
            echo "解密数据失败";
            die;
        }else {
            $data = json_decode($data,true);
            session('myinfo',$data);
            $save['openid'] = $data['openId'];
            $save['uname'] = $data['nickName'];
            $save['unex'] = $data['gender'];
            $save['address'] = $data['city'];
            $save['time'] = time();
            $map['openid'] = $data['openId'];
            !empty($data['unionId']) && $save['unionId'] = $data['unionId'];

//            $res = Db::name('user') -> where($map) -> find();
//            if(!$res){
//                $db = Db::name('user') -> insert($save);
//                if($db !== false){
//                    echo "保存用户成功";
//                }else{
//                    echo "error";
//                }
//            }else{
//                echo "用户已经存在";
//            }
        }
        //生成第三方3rd_session
        $session3rd  = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;
        for($i=0;$i<16;$i++){
            $session3rd .=$strPol[rand(0,$max)];
        }
        return json(['code'=>'1','message'=>'获取成功',"result"=>$session3rd]);
    }
    public function vegt($url){
        $info = curl_init();
        curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($info,CURLOPT_HEADER,0);
        curl_setopt($info,CURLOPT_NOBODY,0);
        curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info,CURLOPT_URL,$url);
        $output= curl_exec($info);
        curl_close($info);
        return $output;
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
        $params = [
            'appid' => $this->appid,
            'secret' => $this->secret,
            'js_code' => $code,
            'grant_type' => $this->grant_type
        ];
        $res = makeRequest($this->url, $params);

        if ($res['code'] !== 200 || !isset($res['result']) || !isset($res['result'])) {
            return json(ret_message('requestTokenFailed'));
        }
        $reqData = json_decode($res['result'], true);
        if (!isset($reqData['session_key'])) {
            return json(ret_message('requestTokenFailed'));
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
        $pc = new WXBizDataCrypt($this->appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data );

        if ($errCode !== 0) {
            return json(ret_message("encryptDataNotMatch"));
        }


        /**
         * 7.生成第三方3rd_session，用于第三方服务器和小程序之间做登录态校验。为了保证安全性，3rd_session应该满足：
         * a.长度足够长。建议有2^128种组合，即长度为16B
         * b.避免使用srand（当前时间）然后rand()的方法，而是采用操作系统提供的真正随机数机制，比如Linux下面读取/dev/urandom设备
         * c.设置一定有效时间，对于过期的3rd_session视为不合法
         *
         * 以 $session3rd 为key，sessionKey+openId为value，写入memcached
         */
        $data = json_decode($data, true);
        $session3rd = randomFromDev(16);

        $data['session3rd'] = $session3rd;
        cache($session3rd, $data['openId'] . $sessionKey);



        return json($data);
    }

    function ret_message($message = "") {
        if ($message == "") return ['result'=>0, 'message'=>''];
        $ret = lang($message);

        if (count($ret) != 2) {
            return ['result'=>-1,'message'=>'未知错误'];
        }
        return array(
            'result'  => $ret[0],
            'message' => $ret[1]
        );
    }

    /**
     * 发起http请求
     * @param string $url 访问路径
     * @param array $params 参数，该数组多于1个，表示为POST
     * @param int $expire 请求超时时间
     * @param array $extend 请求伪造包头参数
     * @param string $hostIp HOST的地址
     * @return array    返回的为一个请求状态，一个内容
     */
    function makeRequest($url, $params = array(), $expire = 0, $extend = array(), $hostIp = '')
    {
        if (empty($url)) {
            return array('code' => '100');
        }

        $_curl = curl_init();
        $_header = array(
            'Accept-Language: zh-CN',
            'Connection: Keep-Alive',
            'Cache-Control: no-cache'
        );
        // 方便直接访问要设置host的地址
        if (!empty($hostIp)) {
            $urlInfo = parse_url($url);
            if (empty($urlInfo['host'])) {
                $urlInfo['host'] = substr(DOMAIN, 7, -1);
                $url = "http://{$hostIp}{$url}";
            } else {
                $url = str_replace($urlInfo['host'], $hostIp, $url);
            }
            $_header[] = "Host: {$urlInfo['host']}";
        }

        // 只要第二个参数传了值之后，就是POST的
        if (!empty($params)) {
            curl_setopt($_curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($_curl, CURLOPT_POST, true);
        }

        if (substr($url, 0, 8) == 'https://') {
            curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($_curl, CURLOPT_URL, $url);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($_curl, CURLOPT_USERAGENT, 'API PHP CURL');
        curl_setopt($_curl, CURLOPT_HTTPHEADER, $_header);

        if ($expire > 0) {
            curl_setopt($_curl, CURLOPT_TIMEOUT, $expire); // 处理超时时间
            curl_setopt($_curl, CURLOPT_CONNECTTIMEOUT, $expire); // 建立连接超时时间
        }

        // 额外的配置
        if (!empty($extend)) {
            curl_setopt_array($_curl, $extend);
        }

        $result['result'] = curl_exec($_curl);
        $result['code'] = curl_getinfo($_curl, CURLINFO_HTTP_CODE);
        $result['info'] = curl_getinfo($_curl);
        if ($result['result'] === false) {
            $result['result'] = curl_error($_curl);
            $result['code'] = -curl_errno($_curl);
        }

        curl_close($_curl);
        return $result;
    }

    /**
     * 读取/dev/urandom获取随机数
     * @param $len
     * @return mixed|string
     */
    function randomFromDev($len) {
        $fp = @fopen('/dev/urandom','rb');
        $result = '';
        if ($fp !== FALSE) {
            $result .= @fread($fp, $len);
            @fclose($fp);
        }
        else
        {
            trigger_error('Can not open /dev/urandom.');
        }
        // convert from binary to string
        $result = base64_encode($result);
        // remove none url chars
        $result = strtr($result, '+/', '-_');

        return substr($result, 0, $len);
    }
}
