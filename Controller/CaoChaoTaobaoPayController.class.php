<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class CaoChaoTaobaoPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'CaoChaoTaobaoPay',
            'title' => '曹超淘宝支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'service' => $return['appid'],//是	pay.alipay.wappay 支付方式
            'version' => '1.0',
            'charset' => 'UTF-8',
            'sign_type' => 'MD5',
            'merchant_id' => $return['mch_id'],//商户号,
            'out_trade_no' => $return['orderid'], //商户订单号
            'goods_desc' => 'test',
            'total_amount' => sprintf("%.2f", $return["amount"]),
            'notify_url' => $return['notifyurl'], //异步回调 服务器回调的通知地址
            'return_url' => $return['notifyurl'] . 'return',//同步,为了避免回调时候要拿到notify_url，所以两个都是一样的
            'nonce_str' => $this->getRand(),
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        //程序获取参数
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('CaoChaoTaobaoPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('CaoChaoTaobaoPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['status'] == '0' && $response['result_code'] == '0') {
            header("location: {$response['pay_info']}");
            exit();
        } else {
            Log::record('CaoChaoTaobaoPay  nonce_str= ' . $response['nonce_str'] . '.is failt ', 'ERR', true);
            exit();
        }

        echo $response;
    }

// 异步通知
    public function notifyurl()
    {
//        $response = $_REQUEST;
        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" CaoChaoTaobaoPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，CaoChaoTaobaoPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $response = json_decode($response,true);
        $data = [
            'version' => $response["version"],
            'charset' => $response["charset"],
            'sign_type' => $response["sign_type"],
            'status' => $response["status"],
            'result_code' => $response["result_code"],
            'merchant_id' => $response["merchant_id"],
            'nonce_str' => $response["nonce_str"],
            'sign' => $response["sign"],
            'trade_type' => $response['trade_type'],
            'pay_result' => $response['pay_result'],
            'transaction_id' => $response["transaction_id"],//上游平台订单号
            'out_transaction_id' => $response['out_transaction_id'],//第三方订单id
            'out_trade_no' => $response['out_trade_no'],//我们自己的订单号
            'total_amount' =>$response['total_amount'],
            'real_amount' => $response['real_amount'],//实际支付的金额
            'fee_type' => $response['fee_type'],//币种类
            'time_end' => $response['time_end'],//完成时间
        ];

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if (is_null($response["status"])||is_null($response["result_code"]) || is_null($response["pay_result"] ) ) {
            Log::record('------------------------- 111111111  CaoChaoTaobaoPay回调 orderid= ' . $response['out_trade_no'] . '  状态失败 $response' . json_encode($response), 'ERR', true);
            exit("fail");
        }else if($response["status"] != '0' || $response["result_code"] != '0' || $response["pay_result"] != '0'){
            Log::record('---------22222222222222222222222222222222222222222 CaoChaoTaobaoPay回调 orderid= ' . $response['out_trade_no'] . '  状态失败 $response' . json_encode($response), 'ERR', true);
            exit("fail");
        } else {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
            $publiKey = getKey($response["out_trade_no"]); // 密钥
            $result = $this->verifySign($data, $publiKey);

            if ($result) {
                $orderid = $response["out_trade_no"];//自己的订单号
                $upstream_order_id = $response["transaction_id"];// 上游的订单id,
                $paymoney = $response["real_amount"];// 交易金额
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
                Log::record(' CaoChaoTaobaoPay回调 orderid= ' . $response['out_trade_no'] . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
                exit('fail');
            }
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

    private function getRand()
    {
        $a = range(0, 9);
        for ($i = 0; $i < 16; $i++) {
            $b[] = array_rand($a);
        } // www.yuju100.com
        return var_dump(join("", $b));

    }
//* @Note  生成签名
//* @param $secret   商户密钥
//* @param $data     参与签名的参数
//* @return string
//*/
    private function  filtrfunction($arr){
        if($arr === '' || $arr === null){
            return false;
        }
        return true;
    }
    private function getSign($secret, $data)
    {
        // 去空
        $data = array_filter($data,array($this,"filtrfunction"));
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            $string_a .= $k . "=" . $v . "&";
        }
        //签名步骤二：在string后加入key
        $string_sign_temp = $string_a . "key=" . $secret;
        Log::record('createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
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
        Log::record('verifySign ===== 校验签名的字符串签名  ：' . $sign2, 'ERR', true);

        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}