<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class WxWuxinController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'WxWuxin',
            'title'     => 'WxWuxin',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'mcht_order_no'   => $return['orderid'],
            'mcht_no'  => $return['mch_id'], //
            'pay_type' => $return['appid'], // ,
            'amount'        => (string)$return["amount"],
            'notify_url'     => $return['notifyurl'],
            'req_time'   => (string)time(),
           
            
        ];
        
        // 转json字符串
        $data = json_encode($data);
        // 商户私钥
        $pri_key = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCsEZxJd5t/6VKQhMfoXNhnHB/onr4uFxr/IybEYU7AQRktarZ5e0F5PTA9lEGAiYRW5ex6JmbIHrIiQFJUvfeYD4MDf5w3xjLTqzj7EEb8JSOCCapazHM0dtZ5i/MUnPUJ7U8M9B3m2XAw2PJIXIiLsLioZI6cBqkFonWK0fTXBnyPXsZPqlQNhc4PjCHSSUZFk4phVnsHa1cnFdjJWsqeu6DS1wbunyDbEcUpZ9Il3HMoezrvxan0T1EeeOb0JbLdEcVQC+Ee70w+PXUEC8lVOhjgz2DPaHYjyZh5ih2oaX5Qlay8mR/iNdVbjqL3T5rMeYVt9Mf0ICDA/+BArAm/AgMBAAECggEAbuufSwDOfeNjtQPTdme7nKRVsXf7cuy0G3qGeBueT3Lnjw52eNNKvqQCIAAdRYXgiMAI9CkjIqge/tNl/3jCTgTZ2Px/MLkUdLywq6+vgsVSIXanmYaoUU62LX5ZAZW4pGCVD+2iBPlwSBzh+mGkKCCQuQSxpcTpWleC3C1CXwWho1SKJc26cHfdox1s4EV7LsoGGBpJqKsLDZvu6doomaegV6NcbTrT8fa9kmQg+VZxNdAmQ2bszWu+Zz76rf6sH+/W/cxG1vc8wBZ9fmQFt8b9eB4rexprjJyhVhmVoCwIzE4opqhBFS4tt4U8Ue2W64IZn/tywUl8rG8SKJBZGQKBgQDaQBFG4YtdsOSIGZfeRp51Mqg6me0482JlZvdOK2Ng2MP3yrZyoTrXpNxZb30tl3OZEAJMtx99ruSihqX8OIWQcmyN8XX4m8QPawI+4Nl0q/ixzG5dXIK7FRQwjjVt4TRff8rsHuJ152E244Z/nRsBDH7FNUbRsU8zxVnpkX1PFQKBgQDJ1KrceSf+6SZ5jWU1dXZz8sQEDSSLLnsYyE/dDBAb/9wHTKOQMkYC2t/Lnl8WhDJWIMclvfipOuybY+ck8ponI65859V838umK1k7uBcLLXWnMhK862GMc+7kyPktGrRuy1IIhTotVlQ16xeACf682kQTyfO1piltKxWbsg7KgwKBgCU1SPzBlQX7E2sUmeyeM4OdiEq9VVEhRUQuYrkj8oRfUEGdgK9YR3TeOWbR+BBewql0rj+v9KFzwKzoscGnTTYMG++zG76vp6RNRQu9P7WBYBvH04T9MZh9hnyksf0yqMAjRFAvD8K0GMHH1nVJLoJmQ/KRG6rCRNN5sNN+J1PlAoGBAML9p6UhmDZ6YiWFKYagWTRkEmQnnmqpGVw0CpRwlw+1/Yk/zbX+HA2eECDUfOFwDoGPYVdhVd+JghYOSr4zdCLkIiuif2sJe+KqdqdvjzPJU6WYhunmLnRXfTGjyLh+2FtCK5r3u+EZSnpdCnM1NNqXtLW5oq6YPeWufk3RlOCpAoGBAMKwLe0cWCqqrBevMuc+byQ1rcW4Nwr7THUeWRMcJxlIHs1BVgEuAEm+N1bpbdcmh/83izwgw1cas6X7nDfQUX3PTs3XOiOqFaA/E3fewg6o7Dan4/zYammqtrqFlMMckM+zfSJOpRndKa6Z3xI3lLIzhCoZWSN4oD7kiFAEcRbx';
        // 平台公钥
        $p_pub_key = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvsDHK4Ne7eNn4GqXcUrFcTAA6hqV415G173ahDL4H6s1i49J4U/r7fuKsMvJVeNyjFRreJWS5G2k/mUil3hUCXMcFU4YeFDkqPdClQArYgRGZivijDE7Hcm1dLm//rv1R2u7GYY0AF14pN0EfU8OevfbbYczW7WGXiis4/gITcIEKoBuoUdEBrZk5jwDAHr1HeXxvNhyoaklRJA7jTTevbVR59wEwqkSWJe6IXTDuRmwVYrgLrb5rRHAL9qCdtjTZVd4j9YK8Bgx2rK9/nyHljhHnp/Cu9LVbhOU8e2tEnI4mqHCMtqhHp6GuJg3eY2HMe1yeRCILJV3zlqStZ1knwIDAQAB';
        // 组合密钥格式
        $pri_key = $this->format_secret_key($pri_key, "pri");
        $p_pub_key = $this->format_secret_key($p_pub_key, 'pub');
        // 平台公钥加密
        $content = $this->pubkeyEncrypt($data, $p_pub_key);
        $reqContext=base64_encode($content);
        $sign = $this->wxsign($pri_key, $content);

        $senddata =  ['sign' => $sign,
            'context' => $reqContext];
        Log::record('111111:'.$sign.'-----'.$reqContext, 'ERR', true);


        $response = $this ->wxpost($return['gateway'], $senddata,10,['Content-Type:application/json']);
        
        $cost_time = $this->msectime() - $start_time;
        Log::record('wxwuxin pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);

        if(isset($response['data']) && $response['status'] == 200){
            header("location: {$response['data']['pay_data']}");
        }
        echo json_encode($response);


    }

    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        $response = json_decode($response, true);
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" wxwuxin \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，wxwuxin notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $pri_key = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCsEZxJd5t/6VKQhMfoXNhnHB/onr4uFxr/IybEYU7AQRktarZ5e0F5PTA9lEGAiYRW5ex6JmbIHrIiQFJUvfeYD4MDf5w3xjLTqzj7EEb8JSOCCapazHM0dtZ5i/MUnPUJ7U8M9B3m2XAw2PJIXIiLsLioZI6cBqkFonWK0fTXBnyPXsZPqlQNhc4PjCHSSUZFk4phVnsHa1cnFdjJWsqeu6DS1wbunyDbEcUpZ9Il3HMoezrvxan0T1EeeOb0JbLdEcVQC+Ee70w+PXUEC8lVOhjgz2DPaHYjyZh5ih2oaX5Qlay8mR/iNdVbjqL3T5rMeYVt9Mf0ICDA/+BArAm/AgMBAAECggEAbuufSwDOfeNjtQPTdme7nKRVsXf7cuy0G3qGeBueT3Lnjw52eNNKvqQCIAAdRYXgiMAI9CkjIqge/tNl/3jCTgTZ2Px/MLkUdLywq6+vgsVSIXanmYaoUU62LX5ZAZW4pGCVD+2iBPlwSBzh+mGkKCCQuQSxpcTpWleC3C1CXwWho1SKJc26cHfdox1s4EV7LsoGGBpJqKsLDZvu6doomaegV6NcbTrT8fa9kmQg+VZxNdAmQ2bszWu+Zz76rf6sH+/W/cxG1vc8wBZ9fmQFt8b9eB4rexprjJyhVhmVoCwIzE4opqhBFS4tt4U8Ue2W64IZn/tywUl8rG8SKJBZGQKBgQDaQBFG4YtdsOSIGZfeRp51Mqg6me0482JlZvdOK2Ng2MP3yrZyoTrXpNxZb30tl3OZEAJMtx99ruSihqX8OIWQcmyN8XX4m8QPawI+4Nl0q/ixzG5dXIK7FRQwjjVt4TRff8rsHuJ152E244Z/nRsBDH7FNUbRsU8zxVnpkX1PFQKBgQDJ1KrceSf+6SZ5jWU1dXZz8sQEDSSLLnsYyE/dDBAb/9wHTKOQMkYC2t/Lnl8WhDJWIMclvfipOuybY+ck8ponI65859V838umK1k7uBcLLXWnMhK862GMc+7kyPktGrRuy1IIhTotVlQ16xeACf682kQTyfO1piltKxWbsg7KgwKBgCU1SPzBlQX7E2sUmeyeM4OdiEq9VVEhRUQuYrkj8oRfUEGdgK9YR3TeOWbR+BBewql0rj+v9KFzwKzoscGnTTYMG++zG76vp6RNRQu9P7WBYBvH04T9MZh9hnyksf0yqMAjRFAvD8K0GMHH1nVJLoJmQ/KRG6rCRNN5sNN+J1PlAoGBAML9p6UhmDZ6YiWFKYagWTRkEmQnnmqpGVw0CpRwlw+1/Yk/zbX+HA2eECDUfOFwDoGPYVdhVd+JghYOSr4zdCLkIiuif2sJe+KqdqdvjzPJU6WYhunmLnRXfTGjyLh+2FtCK5r3u+EZSnpdCnM1NNqXtLW5oq6YPeWufk3RlOCpAoGBAMKwLe0cWCqqrBevMuc+byQ1rcW4Nwr7THUeWRMcJxlIHs1BVgEuAEm+N1bpbdcmh/83izwgw1cas6X7nDfQUX3PTs3XOiOqFaA/E3fewg6o7Dan4/zYammqtrqFlMMckM+zfSJOpRndKa6Z3xI3lLIzhCoZWSN4oD7kiFAEcRbx';
        // 平台公钥
        $pub_key = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvsDHK4Ne7eNn4GqXcUrFcTAA6hqV415G173ahDL4H6s1i49J4U/r7fuKsMvJVeNyjFRreJWS5G2k/mUil3hUCXMcFU4YeFDkqPdClQArYgRGZivijDE7Hcm1dLm//rv1R2u7GYY0AF14pN0EfU8OevfbbYczW7WGXiis4/gITcIEKoBuoUdEBrZk5jwDAHr1HeXxvNhyoaklRJA7jTTevbVR59wEwqkSWJe6IXTDuRmwVYrgLrb5rRHAL9qCdtjTZVd4j9YK8Bgx2rK9/nyHljhHnp/Cu9LVbhOU8e2tEnI4mqHCMtqhHp6GuJg3eY2HMe1yeRCILJV3zlqStZ1knwIDAQAB';
       
        $pub_key = $this->format_secret_key($pub_key, 'pub');
        $pri_key = $this->format_secret_key($pri_key, "pri");
        $content=$response['context'];
        $sign=$response['sign'];
        $v = $this->wxverify($sign, $pub_key, $content);
        if (!$v) {
            trace("验签失败");
            exit("验签失败");
        }

        $m_content = $this->pikeyDecrypt($content, $pri_key);
        if(empty($m_content)){
            trace("解密失败");
            exit("no content");
        }
        $response=json_decode($m_content,true);
        if(empty($response)){
            trace("转换失败");
            exit("no content");
        }






        if ($response['status'] == 2) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["mcht_order_no"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["mcht_order_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                
                $diff1 = $response['real_amount'] - $pay_amount;
                if($diff1 <= -1 || $diff1 >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['real_amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $this->EditMoney($response['mcht_order_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('上游wap回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('UpPdd error:check sign Fail!','ERR',true);
            exit('error:check sign Fail!');
        }
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

    

    // 公钥加密
    private function pubkeyEncrypt($source_data, $pu_key)
    {
        $data = "";
        $dataArray = str_split($source_data, 245);
        foreach ($dataArray as $value) {
            $encryptedTemp = "";
            openssl_public_encrypt($value, $encryptedTemp, $pu_key); // 公钥加密
            $data .= $encryptedTemp;
        }
        return $data;
    }

    // 私钥解密
    private function pikeyDecrypt($eccryptData, $decryptKey)
    {
        $decrypted = "";
        $decodeStr = base64_decode($eccryptData);
        $enArray = str_split($decodeStr, 256);
        foreach ($enArray as $va) {
            $decryptedTemp = "";
            openssl_private_decrypt($va, $decryptedTemp, $decryptKey); // 私钥解密
            $decrypted .= $decryptedTemp;
        }
        return $decrypted;
    }

    // 私钥签名
    private function wxsign($private_key, $original_str)
    {
        $sign = "";
        openssl_sign($original_str, $sign, $private_key, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign); // 最终的签名　
        return $sign;
    }

    // 公钥验签
    private function wxverify($sign, $public_key, $original_str)
    {
        $result = "";
        $sign = base64_decode($sign); // 得到的签名
        $original_str = base64_decode($original_str);// 得到密文
        $result = (bool)openssl_verify($original_str, $sign, $public_key, OPENSSL_ALGO_SHA256); // $result为真时签验通过,假时未通过
        return $result;
    }

   private function format_secret_key($secret_key, $type)
    {
        // 64个英文字符后接换行符"\n",最后再接换行符"\n"
        $key = (wordwrap($secret_key, 64, "\n", true)) . "\n";
        // 添加pem格式头和尾
        if ($type == 'pub') {
            $pem_key = "-----BEGIN PUBLIC KEY-----\n" . $key . "-----END PUBLIC KEY-----\n";
        } else if ($type == 'pri') {
            $pem_key = "-----BEGIN RSA PRIVATE KEY-----\n" . $key . "-----END RSA PRIVATE KEY-----\n";
        } else {
            echo ('公私钥类型非法');
            exit();
        }
        return $pem_key;
    }

        private function wxpost($url, $data = [], $second = 10, $header = [])
    {
        $curl = curl_init();
        $data=is_array($data) ? json_encode($data) : $data;
        //self::applyHttp($curl, $url);
         if (stripos($url, "https") === 0) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, $second);
        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        if (!empty($header)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        list($content, $status) = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];
        return $content;
    }

}