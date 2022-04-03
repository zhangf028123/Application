<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class TaoBaoMfController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter1 = array(
            'code' => 'TaoBaoMf', // 通道名称
            'title' => '淘宝红包（mf）h5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter1);

        $app_id        = $return['mch_id'];
        $amount        = $return['amount'] * 100;
        $order_no      = $return['orderid'];
        $device        = 'alipay_wp';
        $app_secret    = $return['signkey'];
        $requestParams = compact('app_id', 'amount', 'order_no', 'device', 'app_secret');
        $notify_url    = $return['notifyurl'];
        $return_url    = $return['callbackurl'];
        //生成签名
        $sign = md5(http_build_query($requestParams) . "&notify_url={$notify_url}");
        //构造请求链接
        $requestParams['notify_url'] = $notify_url;
        $requestParams['return_url'] = $return_url;
        $requestParams['sign']       = $sign;
        unset($requestParams['app_secret']);
        $requestParmsUrl = http_build_query($requestParams);
        //$url             = 'http://www.1pluspay.com/trade/pay?';//正式请求地址

        $url     = $return['gateway'] .'?'. $requestParmsUrl;

        //返回json处理
        $return = $this -> get_curl($url);
        $cost_time = $this->msectime() - $start_time;
        Log::record('TaoBaoMf pay url='.$url.'data='.json_encode($requestParams).'response='.$return."cost time={$cost_time}ms",'ERR',true);

        //$return = json_decode($return,TRUE);
        echo $return;

    }


    private  function get_curl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30 );
        $result = curl_exec($ch);
        if(curl_errno($ch)){
            return curl_error($ch);
        }
        curl_close($ch);
        unset ( $ch );
        return $result;
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
        $response  = $_REQUEST;

        Log::record('淘宝红包（mf）订单失败：'.$response.'--'.json_decode($response).'--'.json_encode($response),'ERR',true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，TaoBaoCh notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["order_no"]); // 密钥
        $data = [
            'status' => $response['status'],
            'amount' => $response['amount'],
            'order_no' => $response['order_no'],
            'order_status' => $response['order_status'],
            'pay_time' => $response['pay_time'],
            'app_secret'   => $publiKey,

        ];

        $sign      = md5(http_build_query($data));

        if ($sign == $response['sign'] && $response['order_status'] == 'success') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["order_no"]])->find();
                if(!$o){
                    Log::record('淘宝红包（mf）回调失败,找不到订单：'.$response,'ERR',true);
                    exit('error:order not fount'.$response["order_no"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['amount'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("淘宝红包（mf）回调失败,金额不等：{$response['amount'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error!');
                }

                /*$old_order = $Order->where(['upstream_order'=>$response['sys_order']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["mch_order"]){
                    Log::record("淘宝红包（mf）回调失败,重复流水号  ：".$response.'旧订单号'.$old_order['order_id'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['sys_order'])){
                    Log::record("流水号为空  ：".$response.'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["mch_order"]])->save([ 'upstream_order'=>$response['sys_order']]);
                */
                $this->EditMoney($response['order_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('淘宝红包（mf）回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //未支付。。。
            Log::record('淘宝红包（mf）订单失败：'.$response,'ERR',true);
            exit("error:not pay");
        }
        else {
            exit('error:check sign Fail!');
        }
    }



}

