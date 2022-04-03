<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

class AliwapController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        $orderid     = I("request.pay_orderid");
        $body        = I('request.pay_productname');

        $parameter = array(
            'code'         => 'Aliwap', // 通道名称
            'title'        => '支付宝手机网站支付',
            'exchange'     => 1, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => $orderid,
            'body'         => $body,
            'channel'      => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $return['subject'] = $body;

        $url = U('Aliwap/wappage', ['orderid'=>$return['orderid']], true, true);

        $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        \Think\Log::record('Aliwap Pay,$useragent='.json_encode($useragent),'ERR',true);
        
        if($this->channel['istest'] || !$this->isMobile()){   // 测试/电脑要显示二维码
            $QR = "Uploads/codepay/". $return['orderid'] . ".png";
            import("Vendor.phpqrcode.phpqrcode",'',".php");
            \QRcode::png($url, $QR, "L", 20);
            $this->assign("imgurl", '/'.$QR);
            $this->assign('params',$return);
            $this->assign('orderid',$return['orderid']);
            $this->assign('money',sprintf('%.2f',$return['amount']));
            $this->display("WeiXin/alipay");
        }else {
            header('location:'.$url);   // 直接跳转
        }
/*
        if($this->isInAlipayClient()){
            return $this->wappage($return['orderid']);
        }

        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$return['amount']));

        if($this->isMobile()){
            $this->assign('zfbpayUrl',$url);
            $this->display("WeiXin/alipayori");
        }else{
            $this->display("WeiXin/alipay");
        }*/
    }

    // 电脑浏览器显示二维码
    public function wappage($orderid){
        if(!$orderid)die('orderid error');
        $order = M('Order')->where(['pay_orderid'=>$orderid])->find();
        if(!$order)die("order {$orderid} not found error");
        $notifyurl   = $this->_site . 'Pay_Aliwap_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Aliwap_callbackurl.html'; //返回通知

        //---------------------引入支付宝第三方类-----------------
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        //组装系统参数
        $data = [
            'out_trade_no' => $order['pay_orderid'],
            'total_amount' => sprintf('%.2f', $order['pay_amount']),
            'subject'      => $order['pay_productname'],
            'product_code' => "QUICK_WAP_WAY",  // 手机网站支付 固定此值
        ];

        $channel_account = M('ChannelAccount')->where(['id'=>$order['account_id']])->find();
        if(!$channel_account)die("account {$order['account_id']} not found error");
        $sysParams               = json_encode($data, JSON_UNESCAPED_UNICODE);
        $aop                     = new \AopClient();
        $aop->gatewayUrl         = "https://openapi.alipay.com/gateway.do";
        $aop->appId              = $channel_account['appid'];
        $aop->rsaPrivateKey      = $channel_account['appsecret'];
        $aop->alipayrsaPublicKey = $channel_account['signkey'];
        $aop->apiVersion         = '1.0';
        $aop->signType           = 'RSA2';
        $aop->postCharset        = 'UTF-8';
        $aop->format             = 'json';
        $aop->debugInfo          = true;
        $request                 = new \AlipayTradeWapPayRequest();
        $request->setBizContent($sysParams);
        $request->setNotifyUrl($notifyurl);
        $request->setReturnUrl($callbackurl);
        $result = $aop->pageExecute($request,'post');
        die($result);
    }

    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");
       
        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->getField("pay_status");
        if ($pay_status > 0) { // 已支付
            //exit("error");
            $this->EditMoney($_REQUEST["out_trade_no"], '', 1);
        } else {
            exit("error: unpaied!");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_POST;

        \Think\Log::record('notifyurl $_POST='.json_encode($_POST),'ERR',true);
        $sign      = $response['sign'];
        $sign_type = $response['sign_type'];
        unset($response['sign']);
        unset($response['sign_type']);
        $publiKey = getKey($response["out_trade_no"]); // 密钥

        ksort($response);
        $signData = '';
        foreach ($response as $key => $val) {
            $signData .= $key . '=' . $val . "&";
        }
        $signData = trim($signData, '&');
        //$checkResult = $aop->verify($signData,$sign,$publiKey,$sign_type);
        $res    = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publiKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $result = (bool) openssl_verify($signData, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

        if ($result) {
            if ($response['trade_status'] == 'TRADE_SUCCESS' || $response['trade_status'] == 'TRADE_FINISHED') {
                $Order      = M("Order");
                //$pay_status = $Order->where(['pay_orderid' => $response["out_trade_no"]])->find();
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save(['bill_account'=>$response['buyer_logon_id'], 'upstream_order'=>$response['trade_no']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success");
            }
        } else {
            \Think\Log::record('notifyurl-error:check sign Fail!','ERR',true);
            exit('error:check sign Fail!');
        }

    }

}
