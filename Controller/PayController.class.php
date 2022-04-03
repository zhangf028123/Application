<?php

namespace Pay\Controller;

require_once('redis_util.class.php');

use MoneyCheck;
use Think\Cache;
use Think\Controller;
use Org\Util\Date;
use Org\SignatureUtil;
use Think\Exception;
use \Think\Log;

class PayController extends Controller
{
    //商家信息
    protected $merchants;   // pay_member 一行
    //网站地址
    protected $_site;   // 本站网址
    //通道信息
    protected $channel; // ProductUser 一行

    public function __construct()
    {
        parent::__construct();
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';   // 网址根地址

        $this->assign("siteurl", $this->_site);
        //$client_ip = get_client_ip(); // 不能用thinkphp提供的，有可能通过代理伪造
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $ip = get_client_ip(); // 可能是伪造的
        if(ACTION_NAME == 'notifyurl' && (empty($client_ip) || !in_array($client_ip , C('NOTIFY_WHITELIST')))
            && !in_array(CONTROLLER_NAME, ['Alipage', 'Aliscan', 'Aliwap', 'Mqali', 'PddQrcode',])   // 这些不需要禁止
        ){
            Log::record("非法回调IP： $client_ip $ip, request=".json_encode($_REQUEST),'ERR',true); // 看看发了什么非法内容
            exit(''); // 不能给前端返回过多信息了
        }
        if( !empty($client_ip) && $client_ip != $ip){
            Log::record("禁止通过代理访问，也可能是伪造ip： client_ip = $client_ip , ip = $ip, request=".json_encode($_REQUEST),'ERR',true);
            if(empty($_REQUEST)) 
                die("");
        }
    }

    /**
     * 创建订单
     * @param $parameter
     * @param $type==0为默认模式    1为检测子账户金额满足模式
     * @return array
     */
    public function orderadd($parameter,$type=0)
    {
        $this->channel = $parameter['channel'];
        //银行通道费率
        $syschannel = M('Channel')
            ->where(['id' => $this->channel['api']])    // pay_channel.id
            ->find();
        $pay_amount1 = I("post.pay_amount", 0);
        $pay_orderamount = $pay_amount1;
        $pay_amount = 0;
        $moneylist = $syschannel['money_list'];

        $moneylist1 = explode(',', $moneylist);
        $moneylist2 = explode('-', $moneylist);
        if (strstr($moneylist, ',')) {
            if (in_array($pay_amount1, $moneylist1)) {
                $pay_amount = $pay_amount1;
            } else {
                //遍历加减1块.
                foreach ($moneylist1 as $k => $v) {
                    if ($pay_amount1 - intval($v) <= 15 && $pay_amount1 - intval($v) >= -15) {
                        $pay_amount = intval($v);
                        break;
                    }
                }
            }
        }
        elseif (strstr($moneylist, '-')){
            if ($pay_amount1 >= intval($moneylist2['0']) && $pay_amount1 <= intval($moneylist2['1'])) {
                $pay_amount = $pay_amount1;
            }
        }
         else {
            $pay_amount = $pay_amount1;
        }
        if ($pay_amount == 0) {
            \Think\Log::record('orderadd moneyerror:'.$this->channel['api'].'---'.$pay_amount1.'---'.$moneylist, 'ERR', true);
            $this->showmessage('金额错误！');
        }
        if (!$pay_amount || !is_numeric($pay_amount) || $pay_amount <= 0) {
            $this->showmessage('金额错误');
        }
        if(!isset($_SERVER['HTTP_REFERER'])){
            //$this->showmessage('no HTTP_REFERER');
        }
        if($type){
            $moneyCheck = new MoneyCheck();
        }
        //通道信息
     //   $this->channel = $parameter['channel'];

        $pay_order_amount = $pay_amount;
        if(in_array($this->channel['pid'], [  //903, 支付宝免签扫码
            932, ])){
            $pay_amount += mt_rand(-5, 5) / 100.0;    // 随机加几分钱
        }
        //$this->merchants = $this->channel['userid'];
        //用户信息
        $usermodel       = D('Member');
        $this->merchants = $usermodel->get_Userinfo($this->channel['userid']);
        if($this->merchants['groupid'] != 4){
            $this->showmessage('商户:' . '无权限下单!');
        }
        //验签
        if ( ! $this->verify()) {
            $this->showmessage('签名验证失败', $_POST);
        }

        $m_Tikuanconfig     = M('Tikuanconfig');
        $tikuanconfig       = $m_Tikuanconfig->where(['userid' => $this->merchants['id']])->find();
        if (!$tikuanconfig || $tikuanconfig['tkzt'] != 1 || $tikuanconfig['systemxz'] != 1) {
            $tikuanconfig = $m_Tikuanconfig->where(['issystem' => 1])->find();
        }
        //费率
        $_userrate = M('Userrate')
            ->where(["userid" => $this->channel['userid'], "payapiid" => $this->channel['pid']])
            ->find();
        //银行通道费率
        /*$syschannel = M('Channel')
            ->where(['id' => $this->channel['api']])    // pay_channel.id
            ->find();
        */

        //---------------------------子账号风控start------------------------------------
        $isSelfCouont = (1 == $this->merchants['collect_type']); // 是不是自己供号,自己供号，只能是自己id，否则采用系统提供的账号，memberid=0
        $tmp_where = ['channel_id' => $syschannel['id'], 'status' => '1', 'heartbeat_switch'=>1,'manual_switch'=>1, ];
        $channel_account_list        = M('channel_account')->where($tmp_where)->select(); // 所有子账号
        $forbit_accounts = [];
        if($isSelfCouont){
            $tmp_where['memberid'] = $this->merchants['id']; // 自供，只能选自己的
        }else{  // 否则可以选择，所有的非自供账号
            $forbit_accounts = M('channel_account')->alias('a')->field('a.id')->join('__MEMBER__ as m ON a.memberid=m.id')->where(['m.collect_type'=>1])->select();
        }
        $account_ids                 = M('UserChannelAccount')->where(['userid' => $this->channel['userid'], 'status' => 1])->getField('account_ids');  // 有些用户指定只能使用某些子账号
        if($account_ids){
             $account_ids  = explode(',',  $account_ids );
            foreach($channel_account_list as $k => $v){
                //如果不在指定的子账号，将其删除
                if(!in_array($v['id'], $account_ids )){
                    unset($channel_account_list[$k]);
                }
            }
        }
        // 排除自供账号
        if($forbit_accounts){
            foreach($channel_account_list as $k => $v){
                //如果不在指定的子账号，将其删除
                if(in_array($v['id'], $forbit_accounts )){
                    unset($channel_account_list[$k]);
                }
            }
        }

        $error_msg                   = '已下线';
        $i = 0;
        do{
            $pay_amount = $pay_order_amount + ($i++ % 100) / 100;
            Log::record("金额可否？： pay_amount = $pay_amount ",'ERR',true);
            $l_ChannelAccountRiskcontrol = new \Pay\Logic\ChannelAccountRiskcontrolLogic($pay_amount);
            $channel_account_item        = [];  // 筛选可以付款的子账号
            foreach ($channel_account_list as $k => $v) {
                //判断是自定义还是继承渠道的风控
                $temp_info               = $v['is_defined'] ? $v : $syschannel;
                $temp_v = null;
                // control_status  或者 control_status和offline_status同时等1才会可能选到这个账号
                if ($temp_info['offline_status'] && $temp_info['control_status']) { // offline_status 上线状态-1上线,0下线；control_status风控状态-0不风控,1风控中
                    $temp_info['account_id'] = $v['id']; //用于子账号风控类继承渠道风控机制时修改数据的id
                    //子账号风控
                    $l_ChannelAccountRiskcontrol->setConfigInfo($temp_info);
                    $error_msg = $l_ChannelAccountRiskcontrol->monitoringData();
                    if ($error_msg === true) {
                        $temp_v = $v;
                    }elseif(preg_match( '/当天总交易金额超额!.*/',$error_msg)){
                        M('channel_account')->where(['id'=>$v['id']])->save(['status'=>0]);  // 下线该账号
                        write_account_switch_log($v['id'], 0,8, $error_msg);  // 风控关
                    }
                } else if ($temp_info['control_status'] == 0) {
                    $temp_v = $v;
                }
                if($temp_v){
                    //过滤掉金额已经存在的子账户
                    if($type){
                        //过滤掉金额已经存在的子账户
                        $checkResult = $moneyCheck->checkAccountMoney($temp_v['id'],$pay_amount);    // 一段时间内，只能有一笔此金额的订单
                        if($checkResult){
                            //$channel_account_item[] = $v;
                        }
                        else{
                            $error_msg = "收款账户该金额：".sprintf('%.2f', $pay_amount)."已经用满，请稍后再试或者换一个金额充值";
                        }
                    }
                    else{
                        $checkResult = true;
                        //$channel_account_item[] = $v;   // 插入一个
                    }
                    if($checkResult)
                        $channel_account_item[] = $temp_v;   // 插入一个
                }
            }

            //Log::record("channel_account_item =".json_encode($channel_account_item),'ERR',true);
        }while($type && $i < 10 && empty($channel_account_item));

        if($this->channel['istest']){   // 测试
            $channel_account_item = [M('channel_account')->where(['id'=>$this->channel['channel_account_id']])->find()];
        }
        if (empty($channel_account_item)) {
            $this->showmessage('账户:' . $error_msg); // 只返回最后一个错误
        }
        //-------------------------子账号风控end-----------------------------------------

        if (count($channel_account_item) == 1) {
            $channel_account = current($channel_account_item);
        } else {
            $channel_account = getWeight($channel_account_item);
        }

        //商户订单号
        $out_trade_id = $parameter['out_trade_id'];
        //生成系统订单号
        $cache      =   Cache::getInstance('redis');
        do{
            $pay_orderid = $parameter['orderid'] ? $parameter['orderid'] : get_requestord();    // 这里可能订单号冲突了，需要优化
        }while( ! $cache->setnx($pay_orderid, $pay_orderid) );
        $cache->setex($pay_orderid, 60, $pay_orderid);

        //开启自定义模式则继续判断是否有使用过，防止高并发
        if($type){
            Log::record("尝试写入金额： pay_amount = $pay_amount , pay_orderid = $pay_orderid",'ERR',true);
            $checkResult = $moneyCheck->setAccountKey($channel_account['id'],$pay_amount, $pay_orderid);
            if(!$checkResult){
                $this->showmessage('账户:交易量过大，限制交易！');
            }
        }

        $syschannel['mch_id']    = $channel_account['mch_id'];
        $syschannel['signkey']   = $channel_account['signkey'];
        $syschannel['appid']     = $channel_account['appid'];
        $syschannel['appsecret'] = $channel_account['appsecret'];
        $syschannel['account']   = $channel_account['title'];

        // 收款账号定制费率
        if ($channel_account['custom_rate']) {
            $syschannel['t0defaultrate'] = $channel_account['t0defaultrate'];   // 运营费率
            $syschannel['t0fengding']    = $channel_account['t0fengding'];  // 封顶？
            $syschannel['t0rate']        = $channel_account['t0rate'];  // 计算成本
            $syschannel['defaultrate'] = $channel_account['defaultrate'];   // 运营费率
            $syschannel['fengding']    = $channel_account['fengding'];  // 封顶？
            $syschannel['rate']        = $channel_account['rate'];  // 计算成本
        }
        //平台通道
        $platform = M('Product')
            ->where(['id' => $this->channel['pid']])    // ProductUser.pid
            ->find();
        if ($channel_account['unlockdomain']) {
            $unlockdomain = $channel_account['unlockdomain'] ? $channel_account['unlockdomain'] : '';
        } else {
            $unlockdomain = $syschannel['unlockdomain'] ? $syschannel['unlockdomain'] : '';
        }

        // 通道名称
        $PayName = $parameter["code"];  // 控制器
        //回调参数
        $return = [
            "mch_id"       => $syschannel["mch_id"], //商户号
            "signkey"      => $syschannel["signkey"], // 签名密钥
            "appid"        => $syschannel["appid"], // APPID
            "appsecret"    => $syschannel["appsecret"], // APPSECRET
            "gateway"      => $syschannel["gateway"] ? $syschannel["gateway"] : $parameter["gateway"], // 网关
            "notifyurl"    => $syschannel["serverreturn"] ? $syschannel["serverreturn"] : $this->_site . "Pay_" . $PayName . "_notifyurl.html",
            "callbackurl"  => $syschannel["pagereturn"] ? $syschannel["pagereturn"] : $this->_site . "Pay_" . $PayName . "_callbackurl.html",
            'unlockdomain' => $unlockdomain, //防封域名
        ];

        //商户编号
        $return["memberid"] = $userid = $this->merchants['id'] + 10000;

        // 费率，有3个地方,优先级： 下单商户的费率 ， 成本： 收款账号的费率 > 支付通道的费率
        // 下单商户费率

        $todaydate = time();
        $todayhour = date("H", $todaydate);
        if ($todayhour < 9) {
            $_userrate['t0feilv']    = max($_userrate['t0feilv_night'], $_userrate['t0feilv']);
            $_userrate['t0fengding'] = max($_userrate['t0fengding_night'], $_userrate['t0fengding']);
        }

        if ($tikuanconfig['t1zt'] == 0) { //T+0费率
            $feilv    = $_userrate['t0feilv'] ? $_userrate['t0feilv'] : $syschannel['t0defaultrate']; // 交易费率
            $fengding = $_userrate['t0fengding'] ? $_userrate['t0fengding'] : $syschannel['t0fengding']; // 封顶手续费
        } else { //T+1费率
            $feilv    = $_userrate['feilv'] ? $_userrate['feilv'] : $syschannel['defaultrate']; // 交易费率
            $fengding = $_userrate['fengding'] ? $_userrate['fengding'] : $syschannel['fengding']; // 封顶手续费
        }
        $fengding = $fengding == 0 ? 9999999 : $fengding; //如果没有设置封顶手续费自动设置为一个足够大的数字

        // 交易金额比例
        $moneyratio = $parameter["exchange"];
        //金额格式化
        $return["amount"] = floatval($pay_amount) * $moneyratio; // 交易金额
        $pay_sxfamount    = (($pay_amount * $feilv) > ($pay_amount * $fengding)) ? ($pay_amount * $fengding) :
            ($pay_amount * $feilv); // 手续费

        $pay_shijiamount = $pay_amount - $pay_sxfamount; // 实际到账金额   //实际到账金额让商户处减掉的钱,实价

        // 计算成本
        if ($tikuanconfig['t1zt'] == 0) { //T+0费率
            $cost = bcmul($syschannel['t0rate'], $pay_amount, 2); //计算成本
        } else {
            $cost = bcmul($syschannel['rate'], $pay_amount, 2); //计算成本
        }

        //验签
        //if ($this->verify()) // 前面验过了
        {
            // 该收款账号的主人的所有账号id
            $cur_account_ids = array_column(M('channel_account')->where(['memberid'=>$channel_account['memberid']])->field('id')->select(), 'id') ?: [];
            if($isSelfCouont && $pay_sxfamount > $this->merchants['td_sxf']){ // 预存手续费不足
                //M('Member')->where(['id'=>$this->merchants['td_sxf']])->save(['status'=>0]);    // 把用户关闭?
                M('channel_account')->where(['memberid'=>$channel_account['memberid']])->save(['status'=>0]);  // 全部下线
                // TODO: 写日志
                $this->showmessage('预存手续费不足，收款账号已下线！');
            }
            // 是不是供号商的账号？
            if(!$isSelfCouont && $channel_account['memberid'] != 0){
                $account_member = $usermodel->get_Userinfo($channel_account['memberid']);
                if($account_member ) {
                    // 供码商的分润
                    $pay_profit = $this->CalcPayProfit($tikuanconfig['t1zt'], $pay_amount, $channel_account)[0];
                    // 计算额度
                    //
                    if ($account_member['td_balance'] < $account_member['amount_water'] + ($pay_amount - $pay_profit) + $account_member['dj_amount_water']) {  // 额度不足
                        M('channel_account')->where(['memberid' => $channel_account['memberid']])->save(['status' => 0]);  // 全部下线
                        $logstr = "td_balance < amount_water + (pay_amount-pay_profit) + dj_amount_water:{$account_member['td_balance']} < {$account_member['amount_water']} + ({$pay_amount} - {$pay_profit}) + {$account_member['dj_amount_water']}";
                        write_account_switch_log($cur_account_ids, 0,6, $logstr);  // 额度不足
                        $this->showmessage('额度不足，收款账号已下线！');
                    }
                }
            }

            // 连续失败次数是否超过
            if($syschannel['fail_limit'] <= $channel_account['fail_times']){
                M('channel_account')->where(['id'=>$channel_account['id'], 'memberid'=>$channel_account['memberid']])->save(['status'=>0, 'fail_times'=>0]);  // 全部下线
                write_account_switch_log($channel_account['id'], 0,5);  // 连续失败
                $this->showmessage('连续失败次数超过阀值，收款账号已下线！');
            }
            // 心跳是否超时
            if( in_array( $this->channel['pid'], [903, 928, 929, 930, 931, ]) &&   // 已经做了心跳的监控
                //!in_array($this->channel['pid'], [933, 934, 935, 936, 937, 938, 939, 941, 942, 944, 945, 946, 947, 948, 949,950, ]) && // 支付宝手机支付不需要心跳
                $channel_account['last_monitor'] + 3*60 < time()){
                M('channel_account')->where(['id'=>$channel_account['id']])->save(['heartbeat_switch'=>0]);  // 下线该账号
                write_account_switch_log($channel_account['id'], 1,4);  // 心跳超时关
                $this->showmessage('心跳超时，收款账号已下线！');
            }
            $Order                       = M("Order");
            $return['bankcode']          = $this->channel['pid'];   // pay_product.id
            $return['code']              = $platform['code']; //银行英文代码
            $return['orderid']           = $pay_orderid; // 系统订单号
            $return['out_trade_id']      = $out_trade_id; // 外部订单号
            $return['subject']           = $parameter['body']; // 商品标题
            $return['account_id']        = $channel_account['id']; //子账户ID
            $return['channel_account']   = $channel_account;    // 验证名字用

            $data['pay_memberid']        = $userid;
            $data['pay_orderid']         = $return["orderid"];
            $data['pay_amount']          = $pay_amount; // 交易金额
            $data['pay_poundage']        = $pay_sxfamount; // 手续费
            $data['pay_actualamount']    = $pay_shijiamount; // 商户到账金额 $pay_shijiamount = $pay_amount - $pay_sxfamount;
            $data['pay_applydate']       = time();  // 订单生成的时间
            $data['expire_time']         = time()+300;
            $data['pay_bankcode']        = $this->channel['pid'];
            $data['pay_bankname']        = $platform['name'];
            $data['pay_notifyurl']       = I('post.pay_notifyurl', '');
            $data['pay_callbackurl']     = I('post.pay_callbackurl', '');
            $data['pay_status']          = 0;
            $data['pay_tongdao']         = $syschannel['code'];
            $data['pay_zh_tongdao']      = $syschannel['title'];
            $data['pay_channel_account'] = $syschannel['account'];
            $data['pay_ytongdao']        = $parameter["code"];
            $data['pay_yzh_tongdao']     = $parameter["title"];
            $data['pay_tjurl']           = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            $data['pay_productname']     = I("request.pay_productname");
            $data['attach']              = I("request.pay_attach");
            $data['out_trade_id']        = $out_trade_id;
            $data['ddlx']                = I("post.ddlx", 0);   // 下游订单类型
            $data['memberid']            = $return["mch_id"];
            $data['key']                 = $return["signkey"];
            $data['actual_amount']       = $pay_amount;
            $data['account']             = $return["appid"];
            $data['cost']                = $cost;
            $data['cost_rate']           = $tikuanconfig['t1zt'] == 0 ? $syschannel['t0rate'] : $syschannel['rate'];    // 成本费率
            $data['channel_id']          = $this->channel['api'];
            $data['account_id']          = $channel_account['id'];
            $data['appsecret']           = $channel_account['appsecret'];
            $data['t']                   = $tikuanconfig['t1zt'];
            $data['istest']              = $this->channel['istest'];    // 测试
            $data['pay_order_amount']    = $pay_order_amount;
            $data['pay_orderamount']     = $pay_orderamount;

            $data['pay_device']          = $this -> get_device_type();
            $date['pay_ip'] = $_SERVER['REMOTE_ADDR'];


            // return $data;
            //添加订单
            if ($Order->add($data)) {
                $return['datetime'] = date('Y-m-d H:i:s', $data['pay_applydate']);
                $return["status"]   = "success";

                // 统计账号下单数
                //if(!$data['istest'])
                    $this->day_account_stat('submit', $data);

                return $return;
            } else {
                $this->showmessage('系统错误:'.$Order->getError());
            }
        }
    }

    public function get_device_type()
    {
        //全部变成小写字母
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $type = 'other';
        //分别进行判断
        if(strpos($agent, 'iphone') || strpos($agent, 'ipad')) {
            $type = 'ios';
        }
        if(strpos($agent, 'android')) {
            $type = 'android';
        }
        return $type;
    }


    public function autoNotify($trans_id, $sign){
        if (!$trans_id || !$sign){
            die('参数错误!');
        }
        $key = 'itxmEHU8f6FASrwurOD';
        if (md5($trans_id.$key) != $sign){
            die('验签失败');
        }
        return $this->EditMoney($trans_id, '', 0);
    }

    /**
     * 回调处理订单
     * @param $trans_id string 订单号
     * @param $PayName
     * @param int $returntype 0:异步回调，Web服务器post到 pay_notifyurl, 1:同步回调，浏览器post到 pay_callbackurl
     * @return bool
     */
    public function EditMoney($trans_id, $pay_name = '', $returntype = 1, $transaction_id = '')
    {
        $m_Order    = M("Order");
        $order_info = $m_Order->where(['pay_orderid' => $trans_id])->find(); //获取订单信息
        $userid     = intval($order_info["pay_memberid"] - 10000); // 商户ID
        $time       = time(); //当前时间

        //********************************************订单支付成功上游回调处理********************************************//
        if ($order_info["pay_status"] == 0) {
            //开启事务
            M()->startTrans();
            //查询用户信息
            $m_Member    = M('Member');
            $member_info = $m_Member->where(['id' => $userid])->lock(true)->find();
            //更新订单状态 1 已成功未返回 2 已成功已返回
            $save_data = ['pay_status' => 1, 'pay_successdate' => $time, ];
            $res = $m_Order->where(['pay_orderid' => $trans_id, 'pay_status' => 0])
                ->save($save_data);    // 成功支付的时间
            if (!$res) {
                M()->rollback();
                \Think\Log::record('EditMoney 修改pay_status-回滚','ERR',true);
                return false;
            }

            //-----------------------------------------修改用户数据 商户余额、冻结余额start-----------------------------------
            //要给用户增加的实际金额（扣除投诉保证金）
            $actualAmount          = $order_info['pay_actualamount'];
            $complaintsDepositRule = $this->getComplaintsDepositRule($userid);
            if (isset($complaintsDepositRule['status']) && $complaintsDepositRule['status'] == 1) {
                if ($complaintsDepositRule['ratio'] > 100) {
                    $complaintsDepositRule['ratio'] = 100;
                }
                $depositAmount = round($complaintsDepositRule['ratio'] / 100 * $actualAmount, 4);
                $actualAmount -= $depositAmount;
            }

            //创建修改用户修改信息
            $member_data = [
                'last_paying_time'   => $time,                                          // 当天最后一笔已交易时间
                'unit_paying_number' => ['exp', 'unit_paying_number+1'],                // 单位时间已交易次数
                'unit_paying_amount' => ['exp', 'unit_paying_amount+' . $actualAmount], // 单位时间已交易金额
                'paying_money'       => ['exp', 'paying_money+' . $actualAmount],       // 当天已交易金额
            ];

            //判断用结算方式
            switch ($order_info['t']) {
                case '0':
                //t+0结算
                case '7':
                //t+7 只限制提款和代付时间，每周一允许提款
                case '30':
                    //t+30 只限制提款和代付时间，每月第一天允许提款
                    $ymoney                 = $member_info['balance']; //改动前的金额
                    $gmoney                 = bcadd($member_info['balance'], $actualAmount, 4); //改动后的金额
                    $member_data['balance'] = ['exp', 'balance+' . $actualAmount]; //防止数据库并发脏读，加余额
                    break;
                case '1':
                    //t+1结算，记录冻结资金
                    $blockedlog_data = [
                        'userid'     => $userid,
                        'orderid'    => $order_info['pay_orderid'],
                        'amount'     => $actualAmount,
                        'thawtime'   => (strtotime('tomorrow') + rand(0, 7200)),    // 明天解冻，TODO: 什么时候执行解冻？
                        'pid'        => $order_info['pay_bankcode'],    // pay_product.id
                        'createtime' => $time,
                        'status'     => 0,
                    ];
                    $blockedlog_result = M('Blockedlog')->add($blockedlog_data);
                    if (!$blockedlog_result) {
                        M()->rollback();
                        \Think\Log::record('EditMoney 添加Blockedlog-回滚','ERR',true);
                        return false;
                    }
                    $ymoney                        = $member_info['blockedbalance']; //原冻结资金
                    $gmoney                        = bcadd($member_info['blockedbalance'], $actualAmount, 4); //改动后的冻结资金
                    $member_data['blockedbalance'] = ['exp', 'blockedbalance+' . $actualAmount]; //防止数据库并发脏读

                    break;
                default:
                    # code...
                    break;
            }

            // 扣取预存手续费
            $isSelfCouont = (1 == $member_info['collect_type']); // 是不是自己供号,自己供号，只能是自己id，否则采用系统提供的账号，memberid=0
            if($isSelfCouont ){ // 预存手续费不足
                $member_data['td_sxf'] = bcsub($member_info['td_sxf'], $order_info['pay_poundage'], 4); //改动后的预存手续费
                // 扣预存手续费记录
                //预存手续费变更记录
                $arrayRedo = array(
                    'user_id'  => $userid,
                    'orderid'   => $order_info['id'], // 订单表的id，扣手续费的时候用
                    'admin_id' => 0,
                    'ymoney'   => $member_info['td_sxf'],   // 旧值
                    'money'    => $order_info['pay_poundage'],
                    "gmoney"   => $member_data['td_sxf'],    // 新值
                    'type'     => 3, // 1增加，2减少，3订单成功消耗
                    'remark'   => '完成订单',  // 备注
                    'ctime'    => time(),
                );
                $res2 = M('TdsxfOrder')->add($arrayRedo);
                if (!$res2 ) {
                    M()->rollback();
                    \Think\Log::record('EditMoney 添加TdsxfOrder-回滚','ERR',true);
                    return false;
                }
            }
            // 如果 风控关了，不要统计风控数据
            try{
                if(!D('UserRiskcontrolConfig')->findConfigInfo($userid)){
                    foreach (['unit_paying_number', 'unit_paying_amount', 'paying_money',] as $key ){
                        unset($member_data[$key]);
                    }
                }
            }catch (Exception $e){
                Log::record("查看风控发生异常 {$e->getMessage()}",'ERR',true);
            }
            $member_result = $m_Member->where(['id' => $userid])->save($member_data);
            if ($member_result != 1) {
                M()->rollback();
                Log::record("EditMoney userid=$userid, error={$m_Member->getError()},dberror={$m_Member->getDbError()} 手续费-回滚:".json_encode($member_data),'ERR',true);
                return false;
            }

            // 商户充值金额变动记录
            $moneychange_data = [
                'userid'     => $userid,                                                                    // 商户编号
                'ymoney'     => $ymoney, //原金额或原冻结资金                                               // 原金额
                'money'      => $actualAmount,                                                              // 变动金额
                'gmoney'     => $gmoney, //改动后的金额或冻结资金                                           // 变动后金额
                'datetime'   => date('Y-m-d H:i:s'),                                               // 修改时间
                'tongdao'    => $order_info['pay_bankcode'],                                                // 支付通道ID
                'transid'    => $trans_id,                                                                  // 交易流水号
                'orderid'    => $order_info['out_trade_id'],                                                // 订单号
                'contentstr' => $order_info['out_trade_id'] . '订单充值,结算方式：t+' . $order_info['t'],   // 备注
                'lx'         => 1,                                                                          // 类型
                't'          => $order_info['t'],                                                           // 结算方式
            ];
            $moneychange_result = $this->MoenyChange($moneychange_data); // 资金变动记录

            if ($moneychange_result == false) {
                M()->rollback();
                \Think\Log::record('EditMoney MoenyChange-回滚','ERR',true);
                return false;
            }

            // 记录投诉保证金
            if (isset($depositAmount) && $depositAmount > 0) {
                $depositResult = M('ComplaintsDeposit')->add([
                    'user_id'       => $userid,                                         // 用户ID
                    'pay_orderid'   => $trans_id,                                       // 系统订单号
                    'out_trade_id'  => $order_info['out_trade_id'],                     // 下游订单号
                    'freeze_money'  => $depositAmount,                                  // 冻结保证金额
                    'unfreeze_time' => time() + $complaintsDepositRule['freeze_time'],  // 计划解冻时间, 系统默认3小时后
                    'status'        => 0,                                               // 解冻状态 0未解冻 1已解冻
                    'create_at'     => time(),
                    'update_at'     => time(),
                ]);
                if ($depositResult == false) {
                    M()->rollback();
                    \Think\Log::record('EditMoney 添加ComplaintsDeposit-回滚','ERR',true);
                    return false;
                }
            }

            $m_ChannelAccount      = M('ChannelAccount');
            $channel_account_where = ['id' => $order_info['account_id']];
            $channel_account_info  = $m_ChannelAccount->where($channel_account_where)->find();
            // 通道ID
            $bianliticheng_data = [
                "userid"  => $userid, // 用户ID
                'from_id' => $channel_account_info['memberid'],
                "transid" => $trans_id, // 订单号
                "money"   => $order_info["pay_amount"], // 金额
                "tongdao" => $order_info['pay_bankcode'],   // pay_product.id
            ];

            $total_tc = $this->bianliticheng($bianliticheng_data); // 提成处理
            if($total_tc === false){
                M()->rollback();
                \Think\Log::record('提成处理-回滚','ERR',true);
                return false;
            }

            if(!$isSelfCouont && $channel_account_info['memberid'] != 0){  // 供号商户提供的账号，要扣押金
                $own_member = $m_Member->where(['id' => $channel_account_info['memberid']])->find();

                // 供码商的分润
                $pay_amount = $order_info['pay_amount'];
                list($pay_profit, $provider_cost) = $this->CalcPayProfit($order_info['t'], $pay_amount, $channel_account_info);
                $limit = $pay_amount - $pay_profit; // 一笔订单占用的额度 = 订单金额 - 供码分润
                if (!$m_Member->where(['id' => $channel_account_info['memberid']])->save(['amount_water'=>['exp', 'amount_water+'.$limit]]) ) {
                    /*M()->rollback();
                    \Think\Log::record('记录已用额度-回滚','ERR',true);
                    return false;*/
                }
                // 加个记录
                // 额度变更记录
                $arrayRedo = array(
                    'user_id'  => $own_member['id'],
                    'orderid'   => $order_info['id'], // 订单表的id，
                    'ymoney'   => $own_member['amount_water'],   // 旧值
                    'money'    => $limit,
                    "gmoney"   => $own_member['amount_water'] + $limit,    // 新值
                    'type'     => 3, // 1增加，2减少，3订单成功增加
                    'remark'   => '完成订单，增加：amount_water',  // 备注
                    'ctime'    => time(),
                );
                $res2 = M('AmountWaterOrder')->add($arrayRedo);
                if (!$res2 ) {
                    M()->rollback();
                    \Think\Log::record('添加额度变更记录-回滚','ERR',true);
                    return false;
                }

                if(!$this->addArrearage($userid, $channel_account_info['memberid'], $order_info['pay_actualamount'])){  // 记录供号商户要交给下单商户的钱
                    M()->rollback();
                    \Think\Log::record('记录供号商户要交给下单商户的钱-回滚','ERR',true);
                    return false;
                }

                // 计算收款账号要返给平台的钱
                //$margins = $order_info['pay_poundage'] - $order_info['cost'] - $total_tc;
                $margins = bcsub($order_info['pay_poundage'], $pay_profit, 4);
                $margins = bcsub($margins, $total_tc, 4);
                //$margins = $order_info['pay_poundage'] - $pay_profit - $total_tc;
                //if(!
                $this->addArrearage(0, $channel_account_info['memberid'], $margins);    // 这里$margins=0会失败，允许为负数，管理员会看到
                /*){  // 记录供号商户要交给平台的钱
                    M()->rollback();
                    \Think\Log::record('记录供号商户要交给平台的钱-回滚','ERR',true);
                    return false;
                }*/
                if(!$m_Order->where(['pay_orderid' => $trans_id])->save(['pay_profit'=>$pay_profit, 'provider_cost'=>$provider_cost, 'margins'=>$margins])){   // 手续费 - 给收款的利润-提成=要交给平台的
                    /*M()->rollback();
                    \Think\Log::record("EditMoney 手续费 - 给收款的利润-提成=要交给平台的-回滚:$pay_amount,{$order_info['pay_poundage']}=$pay_profit+$total_tc+$margins".$m_Order->getError(),'ERR',true);
                    return false;*/
                }
            }
            M()->commit();
            //-----------------------------------------修改用户数据 商户余额、冻结余额end-----------------------------------

            //-----------------------------------------修改通道风控支付数据start----------------------------------------------
            $m_Channel     = M('Channel');
            $channel_where = ['id' => $order_info['channel_id']];
            $channel_info  = $m_Channel->where($channel_where)->find();
            //判断当天交易金额并修改支付状态
            $channel_res = $this->saveOfflineStatus(
                $m_Channel,
                $order_info['channel_id'],
                $order_info['pay_amount'],
                $channel_info
            );
            //-----------------------------------------修改通道风控支付数据end------------------------------------------------

            //-----------------------------------------修改子账号风控支付数据start--------------------------------------------
            // 更新子账号测试状态
            if($order_info["istest"]){
                $m_ChannelAccount->where($channel_account_where)->save(['test_status'=>1]);
                write_account_switch_log($channel_account_where['id'], 2, 7);
            }

            if ($channel_account_info['is_defined'] == 0) { // 是否自定义:1-是,0-否
                //继承自定义风控规则
                $channel_info['paying_money'] = $channel_account_info['paying_money']; //当天已交易金额应该为子账号的交易金额
                $channel_account_info         = $channel_info;
            }

            //判断当天交易金额并修改支付状态
            //$channel_account_res =
                $this->saveOfflineStatus(
                $m_ChannelAccount,
                $order_info['account_id'],
                $order_info['pay_amount'],
                $channel_account_info
            );

            // ($channel_account_info['is_defined'] == 0) 继承$channel_info的时候没有这个字段
            if (isset($channel_account_info['unit_interval']) && $channel_account_info['unit_interval']) {
                \Think\Log::record('EditMoney-debug6','ERR',true);
                $m_ChannelAccount->where([
                    'id' => $order_info['account_id'],
                ])->save([
                    'unit_paying_number' => ['exp', 'unit_paying_number+1'],    // 完成交易次数
                    'unit_paying_amount' => ['exp', 'unit_paying_amount+' . $order_info['pay_actualamount']],// 完成收款金额，订单金额扣取手续费之后的
                ]);
            }
            //-----------------------------------------修改子账号风控支付数据end----------------------------------------------

            // 统计账号成功数
            //if(0 == $order_info['istest'])
                $this->day_account_stat('payed', $order_info);

        }   // if($order_info["pay_status"] == 0)

        //************************************************回调，支付跳转*******************************************//
        $return_array = [ // 返回字段
            "memberid"       => $order_info["pay_memberid"], // 商户ID
            "orderid"        => $order_info['out_trade_id'], // 订单号
            'transaction_id' => $order_info["pay_orderid"], //支付流水号
            "amount"         => $order_info["pay_amount"], // 交易金额
            "datetime"       => date("YmdHis"), // 交易时间
            "returncode"     => "00", // 交易状态
        ];
        if (!isset($member_info)) {
            $member_info = M('Member')->where(['id' => $userid])->find();
        }
        $toSignStr = $this->createToSignStr($member_info['apikey'], $return_array);
        $sign                   = $this->createSign($member_info['apikey'], $return_array);
        $return_array["sign"]   = $sign;
        // 添加RSA数字签名
        $return_array["signRsa"] = SignatureUtil::sign($sign, C('private_key'));

        $return_array["attach"] = $order_info["attach"];    // 原样返回的信息
        switch ($returntype) {
            case '0':
                $notifystr = "";
                foreach ($return_array as $key => $val) {
                    $notifystr = $notifystr . $key . "=" . $val . "&";
                }
                $notifystr = rtrim($notifystr, '&');
                $ch        = curl_init();
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, $order_info["pay_notifyurl"]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $notifystr);
                $contents = curl_exec($ch);
                $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                curl_close($ch);
                log_server_notify($order_info["pay_orderid"], $order_info["pay_notifyurl"], $notifystr.",toSignStr=$toSignStr", $httpCode, $contents);
                $order_where = [
                    'id'          => $order_info['id'],
                    'pay_orderid' => $order_info["pay_orderid"],
                ];
                if (strstr(strtolower($contents), "ok") != false) {
                    //更新交易状态
                    //$order_result =
                        $m_Order->where($order_where)->setField("pay_status", 2);
                        M('Notify')->where(['pay_orderid'=>$trans_id]) ->delete();
                }
                else {
                    if(!M('Notify')->where(['pay_orderid'=>$trans_id])->find() && $order_info['num'] < 60){
                        M('Notify')->add(['pay_orderid'=>$trans_id]);
                    }else{
                        $m_Order->where($order_where)->setField("num", $order_info['num']+1);
                    }
                    // $this->jiankong($order_info['pay_orderid']);
                    \Think\Log::record("给下游回调失败：pay_orderid={$order_info['pay_orderid']},pay_notifyurl={$order_info['pay_notifyurl']},notifystr={$notifystr},httpCode={$httpCode},contents={$contents}",'ERR',true);
                }
                break;

            case '1':
                $this->setHtml($order_info["pay_callbackurl"], $return_array);
                break;

            default:
                # code...
                break;
        }
        return true;
    }

    /// 统计收款账号流水
    /// @param $submitOrPayed submit:下单,payed:支付成功
    /// @param $order_info 订单数据
    private function day_account_stat($submitOrPayed, $order_info){
        $day = (new Date((int)$order_info['pay_applydate']))->getYmd();
        $day = date('Ymd');
        $where = ['date'=>$day, 'account_id'=>$order_info['account_id']];
        $tbl = M('AccountDayStat'); // 统计表
        $day_data = $tbl->where($where)->find();
        if('submit' == $submitOrPayed){ // 下单
            if($day_data)$tbl->where($where)->save(['order_number'=>['exp', 'order_number+1']]);
            else{   // 不存在，表示今天第一单
                $tbl->add(['date'=>$day, 'account_id'=>$order_info['account_id'], 'order_number'=>1]);
            }
            M('ChannelAccount')->where(['id'=>$order_info['account_id']])->save(['fail_times'=>['exp', 'fail_times+1']]);   // 累计失败次数
        }elseif('payed' == $submitOrPayed){ // 成功付款
            if($day_data){
                $ret1 = $tbl->where($where)->save(['payed_number'=>['exp', 'payed_number+1'], 'pay_amount'=>['exp', 'pay_amount+'.$order_info['pay_amount']]]);
                \Think\Log::record("day_account_stat3,ret1={$ret1},day={$day},account_id={$order_info['account_id']},pay_amount={$order_info['pay_amount']}",'ERR',true);
            }else{
                $ret2 = $tbl->add(['date'=>$day, 'account_id'=>$order_info['account_id'], 'order_number'=>1, 'payed_number'=>1, 'pay_amount'=>$order_info['pay_amount']]);
                \Think\Log::record("day_account_stat4,ret2={$ret2},day={$day},account_id={$order_info['account_id']},pay_amount={$order_info['pay_amount']}",'ERR',true);
            }
            M('ChannelAccount')->where(['id'=>$order_info['account_id']])->save(['fail_times'=>0]);   // 清零失败次数
        }
    }

    //修改渠道跟账号风控状态
    protected function saveOfflineStatus($model, $id, $pay_amount, $info)
    {
        if ($info['offline_status'] && $info['control_status'] && $info['all_money'] > 0) {
            //通道是否开启风控和支付状态为上线
            $data['paying_money']     = bcadd($info['paying_money'], $pay_amount, 4);
            $data['last_paying_time'] = time();

            if ($data['paying_money'] >= $info['all_money']) {
                $data['offline_status'] = 0;
            }
            return $model->where(['id' => $id])->save($data);
        }
        return true;
    }

    /**
     *  验证签名
     * @return bool
     */
    protected function verify()
    {
        //POST参数
        if (I('request.is_juhe', 0, 'intval') == 1) {
            $requestarray = array(
                'pay_memberid'    => I('request.pay_memberid', 0, 'intval'),
                'pay_orderid'     => I('request.pay_orderid', ''),
                'pay_amount'      => I('request.pay_amount', ''),
                'pay_applydate'   => I('request.pay_applydate', ''),
     //           'pay_bankcode'    => I('request.pay_bankcode', ''),
                'pay_notifyurl'   => I('request.pay_notifyurl', ''),
                'pay_callbackurl' => I('request.pay_callbackurl', ''),
            );
        } else {
            $requestarray = array(
                'pay_memberid'    => I('request.pay_memberid', 0, 'intval'),
                'pay_orderid'     => I('request.pay_orderid', ''),
                'pay_amount'      => I('request.pay_amount', ''),
                'pay_applydate'   => I('request.pay_applydate', ''),
                'pay_bankcode'    => I('request.pay_bankcode', ''),
                'pay_notifyurl'   => I('request.pay_notifyurl', ''),
                'pay_callbackurl' => I('request.pay_callbackurl', ''),
            );
        }
		
        $md5key        = $this->merchants['apikey'];
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.pay_md5sign');

        if ($pay_md5sign == $md5keysignstr) {
            return true;
        } else {
            ksort($requestarray);
            $md5str = "";
            foreach ($requestarray as $key => $val) {
                if (!empty($val)) {
                    $md5str = $md5str . $key . "=" . $val . "&";
                }
            }

            file_put_contents("Data/pdd_sign_failed.txt","pay_md5sign=$pay_md5sign, != md5keysignstr=$md5keysignstr, request=".json_encode($_REQUEST).",str=".$md5str . "key=" . $md5key."\n", FILE_APPEND);
            return false;
        }
    }

    /// 自动post一个表单
    public function setHtml($tjurl, $arraystr)
    {
        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';
        foreach ($arraystr as $key => $val) {
            $str .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $str .= '</form>';
        $str .= '<script>';
        $str .= 'document.Form1.submit();';
        $str .= '</script>';
        exit($str);
    }

    /// 没用到？
    //public function jiankong($orderid)
    public function jiankong()
    {
        ignore_user_abort(true);
        set_time_limit(3600);
        $Order    = M("Order");
        $interval = 10*60;
        do {
            /*if ($orderid) {
                $_where['pay_status']  = 1;
                $_where['num']         = array('lt', 3);
                $_where['pay_orderid'] = $orderid;
                $finds                  = [$Order->where($_where)->find()];
            } else {
                $finds = $Order->where("pay_status = 1 and num < 30")->limit(10)->select();
            }*/
            $finds = $Order->where("pay_status = 1 and num < 30")->limit(10)->order('id desc')->select();
            foreach (($finds ?: []) as $find) {
                if(!$find)continue;

                $this->EditMoney($find["pay_orderid"], $find["pay_tongdao"], 0);
                $Order->where(["id" => $find["id"]])->save(['num' => ['exp', 'num+1']]);
            }

            sleep($interval);
        } while (true);
    }

    /**
     * 资金变动记录
     * @param $arrayField
     * @return bool
     */
    protected function MoenyChange($arrayField)
    {
        // 资金变动
        $Moneychange = M("Moneychange");
        $data = [];
        foreach ($arrayField as $key => $val) {
            $data[$key] = $val;
        }
        $result = $Moneychange->add($data);
        return $result ? true : false;
    }

    /**
     * 佣金处理
     * @param $arrayStr
     * @param int $num
     * @param int $tcjb 提成级别
     * @return bool| number 需要多少提成
     */
    private function bianliticheng($arrayStr, $num = 3, $tcjb = 1)
    {
        $total_tc = 0; // 一共给多少提成
        do {
            if ($num <= 0) {
                break;
            }
            $userid = $arrayStr["userid"];
            $tongdaoid = $arrayStr["tongdao"];
            $trans_id = $arrayStr["transid"];
            $feilvfind = $this->huoqufeilv($userid, $tongdaoid, $trans_id); // 用户应用的费率

            if ($feilvfind["status"] == "error") {
                return false;
            }

            //商户费率（下级）
            $x_feilv = $feilvfind["feilv"];
            //$x_fengding = $feilvfind["fengding"];

            //代理商(上级)
            $parentid = M("Member")->where(["id" => $userid])->getField("parentid");
            if ($parentid <= 1) {
                break;  // 顶层了，不用再算了
            }
            $parentRate = $this->huoqufeilv($parentid, $tongdaoid, $trans_id);  // 上级应用的费率

            if ($parentRate["status"] == "error") {
                return false;
            }

            //代理商(上级）费率
            $s_feilv = $parentRate["feilv"];
            //$s_fengding = $parentRate["fengding"];

            //费率差
            $ratediff = (($x_feilv * 1000) - ($s_feilv * 1000)) / 1000;
            /*if ($ratediff <= 0) {
                \Think\Log::record("bianliticheng：费率差({$x_feilv}<={$s_feilv})-回滚",'ERR',true);
                break;  // return false 会不计码商成本
                //return false;
            }*/
            $parent = M('Member')->where(['id' => $parentid])->field('id,balance')->find();
            if (empty($parent)) {
                \Think\Log::record('bianliticheng：没找到上级-回滚:'.$parentid.$userid,'ERR',true);
                return false;
            }
            //代理佣金
            $brokerage = $arrayStr['money'] * $ratediff;
            $total_tc += $brokerage;
            $rows = [
                'balance' => array('exp', "balance+{$brokerage}"),
            ];
            M('Member')->where(['id' => $parentid])->save($rows);

            //代理商资金变动记录
            $arrayField = array(
                "userid" => $parentid,
                "ymoney" => $parent['balance'],
                "money" => $brokerage,
                "gmoney" => $parent['balance'] + $brokerage,
                "datetime" => date("Y-m-d H:i:s"),
                "tongdao" => $tongdaoid,
                "transid" => $arrayStr["transid"],
                "orderid" => "tx" . date("YmdHis"),
                "tcuserid" => $userid,
                "tcdengji" => $tcjb,
                "lx" => 9,    // 提成
            );
            $this->MoenyChange($arrayField); // 资金变动记录
            if(!$this->addArrearage($parentid, $arrayStr['from_id'], $brokerage)){  // 记录供号商户要交给推广的提出
                \Think\Log::record('bianliticheng：记录供号商户要交给推广的提出-回滚','ERR',true);
                return false;
            }
            // 分佣，写入订单表
            if(!M('Order')->where(['pay_orderid' => $trans_id])->save(['brokerage'.$tcjb =>$brokerage, ])){   // 分佣
                \Think\Log::record("bianliticheng：分佣-回滚 \$brokerage=$brokerage",'ERR',true);
                return false;
            }
            $num = $num - 1;
            $tcjb = $tcjb + 1;
            $arrayStr["userid"] = $parentid;
            $next_tc = $this->bianliticheng($arrayStr, $num, $tcjb);
            if($next_tc === false){
                \Think\Log::record('bianliticheng：递归-回滚','ERR',true);
                return false;
            }
            $total_tc += $next_tc;
        }while(0);
        return $total_tc;
    }

    // 读 费率
    /// 如果设置了用户费率，则使用用户费率，否则使用通道费率
    /// @param $payapiid pay_channel.id
    /// @param $trans_id 订单号
    private function huoqufeilv($userid, $payapiid, $trans_id)
    {
        $return = array();
        $order  = M('Order')->where(['pay_orderid' => $trans_id])->find();
        //用户费率
        $userrate = M("Userrate")->where(["userid" => $userid, "payapiid" => $payapiid])->find();
        //支付通道费率
        $syschannel = M('Channel')->where(['id' => $payapiid])->find();

        $todaydate = time();
        $todayhour = date("H", $todaydate);
        if ($todayhour < 9) {
            $_userrate['t0feilv']    = max($_userrate['t0feilv_night'], $_userrate['t0feilv']);
            $_userrate['t0fengding'] = max($_userrate['t0fengding_night'], $_userrate['t0fengding']);
        }

        if ($order['t'] == 0) { //T+0费率
            $feilv    = $userrate['t0feilv'] ? $userrate['t0feilv'] : $syschannel['t0defaultrate']; // 交易费率
            $fengding = $userrate['t0fengding'] ? $userrate['t0fengding'] : $syschannel['t0fengding']; // 封顶手续费
        } else { //T+1费率
            $feilv    = $userrate['feilv'] ? $userrate['feilv'] : $syschannel['defaultrate']; // 交易费率
            $fengding = $userrate['fengding'] ? $userrate['fengding'] : $syschannel['fengding']; // 封顶手续费
        }
        $return["status"]   = "ok";
        $return["feilv"]    = $feilv;
        $return["fengding"] = $fengding;
        return $return;
    }

    protected function createToSignStr($Md5key, $list){
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        return $md5str . "key=" . $Md5key;
    }
    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
    protected function createSign($Md5key, $list)
    {
        $sign = strtoupper(md5($this->createToSignStr($Md5key, $list)));
        return $sign;
    }

    /// 补发
    public function bufa()
    {
        header('Content-type:text/html;charset=utf-8');
        $TransID    = I("get.TransID");
        $PayName    = I("get.tongdao");
        $m          = M("Order");
        $pay_status = $m->where(array("pay_orderid" => $TransID))->getField("pay_status");
        if (intval($pay_status) >= 1) {
            echo ("订单号：" . $TransID . "|" . $PayName . "已补发服务器点对点通知，请稍后刷新查看结果！<a href='javascript:window.close();'>关闭</a>");
            $this->EditMoney($TransID, $PayName, 0);
        } else {
            echo "补发失败";
        }
    }

    /**
     * 扫码订单状态检查
     *
     */
    public function checkstatus()
    {
        $orderid = I("post.orderid");
        $Order   = M("Order");
        $order   = $Order->where(array('pay_orderid' => $orderid))->find();
        if ($order['pay_status'] != 0) {
            echo json_encode(array('status' => 'ok', 'callback' => $this->_site . "Pay_" . $order['pay_tongdao'] . "_callbackurl.html?orderid="
                . $orderid . "&pay_memberid=" . $order['pay_memberid'] . '&bankcode=' . $order['pay_bankcode']));
            exit();
        } else {
            exit("no-$orderid");
        }
    }

    /// 浏览器每秒检查一次订单是否已经完成支付
    public function checkorder()
    {
        $orderid = I("post.orderid");
        $Order   = M("Order");
        $order   = $Order->where(array('pay_orderid' => $orderid))->find();
        if ($order['pay_status'] != 0) {
            echo json_encode(array('state' => 1, 'callback' => $this->_site . "Pay_" . $order['pay_tongdao'] . "_paysuccess.html?orderid="
                . $orderid . "&pay_memberid=" . $order['pay_memberid'] . '&bankcode=' . $order['pay_bankcode']));die;
            //echo json_encode(array('state' => 1, 'callback' => $order['pay_callbackurl'])); die;
        } else {
            exit("no-$orderid");
        }
    }

    /**
     * 错误返回
     * @param string $msg
     * @param array $fields
     */
    protected function showmessage($msg = '', $fields = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('status' => 'error', 'msg' => $msg, 'data' => $fields);
        echo json_encode($data, 320);
        exit;
    }

    /**
     * 来路域名检查
     * @param $pay_memberid
     */
    protected function domaincheck($pay_memberid)
    {
        //$referer      = $_SERVER["HTTP_REFERER"]; // 获取完整的来路URL
        $domain       = $_SERVER['HTTP_HOST'];
        $pay_memberid = intval($pay_memberid) - 10000;
        $User         = M("User");
        $num          = $User->where(["id" => $pay_memberid])->count();
        if ($num <= 0) {
            $this->showmessage("商户编号不存在");
        } else {
            $websiteid     = $User->where(["id" => $pay_memberid])->getField("websiteid");
            $Websiteconfig = M("Websiteconfig");
            $websitedomain = $Websiteconfig->where(["websiteid" => $websiteid])->getField("domain");

            if ($websitedomain != $domain) {
                $Userverifyinfo = M("Userverifyinfo");
                $domains        = $Userverifyinfo->where(["userid" => $pay_memberid])->getField("domain");
                if (!$domains) {
                    $this->showmessage("域名错误 ");
                } else {
                    $arraydomain = explode("|", $domains);
                    $checktrue   = true;
                    foreach ($arraydomain as $key => $val) {
                        if ($val == $domain) {
                            $checktrue = false;
                            break;
                        }
                    }
                    if ($checktrue) {
                        $this->showmessage("域名错误 ");
                    }
                }
            }
        }
    }

    protected function getParameter($title, $channel, $className, $exchange = 1)
    {
        if (substr_count($className, 'Controller')) {
            $length = strlen($className) - 25;
            $code   = substr($className, 15, $length);
        }
        $parameter = array(
            'code'         => $code, // 通道名称
            'title'        => $title, //通道名称
            'exchange'     => $exchange, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => I('request.pay_orderid', ''), //外部订单号
            'channel'      => $channel,
            'body'         => I('request.pay_productname', ''),
        );
        $return = $this->orderadd($parameter);
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);

        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"]     = $this->_site . 'Pay_' . $code . '_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_' . $code . '_callbackurl.html';
        return $return;
    }

    /// 生成二维码图片，并在前端显示
    protected function showQRcode($url, $return, $view = 'weixin')
    {
        import("Vendor.phpqrcode.phpqrcode", '', ".php");
        $QR = "Uploads/codepay/" . $return["orderid"] . ".png"; //已经生成的原始二维码图
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", $this->_site . $QR);
        $this->assign('params', $return);
        $this->assign('orderid', $return['orderid']);
        $this->assign('money', $return['amount']);
        $this->display("WeiXin/" . $view);
    }

    /**
     * 获取投诉保证金金额
     * @param $userid
     * @return array
     */
    private function getComplaintsDepositRule($userid)
    {
        $complaintsDepositRule = M('ComplaintsDepositRule')->where(['user_id' => $userid])->find();
        if (!$complaintsDepositRule || $complaintsDepositRule['status'] != 1) {     // 找不到或者不为1都采用系统的规则
            $complaintsDepositRule = M('ComplaintsDepositRule')->where(['is_system' => 1])->find();
        }
        return $complaintsDepositRule ? $complaintsDepositRule : [];
    }

    /// 增加供号商户的欠费
    private function addArrearage($user_id, $from_id, $delta){
        $arrearage = M('Arrearage');
        $item = $arrearage->where(['user_id'=>$user_id, 'from_id'=>$from_id])->find();
        if($item) return $arrearage->where(['user_id'=>$user_id, 'from_id'=>$from_id])->limit(1)->save(['balance'=>['exp', 'balance+'.$delta]]);  // 更新
        else return $arrearage->add(['user_id'=>$user_id, 'from_id'=>$from_id, 'balance'=>$delta]); // 新增
    }

    // 计算供码商的分润
    /// @param $t1zt 0：T0，还是1：T1
    /// @param $pay_amount 订单金额
    /// @param $channel_account 收款账号pay_channel_account行
    private function CalcPayProfit($t1zt, $pay_amount, $channel_account){
        //银行通道费率
        $syschannel = M('Channel')
            ->where(['id' => $channel_account['channel_id']])    // pay_channel.id
            ->find();
        // 收款账号定制费率
        if ($channel_account['custom_rate']) {  // 使用账号自定义费率
            $syschannel['t0defaultrate'] = $channel_account['t0defaultrate'];   // 运营费率
            $syschannel['t0fengding']    = $channel_account['t0fengding'];  // 封顶？
            $syschannel['t0rate']        = $channel_account['t0rate'];  // 计算成本
            $syschannel['defaultrate'] = $channel_account['defaultrate'];   // 运营费率
            $syschannel['fengding']    = $channel_account['fengding'];  // 封顶？
            $syschannel['rate']        = $channel_account['rate'];  // 计算成本
        }
        if ($t1zt == 0) { //T+0费率
            $feilv    = $syschannel['t0defaultrate']; // 交易费率
            $fengding = $syschannel['t0fengding']; // 封顶手续费
            $rate = $syschannel['t0rate']; // 运营费率
        } else { //T+1费率
            $feilv    = $syschannel['defaultrate']; // 交易费率
            $fengding = $syschannel['fengding']; // 封顶手续费
            $rate = $syschannel['rate']; // 运营费率
        }
        $fengding = $fengding == 0 ? 9999999 : $fengding; //如果没有设置封顶手续费自动设置为一个足够大的数字
        //$pay_amount = $order_info["pay_amount"];    // 订单金额
        $pay_profit    = (($pay_amount * $feilv) > ($pay_amount * $fengding)) ? ($pay_amount * $fengding) :
            ($pay_amount * $feilv); // 供码分润
        $provider_cost = $pay_amount * $rate;
        return [$pay_profit, $provider_cost];
    }

    protected function amountRedis($id,$amount,$amountTrue,$dec = 1){
        $this->amount = $amountTrue;
        if($dec >20 ) {
            $this->amount = false;
        }
        if(empty($this->amount)) return false;
        $moneyCheck = new MoneyCheck();
        $keyValueJson = $moneyCheck->checkAccountMoney($id,$amountTrue);
        //验证金额的值是否存在
        if($keyValueJson){
            //不存在直接写入redis 记录值，返回实际金额  金额向下浮动
            $moneyCheck->setAccountKey($id,$amountTrue);
            $this->amount  = $amountTrue;
            //echo $amountTrue;die();
            return  $this->amount;
        }
        //金额减 0.01
        $amountTrue = floatval($amountTrue)-0.01;
        $dec+=1;
        //回调
        $this->amountRedis($id,$amount,$amountTrue,$dec);
        return  $this->amount;
    }

    static function CheckSubstrs($substrs,$text){
        foreach($substrs as $substr)
            if(false!==strpos($text,$substr)){
                return true;
            }
        return false;
    }

    //检测是否手机访问
    static public function isMobile(){
        $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';
        $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
        $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');

        $found_mobile= self::CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||
            self::CheckSubstrs($mobile_token_list,$useragent);

        if ($found_mobile){
            return true;
        }else{
            return false;
        }
    }

    public function isInAlipayClient()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            return true;
        }
        return false;
    }

    public function isInWeixinClient()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

    public function paysuccess(){
        $orderid = I('orderid');
        $Order      = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        if ($pay_status != 0) { // 已支付
            $this->EditMoney($orderid, '', 1);  // 发同步通知
        } else {
            exit("error: unpaied!");
        }
    }

    public function bufaAll(){
        $PayName    = 'index/bufa';
        $m          = M("Order");
        $list = $m->where(['pay_status'=>1, ])->limit(30)->select();
        foreach ($list as $order){
            $TransID =  $order['pay_orderid'];
            if($this->EditMoney($TransID, $PayName, 0))
                echo ("订单号：" . $TransID. "|" . $PayName . "已补发服务器点对点通知，请稍后刷新查看结果！<br>");
            else echo("不发订单失败：{$TransID}<br>");
        }
    }

    protected function post($url,$parac){
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

    /// 获取当前的毫秒时间戳
    public function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }

    public function fuck(){
        $sign = 'BA509E017CDBC0B2394E70A8089C1126';
        $signRsa = SignatureUtil::sign($sign, C('private_key'));
        echo $sign.'<br>';
        if(SignatureUtil::verify($sign, $signRsa, C('public_key')) ) {
            echo '验签通过';
        }else echo '验签失败';
    }

    /**
     * 解析url中参数信息，返回参数数组
     */
    protected function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);

        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }

        return $params;
    }
}
