<?php

namespace Pay\Controller;

class DuoduoPageController extends DuoduoBase
{
    protected function _initialize(){
        $this->gatewayUrl = '/wap/payment/gopayv2'; // 当面付
    }

    //支付
    public function Pay($array)
    {
        $orderid     = I("request.pay_orderid");
        $body        = I('request.pay_productname');
        $notifyurl   = $this->_site . 'Pay_DuoduoPage_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_DuoduoPage_callbackurl.html'; //返回通知

        $parameter = array(
            'code'         => 'DuoduoPage', // 通道名称
            'title'        => '多多当面付',
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
            'payType'   => 'alipaywap',
            'returnUrl' => $callbackurl,
            'notifyUrl' => $notifyurl,
            'remark'    => 'DuoduoPage',
            'sign'      => '',
        ];
        $url = $return['gateway']. $this->gatewayUrl . '?'. $this->sign($data, $return['appsecret']);

        if(empty($url)){
            exit("接口请求错误");
        }
        $html = file_get_contents($url);
        if(empty($html)){
            exit("接口请求错误");
        }
        $html = json_decode($html,true);
        if($html['code']){
            $imgurl = $html['wapUrl'];
        }else{
            exit($html['msg']);
        }
        // 电脑页面
        //$this->cors();

        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($imgurl, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        //$this->assign("url", $url);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$return['amount']));

        $this->display("WeiXin/alipay");
    }

    function cors() {
        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
            // you want to allow, and if so:
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }
        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
    }
}
