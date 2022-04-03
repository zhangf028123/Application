<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class MoShaPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'MoShaPay',
            'title' => '新世界默沙支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'merchant' => $return['mch_id'],//商户号,
            'qrtype' => $return['appid'],//支付类型 固定值：wp微信；ap 支付宝 aph5 支付宝h5
            'customno' => $return['orderid'], //商户订单号
            'money' =>sprintf("%.2f", $return["amount"]),
            'sendtime' => time(),//时间   使用10位数UNIX时间戳
            'notifyurl' => $return['notifyurl'], //异步回调 服务器回调的通知地址
            'backurl'=>"http://www.baidu.com",
            'risklevel' =>2,
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        //程序获取参数
        $this->setHtml($return['gateway'], $data);
        die; // 上游跳转
    }

// 异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
//        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" MoShaPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，MoShaPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $data = [
            'merchant' => $response["merchant"],
            'merchant_money' => $response["merchant_money"],//
            'qrtype'=> $response["qrtype"],//
            'customno' => $response['customno'],//我们自己的订单号
            'sendtime' => $response['sendtime'],//订单时间
            'orderno' => $response['orderno'],//上游订单id
            'money' =>$response['money'],
            'paytime'=>$response['paytime'],//提交的时间
            'state' => $response['state'],//订单状态  1就是成功
            'sign' => $response['sign'],
        ];

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if(is_null($response["state"])||$response["state"] != '1' ){
            Log::record(' MoShaPay回调 orderid= ' . $response['customno'] . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
            exit("fail");
        } else {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
            $orderid = $response["customno"];//自己的订单号
            $upstream_order_id = $response["orderno"];// 上游的订单id,
            $paymoney = $response["money"];// 交易金额
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
                    exit("OK");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            } else {
                Log::record(' MoShaPay回调 orderid= ' . $orderid. '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            if($k != "sign" && $v != ""){
                $string_a = $string_a."&$k=$v";
            }
        }
        //签名步骤二：在string后加入key
        $string_sign_temp =  substr($string_a,1).$secret;
        Log::record('上游签名的字符串，加密之前的,getSign：' .$string_sign_temp , 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtolower($sign);
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