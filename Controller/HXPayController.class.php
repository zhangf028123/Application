<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class HXPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'HXPay',
            'title' => 'HX支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'fxid' => $return['mch_id'], //商户号,
            'fxddh' => $return['orderid'], //商户订单号
            'fxfee' => $return["amount"],
            'fxattch' => $return['orderid'],
            'fxnotifyurl' => $return['notifyurl'], //异步回调 服务器回调的通知地址
            'fxbackurl' => 'http://117.24.12.119:39001',
            'fxpay' => $return['appid'],//是
            'device' => $this->get_device_type(),
            'fxip' => $_SERVER['REMOTE_ADDR'],
        ];
        $signStr = $data['fxid'] . $data['fxddh'] . $data['fxfee'] . $data['fxnotifyurl'];
        $sign = $this->getSign($signStr, $return["signkey"]);
        $data['fxsign'] = $sign;
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('HXPay pay url=' . $return['gateway'] . ' data=' . json_encode($data), 'INFOR', true);
        $response = json_decode($response, true);
        Log::record('HXPay pay url=' . $return['gateway'] . ' response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('HXPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['status'] == '1') {
            header("location: {$response['payurl']}");
            exit();
        } else {
            Log::record('HXPay  $response code is failt ', 'ERR', true);
            exit(json_encode($response, JSON_UNESCAPED_UNICODE));
        }
    }

    public function get_device_type()
    {
        //Windows,Macintosh,iPad,iPhone,Android
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (strpos($agent, 'iphone')) {
            $type = 'iPhone';
        } else if (strpos($agent, 'ipad')) {
            $type = 'iPad';
        } else if (strpos($agent, 'android')) {
            $type = 'Android';
        } else {
            $type = 'Windows';
        }
        return $type;
    }

    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" HXPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，HXPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }

        $data = [
            'fxid' => $response["fxid"],
            'fxddh' => $response['fxddh'],//我们订单
            'fxorder' => $response['fxorder'],
            'fxdesc' => $response['fxdesc'],//上游的订单id
            'fxfee' => $response['fxfee'],
            'fxattch' => $response['fxattch'],//状态00表示成功，其它表示失败
            'fxstatus' => $response['fxstatus'],
            'fxtime' => $response['fxtime'],
            'fxsign' => $response['fxsign'],
        ];
        $orderid = $response["fxddh"];//自己的订单号
        $upstream_order_id = $response["fxorder"];// 上游的订单id,
        $paymoney = $response["fxfee"];// 交易金额


        //       判断上游返回的状态是不是正常的，成功的再去检查签名
        if (is_null($response["fxstatus"]) || $response["fxstatus"] != '1') {
            Log::record('HXPay orderid= ' . $orderid . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
            exit("fail");
        } else {
            $publiKey = getKey($orderid); // 密钥
            $signStr = $data['fxstatus'] . $data['fxid'] . $data['fxddh'] . $data['fxfee'];
            Log::record('----------------- 上游回调 参与签名的 $signStr= ' . $signStr, 'ERR', true);
            $fxsign = $data['fxsign'];
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
                Log::record(' HXPay orderid= ' . $orderid . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
        Log::record('createToSignStr ===== $string_sign_temp  ：' . $dataString, 'ERR', true);
        Log::record('createToSignStr ===== key  ：' . $secret, 'ERR', true);
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