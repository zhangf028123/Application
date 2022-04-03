<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class AliHqController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'AliHq',
            'title'     => '支付宝转卡（hq）',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'merchant_sn'  => $return['mch_id'], //
            'order_sn'   => $return['orderid'],
            'amount'        => $return["amount"],
            'notify_url'     => $return['notifyurl'],
            'product_id' => $return['appid'],
            'merchant_user_id' => $return['orderid'],
            'product_name' => 'trade',
            //'key' => $return['gateway']

        ];
        $data['sign'] = $this->_createSign($data, $return["signkey"]);

        $response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('AliHq pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if($response['code'] == 1){
            header("location: {$response['data']['pay_url']}");
        }
        echo json_encode($response);

    }

    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        $response = json_decode($response, true);
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" AliHq \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，AliHq notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_trade_sn"]); // 密钥

        $data = [
            'order_sn'    => $response['order_sn'],
            'out_trade_sn'    => $response['out_trade_sn'],
            'amount'    => $response['amount'],
            'product_id'    => $response['product_id'],
            'pay_time'    => $response['pay_time'],

        ];
        $result = $this->_verify($data, $publiKey, $response['sign']);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["out_trade_sn"]])->find();
                if(!$o){
                    Log::record('支付宝转卡（hq）回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["orderid"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝转卡（hq）回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_sn']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_trade_sn"]){
                    Log::record("支付宝转卡（hq）回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["out_trade_sn"]])->save([ 'upstream_order'=>$response['order_sn']]);
                $this->EditMoney($response['out_trade_sn'], '', 0);
                exit("SUCCESS");
            }catch (Exception $e){
                Log::record('支付宝转卡（hq）回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('alihq error:check sign Fail!','ERR',true);
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

    private function _createSign($list, $keyvalue){
        $list['key'] = $keyvalue;
        $md5keysignstr = strtoupper(md5($this->_createToSignStr($list)));
        return $md5keysignstr;
    }


    private function _verify($list, $keyvalue, $pay_md5sign){
        $list['key'] = $keyvalue;
        $md5keysignstr = strtoupper(md5($this->_createToSignStr($list)));
        //$pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

    private function _createToSignStr($list){
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $md5str = rtrim($md5str, '&');
        return $md5str;
    }

}