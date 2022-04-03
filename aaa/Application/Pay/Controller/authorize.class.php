<?php

class authorize
{
    protected $appId;
    protected $charset;
    protected $scope;
    //私钥值
    protected $rsaPrivateKey;
    protected $auth_code;
    public function __construct()
    {
        $this->charset = 'utf8';
        $this->appId = "2018020802161942";
        $this->scope = "auth_base";
        $this->rsaPrivateKey = 'MIIEpQIBAAKCAQEA6Xdps4jxumhIh6wO6m19x/6pQKkf/6HjsFYmI6Yl4KzUOrnBeJ76UM3jqOEwoVV5U/6l8L8FPiAyrimyT6NRxh595QruDiSRlT8o5XtBbr9OkiG+b7IlyuxW+mCqhrRvlPnntSfT/xKOM2hlzb457ESQ9iUdaqqtjcTxLzDnyIeicfCFpp+PMObClvoq6kG9J2V8FfF6U0P8bDvB9LRJ/RDZndMMvJQaabPMML/U5nOWx8NPZAO9ejd8h79tzs626QLOs0MHNGX053YKcILq24VNtx+AdVJOFKUimfCu2KFKJWRBKasclBZqjawG+zFpatGLz31fg3IeFyS7VbDhMwIDAQABAoIBAQDOtaXnCjdM3oxpY5QJSEx3ySi+UYA9bG8WcBBwu+kJlryKCnIchFYJOWJ64neWQQGdtvfhwp+3s/Zrcguoq2f5zIGXTCgeaY5k4HkrRghXMBc3F51vdAI2Oy9/nBsgDZ5F/0aChPMVAq7ZIXQRyH2sjcDzz0TObrQfs+H/8IMobg4X+0OHmXekXOHCDbkC1de5SfKg2W3JJGz7+8q2/J0GxljFYw9e1j078xps3QMd/zUpbeLJH4hVEe7JVUZKHVWFcotZN7lXQo5RcwYeYBOV5mZH6uxERwO4WjtiwEpiJX2Opd0uSWlUpFWpMROZJRQJMPPd//iZPbchjMqjvdmBAoGBAPe2cGRlCHx4hNa0Ma9xgkZJWGPOYGQY7ZKhOCtOoTXdYRfiNjpWHa2ih9/A9B86fBzN4ncoXXwZr6rCMtagD2lO2tsdgxcgMfaF8SzSYOl6cFfYHTx5RjB+LH7jjeBFCOKqV8W/hzuspeubCY4HuFWXcddBUzESYfmjHGpvbcXhAoGBAPFG9fi+PLazCHeIiY5p3hRxkOlTVBGRBRycZTXFSWNTz30Ri/Kf5beQ924eJPOyifPT3svv7rVnuHt9FtmFd7NzLEfH9dbCc96rNULEAMRWY0BwMAPpL4HV+aIg8Qny5mFEqZZsTuL38y5s6MaSUmFv6NZmCR3bUNpvX7sbbmGTAoGBAKVHxZY3E4J5p6jacoxtYE8lgSSW/xnKyDmd+KxsuoQGQlJ9TVF/RC4m1CInzLtJeqZ9eS2ocTfsq5l0Ghe6lI3fX4f0GRPFF5E1rcYKWT9vwqXaPSesg3i3t1iy3GdXqKYUopv/P2xBtjOOLsHlxMjXU84ceDW13kmC2+LoloYBAoGBAJx808gTSrmMgO9WRTFzBLDpv301qI8EKfaWkSZA4QplL2wE12nzv0BB69kl//13TPYx8oz+/yn1LbgaN5m5cRuYlZ2w2YgC8rf2/0Jgccbl6NXAbcP7l+5z48b96pfzTOzFZeDEOp4HB1iTFp7EBF5iAPgdkcglmNkz5zkp33u1AoGAQSOBRQyeHuMLvaXOHeY1H4H5JRm1BgxtW+uaOOGTgn3hKLiUDLiGXa38LL1OezZsQ1sSYus22h+61Yr2va6r2xhab+SotMxzkP6l/73rjapMutbF3hQmrhXhmbRU5sXwrQcuE6extjAE6abUOQ/BKf75BXqTR2t+XCkDB/rVU/w=';
    }

    public function geturl($url){
        return "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=".$this->appId."&scope=auth_base&redirect_uri=" . urlencode($url);
    }

    public function setAppid($appid)
    {
        $this->appId = $appid;
    }
    public function setScope($scope)
    {
        $this->scope = $scope;
    }
    public function setAuthCode($authCode)
    {
        $this->auth_code = $authCode;
    }
    public function setRsaPrivateKey($rsaPrivateKey)
    {
        $this->rsaPrivateKey = $rsaPrivateKey;
    }
    /**
     * 获取access_token和user_id
     * @return array
     */
    public function doAuth()
    {
        // https://docs.open.alipay.com/api_9/alipay.system.oauth.token
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.system.oauth.token',//接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'grant_type'=>'authorization_code',
            'code'=>$this->auth_code,
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do',$commonConfigs);
        $result = iconv('GBK','UTF-8',$result);
        return json_decode($result,true);
    }
    /**
     * 获取access_token和user_id
     */
    public function getToken()
    {
        //通过code获得access_token和user_id
        if (!isset($_GET['auth_code'])){
            //触发微信返回code码
            $scheme = $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
            $baseUrl = urlencode($scheme.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
            if($_SERVER['QUERY_STRING']) $baseUrl = $baseUrl.'?'.$_SERVER['QUERY_STRING'];
            //$baseUrl = "http://new-dev.themeeting.cn/Pay_Gaoji_sk.html";
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $this->setAuthCode($_GET['auth_code']);
            return $this->doAuth();
        }
    }
    /**
     * 通过code获取access_token和user_id
     * @param string $code 支付宝跳转回来带上的auth_code
     * @return openid
     */
    public function getBaseinfoFromAlipay($code)
    {
        $this->setAuthCode($code);
        return $this->doAuth();
    }

    /**
     * 构造获取token的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["app_id"] = $this->appId;           // 开发者应用的app_id
        // 回调页面，是 经过转义 的url链接（url必须以http或者https开头），比如：http%3A%2F%2Fexample.com
        // 在请求之前，开发者需要先到开发者中心对应应用内，配置授权回调地址。
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["scope"] = $this->scope;            // 接口权限值，目前只支持auth_user和auth_base两个值
        // 商户自定义参数，用户授权后，重定向到redirect_uri时会原样回传给商户。 为防止CSRF攻击，建议开发者请求授权时传入state参数，该参数要做到既不可预测，又可以证明客户端和当前第三方网站的登录认证状态存在关联。
        $urlObj["state"] = 123456;  // 非必填参数
        $bizString = $this->ToUrlParams($urlObj);
        return "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?".$bizString;
    }

    /**
     * 拼接签名字符串
     * @param array $urlObj
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign") $buff .= $k . "=" . $v . "&";
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 获取用户信息
     * @return array
     */
    public function doGetUserInfo($token)
    {
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.user.userinfo.share',//接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'auth_token'=>$token,
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do',$commonConfigs);
        $result = iconv('GBK','UTF-8',$result);
        return json_decode($result,true);
    }
    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }
    protected function sign($data, $signType = "RSA") {
        $priKey=$this->rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }
    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }
    public function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}