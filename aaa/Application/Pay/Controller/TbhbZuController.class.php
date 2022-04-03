<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class TbhbZuController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'TbhbZu',
            'title'     => '淘宝红包（zu）',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $data = [
            'merchantId'  => $return['mch_id'], //
            'merchantOrderId'   => $return['orderid'],
            'channelCode' => $return['appid'], // ,
            'amount'        => $return["amount"],
            'notifyUrl'     => $return['notifyurl'],

        ];
        $data['sign'] = $this->_createSign($return["signkey"], $data);

        $response = $this ->curlPost($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('TbhbZu pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);

        $response = json_decode($response, true);
        if($response['code'] == 0){
            //header("location: {$response['data']['payUrl']}");

            $contentType = I("request.content_type");
            if ($contentType == 'json') {
                $return = [
                    'result' => 'ok',
                    'url' => $response['data']['payUrl'],
                    ];
                $this->ajaxReturn($return);
            }else{
                header("location: {$response['data']['payUrl']}");
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
        Log::record(" TbhbZu \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，TbhbZu notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["merchantOrderId"]); // 密钥

        $data = [
            'merchantOrderId'    => $response['merchantOrderId'],
            'orderId'    => $response['orderId'],
            'amount'    => $response['amount'],
            'status'    => $response['status'],
        ];
        $result = $this->_verify($data, $publiKey, $response['sign']);

        if ($result && $data['status'] == "1") {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["merchantOrderId"]])->find();
                if(!$o){
                    Log::record('淘宝红包回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["merchantOrderId"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("淘宝红包回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['orderId']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["merchantOrderId"]){
                    Log::record("淘宝红包回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["merchantOrderId"]])->save([ 'upstream_order'=>$response['orderId']]);
                $this->EditMoney($response['merchantOrderId'], '', 0);
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



    private function _verify($requestarray, $md5key, $sign){
        $md5keysignstr = $this->create1($md5key, $requestarray);
        $md5keysignstr = strtoupper(md5($md5keysignstr));

        $pay_md5sign   = $sign;
        return $md5keysignstr == $pay_md5sign;
    }

    protected function create1($Md5key, $list){
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $md5str = rtrim($md5str, "&");
        return $md5str . $Md5key;
    }

    private function _createSign($Md5key, $list){
        $md5keysignstr = $this->create1($Md5key, $list);
        $md5keysignstr = strtoupper(md5($md5keysignstr));
        return $md5keysignstr;

    }

    private function curlPost($url, $data = array())
    {
        $curl = curl_init();//初始化
        curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array( //改为用JSON格式来提交
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ));
        $result = curl_exec($curl);//执行命令
        curl_close($curl);//关闭URL请求
        return $result;
    }


}