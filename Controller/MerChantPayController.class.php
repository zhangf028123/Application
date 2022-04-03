<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class MerChantPayController extends PayController
{
    public function Pay($array)
    {
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'MerChantPay',
            'title' => 'MerChant支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);


        $data = [
            'amount' => $return["amount"],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'merchantId' => $return['mch_id'],//平台创建商户分配给商户的应用id
            'notifyUrl' => $return['notifyurl'], //异步回调 服务器回调的通知地址
            'outTradeNo' => $return['orderid'], //商户订单号
            'passageCode' => $return['appid'],//是
            'subject' => 'test', //异步回调 服务器回调的通知地址
            'timestamp' => date('Y-m-d H:i:s'),//发送请求的时间，格式 yyyy-MM-dd HH:mm:ss
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        $response = $this->mypost($return['gateway'], $data);

        Log::record('MerChantPay pay url=' . $return['gateway'] . ' $response=' . json_encode($response), 'INFOR', true);
        $response = json_decode($response, true);

        if (empty($response)) {
            Log::record('MerChantPay  $response is empty ', 'ERR', true);
            exit("error");
        }
        if ($response['code'] == '200') {
            $contentType = I("request.return_type");
            Log::record('MerChantPay-json=' . $contentType, 'INFOR', true);

            $URL = $response['data']['payUrl'];
            if ($contentType == 'json') {
                $data_return = [
                    'result' => 'ok',
                    'url' => $URL,
                ];
                Log::record('MerChantPay-json=' . $return['gateway'] . ' response=' . json_encode($data_return), 'INFOR', true);
                $this->ajaxReturn($data_return);
                exit();
            } else {
                header("location: {$URL}");
                exit();
            }

        } else {
            Log::record('MerChantPay  $response code is failt ', 'ERR', true);
            exit(json_encode($response, JSON_UNESCAPED_UNICODE));
        }
        echo $response;
    }

    private function mypost($url, $data)
    {
        {
            $jsonStr = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($jsonStr)
                )
            );
            $response = curl_exec($ch);
            curl_close($ch);

            return $response;
        }
    }

    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" MerChantPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，MerChantPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }

        $data = [
            "amount" => $response['amount'],
            "body" => $response['body'],
            "merchantId" => $response['merchantId'],
            "notifyTime" => $response['notifyTime'],
            "outTradeNo" => $response['outTradeNo'],//商户订单号
            "passageTradeNo" => $response['passageTradeNo'],
            "realAmount" => $response['realAmount'],
            "sign" => $response['sign'],
            "status" => $response['status'],//0=出码失败，1=等待支付，2=支付完成，3=支付失败，4=执行冲正
            "subject" => $response['subject'],
            "tradeNo" => $response['tradeNo'],//上游的订单号
        ];
        $orderid = $response["outTradeNo"];//自己的订单号
        $upstream_order_id = $response["tradeNo"];// 上游的订单id,
        $paymoney = $response["amount"];// 交易金额


        // 判断上游返回的状态是不是正常的，成功的再去检查签名
        if (is_null($response["status"]) || $response["status"] != '2') {
            Log::record(' MerChantPay orderid= ' . $orderid . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
            exit("fail");
        } else {
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
                    exit("SUCCESS");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            } else {
                Log::record(' MerChantPay orderid= ' . $orderid . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
//* @Note  生成签名
//* @param $secret   商户密钥
//* @param $data     参与签名的参数
//* @return string
//*/
    private function getSign($secret, $data)
    {
        // 去空
        $data = array_filter($data, array($this, "filtrfunction"));
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k => $v) {//组装参数
            $string_a .= $k . "=" . $v . "&";;
        }
        //签名步骤二：在string后加入mch_key
        $string_a = rtrim($string_a, '&');

        $string_sign_temp = $string_a  . $secret;
        Log::record('createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        Log::record('createToSignStr ===== key  ：' . $secret, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtolower($sign);
        return $result;
    }

    private function filtrfunction($arr)
    {
        if ($arr === '' || $arr === null) {
            return false;
        }
        return true;
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