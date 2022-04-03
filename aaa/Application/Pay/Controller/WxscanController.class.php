<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class WxscanController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'Wxscan', // 通道名称
            'title' => '微信扫码',
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
            'agentNo' => $return['appid'],
            'merchantNo' => $return['mch_id'],
            'orderAmount' => $return['amount'] * 100, //单位分
            'outOrderNo' => $return['orderid'],
            'notifyUrl' => $return['notifyurl'],
            'callbackUrl' => $return['callbackurl'],
            'productName' => '多多科技',
            'acqCode' => 'YS_A_02',

        ];
        $data['sign'] = $this -> getSign($return["signkey"], $data);


        $response = $this->post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('Wxscan pay url=' . $return['gateway'] . 'data=' . json_encode($data) . 'response=' . $response . "cost time={$cost_time}ms", 'ERR', true);
        $response = json_decode($response, true);

        $response['pay_orderid'] = $return['orderid'];
        $response['url'] = $response['qrcode'] = $response['payUrl'];

        if($response['status'] == 'T'){
            $cache      =   Cache::getInstance('redis');
            $content = ['amount'=> $return['amount'], 'url' => $response['url'], 'qrcode'=>$response['qrcode'], 'pay_applydate'=> I("request.pay_applydate"), ];
            $cache->set($return['orderid'], $content, 12*3600);
        }
        if($response['status'] == '1') {
            //$response['url'] = "{$this->_site}Pay_Wxscan_showwx.html?id={$return['orderid']}";
        }
        if ($response['status'] != 'T' || $contentType == 'json'){
            if($response['status'] != 'T'){    // 记录下单失败的记录
                if(!isset($response['status']))$response['status'] = 'err';
                file_put_contents("Data/Wxscan_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
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
            Log::record("伪造的ip，Wxscan notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["outOrderNo"]); // 密钥
        $data = [
            'merchantNo' => $response['merchantNo'],
            'orderAmount' => $response['orderAmount'],
            'orderNo' => $response['orderNo'],
            'outOrderNo' => $response['outOrderNo'],
            'orderStatus' => $response['orderStatus'],
            'orderStatus' => $response['orderStatus'],
            'payTime' => $response['payTime'],
            'productName' => $response['productName'],

        ];
        $sign = $this -> getSign($publiKey, $data);

        if ($sign == $response['sign'] && $response['orderStatus'] == 'SUCCESS') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["outOrderNo"]])->find();
                if(!$o){
                    Log::record('微信扫码回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["outOrderNo"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['orderAmount'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("微信扫码回调失败,金额不等：{$response['orderAmount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['orderNo']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["outOrderNo"]){
                    Log::record("微信扫码回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['orderNo'])){
                    Log::record("流水号为空  ：".json_encode($response).'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["outOrderNo"]])->save([ 'upstream_order'=>$response['orderNo']]);
                $this->EditMoney($response['outOrderNo'], '', 0);
                exit("OK");
            }catch (Exception $e){
                Log::record('微信扫码回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //支付失败。。。
            Log::record('微信扫码订单失败：'.json_encode($response),'ERR',true);
            exit("error: pay fail");
        }
        else {
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


    private function getSign($secret, $data)
    {
        // 去空
        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        //$string_sign_temp = $string_a . "&key=" . $secret;
        $string_sign_temp = $string_a  . $secret;
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为小写
        $result = strtolower($sign);

        return $result;
    }

}