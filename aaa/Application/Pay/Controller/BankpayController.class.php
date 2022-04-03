<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;
require_once("redis_util.class.php");
use MoneyCheck;

class BankpayController extends PayController
{
    private $amount = 0;
    public function __construct()
    {
        parent::__construct();

    }

    //支付
    public function Pay($array)
    {
		$orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');

        $notifyurl = $this->_site . 'Pay_Bankpay_notifyurl.html'; //异步通知

        $parameter = array(
            'code' => 'Bankpay', // 通道名称
            'title' => '支付宝转账银行卡(短信)',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        $return = $this->orderadd($parameter);
        $memberId = $return['memberid']+10000;
        $lastFour =  substr($return['appsecret'],-4);

        $key = $memberId.$lastFour;

        $amountTrue=$this->amountRedis($key, $return['amount'], $return['amount']);

        if(!$amountTrue){
         exit("money fill,please try another monney");
        }

        if($amountTrue!=$return['amount']){
            $Order      = M("Order");
            $result = $Order->where(['pay_orderid' => $return['orderid']])->save(['actual_amount'=>$amountTrue]);
            if(!$result){
                exit("订单刷新失败");
            }
        }
        $url = U('Bankpay/getPay',array('id'=>$return['orderid']),true,true);
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('zfbpayUrl',$url);
        $this->assign('money',sprintf('%.2f',$amountTrue));

        if($array['pid']==922){
            $this->display("WeiXin/bankpay");//h5
        }
        else{
            $this->display("WeiXin/alipayori");
        }

    }

    public function getMobile(){
        $orderid = $_REQUEST['orderid'];
        $where['pay_orderid'] = $orderid;
        $order = M('Order')->where($where)->find();
        if(empty($order)){
            exit("非法订单");
        }else{
            if($order['pay_status']>0){
                echo '已支付';exit;
            }
            if(IS_POST){
                $aliUrl = 'alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=';
                $aliUrl .= $url = U('Mqpay/getMobile',array('orderid'=>$orderid),true,true);
                echo $aliUrl;exit;
            }
        }
        $bank['CMB'] = "招商银行";
        $bank['CMBC'] = "民生银行";
        $bank['CCB'] = "建设银行";
        $bank['ABC'] = "农业银行";
        $bank['ICBC'] = "工商银行";
        $bank['SPDB'] = "浦发银行";
        $order['bank_id']=$order['key'];
        $order['bank_name']=$bank[$order['key']];
        $location ="https://ds.Alipay.com/?from=mobilecodec&scheme=" . urlencode("alipays://platformapi/startapp?appId=09999988&actionType=toCard&sourceId=bill&cardNo=".$order['memberid']."&bankAccount=".$order['pay_channel_account']."&money=".$order['actual_amount']."&amount=".$order['actual_amount']."&bankMark=".$order['bank_id']."&bankName=".$order['bank_name']) . "&cardIndex=" . $order['account'] . "&cardNoHidden=true&cardChannel=HISTORY_CARD&orderSource=from";
        $this->assign('account',$order['pay_channel_account']);
        $this->assign('carno',$order['memberid']);
        $this->assign('bankname',$order['bank_name']);
        $this->assign('bankmark',$order['bank_id']);
        $this->assign('amount',$order['actual_amount']);
        $this->assign('cardindex',$order['account']);
        $this->display('WeiXin/bankh5');
    }

    /// 支付宝扫码转到的页面
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
        if ($this->isMobile()) {
            $bank['CMB'] = "招商银行";
            $bank['CMBC'] = "民生银行";
            $bank['CCB'] = "建设银行";
            $bank['ABC'] = "农业银行";
            $bank['ICBC'] = "工商银行";
            $bank['SPDB'] = "浦发银行";
            $order['bank_id']=$order['key'];
            $order['bank_name']=$bank[$order['key']];
            if($this->isInAlipayClient()){

                if(!empty($order['account'])){
                    $location ="https://ds.Alipay.com/?from=mobilecodec&scheme=" . urlencode("alipays://platformapi/startapp?appId=09999988&actionType=toCard&sourceId=bill&cardNo=".$order['memberid']."&bankAccount=".$order['pay_channel_account']."&money=".$order['actual_amount']."&amount=".$order['actual_amount']."&bankMark=".$order['bank_id']."&bankName=".$order['bank_name'] . "&cardIndex=" . $order['account'] . "&cardNoHidden=true&cardChannel=HISTORY_CARD&orderSource=from");

                }
                else{
                    exit("请输入银行卡cardid");
                    $location ="https://ds.Alipay.com/?from=mobilecodec&scheme=" . urlencode("alipays://platformapi/startapp?appId=09999988&actionType=toCard&sourceId=bill&cardNo=".$order['memberid']."&bankAccount=".$order['pay_channel_account']."&money=".$order['actual_amount']."&amount=".$order['actual_amount']."&bankMark=".$order['bank_id']."&bankName=".$order['bank_name']);
                }
                header("Location:".$location);
            }
           exit("fail");
        }
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

    function post($url,$parac){
        $postdata=http_build_query($parac);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }


    // 同步通知
    /// http://www.daikuan2345.vip/Pay_Bankpay_callbackurl.html?orderid=20190301154053535350&pay_memberid=10030&bankcode=924
    public function callbackurl($orderid)
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

    //{"command":"bank","payUser":"","money":"0.10","bankNo":"7114","createTime":"1548643637678","balance":"2.17","receiveUser":"","userid":"10018","sign":"eaa376199ddeab14cf28ce4f713782ff","PHPSESSID":"0rojv57u2s7naa02832uj6qkd3"}
    //异步通知，短信监控app上报
    public function notifyurl()
    {
        $data = $_REQUEST;
        file_put_contents('./Data/smsnotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        //String ord = dt + mark + money + no + type + signKey;
        if($data['sign'] != md5($data['createTime'].$data['bankNo'].$data['money'].$data['no'].$data['command']."111222333"))exit("验签失败");

        $money      = $data['money'];
        $userId     = $data['userid'];
        $lastFour   = $data['bankNo'];

        //$orderWhere['appsecret'] = $lastFour;
        $orderWhere['pay_memberid'] = $userId;
        $orderWhere['actual_amount'] = $money;
        $orderWhere['pay_status'] = 0;
        $orderWhere['pay_bankcode'] = 924;  // 银行编码
        $validTime = time();
        $orderWhere['expire_time'] = array('gt',$validTime);
        $orderInfo = M('Order')->where($orderWhere)->select();  // 未过期的订单

        $orderCount = count($orderInfo);
        if($orderCount<1){
            file_put_contents('./Data/smsorderfail.txt', "【".date('Y-m-d H:i:s')."】找不到订单：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
            exit("order not found ".json_encode($orderWhere));
        }
        if($orderCount==1){
            //正常订单该订单则为正常的
            $orderData = $orderInfo[0];
//            $mchId = $orderData['pay_memberid']-10000;
//            $memberInfo = M('Member')->where(['id' => $mchId])->find();
//
//            if(empty($memberInfo)){
//                //记录找不到商户的订单信息
//                file_put_contents('./Data/bankmemberfail.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
//            }
           // $mchKey = $memberInfo['apikey'];

//            if($mchKey!=$key){
//                file_put_contents('./Data/banksignfail.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
//                $this->success('验签失败');die;
//            }
            $moneyCheck = new moneyCheck();
            $key = $orderData['pay_memberid'].$lastFour;
            $isSystemOrder = $moneyCheck->checkAccountMoney($key,$money);   // TODO:是否系统订单，是怎么定义的？
            if($isSystemOrder){
                //不是系统订单
                file_put_contents('./Data/smssystemfail.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
            //   echo "非系统订单";die;
            }

            $result = $this->EditMoney($orderData['pay_orderid'], 'Bankpay', 0);
            $moneyCheck->deletAccountKey($key,$money);
            echo "success";
        }

        if($orderCount>1){
            //匹配到多个订单
            file_put_contents('./Data/smserror.txt', "【".date('Y-m-d H:i:s')."】多个订单回调参数：\r\n".json_encode($_POST)."\r\n\r\n",FILE_APPEND);
            file_put_contents('./Data/smserror.txt', "【".date('Y-m-d H:i:s')."】多个订单列表：\r\n".json_encode($orderInfo)."\r\n\r\n",FILE_APPEND);
            $this->error("紧急错误！请联系管理员！");
        }
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

    public function paysuccess(){
        $this->display("WeiXin/success");die;
    }
}
