<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class BaiLeMenPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $clientip = $_SERVER['REMOTE_ADDR'];
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'BaiLeMenPay',
            'title' => '百乐门支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            "mchId" => $return['mch_id'], //商户ID
            "appId" =>"",  //商户应用ID  ????
            "productId" => $return['appid'],  //支付产品ID
            "mchOrderNo" => $return['orderid'],   // 商户订单号
            "currency" => 'cny',  //币种
            "amount" => $return["amount"] * 1 * 100, // 支付金额 单位是分
            "returnUrl" => 'http://localhost/html/return_page.html',     //支付结果前端跳转URL
            "notifyUrl" => $return['notifyurl'],     //支付结果后台回调URL
            "subject" => '网络购物',     //商品主题
            "body" => '网络购物',     //商品描述信息
            "reqTime" => date("YmdHis"),     //请求时间, 格式yyyyMMddHHmmss
            "version" => '1.0',     //版本号, 固定参数1.0
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data["sign"] = $sign;
        //程序获取参数
        $response = HttpClient::post($return['gateway'], $data);
        
        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('BaiLeMenPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('BaiLeMenPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['retCode'] == '0' ) {
            header("location: {$response['payJumpUrl']}");
            exit();
        } else {
            Log::record('BaiLeMenPay  orderid  = ' . $return['orderid'] . '.pay failt ', 'ERR', true);
            exit();
        }


    }

// 异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" BaiLeMenPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，BaiLeMenPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $data = [
            "payOrderId" => $response["payOrderId"], //支付中心生成的订单号    上游的id//
            "mchId" => $response["mchId"], // 商户ID
            "appId" => $response["appId"], // 商户应用ID
            "productId" => $response["productId"], // 支付产品ID
            "mchOrderNo" => $response["mchOrderNo"], // 商户订单号w我们的
            "amount" => $response["amount"],//支付金额  分
            "income" => $response["income"],//用户实际付款的金额  分
            "status" => $response["status"],//支付状态,-2:订单已关闭,0-订单生成,1-支付中,2-支付成功,3-业务处理完成,4-已退款（2和3都表示支付成功,3表示支付平台回调商户且返回成功后的状态）
            "paySuccTime" => $response["paySuccTime"],//支付成功时间  毫秒
            "backType" => $response["backType"],//通知类型
            "reqTime" => $response["reqTime"],//请求时间通知
            "channelOrderNo" => $response["channelOrderNo"],//渠道订单id
            "sign" => $response["sign"],
        ];

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if (!is_null($response["status"]) &&($response["status"] == '2'||$response["status"] == '3')) {
            Log::record('----------------- 上游回调 参与签名的 data= ' . json_encode($data), 'ERR', true);
            $publiKey = getKey($response["mchOrderNo"]); // 密钥
            $result = $this->verifySign($data, $publiKey);
            if ($result) {
                $orderid = $response["mchOrderNo"];//自己的订单号
                $upstream_order_id = $response["payOrderId"];// 上游的订单id,
                $paymoney = number_format($response['amount'],2);;// 交易金额
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
                Log::record(' BaiLeMenPay回调 orderid= ' . $response['out_trade_no'] . '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
                exit('fail');
            }

        } else {
            Log::record('BaiLeMenPay回调 orderid= ' . $response['mchOrderNo'] . '  状态失败 $response' . json_encode($response), 'ERR', true);
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