<?php

namespace Pay\Controller;

class DuoduoBase extends PayController
{
    // 域名 	http://www.laiguwenhua.com
    protected $gatewayUrl; // 当面付：/wap/payment/gopayv2 wap支付：/wap/paymentwap/payment

    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");

        \Think\Log::record('Duoduo notifyurl $data='.json_encode($_REQUEST),'ERR',true);
        $order_info = $Order->where(['upstream_order' => $_REQUEST["out_trade_no"]])->find();
        $pay_status = $order_info['pay_status'];
        if ($pay_status > 0) {
            $this->EditMoney($order_info["pay_orderid"], '', 1);
        } else {
            exit("error");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_POST;
        \Think\Log::record('Duoduo notifyurl $_POST='.json_encode($_POST),'ERR',true);

        $publiKey = getKey($response["sOrderBn"]); // 密钥

        $result = $this->_verify($response, $publiKey);

        if ($result) {
            if ($response['status'] == '1' ) {
                $Order      = M("Order");
                $Order->where(['pay_orderid' => $response["sOrderBn"]])->save([ 'upstream_order'=>$response['systemOrderBn']]);
                $this->EditMoney($response['sOrderBn'], '', 0);
                exit("success");
            }
        } else {
            \Think\Log::record('Duoduo notifyurl 验签失败','ERR',true);
            exit('error:check sign Fail!');
        }
    }

    /// 返回请求字符串
    protected function sign($data, $apikey){
        unset($data['sign']);
        $res = '';
        foreach ($data as $key => $value){
            $res .= "$key=$value&";
        }
        $sign = "appId={$data['appId']}&sOrderBn={$data['sOrderBn']}&amount={$data['amount']}&notifyUrl={$data['notifyUrl']}&{$apikey}";
        $sign = md5($sign);
        return $res."sign=$sign";
    }

    /// 验签
    protected function _verify($data, $publicKey)
    {
        //$calcSign = md5("appId={$data['appId']}&status={$data['status']}&systemOrderBn={$data['systemOrderBn']}&sOrderBn={$data['sOrderBn']}&amount={$data['amount']}&payType={$data['payType']}&{$apiKey}");
        $strData= $data['md5Summary'];// 收到的md5Summary
        //if($calcSign != $strData)return false;
        $signature= $data['rsaSign'];// 收到的签名值rsaSign
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----"; // 平台公钥
        if (!openssl_get_publickey($publicKey)) {
            return false;
        }
        $base64Signature = base64_decode($signature);
        if (!openssl_verify($strData, $base64Signature, $publicKey, OPENSSL_ALGO_SHA256)) {
            return false;
        }
        return true;
    }

}