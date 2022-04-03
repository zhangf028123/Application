<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class YuLeHuoZhiFuBaoPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'YuLeHuoZhiFuBaoPay',
            'title' => '特猴支付-支付宝',
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
            "ordersn" => $return['orderid'], //商户订单号
            "date"=> date("Y-m-d H:i:s"),
            "paytype" =>'ALIPAY',//	支付方式	否	是	参数：ALIPAY、WXPAY，默认支付宝（ALIPAY）
            "typecode"=>$return['appid'],//通道编码	是	是	在商户中心查询
            "notifyurl" =>  $return['notifyurl'], //异步回调 服务器回调的通知地址
            "callbackurl"=>$return['notifyurl']."/callbackurl",
            "amount" => $return["amount"],
            "is_form" => '1',
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data["sign"] = $sign;
        //程序获取参数
        Log::record('YuLeHuoZhiFuBaoPay pay url=' . $return['gateway'] . ' data= ' . json_encode($data) , 'INFOR', true);
        $this->setHtml($return['gateway'], $data);
        die();

//        Log::record('YuLeHuoZhiFuBaoPay pay url=' . $return['gateway'] . ' response=' . $response , 'INFOR', true);
//
//        $cost_time = $this->msectime() - $start_time;
//        $response = json_decode($response, true);
//        Log::record('YuLeHuoZhiFuBaoPay pay url=' . $return['gateway'] . ' response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
//        if (empty($response)) {
//            Log::record('YuLeHuoZhiFuBaoPay  $response is empty ', 'ERR', true);
//            exit();
//        }
//        if ($response['code'] == '0' ) {
//            header("location: {$response['data']['pay_url']}");
//            exit();
//        } else {
//            Log::record('YuLeHuoZhiFuBaoPay  orderid= ' .  $return['orderid'] . '.is failt ', 'ERR', true);
//            exit();
//        }
    }


// 异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
//        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" YuLeHuoZhiFuBaoPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，YuLeHuoZhiFuBaoPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }

//        $response = json_decode($response,true);
        $data = [
            'mch_id' => $response["mch_id"],
            'out_trade_no' => $response['out_trade_no'],
            'amount' => $response["amount"],
            'transaction_id'=> $response["transaction_id"],
            'date' => $response['date'],
            'code' => $response['code'],
            'sign' => $response['sign'],
        ];
        $orderid = $response["out_trade_no"];//自己的订单号
        $upstream_order_id = $response["transaction_id"];// 上游的订单id,
        $paymoney = $response["amount"];// 交易金额

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if(is_null($response["code"])||$response["code"]!='00' ){
            Log::record(' YuLeHuoZhiFuBaoPay回调 orderid= ' .$orderid  . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
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
                    exit("OK");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            } else {
                Log::record(' YuLeHuoZhiFuBaoPay回调 orderid= ' . $orderid. '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
        // 去空
        $data = array_filter($data,array($this,"filtrfunction"));
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            $string_a .= $k . "=" . $v . "&";
        }
//        $string_a = rtrim($string_a, '&');

        //签名步骤二：在string后加入key
        $string_sign_temp = $string_a .'key='.$secret;
        Log::record('YuLeHuoZhiFuBaoPay createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
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