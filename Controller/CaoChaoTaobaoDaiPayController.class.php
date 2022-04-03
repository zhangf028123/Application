<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;
use Org\SignatureUtil;


class CaoChaoTaobaoDaiPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'CaoChaoTaobaoDaiPay',
            'title' => '曹超淘宝代付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'pay_memberid' => $return['mch_id'],//商户号,
            'pay_orderid' => $return['orderid'], //商户订单号
            'pay_applydate' => date("Y-m-d H:i:s"),  //订单时间
            'pay_bankcode' => $return['appid'],//是	911支付方式
            'pay_notifyurl' => $return['notifyurl'],
            'pay_callbackurl' => $return['notifyurl'] . 'return',
            'pay_amount' => sprintf("%.2f", $return["amount"]),
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data["pay_md5sign"] = $sign;
        $data['pay_productname'] ='团购商品';

        $this->setHtml($return['gateway'], $data);
        die; // 上游跳转

    }
    public function fuck1(){
        $sign = '7C9E2A40138FFA2E962FC05D272ACF0B';//7C9E2A40138FFA2E962FC05D272ACF0B
        Log::record("CaoChaoTaobaoDaiPayfuck1   ".C('private_key'), 'ERR', true);

        $signRsa = SignatureUtil::sign($sign, C('private_key'));
        Log::record("CaoChaoTaobaoDaiPayfuck1signRsa   ".$signRsa, 'ERR', true);
        Log::record("CaoChaoTaobaoDaiPayfuck1public_key   ".C('public_key'), 'ERR', true);

        $data = [
            "signRsa"=>$signRsa,
            'sign'=>$sign,
            ];
        echo json_encode($data).'</br>';
        $Ut=new SignatureUtil();
        if($Ut->verify($sign, $signRsa, C('public_key')) ) {
            echo '验签通过';
        }else{

        } echo '验签失败';
    }

// 异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" CaoChaoTaobaoDaiPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，CaoChaoTaobaoDaiPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $data = [
            "memberid" => $response["memberid"], // 商户ID
            "orderid" =>  $response["orderid"], // 订单号
            "amount" =>  $response["amount"], // 交易金额
            "datetime" =>  $response["datetime"], // 交易时间
            "transaction_id" =>  $response["transaction_id"], // 流水号
            "returncode" => $response["returncode"],
            "sign"=>$response["sign"],
        ];

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if(is_null($response["returncode"])||$response["returncode"] != '00' ){
            Log::record('CaoChaoTaobaoDaiPay回调 orderid= ' . $response['orderid'] . '  状态失败 $response' . json_encode($response), 'ERR', true);
            exit("fail");
        } else {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
            $publiKey = getKey($response["orderid"]); // 密钥
            $result = $this->verifySign($data, $publiKey);

            if ($result) {
                $orderid = $response["orderid"];//自己的订单号
                $upstream_order_id = $response["transaction_id"];// 上游的订单id,
                $paymoney = $response["amount"];// 交易金额
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
                Log::record(' CaoChaoTaobaoDaiPay回调 orderid= ' . $response['out_trade_no'] . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
        $string_sign_temp = $string_a . "fuckkey007=" . $secret;
        Log::record('createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        return strtoupper($sign);
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