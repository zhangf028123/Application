<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class PddALZfbController extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'PddALZfb',
            'title'     => 'pddzfb(al)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            //'type' => 'wechat',
            'type'  => 'alipay',
            'total' => $return['amount'],
            'api_order_sn'   => $return['orderid'],
            'notify_url'     => $return['notifyurl'],
            'client_id'      => $return['mch_id'],
            'timestamp'=>time().'000',  //13位时间戳

        ];

        $data['sign']= $this -> sign($data, $return["signkey"]);//签名
        $res = $this -> sendRequest($return['gateway'], $data); // 下单
        $cost_time = $this->msectime() - $start_time;
        Log::record(' PddALZfb pay url='.$return['gateway'].',data='.json_encode($data).',response='.json_encode($res).',cost time='.$cost_time,'ERR',true);

        if ($res['ret'] == true) {
            $ress = json_decode($res['msg'],true);
            if( $ress['error_code'] == 200 ){
                $orderdata = $ress['data'];
                header('location:'.base64_decode($orderdata['qr_url']));
            } else {
                echo $ress['msg'];
            }
        } else {
            echo $res['msg'];
        }



        /*
        if( $res['ret'] != true ){
            var_dump($res);die;
        }
        $ress = json_decode($res['msg'],true);

        if( $ress['error_code'] == 200 ){//error_code=200表示下单成功，其他表示失败，msg表示失败原因。
            // 下单成功  自行处理
            $orderdata=$ress['data'];
            $sign=$orderdata['sign'];
            unset($orderdata['sign']);

            if($sign== $this -> sign($orderdata,$return["signkey"])){
                var_dump($orderdata);
                header('location:'.base64_decode($orderdata['qr_url']));
            }else{
                echo '校验失败！';
            }
        }else{
            // 下单失败
            var_dump($ress['msg']);
        } */

    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_POST;
        //$response  = $_REQUEST;
        //$response = file_get_contents("php://input");
        //$response = json_decode($response, true);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，PddALZfb notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["api_order_sn"]); // 密钥
        Log::record("PddALZfb notifyurl".json_encode($_REQUEST),'ERR',true);
        $data = [
            'callbacks'    => I("post.callbacks"),
            'type'    => I("post.type"),
            'total'    => I("post.total"),
            'api_order_sn'    => I("post.api_order_sn"),
            'order_sn'    => I("post.order_sn"),

        ];
        $sign = $this -> sign($data, $publiKey);//签名
        $data['sign'] = I("post.sign");

        if ($data['sign'] == $sign &&  $data['callbacks'] == 'CODE_SUCCESS') {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $response["api_order_sn"]])->find();
                if(!$o){
                    Log::record('PddALZfb回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$response["api_order_sn"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['total'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("PddALZfb回调失败,金额不等：{$response['total'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }

                $old_order = $Order->where(['upstream_order'=>$response['order_sn']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["order_sn"]){
                    Log::record("PddALZfb回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["api_order_sn"]])->save([ 'upstream_order'=>$response['order_sn']]);
                $this->EditMoney($response['api_order_sn'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('PddALZfb回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('PddALZfb error:check sign Fail!','ERR',true);
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



    /**
     * CURL发送Request请求,含POST和REQUEST
     * @param string $url 请求的链接
     * @param mixed $params 传递的参数
     * @param string $method 请求的方法
     * @param mixed $options CURL的参数
     * @return array
     */
    private function sendRequest($url, $params = [], $method = 'POST', $options = []) {
        $method = strtoupper($method);
        $protocol = substr($url, 0, 5);
        $query_string = is_array($params) ? http_build_query($params) : $params;

        $ch = curl_init();
        $defaults = [];
        if ('GET' == $method) {
            $geturl = $query_string ? $url . (stripos($url, "?") !== FALSE ? "&" : "?") . $query_string : $url;
            $defaults[CURLOPT_URL] = $geturl;
        } else {
            $defaults[CURLOPT_URL] = $url;
            if ($method == 'POST') {
                $defaults[CURLOPT_POST] = 1;
            } else {
                $defaults[CURLOPT_CUSTOMREQUEST] = $method;
            }
            $defaults[CURLOPT_POSTFIELDS] = $query_string;
        }

        $defaults[CURLOPT_HEADER] = FALSE;
        $defaults[CURLOPT_USERAGENT] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36";
        $defaults[CURLOPT_FOLLOWLOCATION] = TRUE;
        $defaults[CURLOPT_RETURNTRANSFER] = TRUE;
        $defaults[CURLOPT_CONNECTTIMEOUT] = 3;
        $defaults[CURLOPT_TIMEOUT] = 3;

        // disable 100-continue
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

        if ('https' == $protocol) {
            $defaults[CURLOPT_SSL_VERIFYPEER] = FALSE;
            $defaults[CURLOPT_SSL_VERIFYHOST] = FALSE;
        }

        curl_setopt_array($ch, (array)$options + $defaults);

        $ret = curl_exec($ch);
        $err = curl_error($ch);

        if (FALSE === $ret || !empty($err)) {
            $errno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            return [
                'ret' => FALSE,
                'errno' => $errno,
                'msg' => $err,
                'info' => $info,
            ];
        }
        curl_close($ch);
        return [
            'ret' => TRUE,
            'msg' => $ret,
        ];
    }



    //加密函数
    private function sign($params = [], $secret = '')
    {
        unset($params['sign']);
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            $str = $str . $k . $v;
        }
        $str = $secret . $str . $secret;
        return strtoupper(md5($str));
    }

}