<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class AliCodeChController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'AliCodeCh', // 通道名称
            'title' => '支付宝个码（ch）',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $now_time=time();
        $p_data=array(
            'time'=>$now_time,
            'mch_id'=>$return['mch_id'],
            'ptype'=>$return['appid'],
            'order_sn'=>$return['orderid'],
            'money'=>$return['amount'],
            'goods_desc'=>'buy',
            'client_ip'=>'127.0.0.1',
            'format'=>'page',
            'notify_url'=>$return['notifyurl'],
        );
        ksort($p_data);
        $sign_str='';
        foreach($p_data as $pk=>$pv){
            $sign_str.="{$pk}={$pv}&";
        }
        $sign_str.="key={$return['signkey']}";
        $p_data['sign']=md5($sign_str);

        //$url=$return['gateway'];
        $url = 'https://nqzhifu.cn/?c=Pay&';
        $url.=http_build_query($p_data);
        \Think\Log::record('testing...'.$url, 'ERR', true);
        header("Location:{$url}");


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
        Log::record('支付宝个码（ch）订单失败：'.$response.'--'.json_decode($response).'--'.json_encode($response),'ERR',true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，AliCodech notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["sh_order"]); // 密钥

        $data=[
            'pt_order'=>$response['pt_order'],
            'sh_order'=>$response['sh_order'],
            'money'=>$response['money'],
            'status'=>$response['status'],
            'time'=>$response['time']
        ];
        ksort($data);
        $str='';
        foreach($data as $pk=>$pv){
            $str.="{$pk}={$pv}&";
        }
        $str.="key={$publiKey}";
        $sign=md5($str);



        if ($sign == $response['sign'] && $response['status'] == 'success') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["sh_order"]])->find();
                if(!$o){
                    Log::record('支付宝个码（ch）回调失败,找不到订单：'.$response,'ERR',true);
                    exit('error:order not fount'.$response["sh_order"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['money']  - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝个码（ch）回调失败,金额不等：{$response['amount'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error!');
                }

                $old_order = $Order->where(['upstream_order'=>$response['pt_order']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["sh_order"]){
                    Log::record("支付宝个码（ch）回调失败,重复流水号  ：".$response.'旧订单号'.$old_order['order_id'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['pt_order'])){
                    Log::record("流水号为空  ：".$response.'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["sh_order"]])->save([ 'upstream_order'=>$response['pt_order']]);
                $this->EditMoney($response['sh_order'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('支付宝个码（ch）回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //未支付。。。
            Log::record('支付宝个码（ch）订单失败：'.$response,'ERR',true);
            exit("error:not pay");
        }
        else {
            exit('error:check sign Fail!');
        }
    }




}

