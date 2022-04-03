<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class HanYiPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'HanYiPay',
            'title' => '韩艺支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'parter' => $return['mch_id'],//商户号,
            'type'=> $return['appid'],
            'value' => sprintf("%.2f", $return["amount"]),
            'orderid' => $return['orderid'], //商户订单号
            'callbackurl' => $return['notifyurl'], //异步回调 服务器回调的通知地址
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        Log::record('HanYiPay pay url=' . $return['gateway'] . ' data=' . json_encode($data) , 'INFOR', true);

        //程序获取参数
        $this->setGetHtml($return['gateway'], $data);
        die; // 上游跳转
//        $response =
//        Log::record('HanYiPay pay url=' . $return['gateway'] . ' response=' . $response , 'INFOR', true);
//
//        $cost_time = $this->msectime() - $start_time;
//        $response = json_decode($response, true);
//        Log::record('HanYiPay pay url=' . $return['gateway'] . ' response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
//        if (empty($response)) {
//            Log::record('HanYiPay  $response is empty ', 'ERR', true);
//            exit();
//        }
//        if ($response['code'] == '1' ) {
//            header("location: {$response['url']}");
//            exit();
//        } else {
//            Log::record('HanYiPay  nonce_str= ' .  $return['orderid'] . '.is failt ', 'ERR', true);
//            exit();
//        }
    }
    /// 自动post一个表单
    private function setGetHtml($tjurl, $arraystr)
    {
        $str = '<form id="Form1" name="Form1" method="get" action="' . $tjurl . '">';
        foreach ($arraystr as $key => $val) {
            $str .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $str .= '</form>';
        $str .= '<script>';
        $str .= 'document.Form1.submit();';
        $str .= '</script>';
        exit($str);
    }
    /**
     * curl方法
     * @param $url
     * @return mixed
     */
    private function httpGet($url, $postData='') {
        Log::record('HanYiPay  $postDat=  '.http_build_query($postData), 'ERR', true);
        $restUrl=$url .'?'.http_build_query($postData);
        Log::record('HanYiPay  $restUrl= ' . $restUrl, '  ERR', true);


//        $ch = curl_init();
        $ch = curl_init((string)$restUrl);
//        curl_setopt($ch, CURLOPT_URL, $restUrl)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30 );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;

    }

// 异步通知
    public function notifyurl()
    {

        $response = $_REQUEST;
//        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" HanYiPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，HanYiPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $data = [
            'orderid' => $response["orderid"],
            'opstate' => $response['opstate'],//0:支付成功，非0为支付失败
            'ovalue' => $response["ovalue"],
            'sign' => $response['sign'],
        ];
        $orderid = $response["orderid"];//自己的订单号
        $upstream_order_id = $response["sysorderid"];// 上游的订单id,
        $paymoney = $response["ovalue"];// 交易金额

//       判断上游返回的状态是不是正常的，成功的再去检查签名
        if(is_null($response["opstate"])||$response["opstate"]!='0' ){
            Log::record(' HanYiPay回调 orderid= ' .$orderid  . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
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

                   exit("opstate=0");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            } else {
                Log::record(' HanYiPay回调 orderid= ' . $orderid. '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
//        parter={}&type={}&value={}&orderid={}&callbackurl={}key其中，key为商户签名。
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            $string_a .= $k . "=" . $v . "&";
        }
        //签名步骤二：在string后加入key
        $string_a= substr($string_a, 0, -1);
        $string_sign_temp = $string_a . $secret;
        Log::record('HanYiPay createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为小写
        return strtolower($sign);
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

        //---------start 回调的签名方式
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            $string_a .= $k . "=" . $v . "&";
        }
        //签名步骤二：在string后加入key
        $string_a= substr($string_a, 0, -1);
        $string_sign_temp = $string_a . $secret;
        Log::record('HanYiPay createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为小写
        $sign2 =strtolower($sign);

        //---------end 回调的签名方式

        Log::record('verifySign ===== 校验签名的字符串签名  ：' . $sign2, 'ERR', true);
        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}