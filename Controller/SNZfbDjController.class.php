<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class SNZfbDjController extends PayController
{
    public function Pay($array)
    {
        $body = I('request.pay_productname');
        $parameter = [
            'code' => 'SNZfbDj',
            'title' => '苏宁支付宝(dj)',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'app_code' => $return['mch_id'], //
            'nonce_str' => time(),
            'out_trade_no' => $return['orderid'],
            'total_fee' => $return["amount"] * 100,
            'pay_type' => 'alipay',
            'notify_url' => $return['notifyurl'],
        ];

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $data['sign'] =  md5($md5str . "key=" . $return["signkey"]);
        $response = $this -> post($return['gateway'], $data);



        Log::record('SNZfbDj pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);

        $response = json_decode($response, true);
        if ($response['response']['code'] == '10000' ) {

            header("location: {$response['response']['h5_page']}");
        } else {
            echo $response;
        }

    }

    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        Log::record('SNZfbDj订单失败：'.$response.'--'.json_decode($response).'--'.json_encode($response),'ERR',true);
        $response = json_decode($response, true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，SNZfbDj notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = '389150c2edcd4284a2877ec40046e1d6';
        //$publiKey = getKey($response["out_trade_no"]); // 密钥
        $data = [
            'nonce_str' => $response['nonce_str'],
            'out_trade_no' => $response['out_trade_no'],
            'status' => $response['status'],
            'total_fee' => $response['total_fee'],
            'sn' => $response['sn'],

        ];
        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $sign =  strtoupper(md5($md5str . "key=" . $publiKey));

        if ($sign == $response['sign'] && $response['status'] == '5') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["out_trade_no"]])->find();
                if(!$o){
                    Log::record('SNZfbDj回调失败,找不到订单：'.$response,'ERR',true);
                    exit('error:order not fount'.$response["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['total_fee'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("SNZfbDj回调失败,金额不等：{$response['total_fee'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error!');
                }


                $old_order = $Order->where(['upstream_order'=>$response['sn']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response['out_trade_no']){
                    Log::record("SNZfbDj回调失败,重复流水号  ：".$response.'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['sn'])){
                    Log::record("流水号为空  ：".$response.'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save([ 'upstream_order'=>$response['sn']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                $return = [
                    'code' => '10000',
                    'message' => "处理成功",
                ];
                exit(json_encode($return));
            }catch (Exception $e){
                Log::record('SNZfbDj回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //未支付。。。
            Log::record('SNZfbDj订单失败：'.$response,'ERR',true);
            exit("error:not pay");
        }
        else {
            Log::record('SNZfbDj订单失败：'.$sign.'---'.$response['sign'],'ERR',true);
            exit('error:check sign Fail!');
        }
    }

    //同步通知
    public function callbackurl()
    {
        $Order = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        } else {
            exit("error");
        }
    }

    protected function post($url,$data)
    {
        {
            $jsonStr = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($jsonStr)
                )
            );
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $response;
        }
    }




}