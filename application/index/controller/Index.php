<?php
namespace app\index\controller;

use app\index\common\Base;
use app\index\api\wxBizDataCrypt;
use app\index\api\errorCode;

class Index extends Base
{
    public function index()
    {
        return $this->fetch('index');
    }

    public function weixinlogin($user_id = null)
    {
        global $App_Error_Conf, $Gift_Ids, $Server_Http_Path, $Is_Local, $Test_User, $Good_Vcode, $WeiXin_Xd_Conf;
        $validator_result = input_validator(array('code', 'iv', 'encryptedData'));
        if (!empty($validator_result)) {
            return response($validator_result);
        }
        $js_code = $_REQUEST['code'];
        $encryptedData = $_REQUEST['encryptedData'];
        $iv = $_REQUEST['iv'];
        $appid = $WeiXin_Xd_Conf['appid'];
        $secret = $WeiXin_Xd_Conf['secret'];
        $grant_type = $WeiXin_Xd_Conf['grant_type'];
        //从微信获取session_key
        $user_info_url = $WeiXin_Xd_Conf['code2session_url'];
        $user_info_url = sprintf("%s?appid=%s&secret=%s&js_code=%s&grant_type=%", $user_info_url, $appid, $secret, $js_code, $grant_type);
        $weixin_user_data = json_decode(get_url($user_info_url));
        $session_key = $weixin_user_data->session_key;
//解密数据
        $data = '';
        $wxBizDataCrypt = new WXBizDataCrypt($appid, $session_key);
        $errCode = $wxBizDataCrypt > decryptData($appid, $session_key, $encryptedData, $iv, $data);
    }

    public function wxlogin(){

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
}
