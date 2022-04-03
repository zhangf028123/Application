<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class HongNiaoPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'HongNiaoPay',
            'title' => '雄鸟支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'PayChannelId' =>$return['appid'],//是
            'OrderNo'=> $return['orderid'], //商户订单号
            'Amount'=>$return["amount"],
            "GoodsName"=>"Iphonexyz771",
            'Payer'=>'0',
            'PayerNo'=>'0',
            'PayerAddress'=>'0',
            'Ext'=>'none',
            'CallbackUrl'=>$return['notifyurl'], //异步回调 服务器回调的通知地址,
            'AccessKey'=> $return['mch_id'], //商户号,
            'Timestamp'=>time(),
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data['Sign'] = $sign;
        $response = $this->curlPost($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('HongNiaoPay pay url=' . $return['gateway'] . ' data=' . json_encode($data), 'INFOR', true);
        $response = json_decode($response, true);
        Log::record('HongNiaoPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('HongNiaoPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['Code'] == '0') {
            //Data.PayeeInfo.CashUrl
            header("location: {$response['Data']['PayeeInfo']['CashUrl']}");
            exit();
        } else {
            Log::record('HongNiaoPay  $response code is failt ', 'ERR', true);
            exit(json_encode($response,JSON_UNESCAPED_UNICODE));
        }
        echo $response;
    }

    private function curlPost($url, $data = array())
    {
        $curl = curl_init();//初始化
        curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (preg_match('/^https:\/\//i', $url)) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, array( //改为用JSON格式来提交
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ));
        $result = curl_exec($curl);//执行命令
//        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $request_info = curl_getinfo($curl);
        $http_code = $request_info['http_code'];

        Log::record('HongNiaoPay  $httpCode code is failt '.$http_code, 'ERR', true);

        curl_close($curl);//关闭URL请求
        return $result;
    }
 //异步通知
    public function notifyurl()
    {
//        $response = $_REQUEST;
        $response = file_get_contents("php://input");

        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" HongNiaoPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，HongNiaoPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $response = json_decode($response,true);

        $data = [
            'AccessKey' => $response["AccessKey"],
            'Timestamp'=>$response['Timestamp'],
            'Amount'=>$response['Amount'],
            'Sign'=>$response['Sign'],
            'OrderNo'=>$response['OrderNo'],
            'Status'=>$response['Status'],
            'Ext'=>$response['Ext'],
            'OrderInfoExt'=> $response['OrderInfoExt'],
        ];
        $orderid = $response["OrderNo"];//自己的订单号
        $upstream_order_id = $response["OrderNo"];// 上游的订单id,
        $paymoney = $response["Amount"];// 交易金额

        //       判断上游返回的状态是不是正常的，成功的再去检查签名
        if(is_null($response["Status"])||$response["Status"]!='4' ){
            Log::record(' HongNiaoPay orderid= ' .$orderid  . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
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
                    exit("ok");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            } else {
                Log::record(' HongNiaoPay orderid= ' . $orderid. '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
//        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a='';
        foreach ($data as $k => $v) {//组装参数
            $string_a .=  $k . $v ;
        }
        //签名步骤二：在string后加入mch_key
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
    private function verifySign($data, $secret)
    {
        // 验证参数中是否有签名
        if (!isset($data['Sign']) || !$data['Sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['Sign'];
        unset($data['Sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);
        Log::record('verifySign ===== $sign2  ：' . $sign2, 'ERR', true);

        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}