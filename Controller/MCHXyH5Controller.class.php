<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class MCHXyH5Controller extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');//
        $parameter = [
            'code'      => 'MCHXyH5',
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
            'merchant_id'  => $return['mch_id'], //
            'orderid'   => $return['orderid'],
            'amount'=>$return["amount"],
            'notify_url'     => $return['notifyurl'],
        ];
        $data['sign'] = $this->makeSign($return["signkey"], $data);
        $data['pay_type']='1001';
        $post_string = json_encode($data);
        $response = $this->curl_post_1($return['gateway'], $post_string);

        var_dump($response);
        $cost_time = $this->msectime() - $start_time;
        Log::record('MCHXyH5 pay url='.$return['gateway'].' data='.$post_string .'response='. $response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == '1'){
            header("location: {$response['data']['pay_url']}");
        }
        echo $response;
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" MCHXyH5 \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，MCHXyH5 notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["orderid"]); // 密钥

        $data = [
            'memberid'    => I("request.merchant_id"),
            'orderid'    => I("request.orderid"),
            'amount'    => I("request.amount"),
            'attach'    => I("request.attach"),
            'platform_orderid'    => I("request.platform_orderid"),
        ];
        $result = $this->_verify($data, $publiKey);
        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderid"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['platform_orderid']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderid"]){
                    Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["orderid"]])->save([ 'upstream_order'=>$response['platform_orderid']]);
                $this->EditMoney($response['orderid'], '', 0);
                exit("OK");
            }catch (Exception $e){
                Log::record('上游wap回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('MCHXyH5 error:check sign Fail!','ERR',true);
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
     private function makeSign($Md5key, $list)
    {
        Log::record('makeSign：=========== $Md5key '.$Md5key,'ERR',true);
        $sign = md5($this->makeToSignStr($Md5key, $list));
        return $sign;
    }
    private function makeToSignStr($Md5key, $list){
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $signStr=$md5str . "key=" . $Md5key;
        Log::record('makeToSignStr：  '.$signStr,'ERR',true);
        return $signStr;
    }

    private function curl_post_1($api_url, $post_string){
        Log::record(' curl_post    $api_url：  '.$api_url,'ERR',true);
        Log::record(' curl_post    $post_string：  '.$post_string,'ERR',true);

        $ch = curl_init($api_url);
        $headers = array('content-type: application/json;charset=utf-8');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $reponse = curl_exec($ch);
        curl_close($ch);
        Log::record(' curl_post    $reponse：  '.$reponse,'ERR',true);

        return $reponse;
    }

    private function _verify($requestarray, $md5key){
        $md5keysignstr = $this->makeSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}