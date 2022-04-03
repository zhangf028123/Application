<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class OneBasePayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'OneBasePay',
            'title' => '上游Wap',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);


//发起交易：
//mch_id		商户id
//order_type	支付通道类型
//out_trade_no	商户订单号
//total_fee	订单金额(元)
//body		商品名称   *只支持中文、英文、数字（不支持任何符号）
//notify_url	通知回调网址
//return_url	支付完成返回网址
//authCode	授权码(可空，紧扫码付需要)
//sign		签名

        $data = [
            'mch_id' =>   intval($return['mch_id']), //商户号,
            'order_type' => $return['appid'],//是
            'out_trade_no'=> $return['orderid'], //商户订单号
            'total_fee' =>$return["amount"],
        ];
        //        $sign=strtolower(md5($mch_id.'|'.$order_type.'|'.$out_trade_no.'|'.$total_fee.'|'.$key));

        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        $data['notify_url']= $return['notifyurl'];//异步回调 服务器回调的通知地址
        $data['return_url']='http://117.24.12.119:39001';
        $data['body']="测试";

        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('OneBasePay pay url=' . $return['gateway'] . ' data=' . json_encode($data), 'INFOR', true);
        $response = json_decode($response, true);
        Log::record('OneBasePay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('OneBasePay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '0') {
            header("location: {$response['pay_url']}");
            exit();
        } else {
            Log::record('OneBasePay  $response code is failt ', 'ERR', true);
            exit();
        }
        echo $response;
    }
 //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" OneBasePay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，OneBasePay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
//mch_id		商户id
//order_type	支付通道类型
//out_trade_no	商户订单号
//orderid		平台订单号(只作为平台记录，不加入签名算法)
//total_fee	支付金额(元)
//sign		签名
        $data = [
            'mch_id' => $response['mch_id'], //商户号,
            'order_type' => $response['order_type'],//是
            'out_trade_no'=> $response['out_trade_no'], //商户订单号
            'total_fee' =>$response["total_fee"],
        ];
        $orderid = $_REQUEST["out_trade_no"];//自己的订单号
        $upstream_order_id = $_REQUEST["orderid"];// 上游的订单id,
        $paymoney = $_REQUEST["total_fee"];// 交易金额

        $publiKey = getKey($orderid); // 密钥
        $result_sign= $response['sign'];
        $result = $this->verifySign($data, $publiKey,$result_sign);
        if ($result) {
            try {
                $Order = M("Order");
                $o = $Order->where(['pay_orderid' => $orderid])->find();
                if (!$o) {
                    Log::record('上游wap回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                    exit('error:order not fount' . $orderid);
                }
                $pay_amount = $o['pay_amount'];
//                $diff = bccomp($paymoney, $pay_amount, 2)==0 ?ture:false;
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
            Log::record('OneBasePay error:check sign Fail!', 'ERR', true);
            exit('error:check sign Fail!');
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
//* @Note  生成签名
//* @param $secret   商户密钥
//* @param $data     参与签名的参数
//* @return string
//*/
    private function getSign($secret, $data)
    {
        $string_a='';
//        $sign=strtolower(md5($mch_id.'|'.$order_type.'|'.$out_trade_no.'|'.$total_fee.'|'.$key));


        foreach ($data as $k => $v) {//组装参数
            $string_a .=  $v .'|' ;;

        }
        //签名步骤二：
        $string_sign_temp = $string_a . $secret;
        Log::record('createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        Log::record('createToSignStr ===== key  ：' . $secret, 'ERR', true);
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
    private function verifySign($data, $secret,$result_sign)
    {

        // 验证参数中是否有签名
        if ( empty($result_sign) ) {
            return false;
        }
        // 要验证的签名串
        $sign = $result_sign;

        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);

        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}