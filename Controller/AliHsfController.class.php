<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class AliHsfController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'AliHsf',
            'title'     => '支付宝个码(hsf)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'bid'       => $return['mch_id'],
            'money'     =>  sprintf("%.2f",$return["amount"]),
            'order_sn'   => $return['orderid'],
            'notify_url'     => $return['notifyurl'],
            'pay_type' => '2',

        ];
        $publiiv = 'AKeSU0DoIYjzIzgJ';
        $data['sign'] = md5(
            $return["signkey"] . '|' .
            $data['bid'] . '|' .
            $data['money'] . '|' .
            $data['order_sn'] . '|' .
            $data['notify_url'] . '|' .
            $publiiv
        );

        $response = $this -> curlpost($return['gateway'], $data);
        Log::record(' AliHsf pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        if($response['code'] == 100) {
            header("location: {$response['data']['url']}");
        }

        /*$response = json_decode($response, true);
        $contentType = I("request.content_type");
        //$this->setHtml($return['gateway'], $data);

        if ($response['result'] != 'ok' || $contentType == 'json'){
            if($response['result'] != 'ok'){    // 记录下单失败的记录
                if(!isset($response['result']))$response['result'] = 'err';
                file_put_contents("Data/AliPerson_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
            }else {
                $response['result'] = 'ok';
            }
            $this->ajaxReturn($response);
        }
        $this->assign("imgurl", $response['qrcode'] );
        //$this->assign("data", $data);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('zfbpayUrl',$response['url']);
        $this->assign('money',sprintf('%.2f',$return['amount']));

        $this->assign('isInWeixin',false);
        /*if($array['pid'] == 941  ){ // 微信
            if($this->isInWeixinClient() ){
                $this->assign('isInWeixin',true);
                header("location: {$response['qrcode']}");  // 跳到代付
            }
            header("location: {$response['url']}");
        }*/
        /*if (parent::isMobile()){ // 手机直接跳转
            header("location: {$response['url']}");
        }

        $this->display("ZhiFuBao/alipayori");*/


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" AliHsf \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，AliHsf notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["order_sn"]); // 密钥

        $data = [
            'pay_time'    => I("request.pay_time"),
            'money'    => I("request.money"),
            'pay_money'    => I("request.pay_money"),
            'order_sn'    => I("request.order_sn"),
            'sys_order_sn'    => I("request.sys_order_sn"),

        ];
        $publiiv = 'AKeSU0DoIYjzIzgJ';
        $sign = md5(
            $publiKey . '|' .
            $data['pay_time'] . '|' .
            $data['money'] . '|' .
            $data['pay_money'] . '|' .
            $data['order_sn'] . '|' .
            $data['sys_order_sn'] . '|' .
            $publiiv
        );

        if ($sign == $_REQUEST['sign']) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["order_sn"]])->find();
                if(!$o){
                    Log::record('支付宝个码(hsf)回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["order_sn"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['money'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝个码(hsf)回调失败,金额不等：{$response['money'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $diff = $response['pay_money'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝个码(hsf)回调失败,金额不等1：{$response['pay_money'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount1 error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['sys_order_sn']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["order_sn"]){
                    Log::record("支付宝个码(hsf)回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["order_sn"]])->save([ 'upstream_order'=>$response['sys_order_sn']]);
                $this->EditMoney($response['order_sn'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('支付宝个码(hsf)回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('AliHsf error:check sign Fail!','ERR',true);
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


    private function curlpost($url, $post) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        if (preg_match('/^https:\/\//i', $url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
        $response = curl_exec($ch);
        $request_info = curl_getinfo($ch);
        $http_code = $request_info['http_code'];
        if ($http_code == 200) {
            curl_close($ch);
            return $response;
        }
        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }
        if ($http_code != 200) {
            curl_close($ch);
            return $response;
        }
        curl_close($ch);
        return $request_info;

    }

    private function postnew($url,$parac){
        $postdata=http_build_query($parac);
        //$postdata = json_encode($parac, JSON_UNESCAPED_UNICODE);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }

}