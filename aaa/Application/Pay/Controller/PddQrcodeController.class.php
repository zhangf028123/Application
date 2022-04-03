<?php

namespace Pay\Controller;

use MoneyCheck;

require_once('redis_util.class.php');

/// 拼多多-交易二维码
class PddQrcodeController extends PayController
{
	public function Pay($array){
	    $orderid = I('request.pay_orderid');
        $body = I('request.pay_productname');

        $contentType = I("request.content_type");
        $parameter = array(
            'code'         => 'PddQrcode', // 通道名称
            'title'        => '拼多多-交易二维码',
            'exchange'     => 1, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => $orderid,
            'body'         => $body,
            'channel'      => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter, true);

        $url = 'https://mobile.yangkeduo.com/transac_qr_pay.html?mall_id='.$return['appid'];
        if($contentType == 'json'){
            $this->ajaxReturn(['result'=>'ok', 'url'=>$url, 'qrcode'=>$url, 'pay_orderid'=>$return['orderid'], 'out_trade_id'=>$return['out_trade_id'] ]);
        }
        $this->assign("imgurl", $url);   // 二维码的内容
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('zfbpayUrl',$url);
        $this->assign('money',sprintf('%.2f',$return['amount']));
        $this->display("Pdd/qrcode");
    }

    /// 心跳
    public function heartbeat(){
        $key = "1235678";
        $data = $_REQUEST;
        file_put_contents('./Data/pdd_xintiao.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        if($data['sign'] == md5($data['MallId'].$key)){
            $channel_account = M('ChannelAccount')->where(['appid'=>$data['MallId'], 'channel_id'=>251,])->find();
            if($channel_account){
                M('ChannelAccount')->where(['id'=>$channel_account['id'],  'channel_id'=>251, ])->limit(1)->save(['last_monitor'=>time(), 'heartbeat_switch'=>1, ]); // 更新心跳
                exit("heartbeat ok");
            }
            exit('channel_account not fouond.');
        }
        exit('xintiao error');
    }

    /// 回调
    //异步通知
    public function notifyurl()
    {
        $key = "1235678";
        $data = $_REQUEST;
        file_put_contents('./Data/PddQrcode.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        $signStr = $data['MallId'].$data['order_sn'].$data['order_amount'].$key;
        $sign = md5($signStr);
        if($sign != $data['sign']){
            file_put_contents('./Data/PddQrcode.txt', "【".date('Y-m-d H:i:s')."】\r\n签名错误".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("签名错误");
        }
        $channel_account = M('ChannelAccount')->where(['appid'=>$data["MallId"], 'channel_id'=>251,])->find();
        if(empty($channel_account)){
            file_put_contents('./Data/PddQrcode.txt', "【".date('Y-m-d H:i:s')."】\r\nno store,".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit('no store');
        }
        $order_amount = sprintf('%.2f',$data['order_amount'] / 100.0);
        $orderWhere = [
            'pay_status'=>0,
            'pay_amount'=>$order_amount,
            'account_id'=>$channel_account['id'],
            'pay_bankcode'=>943,
            'expire_time'=>['gt', time(),],
        ];
        $orderInfo = M('Order')->where($orderWhere)->find();
        if(empty($orderInfo)){
            file_put_contents('./Data/PddQrcode.txt', "【".date('Y-m-d H:i:s')."】\r\nno order $order_amount".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("no order");
        }
        if($orderInfo['pay_status']!=0){
            file_put_contents('./Data/PddQrcode.txt', "【".date('Y-m-d H:i:s')."】\r\nnotified".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit('notified!');
        }
        $moneyCheck = new MoneyCheck();
        $money = sprintf('%.2f', $data['order_amount'] / 100.0);
        $pay_orderid = $moneyCheck->getAccountKey($channel_account['id'], $money);
        if($pay_orderid != $orderInfo['pay_orderid']){
            file_put_contents('./Data/PddQrcode.txt', "【".date('Y-m-d H:i:s')."】\r\norder not unique ".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("order not unique {$pay_orderid} ".$orderInfo['pay_orderid']. ',channel_account='.$channel_account['id'].'order.id='.$orderInfo['id']);
        }
        $oder_amount = sprintf('%.2f', $orderInfo['pay_amount']);

        if($money != $oder_amount){
            file_put_contents('./Data/PddQrcode.txt', "【".date('Y-m-d H:i:s')."】\r\nnot equ".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("$money not equ");
        }
        if( !empty($orderInfo['upstream_order']) && $orderInfo['upstream_order'] != $data['order_sn']){
            file_put_contents('./Data/PddQrcode.txt', "【".date('Y-m-d H:i:s')."】\r\n upstream_order not empty".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("upstream_order not empty");
        }

        M('Order')->where($orderWhere)->save(['upstream_order'=>$data['order_sn']]);
        if($this->EditMoney($pay_orderid, 'PddQrcode', 0)){
            $moneyCheck->deletAccountKey($channel_account['id'], $money);
            exit('ok');
        }
        exit('failed final.');
        //$this->showmessage("处理成功");
    }
}
