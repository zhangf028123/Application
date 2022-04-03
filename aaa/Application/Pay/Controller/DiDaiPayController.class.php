<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class DiDaiPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $clientip = $_SERVER['REMOTE_ADDR'];
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'DiDaiPay',
            'title' => '滴答支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            "client_id" => $return['mch_id'], //商户ID
            "out_trade_no" => $return['orderid'],   // 商户订单号
            "total_fee" => $return["amount"] * 1 * 100, // 支付金额 单位是分
            "channel_code" => $return['appid'],  //渠道编码
            "notify_url" => $return['notifyurl'],     //支付结果后台回调URL

        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data["sign"] = $sign;
        //程序获取参数
        $response = $this->_post($return['gateway'], $data);
        
        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('DiDaiPay pay url=' . $return['gateway'] . ' response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('DiDaiPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '0' ) {
            header("location: {$response['data']['order_info']}");
            exit();
        } else {
            Log::record('DiDaiPay  orderid  = ' . $return['orderid'] . '.pay failt ', 'ERR', true);
            exit(json_encode($response,JSON_UNESCAPED_UNICODE));
        }


    }
    private function _post($url,$parac){
        $postdata=http_build_query($parac);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }

// 异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" DiDaiPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，DiDaiPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
//status	Integer	订单状态,2表示支付成功,其它都为失败
//order_id	String	平台订单号
//out_trade_no	String	商户订单号
//total_fee	Integer	订单金额,单位是分,且不带小数
//sign	String	签名,加密之后的MD5值,转换为大写
        $data = [
            "order_id" => $response["order_id"], //平台订单号
            "status" => $response["status"], // 订单状态,2表示支付成功,其它都为失败
            "out_trade_no" => $response["out_trade_no"], // 商户订单号
            "total_fee" => $response["total_fee"], //订单金额,单位是分,且不带小数
            "sign" => $response["sign"], //签名,加密之后的MD5值,转换为大写
        ];

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if ($response["status"] == '2') {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
            $publiKey = getKey($response["out_trade_no"]); // 密钥
            $result = $this->verifySign($data, $publiKey);
            if ($result) {
                $orderid = $response["out_trade_no"];//自己的订单号
                $upstream_order_id = $response["order_id"];// 上游的订单id,
                $paymoney =  $_REQUEST["total_fee"]/100;
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
                Log::record(' DiDaiPay回调 orderid= ' . $response['out_trade_no'] . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
                exit('fail');
            }

        } else {
            Log::record('DiDaiPay回调 orderid= ' . $response['mchOrderNo'] . '  状态失败 $response' . json_encode($response), 'ERR', true);
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
        ksort($data);
        reset($data);
        $string_a = '';
        foreach ($data as $key => $val) {//组装参数
            if (strlen($key) && strlen($val)) {
                $string_a = $string_a . $key . "=" . $val . "&";
            }
        }
        //签名步骤二：在string后加入key
        $sign = strtoupper(md5($string_a . "key=" . $secret));  //签名
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