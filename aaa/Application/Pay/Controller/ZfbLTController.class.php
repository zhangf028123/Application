<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class ZfbLTController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'ZfbLT',
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
            'amount'        => sprintf('%.2f', $return["amount"]),
            'partnerid'  => $return['mch_id'], //
            'notifyUrl'     => $return['notifyurl'],
            'out_trade_no'   => $return['orderid'],
            'payType' => $return['appid'], 
            'returnUrl'   => $return['callbackurl'],
            'version' => '1.0',
            'format' => 'json',
            
           
        ];
        $data['sign'] = $this->createSign($return["signkey"], $data);
        // 不用签名的参数
       

        $response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('UpPddWap pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if (isset($response['code']) && $response['code'] == 200){
            header("location: {$response['data']['data']['qrcode']}");
        }
       
        echo json_encode($response);


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" UpPddWap \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，UpPddWap notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_trade_no"]); // 密钥

        $data = $response;

        unset($data['sign']);

        Log::record(" UpPddWap \$response=".json_encode($data),'ERR',true);
        $result = $this->_verify($data, $publiKey);

        if ($result && $data['callbacks'] == 'ORDER_SUCCESS' && $data['status'] == 4) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['sysorderno']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_trade_no"]){
                    Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save([ 'upstream_order'=>$response['sysorderno']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('上游wap回调失败,发生异常：'.$e->getMessage(),'ERR',true);
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


    private function _createSign($Md5key, $list)
    {
        $sign = strtoupper(md5($this->_createToSignStr($Md5key, $list)));
        return $sign;
    }

    private function _createToSignStr($Md5key, $list){
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            
                $md5str = $md5str . $key . "=" . $val . "&";
            
        }
        return $md5str . "key=" . $Md5key;
    }
}