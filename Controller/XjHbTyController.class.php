<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XjHbTyController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XjHbTy',
            'title'     => '现金红包(ty)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);



        $mer_no = '001215001326'; //商户编码(请修改为平台提供的商户编码)
        $mer_user_no = '001215001326000000000001'; //商户编码(请修改为平台提供的商户编码)
        $trans_id = $return['orderid']; //商户订单编码(必须保证唯一性);
        $trade_type = "T0"; //交易类型(T0或T1)
        $body = "测试商品"; //交易或商品的描述
        $version = "1.0.0"; //版本号 固定
        $sign_type="RSA";
        $front_url = $return['callbackurl']; //同步回调地址 可不填
        $notify_url = $return['notifyurl']; //异步回调地址 必填
        $spbill_create_ip = "127.0.0.1"; //客户端ip 可不填
        $pay_time = $start_time; //时间戳（当前utc时间的毫秒值） 必填
        $money = strval($return["amount"]*100); //付款金额（单位分,不能有小数点）
        $pay_type = "xjhb"; //支付类型  zfbwap wap支付 zfbzpp app支付 其余请询问运营人员
        //加密参数
        //先加密后签名
        $aesMap = array(
            'money'=>$money,
            'pay_type'=>$pay_type,
            'trade_type'=>$trade_type,
        );
        //参数加密
        $aesStr= json_encode($aesMap,true);
        Log::record('XjHbTy_error:'.$aesStr, 'ERR', true);
        $aesKey = '001215l4a1ctqnkm'; //AES加密的key(请修改为平台提供的商户AES加密的key)
        $aes_iv = 'alpayaesivvector'; //AES加密解密向量
        $data = $this->AesEncrypt($aesStr,$aesKey,$aes_iv);
        //参数签名
        $paramMap = array(
            'body'=>$body,
            'data'=>$data,
            'front_url'=>$front_url,
            'mer_no'=>$mer_no,
            'pay_time'=>$pay_time,
            'mer_user_no'=>$mer_user_no,
            'notify_url'=>$notify_url,
            'sign_type'=>$sign_type,
            'spbill_create_ip'=>$spbill_create_ip,
            'trans_id'=>$trans_id,
            'version'=>$version,
        );
        $aesStr= $this->toString($paramMap);
        //Log::record('XjHbTy_error:'.json_encode($aesStr), 'ERR', true);

        $private_key = "MIIBVAIBADANBgkqhkiG9w0BAQEFAASCAT4wggE6AgEAAkEAiL80uwoC4EhXfsndm7VcB4z6B4VfEcnxvAMYVg22FJyT2tjBIbC65YSTszMk6jrggIcx3RxLJgoPdsGAxBe7IwIDAQABAkAmFxSKEPTSInR0tagL6k2TMNqoY6cinly+YSJTPgh83uOu0+VmLT7G7XOH4rOC6CwvM73i26PARPcT9Q7kM3vpAiEAzxgxw6mYFteGGBs//dVCh+KjA2kSQdklbIeenU2gsMcCIQCpCi2ntszxPOZQU39h9VLIZK0frZWh3ShLlR3/FeS+xQIgBeZPjJ5pOcVcCZXFJesMYSigsjktDvkrqsLWTu7mNAMCIDV8AvYN4Mpzemvv/13/QTImqKBdS/rq/tTrWZJcWwQBAiEAsct2a4BQtnOJWJF1MJkSINApjOna14Fw8uPBl+KdWn8=";
        $pemPriKey = chunk_split($private_key, 64, "\n");
        $pemPriKey = "-----BEGIN RSA PRIVATE KEY-----\n".$pemPriKey."-----END RSA PRIVATE KEY-----\n";
        $paramMap['sign'] = $this->getSignMsgs($aesStr,$pemPriKey,'RSA');
        //$url = $return['gateway'];
        //$this->geturl($url,$paramMap);

        $response = HttpClient::post($return['gateway'], $paramMap);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('XjHbTy pay url='.$return['gateway'].'data='.json_encode($paramMap).'response='.$response."cost time={$cost_time}ms",'ERR',true);

        $response = json_decode($response, true);
        $contentType = I("request.content_type");
        //$this->setHtml($return['gateway'], $data);

        if ($response['success'] != 'true' || $contentType == 'json'){
            if($response['success'] != 'true'){    // 记录下单失败的记录
                if(!isset($response['result']))$response['result'] = 'err';
                file_put_contents("Data/UpPdd_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
            }else {
                $response['result'] = 'ok';
				$response['url'] = $response['content'];
            }
            $this->ajaxReturn($response);
        }


        header("location: {$response['content']}");
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XjHbTy \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XjHbTy notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        if($response['status'] != 1){
            die("not ok2");
        }

        //$publiKey = getKey($response["trans_id"]); // 密钥

        $data['status'] = $response['status'];
        $data['message'] = $response['message'];
        $data['mer_no'] = $response['mer_no'];
        $data['trans_id'] = $response['trans_id'];
        $data['order_id'] = $response['order_id'];
        $data['pay_time'] = $response['pay_time'];
        $data['money'] = $response['money'];
        $data['sign_type'] = $response['sign_type'];
        $data['settle_status'] = $response['settle_status'];


        $sign = $response['sign'];
        $sign = urldecode($sign);
		$sign = str_replace(' ', '+', $sign);

        $str = $this->toString($data);
        $public_key = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnvsPhtfbKlqJwrX1x/H9/ggxnOqHGjpiNutkkyoFZcLcDioZrKZOjTyakkRbLqSYeX2Uhyx2eegLBAOvEycMsGjTR4yUtrvLe3yQxstjrrB5qemWqDK/9Pe5vP32PJU36BQdR+hY6Pc+OyZ1kCWXF+aItpF1YOfuw+oFCDy1Ug1ruGy2xzaHIR/agl7xDDHrWeyHLKpqbuosgSAlIGXbGkNID8g0Z08mGxpbTj3X+5M8geqx/+lpfownb+HmnBp4ephTnu9p67XkkxHiSsrPr+ncVH59uZn5/+/auY+W16i49PHZ4vnJD2IaADEXqvBr3OjiczWsVNNG0zo/V6u7rQIDAQAB';
        $pemPubKey = chunk_split($public_key, 64, "\n");
        $pemPubKey = "-----BEGIN PUBLIC KEY-----\n".$pemPubKey."-----END PUBLIC KEY-----\n";
        //验证签名
        //Log::record('notifyurl_xjhbty_error:'.$str.'----'.$pemPubKey.'----'.$sign, 'ERR', true);

        if ($this->rsaVerify($str, $pemPubKey, $sign) !== true) {
            die("not ok3");
        }
        $result = true;
        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["trans_id"]])->find();
                if(!$o){
                    Log::record('现金红包ty回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["trans_id"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['money']/100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("现金红包ty回调失败,金额不等：{$response['money'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_id']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["trans_id"]){
                    Log::record("现金红包ty回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["trans_id"]])->save([ 'upstream_order'=>$response['order_id']]);
                $this->EditMoney($response['trans_id'], '', 0);
                exit("allpay_success");
            }catch (Exception $e){
                Log::record('现金红包ty回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('现金红包ty error:check sign Fail!','ERR',true);
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

    //Hr_AES加密
    private static function AesEncrypt($plaintext, $key, $aes_iv)
    {
        $plaintext = trim($plaintext);
        if ($plaintext == '') return '';
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        //PKCS5Padding
        $str = extension_loaded('mbstring') ? mb_strlen($plaintext,'8bit') : strlen($plaintext);
        $padding = $size -$str % $size;
        // 添加Padding
        $plaintext .= str_repeat(chr($padding), $padding);
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
        $key=self::substr($key, 0, mcrypt_enc_get_key_size($module));
        $iv = str_repeat($aes_iv, $size);
        /* Intialize encryption */
        mcrypt_generic_init($module, $key, $iv);
        /* Encrypt data */
        $encrypted = mcrypt_generic($module, $plaintext);
        /* Terminate encryption handler */
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        return base64_encode($encrypted);
    }

    /**
     * @param array $data
     * @return bool|string
     */
    private function toString($data =array()){
        if (empty($data))
            return false;
        ksort($data);
        $str ='';
        foreach ($data as $key=>$value){
            $str.="{$key}={$value}&";
        }
        $str = rtrim(trim($str), "&");
        return $str;
    }

    /**
     * @param $string
     * @param $start
     * @param $length
     * @return false|string
     */
    private static function substr($string,$start,$length){
        return extension_loaded('mbstring') ? mb_substr($string,$start,$length,'8bit') : substr($string,$start,$length);
    }

    /**
     * @param $url
     * @param array $data
     */
    private function geturl($url, $data = array()) {
        $str ='';
        foreach ($data as $key=>$value){
            $value = urlencode(urlencode($value));
            $str .="{$key}={$value}&";
        }
        rtrim(trim($str), "&");
        $data_array = array(
            'url' =>stripslashes($url),
            'str'=>stripslashes($str),
        );
        $url = $url.'?'.$data_array['str'];
        echo "<script language=\"javascript\">";
        echo "location.href=\"$url\"";
        echo "</script>";
        //echo $this->buildForm($url, urlencode($rsa_json));
        //$this->response(REQUEST_SUCCESS, $data_array);
    }
    /**
     * @json 需加密的参数
     * @string 私钥
     * @bool 是否MD5
     * return:
     *      base64加密的密文
     */
    private function getSignMsgs($params,$private='',$type='RSA') {

        switch ($type) {
            case 'RSA' :
                $signMsg = $this->rsaSign($params,  $private);
                break;
            case 'MD5' :
            default :
                $signMsg = strtolower(md5($params));
                break;
        }
        return $signMsg;
    }

    /**
     * @param $data
     * @param $priKey
     * @return string
     */
    private static function rsaSign($data, $priKey) {
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * @param $data
     * @param $pubKey
     * @param $sign
     * @return bool
     */
    private static function rsaVerify($data, $pubKey, $sign)  {
        $res = openssl_get_publickey($pubKey);
        //$list = [1,2,3,4,5,6,7,8,9,10];
        //foreach($list as $k => $v){
        //$result = (bool)openssl_verify($data, base64_decode($sign), $res, $v);
        //Log::record('testig...:'.$v.$result, 'ERR', true);
       // }
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, 5);
        openssl_free_key($res);
        return $result;
    }
    /**
     * curl方法
     * @param $url
     * @return mixed
     */
     private function httpGet($url, $postData='') {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        if(!empty($postData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;

    }

}
