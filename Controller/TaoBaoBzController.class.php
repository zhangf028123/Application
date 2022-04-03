<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class TaoBaoBzController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'TaoBaoBz',
            'title'     => '淘宝红包(Bz)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'version'  => '2',
            'merchant_number'  => $return['mch_id'],
            'cash'        => sprintf('%.2f', $return["amount"]),
            'server_url'     => $return['notifyurl'],
            'brower_url'   => $return['callbackurl'],
            'order_id'   => $return['orderid'],
            'order_time' => time(),
            'pay_type' => $return['appid'],

        ];
        $data['sign'] = $this->_createSign($return["signkey"], $data);

        $this->setHtml($return['gateway'], $data);

        //$response = $this -> post($return['gateway'], $data);
        /*Log::record('TaoBaoBz pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 0 ) {
            header("location: {$response['payurl']}");
        } else {
            echo $response;
        }*/



    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" TaoBaoBz \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，TaoBaoBz notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["order_id"]); // 密钥

        $data = [
            'merchant_number'    => I("request.merchant_number"),
            'order_id'    => I("request.order_id"),
            'cash'    => I("request.cash"),
            'order_time'    => I("request.order_time"),
            'status'    => I("request.status"),
            'notify_type'    => I("request.notify_type"),
        ];
        $result = $this->_verify($data, $publiKey);

        if ($result && $data['status'] == '2') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["order_id"]])->find();
                if(!$o){
                    Log::record('淘宝红包bz回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["order_id"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['cash'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("淘宝红包bz回调失败,金额不等：{$response['cash'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                /*$old_order = $Order->where(['upstream_order'=>$response['transaction_id']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["order_id"]){
                    Log::record("淘宝红包xf回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }*/
                //$Order->where(['pay_orderid' => $response["order_id"]])->save([ 'upstream_order'=>$response['transaction_id']]);
                $this->EditMoney($response['order_id'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('淘宝红包bz回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('TaoBaobz error:check sign Fail!','ERR',true);
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
        $md5keysignstr = $this->_createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

    protected function _createSign($Md5key, $list)
    {
        $sign = strtolower(md5($this->createToSignStr($Md5key, $list)));
        return $sign;
    }

}