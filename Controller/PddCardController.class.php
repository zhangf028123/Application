<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class PddCardController extends PayController{

    public function Pay($channel){
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');
        $contentType = I("request.content_type");

        //$return = $this->getParameter('Uu898', $channel, 'CardController');

        $parameter = array(
            'code'         => 'PddCard', // 通道名称
            'title'        => '网关hx',
            'exchange'     => 1, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => $orderid,
            'body'         => $body,
            'channel'      => $channel,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $data = [

            'total'        => $return['amount'],   // 单位 元
            'api_order_sn'    => $return['orderid'],
            'notify_url'     => $return['notifyurl'],
            'client_id'         => $return['mch_id'],
            'member_id'         => $return['appsecret'],    // 上游商户的id
            'timestamp'        => getUnixTimestamp(),
            //'store_id'       => $return['appid'], // 不要店铺id，就随机取一个

        ];

        $Key = $return['signkey'];
        $data['sign'] = $this->sign($data, $Key);

        $response = HttpClient::post($return['gateway'], $data);
        $cost_time = $this->msectime() - $start_time;
        Log::record('pddcard pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if($response['status'] == 1){
            header("location: {$response['data']['h5_url']}");
        }else{
            echo $response;
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
        $clientip = $_SERVER['REMOTE_ADDR'];
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Pddcard notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $pay_orderid = $response["api_order_sn"];
        $publiKey = getKey($pay_orderid); // 密钥

        $result = $this->_verify($response, $publiKey);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $pay_orderid])->find();
                if(!$o){
                    Log::record('pddcard回调失败X,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$pay_orderid );
                }

                $pay_amount = $o['pay_amount'];
                $diff = $response['total'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("pddcard回调失败x,金额不等：{$response['total'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: fuck1!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['order_sn']])->find();
                if( $old_order && $old_order['pay_orderid'] != $pay_orderid){
                    Log::record("pddcard回调失败x,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $pay_orderid])->save([ 'upstream_order'=>$response['order_sn']]);
                $this->EditMoney($pay_orderid, '', 0);
                exit("ok");
            }catch (Exception $e){
                Log::record('pddcard回调失败,发生异常：'.$e->getMessage(),'ERR',true);
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
        $result = strtoupper(md5($str));
        if($recv_sign && $recv_sign != $result){
            Log::record("pddcard验签失败: \$recv_sign=$recv_sign \$result=$result \$str=$str, ",'ERR',true);
        }
        return $result;
    }

    private function _verify($response, $publiKey){
        $keys = ['callbacks', //'type',
                  'total',
                  'api_order_sn',
                  'order_sn', ];
        foreach ($keys as $key){
            if(!isset($response[$key]) || empty($response[$key])){
                Log::record('pddcard回调失败:字段为空'.$key.'response='.json_encode($response),'ERR',true);
                return false;
            }
        }
        $recv_sign = $response['sign'];
        if($this->sign($response, $publiKey, $recv_sign) == $recv_sign)
            return true;

        Log::record("pddcard notifyurl 验签失败:,response=".json_encode($response),'ERR',true);
        return false;
    }
}
