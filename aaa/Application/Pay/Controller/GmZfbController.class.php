<?php


namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

/// 国美支付宝
class GmZfbController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'GmZfb',
            'title'     => '国美支付宝',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $return['subject'] = $body;

        $data = [
            'mch_id'  => $return['mch_id'],
            'order_type'    => 15,  // 国美支付宝
            'out_trade_no'   => $return['orderid'],
            'total_fee'        => $return["amount"],
            'body'          => 'fuckyoueverybody',
            //'pay_applydate' => I("request.pay_applydate"),
            //'pay_bankcode' => $return['appid'], // 921,
            'notify_url'     => $return['notifyurl'],
            'return_url'   => $return['callbackurl'],
            'authCode'  => '123456',  //
        ];
        $sign_str = $data['mch_id'].'|'.$data['order_type'].'|'.$data['out_trade_no'].'|'.$data['total_fee'].'|'.$return["signkey"];
        $data['sign'] = strtolower(md5($sign_str));
        $response = HttpClient::post($return['gateway'], $data);    // 还是curl靠谱
        Log::record('GmZfb pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        $response['qrcode'] = $response['url'] = $response['pay_url'];
        $contentType = I("request.content_type");

        if ($response['code'] == 0 && $response['return_code'] == 'Success'){
            $cache      =   Cache::getInstance('redis');

            // 支付宝H5
            $url = $response['pay_url'];
            if ($return['appid'] == 946) {
                $response['qrcode'] = $response['url'] = "{$this->_site}Pay_GmZfb_showh5.html?id={$return['orderid']}";   // 中转页面
                /*$url = parse_url($response['qr_url']);
                $param_arr = $this->convertUrlQuery($url['query']);
                $url = urldecode($param_arr['or']);*/
            }else
            {
                $response['qrcode'] = $response['url'] = $url;
            }
            $content = ['amount'=> $return['amount'], 'url' => $url, 'qrcode'=>$response['qrcode']];
            $cache->set($return['orderid'], $content, 12*3600);
        }

        if ($response['code'] != 0 || $contentType == 'json'){
            if($response['code'] != 0){    // 记录下单失败的记录
                if(!isset($response['result']))$response['result'] = 'err';
                file_put_contents("Data/GmZfb_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
            }else {
                $response['result'] = 'ok';
            }
            $this->ajaxReturn($response);
        }
        if (parent::isMobile()) {
            header("location: {$response['url']}");
        }
        $this->assign("imgurl", $response['qrcode'] );
        //$this->assign("data", $data);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('zfbpayUrl',$response['url']);
        $this->assign('money',sprintf('%.2f',$return['amount']));

        $this->assign('isInWeixin',false);

        $this->display("ZhiFuBao/alipayori");
    }

    //异步通知
    public function notifyurl()
    {
        $param_keys = ['mch_id', 'order_type', 'out_trade_no', 'orderid', 'total_fee', 'sign'];
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" GmZfb \$response=".json_encode($response),'ERR',true);

        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Ham7 notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        foreach ($param_keys as $key){
            if(!isset($response[$key])){
                die("缺少参数 $key");
            }
        }

        $mch_id=$_REQUEST["mch_id"];  //商户id
        $p_order_type=$_REQUEST["order_type"];  //支付渠道
        $p_out_trade_no=$_REQUEST["out_trade_no"];  //商户订单号
        $p_orderid=$_REQUEST["orderid"];  //平台订单号
        $p_total_fee=$_REQUEST["total_fee"];  //订单成功金额
        $p_sign=$_REQUEST["sign"];
        $publiKey = getKey($p_out_trade_no); // 密钥

        $md5str= $mch_id.'|'.$p_order_type.'|'.$p_out_trade_no.'|'.$p_total_fee.'|'.$publiKey;
        $sign=strtolower(md5($md5str));

        $result = ($sign==$p_sign);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $p_out_trade_no])->find();
                if(!$o){
                    Log::record('GmZfb回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$p_out_trade_no );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $p_total_fee - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("GmZfb回调失败,金额不等：{$p_total_fee} != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: fuck1!');
                }
                $old_order = $Order->where(['upstream_order'=>$p_orderid])->find();
                if( $old_order && $old_order['pay_orderid'] != $p_out_trade_no){
                    Log::record("GmZfb回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $p_out_trade_no])->save([ 'upstream_order'=>$p_orderid]);
                $this->EditMoney($p_out_trade_no, '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('GmZfb回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record("GmZfb error:check sign Fail!$md5str $sign",'ERR',true);
            exit('error:check sign Fail!');
        }
    }

    private function _verify($requestarray, $md5key){
        unset($requestarray['attach']);
        unset($requestarray['sign']);
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.sign');
        return $md5keysignstr == $pay_md5sign;
    }

    /// 支付宝虚拟中转页
    public function showh5($id){
        $cache      =   Cache::getInstance('redis');
        $data = $cache->get($id);
        $this->assign('orderid',$id);
        $this->assign('zfbpayUrl',$data['url']);
        $this->assign($data);
        return $this->display("ZhiFuBao/alipay_ham7");
    }
}
