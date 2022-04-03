<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class WBKkController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');//
        $parameter = [
            'code'      => 'WBKk',
            'title'     => '上游Wap',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        Log::record('WBKk $parameter ='.json_encode($parameter),'ERR',true);
        $return = $this->orderadd($parameter);
//
        $data = [
            'merchant_code'  => $return['mch_id'], //
            'order_id'   => $return['orderid'],
            'merchant_time' => date('Y-m-d H:i:s'),
//            'pay_bankcode' => $return['appid'], // ,这个appid  对于什么东？
           // 'paytype' => I("request.paytype"),//'201',//是配置还是写死呢？不知道能不能通过配置实现
            'paytype' =>'204',//支付宝话费编码
             'subject' => '测试商品',
            'body' => I("request.body"),
            'amount'=>$return["amount"],
            'notify_url'     => $return['notifyurl'],
            'return_url'   => $return['callbackurl'],
        ];
        $data['sign'] =makeSign($data, $return["signkey"]);
        $data['content_type']       = 'json';

        $response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('WBKk pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == '0'){
            header("location: {$response['url']}");
        }
        echo $response;
    }
    /*
    'code' => 0,
    'msg' => '操作成功',
    'merchant_code' => 商户号,
    'order_id' => 商户订单号,
    'pay_no' => 平台订单号,
    'amount' => 付款金额,
    'resp_code' => 支付状态,
    'sign' =>  签名
    */
    //异步通知
    public function notifyurl()
    {
        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" WBKk \$response=".$response,'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，WBKk notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["order_id"]); // 密钥，就是之前订单对应的密钥，就是商家给我们加密用的
        //判断签名是否一样
        if (makeSign($response, $publiKey) == $response['sign']) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["order_id"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["order_id"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['pay_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["order_id"]){
                    Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["order_id"]])->save([ 'upstream_order'=>$response['pay_no']]);
                $this->EditMoney($response['order_id'], '', 0);
                exit("OK");
            }catch (Exception $e){
                Log::record('上游wap回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('WBKk error:check sign Fail!','ERR',true);
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
    //生成签名
    function makeSign($data, $key){
        $data_string = createSignString($data);
        $data_string .= $key;

        return strtolower(md5($data_string));
    }

    function createSignString($data){
        ksort($data);

        $data_string = '';
        foreach($data as $k=>$v){
            if('sign' == $k || '' === trim($v) ){
                continue;
            }
            $data_string .= "{$k}={$v}&";
        }
        if(strlen($data_string)){
            $data_string = substr($data_string, 0, -1);
        }

        return $data_string;
    }
    private function _verify($requestarray, $md5key){
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}