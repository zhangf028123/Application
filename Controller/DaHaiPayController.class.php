<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class DaHaiPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'DaHaiPay',
            'title' => '大海支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
//        merchant_no	字符串	是	商户号
//out_order_no	字符串	是	商户订单号
//amount	整数	是	交易金额
//pay_type	字符串	是	alipay或wechat
//notify_url	字符串	是	服务器回调的通知地址
//sign	字符串	是	下单签名
        $data = [
            'merchant_no' => $return['mch_id'], //商户号,
            'out_order_no' => $return['orderid'], //商户订单号
            'amount' => $return["amount"],
            'pay_type' => $return["appid"],
            'notify_url' => $return['notifyurl'], //异步回调 服务器回调的通知地址
        ];
        //sign=md5(merchant_no+out_order_no+amount+pay_type+notify_url+商户秘钥)
        $signStr = $data['merchant_no'] . $data['out_order_no'] . $data['amount'] . $data['pay_type']. $data['notify_url'];
        $sign = $this->getSign($signStr, $return["signkey"]);
        $data['sign'] = $sign;
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('DaHaiPay pay url=' . $return['gateway'] . ' data=' . json_encode($data), 'INFOR', true);
        $response = json_decode($response, true);
        Log::record('DaHaiPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('DaHaiPay  $response is empty ', 'ERR', true);
            exit();
        }
//        {"code":1,"msg":"成功","data":{"merchant_no":"xxx","order_no":"xxx","out_order_no":"xxx","amount":"20","pay_type":"wechat","pay_url":"http://xxxx"}}
        if ($response['code'] == '1') {
            header("location: {$response['data']['pay_url']}");
            exit();
        } else {
            Log::record('DaHaiPay  $response code is failt ', 'ERR', true);
            exit(json_encode($response, JSON_UNESCAPED_UNICODE));
        }
        echo $response;
    }



    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" DaHaiPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，DaHaiPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }


//参数名	类型	说明
//order_no	字符串	订单号
//merchant_no	字符串	商户号
//out_order_no	字符串	商户订单号
//amount	字符串	交易金额
//pay_type	字符串	交易类型
//code	字符串	交易结果 1（成功）
//sign	字符串	签名，详见回调签名方式
//
//回调签名方式
//sign = md5(order_no+merchant_no+out_order_no+amount+pay_type+code+商户秘钥);
//
//收到回调后，商户必须同步返回字符串success ,否则平台认为商户没有收到回调，会重复发送回调
        $data = [
            'order_no' => $response["order_no"],
            'merchant_no' => $response['merchant_no'],
            'out_order_no' => $response['out_order_no'],//商户订单号
            'amount' => $response['amount'],
            'pay_type' => $response['pay_type'],
            'code' => $response['code'],
            'sign' => $response['sign'],
        ];
        $orderid = $response["out_order_no"];//自己的订单号
        $upstream_order_id = $response["order_no"];// 上游的订单id,
        $paymoney = $response["amount"];// 交易金额


        //       判断上游返回的状态是不是正常的，成功的再去检查签名
        if (is_null($response["code"]) || $response["code"] != '1') {
            Log::record(' DaHaiPay orderid= ' . $orderid . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
            exit("fail");
        } else {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
            $publiKey = getKey($orderid); // 密钥

            ////sign = md5(order_no+merchant_no+out_order_no+amount+pay_type+code+商户秘钥);
            $signStr = $data['order_no'] . $data['merchant_no'] . $data['out_order_no'] . $data['amount']. $data['pay_type']. $data['code'];
            Log::record('----------------- 上游回调 参与签名的 $signStr= ' .$signStr, 'ERR', true);

            $fxsign = $data['sign'];
            $result = $this->verifySign($signStr, $publiKey, $fxsign);
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
                Log::record(' DaHaiPay orderid= ' . $orderid . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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

    private function getSign($dataString, $secret)
    {
        Log::record('DaHaiPay-acreateToSignStr ===== $string_sign_temp  ：' . $dataString, 'ERR', true);
        Log::record('DaHaiPay-createToSignStr ===== key  ：' . $secret, 'ERR', true);
        //MD5加密
        $sign = md5($dataString . $secret);
        return $sign;
    }


    /**
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
    private function verifySign($signStr, $secret, $fxsign)
    {

        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($signStr, $secret);
        Log::record('verifySign ===== $signStr  ：' . $signStr, 'ERR', true);
        Log::record('verifySign ===== $fxsign  ：' . $fxsign, 'ERR', true);
        Log::record('verifySign ===== $sign2  ：' . $sign2, 'ERR', true);

        if ($fxsign != $sign2) {
            return false;
        }
        return true;
    }


}