<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XjHbXYController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XjHbXY',
            'title'     => '现金红包(xy)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'merchant'  => $return['mch_id'], //
            'amount'        => sprintf('%.2f',$return['amount']),
            'pay_code' => 'alipay_h5_b',
            'order_no'   => $return['orderid'],
            'notify_url'     => $return['notifyurl'],
            'return_url'     => $return['callbackurl'],
            'order_time'     => time(),
            'attach'     => 'ceshi',
            'cuid' => 'cuid',

        ];
        $data['sign'] = $this -> getSign($data, $return['gateway']);

        $response = $this -> postnew($return['gateway'], $data);
        //$response = HttpClient::post($return['gateway'], $data);    //
        //$response = HttpClient::get($return['gateway'], $data);
        Log::record(' XjHbXY pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);


        echo   $response;


    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XjHbXY notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_order_no"]); // 密钥

        $data = [
            'merchant'    => I("request.merchant"),
            'amount'    => I("request.amount"),
            'sys_order_no'    => I("request.sys_order_no"),
            'out_order_no'    => I("request.out_order_no"),
            'order_time'    => I("request.order_time"),
            'attach'    => I("request.attach"),
            'cuid'  => I("request.cuid"),
            'realPrice'  => I("request.realPrice"),
        ];

        $sign = $this -> getSign($data, $publiKey);

        if ($data['sign'] == $sign) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["out_order_no"]])->find();
                if(!$o){
                    Log::record('现金红包(xy)回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["out_order_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("现金红包(xy)回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $diff1 = $response['realPrice'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("现金红包(xy)回调失败,金额不等：{$response['realPrice'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error111!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['sys_order_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_order_no"]){
                    Log::record("现金红包(xy)回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["out_order_no"]])->save([ 'upstream_order'=>$response['sys_order_no']]);
                $this->EditMoney($response['out_order_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('现金红包(xy)回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('XjHbXY error:check sign Fail!','ERR',true);
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



    private function posttest($pay_notifyurl, $data) {
        $notifystr = "";
        foreach ($data as $key => $val) {
            $notifystr = $notifystr . $key . "=" . $val . "&";
        }
        $notifystr = rtrim($notifystr, '&');
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $pay_notifyurl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $notifystr);
        $contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    private function postnew($url,$parac){
        $postdata=http_build_query($parac);
        //$postdata = json_encode($parac, JSON_UNESCAPED_UNICODE);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }

    /**
     * 数据验签
     * @param array $data
     * @param string $key
     * @return string
     */
    public function getSign($data,$key)
    {
        $para_filter = $this->paraFilter($data);
        $para_sort   = $this->argSort($para_filter);
        $prestr      = $this->createLinkString($para_sort);

        return $this->md5Encrypt($prestr, $key);
    }
    /**
     * 除去数组中值为空参数
     * @param array $data
     * @return array
     */
    public function paraFilter($data)
    {
        $para_filter = array();
        foreach ($data as $key=>$val)
        {
            if($key == "sign" || $val == '' || $key == "json")continue;
            else $para_filter[$key] = $data[$key];
        }
        return $para_filter;
    }
    /**
     * 对待签名参数数组排序
     * @param array $para
     * @return array
     */
    public function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;

    }
    /**
     *把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para
     * @return bool|string
     */
    public function createLinkString($para) {
        $arg  = "";
        foreach ($para as $key=>$val)
        {
            $arg.=$key."=".$val."&";
        }

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    public function md5Encrypt($prestr, $key) {
        $prestr = $prestr . 'key='.$key;
        return md5($prestr);
    }
}