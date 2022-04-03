<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class UpPddController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'UpPdd',
            'title'     => '上游拼多多',
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
            'pay_applydate' => I("request.pay_applydate"),
            'pay_bankcode' => $return['appid'], // ,
            'pay_notifyurl'     => $return['notifyurl'],
            'pay_callbackurl'   => $return['callbackurl'],
            'pay_amount'        => $return["amount"],
        ];
        $data['pay_md5sign'] = $this->createSign($return["signkey"], $data);
        // 不用签名的参数
        $data['pay_productname']    = I("request.pay_productname");
        $data['pay_productnum']     = I("request.pay_productnum");
        $data['pay_productdesc']    = I("request.pay_productdesc");
        $data['pay_producturl']     = I("request.pay_productnum");
        $data['pay_attach']         = I("request.pay_attach");
        $data['content_type']         = I("request.content_type");


        $response = HttpClient::post($return['gateway'], $data);    //
        Log::record('UpPdd pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        $responsehtml = $response;
        $contentType = I("request.content_type");
        //$this->setHtml($return['gateway'], $data);

        if ($response['returncode'] != '00' || $contentType == 'json'){
            if($response['result'] != '00'){    // 记录下单失败的记录
                if(!isset($response['result']))$response['result'] = 'err';
                file_put_contents("Data/UpPdd_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
            }else {
                $response['result'] = 'ok';
            }
            $this->ajaxReturn($response);
        };
        echo $responsehtml;
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" UpPdd \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，UpPdd notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["orderid"]); // 密钥

        $data = [
            'memberid'    => I("request.memberid"),
            'orderid'    => I("request.orderid"),
            'amount'    => I("request.amount"),
            'datetime'    => I("request.datetime"),
            'transaction_id'    => I("request.transaction_id"),
            'returncode'    => I("request.returncode"),
        ];
        $result = $this->_verify($data, $publiKey);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->find();
                if(!$o){
                    Log::record('上游拼多多回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderid"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游拼多多回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: fuck1!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['transaction_id']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderid"]){
                    Log::record("上游拼多多回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["orderid"]])->save([ 'upstream_order'=>$response['transaction_id']]);
                $this->EditMoney($response['orderid'], '', 0);
                exit("OK");
            }catch (Exception $e){
                Log::record('上游拼多多回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('UpPdd error:check sign Fail!','ERR',true);
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