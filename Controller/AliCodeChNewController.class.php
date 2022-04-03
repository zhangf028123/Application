<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class AliCodeChNewController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'AliCodeChNew',
            'title'     => '支付宝个码（ch）',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'merchantNum'  => $return['mch_id'], //
            'orderNo'   => $return['orderid'],
            'amount'        => $return["amount"],
            'notifyUrl'     => $return['notifyurl'],
            'returnUrl'   => $return['callbackurl'],
            'payType'  => 'alipay',
            'attch' => 'trade',

        ];

        $sign = md5($data['merchantNum'].$data['orderNo'].strval($data['amount']).$data['notifyUrl'].$return['signkey']);
        $data['sign'] = $sign;


        $response = HttpClient::post($return['gateway'], $data);
        Log::record('AliCodeChNew pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 200) {
            header("location: {$response['data']['payUrl']}");

        }
        echo $response;

        //$this -> setHtml($return['gateway'], $data);

    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" AliCodeChNew \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，AliCodeChNew notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["orderNo"]); // 密钥

        $data = [
            'merchantNum'    => I("request.merchantNum"),
            'orderNo'    => I("request.orderNo"),
            'platformOrderNo'    => I("request.platformOrderNo"),
            'amount'    => I("request.amount"),
            'attch'    => I("request.attch"),
            'state'    => I("request.state"),
            'payTime'    => I("request.payTime"),
            'actualPayAmount'    => I("request.actualPayAmount"),


        ];
        $result = $this->_verify($data, $publiKey);

        if ($result && $data['state'] == 1) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["orderNo"]])->find();
                if(!$o){
                    Log::record('AliCodeChNew回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderNo"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差二块钱 特殊处理!
                    Log::record("AliCodeChNew回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $diff = $response['actualPayAmount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差二块钱 特殊处理!
                    Log::record("AliCodeChNew回调失败111,金额不等：{$response['actualPayAmount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount1111 error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['platformOrderNo']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderNo"]){
                    Log::record("AliCodeChNew回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["orderNo"]])->save([ 'upstream_order'=>$response['platformOrderNo']]);
                $this->EditMoney($response['orderNo'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('AliCodeChNew回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('AliCodeChNew error:check sign Fail!','ERR',true);
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

        $md5keysignstr = md5($requestarray['state'].$requestarray['merchantNum'].$requestarray['orderNo'].strval($requestarray['amount']).$md5key);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}