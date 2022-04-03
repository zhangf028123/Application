<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class ZhongJiController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');
        $parameter = [
            'code' => 'ZhongJi',
            'title' => '终极商城',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        Log::record('ZhongJiController  ==== parameter    ' . json_encode($parameter), 'ERR', true);
        $return = $this->orderadd($parameter);
        //$return['appid'], //   什么用途的
        $data = [
            'api_id' => $return['mch_id'], //
            'record' => $return['orderid'],// ,附加参数（可传入您网站的订单号或用户名等唯一参数）
            'money' => sprintf("%.2f", $return["amount"]), //充值金额（注意：php使用 sprintf("%.2f",金额) 强制转换2位小数后提交）
        ];
        $data['sign'] = $this->getSign($return["signkey"], $data);
        $data['refer'] = 'http://117.24.12.119:39001'; // 同步回调网址（当支付成功或
        $data['notify_url'] = $return['notifyurl'];
        $data['mid'] = "";
        Log::record('ZhongJi pay url=' . $return['gateway'] . ' data=' . json_encode($data) , 'ERR', true);

        //表单提交
        $this->setHtml($return['gateway'], $data);
    }


    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" ZhongJi \$ response=" . json_encode($response) . " time= " . date('Y-m-d h:i:s', time()), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，ZhongJi notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $publiKey = getKey($response["record"]); // 密钥
        $data = [
            'record' => I("request.record"),
            'money' => sprintf("%.2f", I("request.money")),
        ];
        $reture_key = $_REQUEST["key"];
        if ($reture_key === $publiKey) {
            try {
                $Order = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["record"]])->find();
                if (!$o) {
                    Log::record('ZhongJi回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                    exit('error:order not fount' . $_REQUEST["record"]);
                }
                $data["api_id"] = $o['memberid'];
                //memberid  商户的id
                $result = $this->_verify($data, $publiKey);
                if ($result) {
                    $pay_amount = $o['pay_amount'];
                    $paymoney = number_format($_REQUEST["money"], 2);
                    $diff = $paymoney - $pay_amount;
                    if ($diff <= -1 || $diff >= 1) { // 允许误差一块钱
                        Log::record("ZhongJi回调失败,金额不等：{$response['money'] } != {$pay_amount}," . json_encode($response), 'ERR', true);
                        exit('error: amount error!');
                    }
                    $old_order = $Order->where(['upstream_order' => $response['order']])->find();
                    if ($old_order && $old_order['pay_orderid'] != $response["order"]) {
                        Log::record("ZhongJi回调失败,重复流水号  ：" . json_encode($response) . '旧订单号' . $old_order['pay_orderid'], 'ERR', true);
                        //die("not ok2");
                    }
                    $Order->where(['pay_orderid' => $response["record"]])->save(['upstream_order' => $response['order']]);
                    $this->EditMoney($response['record'], '', 0);
                    exit("success");
                } else {
                    Log::record('ZhongJi error---:check sign Fail! record= ' . $data['record'], 'ERR', true);
                    exit("success");
                }
            } catch (Exception $e) {
                Log::record('ZhongJi回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                exit("Exception");
            }
        } else {
            Log::record('ZhongJi回调失败,发生异常：', 'ERR', true);
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

    private function _verify($requestarray, $md5key)
    {
        $md5keysignstr = $this->getSign($md5key, $requestarray);
        $pay_md5sign = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

    private function getSign($secret, $data)
    {
        ksort($data);
        $str1 = '';
        foreach ($data as $k => $v) {//组装参数
            $str1 .= '&' . $k . "=" . $v;
        }
        $temp_str = trim($str1) . $secret;
        $result = md5($temp_str);//md5加密参数
        Log::record('ZhongJi 签名,$temp_str= ' . $temp_str . ' $result= ' . $result, 'ERR', true);

        return $result;
    }


}