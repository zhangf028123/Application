<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

/// 现金红包
class XjHbController extends PayController{
    protected function _initialize(){
        $this->gatewayUrl = '/pay/index/tdd';
    }

  public function Pay($array){
    $orderid     = I("request.pay_orderid");
    $body        = I('request.pay_productname');
    $notifyurl   = $this->_site . 'Pay_WangYi_notifyurl.html'; //异步通知
    $callbackurl = $this->_site . 'Pay_DuoduoPage_callbackurl.html'; //返回通知

    $parameter = array(
      'code'         => 'XjHb', // 通道名称
      'title'        => '现金红包',
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

    $data = [
      'appId'     => $return['appid'],
      'sOrderBn'  => $return['orderid'],
      'amount'    => $return['amount'],
      'returnUrl' => ($callbackurl),
      'notifyUrl' => ($notifyurl),
      'remark'    => 'WangYi',
        'shopName' => $return['mch_id'], // 淘宝登录名称
      'sign'      => '',
    ];
    $url = $return['gateway']. $this->gatewayUrl . '?'. $this->sign($data, $return['appsecret']);
    //$this->setHtml($return['gateway']. $this->gatewayUrl, $data);

    // ['code'=> '1', 'msg'=>'', 'payUrl'=>]
    /*if(empty($url)){
      exit("接口请求错误");
    }*/
    header("location: {$url}");
      $response = file_get_contents($url);
      Log::record('WangYi pay url='.$url.',data='.json_encode($data).',response='.$response,'ERR',true);
      $response = json_decode($response, true);
      $response['qrcode'] = $response['url'] = $response['payUrl'];
    $contentType = I("request.content_type");
      //if($array['pid'] == 938)
      {
          /*$cache      =   Cache::getInstance('redis');
          $content = file_get_contents($response['url']);
          $info = parse_url($response['url']);
          list($t, $tv) = explode('=', $info['query']);
          $cache->setex($tv, 600, $content);*/
          /*$response['url'] = "alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=".urlencode( $response['payUrl']);
          //$response['url'] ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($response['url']);
          $response['url'] ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($response['url']);
          $response['url'] = "alipays://platformapi/startapp?appId=20000067&clientVersion=3.7.0.0718&url=".urlencode( $response['payUrl']); */// 先打开支付宝，在支付宝里面打开链接，安卓下有转圈的加载效果
          $response['url'] = "alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=".urlencode( $response['payUrl']);
          $response['url'] ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($response['url']);
      }
      if ($response['code'] != '1' || $contentType == 'json'
      ){
          if($response['code'] != '1'){    // 记录下单失败的记录
              file_put_contents("Data/WangYi_failed.txt",json_encode($response).",gateway={$return['gateway']},\$_REQUEST=".json_encode($_REQUEST).",\$data=".json_encode($data)."\n", FILE_APPEND);
          }
          $this->ajaxReturn($response);
      }
      $this->assign("imgurl", $response['qrcode'] );
      //$this->assign("data", $data);
      $this->assign('params',$return);
      $this->assign('orderid',$return['orderid']);
      $this->assign('zfbpayUrl',$response['url']);
      $this->assign('money',sprintf('%.2f',$return['amount']));
      /*if(I("request.pay_memberid") == "190973009"){
          file_put_contents("Data/tdd_debug.txt","调试".json_encode($response).",gateway={$return['gateway']},\$_REQUEST=".json_encode($_REQUEST).",\$data=".json_encode($data)."\n", FILE_APPEND);
      }*/
      if (parent::isMobile()){ // 手机直接跳转
          //header("location: {$response['url']}");
      }
      $this->display("ZhiFuBao/alipayori");
  }

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
    $response  = $_POST;
    $clientip = $_SERVER['REMOTE_ADDR'];
    $ip = getIP(); // 可能是伪造的
    if($clientip != $ip){
      Log::record("可能伪造的ip，WangYi notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
      die("not ok1");
    }

    $publiKey = getKey($response["sOrderBn"]); // 密钥

    $result = $this->_verify($response, $publiKey);

    if ($result) {
      //if ($response['status'] == 'ok' ) {
      try{
        $Order      = M("Order");
        $o = $Order->where(['pay_orderid' => $response["sOrderBn"]])->find();
        if(!$o){
          Log::record('WangYi回调失败,找不到订单：'.json_encode($response),'ERR',true);
          exit('error:order not fount'.$response["sOrderBn"] );
        }
        if(!$this->_verify_md5($response, $o['appsecret'])){
            exit('md5 not match.');
        }
        $pay_amount = $o['pay_amount'];
        $diff = $response['amount'] - $pay_amount;
        if($diff < -0.5 || $diff > 1 ){
          Log::record("WangYi回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
          exit('error: fuck1!');
        }
        $Order->where(['pay_orderid' => $response["sOrderBn"]])->save([ 'upstream_order'=>$response['out_biz_no']]);    // 网易流水号
        $this->EditMoney($response['sOrderBn'], '', 0);
        exit("success");
      }catch (Exception $e){
        Log::record('WangYi回调失败,发生异常：'.$e->getMessage(),'ERR',true);
        exit("Exception");
      }
      //}
    } else {
      exit('error:check sign Fail!');
    }
  }
    private function _verify_md5($data, $apiKey){
      $str = "appId={$data['appId']}&systemOrderBn={$data['systemOrderBn']}&sOrderBn={$data['sOrderBn']}&amount={$data['amount']}&".$apiKey;
        $calcSign=md5($str);
        $strData= $data['md5Summary'];// 收到的md5Summary
        if($calcSign != $strData){
            Log::record("WangYi回调失败,MD5不对：$calcSign != $strData, str=$str".json_encode($data),'ERR',true);
            return false;
        }
        return true;
    }
  private function _verify($data, $publiKey){
      $strData= $data['md5Summary'];// 收到的md5Summary
    $signature= $data['rsaSign'];// 收到的签名值rsaSign
    $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
      wordwrap($publiKey, 64, "\n", true) .
      "\n-----END PUBLIC KEY-----"; // 平台公钥
    if (!openssl_get_publickey($publicKey)) {
        Log::record("WangYi回调失败,打开公钥失败：".json_encode($data),'ERR',true);
      return false;
    }
    $base64Signature = base64_decode($signature);
    if (!openssl_verify($strData, $base64Signature, $publicKey, OPENSSL_ALGO_SHA256)) {
        Log::record("WangYi回调失败,rsa验签失败：".json_encode($data),'ERR',true);
      return false;
    }
    return true;
  }

  public function testwx(){
      $response['url'] = $payUrl = 'https://qr.alipay.com/_d?_b=peerpay&enableWK=YES&biz_no=2019082504200358581042645490_0c8f0e77e03d120c09f3e75fab18b6c2&app_name=tb&sc=qr_code&v=20190901&sign=32f927&__webview_options__=pd%3dNO';
      //$response['url'] = "alipays://platformapi/startapp?appId=20000067&qrcode=".urlencode( $payUrl); // 先打开支付宝，在支付宝里面打开链接，安卓下有转圈的加载效果
      $response['url'] = "alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=".urlencode( $response['url']);

      $response['url'] ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($response['url']);

      $this->assign('zfbpayUrl',$response['url']);
    return $this->display("ZhiFuBao/alipayori_tdd");
  }
}
