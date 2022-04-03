<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class WeiBoSpeedController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'WeiBoSpeed', // 通道名称
            'title' => '微博红包speed',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $data = array(
            "fxid" => $return['mch_id'], //商户号
            "fxddh" => $return['orderid'], //商户订单号
            "fxdesc" => 'ceshi', //商品名
            "fxfee" => $return['amount'], //支付金额 单位元
            "fxnotifyurl" => $return['notifyurl'], //异步回调 , 支付结果以异步为准
            "fxbackurl" => $return['callbackurl'], //同步回调 不作为最终支付结果为准，请以异步回调为准
            "fxpay" => 'hbpay', //支付类型 此处可选项以网站对接文档为准 微信公众号：wxgzh   微信H5网页：wxwap  微信扫码：wxsm   支付宝H5网页：zfbwap  支付宝扫码：zfbsm 等参考API
            "fxip" => $_SERVER['SERVER_ADDR'], //支付端ip地址

        );
        $data["fxsign"] = md5($data["fxid"] . $data["fxddh"] . $data["fxfee"] . $data["fxnotifyurl"] . $return['signkey']); //加密

        //$response = HttpClient::post($return['gateway'], $data);    //
        //$response = $this->post($return['gateway'], $data);
        $response = $this -> getHttpContent($return['gateway'], "POST", $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('WeiBoSpeed pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if($response['status'] == '1') {
            header("location: {$response['payurl']}");
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


    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，WeiBoSpeed notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["fxddh"]); // 密钥
        $data = [
            'fxid' => $response['fxid'],
            'fxddh' => $response['fxddh'],
            'fxorder' => $response['fxorder'],
            'fxdesc' => $response['fxdesc'],
            'fxfee' => $response['fxfee'],
            'fxattch' => $response['fxattch'],
            'fxstatus' => $response['fxstatus'],
            'fxtime' => $response['fxtime'],

        ];
        $sign = md5($data['fxstatus'] . $data['fxid'] . $data['fxddh'] . $data['fxfee'] . $publiKey); //验证签名

        if ($sign == $response['fxsign'] && $response['fxstatus'] == '1') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["fxddh"]])->find();
                if(!$o){
                    Log::record('微博红包speed回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["fxddh"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['fxfee'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("微博红包speed回调失败,金额不等：{$response['fxfee'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['fxorder']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["fxddh"]){
                    Log::record("微博红包speed回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['fxorder'])){
                    Log::record("流水号为空  ：".json_encode($response).'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["fxddh"]])->save([ 'upstream_order'=>$response['fxorder']]);
                $this->EditMoney($response['fxddh'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('微博红包speed回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['fxsign']) {
            //未支付。。。
            Log::record('微博红包speed订单失败：'.json_encode($response),'ERR',true);
            exit("error:not pay");
        }
        else {
            exit('error:check sign Fail!');
        }
    }



    private function getHttpContent($url, $method = 'GET', $postData = array()) {
        $data = '';
        $user_agent = $_SERVER ['HTTP_USER_AGENT'];
        $header = array(
            "User-Agent: $user_agent"
        );
        if (!empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
                if(strstr($url,'https://')){
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                }

                if (strtoupper($method) == 'POST') {
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                }
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                $data = '';
            }
        }
        return $data;
    }



}

