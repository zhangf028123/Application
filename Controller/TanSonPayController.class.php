<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class TanSonPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'TanSonPay',
            'title' => '上游Wap',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'amount' => sprintf("%.2f", $return["amount"]),
            'partnerid' => $return['mch_id'], //商户号,
            'notifyUrl' => $return['notifyurl'], //异步回调 服务器回调的通知地址
            'payType' => $return['appid'],//是
            'out_trade_no'=> $return['orderid'], //商户订单号
            'returnUrl'=>'http://117.24.12.119:39001',
            'version' => "1.0",
            'format' => "json",
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('TanSonPay pay url=' . $return['gateway'] . ' data=' . json_encode($data), 'INFOR', true);
        $response = json_decode($response, true);
        Log::record('TanSonPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('TanSonPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '200') {
            header("location: {$response['data']['data']['qrcode']}");
            exit();
        } else {
            Log::record('TanSonPay  $response code is failt ', 'ERR', true);
            exit();
        }
        echo $response;
    }
 //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" TanSonPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，TanSonPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }

        $data = [
            'callbacks' => $_REQUEST["callbacks"],
            'partnerid'=>$response['partnerid'],
            'sysorderno'=>$response['sysorderno'],
            'pay_type'=>$response['pay_type'],
            'pay_time'=>$response['pay_time'],
            'out_trade_no'=>$response['out_trade_no'],//用户订单号
            'amount' => sprintf("%.2f", $_REQUEST['amount']),
            'money' => sprintf("%.2f", $_REQUEST['money']),
            'version'=>$response['version'],
            'status'=>$response['status'],
            'sign'=> $_REQUEST['sign'],
        ];
        $orderid = $_REQUEST["out_trade_no"];//自己的订单号
        $upstream_order_id = $_REQUEST["sysorderno"];// 上游的订单id,
        $paymoney = number_format($_REQUEST["amount"], 2);// 交易金额

        $publiKey = getKey($orderid); // 密钥
        $result = $this->verifySign($data, $publiKey);
        if ($result) {
            try {
                $Order = M("Order");
                $o = $Order->where(['pay_orderid' => $orderid])->find();
                if (!$o) {
                    Log::record('上游wap回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                    exit('error:order not fount' . $orderid);
                }
                $pay_amount = $o['pay_amount'];
                $diff = $paymoney - $pay_amount;
                // 允许误差一块钱
                if ($diff <= -1 || $diff >= 1) {
                    Log::record("上游wap回调失败,金额不等：{$paymoney } != {$pay_amount}," . json_encode($response), 'ERR', true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order' => $upstream_order_id])->find();
                if ($old_order && $old_order['pay_orderid'] != $orderid) {
                    Log::record("上游wap回调失败,重复流水号  ：" . json_encode($response) . '旧订单号' . $old_order['pay_orderid'], 'ERR', true);
                }
                $Order->where(['pay_orderid' => $orderid])->save(['upstream_order' => $upstream_order_id]);
                $this->EditMoney($orderid, '', 0);
                exit("success");
            } catch (Exception $e) {
                Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                exit("Exception");
            }
        } else {
            Log::record('TanSonPay error:check sign Fail!', 'ERR', true);
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
//* @Note  生成签名
//* @param $secret   商户密钥
//* @param $data     参与签名的参数
//* @return string
//*/
    private function getSign($secret, $data)
    {
        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        foreach ($data as $k => $v) {//组装参数
            $string_a .=  $k . "=" . $v . "&";;

        }
        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "key=" . $secret;
        Log::record('createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        Log::record('createToSignStr ===== key  ：' . $secret, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        return $result;
    }


    /**
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
    private function verifySign($data, $secret)
    {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);
        Log::record('verifySign ===== $sign2  ：' . $sign2, 'ERR', true);

        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}