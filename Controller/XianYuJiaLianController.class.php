<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XianYuJiaLianController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XianYuJiaLian',
            'title'     => '咸鱼（jl）',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $param = [
            "mch_id" => $return['mch_id'],
            "out_trade_no" => $return['orderid'],
            "total" => $return["amount"],
            "timestamp" => time(),
            "type" => $return['appid'],
            "notify_url" => $return['notifyurl'],
            "return_url" => $return['callbackurl'],

        ];

        $request_key = 'KZ8uhmh8RP9rs4oCLJchhyVwxCkLFHx4';
        $secret_key  = $return["signkey"];


        //链接参数
        ksort($param);
        $str = '';
        foreach ($param as $k => $v) {
            if (empty($v)) continue;
            $str = $str . $k . $v;
        }
        $str = !empty($request_key) ? $str . $secret_key .  $request_key : $str . $secret_key;
        $param['sign'] = strtoupper(md5($str));
        $param['request_token'] = $request_key;
        $param['is_code'] = '1'; //默认为 1 ，为 1 请查看文档，请求下单成功返回的参数
        $url = $return['gateway'];
        $response = $this->api_post($url,$param);
        $cost_time = $this->msectime() - $start_time;
        Log::record('XianYuJiaLian pay url='.$return['gateway'].'data='.json_encode($param).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == '200'){
            $return = [
                'result' => 'ok',
                'orderStr' => $response['data']['qr_url'],
            ];
            $this->ajaxReturn($return);
        }
        echo json_encode($response);
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XianYuJiaLian \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XianYuJiaLian notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_trade_no"]); // 密钥

        $data = [
            'out_trade_no'    => I("request.out_trade_no"),
            'callbacks'    => I("request.callbacks"),
            'total'    => I("request.total"),
            'pay_time'    => I("request.pay_time"),
            'boby'    => I("request.boby"),
        ];
        $str = 'callbacks'.$data['callbacks'].'out_trade_no'.$data['out_trade_no'].'pay_time'.$data['pay_time'].'total'.$data['total'].$publiKey;
        $sign = strtoupper(md5($str));

        if ($response['sign'] == $sign && $response['callbacks'] = 'CODE_SUCCESS') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["out_trade_no"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['total'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$response['total'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }

                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success ");
            }catch (Exception $e){
                Log::record('上游wap回调失败,发生异常：'.$e->getMessage(),'ERR',true);
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

    private function api_post($url,$param)
    {
        $headers = array('Content-Type: application/x-www-form-urlencoded');
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($param)); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $result;
    }
}