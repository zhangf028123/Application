<?php

namespace Pay\Controller;

use Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;


class RuTongPayController extends PayController
{
    public function Pay($array)
    {
        $start_time = $this->msectime();
        $body = I('request.pay_productname');//
        $parameter = [
            'code' => 'RuTongPay',
            'title' => '上游Wap',
            'exchange' => 1,
            'gateway' => '',
            'orderid' => '', //商户订单号,
            'out_trade_id' => I("request.pay_orderid"),
            'body' => $body,
            'channel' => $array,
        ];
        $return = $this->orderadd($parameter);
//公共的参数
//orgId 商户号 是 是 平台分配的商户号
//txnType 业务类型标识 是 是 order: 订单类型, query: 订单状态查询,
//bizData 业务数据封装 是 是 将业务数据转为 json 字符串后. 再次使用 base64 编码. 存入此字段中
//sign 数据签名 是 否 见本手册加解密章节
//$pay_fen=intval(fround(loatval($return["amount"]*100)));  元转分
//分转元
// $pay_in_number=number_format($response['pay_in_number'],2);//返回的支付金额，要转成为元才可以，保留两位小数
//json_encode
     $clientip = $_SERVER['REMOTE_ADDR'];
//业务的数据
//tradeNo 商户订单号 是 全部 在同一商户下需保证唯一。 最大长度 30 个英文字符
//orderAmount 订单金额(分) 是 全部 订单金额采用分为计算单位. 不带小数点的正整数. 例如 1 元. 需要转换为 100 分
//channelNo 支付通道编码 是 全部 支付通道编码。 见 “支付通道编码信息” 表
//syncAddr 同步通知地址 是 全部 支付成功后前端跳转地址
//asyncAddr 异步通知地址 是 全部 支付成功后异步回调地址, post 方式调用。发送数据格式为 json
//ipAddr 终端客户 IP 地址 是 全部
//extend 客户端扩展字段 是 全部 回调时原样返回
        $commom_data=[
            'orgId' => $return['mch_id'], //商户号,
            'txnType'=>'order',
        ];
        $data_yw=[
            'tradeNo'=>$return['orderid'], //商户订单号
            'orderAmount'=>intval(fround(loatval($return["amount"]*100))), // 元转分
            'channelNo'=>$return['appid'],//付通道编码。 见 “支付通道编码信息” 表
            'syncAddr'=>'http://117.24.12.119:39001',
            'asyncAddr' => $return['notifyurl'], //异步回调 服务器回调的通知地址
            'ipAddr'=>$clientip,
            'extend'=>'test'
        ];
        $ywu_str=json_encode($data_yw);
        $commom_data['bizData']=base64_encode($ywu_str);
        $data =array_merge($commom_data,$data_yw);
        $sign = $this->getSign($return["signkey"], $data);
        $data['sign'] = $sign;
        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('RuTongPay pay url=' . $return['gateway'] . ' data=' . json_encode($data), 'INFOR', true);
        $response = json_decode($response, true);
        Log::record('RuTongPay pay url=' . $return['gateway'] . 'response=' . json_encode($response) . " cost time={$cost_time}ms", 'INFOR', true);
        if (empty($response)) {
            Log::record('RuTongPay  $response is empty ', 'ERR', true);
            exit();
        }
        if ($response['code'] == '20000') {
            $return_data_json_str=base64_decode($response['data']);
            $return_data=json_decode($return_data_json_str);
            Log::record('RuTongPay pay url=' . $return['gateway'] . '$return_data=' . json_encode($return_data) . " cost time={$cost_time}ms", 'INFOR', true);

            header("location: {$return_data['payUrl']}");
            exit();
        } else {
            Log::record('RuTongPay  $response code is failt ', 'ERR', true);
            exit();
        }
        echo $response;
    }
//参数名称 中文名称 参与签名 参数说明
//orgId 商户号 是 平台分配的商户号
//tradeNo 商户订单号 是 商户自己在创建订单时发送的订单号
//orderNo 平台订单号 是 在创建订单成功后平台生成的订单号
//orderAmount 订单金额(分) 是 订单金额采用分为计算单位. 不带小数点的正整数. 例如 1 元. 需要转换为 100 分
//payTime 支付时间 是 订单在完成支付的时间. 格式为 yyyyMMddHHmmss
//actualAmount 实际支付金额 是 订单金额采用分为计算单位. 不带小数点的正整数. 例如 1 元. 需要转换为 100 分
//state 订单状态 是 订单支付状态: 2000: 待支付, 2001: 已支付, 2002:订单异常
//extend 扩展字段 否 客户端上送原样返回
//sign md5 签名字符串 否 见加密解密章节
 //异步通知
    public function notifyurl()
    {
        $response = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" RuTongPay notifyurl \$response=" . json_encode($response), 'ERR', true);
        $ip = getIP(); // 可能是伪造的
        if ($clientip != $ip) {
            Log::record("伪造的ip，RuTongPay notifyurl， clientip={$clientip}, getIP = $ip", 'ERR', true);
            die("not ok1");
        }
//        $pay_in_number=number_format($response['pay_in_number'],2);
        $data = [
            'orgId' => $_REQUEST["orgId"],
            'tradeNo'=>$response['tradeNo'],
            'orderNo'=>$response['orderNo'],//上游的订单id
            'orderAmount'=>$response['orderAmount'],
            'payTime'=>$response['payTime'],
            'actualAmount'=>$response['actualAmount'],
            'state'=>$response['state'],
            'sign'=> $_REQUEST['sign'],
        ];
        $orderid = $_REQUEST["tradeNo"];//自己的订单号
        $upstream_order_id = $_REQUEST["orderNo"];// 上游的订单id,
        $paymoney = number_format($_REQUEST["orderAmount"]/100, 2);// 交易金额
        $publiKey = getKey($orderid); // 密钥
        $result = $this->verifySign($data, $publiKey);
        if ($result) {
            try {
                $Order = M("Order");
                $o = $Order->where(['pay_orderid' => $orderid])->find();
                if (!$o) {
                    Log::record('上游wap回调失败,找不到订单：' . json_encode($response), 'ERR', true);
                    exit('error:order not fount' . $orderid);
                }
                $pay_amount = $o['pay_amount'];
                $diff = $paymoney - $pay_amount;
                // 允许误差一块钱
                if ($diff <= -1 || $diff >= 1) {
                    Log::record("上游wap回调失败,金额不等：{$paymoney } != {$pay_amount}," . json_encode($response), 'ERR', true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order' => $upstream_order_id])->find();
                if ($old_order && $old_order['pay_orderid'] != $orderid) {
                    Log::record("上游wap回调失败,重复流水号  ：" . json_encode($response) . '旧订单号' . $old_order['pay_orderid'], 'ERR', true);
                }
                $Order->where(['pay_orderid' => $orderid])->save(['upstream_order' => $upstream_order_id]);
                $this->EditMoney($orderid, '', 0);
                exit("success");
            } catch (Exception $e) {
                Log::record('上游wap回调失败,发生异常：' . $e->getMessage(), 'ERR', true);
                exit("Exception");
            }
        } else {
            Log::record('RuTongPay error:check sign Fail!', 'ERR', true);
            exit('error:check sign Fail!');
        }
    }

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
//* @Note  生成签名
//* @param $secret   商户密钥
//* @param $data     参与签名的参数
//* @return string
//*/
    private function getSign($secret, $data)
    {
        // 去空
        $data = array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;
        Log::record('createToSignStr ===== $string_sign_temp  ：' . $string_sign_temp, 'ERR', true);
        Log::record('createToSignStr ===== key  ：' . $secret, 'ERR', true);
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);
        return $result;
    }


    /**
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
    private function verifySign($data, $secret)
    {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);
        Log::record('verifySign ===== $sign2  ：' . $sign2, 'ERR', true);

        if ($sign != $sign2) {
            return false;
        }
        return true;
    }


}