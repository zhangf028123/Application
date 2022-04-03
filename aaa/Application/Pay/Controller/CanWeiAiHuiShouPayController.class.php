<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class CanWeiAiHuiShouPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');
        $parameter = [
            'code' => 'CanWeiAiHuiShouPay',
            'title' => '常威爱回收支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            "id" => $return['mch_id'], //商户ID
            "out_order_sn" => $return['orderid'],   // 商户订单号
            "notify_url" => $return['notifyurl'],     //支付结果后台回调URL
            "name" => "football",
            "total_fee" => sprintf("%.2f", $return["amount"]), // 支付金额
            "channel" => $return['appid'],  //渠道编码
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data["sign"] = $sign;
        //程序获取参数
        $response = $this->_post($return['gateway'], $data);

        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('CanWeiAiHuiShouPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('CanWeiAiHuiShouPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '1') {
            $contentType = I("request.content_type");
            if ($contentType == 'json') {
                $data_return = [
                    'result' => 'ok',
                    'url' => $response['url'],
                ];
                Log::record('CanWeiAiHuiShouPay-json=' . $return['gateway'] . 'response=' . json_encode($return) , 'INFOR', true);
                $this->ajaxReturn($data_return);
                exit();
            }else{
                header("location: {$response['url']}");
                exit();
            }

        } else {
            Log::record('CanWeiAiHuiShouPay  orderid  = ' . $return['orderid'] . '.pay failt ', 'ERR', true);
            exit(json_encode($response, JSON_UNESCAPED_UNICODE));
        }


    }

    private function _post($url, $parac)
    {
        $postdata = http_build_query($parac);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,));
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return $result;
    }

// 异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
//        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" CanWeiAiHuiShouPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，CanWeiAiHuiShouPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
//        $response = json_decode($response, true);

        $data = [
            "id" => $response["id"],
            "order_sn" => $response["order_sn"],//上游的订单号
            "out_order_sn" => $response["out_order_sn"], //商户订单号
            "mch_id" => $response["mch_id"],
            "name" => $response["name"], //
            "total_fee" => $response['total_fee'],
            "channel" => $response['channel'],
            "status" => $response["status"], //支付状态 1 已支付 0 未支付
            "create_time" => $response["create_time"],
            "pay_time" => $response["pay_time"],
            "sign" => $response["sign"],
        ];

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if ($response["status"] == '1') {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
            $orderid = $response["out_order_sn"];//自己的订单号
            $upstream_order_id = $response["order_sn"];// 上游的订单id,
            $paymoney = $response["total_fee"];

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
                    header('Content-Type:application/json; charset=utf-8');
                    exit(json_encode(array('code' => '1'), JSON_UNESCAPED_UNICODE));
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            } else {
                Log::record(' CanWeiAiHuiShouPay回调 orderid= ' . $response['out_trade_no'] . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
                exit('fail');
            }

        } else {
            Log::record('CanWeiAiHuiShouPay回调 orderid= ' . $response['mchOrderNo'] . '  状态失败 $response' . json_encode($response), 'ERR', true);
            exit("fail");
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


    private function getSign($secret, $data)
    {
        //签名步骤一：按字典序排序参数
        $data = array_filter($data);
        ksort($data);
        reset($data);
        $string_a = '';
        foreach ($data as $key => $val) {//组装参数
            if (strlen($key) && strlen($val)) {
                $string_a = $string_a . $key . "=" . $val . "&";
            }
        }
        //签名步骤二：在string后加入key
        $string_a = trim($string_a, '&');
        $sign = md5($string_a . $secret);  //签名
        return $sign;
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