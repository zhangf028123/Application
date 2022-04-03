<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XBPAYH5Controller extends PayController
{
    /**
     * PAY 就是下单的方法
     * @param $array
     * 看不懂这个I 是什么方法来的？ 还有request 是不在自定义前端的，这样每一个支付的？
     */
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XBPAYH5',
            'title'     => '上游Wap',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        //把元转成为分的计算单位
        $pay_fen=intval(fround(loatval($return["amount"]*100)));
        $data = [
            'client_id'  => $return['mch_id'], //
            'request_channel'=> I("request.request_channel"),
            'order_id'   => $return['orderid'],//生成系统订单号，我们系统的订单
            'pay_in_number'        => $pay_fen,//支付定额
            'pay_type' =>'wepay',//我们的返回是否有这个东西？支付方式，目前只可以接受wepay 微信支付
            'inform_ur' => $return['notifyurl'],//支付成功返回的回调地址，就是我们notify 的地方
            'request_time'=>$start_time,
            'version' => '1.0',//'目前固定是这个value 1.0'
        ];
        //进行数据和密钥进行签名处理
        $data['sign'] = $this->createSign($return["signkey"], $data);
        // 不用签名的参数
        $data['app_data']=I("request.app_data");//额外的订单信息
        $data['unique_id']    = I("request.unique_id");//对用户进行唯一定位的标识，有什么用的呢？
        $data['gps_city']     = I("request.gps_city");//某些支付通道特有参数，可选。
        $data['channel_property_1']    = I("request.channel_property_1");//channel_property_1	未知	举例demo	某些支付通道特殊的参数（可选）	否
        $data['channel_property_2']     = I("request.channel_property_2");//channel_property_1	未知	举例demo	某些支付通道特殊的参数（可选）	否
        $post_string = json_encode($data, 320);
        $response = $this->curl_post($return['gateway'], $post_string);
//        var_dump($response);
        $cost_time = $this->msectime() - $start_time;
        Log::record('XBPAYH5 pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        $response_data=$response['data'];
        if ($response['err_code'] === '0'){
            $location_url=$response['Data.qr_url'] ? $response['Data.qr_url']: $response['Data. h5_url'];
            header("location: {$location_url}");//这个header 是重定向？302
        }
        echo $response;
    }

    /**
     * 就是上游回调的方法，就是支付的异步回调方法
     */
    //异步通知
    public function notifyurl()
    {
//        $response  = $_REQUEST;
        $response = file_get_contents("php://input");
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XBPAYH5 pay \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XBPAYH5 notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        /**
         * 有两个参数要和支付渠道核实的就是，我们应该是用哪个，什么是实际的金额，难道说包括红包的东西，这样真的到我们手是哪个金额？
         * order_number	整数	13000	原始订单的金额	否
        pay_in_number	整数	12800	用户实际付款的金额	是
         */
        //通过订单id  拿到该订单对有的密钥吗？
        $publiKey = getKey($response["orderid"]); // 通过订单拿到数据里面的密钥 ,原来的primary_key
        $backKey=$response['sign'];//返回的验证签名字符串
        $data = [
            'order_id'    => $response["order_id"],
            'order_number'    => $response["order_number"],
            'pay_in_number'    =>$response["pay_in_number"],
            'pay_time'    => $response["pay_time"],
            'ticket_id'    => $response["ticket_id"],
            'pay_type'    => $response["pay_type"],
        ];
        //对比密钥是否一致
        $result = $this->_verify($data, $publiKey, $backKey);
        if ($result) {
            try{
                $Order      = M("Order");
                //通过我们的系统的订单号，找到对应的订单实体
                $o = $Order->where(['pay_orderid' => $_REQUEST["order_id"]])->find();
                if(!$o){
                    Log::record('上游wap回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderid"] );
                }

                //保存在订单里面的支付的金额，我们应该是和实际支付还是原来订单金额比较？实际支付是不是我们自己的收入，比如红包等等
                $pay_amount = $o['pay_amount'] ;
                //分转元
                $pay_in_number=number_format($response['pay_in_number'],2);//返回的支付金额，要转成为元才可以，保留两位小数
                $diff = $pay_in_number - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游wap回调失败,金额不等：{$pay_in_number} != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }

                //判断支付的流水，也就是支付渠道的流水是否重复了，？？其实我们就是不可以同一个订单出现同样的流水
                //不过如果不同的订单，外部流水是可以一样的？？
                $old_order = $Order->where(['upstream_order'=>$response['ticket_id']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["order_id"]){
                    Log::record("上游wap回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                //通过系统订单id 找到订单，同时记录流水id
                $Order->where(['pay_orderid' => $response["order_id"]])->save([ 'upstream_order'=>$response['ticket_id']]);
                $this->EditMoney($response['order_id'], '', 0);
                exit("OK");
            }catch (Exception $e){
                Log::record('上游wap回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('XBPAYH5 error:check sign Fail!','ERR',true);
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
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
     function createSign($Md5key, $list)
    {
        $temp=$this->createToSignStr($Md5key, $list);
//        $sign = strtoupper(md5($temp));
         $sign = md5($temp);
        Log::record('createToSignStr ：'.$temp.' $sign '.$sign,'ERR',true);
        return $sign;
    }
    function createToSignStr($Md5key, $list){
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=". $val . "&";
            }
        }
        return $md5str . $Md5key;
    }

     function _verify($requestarray, $md5key, $backKey){
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   =$backKey;
        //还是要判断是不是空的吧？
        return $md5keysignstr == $pay_md5sign;
    }

    function curl_post($api_url, $post_string){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $headers = array('content-type: application/json;charset=utf-8');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);

        $reponse = curl_exec($ch);
        curl_close($ch);

        return $reponse;
    }

}