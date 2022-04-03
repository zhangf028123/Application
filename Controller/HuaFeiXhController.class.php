<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class HuaFeiXhController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'HuaFeiXh',
            'title'     => 'HuaFeiXh',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'merchant_no'  => $return['mch_id'], //
            'cus_order_no'   => $return['orderid'],
            'money'        => $return["amount"] * 100,
            'notify_url'     => $return['notifyurl'],
            'return_url'   => $return['callbackurl'],
            'pay_type' => $return['appid'], // ,             
            
        ];
        $data['sign'] = $this->_createSign($return["signkey"], $data);
        $response = $this -> _post($return['gateway'], $data);

    
        $cost_time = $this->msectime() - $start_time;
        Log::record('HuaFeiXh pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        
        if(isset($response['code']) && $response['code'] == "10000"){
            header("location: {$response['data']['pay_url']}");
        }
        echo json_encode($response);


    }


    private function _post($url,$parac){
        $postdata=http_build_query($parac);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
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
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $md5str = rtrim($md5str, "&");
        return "key=" . $Md5key . "&" . $md5str;
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" HuaFeiXh \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，HuaFeiXh notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["cus_order_no"]); // 密钥


        $data = [
            'merchant_no'    => I("request.merchant_no"),
            'money'    => I("request.money"),
            'order_no'    => I("request.order_no"),
            'cus_order_no'    => I("request.cus_order_no"),
            'pay_type'    => I("request.pay_type"),
            'status'    => I("request.status"),
            'pay_time'    => I("request.pay_time"),
            
        ];
        $result = $this->_verify($data, $publiKey);

        if ($result && $data['status'] != 2 ) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["cus_order_no"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["cus_order_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['money'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['money'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["cus_order_no"]){
                    Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["cus_order_no"]])->save([ 'upstream_order'=>$response['order_no']]);
                $this->EditMoney($response['cus_order_no'], '', 0);
                exit("10000");
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
        $md5keysignstr = $this->_createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}