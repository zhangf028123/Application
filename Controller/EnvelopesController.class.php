<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;
class EnvelopesController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');   // 商品标题

        $parameter = array(
            'code' => 'Envelopes', // 通道名称
            'title' => '支付宝转账红包',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);

        $url = U('Envelopes/getPay',array('id'=>$return['orderid']),true,true);
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('zfbpayUrl',$url);
        $this->assign('money',sprintf('%.2f',$return['amount']));

        if($array['pid']==926){
            $encodeInfo = "alipayqr://platformapi/startapp?saId=10000007&qrcode=".$url;
            $location ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($encodeInfo);
            header("Location:".$location);
        }
        else{
            $this->display("WeiXin/alipayori");
        }
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
            $this->assign('userid',$order['account']);
            $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
       $this->assign('account',$order['pay_channel_account']);
            $this->display('WeiXin/envelopes');
     //   }
    }

    public function isInAlipayClient()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            return true;
        }
        return false;
    }

    //检测是否手机访问
    static public function isMobile(){
        $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';
        function CheckSubstrs($substrs,$text){
            foreach($substrs as $substr)
                if(false!==strpos($text,$substr)){
                    return true;
                }
            return false;
        }
        $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
        $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');

        $found_mobile=CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||
            CheckSubstrs($mobile_token_list,$useragent);

        if ($found_mobile){
            return true;
        }else{
            return false;
        }
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
        file_put_contents('./Data/hbnotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);

        $money = $data['money'];
        $orderId   = $data['mark'];
        $orderWhere['pay_orderid'] = $orderId;
        $orderInfo = M('Order')->where($orderWhere)->find();
        $signStr = $data['dt'].$data['mark'].$data['money'].$data['no']."alipayfghbvffghjjjhh".$data['userids'];
        $sign = md5($signStr);
        if($sign!=$data['sign']){
             exit("签名错误");
        }
        $oder_amount = sprintf('%.2f', $orderInfo['pay_amount']);
        if($money!=$oder_amount){
            file_put_contents('./Data/mmmmhbnotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("mo fail");
        }
        if(empty($orderInfo)){
            exit("a");
        }
        if($orderInfo['pay_status']!=0){
            echo "订单已支付";die;
        }

        $this->EditMoney($orderId, 'Envelopes', 0);
        $this->showmessage("处理成功",[]);
    }

    protected function showmessage($msg = '', $fields = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('status' => 'success', 'msg' => $msg, 'data' => $fields);
        echo json_encode($data, 320);
        exit;
    }

    function getIP() {
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv( "HTTP_X_FORWARDED_FOR");
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }
}
