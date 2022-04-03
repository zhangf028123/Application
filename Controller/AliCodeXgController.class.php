<?php

namespace Pay\Controller;

use Org\SignatureUtil;
use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class AliCodeXgController extends PayController
{

    public function Pay($array)
    {
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'AliCodeXg', // 通道名称
            'title' => '支付宝个码（xg）',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        /*注意设置页面编码格式为UTF-8*/
        $signkey=$return['signkey'];//商户签名秘钥
        $priveteKey='MIICXAIBAAKBgQCPR1N/6YmpHH7f92CCKMxvvzEloO8+vOcJpb8kkNWJGacWK171c8rPZv0MZad5pubCc3io8a/1+qKVIv6835FoX4K4ip/GMS+T/T48shI4PJZcZygt218HSlkZwgNMmusSNzZ3sLIOgpdVIj/A+rwPI0p5xHf/JN5TcoR1414g3wIDAQABAoGABdXEy8fKCG4VqK9dac+Zi8+Ag+TK+YYd7qGmaCnR2HSH/nojsuFVWB78nT2ilWy7px2mw1KcdOsRJfu33h9Iv6RpByWjClU0/X8wLv1u5mX1cZ79Tgj6UC+jd093fm/3y1qMQg3vXxNoAnUnCd81AwRMamN+4uiRXuTuHW9NBUECQQDw7Y7Oi4dnna+3ms6IJZyujiwDttIbpRr5dGqbE9rA0QYzXjgR+M1Kiv+l2CNy0/czF2S+RMf/ghtR6VneaVO/AkEAmD3r/zPN92MEYwT1ut0uanZXSe+tcTvHGu0IpfJtZUD1QAV2j7hLaEkoY81505h2Masy9WNsJNqXoYQmh7364QJAAo4SYKBcLD4g2eqbXBhCBBvf3Z43tjFXCuQwKTrZrAfLcAoEwDQKQUseEO0s2w/iZDlQSTBDirMfhQvbdx9Y0wJATRkGu63bf43YeeDYJLLAP9AAcoP7XN29/ifN+mQj/GQCD1L08OGO5pgt6ST0rjCGoq6lVtnruVot8fC/pnySYQJBAPCmivB9TZ9sIBXJOa+TnL6eaBtwOrmTJclzVJLd0YvPGIARsWFJ5hSMmcHyABX80qFQ3+vl3WzvmHCXsqhrGOE=';//RSA 私钥
        //$priveteKey = 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAI9HU3/piakcft/3YIIozG+/MSWg7z685wmlvySQ1YkZpxYrXvVzys9m/Qxlp3mm5sJzeKjxr/X6opUi/rzfkWhfgriKn8YxL5P9PjyyEjg8llxnKC3bXwdKWRnCA0ya6xI3Nnewsg6Cl1UiP8D6vA8jSnnEd/8k3lNyhHXjXiDfAgMBAAECgYAF1cTLx8oIbhWor11pz5mLz4CD5Mr5hh3uoaZoKdHYdIf+eiOy4VVYHvydPaKVbLunHabDUpx06xEl+7feH0i/pGkHJaMKVTT9fzAu/W7mZfVxnv1OCPpQL6N3T3d+b/fLWoxCDe9fE2gCdScJ3zUDBExqY37i6JFe5O4db00FQQJBAPDtjs6Lh2edr7eazoglnK6OLAO20hulGvl0apsT2sDRBjNeOBH4zUqK/6XYI3LT9zMXZL5Ex/+CG1HpWd5pU78CQQCYPev/M833YwRjBPW63S5qdldJ761xO8ca7Qil8m1lQPVABXaPuEtoSShjzXnTmHYxqzL1Y2wk2pehhCaHvfrhAkACjhJgoFwsPiDZ6ptcGEIEG9/dnje2MVcK5DApOtmsB8twCgTANApBSx4Q7SzbD+JkOVBJMEOKsx+FC9t3H1jTAkBNGQa7rdt/jdh54NgkssA/0AByg/tc3b3+J836ZCP8ZAIPUvTw4Y7mmC3pJPSuMIairqVW2eu5Wi3x8L+mfJJhAkEA8KaK8H1Nn2wgFck5r5Ocvp5oG3A6uZMlyXNUkt3Ri88YgBGxYUnmFIyZwfIAFfzSoVDf6+XdbO+YcJeyqGsY4Q==';
        //$priveteKey='MIICXQIBAAKBgQC4Yyqr7X0Nxl3sFSQXFsEKXZZG9F66STVzgHgZPJHXdL6bsRBHnv5wEU6MKCiB01hlRNZ1OA/7Ppb0K9BvCx9yr77cepvZM+Hom+2LM3ckQW79fQOyYf4v44rJ1pocT/HyrPoxOg+X9JzW7u/ubmZVHRYOQG+YFdeUe1nnOeCfVQIDAQABAoGBAK2W1r6D1/6W6Sdwg8ik6Foc33SvbVsNvx+dK/P+XQMtaqFi4gO7gKj68irrR69pzEeStiAnBoyvUShQ82sHWrNcvUA7iO6VseYYCjZaSG6SvoQBjg37zYEoSVQ4shUzfGfEiy3oFxVTXO8jGHV7c0DPuy+q6UuQOHJ9yznagsmlAkEA/wkXLIMML2QQfWO6UEuALyajxqFWjA7Kl3gKT4MH5WCD8lkCqtbgIullpCrDBl/Hfarh2NvH6ujBECCeE3pfwwJBALkVrdWFac/y1MNK5F+/CF1UEZl21jqJxjTIKiQCvrzSr4GUI58BS7gEN3Ti0TICMgvcjEu5YLkM6ahNAhvg6wcCQF6JGxr31Lt4ZxhjsDt1USWpOAo34eH21agB6iiBFJs1BJP/5Jo5Hkoyo+ePpk0lkcgGYMNG7Lsp3e7BeHcV5IsCQQC13MNupyUNm8HME27LVd5WNiEE9mwSIQaNHpGpyLi6uRqS7IkD2DYanqoPRD/iL54VYaTJU2Hi8vk00lZcJmlJAkAR33XbCYDhGIvD8XxR7/kG0xUYVT0SUV1gFjgovVU9x2ZBlZCtKnfbBqwbastwj7d6tfsLJbRWyfivpYdi2+rX';

        $publicKey= 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnLCpu4Y0x0/8AlMW5S/zweX0/K/Q284XYK9szKTWahAUE35CO7ihfItVLKjdq+NlARWve+6jbzEsfA5vZC8shSceqQlu3DTiyhd/9m90fffUb3C8pDhVeFmuhP0wRj+0KhRLi+j4T5MM1lwt7LCMhxSZOAH4ZrEmixgBk384DQQIDAQAB';
        $notify_url=$return['notifyurl'];//异步通知地址
        $mchnt_cd = $return['mch_id'];
//以下是参数以及值，无序
        $s=array(
            "version"=>"1.0",
            "method"=>"trade",
            "mchnt_cd"=>$mchnt_cd,
            "tran_seq"=>$return['orderid'],
            "timestamp"=>date('YmdHis'),
            "pay_type" => $return['appid'],
           // "amount" => "1000",
            "amount"=> strval($return['amount']),
            "order_info"=>'trade',
            "notify_url"=>$notify_url,
        );

        $s=array_change_key_case($s,CASE_LOWER);  //键名转换为小写
        ksort($s,0);  //数组键名 ASCII 排序
//开始拼接签名的字符串
        $verifystring="";
        foreach ($s as $k => $vl) {
            $verifystring=$verifystring==""?$verifystring.($k."=".$vl):($verifystring."&".$k."=".$vl);
        }
        $sign=  strtolower(md5($verifystring.$signkey));   //把签名秘钥拼接到最后 再MD5得到签名
        $s["sign"]=$sign;  //把签名放入原数组
        ksort($s,0);  //再次数组键名 ASCII 排序,因为多了一组值verifystring
        $str=urlencode(json_encode($s));//json序列化然后urlencode转下格式
        //$sendStr = $this -> encryptPrivate($str,$priveteKey);//密钥加密得到发送字符串
        $sendStr = $str;
		$url=$return['gateway'];
        $response=$this -> sendStreamFile($url,$sendStr);  //流形式上送,得到返回值
        $de_str = $response;
        //$de_str = $this -> decryptPublic(trim($response),$publicKey);//公钥解密
        $de_data = json_decode(urldecode($de_str),true);//转换解密后字符串格式

        \Think\Log::record('AliCodeXg222:'.$sendStr.'--1--'.$str.'---2---'.json_encode($s).'--3---'.json_encode($de_data), 'ERR', true);
        if ($de_data['return_code'] == 'SUCCESS') {

            $contentType = I("request.content_type");
            if ($contentType == 'json') {
                $return = [
                    'result' => 'ok',
                    'orderStr' => $de_data['pay_url'],
                ];
                $this->ajaxReturn($return);
            }
            header("Location:{$de_data['pay_url']}");
        } else {
            echo $de_data;
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


    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        $response = json_decode(urldecode($response),true);//转换解密后字符串格式

        Log::record('支付宝个码（xg）订单失败：'.$response.'--'.json_decode($response).'--'.json_encode($response),'ERR',true);
        //$de_str = $this -> decryptPublic(trim($response),$publicKey);//公钥解密
        //$response = json_decode(urldecode($de_str),true);//转换解密后字符串格式
        //Log::record('支付宝个码（xg）订单失败111：'.$response.'--'.json_decode($response).'--'.json_encode($response),'ERR',true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，AliCodexg notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $signkey = getKey($response["old_tran_seq"]); // 密钥
        $s = $response;
        unset($s['sign']);
        $s=array_change_key_case($s,CASE_LOWER);  //键名转换为小写
        ksort($s,0);  //数组键名 ASCII 排序
        //开始拼接签名的字符串
        $verifystring="";
        foreach ($s as $k => $vl) {
            if (!empty($vl)) {
                $verifystring = $verifystring == "" ? $verifystring . ($k . "=" . $vl) : ($verifystring . "&" . $k . "=" . $vl);
            }
        }
        $sign=strtolower(md5($verifystring.$signkey));   //把签名秘钥拼接到最后 再MD5得到签名

        Log::record('支付宝个码（xg）回调失败1111,找不到订单：'.$verifystring.$signkey,'ERR',true);



        if ($sign == $response['sign'] && $response['deal_status'] == '1') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["old_tran_seq"]])->find();
                if(!$o){
                    Log::record('支付宝个码（xg）回调失败,找不到订单：'.$response,'ERR',true);
                    exit('error:order not fount'.$response["old_tran_seq"] );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['amount']  - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝个码（xg）回调失败,金额不等：{$response['amount'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount error!');
                }
                $diff1 = $response['pay_amount']  - $pay_amount;
                if($diff1 <= -1 || $diff1 >= 1 ){ // 允许误差一块钱
                    Log::record("支付宝个码（xg）回调失败,金额不等：{$response['pay_amount'] } != {$pay_amount},".$response,'ERR',true);
                    exit('error: amount11 error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['deal_tran_seq']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["old_tran_seq"]){
                    Log::record("支付宝个码（ch）回调失败,重复流水号  ：".$response.'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                if (empty($response['deal_tran_seq'])){
                    Log::record("流水号为空  ：".$response.'旧订单号','ERR',true);
                    exit('notify error!');
                }
                $Order->where(['pay_orderid' => $response["old_tran_seq"]])->save([ 'upstream_order'=>$response['deal_tran_seq']]);
                $this->EditMoney($response['old_tran_seq'], '', 0);
                exit("SUCCESS");
            }catch (Exception $e){
                Log::record('支付宝个码（ch）回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } elseif ($sign == $response['sign']) {
            //未支付。。。
            Log::record('支付宝个码（ch）订单失败：'.$response,'ERR',true);
            exit("error:not pay");
        }
        else {
            Log::record('支付宝个码（ch）订单失败222222：'.$sign.'------'.$response['sign'],'ERR',true);

            exit('error:check sign Fail!');
        }
    }


    /**
     * 私钥加密
     * @param $data
     * @param $rsakeypath
     */
    public static function  encryptPrivate($data, $rsakeypath) {
        $content = self::getContent($rsakeypath);
        if ($content) {
            $pem = self::transJavaRsaKeyToPhpOpenSSL($content);
            $pem = self::appendFlags($pem,false);
            $res = openssl_pkey_get_private($pem);
            $re='';
            if ($res) {
                $split = str_split($data, 117);  // 1024 bit && OPENSSL_PKCS1_PADDING  不大于117即可
                foreach ($split as $chunk) {
                    $isOkay = openssl_private_encrypt($chunk, $result, $res);
                    if(!$isOkay){
                        echo "<br/>" . openssl_error_string() . "<br/>";
                        return false;
                    }
                    $re .= $result;
                }
                return base64_encode($re);
            }else{
                echo "<br/>私钥格式错误<br/>";
            }
        }
        return false;
    }


    /**
     * 公钥解密
     * @param $data
     * @param $rsakeypath
     */
    public static function decryptPublic($data,$rsakeypath){
        $content = self::getContent($rsakeypath);
        if ($content) {
            $pem = self::transJavaRsaKeyToPhpOpenSSL($content);
            $pem = self::appendFlags($pem, true);
            $res = openssl_pkey_get_public($pem);
            $data = base64_decode($data);
            if ($res) {
                $re='';
                foreach (str_split($data, 128) as $chunk) {
                    $opt = openssl_public_decrypt($chunk, $result, $res);
                    if (!$opt) {
                        echo "<br/>" . openssl_error_string() . "<br/>";
                        return false;
                    }
                    $re.=$result;

                }
                return $re;
            }else{
                echo "<br/>公钥格式错误<br/>";
            }
        }
        return false;
    }

    /**
     * get content forom file
     * @param $filepath
     * @return $content
     */
    private static function getContent($filepath) {
        if (is_file($filepath)) {
            $content = file_get_contents($filepath);
            return strtr($content, array(
                "\r\n" => "",
                "\r" => "",
                "\n" => "",
            ));
        }else{
            return strtr($filepath, array(
                "\r\n" => "",
                "\r" => "",
                "\n" => "",
            ));
        }
        return false;
    }

    /**
     * trans java's rsa key format to php openssl can read
     * @param $content
     * @return string
     */
    private static function transJavaRsaKeyToPhpOpenSSL($content) {
        if ($content) {
            return wordwrap($content, 64, "\n",true);
        }
        return false;
    }

    /**
     * append Falgs to content
     * @param $content
     * @param $isPublic
     * @return string
     */
    private static function appendFlags($content, $isPublic = true) {
        if ($isPublic) {
            return "-----BEGIN PUBLIC KEY-----\n" . $content . "\n-----END PUBLIC KEY-----";
        }
        else {
            return "-----BEGIN RSA PRIVATE KEY-----\n" . $content . "\n-----END RSA PRIVATE KEY-----";
        }
    }

/** php 发送流文件
 * @param  String  $url  接收的路径
 * @param  String  $file 要发送加密数据
 * @return boolean
 */
function sendStreamFile($url, $file){
    $opts = array(
        'http' => array(
            'method' => 'POST',
            //'header' => 'Content-Type: application/json; charset=utf-8',
            'header' => 'content-type:application/x-www-form-urlencoded',
            'content' => $file
        )
    );
    $context = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    return $response;

    //return $this ->prihttp($url, 'POST', $file);

}
    private  function prihttp($url, $method, $postfields = NULL, $headers = array( 'Accept-Charset: utf-8'))
    {
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_ENCODING, "");
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, FALSE);

        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
        }

        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);

        $response = curl_exec($ci);
        $httpCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $httpInfo = curl_getinfo($ci);
        \Think\Log::record('testing...'.$url.$httpCode.json_encode($httpInfo), 'ERR', true);

        curl_close($ci);
        return $response;
    }

}

