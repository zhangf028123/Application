<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class AliCodeMfController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'AliCodeMf',
            'title'     => '支付宝个码（mf）',
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
            'pay_bankcode' => $return['appid'], // ,
            'pay_notifyurl'     => $return['notifyurl'],
            'pay_amount'        => $return["amount"],
            'pay_attach' => 'trade',
            'pay_getip'   => '172.0.0.1',
        ];
        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = md5($md5str . "key=" . $return["signkey"]);
        $data['sign'] = $sign;


        $response = HttpClient::post($return['gateway'], $data);
        Log::record('Alicodemf pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == 'success') {
            header("location: {$response['data']['payurl']}");
        }
        echo $response;


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" Alicodemf \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Alicodemf notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $data = $_POST['data'];
        $data = json_decode($data,true);
        $sign = $_POST['sign'];

        $publiKey = getKey($data['orderid']); // 密钥


        $result = $this->_verify($data, $publiKey, $sign);
        Log::record(" Alicodemf \$response=".json_encode($data).$result.$_POST['code'],'ERR',true);

        if ($result && $_POST['code'] == 'success') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $data['orderid']])->find();
                if(!$o){
                    Log::record('支付宝个码（mf）回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$data['orderid'] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $data['money'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差二块钱 特殊处理!
                    Log::record("支付宝个码（mf）回调失败,金额不等：{$data['money'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$data['tradeid']])->find();
                if( $old_order && $old_order['pay_orderid'] != $data["orderid"]){
                    Log::record("支付宝个码（mf）回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $data["orderid"]])->save([ 'upstream_order'=>$data['tradeid']]);
                $this->EditMoney($data['orderid'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('支付宝个码（mf）回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('Alicodemf error:check sign Fail!','ERR',true);
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

    private function _verify($requestarray, $md5key, $pay_md5sign){
        ksort($requestarray);
        $md5str= urldecode(http_build_query($requestarray));
        $md5keysignstr = md5($md5str .= '&key=' . $md5key);
        \Think\Log::record('testing....'.$md5keysignstr.'----'.$pay_md5sign, 'ERR', true);
        return $md5keysignstr == $pay_md5sign;
    }

}
