<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class PddXyController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'PddXy',
            'title'     => '拼多多(xy)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        //开始创建订单，订单生成参数请根据相关参数自行调整。
        $post['paytype'] = $return['appid'];
        $post['out_trade_no'] =  $return['orderid'];
        $post['notify_url'] = $return['notifyurl'];
        $post['return_url'] = $return['callbackurl'];
        $post['goodsname'] = "trade"; //商品名称
        $post['total_fee'] = $return["amount"];
       // $post['remark'] = "ceshi"; //平台的名称，做区分用的。
        $post['requestip'] = "127.0.0.1"; //玩家的IP。
        //结束创建订单

        $strs = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm"; //随机数基本字符串

        //加入商户ID及算签名
        $post1['mchid'] = 13472;
        //$post1['mchid'] = $return['mch_id'];
        $post1['timestamp'] = time(); //时间戳
        $post1['nonce'] = substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), 10);
        $post1['sign'] = $this -> getSign(array_merge($post, $post1), $return["signkey"]);//商户密匙，请自行调整

        $post1['data'] = $post; //合并真正提交的参数JSON

        //网关地址
        $gateway = $return['gateway'];
        //提交
        $response = $this -> curlPost($gateway, $post1);
        Log::record('PddXy pay url='.$return['gateway'].',data='.json_encode($post1).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        $contentType = I("request.content_type");

        if ($response['error'] == 0 && $contentType == 'json') {
            $return = [
                'result' => 'ok',
                //'url' => $response['pay_url'],
                'orderStr' => $response['data']['payurl']
            ];
            $this->ajaxReturn($return);
            //header("location: {$response['data']['payurl']}");
        } elseif ($response['error'] == 0) {
            header("location: {$response['data']['payurl']}");

        } else {
            echo $response;
        }

    }

    private function curlPost($url, $data = array())
    {
        $curl = curl_init();//初始化
        curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array( //改为用JSON格式来提交
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ));
        $result = curl_exec($curl);//执行命令
        curl_close($curl);//关闭URL请求
        return $result;
    }

    private function getSign($array = array(), $key)
    {
        ksort($array);
        foreach ($array as $k => $v) {
            if ($array[$k] == '' || $k == 'sign' || $k == 'sign_type' || $k == 'key') {
                unset($array[$k]);//去除多余参数
            }
        }
        return strtolower(md5($this ->createLinkString($array) . "&key=" . $key));
    }

    private function createLinkString($para)
    {
        $arg = "";
        foreach ($para as $key => $value) {
            $arg .= $key . "=" . $value . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    //异步通知
    public function notifyurl()
    {
        //先用$GLOBALS['HTTP_RAW_POST_DATA']来接收JSON
        $msg = $GLOBALS['HTTP_RAW_POST_DATA'];
        //如果不行的话，再尝试用php://input接收JSON参数
        if (!$msg) $msg = file_get_contents("php://input");
        if ($msg) {    //将接收到的数据转成数组
            $response = json_decode($msg, true);
        }
        if (!isset($response)) {    //不存在提交的JSON就用正常的表单办法GET或POST来接受参数
            $response = array_merge($_GET, $_POST);//无论GET还是POST
        }

        Log::record(" PddXy \$response=".json_encode($response),'ERR',true);
        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，PddXy notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_trade_no"]); // 密钥
        $getsign = $this -> getSign($response, $publiKey); //商户的KEY，请自行置换


        if ($response['sign'] == $getsign) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["out_trade_no"]])->find();
                if(!$o){
                    Log::record('拼多多xy回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['total_fee'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("拼多多xy回调失败,金额不等：{$response['total_fee'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['trade_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_trade_no"]){
                    Log::record("拼多多xf回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save([ 'upstream_order'=>$response['trade_no']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('拼多多xy回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('PddXy error:check sign Fail!','ERR',true);
            exit('error:check sign Fail!');
        }
    }

    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        } else {
            exit("error");
        }
    }


}