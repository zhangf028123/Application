<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class KS3PayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'KS3Pay',
            'title' => '上游Wap',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            "merchant_no" => $return['mch_id'], //商户号
            "out_order_no" => $return['orderid'], //商户订单号
            "amount" => $return["amount"],
            "pay_type" => $return['appid'],//是	alipay或wechat
            "notify_url" => $return['notifyurl'], //异步回调 服务器回调的通知地址
        ];
        $data['sign'] = $this->createSign_1($return["signkey"], $data);
        $response =HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response,true);
        Log::record('KS3Pay pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        if (empty($response)) {
            Log::record('KS3Pay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '1'){
            header("location: {$response['data']['pay_url']}");
            exit();
        }
        echo $response;
    }

    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" KS3Pay \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，KS3Pay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $data = [
            "order_no" => $_REQUEST["order_no"], // 订单号
            "merchant_no" =>  $_REQUEST["merchant_no"], // 商户号
            "out_order_no" =>  $_REQUEST["out_order_no"], // 商户订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "pay_type" =>  $_REQUEST["pay_type"], // 交易类型
            "status" =>  $_REQUEST["status"], //交易结果 1（成功）
        ];
//回调签名方式
//sign = md5(order_no+merchant_no+out_order_no+amount+pay_type+status+商户秘钥);
        $publiKey = getKey($response["order_no"]); // 密钥
        $result = $this->_verify($data, $publiKey);
        if ($result) {
            $orderid=$_REQUEST["order_no"];//订单号
            $out_order_no=$_REQUEST["out_order_no"];// 商户订单号
            $amount= $_REQUEST["amount"]; // 交易金额
            if ($_REQUEST["status"] == "1"){
                try {
                    $Order = M("Order");
                    $o = $Order->where(['pay_orderid' => $orderid])->find();
                    if (!$o) {
                        Log::record('上游wap回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                        exit('error:order not fount' . $orderid);
                    }

                    $pay_amount = $o['pay_amount'];
                    $diff = $amount - $pay_amount;
                    if ($diff <= -1 || $diff >= 1) { // 允许误差一块钱
                        Log::record("上游wap回调失败,金额不等：{$amount } != {$pay_amount}," . json_encode($response), 'ERR', true);
                        exit('error: amount error!');
                    }
                    $old_order = $Order->where(['upstream_order'=>$out_order_no])->find();
                    if( $old_order && $old_order['pay_orderid'] != $orderid){
                        Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    }
                    $Order->where(['pay_orderid' => $orderid])->save([ 'upstream_order'=>$out_order_no]);
                    $this->EditMoney($orderid, '', 0);
                    exit("success");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            }else{
                Log::record('KS3Pay error:order  fail !', 'ERR', true);
                exit('error:order fail!');
            }
        } else {
            Log::record('KS3Pay error:check sign Fail!', 'ERR', true);
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
    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     * 支付请求的签名方式
    sign=md5(merchant_no+out_order_no+amount+pay_type+notify_url+商户秘钥)
     */
    private function createSign_1($Md5key, $list)
    {
        $temp=$this->createToSignStr_1($Md5key, $list);
        $sign = md5($temp);
        Log::record('createToSignStr ===== ：'.$temp.' sign= '.$sign,'ERR',true);
        return $sign;
    }
    function createToSignStr_1($Md5key, $list){
        $md5str = "";
        foreach ($list as $key => $val) {
            $md5str = $md5str . $val ;
        }
        return $md5str .$Md5key;
    }

    private function _verify($requestarray, $md5key){
        $md5keysignstr = $this->createSign_1($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        Log::record('createToSignStr ===== md5keysignstr  ：'.$md5keysignstr,'ERR',true);
        Log::record('createToSignStr ===== sign  ：'.$pay_md5sign,'ERR',true);
        return $md5keysignstr == $pay_md5sign;
    }







}