<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class WeiBoPayController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'WeiBoPay', // 通道名称
            'title' => '微博红包',
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
            'url' => $return['notifyurl'],
            'key' => 'md5',
            'out_order_id' => $return['orderid'],
            'app_id' => $return['mch_id'],
            'money' => $return['amount'],
            'remark' => $return['appid'],
            'app_script' => $return['signkey'],
        ];
        ksort($data);
        $arr = [];
        foreach ($data as $k => $v) {
            $arr[] = $k . '=' . $v;
        }
        $data['sign'] = md5(implode('&', $arr));
        unset($data['app_script']);
        $response = $this->post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('WeiBoPay pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        $response['pay_orderid'] = $return['orderid'];
        $response['qrcode'] = $response['url'];

        if($response['status'] == '1'){
            $cache      =   Cache::getInstance('redis');
            $content = ['amount'=> $return['amount'], 'url' => $response['url'], 'qrcode'=>$response['qrcode'], 'pay_applydate'=> I("request.pay_applydate"), ];
            $cache->set($return['orderid'], $content, 12*3600);
        }
        if($response['status'] == '1') {
            //$response['url'] = "{$this->_site}Pay_PhoneBill_showh5.html?id={$return['orderid']}";
        }
        if ($response['status'] != '1' || $contentType == 'json'){
            if($response['status'] != '1'){    // 记录下单失败的记录
                if(!isset($response['status']))$response['status'] = 'err';
                file_put_contents("Data/PhoneBillPay_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
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
            Log::record("伪造的ip，WeiBoPay notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["out_order_id"]); // 密钥
        $data = [
            'money' => $response['money'],
            'out_order_id' => $response['out_order_id'],
            'order_no' => $response['order_no'],
            'is_pay' => $response['is_pay'],
            'pay_time' => $response['pay_time'],
            'app_script' => $publiKey,
        ];
        ksort($data);
        $arr = [];
        foreach ($data as $k => $v) {
            $arr[] = $k . '=' . $v;
        }
        $sign = md5(implode('&', $arr));

        if ($sign == $response['sign'] && $response['is_pay'] == 'true') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["out_order_id"]])->find();
                if(!$o){
                    Log::record('微博红包回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["out_order_id"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['money'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("微博红包回调失败,金额不等：{$response['money'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_order_id"]){
                    Log::record("微博红包回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['order_no'])){
                    Log::record("流水号为空  ：".json_encode($response).'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["out_order_id"]])->save([ 'upstream_order'=>$response['order_no']]);
                $this->EditMoney($response['out_order_id'], '', 0);
                exit("true");
            }catch (Exception $e){
                Log::record('微博红包回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //未支付。。。
            Log::record('微博红包订单失败：'.json_encode($response),'ERR',true);
            exit("error:not pay");
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

    public function testwx(){
        return $this->display("Pdd/testwx");
    }

    /// 支付宝虚拟中转页
    public function showh5($id){
        $cache      =   Cache::getInstance('redis');
        $data = $cache->get($id);
        $this->assign('zfbpayUrl',$data['url']);
        $this->assign($data);
        return $this->display("ZhiFuBao/alipay_h5");
    }

}

