<?php
/**
 * Created by PhpStorm.
 * User: luo fei
 * Date: 2019/3/2
 * Time: 13:14
 */

namespace Pay\Controller;

/// 支付宝收款
class ZfbskController extends PayController
{
    //支付
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');   // 商品标题

        $parameter = array(
            'code' => 'Zfbsk', // 通道名称
            'title' => '支付宝收款',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);

        $url = U('Zfbsk/getPay',array('id'=>$return['orderid']),true,true);
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('zfbpayUrl',$url);
        $this->assign('money',sprintf('%.2f',$return['amount']));

        if($array['pid']!=928){
            die('pid error!');
        }
        $this->display("WeiXin/alipayori");
    }

    /// 扫描二维码打开的页面
    public function getPay(){
        $id = $_REQUEST['id'];
        if(empty($id)){
            exit("订单号错误");
        }

        $where['pay_orderid'] = $id;
        $order = M('Order')->where($where)->find();
        if(!$order){
            exit("订单不存在");
        }
        if($order['pay_status']>0){
            exit ('已支付');exit;
        }
        //  if ($this->isMobile()) {
        $this->assign('orderid',$id);
        $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
        $this->assign('account',$order['pay_channel_account']); // 支付宝账号
        $this->assign('userid',$order['account']); // 支付宝pid
        //die($id);
        $this->display('WeiXin/zfbsk');
        //   }
    }

    //同步通知
    public function callbackurl($orderid, $pay_memberid, $bankcode)
    {
        $Order = M("Order");
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        if($pay_status > 0){
            $this->EditMoney($orderid, 'Aliwap', 1);
            exit('交易成功！');
        }else{
            exit("error");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $data = $_REQUEST;
        file_put_contents('./Data/zfbsknotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        if($data['type'] != 'alipay')die('not alipay sk.');

        $money = $data['money'];
        $orderId   = $data['mark'];
        $orderWhere['pay_orderid'] = $orderId;
        $orderInfo = M('Order')->where($orderWhere)->find();

        $signStr = $data['dt'].$data['mark'].$data['money'].$data['no']."alipay".$data['signkey'].$data['userids'];
        $sign = md5($signStr);
        if($sign!=$data['sign']){
            exit("签名错误");
        }
        $oder_amount = sprintf('%.2f', $orderInfo['pay_amount']);
        if($money!=$oder_amount){
            file_put_contents('./Data/mmmmzsbsknotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("mo fail");
        }
        if(empty($orderInfo)){
            exit("a");
        }
        if($orderInfo['pay_status']!=0){
            echo "订单已支付";die;
        }

        $this->EditMoney($orderId, 'Envelopes', 0);
        $this->showmessage("处理成功");
    }
}
