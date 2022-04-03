<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class YingYiPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'YingYiPay',
            'title' => '银翼支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        Log::record('YingYiPay pay $agent=' . $agent  , 'INFOR', true);

        $data = [
            'pay_memberid' => $return['mch_id'],//商户号,
            'pay_orderid'=>$return['orderid'], //商户订单号cus_order_no
            'pay_tradetype'=>$return['appid'], //商户订单号cus_order_no
            'pay_amount' => intval($return["amount"]*100), // 元转分
            'pay_callbackurl'=> $return['notifyurl'],
            'pay_turnyurl'=>$return['notifyurl'].'/tureball',
            'pay_productname'=>'测试奢侈品',

        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data['signature'] = $sign;
        $response = HttpClient::post($return['gateway'], $data);
        Log::record('YingYiPay pay url=' . $return['gateway'] . ' response=' . $response , 'INFOR', true);

        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('YingYiPay pay url=' . $return['gateway'] . ' response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('YingYiPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '11' ) {
            header("location: {$response['codeUrl']}");
            exit();
        } else {
            Log::record('YingYiPay  nonce_str= ' .  $return['orderid'] . '.is failt ', 'ERR', true);
            exit();
        }
    }



// 异步通知
    public function notifyurl()
    {

        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" YingYiPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，YingYiPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }

        $data = [
            'code' => $response['code'],
            'transactionid' => $response["transactionid"],//上游的订单id
            'systemorderid'=>$response['systemorderid'],
            'orderno' => $response['orderno'],//我们自己的订单号
            'bankaccount' => $response["bankaccount"],
            'merno'=> $response["merno"],
            'transamt' => $response['transamt'], //支付金额
            'timeend' => $response['timeend'],
            'banktype'=>$response['banktype'],
            'productid'=>$response['productid'],
            'signature' => $response['signature'],
        ];
        $orderid = $response["orderno"];//自己的订单号
        $upstream_order_id = $response["transactionid"];// 上游的订单id,
        $paymoney = number_format($response["merno"]/100, 2);// 交易金额

//       判断上游返回的状态是不是正常的，成功的再去检查签名
         if(!is_null($response["code"])&&$response["code"]=='11'){
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
                    exit("success");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            } else {
                Log::record(' YingYiPay回调 orderid= ' . $orderid. '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
                exit('fail');
            }
        }else{
            Log::record(' YingYiPay回调 orderid= ' .$orderid  . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
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
        // 去空
        $data = array_filter($data,array($this,"filtrfunction"));
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            $string_a .= $k . "=" . $v . "&";
        }
        //签名步骤二：在string后加入key
        $string_sign_temp = $string_a . 'key='.$secret;
        Log::record('YingYiPaycreateToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
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
        if (!isset($data['signature']) || !$data['signature']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['signature'];
        unset($data['signature']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);
        Log::record('YingYiPayverifySign ===== 校验签名的字符串签名  ：' . $sign2, 'ERR', true);
        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}