<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class HuaFeiCjController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'HuaFeiCj',
            'title'     => '微信话费(cj)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'pid'  => $return['mch_id'], //
            'cid'   => $return['appid'],
            'type' => '',
            'oid'  => $return['orderid'],
            'uid'  => $return['mch_id'],
            'amount'        => $return["amount"] * 100,
            'burl'   => $return['callbackurl'],
            'nurl'     => $return['notifyurl'],
            'eparam' => '',
            'ip' => '1.1.1.1',
            'stype' => 'MD5',


        ];

        $data['sign'] = $this-> _createSign($return["signkey"], $data);

        $this -> setHtml($return['gateway'], $data);

        //$response = HttpClient::post($return['gateway'], $data);
        /*$response = $this -> post($return['gateway'], $data);
        Log::record('HuaFeiCj pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 101 ) {
            header("location: {$response['data']['payurl']}");
        } else {
            echo $response;
        }*/

    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" HuaFeiCj \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，HuaFeiCj notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["oid"]); // 密钥

        $data = [
            'pid'    => I("request.pid"),
            'cid'    => I("request.cid"),
            'oid'    => I("request.oid"),
            'sid'    => I("request.sid"),
            'uid'    => I("request.uid"),
            'amount'    => I("request.amount"),
     //       'ramount'    => I("request.ramount"),
            'stime'    => I("request.stime"),
            'code'    => I("request.code"),
         //   'key'  => $publiKey,

        ];
        $result = $this->_verify($data, $publiKey);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["oid"]])->find();
                if(!$o){
                    Log::record('微信话费(cj)回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["oid"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("微信话费(cj)回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }

                $diff = $response['ramount'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("微信话费(cj)回调失败,金额不等：{$response['ramount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['sid']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["oid"]){
                    Log::record("微信话费(cj)回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["oid"]])->save([ 'upstream_order'=>$response['sid']]);
                $this->EditMoney($response['oid'], '', 0);
                exit("Success");
            }catch (Exception $e){
                Log::record('微信话费(cj)回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('HuaFeiCj error:check sign Fail!','ERR',true);
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

    private function _verify($requestarray, $Md5key){
        $md5str = "";
        foreach ($requestarray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5keysignstr1 =  $md5str . "key=" . $Md5key;
        $md5keysignstr =  md5($md5keysignstr1);
        //$md5keysignstr =  md5(http_build_query($requestarray));
     //   \Think\Log::record('testing...'.$md5keysignstr1.'----'.$md5keysignstr, 'ERR', true);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

    private function _createSign($Md5key, $list)
    {
        $sign = trim(strtolower(md5($this->_createToSignStr($Md5key, $list))));
        return $sign;
    }

    private function _createToSignStr($Md5key, $list){
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        return $md5str . "key=" . $Md5key;
    }


}