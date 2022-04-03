<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XianYuTaiH5Controller extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XianYuTaiH5',
            'title'     => '咸鱼h5(xianyutai)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $params = [
            'appId' =>  $return['mch_id'],
            'totalFee' =>  $return["amount"],
            'apiOrderNo' =>  $return['orderid'],
            'channelCode' => $return['appid'],
            'notifyUrl' =>  $return['notifyurl'],
        ];
        $sign = $this -> getSign($params);

        if (false === $sign){
            return json_encode(array('code'=>2001,'msg'=>'签名失败'));
        }
        $params['sign'] = $sign;
        $url = $return['gateway'];
        $res = $this -> curl_request($url, $params);
        if (!$res){
            return json_encode(array('code'=>2001,'msg'=>'请求失败'));
        }
        //Log::record('XianYuTai pay url='.$return['gateway'].',data='.json_encode($params).',response='.$res,'ERR',true);

        $res = json_decode($res, true);
        //$res = $res['result'];
        Log::record('tesing...'.json_encode($res), 'ERR', true);
        # 验签
        if (is_array($res) && !empty($res) && 2000 === $res['code']) {

            header("location: {$res['data']}");
        }
        echo json_encode($res);
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XianYuTai \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XianYuTai notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        # 验签
        if (isset($_POST['sign']) && (2 === intval($_POST['status']) )) {
            $resSign = $_POST['sign'];
            $resSign = urldecode($resSign);
            unset($_POST['sign']);
            # 验签
            $res = $this -> _verify($_POST, $resSign);

            if (!$res) {
                Log::record('XianYuTai回调失败,验签失败：'.$res,'ERR',true);
                exit('error:check sign Fail!');
            }
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["orderNo"]])->find();
                if(!$o){
                    Log::record('XianYuTai回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderNo"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['price'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("XianYuTai回调失败,金额不等：{$response['price'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['orderNum']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderNo"]){
                    Log::record("XianYutai回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["orderNo"]])->save([ 'upstream_order'=>$response['orderNum']]);
                $this->EditMoney($response['orderNo'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('XianYuTai回调回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
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

    # 加签
    protected function getSign($params)
    {
        if (!$params || !is_array($params))
            return false;

        $params['charset'] = "utf-8";
        ksort($params);
        $privateKeyHeader = "-----BEGIN RSA PRIVATE KEY-----\n";
        # TODO 私钥
        $privateKeyContent = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDRFpIxpdw1muy4ZWHp8iSG0LJ6kwiNeYDTqnYoRLLhxZt9EI30dqbEp8+ZXw/Yi7yV4rDEOQrBfXSygUGZAheexzaSVG6nkz5Ru5Yd6TQbvz4jgMByQl/K6l3xq0SXj7LWt3/h5XYYN5xhEYFvDPYK3IoE6loyrqfRVDHaF7eYW4F+7+oVLzLj/CTMJbJLpx/ldtdlrH/Q7r5bgBZ/lj5vC3VkU6t10I5zTCtsRbK6h5H1axEIrHcUxxNKOt1PHjrNsOyX6HAbNyB9eGChxOECdN9UPfe6Ty01GqLS3LmY9/HjNEahW9k9e8QlWnK2bnHma2iDthr8VuJpODWkCGlJAgMBAAECggEBALhsXjrofxnRMudalUjSyiEXx7WSJ0MSXu1UN7BBGD9IG2PuzCdK2MIw+k3fqYxphMf4Ec6iObh9PgeNNx0M2WS1do8PZiLtH1TcTwbHAa1PvDF4iUa/ANtsabyWQoQkvaviYywPR2EI/CgqVq5rEkJ2UE8RnsmBgIiE6QvjS49X3vcCZH+OmV/26c9T0QXsnPoWLznjXumSLLgmf4WZH7STJKxy+yzfkbgr5ZCUqz4qqbyhCir/j8vK7a0n8BDP2CQzuSCoC6eObMQD9MDetuYa+jFS3Jh9g3sxoztsfHZmbbcjbjG5LhiRWW6FK1jl/+78i3IpybzeN3kyzXUS9uECgYEA6AAi2HLKLj4rCqyH6tHxQ4K0QNy/x8Rdmyqlxi7h3lywpHs49VKQGO3f6DEtx7QlHJ/sQKhY1Zq9MaFG1+cy8ITGIm1xmjvmRa7hW0IxgUT0Dwctgw97XUrDiHGxnTFgY3WaLdF3B5EQfjdjNYcoUVjc5w89Y7XQvhgH6aElF50CgYEA5reqzXFh/mRcWOcAr6aS43x1ZoUEGTMeGUTxxOJ0vUJ/sSQ8kwK5jV/zIe84tU+knP/sIo/q7Rf1oJy7YfgmfNClSkZgfwUwXXLsZq8STsZqAUn36+657ng6Uvat7HKiDTiWIcC026gd7FJcD60O30egeZz6ncFEdtDYr/TkRp0CgYArABN1UNledtFdehr7EAyKucgVGZVGPoQnBWGSeRAOOdnXsqsc9T+WD0jn7W2RzTbvtkiAt3M3rCWS9FSAIe8UG1fp+6UJtD56/e3xDzTDw/cEbg0mdJEl+nyBZqlH/GXkKWD5SxCny2BmNHfj7PgxE6pl/TIgsAtPoH6e8+o/FQKBgQDbfo7LhvvwdnB4z8QSV8nVDJnwX8nHY59V8QaGBd+EEtjCoTPTtLrsqgT4Gst7ivqTttJjC0I4MBwpohKPIPMUubcW05+IGFqr/OK0yggD07YT5dgsqGBRZCYwuag7k89h/pyuHdSySN2275/P9hd5Cn68VEEFhlHpK8WKmNP37QKBgE8Wqowv9xIFIPM21vYxawI1+iBjLMlq4WAF+SGf4ennxAYVq/O4iCBkKj1v0e5IIn7I72tqlTe8ZXtc4gUNbfa4oraCkr/GVbW1UthuwgAAzEjFddb2IR2Ka/zbF4GH018nq0+btZK+Spvh6W6XMAaeshsIUQn1VuP+R1v2I09M';
        //$privateKeyContent = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC7GmGf6SOSZNgeih5YlWRIZaaOqa7kld8uRB+MpY4vgXgk54LfSarvnCFReYMZaxOVlEJD6B/Zvu2JaCR9AymLKGG59/SJsZKFy13KMBmEuJyI+3AI7XbjyVweDn+qJ0W1Qsl+gmMgU4FiIkotFmCdt5TiK59Wz4q4X+dJo15fwY05FGFE2HEbFv2lUGCjuyfgUHnutRu+ploNuj7zr4qYR81X0QQ1po5iA2sgcorodL+cfIT+fvELFnt3fZb/ZSuMp4mG5W6pSNPTartrfkkPSocdLSfcBM/HdUcweWvvD6xYcdS29DYMXTLQZz53lpS3p8fXwMoLkAFaHsSbOJq3AgMBAAECggEAapUzqZlwhxNlPbhmYwXvE4K8Z6Znl5V8hmmOI1un7I3vN+6d0b6wXkBthnEW6mLhzCKhwPc5NZwaylyF9KTZhIkjaoHCTrHWCtt3eRb/Ymib2ziIcL19LXNmQ0T1GqO2hPQfvi5ec2q+WH26DYci0JVkfZZl9VjS6AbRxVQIDblFk/zaNMFOjpIl7qHebsyMpBqXWTk0whV+GjzrY2pBrRrFtFrt1x7OCnKkkFhMUcesR2CEMLpuBxqAgm/t4g1p2lj4pnsxnh27XMlaS7r46V0wqJEbTP/xx6vQHnLiT9tkdea55vh12TswZVZ6bpR6MN5NsCz0OmXOkYyiIjoMgQKBgQDnUMQ8tCkJrtkZ3kPxHryodOWSvPXuuirzJ9z+L3hcH2/iaWE+S8ph0Z8gxJjraV4KR2HW9HJKC4ypB29d8EXf6Pp8+duMyAF6SIF/h4ZfGiVov4aMF6hLr1cBl0QjwsuPdmYQXkz6/mTCVhxlLKLGrvzAxjv4pemBXhMroNQsdwKBgQDPEcoRx3T+AEGr5lGcU6ZaKrKc1gjkE0/82mRbL1rdpMnFyPirQlyPdrh2apnTbq+y8h8zovC0PF1wIFut/r7GLCDxUWAJG6GRFRlyRkA5vudgTm3vQLVPunchA3QhPU7pJnWBpDlYYMqhAmbh8/noyAyb0Yej7nAKTj3A2ovTwQKBgQCHoTDfExf46H+9jjiyOb6O27P8fTWKm7gxSM9obzcYdQpqbDWrjE8HWhz+3qd3aRnN1xsEKeKVjf4U4honr4mZB4dQHkTgYCmVpMvhlfpw6ujCaYKfxANXFWFjumkmusIWxWqE8HYcuWslE86keC6dZt8mvVVOryGiTCHbc/rUcwKBgQCp9dAGB9DfKxa8Ia+awI4qNCGm2YcyrSdaQ2db8OKESl3TGcIBz9Zpaui9SYI5KQDNwC4cFAG97k5DWkvl5NJxlobzi/dngmZ6zvaz9TWCME95nOZfGp23czWUw2DuZ4P9mrOYVVM8VzX6Mh6AF+FoT8sJmlHbHDqaOt41DSz4AQKBgDBL1dWKZIMVpKZyXO0ocXGTt+V7usIpdPPlExTJPkk0DoTN58Pm8YQxWV75UCREHwPy+5G6EYayJgL117cHemXCEPGVmACNknX1u6a+MyKlHFCYZPk9gkOVp3xkppSV8waQ8Or5tTd9t1ZNEm7LGYkx+uovENNsgPGJnPX6Ry+g';

        $privateKeyContent = wordwrap($privateKeyContent, 64, "\n", true);
        $privateKeyEnd = "\n-----END RSA PRIVATE KEY-----";

        $privateKey = $privateKeyHeader . $privateKeyContent . $privateKeyEnd;


        $keyStr = '';
        foreach ($params as $key => $value) {
            if (empty($keyStr))
                $keyStr = $key . '=' . $value;
            else
                $keyStr .= '&' . $key . '=' . $value;
        }

        if (!$keyStr)
            return false;

        try {
            $key = openssl_get_privatekey($privateKey);
            openssl_sign($keyStr, $signature, $key, "SHA256");
            openssl_free_key($key);
            $sign = base64_encode($signature);
            return $sign ? $sign : false;
        } catch (\Exception $e) {

            return false;
        }
    }


    //验签
    protected function _verify($params, $returnSign){

        if (!$params || !is_array($params))
            return false;

        $params['charset'] = "utf-8";
        ksort($params);
        $publicKeyHeader = "-----BEGIN PUBLIC KEY-----\n";
        # TODO 公钥
        $publicKeyContent = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqDcyBzVx2nt0EFUeMEGRRm6nvMW7lePq2m/bdCX/+Y0BgpjyywLG/JI3JA981cK9/C6nlGEG5wdQoYP/DclPnQpkLlus5ocNaotyLxVhCFjXuh918448oOBIZ90aeaVoVcTGdwCbClxY1Vd8zs69Ns5s5Y7K3fYQMVUFN2a1zP78ExqqCmdYL3mZnPfQMSkXveK1y65vfS+B4+4IpYtUfGHt5MWgxfolSDbrzV2S/HfnAwTS0SZ1E4ocuMmSrDanLdKG5LStXkDwLSSycqFzrlbttXB0WN5lBwGqBkEhy+JcPSOrivz4wnvYvKLQxBEaTn2l4VqzqAD6kRsyMGnvqwIDAQAB';
        //$publicKeyContent = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAt3luIDngKRrNcYPqBt/CwH0L2r37irT9lpB/qOFKo83FsH/D4RFqvMFVLAa/M1mX5OTJoP2nMC/rzdRGf6NylKMHSzYHAF07TlPpkkRBJNRR29SZ29YX7Zl/coQBYCFK+DUgIItw6ZAQvlle09isa+TJLJKVUcIHuJwbfooi/qxfeBPp3Q+p5szTIE87QBmViFnL5NQAHA0pFcBEZxpKlmLK0gM2zcL4drcTmMJJ3vvual5j1xH5dtKK2QZjQwIfcdsTERxBN/HjQ2kPr7zqTp+KqiVxjhF78oE0YnUObf4Z64y9BSuvRy/XuFy/8uCruUSi27sEXnSOUfJmDAifLwIDAQAB';
        $publicKeyContent = wordwrap($publicKeyContent, 64, "\n", true);
        $publicKeyEnd = "\n-----END PUBLIC KEY-----";

        $publicKey = $publicKeyHeader . $publicKeyContent . $publicKeyEnd;

        $keyStr = '';
        foreach ($params as $key => $value) {
            if (empty($keyStr))
                $keyStr = $key . '=' . $value;
            else
                $keyStr .= '&' . $key . '=' . $value;
        }

        if (!$keyStr)
            return false;

        try {
            $key = openssl_get_publickey($publicKey);

            $ok = openssl_verify($keyStr,base64_decode($returnSign), $key, 'SHA256');
            openssl_free_key($key);
            return $ok;
        } catch (\Exception $e) {
            return false;
        }
    }

    //参数1：访问的URL，参数2：post数据
    protected function curl_request($url,$post=[]){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        return $data;
    }

}