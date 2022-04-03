<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class AliEpayController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'AliEpay', // 通道名称
            'title' => 'Epay支付宝h5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $data = [
            'merchant_id' => $return['mch_id'],
            'channel' => '0',
            'amount' => $return['amount'] * 100,
            'order_id' => $return['orderid'],
            'notify_url' => $return['notifyurl'],
            'return_url' => $return['callbackurl'],
            'timestamp' => time(),
        ];

        $data['sign'] = $this -> getSign($return['signkey'], $data);

        $response = $this -> curlnew($return['gateway'], $data);
        //$response = $this->postnew($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('AliEpay pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        $response['pay_orderid'] = $return['orderid'];
        $response['qrcode'] = $response['url'] = $response['payurl'];

        if($response['code'] == '100'){
            $cache      =   Cache::getInstance('redis');
            $content = ['amount'=> $return['amount'], 'url' => $response['url'], 'qrcode'=>$response['qrcode'], 'pay_applydate'=> I("request.pay_applydate"), ];
            $cache->set($return['orderid'], $content, 12*3600);
        }
        if($response['code'] == '100') {
            //$response['url'] = "{$this->_site}Pay_AliEpay_showh5.html?id={$return['orderid']}";
        }
        if ($response['code'] != '100' || $contentType == 'json'){
            if($response['code'] != '100'){    // 记录下单失败的记录
                if(!isset($response['status']))$response['status'] = 'err';
                file_put_contents("Data/AliEpay_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
            } else {
                $response['status'] = 'ok';
            }
            $this->ajaxReturn($response);
        }

        $this->assign("imgurl", $response['qrcode'] );
        //$this->assign("data", $data);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('zfbpayUrl',$response['url']);
        $this->assign('money',sprintf('%.2f',$return['amount']));


        if (parent::isMobile()){ // 手机直接跳转
            header("location: {$response['url']}");
        } else {
            header("location: {$response['url']}");
        }

        // $this->display("ZhiFuBao/alipayori");
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


    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        Log::record('支付宝h5(Epay)订单失败：'.$response.'--'.json_decode($response).'--'.json_encode($response),'ERR',true);
        $response = json_decode($response, true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，AliEpay notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["order_id"]); // 密钥
        $data = [
            'code' => $response['code'],
            'message' => $response['message'],
            'amount' => $response['amount'],
            'pay_amount' => $response['pay_amount'],
            'bill_id' => $response['bill_id'],
            'order_id' => $response['order_id'],
            'order_user' => $response['order_user'],


        ];

        $sign = $this -> getSign($publiKey, $data);
        //Log::record('支付宝h5(Epay)订单失败：'.$sign.'-'.$response['sign'].'-'.$response['code'],'ERR',true);
        if ($sign == $response['sign'] && $response['code'] == '100') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["order_id"]])->find();
                if(!$o){
                    Log::record('支付宝h5(Epay)回调失败,找不到订单：'.$response,'ERR',true);
                    exit('error:order not fount'.$response["order_id"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['amount'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝h5(Epay)回调失败,金额不等：{$response['amount'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error!');
                }
                $diff1 = $response['pay_amount'] / 100 - $pay_amount;
                if($diff1 <= -1 || $diff1 >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝h5(Epay)回调失败,金额不等：{$response['pay_amount'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error11!');
                }

                $old_order = $Order->where(['upstream_order'=>$response['bill_id']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["order_id"]){
                    Log::record("支付宝h5(Epay)回调失败,重复流水号  ：".$response.'旧订单号'.$old_order['order_id'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['bill_id'])){
                    Log::record("流水号为空  ：".$response.'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["order_id"]])->save([ 'upstream_order'=>$response['bill_id']]);
                $this->EditMoney($response['order_id'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('支付宝h5(Epay)回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //未支付。。。
            Log::record('支付宝h5(Epay)订单失败：'.$response,'ERR',true);
            exit("error:not pay");
        }
        else {
            exit('error:check sign Fail!');
        }
    }

    private function postnew($url,$parac){
        //$postdata=http_build_query($parac);
        $postdata = json_encode($parac, JSON_UNESCAPED_UNICODE);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }



    private function curlnew($url, $return_array){
        /*
        $notifystr = "";
        foreach ($return_array as $key => $val) {
            $notifystr = $notifystr . $key . "=" . $val . "&";
        }
        $notifystr = rtrim($notifystr, '&');
        */
        $notifystr = json_encode($return_array, JSON_UNESCAPED_UNICODE);

        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $notifystr);
        $contents = curl_exec($ch);
        //$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $contents;
    }

    private function getSign($secret, $array){
        //unset($array['sign']);
        ksort($array, SORT_STRING);
        $query = '';
        foreach ($array as $key => $value) $query.= $key.'='.$value.'&';
        $query.= $secret;
        return md5($query);
    }

    /*private function getSign($secret, $data)
    {
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        //$string_sign_temp = $string_a . "&key=" . $secret;
        $string_sign_temp = $string_a  . $secret;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        return $sign;
    }*/

    /// 支付宝虚拟中转页
    public function showh5($id){
        $cache      =   Cache::getInstance('redis');
        $data = $cache->get($id);
        $this->assign('zfbpayUrl',$data['url']);
        $this->assign($data);
        return $this->display("ZhiFuBao/alipay_h5");
    }

}

