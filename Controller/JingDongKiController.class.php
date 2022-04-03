<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class JingDongKiController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'JingDongKi',
            'title'     => '京东e卡',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        Log::record('JingDongKiController  ==== parameter    ' . json_encode($parameter) , 'ERR', true);
        $return = $this->orderadd($parameter);

        $data = [
            'appid'  => $return['mch_id'], //
            'methond' => 'submitorder', // ,
            'orderid'   => $return['orderid'],
            'channel_code' => $return['appid'], // ,
            'amount'        => $return["amount"],
            'notifyurl'     => $return['notifyurl'],

        ];
        $data['sign'] = $this->createSign($return["signkey"], $data);



        $response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('JingDongKi pay url='.$return['gateway'].'data orderid='.$data['orderid']." time= ".date('Y-m-d h:i:s', time())." cost time={$cost_time}ms",'ERR',true);
//        Log::record('JingDongKi pay url='.$return['gateway'].'data='.json_encode($data).'   response= '.$response. "cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 1){
            Log::record('JingDongKi $response url='.$response['data']['url']. " cost time={$cost_time}ms",'ERR',true);
            header("location: {$response['data']['url']}");
            exit();
        }
        echo json_encode($response);


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" JingDongKi \$response=".json_encode($response)." time= ".date('Y-m-d h:i:s', time()),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，JingDongKi notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["m_orderid"]); // 密钥
        $data = [
            'orderid'    => I("request.orderid"),
            'm_orderid'    => I("request.m_orderid"),
            'payamount'    => I("request.payamount"),
        ];
        $result = $this->_verify($data, $publiKey);
        Log::record('JingDongKi回调 m_orderid=：'.$data['m_orderid']." reulst=  ". $this->_bool($result)." time= ".date('Y-m-d h:i:s', time()),'ERR',true);
        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["m_orderid"]])->find();
                if(!$o){
                    Log::record('JingDongKi回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["m_orderid"] );
                }
                $pay_amount = $o['pay_amount'] ;
                $diff = $response['payamount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("JingDongKi回调失败,金额不等：{$response['payamount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['orderid']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["m_orderid"]){
                    Log::record("JingDongKi回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["m_orderid"]])->save([ 'upstream_order'=>$response['orderid']]);
                $this->EditMoney($response['m_orderid'], '', 0);
                Log::record('JingDongKi回调end m_orderid  =：'.$data['m_orderid']." reulst=  ". $this->_bool($result)." time= ".date('Y-m-d h:i:s', time()),'ERR',true);
                exit("success");
            }catch (Exception $e){
                Log::record('JingDongKi回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('JingDongKi error---:check sign Fail! m_orderid= '.$data['m_orderid']." orderid ".$data['orderid'],'ERR',true);
            exit("success");
//            exit('error:check sign Fail!');
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
        $strsign = $requestarray['orderid'].$requestarray['m_orderid'].$requestarray['payamount'].$md5key;
        $md5keysignstr = md5($strsign);
        $pay_md5sign   = I('request.sign');
        $result=$md5keysignstr == $pay_md5sign;
//        Log::record('JingDongKi回调 orderid=：'.$requestarray['orderid']." md5key=  ".$md5key,'ERR',true);
//        Log::record('JingDongKi回调 orderid=：'.$requestarray['orderid']." strsign=  ".$strsign,'ERR',true);
//        Log::record('JingDongKi回调 orderid=：'.$requestarray['orderid']." md5keysignstr= ".$md5keysignstr,'ERR',true);
//        Log::record('JingDongKi回调 orderid=：'.$requestarray['orderid']." pay_md5sign=  ".$pay_md5sign,'ERR',true);
//        Log::record('JingDongKi回调 orderid=：'.$requestarray['orderid']." reulst=  ". $this->_bool($result),'ERR',true);
        return $result;
    }
    function _bool($b){
        return $b ? 'True' : 'False';
    }

}