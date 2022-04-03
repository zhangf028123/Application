<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class HeShengYuanPayController extends PayController
{
    public function Pay($array)
    {
        $clientip = $_SERVER['REMOTE_ADDR'];
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'HeShengYuanPay',
            'title' => '合盛元',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            "mch_id" => $return['mch_id'],//商户号,
            "pass_code" => $return['appid'],
            "subject" => '钻石',
            "out_trade_no" => $return['orderid'], //商户订单号
            "amount" => $return["amount"],
            "client_ip" => $clientip,
            "notify_url" =>  $return['notifyurl'], //异步回调 服务器回调的通知地址
            "timestamp"=> date("Y-m-d H:i:s"),
        ];
        Log::record('HeShengYuanPay  data=' . json_encode($data) , 'INFOR', true);

        $sign = $this->getSign($return["signkey"], $data);
        $data["sign"] = $sign;
        //程序获取参数
        $response = $this->mypost($return['gateway'], $data);
        Log::record('HeShengYuanPay pay url=' . $return['gateway'] . ' response=' . $response , 'INFOR', true);

        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('HeShengYuanPay pay url=' . $return['gateway'] . ' response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('HeShengYuanPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '0' ) {
            header("location: {$response['data']['pay_url']}");
            exit();
        } else {
            Log::record('HeShengYuanPay  orderid= ' .  $return['orderid'] . '.is failt ', 'ERR', true);
            exit();
        }
    }
    private function mypost($url,$data)
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
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $response;
        }
    }


// 异步通知
    public function notifyurl()
    {

//        $response = $_REQUEST;
        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" HeShengYuanPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，HeShengYuanPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $response = json_decode($response,true);
        $data = [
            'mch_id' => $response["mch_id"],
            'trade_no' => $response['trade_no'],
            'out_trade_no' => $response["out_trade_no"],
            'original_trade_no'=> $response["original_trade_no"],
            'money' => $response['money'],
            'notify_time' => $response['notify_time'],
            'subject' => $response['subject'],
            'body'=> $response['body'],
            'status' => $response['status'],
            'sign' => $response['sign'],
        ];
        $orderid = $response["out_trade_no"];//自己的订单号
        $upstream_order_id = $response["trade_no"];// 上游的订单id,
        $paymoney = $response["money"];// 交易金额

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if(is_null($response["status"])||$response["status"]!='2' ){
            Log::record(' HeShengYuanPay回调 orderid= ' .$orderid  . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
            exit("fail");
        } else {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
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
                Log::record(' HeShengYuanPay回调 orderid= ' . $orderid. '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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


    private function getSign($secret, $data)
    {
//        appid=wxd930ea5d5a258f4f&body=test&device_info=1000&mch_id=10000100&nonce_str=ibuaiVcKdpRxkhJA"
//        amount=100&client_ip=47.243.69.135&mch_id=1021&notify_url=http://8.218.51.235:80/Pay_HeShengYuanPay_notifyurl.html&out_trade_no=HX20211229200235989851&pass_code=321&subject=钻石&timestamp=2021-12-29 20:02:35c28ad87285364d62a585f35470976ab4
        // 去空
        $data = array_filter($data,array($this,"filtrfunction"));
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            $string_a .= $k . "=" . $v . "&";
        }
        $string_a = rtrim($string_a, '&');

        //签名步骤二：在string后加入key
        $string_sign_temp = $string_a .$secret;
        Log::record('HeShengYuanPay createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        return strtoupper($sign);
    }
    private function  filtrfunction($arr){
        if($arr === '' || $arr === null){
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
        Log::record('verifySign ===== 校验签名的字符串签名  ：' . $sign2, 'ERR', true);
        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}