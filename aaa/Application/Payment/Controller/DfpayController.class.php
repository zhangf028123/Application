<?php
/*
 * 代付API， 普通商户发来
 */
namespace Payment\Controller;

use Common\Traits;
use Think\Controller;

class DfpayController extends Controller
{
    use Traits\Withdrawal;

    //商家信息
    protected $merchants;
    //网站地址
    protected $_site;
    //通道信息
    protected $channel;

    public function __construct()
    {
        parent::__construct();
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
    }

    /**
     * 创建代付申请
     * @param $parameter
     * @return array
     */
    public function add()
    {
        if (empty($_POST)) {
            $this->showmessage('no data!');
        }
        $sign = I('request.pay_md5sign');
        if(!$sign) {
            $this->showmessage("缺少签名参数");
        }

        $mchid = I("post.mchid", 0);
        if(!$mchid) {
            $this->showmessage('商户ID不能为空！');
        }
        $user_id =  $mchid - 10000;
        $siteconfig = M("Websiteconfig")->find();
        if(!$siteconfig['df_api']) {
            $this->showmessage('系统：代付API未开启！');
        }
        //用户信息
        $this->merchants = D('Member')->where(array('id'=>$user_id))->find();
        if(empty($this->merchants)) {
            $this->showmessage('商户不存在！'.$user_id);
        }
        if(!$this->merchants['df_api']) {
            $this->showmessage('商户未开启此功能！');
        }
        if (!$this->merchants['df_domain'] && !$this->merchants['df_ip']){
            //$this->showmessage('必须设置代付报备域名或报备ip！'); // 后台编辑用户信息
        }
        if($this->merchants['df_domain'] != '') {
            $referer = getHttpReferer();
            if(!checkDfDomain($referer, $this->merchants['df_domain'])) {
                $this->showmessage('请求来源域名与报备域名不一致！');
            }
        }
        if($this->merchants['df_ip'] != '' && !checkDfIp($this->merchants['df_ip'])) {
            $this->showmessage('IP地址与报备IP不一致！');
        }
        //判断是否设置了节假日不能提现
        $tkHolidayList = M('Tikuanholiday')->limit(366)->getField('datetime', true);
        if ($tkHolidayList) {
            $today = date('Ymd');
            foreach ($tkHolidayList as $k => $v) {
                if ($today == date('Ymd', $v)) {
                    $this->showmessage('节假日暂时无法提款！');
                }
            }
        }
        //结算方式：
        $Tikuanconfig = M('Tikuanconfig');

        $defaultConfig = $Tikuanconfig->where(['issystem' => 1, 'tkzt' => 1])->find();

        //判断是否开启提款设置
        if (!$defaultConfig) {
            $this->showmessage('提款已关闭！');
        }

        $tkConfig     = $Tikuanconfig->where(['userid' => $user_id, 'tkzt' => 1])->find();

        //判断是否设置个人规则
        if (!$tkConfig || $tkConfig['tkzt'] != 1 || $tkConfig['systemxz'] != 1) {
            $tkConfig = $defaultConfig;
        } else {
            //个人规则，但是提现时间规则要按照系统规则
            $tkConfig['allowstart'] = $defaultConfig['allowstart'];
            $tkConfig['allowend']   = $defaultConfig['allowend'];
        }
        //是否在许可的提现时间
        $hour = date('H');
        //判断提现时间是否合法
        if ($tkConfig['allowend'] != 0) {
            if ($tkConfig['allowstart'] > $hour || $tkConfig['allowend'] <= $hour) {
                $this->showmessage('不在提现时间，请换个时间再来!');
            }
        }

        $money = I("post.money", 0);
        if($money<=0) {
            $this->showmessage('金额错误！');
        }
        //单笔最小提款金额
        if ($tkConfig['tkzxmoney'] > $money) {
            $this->showmessage('单笔最低提款额度：' . $tkConfig['tkzxmoney']);
        }
        //单笔最大提款金额
        if ($tkConfig['tkzdmoney'] < $money) {
            $this->showmessage('单笔最大提款额度：' . $tkConfig['tkzdmoney']);
        }
        $bankname = I("post.bankname", '', 'trim');
        if(!$bankname) {
            $this->showmessage('银行名称不能为空！');
        }
        $subbranch = I("post.subbranch", '', 'trim');
        if(!$subbranch) {
            $this->showmessage('支行名称不能为空');
        }
        $accountname = I("post.accountname", '', 'trim');
        if(!$accountname) {
            $this->showmessage('开户名不能为空！');
        }
        $cardnumber = I("post.cardnumber", '', 'trim');
        if(!$cardnumber) {
            $this->showmessage('银行卡号不能为空！');
        }
        $province = I("post.province", '', 'trim');
        if(!$province) {
            $this->showmessage('省份不能为空！');
        }
        $city = I("post.city", '', 'trim');
        if(!$city) {
            $this->showmessage('城市不能为空！');
        }
        $out_trade_no = I("post.out_trade_no", '', 'trim');
        if(!$out_trade_no) {
            $this->showmessage('订单号不能为空！');
        }
        $Order = M("df_api_order");
        $count = $Order->where(['out_trade_no'=>$out_trade_no, 'userid'=>$user_id])->count();
        if($count>0) {
            $this->showmessage('存在重复订单号！');
        }
        //$notifyurl = I("post.notifyurl", '');
        $extends = I("post.extends", '');
        //当前可用代付渠道
        $channel_ids = M('pay_for_another')->where(['status' => 1])->getField('id', true);
        if($channel_ids) {
            //获取渠道扩展字段
            $fields = M('pay_channel_extend_fields')->where(['channel_id'=>['in',$channel_ids]])->select();
            if(!empty($fields)) {
                if(!$extends) {
                    $this->showmessage('扩展字段不能为空！');
                }
                $extend_fields_array = json_decode(base64_decode($extends), true);
                foreach($fields as $k => $v) {
                    if(!isset($extend_fields_array[$v['name']]) || $extend_fields_array[$v['name']]=='') {
                        $this->showmessage('扩展字段【'.$v['alias'].'】不能为空！');
                    }
                }
            }
        }
        //验签
        if ($this->verify($_POST)) {
            M()->startTrans();
            $data['userid']        = $user_id;
            $data['trade_no']      = $this->getOrderId();
            $data['out_trade_no']  = $out_trade_no;
            $data['money']         = $money;
            $data['bankname']      = $bankname;
            $data['subbranch']     = $subbranch;
            $data['accountname']   = $accountname;
            $data['cardnumber']    = $cardnumber;
            $data['province']      = $province;
            $data['city']          = $city;
            $data['ip']            = get_client_ip();
            $data['check_status']  = 0;
            $data['extends']       = base64_decode($extends);
            //$data['notifyurl']     = $notifyurl;
            $data['create_time'] = time();
            //添加订单
            $res = $Order->add($data);
            if ($res) {
                if($this->merchants['df_auto_check']) { // 自动审核
                    $result = $this->dfPass($data, $res);
                    if($result['status'] == 0) {
                        M()->rollback();
                        $this->showmessage($result['msg']);
                    } else {
                        M()->commit();
                    }
                } else {
                    M()->commit();
                }
                header('Content-Type:application/json; charset=utf-8');
                $data = array('status' => 'success', 'msg' => '代付申请成功', 'transaction_id'=>$data['trade_no']);
                echo json_encode($data);
                exit;
            } else {
                $this->showmessage('系统错误');
            }
        } else {
            $this->showmessage('签名验证失败', $_POST);
        }
    }

    //代付查询
    public function query()
    {
        $out_trade_no = I('request.out_trade_no', '', 'trim');
        $sign = I('request.pay_md5sign');
        if(!$sign) {
            $this->showmessage("缺少签名参数");
        }
        if(!$out_trade_no){
            $this->showmessage("缺少订单号");
        }
        $mchid = I("request.mchid");
        if(!$mchid) {
            $this->showmessage("缺少商户号");
        }
        $user_id = $mchid - 10000;
        //用户信息
        $this->merchants = D('Member')->where(array('id'=>$user_id))->find();
        if(empty($this->merchants)) {
            $this->showmessage('商户不存在！');
        }
        if(!$this->merchants['df_api']) {
            $this->showmessage('商户未开启此功能！');
        }
        if (!$this->merchants['df_domain'] && !$this->merchants['df_ip']){
            $this->showmessage('必须设置代付报备域名或报备ip！'); // 后台编辑用户信息
        }
        if($this->merchants['df_domain'] != '') {
            $referer = getHttpReferer();
            if(!checkDfDomain($referer, $this->merchants['df_domain'])) {
                $this->showmessage('请求来源域名与报备域名不一致！');
            }
        }
        if($this->merchants['df_ip'] != '' && !checkDfIp($this->merchants['df_ip'])) {
            $this->showmessage('IP地址与报备IP不一致！');
        }
        $request = [
            'mchid'=>$mchid,
            'out_trade_no'=>$out_trade_no
        ];

        $signature = $this->createSign($this->merchants['apikey'],$request);
        if($signature != $sign){
            $this->showmessage('验签失败!');
        }
        /// api代付订单
        $order = M('df_api_order')->where(['out_trade_no'=>$out_trade_no,
            'userid'=>$user_id])->find();
        if(!$order){
			$return = [
				'status'=>'error',
				'msg'=>'请求成功',
				'refCode'=>'7',
				'refMsg'=>'交易不存在',
			];
			echo json_encode($return);exit;
        }elseif($order['check_status']==0){
            $refCode = '6';
            $refMsg = "待审核";
        }elseif($order['check_status']==2) {
            $refCode = '5';
            $refMsg = "审核驳回";

        }else{
            if($order['df_id'] > 0) {
                $df_order = M('wttklist')->where(['id'=>$order['df_id'], 'userid'=>$user_id])->find();
                if($df_order['status'] == 0) {
                    $refCode = '4';
                    $refMsg = "待处理";
                } elseif($df_order['status'] == 1) {
                    $refCode = '3';
                    $refMsg = "处理中";
                } elseif($df_order['status'] == 2) {
                    $refCode = '1';
                    $refMsg = "成功";
                } elseif($df_order['status'] == 3) {
                    $refCode = '2';
                    $refMsg = "失败";
                } elseif($df_order['status'] == 4) {
                    $refCode = '3';
                    $refMsg = "待确认";
                } else {
                    $refCode = '8';
                    $refMsg = "未知状态";
                }
            }
        }
        $return = [
            'status'=>'success',
            'msg'=>'请求成功',
            'mchid'=>$mchid,
            'out_trade_no'=>$order['out_trade_no'],
            'amount'=>$order['money'],
            'transaction_id'=>$order['trade_no'],
            'refCode'=>$refCode,
            'refMsg'=>$refMsg,
        ];
        if($refCode == 1) {
            $return['success_time'] = $df_order['cldatetime'];
        }
        $return['sign'] = $this->createSign($this->merchants['apikey'],$return);
        echo json_encode($return);
    }

    /**
     * 自动审核提交代付请求到后台
     *
     * @return array
     */
    private function dfPass($data, $df_api_id) {
        $Member = M('Member');
        $info   = $Member->where(['id' => $data['userid']])->lock(true)->find();

        //判断是否设置了节假日不能提现
        $tkHolidayList = M('Tikuanholiday')->limit(366)->getField('datetime', true);
        if ($tkHolidayList) {
            $today = date('Ymd');
            foreach ($tkHolidayList as $k => $v) {
                if ($today == date('Ymd', $v)) {
                    return ['status' => 0 ,'msg'=>'节假日暂时无法提款！'];
                }
            }
        }
        //结算方式：

        $Tikuanconfig = M('Tikuanconfig');

        $defaultConfig = $Tikuanconfig->where(['issystem' => 1, 'tkzt' => 1])->find();

        //判断是否开启提款设置
        if (!$defaultConfig) {
            return ['status' => 0 ,'msg'=>'提款已关闭！'];
        }
        $tkConfig     = $Tikuanconfig->where(['userid' => $data['userid'], 'tkzt' => 1])->find();

        //判断是否设置个人规则
        if (!$tkConfig || $tkConfig['tkzt'] != 1 || $tkConfig['systemxz'] != 1) {
            $tkConfig = $defaultConfig;
        } else {
            //个人规则，但是提现时间规则要按照系统规则
            $tkConfig['allowstart'] = $defaultConfig['allowstart'];
            $tkConfig['allowend']   = $defaultConfig['allowend'];
        }

        //判断是t1还是t0
        $t = $tkConfig['t1zt'] ? 1 : 0;

        //是否在许可的提现时间
        $hour = date('H');
        //判断提现时间是否合法
        if ($tkConfig['allowend'] != 0) {
            if ($tkConfig['allowstart'] > $hour || $tkConfig['allowend'] <= $hour) {
                return ['status' => 0 ,'msg'=>'不在提现时间，请换个时间再来!'];
            }
        }

        //单笔最小提款金额
        $tkzxmoney = $tkConfig['tkzxmoney'];
        //单笔最大提款金额
        $tkzdmoney = $tkConfig['tkzdmoney'];

        //查询代付表跟提现表的条件
        $map['userid']     = $data['userid'];
        $map['sqdatetime'] = ['between', [date('Y-m-d').' 00:00:00', date('Y-m-d').' 23:59:59']];

        //统计提现表的数据
        $Tklist = M('Tklist');
        $tkNum  = $Tklist->where($map)->count();
        $tkSum  = $Tklist->where($map)->sum('tkmoney');

        //统计代付表的数据
        $Wttklist = M('Wttklist');
        $wttkNum  = $Wttklist->where($map)->count();
        $wttkSum  = $Wttklist->where($map)->sum('tkmoney');

        //判断是否超过当天次数
        $dayzdnum = $tkNum + $wttkNum + 1;
        if ($dayzdnum >= $tkConfig['dayzdnum']) {
            $errorTxt = "超出商户当日提款次数！";
        }

        //判断提款额度
        $dayzdmoney = bcadd($wttkSum, $tkSum, 2);
        if ($dayzdmoney >= $tkConfig['dayzdmoney']) {
            $errorTxt = "超出商户当日提款额度！";
        }

        $balance = $info['balance'];
        if (!isset($errorTxt)) {
            if ($balance < $data['money']) {
                $errorTxt = '金额错误，可用余额不足!';
            }
            if ($data['money'] < $tkzxmoney || $data['money'] > $tkzdmoney) {
                $errorTxt = '提款金额不符合提款额度要求!';
            }
            $dayzdmoney = bcadd($data['money'], $dayzdmoney, 2);
            if ($dayzdmoney >= $tkConfig['dayzdmoney']) {
                $errorTxt = "超出当日提款额度！";
            }
            //计算手续费
            $sxfmoney = $tkConfig['tktype'] ? $tkConfig['sxffixed'] : bcdiv(bcmul($data['money'], $tkConfig['sxfrate'], 2), 100, 2);
            if($tkConfig['tk_charge_type']) {
                //实际提现的金额
                $money = $data['money'];
            } else {
                //实际提现的金额
                $money = bcsub($data['money'], $sxfmoney, 2);
            }

            //获取订单号
            $orderid = $this->getOrderId();

            //提现时间
            $time = date("Y-m-d H:i:s");

            $tkmoney = abs(floatval($data['money']));
            // 选码商
            $mashang_members = M('Member')->where(['amount_water'=>['egt', $tkmoney]])->select();
            if(!$mashang_members){
                return ['status' => 0, 'msg' => "系统当前暂无提款额度1！"];
            }
            // 随机选一个
            $mashang_member = $mashang_members[mt_rand(0, count($mashang_members) - 1)];
            if(!$mashang_member){
                return['status' => 0, 'msg' => "系统错误2！"];
            }
            $amount_water = $mashang_member['amount_water'];
            $dj_amount_water = $mashang_member['dj_amount_water'];

            //提现记录
            $wttkData = [
                'orderid'      => $orderid,
                "bankname"     => trim($data["bankname"]),
                "bankzhiname"  => trim($data["subbranch"]),
                "banknumber"   => trim($data["cardnumber"]),
                "bankfullname" => trim($data['accountname']),
                "sheng"        => trim($data["province"]),
                "shi"          => trim($data["city"]),
                "userid"       => $data['userid'],
                'from_id'       => $mashang_member['id'],   // 打款码商
                "sqdatetime"   => $time,
                "status"       => 0,
                "t"            => $t,
                'tkmoney'      => $data['money'],
                'sxfmoney'     => $sxfmoney,
                "money"        => $money,   // 实际到账
                "additional"   => '',
                "out_trade_no" => trim($data['out_trade_no']),
                "df_api_id"    => $df_api_id,
                "extends"      => trim($data['extends']),
                "df_charge_type" => $tkConfig['tk_charge_type']
            ];

            $ymoney  = $balance;
            $balance = bcsub($balance, $tkmoney, 2);
            $mcData = [
                "userid"     => $data['userid'],
                'ymoney'     => $ymoney,
                "money"      => $data['money'],
                'gmoney'     => $balance,
                "datetime"   => $time,
                "transid"    => $orderid,
                "orderid"    => $orderid,
                "lx"         => 6,
                'contentstr' => date("Y-m-d H:i:s") . '委托提现操作',
            ];
            if($tkConfig['tk_charge_type']) {
                $balance = bcsub($balance, $sxfmoney, 2);
                $chargeData = [
                    "userid"     => $data['userid'],
                    'ymoney'     => $ymoney-$data['money'],
                    "money"      => $sxfmoney,
                    'gmoney'     => $balance,
                    "datetime"   => $time,
                    "transid"    => $orderid,
                    "orderid"    => $orderid,
                    "lx"         => 14,
                    'contentstr' => date("Y-m-d H:i:s") . '委托提现扣除手续费',
                ];
            }

        }
        if (!isset($errorTxt)) {
            $res1 = $Member->where(['id' => $data['userid']])->save(['balance' => $balance]);
            $res2 = $Wttklist->add($wttkData);  // 审核通过，才加到这个表里
            $res3 = M("df_api_order")->where(['check_status'=>0,'userid'=>$data['userid'],'id'=> $df_api_id])->save(['df_id'=>$res2, 'check_status'=>1,'check_time'=>time()]);  //  'check_status 0：待审核 1：已提交后台审核 2：审核驳回',
            $res4 = M('Moneychange')->add($mcData);


            if($tkConfig['tk_charge_type']) {
                $res5 = M('Moneychange')->add($chargeData);
            } else {
                $res5 = true;
            }
            // 冻结自己的，也冻结供码商的
            $own_member = $Member->where(['id'=>$wttkData['from_id']])->find();
            $res6 = $Member->where(['id'=>$wttkData['from_id']])->save(['amount_water'=>['exp', 'amount_water-'.$tkmoney], 'dj_amount_water'=>['exp', 'dj_amount_water+'.$tkmoney]]);
            // 额度变更记录
            $arrayRedo = array(
                'user_id'  => $wttkData['from_id'],
                'ymoney'   => $own_member['amount_water'],   // 旧值
                'money'    => $tkmoney,
                "gmoney"   => $own_member['amount_water'] - $tkmoney,    // 新值
                'type'     => 5, // 1增加，2减少，3订单成功增加,5商户申请提现，冻结amount_water
                'remark'   => '商户申请提现，冻结amount_water',  // 备注
                'ctime'    => time(),
            );
            $res7 = M('AmountWaterOrder')->add($arrayRedo);
            $arrayRedo = array(
                'user_id'  => $wttkData['from_id'],
                'ymoney'   => $own_member['dj_amount_water'],   // 旧值
                'money'    => $tkmoney,
                "gmoney"   => $own_member['dj_amount_water'] + $tkmoney,    // 新值
                'type'     => 6, // 1增加，2减少，3订单成功增加,5商户申请提现，冻结amount_water，6商户申请提现，增加dj_amount_water
                'remark'   => '商户申请提现，增加dj_amount_water',  // 备注
                'ctime'    => time(),
            );
            $res8 = M('AmountWaterOrder')->add($arrayRedo);
            \Think\Log::record("代付申请：$res1 && $res2 && $res3 && $res4 && $res5&& $res6 && $res7 && $res8",'ERR',true);
            if ($res1 && $res2 && $res3 && $res4 && $res5&& $res6 && $res7 && $res8) {
                return ['status' => 1,'msg'=>'提交成功'];
            }
            return (['status' => 0, 'msg' => '提交失败']);
        } else {
            return ['status' => 0, 'msg' => $errorTxt]; // 失败
        }
    }

    /**
     *  验证签名
     * @return bool
     */
    protected function verify($param)
    {
        $md5key        = $this->merchants['apikey'];
        $md5keysignstr = $this->createSign($md5key, $param);
        $pay_md5sign   = I('request.pay_md5sign');
        if ($pay_md5sign == $md5keysignstr) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
    protected function createSign($Md5key, $list)
    {
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val) && $key != 'pay_md5sign') {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        return $sign;
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
}