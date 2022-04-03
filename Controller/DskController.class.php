<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

require_once("authorize.class.php");
class DskController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'Ding', // 通道名称
            'title' => '钉钉收款',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        $notifyurl = $this->_site . 'Pay_Ding_notifyurl.html';
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);
        $url = U('Dsk/skf',array('id'=>$return['orderid']),true,true);
        $payUrl = 'alipays://platformapi/Startapp?appId=20000067&backBehavior=pop&url='.urlencode('https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=2018111262130859&scope=auth_base&state=712273707553&redirect_uri='.urlencode($url));
        $encodeInfo = "https://ds.alipay.com/?from=mobilecodec&scheme=alipays%3a%2f%2fplatformapi%2fstartapp%3fsaId%3d10000007%26qrcode%3d".$url;
        $amount = sprintf('%.2f', $return['amount']);
        $this->assign('tempurl',$url);
        $this->senddd($amount,$return['orderid'],$return['mch_id'],$return['signkey'],$return['appid']);
        if($array['pid']==931){

            // 延时
            $channel = D('Channel')->where(['id'=>$array['api']])->find();
            $this->assign('pay_delay',$channel['pay_delay']);   // 延时

            if($contentType=="json"){
                $data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$encodeInfo,'order_id'=>$return['orderid']];
                echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
            }
            $this->display("WeiXin/alipaytao");
        }
        else{
            import("Vendor.phpqrcode.phpqrcode",'',".php");
            $QR = "Uploads/codepay/". $return['orderid'] . ".png";
            \QRcode::png($encodeInfo, $QR, "L", 20);
            $this->assign("imgurl", '/'.$QR);
            $this->assign('params',$return);
            $this->assign('orderid',$return['orderid']);
            $this->assign('money',sprintf('%.2f',$return['amount']));
            $this->assign('zfbpayUrl',$encodeInfo);
            $this->display("WeiXin/alipaytaoori");
        }

    }

	public function check(){
        $orderid = $_REQUEST['orderid'];
        $orderWhere['pay_orderid'] =$orderid;
        $orderInfo = M('Order')->where($orderWhere)->find();
    
      if(!empty($orderInfo['qrurl'])){
       echo json_encode(array('state' => 1, 'callback' =>$orderInfo['qrurl']));die;
      }
    }
  
    public function skf(){

        $orderId = $_GET['id'];
        $orderWhere['pay_orderid'] = $_GET['id'];
        $order = M('Order')->where($orderWhere)->find();
        $amount =  sprintf('%.2f', $order['pay_amount']);
        if(empty($order)){
            exit("订单失效");
        }
        if($order['pay_status']!=0){
            exit("订单已支付");
        }

        // 延时
        $channel = D('Channel')->where(['id'=>$order['channel_id']])->find();
        $this->assign('pay_delay',$channel['pay_delay']);   // 延时
       
        $this->assign('orderid',$_GET['id']);
        $this->assign('userid',$order['account']);
        $this->assign('account',$order['pay_channel_account']);
        $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
        // $this->display('WeiXin/wangxin');
        $this->display('ZhiFuBao/skf');
    }


    public function senddd($money,$orderid,$account,$receiver,$id){

        $client = stream_socket_client('tcp://120.78.200.245:39800');
        $json = json_encode(array(
            'cmd' => 'qrcode',  // 申请二维码
            'method' => "alipaycheck",
            'amount' =>$money,
            'mark'=>$orderid,
            'receiveId' => $receiver,
            'account'=>$account,// 主号
            'cid' => $id,
        ));
        fwrite($client, $json."\n");
    }
	
	// 哪里上报的二维码
    public function getnum(){
        $arrayData = $_REQUEST;
        $qrInfo = $arrayData['qrinfo'];

        file_put_contents('./Data/Dsk.getnum.txt', "【".date('Y-m-d H:i:s')."】\r\n".$qrInfo."\r\n\r\n",FILE_APPEND);

        $qrArray = json_decode($qrInfo,true);
        $orderid = $qrArray['remark'];
        $qrurl = $qrArray['qrcode']['payUrl'];
        if(!empty($orderid)&&!empty($qrurl)){
            $orderWhere['pay_orderid'] =$orderid;
            M('Order')->where($orderWhere)->save(['qrurl'=>$qrurl]);
            $this->showmessage("生成成功");
        }
        else{
            $this->showmessage("生成失败");
        }
    }

    /// 监控的心跳
    public function xintiao(){
        $arrayData = $_REQUEST;
        $uid = $arrayData['uid'];
        $channel_account = M("ChannelAccount")->where([['mch_id'=>$uid], ['signkey'=>$uid], '_logic'=>'or'])->find();
        if($channel_account){
            M("ChannelAccount")->where(['id'=>$channel_account['id']])->save(['last_monitor'=>time(), 'heartbeat_switch'=>1, ]);
            $this->showmessage("心跳ok");
        }else{
            $this->showmessage("账号不存在uid=".$uid);
        }
    }

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
        $this->assign('orderid',$id);
        $this->assign('userid',$order['account']);
        $this->assign('account',$order['pay_channel_account']);
        $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
        $this->display('WeiXin/zhudong');


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
    public function callbackurl()
    {
        exit('交易成功！');

    }

    //异步通知
    public function notifyurl()
    {
        $dataInfo = $_REQUEST;
		$key = "fdafdasfdasfagfdghdfhgdhgdhgdhgdfhf";
		if($dataInfo['key']!=$key){
		  $this->showmessage("keyfail");die;
		}
        file_put_contents('./Data/dsknotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($dataInfo)."\r\n\r\n",FILE_APPEND);
        $info = json_decode($dataInfo['data'],true);
        $title = $info[0]['consumeTitle'];
        $money = floatval($info[0]['consumeFee']);
        $payInfo = explode('-',$title);
        $orderId = $payInfo[0];
        if(empty($orderId)||empty($money)){
            $this->showmessage("a");die;
        }
        $orderWhere['pay_orderid'] = $orderId;
        $orderInfo = M('Order')->where($orderWhere)->find();
        $oder_amount = sprintf('%.2f', $orderInfo['pay_amount']);
        if($money!=$oder_amount){
            file_put_contents('./Data/mmmmhbnotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
            $this->showmessage("b");die;
        }
        M('Order')->where($orderWhere)->save(['upstream_order'=>$info[0]['bizInNo']]);  // 写入支付宝流水号
        $this->EditMoney($orderId, 'Ding', 0);
      $this->showmessage("回调成功");die;   // 回给主号监控

    }
	
    protected function showmessage($msg = '', $fields = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('result' => '200', 'msg' => $msg, 'data' => $fields);
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
