<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class AliChController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'AliCh',
            'title'     => '支付宝H5(ch)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $token = $return["signkey"];
        $postData['merchant_id'] = $return['mch_id'];
        $postData['bank_code'] = $return['appid'];
        $postData['amount'] = $return["amount"]*100;
        $postData['notify_url'] = $return['notifyurl'];
        $postData['return_url'] = $return['callbackurl'];
        $postData['order_id'] = $return['orderid'];
        $postData['order_name'] = 'trade';
        $postData['sign'] = $this ->generateSign($postData,$token);

        $response = $this -> request_post($return['gateway'], json_encode($postData));
        //Log::record('Alich pay url='.$return['gateway'].',data='.json_encode($postData).',response='.json_encode($response),'ERR',true);

        $response = json_decode($response['response'], true);
        Log::record('Alich pay url='.$return['gateway'].',data='.json_encode($postData).',response='.json_encode($response),'ERR',true);

        if ($response['code'] == 200 ) {
            header("location: {$response['data']['pay_url']}");
        } else {
            echo $response;
        }
    }

    public function request_post($url = '', $post_data,$content_type='application/json')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        echo $post_data;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:'.$content_type.'; charset=utf-8',
                'Content-Length: ' . strlen($post_data)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['code' => $httpCode, 'response' => $response];
    }

    //签钥辅助函数
    public function generateSign($signData, $token)
    {
        $str = "";
        ksort($signData);
        foreach ($signData as $key => $val) {
            if ($val != '') {
                if ($key != 'sign') {
                    $str = $str . $key . "=" . $val . '&';
                }
            }
        }
        return md5($str . 'key=' . $token);
    }


    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" Alich \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Alich notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $merchant_id = $_POST['merchant_id'];
        $order_id = $_POST['order_id'];
        $out_trade_no = $_POST['out_trade_no'];
        $amount = $_POST['amount'];
        $pay_status = $_POST['pay_status'];
        $bank_code = $_POST['bank_code'];
        $sign = $_POST['sign'];

        $publiKey = getKey($order_id); // 密钥

        $reqData=[
            'merchant_id' => $merchant_id,
            'order_id'=> $order_id,
            'amount' => $amount,
            'out_trade_no' => $out_trade_no,
            'pay_status' => $pay_status,
            'bank_code' => $bank_code,

        ];
        $token = $publiKey;
        $verifySign = $this -> generateSign($reqData,$token);

        if ($pay_status == 1 && $verifySign == $sign) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $order_id])->find();
                if(!$o){
                    Log::record('alich回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$order_id );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $amount / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("alich回调失败,金额不等：{$amount } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$out_trade_no])->find();
                if( $old_order && $old_order['pay_orderid'] != $order_id){
                    Log::record("alich回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $order_id])->save([ 'upstream_order'=>$out_trade_no]);
                $this->EditMoney($order_id, '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('alich回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('alich error:check sign Fail!','ERR',true);
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