<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class UpPddWuXin2Controller extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'UpPddWuXin2',
            'title'     => '上游Wap',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'merchant_no'  => $return['mch_id'], //
            'business_no'   => $return['orderid'],
            'amount'        => $return["amount"],
            'pay_type' => $return['appid'], // ,
            'notify_url'     => $return['notifyurl'],
        ];
        $sign = $this->createSign($return["signkey"], $data);
        $content = base64_encode(json_encode($data));

        $senddata =  ['sign' => $sign,
            'context' => $content];

        $response = HttpClient::post($return['gateway'], $senddata);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('UpPddWap pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);

        if(isset($response['data']) && $response['status'] == 200){
            header("location: {$response['data']}");
        }
        echo json_encode($response);


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" UpPddWap \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，UpPddWap notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $response = base64_decode($_REQUEST['context']);
        $response = json_decode($response, true);




        $publiKey = getKey($response["business_no"]); // 密钥

        $data = [
            'merchant_no'    => $response['merchant_no'],
            'business_no'    => $response['business_no'],
            'order_no'    => $response['order_no'],
            'order_status'    => $response['order_status'],
            'pay_type'    => $response['pay_type'],
            'amount'    => $response['amount'],
            'real_amount'    => $response['real_amount'],

        ];
        $result = $this->_verify($data, $publiKey);

        if ($result && $response['order_status'] == 2) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["business_no"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["business_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["business_no"]){
                    Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["business_no"]])->save([ 'upstream_order'=>$response['order_no']]);
                $this->EditMoney($response['business_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('上游wap回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('UpPdd error:check sign Fail!','ERR',true);
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

    private function _verify($requestarray, $md5key){
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}