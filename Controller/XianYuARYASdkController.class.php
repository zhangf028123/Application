<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XianYuARYASdkController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XianYuARYASdk',
            'title'     => '咸鱼代付(tai-sdk)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $params = [
            'version' =>  '3.0',
            'method' =>  'Gt.online.interface',
            'partner'=> $return['mch_id'],
            'banktype'=> $return['appid'],
            'paymoney' =>  sprintf('%.2f', $return["amount"]),
            'ordernumber' =>  $return['orderid'],
            'callbackurl' =>  $return['notifyurl'],
        ];
        $sign = $this -> getSign($return["signkey"],$params);
        $params['sign'] = $sign;
        $params['hrefbackurl'] = '';
        $params['attach'] = '';
        $params['notreturnpage'] = false;
        $response = HttpClient::post($return['gateway'], $params);
        $cost_time = $this->msectime() - $start_time;
        Log::record('XianYuARYASdk pay url='.$return['gateway'].'data='.json_encode($params).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == '0'){
            $result_data=$response['data'];
            header("location: {$result_data['payUrl']}");
        }
        echo $response;
    }


    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XianYuARYASdk \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，XianYuARYASdk notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $data = [
            "partner" => $_REQUEST["partner"], // 商户ID
            "ordernumber" =>  $_REQUEST["ordernumber"], // 订单号
            "orderstatus" =>  $_REQUEST["orderstatus"], // 状态
            'paymoney' =>   $_REQUEST["paymoney"],
            "sysnumber" =>  $_REQUEST["sysnumber"], // 支付流水号
        ];
        $publiKey = getKey($response["ordernumber"]); // 密钥
        $result = $this->_verify($data, $publiKey);

        if ($result) {
            if ($_REQUEST["orderstatus"] == "1"){
                try {
                    $Order = M("Order");
                    $o = $Order->where(['pay_orderid' => $_REQUEST["ordernumber"]])->find();
                    if (!$o) {
                        Log::record('上游wap回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                        exit('error:order not fount' . $_REQUEST["fxddh"]);
                    }

                    $pay_amount = $o['pay_amount'];
                    $paymoney=number_format($_REQUEST["paymoney"], 2);
                    $diff =  $paymoney- $pay_amount;
                    if ($diff <= -1 || $diff >= 1) { // 允许误差一块钱
                        Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount}," . json_encode($response), 'ERR', true);
                        exit('error: amount error!');
                    }
                    $old_order = $Order->where(['upstream_order'=>$response['sysnumber']])->find();
                    if( $old_order && $old_order['pay_orderid'] != $response["ordernumber"]){
                        Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    }
                    $Order->where(['pay_orderid' => $response["ordernumber"]])->save([ 'upstream_order'=>$response['sysnumber']]);
                    $this->EditMoney($response['ordernumber'], '', 0);
                    exit("ok");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            }else{
                Log::record('XianYuARYASdk error:order  fail !', 'ERR', true);
                exit('error:order fail!');
            }
        } else {
            Log::record('XianYuARYASdk error:check sign Fail!', 'ERR', true);
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
        $md5keysignstr = $this->getSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }
    private function getSign($secret, $data)
    {
        // 计算方法:version={0}&method={1}&partner={2}&banktype={3}&paymoney={4}&ordernumber={5}&callbackurl={6}token 其中，
        //签名步骤一，直接组装
        $string_a = http_build_query($data);
        //签名步骤二：在string后加入token
        $string_sign_temp = $string_a . $secret;
        //签名步骤三：MD5加密 32位小写MD5签名值。
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为小写
        $result = strtolower($sign);
        return $result;
    }

}