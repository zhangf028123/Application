<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class PddCjController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'PddCj',
            'title'     => '拼多多(cj)',
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
            'out_order_id'   => $return['orderid'],
            'bank_code' => $return['appid'], // ,
            'money'        => $return["amount"],
            'notify_url'  => 'http://47.56.13.49:39001/Pay_PddCj_notifyurl.html',
            //'notify_url'     => $return['notifyurl'],

        ];
        $data['sign'] = md5($data['mch_id'].$return["signkey"].
            $data['out_order_id'].$return["signkey"].
            $data['bank_code'].$data['money'].$return["signkey"].
            $data['notify_url']);

        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('PddCj pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        $contentType = I("request.content_type");

        if ($response['code'] == 1 ) {
            if ( $contentType == 'json') {
                $return = [
                    'result' => 'ok',
                    'orderStr' => $response['data']['sdk_url'],
                ];
                $this->ajaxReturn($return);
            }
            header("location: {$response['data']['pay_url']}");
        } else {
            echo $response;
        }


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" Pddcj \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Pddcj notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_order_id"]); // 密钥

        $data = [
            'out_order_id'    => I("request.out_order_id"),
            'mch_id'    => I("request.mch_id"),
            'attach'    => I("request.attach"),
            'money'    => I("request.money"),
            'status'    => I("request.status"),
        ];
        $result = $this->_verify($data, $publiKey);

        if ($result && $data['status'] == 1) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["out_order_id"]])->find();
                if(!$o){
                    Log::record('拼多多cj回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["out_order_id"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['money'] - $pay_amount;
                if($diff <= -2 || $diff >= 2 ){ // 允许误差二块钱 特殊处理!
                    Log::record("拼多多cj回调失败,金额不等：{$response['money'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }

                //$Order->where(['pay_orderid' => $response["out_order_id"]])->save([ 'upstream_order'=>$response['transaction_id']]);
                $this->EditMoney($response['out_order_id'], '', 0);
                exit("SUCCESS");
            }catch (Exception $e){
                Log::record('拼多多cj回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('Pddcj error:check sign Fail!','ERR',true);
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
        $md5keysignstr = md5($requestarray['mch_id'].$md5key.$requestarray['out_order_id'].$requestarray['money'].$requestarray['status'].$md5key);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}
