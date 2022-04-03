<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class XianYuTsSdkController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'XianYuTsSdk', // 通道名称
            'title' => '咸鱼（ts）',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );
        Log::record('XianYuTsSdkController  ==== parameter    ' . json_encode($parameter) , 'ERR', true);

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $data = [
            'mch_id' => $return['mch_id'],
            'pass_code' => $return['appid'],
            'subject' => 'trade',
            'out_trade_no' => $return['orderid'],
            'amount' => $return['amount'],
            'client_ip' => '127.0.0.1',
            'notify_url' => $return['notifyurl'],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $data['sign'] = $this -> getSign($return['signkey'], $data);
        $response = $this -> curlnew($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('XianYuTsSdk pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == '0'){
                $return = [
                    'result' => 'ok',
                    'orderStr' => $response['data']['sdk_content'],
                ];
                $this->ajaxReturn($return);
        }
        //返回错误
        echo $response;
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


    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        Log::record('咸鱼（ts）订单失败：'.$response.'--'.json_decode($response).'--'.json_encode($response),'ERR',true);
        $response = json_decode($response, true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XianYuTsSdk notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["out_trade_no"]); // 密钥
        /*$data = [
            'mch_id' => $response['mch_id'],
            'trade_no' => $response['trade_no'],
            'out_trade_no' => $response['out_trade_no'],
            'money' => $response['money'],
            'notify_time' => $response['notify_time'],
        ];*/

        $sign = $this -> getSign($publiKey, $response);

        //Log::record('咸鱼（ts）订单失败：'.$sign.'-'.$response['sign'],'ERR',true);
        if ($sign == $response['sign']) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["out_trade_no"]])->find();
                if(!$o){
                    Log::record('咸鱼（ts）回调失败,找不到订单：'.$response,'ERR',true);
                    exit('error:order not fount'.$response["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['money']  - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("咸鱼（ts)回调失败,金额不等：{$response['money'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error!');
                }

                $old_order = $Order->where(['upstream_order'=>$response['trade_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_trade_no"]){
                    Log::record("咸鱼（ts)回调失败,重复流水号  ：".$response.'旧订单号'.$old_order['order_id'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['trade_no'])){
                    Log::record("流水号为空  ：".$response.'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save([ 'upstream_order'=>$response['trade_no']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("SUCCESS");
            }catch (Exception $e){
                Log::record('咸鱼（ts)回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            exit('error:check sign Fail!');
        }
    }





    private function curlnew($url, $return_array){
        $notifystr = json_encode($return_array, JSON_UNESCAPED_UNICODE);

        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $notifystr);
        $contents = curl_exec($ch);
        //$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $contents;
    }

    private function getSign($secret, $array){
        ksort($array);
        foreach ($array as $k => $v) {
            if ($array[$k] == '' || $k == 'sign') {
                unset($array[$k]);//去除多余参数
            }
        }
        $query = '';
        foreach ($array as $key => $value) $query.= $key.'='.$value.'&';
        $query = rtrim($query, "&");
        $query.= $secret;
        return strtoupper(md5($query));
    }




}

