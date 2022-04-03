<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class TlKaZKaController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'TlKaZKa',
            'title'     => '卡转卡（tl）',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'mchId'  => $return['mch_id'], //
            'appId'  => '50c7f9e8f91a440493a8e8e9264afc8d', //
            'productId' => $return['appid'], // ,
            'mchOrderNo'   => $return['orderid'],
            'currency' => 'cny',
            'amount'        => $return["amount"]*100,
            'notifyUrl'     => $return['notifyurl'],
            'subject' => 'trade',
            'body' => 'test',
            'extra' => $return['orderid']

        ];
        $data['sign'] = $this->createSign($return["signkey"], $data);

        $response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('TlKaZKa pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['retCode'] == 'SUCCESS'){
            header("location: {$response['payParams']['payUrl']}");
        }
        echo json_encode($response);


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" TlKaZKa \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，TlKaZKa notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["mchOrderNo"]); // 密钥

        if($response['status']<2){
            die("not ok1");
        }

        ksort($response);
        $sign1=$response['sign'];
        $param=[];
        unset($response['sign']);
        ksort($response);
        foreach($response as $k=>$v){
            if($response[$k]){
                $param[]=$k.'='.$v;
            }
        }
        $param=implode('&',$param);
        $sign2=strtoupper(md5($param.'&key='.$publiKey));


        if ($sign1==$sign2) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["mchOrderNo"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["mchOrderNo"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['payOrderId']])->find();
                if( $old_order && $old_order['
                '] != $response["mchOrderNo"]){
                    Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["mchOrderNo"]])->save([ 'upstream_order'=>$response['payOrderId']]);
                $this->EditMoney($response['mchOrderNo'], '', 0);
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

}