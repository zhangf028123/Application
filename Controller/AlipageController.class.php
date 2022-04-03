<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;
use AlipayTradePrecreateContentBuilder;
use AlipayTradeService;

require_once 'f2fpay/model/builder/AlipayTradePrecreateContentBuilder.php';
require_once 'f2fpay/service/AlipayTradeService.php';

/// 当面付
class AlipageController extends PayController
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
        $notifyurl   = $this->_site . 'Pay_Alipage_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Alipage_callbackurl.html'; //返回通知

        $parameter = array(
            'code'         => 'Alipage', // 通道名称
            'title'        => '支付宝扫码',
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

        //---------------------引入支付宝第三方类-----------------
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradePrecreateRequest');
        $config = array (
            'sign_type' => "RSA2",
            'alipay_public_key' => $return['signkey'],
            'merchant_private_key' =>$return['appsecret'],
            'charset' => "UTF-8",
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
            'app_id' => $return['appid'],
            'notify_url' => $notifyurl,
            'MaxQueryRetry' => "10",
            'QueryDuration' => "3"
        );
        $qrPayRequestBuilder = new AlipayTradePrecreateContentBuilder();
        $qrPayRequestBuilder->setOutTradeNo($return['orderid']);
        $qrPayRequestBuilder->setTotalAmount($return['amount']);
        $qrPayRequestBuilder->setSubject($body);

        // 调用qrPay方法获取当面付应答
        $qrPay = new AlipayTradeService($config);
        $qrPayResult = $qrPay->qrPay($qrPayRequestBuilder);
        $url = $qrPayResult->qr_code;
        if(empty($url)){
            exit("接口请求错误");
        }
        /*if($this->isMobile()){    // 手机直接跳转
          $encodeInfo = "alipayqr://platformapi/startapp?saId=10000007&qrcode=".$url;
            $location ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($encodeInfo);
            header("Location:".$location);
            //header('Location: '.$url);
        }*/
        // 电脑页面

        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$return['amount']));

        $this->display("WeiXin/alipay");
    }


    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");
       
        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["out_trade_no"], '', 1);
        } else {
            exit("error");
        }
    }

    //异步通知
    public function notifyurl()
    {
        file_put_contents('./Data/ztnotify.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".$_REQUEST['trade_status'].'notifyurl $_POST='.json_encode($_POST)."\r\n\r\n",FILE_APPEND);
//
//        $Order      = M("Order");
//
//        $payStatus = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->getField("pay_status");
//        vendor('Alipay.aop.AopClient');
//        $aop = new \AopClient ();
//        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
//        $aop->appId = 'your app_id';
//        $aop->rsaPrivateKey = '请填写开发者私钥去头去尾去回车，一行字符串';
//        $aop->alipayrsaPublicKey='请填写支付宝公钥，一行字符串';
//        $aop->apiVersion = '1.0';
//        $aop->signType = 'RSA2';
//        $aop->postCharset='GBK';
//        $aop->format='json';
//        $request = new \AlipayTradeOrderSettleRequest ();
//        $request->setBizContent("{" .
//            "\"out_request_no\":\"20160727001\"," .
//            "\"trade_no\":\"2014030411001007850000672009\"," .
//            "      \"royalty_parameters\":[{" .
//            "        \"trans_out\":\"2088101126765726\"," .
//            "\"trans_in\":\"2088101126708402\"," .
//            "\"amount\":0.1," .
//            "\"amount_percentage\":100," .
//            "\"desc\":\"分账给2088101126708402\"" .
//            "        }]," .
//            "\"operator_id\":\"A0001\"" .
//            "  }");
//        $result = $aop->execute ( $request);
//
//        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
//        $resultCode = $result->$responseNode->code;
//        if(!empty($resultCode)&&$resultCode == 10000){
//            echo "成功";
//        } else {
//            echo "失败";
//        }
//


        /*$response  = $_REQUEST;
        $sign      = $response['sign'];
        $sign_type = $response['sign_type'];
        if ($response['trade_status'] == 'TRADE_SUCCESS' || $response['trade_status'] == 'TRADE_FINISHED') {
            $this->EditMoney($response['out_trade_no'], '', 0);
            exit("success");
        }*/

        $response  = $_POST;
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
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save(['bill_account'=>$response['buyer_logon_id'], 'upstream_order'=>$response['trade_no']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success");
            }
        } else {
            file_put_contents('./Data/ztnotify.txt', "【".date('Y-m-d H:i:s')."】验签失败：\r\n".$_REQUEST['trade_status'].'notifyurl $_POST='.json_encode($_POST)."\r\n\r\n",FILE_APPEND);
            exit('error:check sign Fail!');
        }

    }


    /**
     * 订单分账
     * @param $tradeNo   支付宝订单号
     * @param $tradeIn   入款账号pid-即钱分到哪个账号
     * @param $tradeOut  出款账号pid
     * @param $appId     应用appid
     * @param $connectKey   应用公钥
     * @return \SimpleXMLElement|string
     */
    public static function settle($tradeNo,$tradeIn){
        //变更验签账号密钥
        $Order      = M("Order");
        $orderInfo = $Order->where(['pay_orderid' => $tradeNo])->find();
        file_put_contents('./Data/settle1.txt', "【".date('Y-m-d H:i:s')."】分账开始：\r\n"."orderid:".$tradeNo."|分账给:".$tradeIn."\r\n\r\n",FILE_APPEND);
        vendor('Alipay.aop.AopClient');


        if(empty($orderInfo)){
            return false;
        }
        $channel_account  = M('channel_account')
            ->where(['id' => $orderInfo['account_id']])
            ->find();

        if(empty($channel_account)){
            file_put_contents('./Data/notfound.txt', "【".date('Y-m-d H:i:s')."】找不到账户：\r\n"."orderid:".$tradeNo."|分账给:".$tradeIn."\r\n\r\n",FILE_APPEND);
            return false;
        }

        if($channel_account['appid']!=$orderInfo['account']){
            file_put_contents('./Data/appidnotfound.txt', "【".date('Y-m-d H:i:s')."】校验APPID：\r\n"."orderid:".$tradeNo."|分账给:".$tradeIn."\r\n\r\n",FILE_APPEND);
            return false;
        }

        $config['app_id'] = $orderInfo['account'];
        $config['merchant_private_key'] = $channel_account['appsecret'];
        $config['alipay_public_key'] = $orderInfo['key'];

        if(empty($config['app_id'])||empty( $config['merchant_private_key'])||$config['alipay_public_key']){
            file_put_contents('./Data/paramerr.txt', "【".date('Y-m-d H:i:s')."】参数错误：\r\n"."orderid:".$tradeNo."|分账给:".$tradeIn."\r\n\r\n",FILE_APPEND);
        }


        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $config['app_id'];
        $aop->rsaPrivateKey = $config['merchant_private_key'];
        $aop->alipayrsaPublicKey=$config['alipay_public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';

        $request = new \AlipayTradeOrderSettleRequest ();

        $bizContent['out_request_no'] = time().rand(10000,99999);
        $bizContent['trade_no'] = $tradeNo;
        $royaltyParameters['trans_out'] = $orderInfo['mch_id'];
        $royaltyParameters['trans_in'] = $tradeIn;
        $royaltyParameters['amount_percentage'] = 100;
        $royaltyParameters['desc'] = '分帐给'.$royaltyParameters['trans_in'];
        $bizContent['royalty_parameters'][] = $royaltyParameters;
        $bizContent['operator_id'] = '';
        $json = json_encode($bizContent);
        $request->setBizContent($json);
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        file_put_contents('./Data/fzresult.txt', "【".date('Y-m-d H:i:s')."】分账结果：\r\n".$result."\r\n\r\n",FILE_APPEND);
        if(!empty($resultCode)&&$resultCode == 10000){
            return ['code'=>$resultCode,'msg'=>'成功'];
        } else {
            return ['code'=>$resultCode,'msg'=>$result->$responseNode->sub_msg];
        }
    }


}
