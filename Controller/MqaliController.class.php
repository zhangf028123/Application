<?php

namespace Pay\Controller;
use MoneyCheck;

require_once("redis_util.class.php");
class MqaliController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
//        $data['rmark'] = "201912291248279849502019";
//        var_dump(strpos($data['rmark'],'206666619'));die;
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'Mqalia', // 通道名称
            'title' => '支付宝免签',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );

        if($array['pid']==903){ // 支付宝免签扫码

            $return = $this->orderadd($parameter, 1);
//            $url = 'alipays://platformapi/startapp?appId=09999988&actionType=toAccount&goBack=NO&userId='.$return['appid'].'&amount='.$return['amount'].'&memo='.$return['orderid'];
            // 支付宝收款码
            /*$url = 'alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data=';
            //$url .= '{"s": "money","u": "'.$return['appid'].'","a": "'.$return['amount'].'","m":"'.$return['orderid'].'"}';
            $url .= '{"s": "money","u": "'.$return['appid'].'","a": "'.$return['amount'].'",}';
            $QR = "Uploads/codepay/". $return['orderid'] . ".png";//已经生成的原始二维码图 */
            $QR = "Uploads/codepay/alipay_". $return['appid'] . ".png";
            $now = time();
            if (file_exists($QR) && filectime($QR)+ 5*60 < $now){
                unlink($QR);
            }
            if (!file_exists($QR)) {
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                \QRcode::png($return['appsecret'], $QR, QR_ECLEVEL_H, 20);
                \Think\Log::record("生成支付宝收款码：{$return['appid']} {$return['appsecret']}");
            }
            //\QRcode::png($url, $QR, "L", 20);
            $this->assign("imgurl", '/'.$QR."?t={$now}");
            $this->assign('params',$return);
            $this->assign('orderid',$return['orderid']);
            $this->assign('money',sprintf('%.2f',$return['amount']));
            if($contentType=="json"){
                $payUrl = U('Mqali/toPay',array('orderid'=>$orderid),true,true);
                //$data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$payUrl,'order_id'=>$orderid];
                $data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$return['appsecret'],'order_id'=>$orderid];
                $this->ajaxReturn($data,'JSON');
            }
            else{
                $this->display("ZhiFuBao/alipay_smmq");//h5
            }
        }
        else{
            if ($array['pid']==929)
                $return = $this->orderadd($parameter,0);
            else
                $return = $this->orderadd($parameter,1);
        }
        // 订单号，可以为空，如果为空，由系统统一生成
        $QR = "Uploads/codepay/". $return['account_id'] . ".png";
        if($array['pid']==902 || $array['pid']==929){     // 微信扫码 || 支付宝个人收款码
            if (!file_exists($QR)) {
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                \QRcode::png($return['appid'], $QR, "L", 20);
            }
//            if($array['pid']==903){
//                //支付宝扫码
//                $this->assign("imgurl", '/'.$QR);
//                $this->assign('params',$return);
//                $this->assign('orderid',$return['orderid']);
//                $this->assign('money',sprintf('%.2f',$return['amount']));
//                $this->assign('zfbpayUrl',$return['appid']);
//                $this->display("WeiXin/alipay");
//            }
            if($array['pid']==929){
                $amountTrue = $this->amountRedis($return['account_id'], $return['amount'], $return['amount']);
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
            }

            if($array['pid']==902){
                $this->assign("imgurl", '/'.$QR);
                $this->assign('params',$return);
                $this->assign('orderid',$return['orderid']);
                $this->assign('money',sprintf('%.2f',$return['amount']));
                $this->display("WeiXin/weixin");
            }
            elseif($array['pid']==929){
                $this->assign("imgurl", '/'.$QR);
                $this->assign('params',$return);
                $this->assign('orderid',$return['orderid']);
                $this->assign('money',sprintf('%.2f',$return['amount']));
                $this->display("WeiXin/alipay");
            }
        }elseif($array['pid']==904){ // 支付宝H5
            header('Location: ' . $return['appid']);
        }
    }

    public function toPay(){
        $orderid = $_REQUEST['orderid'];
        $where['pay_orderid'] = $orderid;
        $order = M('Order')->where($where)->find();
        if(empty($order)){
            exit("找不到该订单");
        }else{
            if($order['pay_status']>0){
                echo '订单已支付';exit;
            }
        }
        $amount = sprintf('%.2f',$order['pay_amount']);

        if($this->isInAlipayClient()){
            $url = 'alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data=';
            $url .= '{"s": "money","u": "'.$order['account'].'","a": "'.$amount.'","m":"'.$order['pay_orderid'].'"}';
            header("Location:".$url);
        }
        else{
            exit("请使用支付宝扫一扫打开");
        }
    }
    /**
     * 商户登陆接口
     * PayHelper监控app登陆
     * @return string
     */
    public function login(){

        $wechatAccount = I('username');//微信收款账户  //0表示不使用
        $apiKey = I('usertoken');//商户APIKEY
        $aliAccount = I('userid');//支付宝收款账户

        if($wechatAccount===0&&$aliAccount===0){
            $this->error('微信和支付宝账户需填写一个！');
        }

        if(empty($apiKey)){
            $this->error('请填写商户APIKEY');
        }

        if($apiKey!="111222333"){
            $this->error('商户秘钥不正确！');
        }

        $accountModel = M('ChannelAccount');
        if($aliAccount!==0&&!empty($aliAccount)){
            $where['title'] = $aliAccount;
            $aliAccountInfo = $accountModel->where($where)->find();
            if(!$aliAccountInfo){
                $this->error('支付宝账户不存在,不使用微信请输入0');
            }
            $accountModel->where($where)->save(['last_monitor'=>time(), 'heartbeat_switch'=>1]);
        }

        if($wechatAccount!==0&&!empty($wechatAccount)){
            $where['title'] = $wechatAccount;
            $wechatAccountInfo = $accountModel->where($where)->find();
            if(!$wechatAccountInfo){
                $this->error('微信账户不存在,不使用微信请输入0');
            }
            $accountModel->where($where)->save(['last_monitor'=>time(), 'heartbeat_switch'=>1]);
        }

        $re_account['zfb'] = ['*'];
        $re_account['wx'] = ['*'];
        $re_account['qq'] = ['*'];
        $this->success('登陆成功',$re_account);
    }

    /**
     * 商户登陆接口 未加微信扫码免签使用
     * @return string
     */
    public function login1(){
        file_put_contents('./Data/login.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
        //{"usertoken":"gghhv","userid":"123456","username":"dddd","key":"0bb0e2bf26da1774743ccb71084db866","m":"Pay"}

        $id = I('username','intval')-10000;//商户号
        $userToken = I('usertoken');//signkey
        $username = I('userid');//收款账户子账户名称
        if($id<0||empty($id)){
            $this->error('找不到商户ID');
        }
        if(!is_numeric($id)){
            $this->error('商户ID为数字');
        }
        if(empty($userToken)){
            $this->error('signkey不能为空');
        }
        $mchInfo = M('Member')->where(['id' => $id])->find();
        if(empty($mchInfo)){
            $this->error('找不到该商户！');
        }
        if($mchInfo['apikey']!=$userToken){
            $this->error('商户秘钥不正确！');
        }
        if(empty($username)){
            $this->error('商户名称请填写支付宝登录手机或邮箱');
        }
        if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $username)>0){
            $this->error('商户名称请填写支付宝登录手机或邮箱!');
        }
        if(!($this->is_mobile($username)||$this->checkEmail($username))){
            $this->error('商户名称不是填写邮箱或手机!');
        }
        $accountModel = M('ChannelAccount');
        $where['title'] = $username;
        $accountInfo = $accountModel->where($where)->find();
        if(!$accountInfo){
            $this->error('账户不存在');
        }
        if($accountInfo['status']!=1){
            $this->error('子账户状态未开启！');
        }

        //$accountInfo['title'] = substr_replace($accountInfo['title'],'******',3,6);
        $re_account['zfb'] = [$accountInfo['title']];
        $re_account['wx'] = ['*'];
        $re_account['qq'] = ['*'];
        $this->success('登陆成功',$re_account);
    }
    public function result($msg,$data,$code = 0){
        if($code == 1){
            $this->success($msg,$data);
        }else{
            $this->error($msg,$code);
        }
    }
    public function error($msg,$code = 0){
        $data = ['code'=>0,'msg'=>$msg];
        $this->ajaxReturn($data,'JSON');
    }

    public function success($msg,$redata= []){
        $data = ['code'=>1,'msg'=>$msg,'data'=>$redata];
        $this->ajaxReturn($data,'JSON');
    }

    /// 监控登陆
    public function bind_cid()
    {
        file_put_contents('./Data/bind.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);

        $bindInfo  = $_REQUEST;
        if(isset($bindInfo['flag'])&&$bindInfo['flag']=="notify"){
            $accountModel = M('ChannelAccount');
            $where['title'] =array(array('eq',$bindInfo['username']),array('eq',$bindInfo['userid']),'or');;
            $data['last_monitor'] = time();
            $data['heartbeat_switch']=1;
            $accountInfo = $accountModel->where($where)->save($data);
        }

        $this->result("成功",[],1);die;
        $cid = I('cid');
        if(!$cid){
            $this->result("cid 不能为空",[],99);
        }
        $id = I('userid',0,'intval');
        $account = I('account');
        $deviceid = I('deviceid');

        $accountModel = M('ChannelAccount');
        $where['mch_id'] = $id;
        $where['status'] = 1;
        $accountInfo = $accountModel->where($where)->find();

        if(!$accountInfo || !$id){
            $this->result("用户不存在",[],99);
        }
        $pid = I('uid','');
        if($pid){
            $data['appid'] = $pid;
        }
        $data['appsecret'] = $cid;
        $result = $accountModel->where($where)->save($data);
        if($result === false){
            $this->result("更新cid失败",[],99);
        }
        $this->result("成功",[],1);
    }

    function is_mobile($mobile) {
        if(preg_match("/^1[345678]{1}\d{9}$/",$mobile)){
            return true;
        }else{
            return false;
        }
    }
    function checkEmail($str){
        $checkmail="/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";
        if(preg_match($checkmail,$str)){
            return true;
        }
        else{
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
        return $result;}


    //同步通知
    public function callbackurl($trade_no)
    {
        $Order = M("Order");
        $pay_status = $Order->where(['pay_orderid' => $trade_no])->getField("pay_status");
        if($pay_status > 0){
            $this->EditMoney($trade_no, 'Aliwap', 1);

            exit('交易成功！');
        }else{
            exit("error");
        }


    }

    // PayHelper 通知
    public function notify(){
 
//        $temp = str_replace('￥','',$orderamount);
        $ip = $this->getIP();
        $data = $_POST;

        file_put_contents('./Data/alinotify.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_POST)."请求IP地址:".$ip."\r\n\r\n",FILE_APPEND);

        // date('Y')， 2019
        if(strpos($data['rmark'],date('Y')) !== false){
            
            $data = $_POST;
            $orderId = $data['rmark'];
            $orderWhere['pay_orderid'] = $orderId;
            $orderInfo = M('Order')->where($orderWhere)->find();

            if(empty($orderInfo)){
                $this->error('order not found');
            }else{
                $orderamount = sprintf('%.2f', $orderInfo['pay_amount']);
                if(!is_numeric($orderamount)){
                    $temp = str_replace('￥','',$orderamount);
                    $data['money'] = sprintf('%.2f', $temp);
                }
                if($orderamount!=$data['money']){
                    $this->error('different money');
                }
                $account =1;
                if(empty($account)){
                    $this->success('1235');
                }else{
                    $isSuccess = $this->checkSignG($data,"111222333");
                    if($isSuccess != 1){
                        $this->error('1236');
                    }else{
                        //如果订单状态为未支付，才需要算余额
                        if($orderInfo['pay_status']== 0) {
                            try{
                                M('Order')->where($orderWhere)->save(['upstream_order'=>$data['no'], 'bill_account'=>$data['bill_account']]);    // 写入支付宝流水号
                                $this->EditMoney($orderId, 'OnePay', 0);
                                $this->success('success');
                            }
                            catch (Exception $e){
                                $this->error('1237');
                            }
                        }else{
                            $this->success('11');
                        }
                    }
                }
            }
        }
        else{   // 收款码

            // 验证签名
            $mchKey ="111222333";
            $checkSign = $this->checkSignG($data,$mchKey);
            if(!$checkSign){
                file_put_contents('./Data/signfail.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                $this->error('sign fail');die;
            }

            $orderWhere['pay_amount'] = $data['money'];
            $orderWhere['pay_status'] = 0;
            if($data['type']==1){   // 微信Pay
                $orderWhere['pay_channel_account'] = $data['username'];
                $orderWhere['pay_bankcode'] = 902;
            }
            if($data['type']==2){   // 支付宝 个人收款码
                $orderWhere['pay_bankcode'] = array(array('eq',904),array('eq',903),array('eq',929), array('eq',932), 'or');
                $orderWhere['pay_channel_account'] = $data['userid'];
            }
            $validTime = time();
            $orderWhere['expire_time'] = array('gt',$validTime);    // 找未过期的
            $orderInfo = M('Order')->where($orderWhere)->select();

            $orderCount = count($orderInfo);
            if($orderCount<1){
                file_put_contents('./Data/orderfail.txt', "【".date('Y-m-d H:i:s')."】找不到订单：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                $this->success('success');
            }
            if($orderCount==1){
                $orderData = $orderInfo[0];
                $mchId = $orderData['pay_memberid']-10000;
                $memberInfo = M('Member')->where(['id' => $mchId])->find();

                if(empty($memberInfo)){
                    file_put_contents('./Data/memberfail.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                }
                $moneyCheck = new MoneyCheck();
                $isSystemOrder = $moneyCheck->checkAccountMoney($orderData['account_id'],$data['money']);
                if($isSystemOrder){
                    file_put_contents('./Data/systemfail.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                    $this->success('success');die;
                }

                M('Order')->where(['id'=>$orderData['id']])->save(['upstream_order'=>$data['no'], 'bill_account'=>$data['bill_account']]);    // 写入支付宝流水号
                $result = $this->EditMoney($orderData['pay_orderid'], 'Mqali', 0);
                $moneyCheck->deletAccountKey($orderData['account_id'],$data['money']);
                $this->success('success');
            }

            if($orderCount>1){
                //匹配到多个订单
                file_put_contents('./Data/syserror.txt', "【".date('Y-m-d H:i:s')."】多个订单回调参数：\r\n".json_encode($_POST)."\r\n\r\n",FILE_APPEND);
                file_put_contents('./Data/syserror.txt', "【".date('Y-m-d H:i:s')."】多个订单列表：\r\n".json_encode($orderInfo)."\r\n\r\n",FILE_APPEND);
                // 记录数据库

                $this->error("too many order");
            }
        }



    }

    public function checkSignG($parameter,$merkey = null){
        if(!isset($parameter['key'])){
            return 0;
        }else{
            $key  = $parameter['key'];
            unset($parameter['key']);
            $checkKey = $this->createSignGG($parameter,$merkey);
            if($key == $checkKey){
                return 1;
            }else{
                return 0;
            }
        }
    }

    /**
     * 生成签名
     * @param $parameter
     * @param bool $moveNull
     * @return string
     */
    public function createSignGG($parameter, $merkey = null, $moveNull = true) {
        $signature = "";
        if (is_array($parameter)) {
            ksort($parameter);
            foreach ($parameter as $k => $v) {
                if ($moveNull) {
                    if ($v !== "" && !is_null($v)) {
                        if($v=="999"){
                            $v="";
                        }
                        $signature .= $k . "=" . $v . "&";

                    }
                } else {
                    $signature .= $k . "=" . $v . "&";
                }
            }

            if ($signature) {
                $signature .= "token=" . $merkey;
                $signature = md5($signature);
            }
        }
        //echo "生成签名".$signature."<br />";
        return $signature;
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

    /*public function paysuccess(){
        $this->display("WeiXin/success");die;
    }*/
}
