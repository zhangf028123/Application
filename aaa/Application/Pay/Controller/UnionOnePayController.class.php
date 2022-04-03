<?php

namespace Pay\Controller;

use Org\SignatureUtil;
use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class UnionOnePayController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'UnionOnePay', // 通道名称
            'title' => '银联onepay',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $data = [
            'version' => '1.0',
            'inputCharset' => 'UTF-8',
            'signType' => 'RSA',
            'returnUrl' => $return['callbackurl'],
            'notifyUrl' => $return['notifyurl'],
            'deviceType' => 'H5',
            'payType' => 'NC',
            'merchantId' => $return['mch_id'],
            'merchantTradeId' => $return['orderid'],
            'currency' => 'CNY',
            'amountFee' => sprintf("%.2f",$return["amount"]),
            'goodsTitle' => 'ceshi',
            'issuingBank' => 'UNIONPAY',
            'cardType' => 'D',

        ];
        $data['sign'] = $this -> getPaySign($data);

        $response = $this->post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('UnionOnePay pay url=' . $return['gateway'] . 'data=' . json_encode($data) . 'response=' . $response . "cost time={$cost_time}ms", 'ERR', true);
        echo $response;

    }

    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["merchantTradeId"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["merchantTradeId"], '', 1);
        } else {
            exit("error");
        }
    }



    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，UnionOnePay notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $sign = $this -> getNotifySign($response);


        if ($sign == $response['sign'] && $response['tradeStatus'] == 'PS_PAYMENT_SUCCESS') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["merchantTradeId"]])->find();
                if(!$o){
                    Log::record('银联onepay回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["merchantTradeId"] );
                }

                $pay_amount = $o['amountFee'];
                $diff = $response['amountFee'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 || $response['currency'] != 'CNY'){ // 允许误差一块钱
                    Log::record("银联onepay回调失败,金额不等：{$response['amountFee'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }

                $old_order = $Order->where(['upstream_order'=>$response['pwTradeId']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["merchantTradeId"]){
                    Log::record("银联onepay回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['pwTradeId'])){
                    Log::record("流水号为空  ：".json_encode($response).'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["merchantTradeId"]])->save([ 'upstream_order'=>$response['pwTradeId']]);
                $this->EditMoney($response['mercOrderId'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('银联onepay失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //支付失败。。。
            Log::record('银联onepay失败订单失败：'.json_encode($response),'ERR',true);
            exit("error: pay fail");
        }
        else {
            exit('error:check sign Fail!');
        }
    }






    private function getPaySign($data) {
        $private_key = 'MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAIYBXmODnzkS8buLtoMEoMPdLyTQtBRUbF82G7UG879Xs1TJEk0LirZfolBV2i16p4tWF65G/kpOgBqBmxoEUaZVOh2CvXcGpelQkJmNZ/UrHSRef1LDsCgWRHFC6eCwaWJWZIUYO6X1WXTkWHzjZRe3OJLqMkjVO5NosFDNHxeTAgMBAAECgYATCVK9VE9kLjrE574PsrKb4Gn4EuXiFXQnumoJN2mc/vpsyvuckk0sRz2pp+iMmWX/t0U57r/lEm3EVjEQaximD/fYgMxM4kDgUfIpeCpx9OtbXYosnAMPm2ckOoXW4s8Vd4tN4VyCirKVGvtQFf8IbziF2v3Bd7u+NA6bIAWXwQJBAPDBD/McX5TH/Hn51YnXyJ8EAivcYksmPr385UfmGzkog0WmcOL57yP+ZLPGZzz71wtLYNjrgyrFVdCNbrO6C2MCQQCOfcUBZBx9M8+dDSFUrr9mgC+JbkAZE70PuJImLwNtTNpDezimsyu0Az4DIjI2FNLzSIMWgvqHnfLOnRqPuTIRAkAgyGk/lXF+dOzwPxDQwE1VOdqB1nSb/w00Gaeu7qpuUhHt/ggJIDdsE0vrHu0X5MMXiqAZaZhmzpAs4dVdK8w5AkBYrfd0xSxh02Prdyd+P39JOI/dNStZMAqjBRiYAPxeAs133/FC9hFF6Bqo8phTRiR/WmqTERMAYhGh+u5z8isxAkA297wyB53r8bE6pLRo4l2n7PUsl79XNv10gOR7+9tUQN/EThQDrBGWjaJkjV9xWZs89lCIwQE5lzj0k9bTzNSR';
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            if (!empty($value) && ($key != 'sign') && ($key != 'signType') ) {
                $str .= $key . '=' . utf8_encode($value) . '&';
            }
        }
        $str = rtrim($str, "&");
        $sign = $this -> rsakey($str, $private_key);
        $rsasign = $this -> strToHex($sign);
        return $rsasign;
    }

    private function rsakey($data, $privateKey)
    {
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $key = openssl_get_privatekey($privateKey);
        openssl_sign($data, $signature, $key);
        openssl_free_key($key);
        $sign = base64_encode($signature);
        return $sign;
    }

    private function getNotifySign($data) {
        $pubKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCgKT9viI9wRwyiUQ5dduOXybrHv/Z+z4uY9pYa4KYjbGPUZ5DgF+A5Mr0TwcYu8YP6e6KaYkBRn35/A5y94MVA0MFHFPDY5nitjCC/fVPE+wMl7ssaU9PKbQ5aUf1FmK+Kc+fuCJ2NKswaIZXukV7cFFaGqsTQR+0Lem/doM7fdwIDAQAB';
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            if (!empty($value) && ($key != 'sign') && ($key != 'signType') ) {
                $str .= $key . '=' . utf8_encode($value) . '&';
            }
        }
        $str = rtrim($str, "&");
        $sign = $this -> hexToStr($str);
        return $this -> opverify($sign, $data['sign'], $pubKey);
    }

    private function opverify($data, $sign, $pubKey)
    {
        $sign = base64_decode($sign);
        $pubKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $key = openssl_pkey_get_public($pubKey);
        $result = openssl_verify($data, $sign, $key, OPENSSL_ALGO_SHA1) === 1;
        return $result;
    }

    private function strToHex($str){
        $hex="";
        for($i=0;$i<strlen($str);$i++)
            $hex.=dechex(ord($str[$i]));
        return $hex;
    }

    private function hexToStr($hex){
        $str="";
        for($i=0;$i<strlen($hex)-1;$i+=2)
            $str.=chr(hexdec($hex[$i].$hex[$i+1]));
        return $str;
    }


}