<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class TaoBaoChController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter1 = array(
            'code' => 'TaoBaoCh', // 通道名称
            'title' => '淘宝支付宝h5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter1);

        $parameter = [
            'mch_id' => $return['mch_id'],
            'pay_way' => $return['appid'],
            'pay_type' => 'wap',
            'mch_order' => $return['orderid'],
            'member_id' => $return['mch_id'],
            'goods_price' => $return['amount'] * 100,
            'async_url' => $return['notifyurl'],
            'sync_url' => $return['callbackurl'],
            'ext_param' => 'wap',
            'user_ip' => '1.1.1.1',
            'sign_method' => 'MD5',

        ];

        ksort($parameter);
        $verifystring="";	//待签名字符串
        foreach ($parameter as $k => $vl) {
            $verifystring=$verifystring==""?$verifystring.($k."=".$vl):($verifystring."&".$k."=".$vl);
        }
        $parameter['sign'] = md5($verifystring.'&key='.$return['signkey']);
        $parameter['goods_title'] = 'goods'.$data['goods_price']*100; //商品名称
        //$parameter['api_format '] = 'json'; //需要返回json用此参数
        //$requery_url = "http://api.jiumi.mobi:7878/var/Order.html";

        //请求链接
        $url  = $return['gateway'] .'?'. $verifystring . '&sign='.$parameter['sign'];


        //返回json处理
        $return = $this -> get_curl($url);
        $cost_time = $this->msectime() - $start_time;
        Log::record('TaoBaoCh pay url='.$url.'data='.json_encode($parameter).'response='.$return."cost time={$cost_time}ms",'ERR',true);

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

        Log::record('淘宝支付宝h5订单失败：'.$response.'--'.json_decode($response).'--'.json_encode($response),'ERR',true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，TaoBaoCh notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["mch_order"]); // 密钥
        $data = [
            'mch_id' => $response['mch_id'],
            'pay_way' => $response['pay_way'],
            'mch_order' => $response['mch_order'],
            'sys_order' => $response['sys_order'],
            'member_id' => $response['member_id'],
            'goods_price' => $response['goods_price'],
            'succ_time' => $response['succ_time'],
            'code' => $response['code'],

        ];
        $verifystring="";	//待签名字符串
        foreach ($data as $k => $vl) {
            $verifystring=$verifystring==""?$verifystring.($k."=".$vl):($verifystring."&".$k."=".$vl);
        }
        $sign = md5($verifystring.'&key='.$publiKey);

        if ($sign == $response['sign'] && $response['code'] == '10001') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["mch_order"]])->find();
                if(!$o){
                    Log::record('淘宝支付宝h5回调失败,找不到订单：'.$response,'ERR',true);
                    exit('error:order not fount'.$response["mch_order"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['goods_price'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("淘宝支付宝h5回调失败,金额不等：{$response['goods_price'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error!');
                }
                $diff1 = $response['real_price'] / 100 - $pay_amount;
                if($diff1 <= -1 || $diff1 >= 1 ){ // 允许误差一块钱
                    Log::record("淘宝支付宝h5回调失败,金额不等：{$response['real_price'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error11!');
                }

                $old_order = $Order->where(['upstream_order'=>$response['sys_order']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["mch_order"]){
                    Log::record("淘宝支付宝h5回调失败,重复流水号  ：".$response.'旧订单号'.$old_order['order_id'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['sys_order'])){
                    Log::record("流水号为空  ：".$response.'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["mch_order"]])->save([ 'upstream_order'=>$response['sys_order']]);
                $this->EditMoney($response['mch_order'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('淘宝支付宝h5回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //未支付。。。
            Log::record('淘宝支付宝h5订单失败：'.$response,'ERR',true);
            exit("error:not pay");
        }
        else {
            exit('error:check sign Fail!');
        }
    }



}

