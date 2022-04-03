<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class TbhbWenXiController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'TbhbWenXi',
            'title'     => '淘宝红包（wenxi）',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'mch_no' => $return['mch_id'],
            'app_id' => $return['appid'],
            'nonce_str' => 'adf34afgadfaddadffadfadfbc',
            'trade_type' => 'TB_HB',
            'total_fee' => $return["amount"] * 100,
            'body' => 'TRADE',
            'notify_url' => $return['notifyurl'],
            'out_trade_no' => $return['orderid'],

        ];
        $data['sign'] = $this->_createSign($return["signkey"], $data);

        //$response = $this ->curlPost($return['gateway'], $data);    //
        $response = $this -> http_post_data_json($return['gateway'],json_encode($data,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $cost_time = $this->msectime() - $start_time;
        Log::record('Tbhbwenxi pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);

        $response = json_decode($response, true);
        if($response['return_code'] == "0000"){
            //header("location: {$response['data']['payUrl']}");

            $contentType = I("request.content_type");
            if ($contentType == 'json') {
                $return = [
                    'result' => 'ok',
                    'url' => $response['pay_info'],
                ];
                $this->ajaxReturn($return);
            }else{
                header("location: {$response['pay_info']}");
            }

        }
        echo json_encode($response);
    }

    //异步通知
    public function notifyurl()
    {
        //$response  = $_REQUEST;
        $response = file_get_contents("php://input");
        $response = json_decode($response, true);
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" Tbhbwenxi \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Tbhbwenxi notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["out_trade_no"]); // 密钥


        $result = $this->_verify($response, $publiKey, $response['sign']);

        if ($result && $response['trade_status'] == "SUCCESS") {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["out_trade_no"]])->find();
                if(!$o){
                    Log::record('淘宝红包回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["out_trade_no"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['total_fee'] / 100 - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("淘宝红包回调失败,金额不等：{$response['total_fee'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['trans_no']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["out_trade_no"]){
                    Log::record("淘宝红包回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["out_trade_no"]])->save([ 'upstream_order'=>$response['trans_no']]);
                $this->EditMoney($response['out_trade_no'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('淘宝红包回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('淘宝红包 error:check sign Fail!','ERR',true);
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



    private function _verify($data, $key, $sign){
        unset($data['sign']);
        $strs = strtoupper(md5($this->createToSignStr($key, $data)));

        return $strs == $sign ? true : false;

    }



    private function _createSign($key, $data){
        $sign = strtoupper(md5($this->createToSignStr($key, $data)));
        return $sign;

    }

    private function curlPost($uri, $data = array())
    {
        /***异步请求Post提交***/
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL,$uri);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS,json_encode($data));
        $return = curl_exec ($ch);//返回内容
        curl_close ($ch);
        /***返回JSON格式转换***/
        $return = json_decode($return,true);
        return $return;

    }

    private function http_post_data_json($url, $data_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json; charset=utf-8",
                "Content-Length: " . strlen($data_string))
        );
        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $return_content;
    }


}