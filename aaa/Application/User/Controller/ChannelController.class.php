<?php
namespace User\Controller;

use Org\Util\HttpClient;
use Think\Page;
use Org\Util\Date;

/**
 * 支付通道控制器
 * Class ChannelController
 * @package User\Controller
 */
class ChannelController extends UserController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 通道费率
     */
    public function index()
    {
        $userid = I('get.uid', $this->fans['uid'], 'intval');
        //系统产品列表
        $products = M('Product')
            ->where(['status' => 1, 'isdisplay' => 1])
            ->field('id,name')
            ->select();
        //用户产品列表
        $userprods = M('Product_user')->where(['userid' => $userid])->select();
        if ($userprods) {
            foreach ($userprods as $key => $item) {
                $product_user_tmpData[$item['pid']] = $item;
            }
        }

        //用户产品列表
        $userprods = M('Userrate')->where(['userid' => $userid])->select();
        if ($userprods) {
            foreach ($userprods as $item) {
                $_tmpData[$item['payapiid']] = $item;
            }
        }
        //重组产品列表
        $list = [];
        if ($products) {
            foreach ($products as $key => $item) {
                $products[$key]['t0feilv']    = $_tmpData[$item['id']]['t0feilv'] ? $_tmpData[$item['id']]['t0feilv'] : '0.0000';
                $products[$key]['t0fengding'] = $_tmpData[$item['id']]['t0fengding'] ? $_tmpData[$item['id']]['t0fengding'] : '0.0000';
                $products[$key]['feilv']      = $_tmpData[$item['id']]['feilv'] ? $_tmpData[$item['id']]['feilv'] : '0.0000';
                $products[$key]['fengding']   = $_tmpData[$item['id']]['fengding'] ? $_tmpData[$item['id']]['fengding'] : '0.0000';
                $products[$key]['status']     = $product_user_tmpData[$item['id']]['status'];
            }
        }

        //已开通通道
        /*$list = M('ProductUser')
            ->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id = __PRODUCT_USER__.pid')
            ->where(['pay_product_user.userid'=>$this->fans['uid'],'pay_product_user.status'=>1,
                'pay_product.isdisplay'=>1])
            ->field('pay_product.name,pay_product.id,pay_product_user.status')
            ->select();

        foreach ($list as $key=>&$item){
            $feilv = M('Userrate')->where(['userid'=>$this->fans['uid'],'payapiid'=>$item['id']])->getField('feilv');
            $list[$key]['feilv'] = $feilv;
        }*/

        //结算方式：
        $tkconfig = M('Tikuanconfig')->where(['userid'=>$this->fans['uid']])->find();
        if(!$tkconfig || $tkconfig['tkzt']!=1){
            $tkconfig = M('Tikuanconfig')->where(['issystem'=>1])->find();
        }
        $list = $products;
        $this->assign('tkconfig',$tkconfig);
        $this->assign('list',$list);
        $this->display();
    }

    //供应商接口列表
    public function myChannel()
    {
        // select * from pay_product_user where userid=30 and `status`=1;
        $list = M('ProductUser')->where(['userid'=>$this->fans['uid'], 'status'=>1])->select();
        $channel_ids = [];  // 我的通道
        foreach ($list as $k => $v){
            if($v['polling']){
                if($v['weight']){
                    $temp_weights = explode('|', $v['weight']); // channelid1:weight1|channelid2:weight2
                    foreach ($temp_weights as $k1 => $v1) {
                        list($pid, $weight) = explode(':', $v1);
                        if($pid)$channel_ids[] = $pid;
                    }
                }
            }else if($v['channel'])$channel_ids[] = $v['channel'];   // 单独
        }

        $where = ['id'=>['in', $channel_ids], 'status'=>1];
        $count = M('Channel')->where($where)->count();

        $size  = 15;
        $rows  = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Page = new Page($count, $rows);
        $data = M('Channel')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();
        $this->assign('rows', $rows);
        $this->assign('list', $data);
        $this->assign('page', $Page->show());
        $this->display();
    }

    /// $a = [
    ///     'polling' => 0/1,
    ///     'channel' => id,
    ///     'weight' => 'chid:weight|chid:weight'
    /// ];
    private function find_channel_id($a){
        $return = [];
        if($a['polling']){
            if($a['weight']){
                $temp_weights = explode('|', $a['weight']); // channelid1:weight1|channelid2:weight2
                foreach ($temp_weights as $k1 => $v1) {
                    list($pid, $weight) = explode(':', $v1);
                    if($pid)$return[] = $pid;
                }
            }
        }else $return[] = $a['channel'];
        return $return;
    }

    /**
     * 通道账户列表
     */
    public function account()
    {
        $channel_id = I('get.pid');
        $channel    = M('Channel')->where(['id' => $channel_id])->find();
        $accounts   = M('channel_account')->where(['channel_id' => $channel_id, 'memberid'=>$this->fans['uid']])->select();
        $this->assign('channel', $channel);
        $this->assign('accounts', $accounts);
        $this->display();
    }


    /**
     * 所有子账号列表
     */
    public function accounts()
    {
        //通道
        $tongdaolist = M("Channel")->field('id,code,title')->select();
        $this->assign("tongdaolist", $tongdaolist);

        $tongdao = I("request.tongdao");
        $where = ['memberid'=>$this->fans['uid']];  // 只能选自己的
        if ($tongdao) {
            $where['a.channel_id'] = array('eq', $tongdao);//
        }

        // 开关
        $switchs = [
            'status'            => ['账户状态',-1],
            'heartbeat_switch'  => ['心跳开关',-1],
            'manual_switch'     => ['手动开关',-1],
            'test_status'       => ['测试状态',-1],
        ];

        foreach($switchs as $switch => &$item){
            $value = I("request.".$switch);
            if($value != ''){
                $where['a.'.$switch] = array('eq', $value);
                $item[1] = $value;
            }
        }
        $this->assign('switchs', $switchs);
        $titleid = I("request.titleid");
        if ($titleid) {
            $where['a.id'] = array('eq', $titleid);//
        }
        foreach(['title'] as $key){
            $value = I("request.".$key);
            if($value)$where['a.'.$key] = ['like', '%'.$value.'%'];
        }
        $join = 'LEFT JOIN __CHANNEL__ b ON b.id=a.channel_id';
        $count   = M('channel_account')->alias('a')->join($join)->field('a.*, b.title as p_title')->where($where)->count();
		
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $this->assign('rows', $rows);

        $page = new Page($count, $rows);
        $this->assign('page', $page->show());
		
        $accounts   = M('channel_account')->alias('a')->join($join)->field('a.*, b.title as p_title')->where($where)
			->limit($page->firstRow . ',' . $page->listRows)->order('a.id desc')->select();
        // 找product.id

        $userid = C('demo_memberid', null, "10030") - 10000;   // 测试商户
        $product_list = M('ProductUser')->where(['status'=>1, 'userid'=>$userid,])->select();  // 支付产品

        $today_time = new Date();
        $yesterday = $today_time->dateAdd(-1)->getYmd();  // 昨天
        $today = $today_time->getYmd();  // 今天
        $orderTbl = M('Order');
        $tbl = M('AccountDayStat'); // 统计表
        foreach ($accounts as &$account) {
            // product_id
            foreach ($product_list as $product){
                $channel_ids = $this->find_channel_id($product);
                if(in_array($account['channel_id'], $channel_ids)){
                    $account['product_id'] = $product['pid'];
                    break;
                }
            }
            // 统计
            // 今日
            $today_stat = $tbl->where(['date'=>$today, 'account_id'=>$account['id']])->find();
            if($today_stat){
                $account['today_order_number'] = $today_stat['order_number'];   // 订单总数
                $account['today_payed_number'] = $today_stat['payed_number'];   // 成功订单数
                $account['today_pay_amount']   = $today_stat['pay_amount'];   // 今日流水
            }else{
                $account['today_order_number'] = $orderTbl->where(['account_id'=>$account['id'], "FROM_UNIXTIME(pay_applydate, '%Y%m%d')"=>$today])->count();
                $account['today_payed_number'] = $orderTbl->where(['account_id'=>$account['id'], "FROM_UNIXTIME(pay_applydate, '%Y%m%d')"=>$today, 'pay_status'=>['gt', 0]])->count();
                $today_pay_amount = $orderTbl->where(['account_id'=>$account['id'], "FROM_UNIXTIME(pay_applydate, '%Y%m%d')"=>$today, 'pay_status'=>['gt', 0]])->sum('pay_amount');
                $account['today_pay_amount']   = $today_pay_amount ?: 0;
                $tbl->add(['date'=>$today, 'account_id'=>$account['id'], 'order_number'=>$account['today_order_number'], 'payed_number'=>$account['today_payed_number'], 'pay_amount'=>$account['today_pay_amount']]);
            }
            $account['today_payed_rate'] = sprintf("%.2f",$account['today_order_number'] > 0 ? $account['today_payed_number'] * 100 / $account['today_order_number'] : 0); // 今日付款成功率
            // 昨天
            $yesterday_stat = $tbl->where(['date'=>$yesterday, 'account_id'=>$account['id']])->find();
            if($yesterday_stat){
                $account['yesterday_pay_amount']   = $yesterday_stat['pay_amount'];   // 昨日流水
            }else{
                $yesterday_pay_amount = $orderTbl->where(['account_id'=>$account['id'], "FROM_UNIXTIME(pay_applydate, '%Y%m%d')"=>$yesterday, 'pay_status'=>['gt', 0]])->sum('pay_amount');
                $account['yesterday_pay_amount'] = $yesterday_pay_amount ?: 0;
                if($today_time->getHour() > 6){ // 超过6小时，就统计
                    $yesterday_order_number = $orderTbl->where(['account_id'=>$account['id'], "FROM_UNIXTIME(pay_applydate, '%Y%m%d')"=>$yesterday])->count();
                    $yesterday_payed_number = $orderTbl->where(['account_id'=>$account['id'], "FROM_UNIXTIME(pay_applydate, '%Y%m%d')"=>$yesterday, 'pay_status'=>['gt', 0]])->count();
                    $tbl->add(['date'=>$yesterday, 'account_id'=>$account['id'], 'order_number'=>$yesterday_order_number, 'payed_number'=>$yesterday_payed_number, 'pay_amount'=>$account['yesterday_pay_amount']]);
                }
            }
            // 最近成功率
            $count_limit = 10;
            $latest_orders = $orderTbl->where(['account_id'=>$account['id']])->order('id desc')->limit($count_limit)->select();
            $latest_order_number = count($latest_orders);
            $latest_payed_number = 0;
            if($latest_order_number){
                $min_order_id = $latest_orders[$latest_order_number - 1]['id'];
                $max_order_id = $latest_orders[0]['id'];
                $latest_payed_number = $orderTbl->where(['account_id'=>$account['id'], 'id'=>['between', [$min_order_id, $max_order_id]],'pay_status'=>['gt',0]])->count();
            }
            $account['latest_order_number'] = $latest_order_number;
            $account['latest_payed_number'] = $latest_payed_number;
            $account['latest_payed_rate'] =  sprintf("%.2f",$latest_order_number > 0 ? $latest_payed_number * 100 / $latest_order_number : 0);
        }
        $this->assign('accounts', $accounts);
        $this->assign('disabled', C('ms_disabled') ? 'disabled': '');
        $this->display();
    }

    /**
     * 新增账户
     */
    public function addAccount()
    {
        $pid = intval($_GET['pid']);
        $this->assign('pid', $pid);
        $this->display('addAccount');
    }

    /**
     * 编辑账户
     */
    public function editAccount()
    {
        $aid = intval($_GET['aid']);
        $disabled = '';
        if ($aid) {
            $pa = M('channel_account')->where(['id' => $aid])->find();
            if($pa['status'])$disabled = 'disabled readonly ';
        }

        $this->assign('disabled', $disabled);

        $this->assign('pa', $pa);
        $this->assign('pid', $pa['channel_id']);
        $this->display('addAccount');
    }

    /**
     * 保存账户
     */
    public function saveEditAccount()
    {
        if (IS_POST) {
            if($this->fans['open_channel_account'] != 1)return;
            $id                     = I('post.id', 0, 'intval');
            $papiacc                = I('post.pa/a');
            $_request['xingming']      = trim($papiacc['xingming']);
            $_request['title']      = trim($papiacc['title']);
            $_request['channel_id'] = trim($papiacc['pid']);
            $_request['mch_id']     = trim($papiacc['mch_id']);
            $_request['signkey']    = trim($papiacc['signkey']);
            $_request['appid']      = trim($papiacc['appid']);
            $_request['appsecret']  = trim($papiacc['appsecret']);
            // 默认为1
            $weight                     = trim($papiacc['weight']);
            $_request['weight']         = $weight === '' ? 1 : $weight;
            $_request['custom_rate']    = $papiacc['custom_rate'];
            $_request['defaultrate']    = $papiacc['defaultrate'] ? $papiacc['defaultrate'] : 0;
            $_request['fengding']       = $papiacc['fengding'] ? $papiacc['fengding'] : 0;
            $_request['rate']           = $papiacc['rate'] ? $papiacc['rate'] : 0;
            $_request['t0defaultrate']    = $papiacc['t0defaultrate'] ? $papiacc['t0defaultrate'] : 0;
            $_request['t0fengding']       = $papiacc['t0fengding'] ? $papiacc['t0fengding'] : 0;
            $_request['t0rate']           = $papiacc['t0rate'] ? $papiacc['t0rate'] : 0;
            $_request['updatetime']     = time();
            $_request['status']         = 0; // 编辑之后需要审核，所以先关闭 $papiacc['status'];
            $_request['is_defined']     = $papiacc['is_defined'];
            $_request['all_money']      = $papiacc['all_money'] == '' ? 0:$papiacc['all_money'];
            $_request['min_money']      = $papiacc['min_money'] == '' ? 0:$papiacc['min_money'];
            $_request['max_money']      = $papiacc['max_money'] == '' ? 0:$papiacc['max_money'];
            $_request['start_time']     = $papiacc['start_time'];
            $_request['end_time']       = $papiacc['end_time'];
            $_request['offline_status'] = $papiacc['offline_status'];
            $_request['control_status'] = $papiacc['control_status'];
            $_request['unlockdomain'] = $papiacc['unlockdomain'];
            $_request['memberid'] = $this->fans['uid'];  // 归属

            $code = M('channel')->where(['id'=>$_request['channel_id']])->find()['code'];
            $can_repeat_channel_ids = [230,];   // 可以重复的id
            $can_repeat_channel_codes = ['Tdd',];   // 可以重复的控制器

            if ($id) {
                $old = M('channel_account')->where(array('id' => $id))->find();
                if($old['status']){ // 开放的时候，禁止修改
                    foreach(['title', 'mch_id', 'signkey', 'appid', 'appsecret', 'defaultrate', 'fail_times',
                                'fengding', 'rate', 't0defaultrate', 't0fengding', 't0rate', ] as $key){
                        unset($_request[$key]); // 防止变空值
                    }
                }
                if(  (! in_array( $_request['channel_id'], $can_repeat_channel_ids)) && ( ! in_array($code, $can_repeat_channel_codes)) ){
                    $old = M('channel_account')->where(array('id' => $id))->find();
                    foreach (['title', 'appid', ] as $key)
                        if ($old[$key] != $papiacc[$key] && M('channel_account')->where(['channel_id'=>$_request['channel_id'], $key => $papiacc[$key], ])->count() > 0)
                            $this->ajaxReturn(['status' => false, ]);
                }
                //更新
                $res = M('channel_account')->where(array('id' => $id))->save($_request);
            } else {
                if(  (! in_array( $_request['channel_id'], $can_repeat_channel_ids)) && ( ! in_array($code, $can_repeat_channel_codes)) ){    // 微信不限制
                    $count = M('channel_account')->where(['channel_id'=>$_request['channel_id'], [['title' => $papiacc['title'], 'appid'=>$papiacc['appid'], '_logic'=>'or'], '_logic'=>'and']])->count();
                    if($count > 0){
                        $this->ajaxReturn(['status' => false, ]);
                    }
                }
                //添加
                $_request['createtime']   = time();
                $res = M('channel_account')->add($_request);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //开启供应商接口
    public function editAccountStatus()
    {
        if (IS_POST) {
            $aid    = intval(I('post.aid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('channel_account')->where(['id' => $aid])->save(['status' => $isopen]);
            write_account_switch_log($aid, 0, $isopen ? 3 : 2);
            $this->ajaxReturn(['status' => $res]);
        }
    }
    //开启心态状态
    public function editAccountSwitchHeartBeat()
    {
        if (IS_POST) {
            $aid    = intval(I('post.aid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('channel_account')->where(['id' => $aid])->save(['heartbeat_switch' => $isopen]);
            write_account_switch_log($aid, 1, $isopen ? 3 : 2);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    // 测试收款账号
    public function editAccountTestStatus()
    {
        if (IS_POST) {
            $aid    = intval(I('post.aid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('channel_account')->where(['id' => $aid])->save(['test_status' => $isopen]);
            write_account_switch_log($aid, 2, $isopen ? 3 : 2);
            $this->ajaxReturn(['status' => $res]);
        }
    }
    /**
     * 开发文档
     */
    public function apidocumnet()
    {
        if($this->fans[groupid] != 4) {
            $this->error('您没有权限访问该页面!');
        }
        $sms_is_open = smsStatus();//短信开启状态
        $info = M('Member')->where(['id'=>$this->fans['uid']])->find();
        $this->assign('sms_is_open',$sms_is_open);
        $this->assign('mobile', $this->fans['mobile']);
        $this->assign('info',$info);
        $this->assign('public_key',C('public_key'));
        $this->display();
    }

    public function apikey()
    {
        $code = I('request.code');
        $res = check_auth_error($this->fans['uid'], 6);
        if(!$res['status']) {
            $this->ajaxReturn(['status' => 0, 'msg' => $res['msg']]);
        }
        $data = M('Member')->field('paypassword')->where(['id'=>$this->fans['uid']])->find();
        if(md5($code) != $data['paypassword']){
            log_auth_error($this->fans['uid'],6);
            $this->ajaxReturn(['status'=>0,'msg'=>'支付密码错误']);
        } else {
            clear_auth_error($this->fans['uid'],6);
        }
        $apikey = M('Member')->where(['id'=>$this->fans['uid']])->getField('apikey');
        $this->ajaxReturn(['status' => 1, 'apikey' => $apikey]);
    }

    //开启心态状态
    public function editSwitchManual()
    {
        if (IS_POST) {
            $aid    = intval(I('post.aid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('channel_account')->where(['id' => $aid, 'memberid'=>$this->fans['uid'], ])->save(['manual_switch' => $isopen]);
            write_account_switch_log($aid, 3, $isopen ? 3 : 2);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    public function accountSwitchLog(){
        $aid = I('request.aid');
        $where = ['account_id'=>$aid, 'memberid'=>$this->fans['uid']];
        $count = M('account_switch_log')->where($where)->count();
        $size  = 15;
        $rows  = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Page = new Page($count, $rows);
        $data = M('account_switch_log')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();
        $this->assign('rows', $rows);
        $this->assign('list', $data);
        $this->assign('page', $Page->show());
        $this->display('Admin@Channel/accountSwitchLog');
    }
}