<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class Hfgh2WeiXinController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $ddh = time() . mt_rand(100, 999);
        $parameter = [
            'code' => 'Hfgh2WeiXin',
            'title' => '上游Wap',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => $ddh, //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            "fxid" => $return['mch_id'], //商户号
            "fxddh" => $return['orderid'], //商户订单号
            "fxfee" => $return["amount"], //支付金额 单位元
            "fxnotifyurl" => $return['notifyurl'], //异步回调 , 支付结果以异步为准
        ];

        $data['fxsign'] = $this->createSign_1($return["signkey"], $data);
        $data['fxdesc'] ="商品";
        $data['fxbackurl']=$return['callbackurl'];
        $data['fxpay'] = $return['appid'];
        $data['fxip']= getIP();
        $data['fxuserid']='aa013';
        $data['fxfrom']=3;
        $response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response,true);
        Log::record('Hfgh2WeiXin pay url=' . $return['gateway'] . ' data=' . json_encode($data) . ' response=' .json_encode($response) . "cost time={$cost_time}ms", 'ERR', true);

        if (empty($response)) {
            Log::record('Hfgh2WeiXin  $response is empty ', 'ERR', true);
        }
        if ($response['status'] == 1) {
            header('Location:' . $response["payurl"]); //转入支付页面
        } else {
            //echo $r['error'].print_r($backr); //输出详细信息
            echo $response;
            exit();
        }
    }

    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" Hfgh2WeiXin \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，Hfgh2WeiXin notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }

        $data = [
            'fxid' => $_REQUEST['fxid'], //商户编号
            'fxddh' => $_REQUEST['fxddh'], //商户订单号
            'fxorder' =>$_REQUEST['fxorder'], //平台订单号
            'fxfee' => $_REQUEST['fxfee'], //交易金额
            'fxstatus' => $_REQUEST['fxstatus'], //订单状态
            'fxtime' => $_REQUEST['fxtime'], //支付时间
        ];
        $publiKey = getKey($response["fxddh"]); // 密钥
        $result = $this->_verify($data, $publiKey);
        Log::record('Hfgh2WeiXinnotifyurl    data=' . json_encode($data) , 'ERR', true);
        $fxstatus=$_REQUEST['fxstatus']; //订单状态;
        if ($result) {
            try {
                if($fxstatus=='0'){
                    Log::record('Hfgh2WeiXin pay_fail  data=' . json_encode($data) , 'ERR', true);
                    exit("success");
                }else if($fxstatus=='1'){//如果订单支付成功就执行
                    $Order = M("Order");
                    $o = $Order->where(['pay_orderid' => $_REQUEST["fxddh"]])->find();
                    if (!$o) {
                        Log::record('上游wap回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                        exit('error:order not fount' . $_REQUEST["fxddh"]);
                    }

                    $pay_amount = $o['pay_amount'];
                    $diff = $response['fxfee'] - $pay_amount;
                    if ($diff <= -1 || $diff >= 1) { // 允许误差一块钱
                        Log::record("上游wap回调失败,金额不等：{$response['fxfee'] } != {$pay_amount}," . json_encode($response), 'ERR', true);
                        exit('error: amount error!');
                    }
                    $old_order = $Order->where(['upstream_order'=>$response['fxorder']])->find();
                    if( $old_order && $old_order['pay_orderid'] != $response["fxddh"]){
                        Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                        //die("not ok2");
                    }
                    $Order->where(['pay_orderid' => $response["fxddh"]])->save([ 'upstream_order'=>$response['fxorder']]);
                    $this->EditMoney($response['fxddh'], '', 0);
                    exit("success");
                }
            } catch (Exception $e) {
                Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                exit("Exception");
            }
        } else {
            Log::record('Hfgh2WeiXin error:check sign Fail!', 'ERR', true);
            exit('error:check sign Fail!');
        }
    }

    //同步通知
    public function callbackurl()
    {
        $Order = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        } else {
            exit("error");
        }
    }

    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
    private function createSign_1($Md5key, $list)
    {
        $temp=$this->createToSignStr_1($Md5key, $list);
        $sign = strtolower(md5($temp));
        Log::record('createToSignStr ===== ：'.$temp.' sign= '.$sign,'ERR',true);
        return $sign;
    }
    function createToSignStr_1($Md5key, $list){
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            $md5str = $md5str . $key . "=". $val . "&";
        }
        return $md5str . 'key='.$Md5key;
    }

    private function _verify($requestarray, $md5key){
        $md5keysignstr = $this->createSign_1($md5key, $requestarray);
        $pay_md5sign   = I('request.fxsign');
        return $md5keysignstr == $pay_md5sign;
    }







}