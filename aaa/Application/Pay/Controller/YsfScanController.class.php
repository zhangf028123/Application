<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class YsfScanController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'YsfScan', // 通道名称
            'title' => '云闪付扫码',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $time = time();
        $data = [
            'mercId' => $return['mch_id'],
            'mercOrderId' => $return['orderid'],
            'amount' => $return['amount'],
            'notifyUrl' => $return['notifyurl'],
            'productName' => 'ysf_scan',
            'channel' => 'union',
           // 'channel_id' => $return['appid'],
            'time' => $time,
            'signType' => 'md5_v2',


        ];
        $data['sign'] = $this -> getSign($return["signkey"], $data);


        $response = $this->post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('YsfScan pay url=' . $return['gateway'] . 'data=' . json_encode($data) . 'response=' . $response . "cost time={$cost_time}ms", 'ERR', true);
        $response = json_decode($response, true);

        $response['pay_orderid'] = $return['orderid'];
        $response['url'] = $response['qrcode'] = $response['data']['qrCode'];

        if($response['code'] == '0'){
            $cache      =   Cache::getInstance('redis');
            $content = ['amount'=> $return['amount'], 'url' => $response['url'], 'qrcode'=>$response['qrcode'], 'pay_applydate'=> I("request.pay_applydate"), ];
            $cache->set($return['orderid'], $content, 12*3600);
        }
        if($response['code'] == '0') {
            //$response['url'] = "{$this->_site}Pay_YsfScan_showwx.html?id={$return['orderid']}";
        }
        if ($response['code'] != '0' || $contentType == 'json'){
            if($response['code'] != '0'){    // 记录下单失败的记录
                if(!isset($response['status']))$response['status'] = 'err';
                file_put_contents("Data/Ysfscan_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
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
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Ysfscan notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["mercOrderId"]); // 密钥
        $data = [
            'mercId' => $response['mercId'],
            'mercOrderId' => $response['mercOrderId'],
            'orderStatus' => $response['orderStatus'],
            'createdTime' => $response['createdTime'],
            'paidTime' => $response['paidTime'],
            'orderId' => $response['orderId'],
            'txnAmt' => $response['txnAmt'],
            'txnFee' => $response['txnFee'],
            'paidAmt' => $response['paidAmt'],

        ];
        $sign = $this -> getSign($publiKey, $data);


        if ($sign == $response['sign'] && $response['orderStatus'] == 'paid') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["mercOrderId"]])->find();
                if(!$o){
                    Log::record('云闪付扫码回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["mercOrderId"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['txnAmt'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("云闪付扫码回调失败,金额不等：{$response['txnAmt'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $diff1 = $response['paidAmt'] - $pay_amount;
                if($diff1 <= -1 || $diff1 >= 1 ){ // 允许误差一块钱
                    Log::record("云闪付扫码回调失败,金额不等：{$response['paidAmt'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error11!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['orderId']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["mercOrderId"]){
                    Log::record("云闪付扫码回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['orderId'])){
                    Log::record("流水号为空  ：".json_encode($response).'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["mercOrderId"]])->save([ 'upstream_order'=>$response['orderId']]);
                Log::record('云闪付扫码失败11111,发生异常：'.json_encode($response),'ERR',true);

                $this->EditMoney($response['mercOrderId'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('云闪付扫码失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //支付失败。。。
            Log::record('云闪付扫码订单失败：'.json_encode($response),'ERR',true);
            exit("error: pay fail");
        }
        else {
            Log::record('check sign Fail,发生异常：'.$sign.json_encode($response),'ERR',true);

            exit('error:check sign Fail!');
        }
    }



    /// 微信中转页
    public function showwx($id){
        $cache      =   Cache::getInstance('redis');
        $data = $cache->get($id);
        $this->assign('params', ['out_trade_id'=>$id, 'datetime'=>$data['pay_applydate']]);
        $this->assign($data);
        $this->assign("imgurl", $data['qrcode'] );
        $this->assign('zfbpayUrl',$data['url']);
        $this->assign('orderid',$id);
        $this->assign('money',sprintf('%.2f',$data['amount']));
        return $this->display("Pdd/weixin");
        /*$url = $this->isInWeixinClient() ? $data['qrcode'] : $data['url'];
        header("location:  $url ");*/
    }


    private function getSign($publiKey, $data)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $str .= $key . '=' . utf8_encode($value) . '&';
            }
        }
        if (strlen($str) > 0) {
            $str = substr($str, 0, strlen($str)-1);
        }

        $str =$str.$publiKey;
        $md5Str = md5($str);

        return strtolower($md5Str);
    }

}