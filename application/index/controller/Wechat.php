<?php
/**
 * Created by PhpStorm.
 * User: PTcZn
 * Date: 2018/5/5
 * Time: 15:42
 */

namespace app\index\controller;


use app\index\common\Base;
use think\Db;

class Wechat extends Base
{
    public function show_qrcode(){
//        $studentid=1701210403;
        session_start();
        if(empty($_SESSION['studentid'])) {
            return $this->redirect('Index/index');
        }
        $studentid = $_SESSION['studentid'];
        $qrcode = $this->get_qrcode($studentid);

        $this->assign('studentid', $studentid);
        $this->assign('qrcode', $qrcode);
        return $this->fetch('show_qrcode');
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

    public function get_token(){
        $get_sql = Db::table('wx_token')->order('id','desc')->limit(1)->select();
        $get_sql = $get_sql['0'];
        $old_time = $get_sql['time'];
        $old_token = $get_sql['token'];
        $now = time();
        if(($now - $old_time)<3600){
            $token = $old_token;
        }else{
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx3dd12da36570cd80&secret=7799fedb17fd1463543704571be52cb4';
            $curl = curl_init(); // 启动一个CURL会话
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
            $tmpInfo = curl_exec($curl);     //返回api的json对象
            //关闭URL请求
            curl_close($curl);

            $token_add = strpos($tmpInfo,'access_token');
            $token_add =$token_add+15;
            $token_add_tmp =  substr($tmpInfo,$token_add);
            $token_tmp2 = strpos($token_add_tmp,'"');
            $token = substr($tmpInfo,$token_add,$token_tmp2);

            $insert['time'] = time();
            $insert['token'] = $token;
            Db::table('wx_token')->insert($insert);
        }

        return $token;
    }

    public function get_qrcode($studentid=1701210403){
        $token = $this->get_token();
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$token;

        $data_tmp = ["scene"=>$studentid];
        $data = json_encode($data_tmp);
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

        $ok = file_put_contents('static/qrcode/'.$studentid.'data.jpg', $data);
        if($ok){
            return 'ok';
        }else{
            return $data;
        }
    }

    public function send_message($token,$openId,$formId,$apply_id,$car_number,$status=1,$status_2_because=[]){
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$token;

        if($status==1){
            $data_tmp = ["touser"=>$openId,"form_id"=>$formId,"page"=>"pages/center/center"
                ,"data"=>["keyword1"=>["value"=>$apply_id."(车号：".$car_number.")"],"keyword2"=>["value"=>"审核通过"],"keyword3"=>["value"=>"请至学院6号楼领取车证，并现场缴纳10元工本费"]]
                ,"template_id"=>"k5AUvxmGk7LO3Et6li5_dRKfEVgc-bhRRG4G9pxcYVc"];
        }elseif ($status==2){
            $data_tmp = ["touser"=>$openId,"form_id"=>$formId,"page"=>"pages/center/center"
                ,"data"=>["keyword1"=>["value"=>$apply_id."(车号：".$car_number.")"],"keyword2"=>["value"=>"申请被驳回，（理由：".$status_2_because.")"],"keyword3"=>["value"=>"请重新提交申请"]]
                ,"template_id"=>"k5AUvxmGk7LO3Et6li5_dRKfEVgc-bhRRG4G9pxcYVc"];
        }

        $data = json_encode($data_tmp);
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
        $ret  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        //发生错误，抛出异常
        if($error) throw new \Exception('请求发生错误：' . $error);

        return $ret;
    }

    public function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $echostr = $_GET["echostr"];

        $token = 'sscar';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return $echostr;
        }else{
            return false;
        }
    }

}