<?php

namespace Pay\Controller;

class DuoduoWapController extends DuoduoBase
{
    protected function _initialize(){
        $this->gatewayUrl = '/wap/paymentwap/payment'; // wap支付
    }

    //支付
    public function Pay($array)
    {
        $orderid     = I("request.pay_orderid");
        $body        = I('request.pay_productname');
        $notifyurl   = $this->_site . 'Pay_DuoduoWap_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_DuoduoWap_callbackurl.html'; //返回通知

        $parameter = array(
            'code'         => 'DuoduoWap', // 通道名称
            'title'        => '多多wap',
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
            //'payType'   => 'alipaywap',
            'returnUrl' => $callbackurl,
            'notifyUrl' => $notifyurl,
            'remark'    => 'DuoduoPage',
            'sign'      => '',
        ];
        $url = $return['gateway']. $this->gatewayUrl . '?'. $this->sign($data, $return['appsecret']);

        if(empty($url)){
            exit("接口请求错误");
        }
        if( ! $this->isMobile()){
            import("Vendor.phpqrcode.phpqrcode",'',".php");
            $QR = "Uploads/codepay/". $return['orderid'] . ".png";
            \QRcode::png($url, $QR, "L", 20);
            $this->assign("imgurl", '/'.$QR);
            //$this->assign("url", $url);
            $this->assign('params',$return);
            $this->assign('orderid',$return['orderid']);
            $this->assign('money',sprintf('%.2f',$return['amount']));

            $this->display("WeiXin/alipay");
        }else{
            header("location: {$url}");
        }
    }

}
