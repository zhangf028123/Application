<?php


namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;
use MoneyCheck;

require_once('redis_util.class.php');


class UpPddWuXin3Controller extends PayController
{
    public function Pay($array)
    {
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'UpPddWuXin3', // 通道名称
            'title' => '上游Wap',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $data = [
            'return_type' => 'app',
            'appid' => $return['mch_id'], //
            'amount' => sprintf("%.2f",$return["amount"]),
            'callback_url' => $return['notifyurl'],
            'success_url' => $return['callbackurl'],
            'error_url' => '',
            'pay_type' => $return['appid'],
            'out_uid' => $return['orderid'],
            'out_trade_no' => $return['orderid'],
            'version' => 'v1.1',
        ];
        $data['sign'] = $this -> getSign($return["signkey"], $data);
        $this->setHtml($return['gateway'], $data);
        die();
        //$response = $this ->post($return['gateway'], $data);
        //$response = HttpClient::post($return['gateway'], $data);    //
        Log::record('UpPddWuXin3 pay url=' . $return['gateway'] . ',data=' . json_encode($data) . ',response=' . $response, 'ERR', true);

        $response = json_decode($response, true);

        if(isset($response['url']) && $response['code'] == 200){
            header("location: {$response['url']}");
        }
        echo json_encode($response);

    }


    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        } else {
            exit("error");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        Log::record(" UpPddWuXin3 \$response=".json_encode($response),'ERR',true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，UpPddWuXin3  notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["out_trade_no"]); // 密钥
        $data = [
            'callbacks' => $response['callbacks'],
            'appid' => $response['appid'],
            'pay_type' => $response['pay_type'],
            'success_url' => $response['success_url'],
            'error_url' => $response['error_url'],
            'out_trade_no' => $response["out_trade_no"],
            'amount'  => $response["amount"],
            'amount_true'  => $response["amount_true"],
            'out_uid' => $response["out_uid"],
        ];

        $sign = $this -> getSign($publiKey, $data);
        if ($sign == $response['sign'] && $response['callbacks'] == 'CODE_SUCCESS') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->find();
                if(!$o){
                    Log::record('咸鱼（xynew）回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("咸鱼（xynew）回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $diff1 = $response['amount_true'] - $pay_amount;
                if($diff1 <= -1 || $diff1 >= 1 ){ // 允许误差一块钱
                    Log::record("咸鱼（xynew）回调失败11,金额不等：{$response['amount_true'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error11!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_trade_no"]){
                    Log::record("咸鱼（xynew）回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                /*if (empty($response['order_no'])){
                    Log::record("流水号为空  ：".json_encode($response).'旧订单号','ERR',true);
                    exit('notify error!');
                }*/
                //$Order->where(['pay_orderid' => $response["out_trade_no"]])->save([ 'upstream_order'=>$response['order_no']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('咸鱼（xynew）回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //未支付。。。
            exit("error:not pay");
        }
        else {
            exit('error:check sign Fail!');
        }
    }




    private function getSign($secret, $data)
    {
        // 去空
        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;

        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }

}