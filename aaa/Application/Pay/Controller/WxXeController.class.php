<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class WxXeController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'WxXe',
            'title'     => 'weixinxe',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);


        $data = [
            'p1_merchantno'  => $return['mch_id'], //
            'p2_amount'        => sprintf('%.2f', $return["amount"]),
            'p3_orderno'   => $return['orderid'],
            'p4_paytype' => $return['appid'], // 
            'p5_reqtime' => date('YmdHis'),
            'p6_goodsname' => 'trade',
            'p8_returnurl'   => $return['callbackurl'],    
            'p9_callbackurl'     => $return['notifyurl'],
           
            
        ];

        $data['sign'] = $this->createSign($return["signkey"], $data);
       //Log::record('wxxe error:'.json_encode($data), 'ERR', true);

        $response = $this -> post_url($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('WxXe pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        //$responsehtml = $response;
        $response = json_decode($response, true);
        if($response['rspcode'] == 'A0'){
            header("location: {$response['data']}");
        }else{
            echo json_encode($response);
        }
    }

    private function post_url($url,$parac){
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

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" WxXe \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，WxXe notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["p3_orderno"]); // 密钥

        $data = $response;
        unset($data['sign']);

        $result = $this->_verify($data, $publiKey);

        if ($result && $data['p4_status'] == 2) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["p3_orderno"]])->find();
                if(!$o){
                    Log::record('上游拼多多回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["p3_orderno"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['p2_amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游拼多多回调失败,金额不等：{$response['p2_amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['p9_porderno']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["p3_orderno"]){
                    Log::record("上游拼多多回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["p3_orderno"]])->save([ 'upstream_order'=>$response['p9_porderno']]);
                $this->EditMoney($response['p3_orderno'], '', 0);
                exit("SUCCESS");
            }catch (Exception $e){
                Log::record('上游拼多多回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('WxXe error:check sign Fail!','ERR',true);
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