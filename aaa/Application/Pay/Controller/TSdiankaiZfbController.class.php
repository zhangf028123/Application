<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

/**
 *
 * 原生支付宝h5(TS点卡)
 */
class TSdiankaiZfbController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');//
        $parameter = [
            'code'      => 'TSdiankaiZfb',
            'title'     => '原生支付宝h5(TS点卡)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        Log::record('TSdiankaiZfb $parameter ='.json_encode($parameter),'ERR',true);
        $return = $this->orderadd($parameter);
        $data = [
            'merchant_code'  => $return['mch_id'], //
            'order_sn'   => $return['orderid'],
            'amount'=>$return["amount"],
            'notify_url'     => $return['notifyurl'],
            'return_url'   => $return['callbackurl'],
        ];
        $data['sign'] = $this->createSign_1($return["signkey"], $data);
        $data['content_type']       = 'json';

        $response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('TSdiankaiZfb pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == '0'){
            header("location: {$response['pay_url']}");
        }
        echo $response;
    }
    //还差一个回调就可以了
/*
merchant_code	string	Y	Y	商户号
order_sn	string	Y	Y	商户订单号
sys_no	string	Y	Y	平台订单号
amount	string	Y	Y	付款金额
resp_code	string	Y	Y	支付状态 00成功 其它失败
sign	string	Y	N	签名 详见签名	
商户收到异步通知输出 SUCCESS 表示通知成功，其它失败
平台在收到通知失败的情况下，会间隔几分钟重复通知数次
*/
    //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" TSdiankaiZfb \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，TSdiankaiZfb notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
        $data = [
            "merchant_code" => $_REQUEST["merchant_code"], // 商户ID
            "order_sn" =>  $_REQUEST["order_sn"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "resp_code" =>  $_REQUEST["resp_code"], // 交易状态码
            "sys_no" =>  $_REQUEST["sys_no"], // 支付流水号

        ];
        $publiKey = getKey($response["order_sn"]); // 密钥
        $result = $this->_verify($data, $publiKey);

        if ($result) {
            if ($_REQUEST["resp_code"] == "00"){
                try {
                    $Order = M("Order");
                    $o = $Order->where(['pay_orderid' => $_REQUEST["order_sn"]])->find();
                    if (!$o) {
                        Log::record('上游wap回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                        exit('error:order not fount' . $_REQUEST["order_sn"]);
                    }

                    $pay_amount = $o['pay_amount'];
                    $diff = $response['amount'] - $pay_amount;//db 记录的充值金额是否和回调的一致
                    if ($diff <= -1 || $diff >= 1) { // 允许误差一块钱
                        Log::record("上游wap回调失败,金额不等：{$response['amount'] } != {$pay_amount}," . json_encode($response), 'ERR', true);
                        exit('error: amount error!');
                    }
                    $old_order = $Order->where(['upstream_order'=>$response['sys_no']])->find();
                    if( $old_order && $old_order['pay_orderid'] != $response["order_sn"]){
                        Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    }
                    $Order->where(['pay_orderid' => $response["orderid"]])->save([ 'upstream_order'=>$response['sys_no']]);
                    //update  我们平台定义的订单
                    $this->EditMoney($response['order_sn'], '', 0);
                    exit("SUCCESS");
                } catch (Exception $e) {
                    Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                    exit("Exception");
                }
            }else{
                Log::record('TSdiankaiZfb error:order  fail !', 'ERR', true);
                exit('error:order fail!');
            }
        } else {
            Log::record('TSdiankaiZfb error:check sign Fail!', 'ERR', true);
            exit('error:check sign Fail!');
        }
    }

    /**
     * 什么地方调用到这里的那？
     */
    //同步通知
    public function callbackurl()
    {
        $Order = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        } else {
            exit("error");
        }
    }

    

    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     * 第一步，将需要签名的参数按照参数名ASCII码从小到大排序（字典序），使用URL键值对的格式
     * （即key1=value1&key2=value2）拼接成字符串stringA。

    第二步，在stringA最后拼接上key（即key1=value1&key2=value2key）得到stringSignTemp字符串，并对stringSignTemp进行MD5运算，再将得到的字符串所有字符转换为小写

     */
    private function createSign_1($Md5key, $list)
    {
        $temp=$this->createToSignStr_1($Md5key, $list);
        $sign = strtolower(md5($temp));
        Log::record('createToSignStr ===== ：'.$temp.' sign= '.$sign,'ERR',true);
        return $sign;
    }

    /**
     * @param $Md5key
     * @param $list
     * @return
     * string第一步，将需要签名的参数按照参数名ASCII码从小到大排序（字典序），使用URL键值对的格式
     * （即key1=value1&key2=value2）拼接成字符串stringA。
    第二步，在stringA最后拼接上key（即key1=value1&key2=value2key）
     * 得到stringSignTemp字符串，并对stringSignTemp进行MD5运算，
     * 再将得到的字符串所有字符转换为小写

     */
    function createToSignStr_1($Md5key, $list){
        ksort($list);
        $md5str = "";
        end($list);
        $lastkey=key($list);
        foreach ($list as $key => $val) {
            //如果是最后一项就不要&
            if($key===$lastkey){
                $md5str = $md5str . $key . "=". $val;
            }else{
                $md5str = $md5str . $key . "=". $val . "&";
            }
        }
        return $md5str.$Md5key;
    }

    private function _verify($requestarray, $md5key){
        $md5keysignstr = $this->createSign_1($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }


}