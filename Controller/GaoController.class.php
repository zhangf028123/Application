<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

require_once("authorize.class.php");

// 支付宝主动收款
class GaoController extends PayController
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
        $parameter = array(
            'code' => 'Gaoji', // 通道名称
            'title' => '主动收款',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);
        $url = U('Gao/skf',array('id'=>$return['orderid']),true,true);
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$return['amount']));
        $encodeInfo = "https://ds.alipay.com/?from=mobilecodec&scheme=alipays%3A%2F%2Fplatformapi%2Fstartapp%3FsaId%3D10000007%26clientVersion%3D3.7.0.0718%26qrcode%3D".$url;
        if($array['pid']==904){ // 支付宝H5
            $encodeInfo = "alipayqr://platformapi/startapp?saId=10000007&qrcode=".$url;
            $location ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($encodeInfo);
            if($contentType=="json"){
                $data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$location,'order_id'=>$return['orderid']];
                echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
            }
            header("Location:".$encodeInfo);
        }
        else{
            if($contentType=="json"){
                $data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$url,'order_id'=>$return['orderid']];
                echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
            }
            $this->assign('zfbpayUrl',$encodeInfo);

            $this->display("WeiXin/alipayori");
        }

    }
    // 付款支付宝浏览器调用
	public function check(){
        $orderid = $_REQUEST['orderid'];
        $orderWhere['pay_orderid'] =$orderid;
        $orderInfo = M('Order')->where($orderWhere)->find();
    
        if(!empty($orderInfo['upstream_order'])){ // getnum 由监控端post写入这个upstream_order 为 支付宝流水号
            echo json_encode(array('state' => 1, 'callback' =>$orderInfo['upstream_order']));die;
        }
	    else{
            if($_REQUEST['type']%4==0){ // type表示请求了多少次
		  	    $amount =  sprintf('%.2f', $orderInfo['pay_amount']);
                $this->send($orderInfo['key'],$amount,$orderid,$orderInfo['pay_channel_account'],$orderInfo['pay_channel_account']);
            }
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
        $url = U('Gao/skf',array('id'=>$orderId),true,true);
        $url = $url."?one=1";


        $one = $_GET['one']?$_GET['one']:0;
        if($one){   // 第二次进来
             $this->send($order['key'],$amount,$orderId,$order['pay_channel_account'],$order['pay_channel_account']);
            $this->assign('transferid',$transferId);
            $this->assign('orderid',$_GET['id']);
            $this->assign('url',$url);
            $this->assign('userid',$order['account']);  // 收款支付宝的数字id
            $this->assign('account',$order['pay_channel_account']); // 收款支付宝的账号
            $this->assign('amount',sprintf('%.2f', $order['pay_amount']));  // 多少钱
            $this->display('WeiXin/bill4');die;
        }
        else{   // 第一次进来
            $this->assign('orderid',$_GET['id']);   // 订单id

            $this->assign('one',1); // 第一次

            $this->assign('url',$url);
            $this->assign('userid',$order['account']);  // 收款支付宝的数字id
            $this->assign('account',$order['pay_channel_account']); // 收款支付宝的账号
            $this->assign('amount',sprintf('%.2f', $order['pay_amount']));  // 支付多少钱

            if(empty($order['key'])){   // 获取付款支付宝的userId
                $sk = new \authorize();
                $data = $sk->getToken();
                $uid = $data['alipay_system_oauth_token_response']['user_id'];  // 获取付款支付宝的userId
                M('Order')->where($orderWhere)->save(['key'=>$uid]);
            }
            // 延时
            $channel = D('Channel')->where(['id'=>$order['channel_id']])->find();
            $this->assign('pay_delay',$channel['pay_delay']);   // 延时

            $this->display('WeiXin/bill2');
        }
    }

    /// TODO: 干嘛的？
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
        $client = stream_socket_client('tcp://120.78.200.245:39800');
        $json = json_encode(array(
            'cmd' => 'req',
            'paytype' => "alipaycheck",
            'type' =>"alipaycheck",
            'uid'=>$uid,        // 付款支付宝数字id
            'money' => $money,
            'mark' => $orderid,
            'key_id'=>$key_id,  // 收款支付宝账号
            'account'=>$account,// 收款支付宝账号
        ));
        fwrite($client, $json."\n");
    }

    // 上报流水号
    public function getnum(){
        $data = $_REQUEST;
        if(isset($data['mark'])&&!empty($data['payurl'])){
            $orderWhere['pay_orderid'] =$data['mark'];
            M('Order')->where($orderWhere)->save(['upstream_order'=>$data['payurl']]);    // 写入支付宝收款的流水号
        }
    }

    /// 心跳
	public function xintiao(){
        $key = "12345678";
        $data = $_REQUEST;
        file_put_contents('./Data/xintiao.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        if($data['sign'] == md5($data['alipay_id'].$data['account_id'].$key)){
            $channel_account = M('ChannelAccount')->where(['id'=>$data['account_id']])->find();
            if($channel_account){
                M('ChannelAccount')->where(['id'=>$data['account_id']])->limit(1)->save(['last_monitor'=>time(), 'heartbeat_switch'=>1, ]); // 更新心跳
                exit("heartbeat ok");
            }
            exit('channel_account not fouond.');
        }
        exit('xintiao error');
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







    //同步通知
    public function callbackurl()
    {
        $this->display('WeiXin/success');
        //exit('交易成功！');
    }

    //异步通知
    public function notifyurl()
    {
      $key = "12345678";
        $data = $_REQUEST;
        file_put_contents('./Data/gao.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        $money = $data['money'];
        $orderId   = $data['order'];
        $orderWhere['pay_orderid'] = $orderId;
        $orderInfo = M('Order')->where($orderWhere)->find();
        if(empty($orderInfo)){
            exit("a");
        }
        if($orderInfo['pay_status']!=0){
            exit(200);
        }
        $signStr = $data['dt'].$key.$data['money'].$data['order'];
        $sign = md5($signStr);
        if($sign!=$data['key']){
            exit("签名错误");
        }
        $oder_amount = sprintf('%.2f', $orderInfo['pay_amount']);

        if($money!=$oder_amount){
            file_put_contents('./Data/mmmmhbnotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("mo fail");
        }
        // 保存支付账号
        M('Order')->where($orderWhere)->save(['bill_account'=>$data['bill_account']]);
        $this->EditMoney($orderId, 'Envelopes', 0);
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
