<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class JianXingPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'JianXingPay',
            'title' => '匠心支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'version'=>'3.0',
            'method'=>'Gt.online.interface',
            'partner' => $return['mch_id'], //商户号,
            'banktype'=>$return["appid"],
            'paymoney' => number_format($return["amount"], 2),
            'ordernumber'=> $return['orderid'], //商户订单号
            'callbackurl' => $return['notifyurl'], //异步回调 服务器回调的通知地址
        ];
 //32位小写MD5签名值。计算方法:version={0}&method={1}&partner={2}&banktype={3}&paymoney={4}&ordernumber={5}&callbackurl={6}token 其中，token在到商户后台获取
        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        $data['hrefbackurl'] =  $return['notifyurl'].'tetet.html';
        $data['notreturnpage']='true';
//8	hrefbackurl	下行同步通知地址	string	必填	否	下行同步通知过程的返回地址(在支付完成后系统将会跳转到的商户系统连接地址)。 注：若提交值无该参数，或者该参数值为空，则在支付完成后，系统将不会跳转到商户系统，用户将停留在系统提示支付成功的页面。
//10	sign	MD5签名	string	必填	否
//32位小写MD5签名值。计算方法:version={0}&method={1}&partner={2}&banktype={3}&paymoney={4}&ordernumber={5}&callbackurl={6}token 其中，token在到商户后台获取
//11	notreturnpage	不返回支付页面	boolean	必填	否	是否不返回支付页面, 默认为false, 为true时会返回json格式的信息
//
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('JianXingPay pay url=' . $return['gateway'] . ' data=' . json_encode($data), 'INFOR', true);
        $response = json_decode($response, true);
        Log::record('JianXingPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('JianXingPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '0') {
            header("location: {$response['data']['payUrl']}");
            exit();
        } else {
            Log::record('JianXingPay  $response code is failt ', 'ERR', true);
            exit(json_encode($response,JSON_UNESCAPED_UNICODE));
        }
        echo $response;
    }
    private function _post($url,$parac){
        $postdata=http_build_query($parac);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }
 //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" JianXingPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，JianXingPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
//7	sign	MD5签名	string	否	32位小写MD5签名值。计算方法：partner={}&ordernumber={}&orderstatus={}&paymoney={}token 其中,token为商户后台获取的
        $data = [
            'partner' => $response["partner"],
            'ordernumber'=>$response['ordernumber'],
            'orderstatus'=>$response['orderstatus'],
            'paymoney'=>$response['paymoney'],
            'sign'=>$response['sign'],
        ];
        $orderid = $response["ordernumber"];//自己的订单号
        $upstream_order_id = $response["sysnumber"];// 上游的订单id,
        $paymoney =$response["paymoney"];// 交易金额

        //       判断上游返回的状态是不是正常的，成功的再去检查签名
        if(is_null($response["orderstatus"])||$response["orderstatus"]!='1' ){
            Log::record(' JianXingPay orderid= ' .$orderid  . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
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
                Log::record(' JianXingPay orderid= ' . $orderid. '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
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
            $string_a .=  $k . "=" . $v . "&";;
        }
        //签名步骤二：在string后加入mch_key
        $string_a = substr($string_a, 0, count($string_a) - 2);
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
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);
        Log::record('verifySign ===== $sign2  ：' . $sign2, 'ERR', true);

        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}