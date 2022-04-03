<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class PddXController extends PayController{

    public function Pay($array){
        $start_time = $this->msectime();
        $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code'         => 'PddX', // 通道名称
            'title'        => '拼多多',
            'exchange'     => 1, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => $orderid,
            'body'         => $body,
            'channel'      => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        $type = 'zfb';
        $pid = $array['pid'];
        $is_alipay = in_array($pid, [938,948,950]);
        if($is_alipay )$type = 'alipay';  // 拼多多-支付宝
        elseif($pid == 941 )$type = 'wechat';   // 拼多多-微信

        $mode = '0';
        if($pid == 938 || $pid == 941)$mode = '0';  // 实物

        $data = [
            'type'          => $type,   // alipay, wechat
            'total'        => $return['amount'],   // 单位 元
            'api_order_sn'    => $return['orderid'],
            'notify_url'     => $return['notifyurl'],
            'client_id'         => $return['mch_id'],
            'timestamp'        => getUnixTimestamp(),
            'store_id'       => $return['appid'],
            //'sign'  => '',
        ];
        // if($return['appid'] == 'xianggang')$data['storeid'] = '';
        $Key = $return['signkey'];
        $data['sign'] = $this->sign($data, $Key);
        //$data = json_encode($data);
        //$this->setHtml($return['gateway'], $data);  // 由浏览器发起post

        $response = HttpClient::post($return['gateway'], $data);
        // $response = $this->post($return['gateway'], $data); // 因为用现有的post发给asp.net的服务器会报异常，所以单独写了个
        $cost_time = $this->msectime() - $start_time;
        Log::record('PinDDX pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);

        $response['pay_orderid'] = $return['orderid'];
        if($response['status'] == 1){
            $response['result'] = 'ok';
            $response['url'] = $response['data']['h5_url'];
            $response['qrcode'] = $response['data']['qr_url'];
        }
        if($response['result'] == 'ok'){
            $cache      =   Cache::getInstance('redis');
            $content = ['amount'=> $return['amount'], 'url' => $response['url'], 'qrcode'=>$response['qrcode'], 'pay_applydate'=> I("request.pay_applydate"), ];
            $cache->set($return['orderid'], $content, 12*3600);
        }
        if($response['result'] == 'ok' && $is_alipay){    // 成功才有url
            /*$cache      =   Cache::getInstance('redis');
            $content = file_get_contents($response['url']);
            $info = parse_url($response['url']);
            list($t, $tv) = explode('=', $info['query']);
            $cache->setex($tv, 600, $content);*/
            // 支付宝H5
            /*if($response['num'] == 6) {
                $response['url'] = "{$this->_site}Pay_Pdd_showh5.html?id={$return['orderid']}";   // 中转页面
            }else if($response['num'] == 9) */
            {
                $response['url'] = "{$this->_site}Pay_PddX_showzfb.html?id={$return['orderid']}";   // 中转页面
                /*$response['url'] = "alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=".urlencode( $response['url']);
                $response['url'] ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($response['url']);*/
            }
        }
        if($response['result'] == 'ok' && ($array['pid'] == 941 )) {
            $response['url'] = "{$this->_site}Pay_Pdd_showwx.html?id={$return['orderid']}";
        }
            // {"result": "ok", "msg": 错误消息, "pay_orderid": 大通订单号, "url": 扫码地址,  "qrcode": h5地址, }
        if ($response['result'] != 'ok'// || $contentType == 'json'
        ){
            if($response['result'] != 'ok'){    // 记录下单失败的记录
                if(!isset($response['result']))$response['result'] = 'err';
                file_put_contents("Data/pdd_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
            }
            $this->ajaxReturn($response);
        }
        $this->assign("imgurl", $response['qrcode'] );
        //$this->assign("data", $data);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('zfbpayUrl',$response['url']);
        $this->assign('money',sprintf('%.2f',$return['amount']));

        $this->assign('isInWeixin',false);

        if($array['pid'] == 941  ){ // 微信
            if($this->isInWeixinClient() ){
                $this->assign('isInWeixin',true);
                //$this->ajaxReturn(['result'=>'err', 'msg'=>'请用手机浏览器打开！']);
                header("location: {$response['qrcode']}");  // 跳到代付
            }
            /*$str = $response['payHtml'];
            $str = str_replace(array("\r", "\t", "\n"), "", $str);
            //$str = nl2br($str);
            Log::record('PinDD weixin page='.$str,'ERR',true);
            die($str);*/
            //$url = $array['pid'] == 941 ? $response['qrcode'] : "{$this->_site}/Pay_Pdd_showwx.html?id={$return['orderid']}";
            header("location: {$response['url']}");
            /*/$url = $response['qrcode'];
            $this->assign("imgurl", $url); // 微信的二维码和h5都用微信协议 weixin://
            return $this->display("Pdd/weixin");    */
        }
        if (parent::isMobile()){ // 手机直接跳转
            /*if($array['pid'] == 938 ){   // 淘宝虚拟
                $response['url'] = "alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=".urlencode( "{$this->_site}Pay_Pdd_show.html?id={$return['orderid']}" );
                $response['url'] ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($response['url']);
            } */
            header("location: {$response['url']}");
        }

        $this->display("ZhiFuBao/alipayori");
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
            Log::record("伪造的ip，Pin duo duo notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $pay_orderid = $response["api_order_sn"];
        $publiKey = getKey($pay_orderid); // 密钥

        $result = $this->_verify($response, $publiKey);

        if ($result) {
            //if ($response['status'] == 'ok' ) {
                try{
                    $Order      = M("Order");
                    $o = $Order->where(['pay_orderid' => $pay_orderid])->find();
                    if(!$o){
                        Log::record('拼多多回调失败X,找不到订单：'.json_encode($response),'ERR',true);
                        exit('error:order not fount'.$pay_orderid );
                    }

                    $pay_amount = $o['pay_amount'];
                    $diff = $response['total'] - $pay_amount;
                    if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                        Log::record("拼多多回调失败x,金额不等：{$response['total'] } != {$pay_amount},".json_encode($response),'ERR',true);
                        exit('error: fuck1!');
                    }
                    $old_order = $Order->where(['upstream_order'=>$response['order_sn']])->find();
                    if( $old_order && $old_order['pay_orderid'] != $pay_orderid){
                        Log::record("拼多多回调失败x,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                        //die("not ok2");
                    }
                    $Order->where(['pay_orderid' => $pay_orderid])->save([ 'upstream_order'=>$response['order_sn']]);
                    $this->EditMoney($pay_orderid, '', 0);
                    exit("ok");
                }catch (Exception $e){
                    Log::record('拼多多回调失败,发生异常：'.$e->getMessage(),'ERR',true);
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
    private function sign($params = [], $secret = '', $recv_sign = '')
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
            Log::record("拼多多X验签失败: \$recv_sign=$recv_sign \$result=$result \$str=$str, ",'ERR',true);
        }
        return $result;
    }

    private function _verify($response, $publiKey){
        $keys = ['callbacks', 'type', 'total', 'api_order_sn', 'order_sn', ];
        foreach ($keys as $key){
            if(!isset($response[$key]) || empty($response[$key])){
                Log::record('拼多多X回调失败:字段为空'.$key.'response='.json_encode($response),'ERR',true);
                return false;
            }
        }
        $recv_sign = $response['sign'];
        if($this->sign($response, $publiKey, $recv_sign) == $recv_sign)
            return true;

        Log::record("Pin duo duoX notifyurl 验签失败:,response=".json_encode($response),'ERR',true);
        return false;
    }

    public function testwx(){
        return $this->display("Pdd/testwx");
    }

    /// 支付宝虚拟中转页
    public function showh5($id){
        $cache      =   Cache::getInstance('redis');
        $data = $cache->get($id);
        $this->assign('zfbpayUrl',$data['url']);
        $this->assign($data);
        return $this->display("ZhiFuBao/alipay_h5");
    }

    /// 支付宝虚拟中转页
    public function showzfb($id){
        $cache      =   Cache::getInstance('redis');
        $data = $cache->get($id);
        $this->assign($data);
        return $this->display("ZhiFuBao/show");
    }

    /// 微信中转页
    public function showwx($id){
        $cache      =   Cache::getInstance('redis');
        $data = $cache->get($id);
        $this->assign('params', ['out_trade_id'=>$id, 'datetime'=>$data['pay_applydate']]);
        $this->assign($data);
        $this->assign("imgurl", $data['qrcode'] );
        $this->assign('zfbpayUrl',$data['url']);
        $this->assign('orderid',$id);
        $this->assign('money',sprintf('%.2f',$data['amount']));
        return $this->display("Pdd/weixin");
        /*$url = $this->isInWeixinClient() ? $data['qrcode'] : $data['url'];
        header("location:  $url ");*/
    }
}
