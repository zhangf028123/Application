<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class AliAlController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'AliAl',
            'title'     => '支付宝个码(al)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'shid'  => $return['mch_id'], //
            'orderid'   => $return['orderid'],
            'amount'        => sprintf('%.2f',$return['amount']),
            'pay' => 'zfb',
            'notifyurl'     => $return['notifyurl'],

        ];
        $data['sign'] = strtolower(md5($data['amount'] .$data['notifyurl'] .$data['orderid'] .$data['pay'] .$data['shid'] . $return["signkey"]));

        $response = $this -> postnew($return['gateway'], $data);
        //$response = HttpClient::post($return['gateway'], $data);    //
        //$response = HttpClient::get($return['gateway'], $data);
        Log::record(' AliAl pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == '1') {
            header("location: {$response['data']['url']}");
        }

    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，AliAl notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["orderid"]); // 密钥

        $data = [
            'status'    => I("request.status"),
            'orderid'    => I("request.orderid"),
            'amount'    => I("request.amount"),
            'applymoney'    => I("request.applymoney"),
            'class'    => I("request.class"),
            'shid'    => I("request.shid"),
            'sign'  => I("request.sign"),
        ];

        $sign = strtolower(md5($data['shid'].$data['orderid'].$publiKey));

        if ($data['sign'] == $sign &&  $data['status'] == '1') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->find();
                if(!$o){
                    Log::record('支付宝个码al回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderid"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝个码al回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $diff1 = $response['applymoney'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝个码al回调失败,金额不等：{$response['applymoney'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error111!');
                }
                /*$old_order = $Order->where(['upstream_order'=>$response['transaction_id']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderid"]){
                    Log::record("支付宝个码回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["orderid"]])->save([ 'upstream_order'=>$response['transaction_id']]); */
                $this->EditMoney($response['orderid'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('支付宝个码al回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('Alial error:check sign Fail!','ERR',true);
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



    private function posttest($pay_notifyurl, $data) {
        $notifystr = "";
        foreach ($data as $key => $val) {
            $notifystr = $notifystr . $key . "=" . $val . "&";
        }
        $notifystr = rtrim($notifystr, '&');
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $pay_notifyurl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $notifystr);
        $contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    private function postnew($url,$parac){
        $postdata=http_build_query($parac);
        //$postdata = json_encode($parac, JSON_UNESCAPED_UNICODE);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }

}