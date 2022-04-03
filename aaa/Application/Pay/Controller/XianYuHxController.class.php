<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class XianYuHxController extends PayController{

    public function Pay($array){
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');
        $contentType = I("request.content_type");
        $type = 'alipay';
        //$return = $this->getParameter('Xianyu', $channel, 'XianYuHxController');

        $parameter = [
            'code'      => 'XianYuHx',
            'title'     => '上游Sdk',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);



        $data = [
            'money'      => $return['amount'],   // 单位 元
            'part_sn'    => $return['orderid'],
            'notify'     => $return['notifyurl'],
            'id'         => $return['mch_id'],
            //'sign'  => '',
        ];
        $Key = $return['signkey'];
        $data['sign'] = $this->sign($data, $Key);

        $response = HttpClient::post($return['gateway'], $data);
        // $response = $this->post($return['gateway'], $data); // 因为用现有的post发给asp.net的服务器会报异常，所以单独写了个
        $cost_time = $this->msectime() - $start_time;
        Log::record('咸鱼 pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);

        if($response['code'] == 1){
            $return = [
                'result' => 'ok',
                //'url' => $response['pay_url'],
                'orderStr' => $response['data']['sdk_url1']
            ];
            $this->ajaxReturn($return);
        }else{
            $return = [
                'result' => 'false',
                //'url' => $response['pay_url'],
                'msg' => $response['msg']
            ];
            $this->ajaxReturn($return);
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

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        if($response['callbacks'] != 'CODE_SUCCESS')die($response['callbacks']);

        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Pin duo duo notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $order_sn = $response["order_sn"];  // 上游单号
        $pay_orderid = $response["part_sn"];    // 平台单号
        $publiKey = getKey($pay_orderid); // 密钥

        $result = $this->_verify($response, $publiKey);

        if ($result) {
            //if ($response['status'] == 'ok' ) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $pay_orderid])->find();
                if(!$o){
                    Log::record('xianyu 回调失败X,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$pay_orderid );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['total'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("xianyu 回调失败x,金额不等：{$response['total'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: fuck1!');
                }
                $old_order = $Order->where(['upstream_order'=>$order_sn])->find();
                if( $old_order && $old_order['pay_orderid'] != $pay_orderid){
                    Log::record("xianyu 回调失败x,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    die("not ok2");
                }
                $Order->where(['pay_orderid' => $pay_orderid])->save([ 'upstream_order'=>$order_sn]);
                $this->EditMoney($pay_orderid, '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('xianyu 回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
            //}
        } else {
            exit('error:check sign Fail!');
        }
    }

    /**
     * 签名
     * @param array $params
     * @param string $secret
     * @return string
     */
    protected function sign($params = [], $secret = '', $recv_sign = '')
    {
        unset($params['sign']);
        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            $str = $str . $k . $v;
        }
        $str = $secret . $str . $secret;
        $result = (md5($str));
        if($recv_sign && $recv_sign != $result){
            Log::record("咸鱼验签失败: \$recv_sign=$recv_sign \$result=$result \$str=$str, ",'ERR',true);
        }
        return $result;
    }

    private function _verify($response, $publiKey){
        $keys = ['callbacks',
            //'type',
            'total',
            'part_sn',
            'order_sn',
            ];
        foreach ($keys as $key){
            if(!isset($response[$key]) || empty($response[$key])){
                Log::record('闲鱼 回调失败:字段为空'.$key.'response='.json_encode($response),'ERR',true);
                return false;
            }
        }
        $recv_sign = $response['sign'];
        if($this->sign($response, $publiKey, $recv_sign) == $recv_sign)
            return true;

        Log::record("闲鱼 notifyurl 验签失败:,response=".json_encode($response),'ERR',true);
        return false;
    }
}
