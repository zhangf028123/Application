<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class HuaBeiTianController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'HuaBeiTian',
            'title'     => '花呗(tian)',
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
            'out_trade_no'   => $return['orderid'],
            'total_amount'        => $return["amount"],
            'notify_url'     => $return['notifyurl'],

        ];
        $data['sign'] = $this->createSign($return["signkey"], $data);
        //$this -> setHtml($return['gateway'], $data);
        $response = HttpClient::post($return['gateway'], $data);    //
        Log::record('HuaBeiTian pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        //$responsehtml = $response;
        $response = json_decode($response, true);
        if ($response['message'] == 'success') {
            header("location: {$response['url']}");
        }



    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" HuaBeiTian \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，HuaBeiTian notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_trade_no"]); // 密钥

        $data = [
            'trade_no'    => I("request.trade_no"),
            'pay_type'    => I("request.pay_type"),
            'out_trade_no'    => I("request.out_trade_no"),
            'total_amount'    => I("request.total_amount"),
            'pay_status'    => I("request.pay_status"),
        ];
        $result = $this->_verify($data, $publiKey);

        if ($result && $data['pay_status'] == 'success') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->find();
                if(!$o){
                    Log::record('花呗tian回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['total_amount'] - $pay_amount;
                if($diff <= -2 || $diff >= 2 ){ // 允许误差二块钱 特殊处理!
                    Log::record("花呗tian回调失败,金额不等：{$response['total_amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['trade_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_trade_no"]){
                    Log::record("花呗tian回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save([ 'upstream_order'=>$response['trade_no']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('花呗tian回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('Pddtian error:check sign Fail!','ERR',true);
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
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}
