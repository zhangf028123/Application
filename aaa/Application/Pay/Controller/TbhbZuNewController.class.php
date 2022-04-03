<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class TbhbZuNewController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'TbhbZuNew',
            'title'     => '淘宝红包（zu）',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'version' => "v1.0",
            'serviceType' => "qrcodeReceipt",
            'signType' => "md5",
            'merNo' => $return['mch_id'],
            'requestNo' => $return['orderid'],
            'merOrderNo' => $return['orderid'],
            'payType' => $return['appid'],
            'tradeAmt' => $return["amount"],
            'notifyUrl' => $return['notifyurl'],

        ];
        $data['signature'] = $this->_createSign($return["signkey"], $data);

        $response = $this ->curlPost($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('TbhbZu pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);

        //$response = json_decode($response, true);
        if($response['respCode'] == "P000"){
            //header("location: {$response['data']['payUrl']}");

            $contentType = I("request.content_type");
            if ($contentType == 'json') {
                $return = [
                    'result' => 'ok',
                    'url' => $response['payUrl'],
                ];
                $this->ajaxReturn($return);
            }else{
                header("location: {$response['payUrl']}");
            }

        }
        echo json_encode($response);
    }

    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        $response = json_decode($response, true);
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" TbhbZu \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，TbhbZu notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["merOrderNo"]); // 密钥


        $result = $this->_verify($response, $publiKey, $response['signature']);

        if ($result && $response['respCode'] == "0000") {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["merOrderNo"]])->find();
                if(!$o){
                    Log::record('淘宝红包回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["merOrderNo"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['tradeAmt'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("淘宝红包回调失败,金额不等：{$response['tradeAmt'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['orderNo']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["merOrderNo"]){
                    Log::record("淘宝红包回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["merOrderNo"]])->save([ 'upstream_order'=>$response['orderNo']]);
                $this->EditMoney($response['merOrderNo'], '', 0);
                exit("SUCCESS");
            }catch (Exception $e){
                Log::record('淘宝红包回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('淘宝红包 error:check sign Fail!','ERR',true);
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



    private function _verify($data, $key, $sign){
        ksort($data);
        $str='';
        foreach ($data as $k => $v) {
            if($k == 'signature') continue;
            $str.= $k.'='.$v.'&';
        }
        $strs = md5(rtrim($str,"&").$key);

        return $strs == $sign ? true : false;

    }



    private function _createSign($key, $data){
        return md5("merNo={$data['merNo']}&merOrderNo={$data['merOrderNo']}&notifyUrl={$data['notifyUrl']}&payType={$data['payType']}&requestNo={$data['requestNo']}&serviceType={$data['serviceType']}&signType={$data['signType']}&tradeAmt={$data['tradeAmt']}&version={$data['version']}{$key}");;
    }

    private function curlPost($uri, $data = array())
    {
        /***异步请求Post提交***/
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL,$uri);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS,json_encode($data));
        $return = curl_exec ($ch);//返回内容
        curl_close ($ch);
        /***返回JSON格式转换***/
        $return = json_decode($return,true);
        return $return;

    }


}
