<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class PddXFController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'PddXF',
            'title'     => '拼多多(xf)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'pay_memberid'  => $return['mch_id'], //
            'pay_orderid'   => $return['orderid'],
            'pay_applydate' => I("request.pay_applydate"),
            'pay_bankcode' => $return['appid'], // ,
            'pay_notifyurl'     => $return['notifyurl'],
            'pay_callbackurl'   => $return['callbackurl'],
            'pay_amount'        => $return["amount"],
        ];
        $data['pay_md5sign'] = $this->createSign($return["signkey"], $data);
        // 不用签名的参数
        /*
        $data['pay_productname']    = I("request.pay_productname");
        $data['pay_productnum']     = I("request.pay_productnum");
        $data['pay_productdesc']    = I("request.pay_productdesc");
        $data['pay_producturl']     = I("request.pay_productnum");
        $data['pay_attach']         = I("request.pay_attach");
        */

        //$response = HttpClient::post($return['gateway'], $data);
        $response = $this -> post($return['gateway'], $data);
        Log::record('PddXF pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 0 ) {
            header("location: {$response['payurl']}");
        } else {
            echo $response;
        }

    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" PddXF \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，PddXF notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["orderid"]); // 密钥

        $data = [
            'memberid'    => I("request.memberid"),
            'orderid'    => I("request.orderid"),
            'amount'    => I("request.amount"),
            'datetime'    => I("request.datetime"),
            'transaction_id'    => I("request.transaction_id"),
            'returncode'    => I("request.returncode"),
        ];
        $result = $this->_verify($data, $publiKey);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->find();
                if(!$o){
                    Log::record('拼多多xf回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderid"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("拼多多xf回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['transaction_id']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderid"]){
                    Log::record("拼多多xf回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["orderid"]])->save([ 'upstream_order'=>$response['transaction_id']]);
                $this->EditMoney($response['orderid'], '', 0);
                exit("OK");
            }catch (Exception $e){
                Log::record('拼多多xf回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('PddXF error:check sign Fail!','ERR',true);
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