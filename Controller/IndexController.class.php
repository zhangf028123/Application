<?php
namespace Pay\Controller;

/// 支付入口点
class IndexController extends PayController
{
    protected $channel; // ProductUser 行

    protected $memberid; //商户ID pay_member.id

    protected $pay_amount; //交易金额

    protected $bankcode; //银行码 pay_product_user.pid

    protected $orderid; //订单号,下游订单号

    public function __construct()
    {
        parent::__construct();
        if (empty($_POST)) {
            $this->showmessage('no data!$_POST is empty.');
        }

        \Think\Log::record("index-post=".json_encode($_POST),'ERR',true);
        $this->firstCheckParams(); //初步验证参数 ，设置memberid，pay_amount，bankcode属性

        $this->judgeRepeatOrder(); //验证是否可以提交重复订单

        $this->userRiskcontrol(); //用户风控检测

        $this->productIsOpen(); //判断通道是否开启

        $this->setChannelApiControl(); //判断是否开启支付渠道 ，获取并设置支付通api的id和通道风控

        $this->channel['istest'] = I('request.test') ?: 0;
        if(I('request.test')){ // 测试
            $this->channel['api'] = I('request.channel_id');
            $this->channel['channel_id'] = I('request.channel_id');
            $this->channel['channel_account_id'] = I('request.channel_account_id');
        }
		
        if(I('request.pay_bankcode') == 900){
            $this->channel['api'] = 200;
        }		
    }

    /// 申请支付的入口
    public function index()
    {
        //进入支付
        if ($this->channel['api']) {    // pay_product_user.channel = pay_channel.id
            $info = M('Channel')->where(['id' => $this->channel['api'], 'status' => 1])->find();    // 选一个channel

            //是否存在通道文件
            if (!is_file(APP_PATH . '/' . MODULE_NAME . '/Controller/' . $info['code'] . 'Controller.class.php')) {
                $this->showmessage('支付通道Controller不存在', ['code'=>$info['code'], 'pay_bankcode' => $this->channel['api']]);
            }

            // 调用具体的支付控制器的Pay函数
            if (R($info['code'] . '/Pay', [$this->channel]) === false) {
                $this->showmessage('服务器维护中,请稍后再试...');
            }
        } else {
            \Think\Log::record("抱歉.网络异常暂时无法完成您的请求{$this->channel['api']} 为空！post=".json_encode($_POST),'ERR',true);
            $this->showmessage("抱歉..网络异常暂时无法完成您的请求");
        }
    }

    //======================================辅助方法===================================

    /**
     * [初步判断提交的参数是否合法并设置为属性]
     */
    protected function firstCheckParams()
    {
        $this->memberid = I("request.pay_memberid", 0, 'intval') - 10000;
		
        // 商户编号不能为空
        if (empty($this->memberid) || $this->memberid <= 0) {
            $msg = "不存在的商户编号!".$this->memberid.', requestdata='.json_encode($_POST);
            file_put_contents("Data/firstCheckParamsfailed.txt",$msg."\n", FILE_APPEND);
            $this->showmessage($msg);
        }

        $this->pay_amount = I('post.pay_amount', 0);
        if ($this->pay_amount == 0) {
            $this->showmessage('金额不能为空');
        }

        //银行编码
        $this->bankcode = I('request.pay_bankcode', 0, 'intval');   // bankcode就是 pay_product.id
        if ($this->bankcode == 0) {
            $this->showmessage('不存在的银行编码!', ['pay_banckcode' => $this->bankcode]);
        }

        $this->orderid = I('post.pay_orderid', '');
        if (!$this->orderid) {
            $this->showmessage('订单号不合法！');
        }
        // 商户是否禁用
        $member = M('Member')->where(['id'=>$this->memberid])->find();
        if(!$member){
            $this->showmessage('商户不存在！');
        }
        if(!$member['status']){
            $this->showmessage('商户已禁用！');
        }
        if(!$member['open_charge']){
            $this->showmessage('商户无充值功能！');
        }
        /// 必选参数
        foreach(['pay_memberid', 'pay_orderid', 'pay_applydate', 'pay_bankcode', 'pay_notifyurl', 'pay_callbackurl', 'pay_amount', 'pay_md5sign', 'pay_productname', ] as $key){
            if(empty(I("request.".$key))){
                \Think\Log::record("firstCheckParams 必选参数`$key`为空！post=".json_encode($_POST),'ERR',true);
                $this->showmessage('必选参数`'.$key.'`为空！');
            }
        }
    }

    /**
     * [用户风控]
     */
    protected function userRiskcontrol()
    {
        $l_UserRiskcontrol = new \Pay\Logic\UserRiskcontrolLogic($this->pay_amount, $this->memberid); //用户风控类
        $error_msg         = $l_UserRiskcontrol->monitoringData();
        if ($error_msg !== true) {
            $this->showmessage('商户：' . $error_msg);
        }
    }

    /**
     * [productIsOpen 判断通道是否开启，并分配]
     * @return [type] [description]
     */
    protected function productIsOpen()
    {
        $count = M('Product')->where(['id' => $this->bankcode, 'status' => 1])->count();
        //通道关闭
        if (!$count) {
            $this->showmessage('暂时无法连接支付服务器1!'.$this->bankcode);
        }
        $this->channel = M('ProductUser')->where(['pid' => $this->bankcode, 'userid' => $this->memberid, 'status' => 1])->find();
	
        //用户未分配
        if (!$this->channel) {
            $this->showmessage('暂时无法连接支付服务器2!'.json_encode(['pid' => $this->bankcode, 'userid' => $this->memberid, 'status' => 1]));
        }
    }

    /**
     * [判断是否开启支付渠道 ，获取并设置支付通api的id---->轮询+风控]
     */
    protected function setChannelApiControl()
    {
        $l_ChannelRiskcontrol = new \Pay\Logic\ChannelRiskcontrolLogic($this->pay_amount); //支付渠道风控类
        $m_Channel            = M('Channel');

        // $this->channel['api'] = pay_channel.id
        if ($this->channel['polling'] == 1 && $this->channel['weight']) {
            /***********************多渠道,轮询，权重随机*********************/
            $weight_item  = [];
            $error_msg    = '该通道维护中，请稍后再试';
            $temp_weights = explode('|', $this->channel['weight']); // channelid1:weight1|channelid2:weight2
            foreach ($temp_weights as $k => $v) {
                list($pid, $weight) = explode(':', $v);
                //检查是否开通
                $temp_info = $m_Channel->where(['id' => $pid, 'status' => 1])->find();

                //判断通道是否开启风控并上线
                if ($temp_info['offline_status'] == 1 && $temp_info['control_status'] == 1) {
                    //-------------------------进行风控-----------------
                    $l_ChannelRiskcontrol->setConfigInfo($temp_info); //设置配置属性
                    $error_msg = $l_ChannelRiskcontrol->monitoringData();
                    if ($error_msg === true) {
                        $weight_item[] = ['pid' => $pid, 'weight' => $weight];
                    }
                } else if ($temp_info['control_status'] == 0) {
                    $weight_item[] = ['pid' => $pid, 'weight' => $weight];
                }
            }

            //如果所有通道风控，提示最后一个消息
            if ($weight_item == []) {
                $this->showmessage('通道:' . $error_msg);
            }
            $weight_item          = getWeight($weight_item);
            $this->channel['api'] = $weight_item['pid'];    // pay_channel.id
        } else {
            /***********************单渠道,没有轮询*********************/
            //查询通道信息
            $pid          = $this->channel['channel'];  // pay_product_user.channel
            $channel_info = $m_Channel->where(['id' => $pid])->find();
            \Think\Log::record('testing.'.$this->channel['id'].json_encode($channel_info), 'ERR', true);

            //通道风控
            $l_ChannelRiskcontrol->setConfigInfo($channel_info); //设置配置属性
            $error_msg = $l_ChannelRiskcontrol->monitoringData();
            if ($error_msg !== true) {
                $this->showmessage('通道:' . $error_msg);
            }
            $this->channel['api'] = $pid;   //  pay_product_user.channel = pay_channel.id
        }
    }

    /**
     * 判断是否可以重复提交订单
     * @return [type] [description]
     */
    public function judgeRepeatOrder()
    {
        $is_repeat_order = M('Websiteconfig')->getField('is_repeat_order');
        if (!$is_repeat_order) {
            //不允许同一个用户提交重复订单
            $pay_memberid = $this->memberid + 10000;
            $count = M('Order')->where(['pay_memberid' => $pay_memberid, 'out_trade_id' => $this->orderid])->count();
            if($count){
                $this->showmessage('重复订单！请尝试重新提交订单');
            }
        }
    }
}
