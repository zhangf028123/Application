<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class TAIXydfh5Controller extends PayController
{
    public function Pay($array){
        $start_time = $this->msectime();
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'TAIXydfh5',
            'title'     => 'Ki咸鱼',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);
        $data = [
            'version' => '3.0',
            'method' => 'Gt.online.interface',
            'partner' => $return['mch_id'],
            'banktype' =>$return['appid'],
            //'paymoney' =>$return["amount"],
//            'paymoney' => strval(sprintf('%.2f', $return["amount"])),
            'paymoney' => strval(sprintf('%.2f', $return["amount"])),
            'ordernumber' => $return['orderid'],
            'callbackurl' => $return['notifyurl'],
            'notreturnpage' => 'true',
            'hrefbackurl' => $return['callbackurl'],
            'attach' => 'trade',




        ];
        $data['sign'] = $this->_createSign($return["signkey"], $data);
        //Log::record('1111111111111:'.http_build_query($data), 'ERR', 'true');
        //$response = HttpClient::get($return['gateway'], $data);    // 还是curl靠谱
        $response = $this -> curl_request($return['gateway'], $data);
        //$response = HttpClient::post($return['gateway'], $data);    //
        $cost_time = $this->msectime() - $start_time;
        Log::record('TAIXydfh5 pay url='.$return['gateway'].'data='.json_encode($data).'response='.$response."cost time={$cost_time}ms",'ERR',true);
        $response = json_decode($response, true);
        if ($response['code'] == '0'){

            $return = [
                'result' => 'ok',
                'orderStr' => $response['data']['qrcodeUrl'],
            ];
            header('Location:'.$return['orderStr']);

            exit;
//            $this->ajaxReturn($return);
        }

    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" TAIXydfh5 \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，TAIXydfh5 notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }
        $publiKey = getKey($response["ordernumber"]); // 密钥

        $data = [
            'partner'    => I("request.partner"),
            'ordernumber'    => I("request.ordernumber"),
            'orderstatus'    => I("request.orderstatus"),
            'paymoney'    => I("request.paymoney"),
            'sysnumber'    => I("request.sysnumber"),

        ];
        $result = $this->_verify($response, $publiKey);

        if ($result && $response['orderstatus'] == 1) {
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["ordernumber"]])->find();
                if(!$o){
                    Log::record('上游sdk回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["ordernumber"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['paymoney'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("上游sdk回调失败,金额不等：{$response['paymoney'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['sysnumber']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["ordernumber"]){
                    Log::record("上游sdk回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["ordernumber"]])->save([ 'upstream_order'=>$response['sysnumber']]);
                $this->EditMoney($response['ordernumber'], '', 0);
                exit("ok");
            }catch (Exception $e){
                Log::record('上游sdk回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        } else {
            Log::record('TAIXydfh5 error:check sign Fail!','ERR',true);
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

    private function _createSign($key, $list){
        $signstr =
            'version='.$list['version'].'&method='.$list['method'].
            '&partner='.$list['partner'].'&banktype='.$list['banktype'].
            '&paymoney='.$list['paymoney'].'&ordernumber='.$list['ordernumber'].
            '&callbackurl='.$list['callbackurl'].$key;

        $sign = strtolower(md5($signstr));
        return $sign;
    }


    private function _verify($requestarray, $md5key){
        $signstr = 'partner='.$requestarray['partner'].'&ordernumber='.$requestarray['ordernumber'].'&orderstatus='.$requestarray['orderstatus'].'&paymoney='.$requestarray['paymoney'].$md5key;
        Log::record('11111111111:'.$signstr, 'ERR', 'true');
        $sign = strtolower(md5($signstr));
        $pay_md5sign   = I('request.sign');
        return $sign == $pay_md5sign;
    }
    protected function curl_request($url,$post=[]){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        return $data;
    }

}
