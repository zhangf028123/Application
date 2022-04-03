<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class PddJerryController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'PddJerry',
            'title'     => 'pdd(jerry)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'type'  => 'alipay', //
            'total'   => $return["amount"],
            'api_order_sn' => $return['orderid'],
            'client_id' => $return['mch_id'], // ,
            'notify_url'     => $return['notifyurl'],
            'timestamp'   => time(),

        ];
        $data['sign'] = $this ->_sign($data, $return["signkey"]);
        //$this -> setHtml($return['gateway'], $data);
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('PddJerry pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 200 ) {
                $return = [
                    'result' => 'ok',
                    'orderStr' => $response['data']['url'],
                ];
                $this->ajaxReturn($return);
        } else {
            echo json_encode($response) ;
        }

    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" PddJerry \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，PddJerry notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["api_order_sn"]); // 密钥

        $data = [
            'callbacks'    => I("request.callbacks"),
            'type'    => I("request.type"),
            'total'    => I("request.total"),
            'api_order_sn'    => I("request.api_order_sn"),
            'order_sn'    => I("request.order_sn"),
            //'timestamp'    => I("request.timestamp"),
        ];
        $result = $this->_sign($data, $publiKey);
        $sign = I("request.sign");

        if ($result == $sign && $data['callbacks'] == 'CODE_SUCCESS') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["api_order_sn"]])->find();
                if(!$o){
                    Log::record('pdd(jerry)回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["api_order_sn"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['total'] - $pay_amount;
                if($diff <= -2 || $diff >= 2 ){ // 允许误差二块钱 特殊处理!
                    Log::record("pdd(jerry)回调失败,金额不等：{$response['total'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_sn']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["api_order_sn"]){
                    Log::record("pdd(jerry)回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["api_order_sn"]])->save([ 'upstream_order'=>$response['order_sn']]);
                $this->EditMoney($response['api_order_sn'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('pdd(jerry)回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('pddjerry error:check sign Fail!','ERR',true);
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



    private function _sign($params = [], $secret = '')
    {
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            $str = $str . $k . $v;
        }
        $str = $secret . $str . $secret;
        return strtoupper(md5($str));
    }


}