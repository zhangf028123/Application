<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class PddTsController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'PddTs',
            'title'     => '拼多多Ts',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'num'          => sprintf('%.2f',$return['amount']),
            'notifyurl'    => $return['notifyurl'],
            'outorderno'   => $return['orderid'],
            'agent'        => 'alipay' ,
            'agent_id'  => $return['mch_id'],
        ];
        $data['sign'] = $this->_verify($data, $return["signkey"]);
        $response = HttpClient::post($return['gateway'], $data);

        Log::record('PddTs pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);

        $response = json_decode($response, true);

        if ($response['code'] == '1' ){
            header("location: {$response['data']['payUrl']}");
        }
        echo $response['msg'];


    }

    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        Log::record(" PddTs \$response=".json_encode($response),'ERR',true);
        $response = json_decode($response, true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，PddTs notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["outorderno"]); // 密钥

        $data = [
            'order_sn'    => $response['order_sn'],
            'order_amount'    => $response['order_amount'],
            'pay_status'    => $response['pay_status'],
            'created_at'    => $response['created_at'],
            'pay_time'    => $response['pay_time'],
            'pay_type'    => $response['pay_type'],
            'agent_id'    => $response['agent_id'],
            'outorderno'    => $response['outorderno'],

        ];
        $result = $this->getNotifySign($data, $publiKey);
        $sign = $response['sign'];
        //Log::record(" PddTs data=".json_encode($data).'-'.$publiKey.'--'.$result.'---'.$sign,'ERR',true);

        if ($result == $sign && ($data['pay_status'] == '1' || $data['pay_status'] == '3')) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["outorderno"]])->find();
                if(!$o){
                    Log::record('拼多多Ts回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["outorderno"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['order_amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("拼多多Ts回调失败,金额不等：{$response['order_amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_sn']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["outorderno"]){
                    Log::record("拼多多Ts回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["outorderno"]])->save([ 'upstream_order'=>$response['order_sn']]);
                $this->EditMoney($response['outorderno'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('拼多多Ts回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('PddTs error:check sign Fail!','ERR',true);
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

    private function _verify($data, $secret){
        $param = [
            'num' => $data['num'],
            'pay_type' => $data['agent'],
            'agent_id' => $data['agent_id'],
            'outorderno' => $data['outorderno'],
        ];

        // 对数组的值按key排序
        ksort($param);
        // 生成url的形式
        $params = http_build_query($param);
        // 生成sign
        $sign = strtolower(md5($params . $secret));
        return $sign;
    }

    private function getNotifySign($data, $secret){
        $param = [
            'order_sn' => $data['order_sn'],
            'order_amount' => $data['order_amount'],
            'outorderno' => $data['outorderno'],
            'pay_type' => $data['pay_type'],
        ];
        //对数组的值按key排序
        ksort($param);
        // 生成url的形式
        $params = http_build_query($param);

        // 生成sign
        $sign = md5($params . strtolower(md5($secret)));
        return $sign;
    }





}