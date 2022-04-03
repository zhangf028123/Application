<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class NbzfController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $ddh = 'E'.date("YmdHis").rand(100000,999999);
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $parameter = [
            'code' => 'Nbzf',
            'title' => '上游Wap',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => $ddh, //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            "pay_memberid" => $return['mch_id'], //商户号
            "pay_orderid" => $return['orderid'], //商户订单号
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $return['appid'],
            "pay_amount" => $return["amount"], //支付金额 单位元
            "pay_notifyurl" => $return['notifyurl'], //异步回调 , 支付结果以异步为准
            "pay_callbackurl" => $return['callbackurl'],//同步回调
        ];
        $data['pay_md5sign'] = $this->createSign_1($return["signkey"], $data);
        $data['pay_attach'] ="商品";
        $data['pay_productname'] = "VIP基础服务";

        $response =$this->curl_post($return['gateway'], $data);
//        $response =HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
//        $st=$response->getBody();
//        $response = json_decode($response,true);
        Log::record('Nbzf pay url=' . $return['gateway'] . ' data=' . json_encode($data) . ' response=' .$response. "cost time={$cost_time}ms", 'ERR', true);

        if (empty($response)) {
            Log::record('Nbzf  $response is empty ', 'ERR', true);
            exit();
        }
        echo $response;

//        if ($response['status'] == 1) {
//            header('Location:' . $response["payurl"]); //转入支付页面
//        } else {
//            echo $response;
//            exit();
//        }
    }

    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" Nbzf \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，Nbzf notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $data = [
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"],
        ];
        $publiKey = $this->getKey($response["orderid"]); // 密钥
        $result = $this->_verify($data, $publiKey);

        if ($result) {
            if ($_REQUEST["returncode"] == "00"){
                try {
                    $Order = M("Order");
                    $o = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->find();
                    if (!$o) {
                        Log::record('上游wap回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                        exit('error:order not fount' . $_REQUEST["fxddh"]);
                    }

                    $pay_amount = $o['pay_amount'];
                    $diff = $response['amount'] - $pay_amount;
                    if ($diff <= -1 || $diff >= 1) { // 允许误差一块钱
                        Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount}," . json_encode($response), 'ERR', true);
                        exit('error: amount error!');
                    }
                    $old_order = $Order->where(['upstream_order'=>$response['transaction_id']])->find();
                    if( $old_order && $old_order['pay_orderid'] != $response["orderid"]){
                        Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    }
                    $Order->where(['pay_orderid' => $response["orderid"]])->save([ 'upstream_order'=>$response['transaction_id']]);
                    $this->EditMoney($response['orderid'], '', 0);
                    exit("OK");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            }else{
                Log::record('Nbzf error:order  fail !', 'ERR', true);
                exit('error:order fail!');
            }
        } else {
            Log::record('Nbzf error:check sign Fail!', 'ERR', true);
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

    function curl_post($api_url, $post_string){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $headers = array('Content-Type: application/x-www-form-urlencoded');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, "CURL_HTTP_VERSION_1_1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_string));


        $reponse = curl_exec($ch);
        curl_close($ch);

        return $reponse;
    }
    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
    private function createSign_1($Md5key, $list)
    {
        $temp=$this->createToSignStr_1($Md5key, $list);
        $sign = strtoupper(md5($temp));
        Log::record('createToSignStr ===== ：'.$temp.' sign= '.$sign,'ERR',true);
        return $sign;
    }
    function createToSignStr_1($Md5key, $list){
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=". $val . "&";
            }
        }
        return $md5str . 'key='.$Md5key;
    }

    private function _verify($requestarray, $md5key){
        $md5keysignstr = $this->createSign_1($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        Log::record('createToSignStr ===== fxsign ：'.$pay_md5sign,'ERR',true);
        return $md5keysignstr == $pay_md5sign;
    }







}