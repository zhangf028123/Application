<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class ChengXinPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'ChengXinPay',
            'title' => '诚信赢天下',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        /** 请求参数 */
        $data = [
            'merchant_id' =>  $return['mch_id'],//商户号,
            'app_id'=>'-',
            'version' => 'V2.0', //版本号，固定值
            'pay_type' => $return['appid'],//是
            'device_type' => 'wap', //	支付设备,电脑:pc，手机:wap
            'request_time' => date("Ymdhms"),// 请求时间
            'nonce_str' => $this->getRand(),//随机字符串，英文或者字母组成10-50位
            'pay_ip' => $_SERVER['REMOTE_ADDR'],//支付IP
            'out_trade_no' => $return['orderid'], //商户订单号
            'amount' => sprintf("%.2f", $return["amount"]),//支付金额，单位元，精确到两位小数
            'notify_url' =>  $return['notifyurl'],//	异步通知地址
        ];

        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        //程序获取参数
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('ChengXinPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('ChengXinPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['status'] == "success" ) {
            header("location: {$response['pay_url']}");
            exit();
        } else {
            Log::record('ChengXinPay  下单失败 is failt '. ' response=' . json_encode($response)  , 'ERR', true);
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
        Log::record(" ChengXinPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，ChengXinPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $response = json_decode($response,true);
        $data = [
            'merchant_id' => $response["merchant_id"],
            'request_time' => $response["request_time"],
            'pay_time' => $response["pay_time"],
            'status' => $response["status"],
            'order_amount' => $response["order_amount"],
            'pay_amount' => $response["pay_amount"],
            'out_trade_no' => $response["out_trade_no"],//我们自己的订单号
            'trade_no' => $response["trade_no"],//上游平台订单号
            'fees' => $response['fees'],//手续费
            'pay_type' => $response['pay_type'],
            'nonce_str' => $response["nonce_str"],
            'remarks' => $response['remarks'],
            'sign' => $response['sign']
        ];
//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if (is_null($response["status"] )|| $response["status"] != "success" ) {
            Log::record('ChengXinPay回调 orderid= ' . $response['out_trade_no'] . '  状态失败 $response' . json_encode($response), 'ERR', true);
            exit("fail");
        }else {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
            $publiKey = getKey($response["out_trade_no"]); // 密钥
            $result = $this->verifySign($data, $publiKey);

            if ($result) {
                $orderid = $response["out_trade_no"];//自己的订单号
                $upstream_order_id = $response["trade_no"];// 上游的订单id,
                $paymoney = $response["pay_amount"];// 交易金额
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
                Log::record(' ChengXinPay回调 orderid= ' . $response['out_trade_no'] . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
        $md5str = "";
        foreach ($data as $key => $val) {
            if ($val != null && $val != "") {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $md5str = rtrim($md5str, "&") . $secret;
        $result = strtoupper(md5($md5str));
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