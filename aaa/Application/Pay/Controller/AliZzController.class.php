<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

require_once("authorize.class.php");
class AliZzController extends PayController
{
    public function __construct()
    {
        parent::__construct();

    }

    //Pay_Gaoji_notifyurl.html
    //支付
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $contentType = I("request.content_type");
        $returnType=I("request.return_type",1);
        $parameter = array(
            'code' => 'AliZz', // 通道名称
            'title' => '支付宝转账(免签)',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter, 1);
        $urltemp = U('AliZz/skf',array('id'=>$return['orderid']),true,true);
		$sk = new \authorize();
        $url = $sk->geturl($urltemp);

        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$return['amount']));
        $tbh5="taobao://";
        $append="www.alipay.com/?appId=10000007&qrcode=".urlencode($url);
         
        //$encodeInfo = 'alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode='.urlencode($url); 
      // $encodeInfo =  'https://ds.alipay.com/?from=mobilecodec&scheme='.urlencode("alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=".$url);
		//$qrcode = "alipays://platformapi/startapp?appId=20000691&url="; 
        $encodeInfo1=$tbh5.$append.$qrcode; // 原版，需要按照淘宝app

        // start
        if($this->isInAlipayClient()){  // 支付宝app
            header("Location:".$urltemp);
        }elseif($this->isMobile()) { // 手机浏览器
            $encodeInfo2 = "https://ds.alipay.com/?from=mobilecodec&scheme=alipays%3A%2F%2Fplatformapi%2Fstartapp%3FsaId%3D10000007%26clientVersion%3D3.7.0.0718%26qrcode%3D".urlencode($urltemp);
            //$encodeInfo = "alipayqr://platformapi/startapp?saId=10000007&qrcode=" . $url;
            //header("Location:".$encodeInfo);
        }
        // end
        $this->assign('xingming', $return['channel_account']['xingming']);
        $this->assign('tbpayUrl',$encodeInfo1);     // 淘宝
        $this->assign('zfbpayUrl',$encodeInfo2);    // 支付宝
        $this->display("WeiXin/alipay2");
    }

    public function check(){

        $orderid = $_REQUEST['orderid'];
        $orderWhere['pay_orderid'] = $orderid;
        $orderInfo = M('Order')->where($orderWhere)->find();
        $amount =  sprintf('%.2f', $orderInfo['pay_amount']);
        if($_REQUEST['type']==1){
            $this->send($orderInfo['key'],$amount,$orderid,$orderInfo['pay_channel_account'],$orderInfo['pay_channel_account']);die;
        }
        if(!empty($orderInfo['memberid'])){
            header('Content-type: application/json');
            exit(json_encode(array("state" => 1, "callback" => $orderInfo['memberid'])));
        }
        else{
            if($_REQUEST['type']%4==0&&$_REQUEST['type']>=4){
		        $this->send($orderInfo['key'],$amount,$orderid,$orderInfo['pay_channel_account'],$orderInfo['pay_channel_account']);
            }
      	    echo "no";
        }
    }
  
    public function skf(){

        if (!$this->isAliClient()) {
            exit("订单号错误");
        }
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
        $url = U('Gao/skf',array('id'=>$orderId),true,true);
        $url = $url."?one=1";


        $one = $_GET['one']?$_GET['one']:0;
        if($one){
            $this->assign('orderid',$_GET['id']);
            $this->assign('url',$url);
            $this->assign('userid',$order['account']);
            $this->assign('account',$order['pay_channel_account']);
            $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
            $this->display('ZhiFuBao/alizz');die;
        }
        else{
            $this->assign('orderid',$_GET['id']);
            $this->assign('userid',$order['account']);
            $this->assign('account',$order['pay_channel_account']);
            $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
            
            $sk = new \authorize();
            $data = $sk->getToken();
            $this->assign('url', $url);
            //$this->assign('url', $sk->geturl($url));
            $uid = $data['alipay_system_oauth_token_response']['user_id'];  // 付款支付宝的appid
            file_put_contents('./Data/number.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);

            if(empty($uid)||is_null($uid)){
                exit("非法来源，请从手机支付宝内付款");
            }
            else{
                M('Order')->where($orderWhere)->save(['key'=>$uid]);
            }

            // 延时
            $channel = D('Channel')->where(['id'=>$order['channel_id']])->find();
            $this->assign('pay_delay',$channel['pay_delay']);   // 延时

            //$this->display('WeiXin/alizz');
             $this->display('ZhiFuBao/alizz');
            /*if($this->isInAlipayClient()){
                header("Location:".$sk->geturl($url));
            }else{
                $encodeInfo = "https://ds.alipay.com/?from=mobilecodec&scheme=alipays%3A%2F%2Fplatformapi%2Fstartapp%3FsaId%3D10000007%26clientVersion%3D3.7.0.0718%26qrcode%3D".urlencode($url);
                header("Location:".$sk->geturl($encodeInfo));
            }*/
        }


    }
    /**
     * 判断是否支付宝内置浏览器访问
     * @return bool
     */
    private function isAliClient() {
        $isAli = strpos($_SERVER['HTTP_USER_AGENT'], 'Alipay') !== false;
        //$isAli_1 = empty($_SERVER['HTTP_SPDY_H5_UUID']) !== true;
        $result = $isAli; // && $isAli_1;
        return $result;
    }
    public function sk(){
        $_GET['id'] = $_REQUEST['id'];
        $orderWhere['pay_orderid'] = $_GET['id'];
        $orderInfo = M('Order')->where($orderWhere)->find();
        if(empty($orderInfo)){
            exit("订单失效");
        }
        $sk = new \authorize();
        $data = $sk->getToken();
        file_put_contents('./Data/gaojia.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data).$_GET['id']."\r\n\r\n",FILE_APPEND);
		return $data;

    }

    public function send($uid,$money,$orderid,$key_id,$account){
        $client = stream_socket_client('tcp://60.169.10.64:39800');
        $json = json_encode(array(
            'cmd' => 'req',
            'paytype' => "alipaycheck",
            'type' =>"alipaycheck",
            'uid'=>$uid,
            'money' => $money,
            'mark' => $orderid,
            'key_id'=>$key_id,
            'account'=>$account,
        ));
        fwrite($client, $json."\n");
    }

    public function getnum(){
        $data = $_REQUEST;
        if(isset($data['mark'])&&!empty($data['payurl'])){
            $orderWhere['pay_orderid'] = $data['mark'];
            M('Order')->where($orderWhere)->save(['memberid'=>$data['payurl']]);
        }
    }

	public function xintiao(){
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
       // {"sUserId":"2088432628402085","userId":"2088302839252325","price":"1.10","outOrderNo":"20190331200040011100320025813946","agencyId":"190391622","time":"1554035708206","sign":"c2f4b345551bf96a6ca13f4dfc432b90"}
        $data = $_POST;
        file_put_contents('./Data/alizz.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);

        $where['pay_amount'] = $data['price'];  // 订单金额
        $where['account']=$data['sUserId']; // 收款支付宝的appid
        $where['key']=$data['userId'];  // 付款支付宝的appid
        // $where['pay_memberid']=$data['agencyId'];
        $where['pay_status']=0;
        $orderInfo = M('Order')->where($where)->order('id desc')->find();

        if(empty($orderInfo)){
            exit("ok");
        }
        // $m=intval($where['pay_memberid'])-10000;
        // $key=M('member')->where(['id'=>$m])->getField('apikey');
        $key='1561236';

        $signStr = $data['sUserId'].$data['userId'].$data['price'].$data['outOrderNo'].$data['agencyId'].$data['time'].$key;
        $sign = md5($signStr);
        // file_put_contents('./Data/alizz.txt', "【".date('Y-m-d H:i:s')."】\r\n".$sign."\r\n\r\n",FILE_APPEND);
        if($sign!=$data['sign']){
            exit("签名错误");
        }

        $this->EditMoney($orderInfo['pay_orderid'], 'AliZz', 0);
		$this->showmessage("处理成功");
       
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
