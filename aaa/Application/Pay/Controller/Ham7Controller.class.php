<?php


namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class Ham7Controller extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'Ham7',
            'title'     => '拼多多支付宝-新',
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
            'pay_memberid'  => $return['mch_id'],
            'pay_orderid'   => $return['orderid'],
            'pay_applydate' => I("request.pay_applydate"),
            'pay_bankcode' => $return['appid'], // 921,
            'pay_notifyurl'     => $return['notifyurl'],
            'pay_callbackurl'   => $return['callbackurl'],
            'pay_amount'        => $return["amount"],
        ];
        $data['pay_md5sign'] = $this->createSign($return["signkey"], $data);
        // 不用签名的参数
        $data['pay_productname']    = I("request.pay_productname");
        $data['pay_productnum']     = I("request.pay_productnum");
        $data['pay_productdesc']    = I("request.pay_productdesc");
        $data['pay_producturl']     = I("request.pay_productnum");
        $data['pay_attach']         = I("request.pay_attach");

        $response = HttpClient::post($return['gateway'], $data);    // 还是curl靠谱
        Log::record('Ham7 pay url='.$return['gateway'].',data='.json_encode($data).',response='.$response,'ERR',true);
        $response = json_decode($response, true);
        $response['qrcode'] = $response['url'] = $response['qr_url'];
        $contentType = I("request.content_type");
        // $this->setHtml($return['gateway'], $data);

        if ($response['returncode'] == '00'){
            $cache      =   Cache::getInstance('redis');

            // 支付宝H5
            $url = $response['qr_url'];
            /*if ($return['appid'] == 921) {
                $response['qrcode'] = $response['url'] = "{$this->_site}Pay_Ham7_showh5.html?id={$return['orderid']}";   // 中转页面
                $url = parse_url($response['qr_url']);
                $param_arr = $this->convertUrlQuery($url['query']);
                $url = urldecode($param_arr['or']);
            }else */
                {
                $response['qrcode'] = $response['url'] = $url;
            }
            $content = ['amount'=> $return['amount'], 'url' => $url, 'qrcode'=>$response['qrcode']];
            $cache->set($return['orderid'], $content, 12*3600);
        }

        if ($response['returncode'] != '00' || $contentType == 'json'){
            if($response['result'] != '00'){    // 记录下单失败的记录
                if(!isset($response['result']))$response['result'] = 'err';
                file_put_contents("Data/pddx_failed.txt",json_encode($response).",gateway=".$return['gateway'].",storeid=  ".$data['storeid']."\n", FILE_APPEND);
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
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];

        Log::record(" ham7 \$response=".json_encode($response),'ERR',true);

        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，Ham7 notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        $publiKey = getKey($response["orderid"]); // 密钥

        $result = $this->_verify($response, $publiKey);

        if ($result) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->find();
                if(!$o){
                    Log::record('新拼多多回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["orderid"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['amount'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("新拼多多回调失败,金额不等：{$response['amount'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: fuck1!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['transaction_id']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderid"]){
                    Log::record("新拼多多回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["orderid"]])->save([ 'upstream_order'=>$response['transaction_id']]);
                $this->EditMoney($response['orderid'], '', 0);
                exit("OK");
            }catch (Exception $e){
                Log::record('新拼多多回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('ham7 error:check sign Fail!','ERR',true);
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
