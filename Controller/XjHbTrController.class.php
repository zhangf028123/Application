<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XjHbTrController extends PayController
{
    public function Pay($array){
        //$start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XjHbTr',
            'title'     => '现金红包tr',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'customer_no'  => $return['mch_id'], //
            'customer_order'   => $return['orderid'],
            'amount'        => $return["amount"],
            'produce_date' => date("Y-m-d H:i:s"),
            'bank_code' => $return['appid'], // ,
            'notify_url'     => $return['notifyurl'],
            'callback_url'   => $return['callbackurl'],

        ];

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $return["signkey"]));
        $data['sign_md5'] = $sign;
        Log::record('XjHbTr pay url='.$return['gateway'].'data='.json_encode($data), 'ERR', true);
        $this->setHtml($return['gateway'], $data);

        /*$response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('XjHbTr pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        //$responsehtml = $response;
        $response = json_decode($response, true);*/


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XjHbTr \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XjHbTr notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $md5key = getKey($response["customer_order"]); // 密钥

        $returnArray = array( // 返回字段
            "customer_no"    =>  $_REQUEST["customer_no"],    // 商户ID
            "customer_order" =>  $_REQUEST["customer_order"], // 订单号
            "amount"         =>  $_REQUEST["amount"],         // 交易金额
            "trading_time"   =>  $_REQUEST["trading_time"],   // 交易时间
            "trading_num"    =>  $_REQUEST["trading_num"],    // 支付流水号
            "trading_code"   =>  $_REQUEST["trading_code"],   //交易状态
        );
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $md5key));

        if ($sign == $_REQUEST["sign_md5"] && $_REQUEST["trading_code"] == "00") {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["customer_order"]])->find();
                if(!$o){
                    Log::record('现金红包tr回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["customer_order"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("现金红包tr回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['trading_num']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["customer_order"]){
                    Log::record("现金红包tr回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["customer_order"]])->save([ 'upstream_order'=>$response['trading_num']]);
                $this->EditMoney($response['customer_order'], '', 0);
                exit("ok");
            }catch (Exception $e){
                Log::record('现金红包tr回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('XjHbTr error:check sign Fail!','ERR',true);
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