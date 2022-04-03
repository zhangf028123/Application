<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class JingDongKiSdkController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'JingDongKiSdk',
            'title'     => '京东e卡SDK',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        Log::record('JingDongKiSdk  支付唤起','ERR',true);
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
        Log::record('JingDongKiSdk pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 1){
            $return = [
                'result' => 'ok',
                'orderStr' => $response['data']['sdk'],
            ];
            Log::record('JingDongKiSdk     请求 $return ='.json_encode($return),'ERR',true);
            $this->ajaxReturn($return);

        }
        echo json_encode($response);


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" JingDongKiSdk \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，JingDongKiSdk notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["m_orderid"]); // 密钥

        $data = [
            'orderid'    => I("request.orderid"),
            'm_orderid'    => I("request.m_orderid"),
            'payamount'    => I("request.payamount"),
        ];
        $result = $this->_verify($data, $publiKey);

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
                exit("success");
            }catch (Exception $e){
                Log::record('JingDongKi回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('JingDongKiSdk error:check sign Fail!','ERR',true);
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
        $strsign = $requestarray['orderid'].$requestarray['m_orderid'].$requestarray['payamount'].$md5key;
        $md5keysignstr = md5($strsign);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}