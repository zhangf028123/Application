<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class XiaoWaPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'XiaoWaPay',
            'title' => '小蛙支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        $data_context= [
            'pay_type' => $return['appid'],//是	支付方式
            'merchant_no' => $return['mch_id'],//商户号,
            'business_no' => $return['orderid'], //商户订单号
            'amount' => sprintf("%.2f", $return["amount"]),
            'notify_url' => $return['notifyurl'], //异步回调 服务器回调的通知地址
        ];
        $sign = $this->getSign($return["signkey"], $data_context);
        $ywu_str=json_encode($data_context);
//        封装到content  sign
        $data['context']=base64_encode($ywu_str);
        $data['sign']=$sign;
        //程序获取参数
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('XiaoWaPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('XiaoWaPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['status'] == '200') {
            header("location: {$response['data']}");
            exit();
        } else {
            Log::record('XiaoWaPay  status = ' . $response['message'] . '.is failt ', 'ERR', true);
            exit();
        }

        echo $response;
    }

// 异步通知
    public function notifyurl()
    {
        $response_data = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XiaoWaPay notifyurl \$response=" . json_encode($response_data), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，XiaoWaPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
//        **
//        **//
        $content_data_str   = base64_decode($response_data["context"]);
//        $response=$content_data_str;
        $response=[];
        try{
            $response= json_decode($content_data_str,true);// 对JSON数据进行解码，转换为PHP变量
            Log::record("=解压的东西===== XiaoWaPay \$response= ". json_encode($response), 'ERR', true);
        }catch (Exception $e){
            Log::record("******************解压的东西********** XiaoWaPay \$response= ". json_encode($response), 'ERR', true);

        }

        $data = [
            'merchant_no' => $response["merchant_no"],
            'business_no' => $response["business_no"],//自己的订单
            'order_no' => $response["order_no"],//上游的订单id
            'order_status' => $response["order_status"],
            'pay_type' => $response["pay_type"],
            'amount' => $response['amount'],
            'real_amount'=> $response['real_amount'],
            'sign'=>$response_data["sign"],
        ];
        Log::record('XiaoWaPay上游回调参与签名的data= ' . json_encode($data), 'ERR', true);


//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if (empty($response["order_status"]) ||  $response["order_status"] != '2' ) {
            Log::record(' XiaoWaPay回调 orderid= ' . $response['out_trade_no'] . '  状态失败 $response' . json_encode($response), 'ERR', true);
            exit("fail");
        } else {
//            Log::record('上游回调 参与签名的data= ' . json_encode($data), 'ERR', true);
            $publiKey = getKey($response["business_no"]); // 密钥
            $result = $this->verifySign($data, $publiKey);

            if ($result) {
                $orderid =$response["business_no"];//自己的订单
                $upstream_order_id = $response["order_no"];//上游的订单id
                $paymoney =$response['real_amount'];// 交易金额
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
                Log::record(' XiaoWaPay回调 orderid= ' . $response['out_trade_no'] . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
    private function getSign($secret, $data)
    {
        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            $string_a .= $k . "=" . $v . "&";
        }
        //签名步骤二：在string后加入key
        $string_sign_temp = $string_a . "key=" . $secret;
        Log::record('createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
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