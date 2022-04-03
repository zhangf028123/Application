<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class ZftQKController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'ZftQk',
            'title'     => '直付通',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'mchId'  => $return['mch_id'], //
            'appId'  => '5c7d292ee38a4bf2b9017a5c69efa32a',
            'productId' => $return['appid'],
            'mchOrderNo'   => $return['orderid'],
            'currency' => 'cny',
            'amount'        => $return["amount"],
            'notifyUrl'     => $return['notifyurl'],
            'subject' => '测试商品',
            'body' => 'trade',
            'extra' => 'test',
            
        ];
        $data['sign'] = $this->createSign($return["signkey"], $data);
        

    
        $response = $this -> curl_request($return['gateway'], $data);
        //$response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('ZftQk pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
    
        $response = json_decode($response, true);
        if ($response['retCode'] == 'SUCCESS'){

            header("location: {$response['payParams']['payJumpUrl']}");

        }else{
            echo $response['retMsg'];
        }


    }


    //参数1：访问的URL，参数2：post数据
    private function curl_request($url,$post=[]){
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


    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" ZftQk \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，ZftQk notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["mchOrderNo"]); // 密钥
        $data = $response;
        unset($data['sign']);



        $result = $this->_verify($data, $publiKey);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["mchOrderNo"]])->find();
                if(!$o){
                    Log::record('上游拼多多回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["mchOrderNo"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游拼多多回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['channelOrderNo']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["mchOrderNo"]){
                    Log::record("上游拼多多回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["mchOrderNo"]])->save([ 'upstream_order'=>$response['channelOrderNo']]);
                $this->EditMoney($response['mchOrderNo'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('上游拼多多回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('UpPdd error:check sign Fail!','ERR',true);
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

    private function _verify($requestarray, $md5key){
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

}