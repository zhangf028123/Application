<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class GloryPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'GloryPay',
            'title' => 'GloryPay支付',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);

//        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
//        Log::record('GloryPay pay $agent=' . $agent  , 'INFOR', true);


        $data = [
            'mchId' => $return['mch_id'],//商户号,
            'appId'=>'037b8d80af904ab9b2e9530c8997776d',
            'productId'=>$return['appid'],//'6002',//产品id
            'mchOrderNo' => $return['orderid'], //商户订单号cus_order_no
            'amount'=> intval($return["amount"]*100), // 元转分
            'currency'=>'cny',
            'device'=>'ios10.3.1',//客户端设备
            'notifyUrl' => $return['notifyurl'], //异步回调 服务器回调的通知地址
            'subject'=>'测试奢侈品',
            'body'=>'shootball',
            'reqTime'=>date("YmdHis"),
            'version'=>'1.0',
        ];
        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        $response = json_decode($response, true);
        Log::record('GloryPay pay url=' . $return['gateway'] .' data=' . json_encode($data) . ' response=' . json_encode($response,JSON_UNESCAPED_UNICODE) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('GloryPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['retCode'] == '0' ) {
            header("location: {$response['payJumpUrl']}");
            Log::record('GloryPay  nonce_str= ' .  $return['orderid'] .' payJumpUrl ' . $response['payJumpUrl'], 'ERR', true);
            exit();
        } else {
            Log::record('GloryPay  nonce_str= ' .  $return['orderid'] .'  '. json_encode($response,JSON_UNESCAPED_UNICODE).'  .is failt ', 'ERR', true);
            exit(json_encode($response,JSON_UNESCAPED_UNICODE));
        }
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


// 异步通知
    public function notifyurl()
    {

        $response = $_REQUEST;
//        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" GloryPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，GloryPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
//        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $data = [
            'payOrderId' => $response["payOrderId"],//上游的订单id
            'mchId' => $response['mchId'],
            'appId' => $response["appId"],
            'productId'=> $response["productId"],
            'mchOrderNo' => $response['mchOrderNo'],//我们自己的订单号
            'amount' => $response['amount'], //支付金额
            'income' => $response['income'],
            'status'=>$response['status'],//支付状态,-2:订单已关闭,0-订单生成,1-支付中,2-支付成功,3-业务处理完成,4-已退款（2和3都表示支付成功,3表示支付平台回调商户且返回成功后的状态）
            'channelOrderNo'=>$response['channelOrderNo'],
            'paySuccTime'=>$response['paySuccTime'],
            'backType'=>$response['backType'],
            'reqTime'=>$response['reqTime'],
            'sign' => $response['sign'],
        ];
        $orderid = $response["mchOrderNo"];//自己的订单号
        $upstream_order_id = $response["payOrderId"];// 上游的订单id,
        $paymoney = number_format($response["amount"]/100, 2);// 交易金额

//       判断上游返回的状态是不是正常的，成功的再去检查签名
         if(!is_null($response["status"])&&($response["status"]=='2'||$response["status"]=='3')){
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
                Log::record(' GloryPay回调 orderid= ' . $orderid. '  error:check sign Fail! $response' . json_encode($response), 'ERR', true);
                exit('fail');
            }
        }else{
            Log::record(' GloryPay回调 orderid= ' .$orderid  . '  订单状态失败 $response=' . json_encode($response), 'ERR', true);
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
//        $string_a= substr($string_a, 0, -1);
        $string_sign_temp = $string_a . 'key='.$secret;
//        $string_sign_temp = 'key='.$secret.'&'.$string_a ;
        Log::record('GloryPaycreateToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为小写
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
        Log::record('GloryPayverifySign ===== 校验签名的字符串签名  ：' . $sign2, 'ERR', true);
        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}