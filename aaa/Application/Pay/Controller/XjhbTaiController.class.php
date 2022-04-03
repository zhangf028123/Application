<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XjhbTaiController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XjhbTai',
            'title'     => '现金红包tai',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'mch_id'  => $return['mch_id'], //
            'child_type' => 'H5',
            'out_trade_no'   => $return['orderid'],
            'pay_type' => 'PERSONAL_RED_PACK',
            'total_fee'        => $return["amount"],
            'notify_url'     => urlencode($return['notifyurl']),
            'timestamp' => time(),
        ];

        $data['sign'] = $this->_createSign($return["signkey"], $data);

        $response = $this ->Post($return['gateway'], $data);
        //$response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('XjhbTai pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 100){
            header("location: {$response['data']['url']}");
        }
        echo json_encode($response);


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XjhbTai \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XjhbTai notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_trade_no"]); // 密钥
        /*
        $data = [
            'mch_id'    => I("request.mch_id"),
            'out_trade_no'    => I("request.out_trade_no"),
            'order_no'    => I("request.order_no"),
            'pay_time'    => I("request.pay_time"),
            'timestamp'    => I("request.timestamp"),
            'total_fee'    => I("request.total_fee"),
        ];*/
        $result = $this->_verify($response, $publiKey);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->find();
                if(!$o){
                    Log::record('XjhbTai回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['total_fee'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("XjhbTai回调失败,金额不等：{$response['total_fee'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_trade_no"]){
                    Log::record("XjhbTai回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save([ 'upstream_order'=>$response['order_no']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('XjhbTai回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('XjhbTai error:check sign Fail!','ERR',true);
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

    private function _createToSignStr($Md5key, $list){
        $list['mch_secret'] = $Md5key;
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $md5str = rtrim($md5str, '&');
        return $md5str;
    }

    private function _createSign($Md5key, $list)
    {
        $sign = strtoupper(md5($this->_createToSignStr($Md5key, $list)));
        return $sign;
    }
    private function _verify($list, $md5key){
        $list['mch_secret'] = $md5key;
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if($key != 'sign'){
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $md5str = rtrim($md5str, '&');
        $md5str = strtoupper(md5($md5str));

        $pay_md5sign   = I('request.sign');
        return $md5str == $pay_md5sign;
    }

    private function curlPost($uri, $data = array())
    {
        /***异步请求Post提交***/
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL,$uri);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS,json_encode($data));
        $return = curl_exec ($ch);//返回内容
        curl_close ($ch);
        /***返回JSON格式转换***/
        $return = json_decode($return,true);
        return $return;

    }

}