<?php

namespace Pay\Controller;

use Org\Util\WxH5Pay;

class YouzanController extends PayController {

    public function __construct() {
        parent::__construct();
    }

    public function Pay($array) {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site.'Pay_Youzan_notifyurl.html'; //yibu
        //$callbackurl = $this->_site . 'Pay_Zr_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Youzan', // 通道名称
            'title' => '支付宝H5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body' => $body,
            'channel' => $array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $data = array(
            'merchant_on' => $return['mch_id'],
            'merchant_order_no' => $return['orderid'],
            'pay_type' => 'alipay',
            'amount' => $return['amount'].'00',
            'serial_no' => md5('ccaonima'),
            'lower_url' => $notifyurl,
        );

        $Md5key = $return['signkey'];   //密钥
        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . $val;
        }

        $sign = strtoupper(md5($md5str .$Md5key));
        $data["sign"] = $sign;

        $this->setHtml($return['gateway'], $data);
    }

    //红包请求
    public function zhudongjk($data,$url){

        $Url =  $url;
        $post_data = json_encode($data);
        $ch = curl_init($Url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $result = curl_exec($ch);
        curl_close ( $ch );
        return $result;
    }


    public function callbackurl() {

        $Order = M("Order");
        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->getField("pay_status");
        $callbackurl = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->getField("pay_callbackurl");
        //var_dump($callbackurl);die;
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST['out_trade_no'], 'Alired', 1);
            header("location:$callbackurl");
            die;
            exit('交易成功！');
        } else {
            header("location:$callbackurl");
            die;
        }
    }

 
    // 服务器点对点返回
    public function notifyurl() {

        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        \Think\Log::record("Youzan notifyurl clientip={$clientip},$_POST=".json_encode($response),'ERR',true);

        $Order = M("Order");
        //$apikey = $Order->where(['pay_orderid' => $_REQUEST["merchant_order_no"]])->getField("key");
        $returnArray = array( // 返回字段
            "merchant_on" => $_REQUEST["merchant_on"], // 商户ID
            "merchant_order_no" =>  $_REQUEST["merchant_order_no"], // 订单号
            "order_no" =>  $_REQUEST["order_no"], // 交易金额
            "amount" =>  $_REQUEST["amount"], // 交易时间
            "serial_no" =>  $_REQUEST["serial_no"], // 支付流水号
            "lower_url" =>  $_REQUEST["lower_url"], // 支付流水号
    //        'sign'=>$_REQUEST["sign"]
        );
        $Md5key = getKey($_REQUEST["merchant_order_no"]); //'501905b47220488994cbc5d1e2ecedc8';   //密钥
        file_put_contents('Data/22222.txt',$returnArray);
        ksort($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . $val;
        }

        $sign = strtoupper(md5($md5str .$Md5key));
        if ($sign == $_REQUEST["sign"]) {
            $pay_amount = $Order->where(['pay_orderid' => $_REQUEST["merchant_order_no"]])->getField("pay_amount");
            if($_REQUEST["amount"] < $pay_amount){
                file_put_contents('Data/22222.txt',"金额<$pay_amount");
                echo 'sb';die;
            }
            $this->EditMoney( $_REQUEST["merchant_order_no"], 'Alired', 0);
            exit("SUCCESS");
        }else{
            file_put_contents('Data/22222.txt',"签名失败：$md5str, $Md5key=$sign,".$_REQUEST["sign"]);
            exit("FAIL");
        }
    }
}