<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class TaoBaoSdkLDController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'TaoBaoLDsdk',
            'title'     => 'TaoBaoLDsdk',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'merchant_id'  => $return['mch_id'],
            'orderid'   => $return['orderid'],
            'amount'        => $return["amount"],
            'notify_url'     => $return['notifyurl'],
            'pay_type' => $return['appid'],
            'ns_request' => '1',

        ];
        $data['sign'] = md5(
            'merchant_id='.$data['merchant_id'].
            '&orderid='.$data['orderid'].
            '&amount='.strval($data['amount']).
            '&notify_url='.$data['notify_url'].
            '&key='.$return['signkey']
        );


        $response = HttpClient::post($return['gateway'], $data);    //

        $cost_time = $this->msectime() - $start_time;
        Log::record('LingDang pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if($response['code']==1){
            
           $return = [
                    'result' => 'ok',
                    'orderStr' => $response['orderInfo'],
                    ];
                $this->ajaxReturn($return);
        }
        
        echo json_encode($response);

    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" LingDang \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，LingDang notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["orderid"]); // 密钥

        $data = [
            'merchant_id'    => I("request.merchant_id"),
            'orderid'    => I("request.orderid"),
            'amount'    => I("request.amount"),
            'attach'    => I("request.attach"),
            'status'    => I("request.status"),
            'platform_orderid'    => I("request.platform_orderid"),
        ];
        $result = $this->_verify($data, $publiKey);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->find();
                if(!$o){
                    Log::record('LingDang回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderid"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("LingDang回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['platform_orderid']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderid"]){
                    Log::record("LingDang回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["orderid"]])->save([ 'upstream_order'=>$response['platform_orderid']]);
                $this->EditMoney($response['orderid'], '', 0);
                exit("ok");
            }catch (Exception $e){
                Log::record('LingDang回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('LingDang error:check sign Fail!','ERR',true);
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

    private function _verify($data, $md5key){
        $md5keysignstr = md5(
            'merchant_id='.$data['merchant_id'].
            '&platform_orderid='.$data['platform_orderid'].
            '&amount='.strval($data['amount']).
            '&key='.$md5key
        );
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}
