<?php
namespace Admin\Controller;

use Org\Util\Date;
use Think\Page;

class ChannelController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->assign("Public", MODULE_NAME); // 模块名称
        $this->assign('paytypes', C('PAYTYPES'));

        //通道
        $channels = M('Channel')
            ->where(['status' => 1])
            ->field('id,code,title,paytype,status')
            ->select();
        $this->assign('channels', $channels);
        $this->assign('channellist', json_encode($channels));
    }

    //供应商接口列表
    public function index()
    {
        $count = M('Channel')->count();
        $size  = 15;
        $rows  = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Page = new Page($count, $rows);
        $data = M('Channel')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();
        // 剩余额度
        foreach ($data as &$channel){
            $where = ['channel_id'=>$channel['id'], 'status'=>1, ];
            $all_money = M('ChannelAccount')->where($where)->sum('all_money');
            $today_pay_amount = M('ChannelAccount')->where($where)->sum('today_pay_amount');
            $channel['left_limit'] = $all_money-$today_pay_amount;
        }
        $this->assign('rows', $rows);
        $this->assign('list', $data);
        $this->assign('page', $Page->show());
        $this->display();
    }

    /**
     * 保存编辑供应商
     */
    public function saveEditSupplier()
    {
        if (IS_POST) {
            $id                       = I('post.id', 0, 'intval');
            $papiacc                  = I('post.pa/a');
            $_request['code']         = trim($papiacc['code']);
            $_request['title']        = trim($papiacc['title']);
            $_request['mch_id']       = trim($papiacc['mch_id']);
            $_request['signkey']      = trim($papiacc['signkey']);
            $_request['appid']        = trim($papiacc['appid']);
            $_request['appsecret']    = trim($papiacc['appsecret']);
            $_request['gateway']      = trim($papiacc['gateway']);
            $_request['pagereturn']   = $papiacc['pagereturn'];
            $_request['serverreturn'] = $papiacc['serverreturn'];
            $_request['defaultrate']  = $papiacc['defaultrate'] ? $papiacc['defaultrate'] : 0;
            $_request['fengding']     = $papiacc['fengding'] ? $papiacc['fengding'] : 0;
            $_request['rate']         = $papiacc['rate'] ? $papiacc['rate'] : 0;
            $_request['t0defaultrate']  = $papiacc['t0defaultrate'] ? $papiacc['t0defaultrate'] : 0;
            $_request['t0fengding']     = $papiacc['t0fengding'] ? $papiacc['t0fengding'] : 0;
            $_request['t0rate']         = $papiacc['t0rate'] ? $papiacc['t0rate'] : 0;
            $_request['updatetime']   = time();
            $_request['unlockdomain'] = $papiacc['unlockdomain'];
            $_request['paytype']      = $papiacc['paytype'];
            $_request['status']       = $papiacc['status'];
            $_request['fail_limit']   = $papiacc['fail_limit']; // 失败次数阀值
            $_request['pay_delay']    = $papiacc['pay_delay']; // 延时支付
            $_request['money_list']    = $papiacc['money_list']; //

            if ($id) {
                //更新
                $res = M('Channel')->where(array('id' => $id))->save($_request);
            } else {
                //添加
                $res = M('Channel')->add($_request);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //开启供应商接口
    public function editStatus()
    {
        if (IS_POST) {
            $pid    = intval(I('post.pid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('Channel')->where(['id' => $pid])->save(['status' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //新增供应商接口
    public function addSupplier()
    {
        $this->display();
    }

    //编辑供应商接口
    public function editSupplier()
    {
        $pid = intval($_GET['pid']);
        if ($pid) {
            $pa = M('Channel')->where(['id' => $pid])->find();
        }
        $this->assign('pa', $pa);
        $this->display('addSupplier');
    }
    //删除供应商接口
    public function delSupplier()
    {
        $pid = I('post.pid', 0, 'intval');
        if ($pid) {
            // 删除子账号
            M('channel_account')->where(['channel_id' => $pid])->delete();
            $res = M('Channel')->where(['id' => $pid])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //编辑费率
    public function editRate()
    {
        if (IS_POST) {
            $pa = I('post.pa/a');
            $pid = I('post.pid', 0, 'intval');
            if ($pid) {
                $res       = M('Channel')->where(['id' => $pid])->save($pa);
                $pa['pid'] = $pid;
                $this->ajaxReturn(['status' => $res, 'data' => $pa]);
            }
        } else {
            $pid = intval(I('get.pid'));
            if ($pid) {
                $data = M('Channel')->where(['id' => $pid])->find();
            }

            $this->assign('pid', $pid);
            $this->assign('pa', $data);
            $this->display();
        }
    }

    //产品列表
    public function product()
    {
        $data = M('Product')->order('id asc')->select();
        $this->assign('list', $data);
        $this->display();
    }

    //切换产品状态
    public function prodStatus()
    {
        if (IS_POST) {
            $id    = I('post.id', 0, 'intval');
            $colum = I('post.k');
            $value = I('post.v');
            $res   = M('Product')->where(['id' => $id])->save([$colum => $value]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //切换用户显示状态
    public function prodDisplay()
    {
        if (IS_POST) {
            $id    = I('post.id', 0, 'intval');
            $colum = I('post.k');
            $value = I('post.v');
            $res   = M('Product')->where(['id' => $id])->save([$colum => $value]);
            $this->ajaxReturn(['status' => $res]);
        }
    }
    //添加产品
    public function addProduct()
    {
        $this->display();
    }

    //编辑产品
    public function editProduct()
    {
        $id   = I('get.pid', 0, 'intval');
        $data = M('Product')->where(['id' => $id])->find();

        //权重
        $weights    = [];
        $weights    = explode('|', $data['weight']);
        $_tmpWeight = '';
        if (is_array($weights)) {
            foreach ($weights as $value) {
                list($pid, $weight) = explode(':', $value);
                if ($pid) {
                    $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
                }
            }
        } else {
            list($pid, $weight) = explode(':', $data['weight']);
            if ($pid) {
                $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
            }
        }
        $data['weight'] = $_tmpWeight;
        //通道
        $channels = M('Channel')->where(["paytype" => $data['paytype'], "status" => 1])->select();
        $this->assign('channels', $channels);
        $this->assign('pd', $data);
        $this->display('addProduct');
    }

    //保存更改
    public function saveProduct()
    {
        if (IS_POST) {
            $id     = intval(I('post.id'));
            $rows   = I('post.pd/a');
            $weight = I('post.w/a');
            //权重
            $weightStr = '';
            if (is_array($weight)) {
                foreach ($weight as $weigths) {
                    if ($weigths['pid']) {
                        $weightStr .= $weigths['pid'] . ':' . $weigths['weight'] . "|";
                    }
                }
            }
            $rows['weight'] = trim($weightStr, '|');
            //保存
            if ($id) {
                $res = M('Product')->where(['id' => $id])->save($rows);
            } else {
                $res = M('Product')->add($rows);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //删除产品
    public function delProduct()
    {
        if (IS_POST) {
            $id  = I('post.pid', 0, 'intval');
            $res = M('Product')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //接口模式
    public function selProduct()
    {
        if (IS_POST) {
            $paytyep = I('post.paytype', 0, 'intval');
            //通道
            $data = M('Channel')->where(["paytype" => $paytyep, "status" => 1])->select();
            $this->ajaxReturn(['status' => 0, 'data' => $data]);
        }
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
        $userid = 30;   // 测试商户
        $product_list = M('ProductUser')->where(['status'=>1, 'userid'=>$userid,])->select();  // 支付产品
        $channel_id = I('get.pid');
        $channel    = M('Channel')->where(['id' => $channel_id])->find();
        $accounts   = M('channel_account')->where(['channel_id' => $channel_id])->select();
        // 找product.id
        $product_id = 0;
        foreach ($product_list as $product){
            $channel_ids = $this->find_channel_id($product);
            if(in_array($channel_id, $channel_ids)){
                $product_id = $product['pid'];
                break;
            }
        }
        $this->assign('product_id', $product_id);
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
        $where = [];
        if ($tongdao) {
            $where['a.channel_id'] = array('eq', $tongdao);//
        }
        // 码商列表
        $mashang = M('member')->where([])->select() ?: [];

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
        ///
        foreach(['title'] as $key){
            $value = I("request.".$key);
            if($value)$where['a.'.$key] = ['like', '%'.$value.'%'];
        }
        $join = 'LEFT JOIN __CHANNEL__ b ON b.id=a.channel_id';
        //$join1 = 'LEFT JOIN __MEMBER__ m ON m.id=a.member_id';
        $count = M('channel_account')->alias('a')->join($join)->where($where)->count();

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

        // 所属商户的名字
        $users = M("member")->field('id,username')->select();
        $usernames = [];
        foreach($users as $user){
            $usernames[$user['id']] = $user['username'];
        }
        $today_time = new Date();
        $yesterday = $today_time->dateAdd(-1)->getYmd();  // 昨天
        $today = $today_time->getYmd();  // 今天
        $orderTbl = M('Order');
        $tbl = M('AccountDayStat'); // 统计表
        foreach ($accounts as &$account) {
            // 所属商户
            $account['username'] = $usernames[$account['memberid']] ?: '系统';
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
        $this->display();
    }

    /**
     * 编辑账户
     */
    public function editAccountControl()
    {
        if (IS_POST) {
            $data = I('post.data', '');

            if ($data['start_time'] != 0 || $data['end_time'] != 0) {
                if ($data['start_time'] >= $data['end_time']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '交易结束时间不能小于开始时间！']);
                }
            }
            if ($data['max_money'] != 0 && $data['min_money'] != 0) {
                if ($data['min_money'] >= $data['max_money']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '最大交易金额不能小于或等于最小金额！']);
                }
            }
            if ($data['is_defined'] == 0) {
                $channel_id = M('ChannelAccount')->where(['id' => $data['id']])->getField('channel_id');
                $channelInfo = M('Channel')->where(['id' => $channel_id])->find();
                $data['offline_status'] = $channelInfo['offline_status'];
                $data['control_status'] = $channelInfo['control_status'];
            }
            $res = M('ChannelAccount')->where(['id' => $data['id']])->save($data);
            $this->ajaxReturn(['status' => $res]);
        } else {
            $aid  = I('get.aid', '', 'intval');
            $info = M('ChannelAccount')->where(['id' => $aid])->find();

            $this->assign('info', $info);
            $this->assign('aid', $aid);
            $this->display();
        }

    }

    /**
     * 编辑账户
     */
    public function editAccount()
    {
        $aid = intval($_GET['aid']);
        if ($aid) {
            $pa = M('channel_account')->where(['id' => $aid])->find();
        }
        $this->assign('pa', $pa);
        $this->assign('pid', $pa['channel_id']);
        $this->display('addAccount');
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

    public function showEven()
    {
        // echo "<pre>";
        $channelList = M('Channel')->where(['control_status' => 1, 'status' => 1])->select();
        $accountList = M('ChannelAccount')->where(['control_status' => 1, 'status' => 1])->select();

        $list = [];
        foreach ($channelList as $k => $v) {
            $v['offline_status'] = $v['offline_status'] ? '上线' : '下线';
            $list[$k]            = $v;
            foreach ($accountList as $k1 => $v1) {
                if ($v1['channel_id'] == $v['id']) {
                    $v1['offline_status']  = $v1['offline_status'] ? '上线' : '下线';
                    $list[$k]['account'][] = $v1;
                }
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 保存账户
     */
    public function saveEditAccount()
    {
        if (IS_POST) {
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
            $_request['status']         = $papiacc['status'];
            $_request['is_defined']     = $papiacc['is_defined'];
            $_request['all_money']      = $papiacc['all_money'] == '' ? 0:$papiacc['all_money'];
            $_request['min_money']      = $papiacc['min_money'] == '' ? 0:$papiacc['min_money'];
            $_request['max_money']      = $papiacc['max_money'] == '' ? 0:$papiacc['max_money'];
            $_request['start_time']     = $papiacc['start_time'];
            $_request['end_time']       = $papiacc['end_time'];
            $_request['offline_status'] = $papiacc['offline_status'];
            $_request['control_status'] = $papiacc['control_status'];
            $_request['unlockdomain']   = $papiacc['unlockdomain'];
            //$_request['fail_times']     = $papiacc['fail_times'];

            $code = M('channel')->where(['id'=>$_request['channel_id']])->find()['code'];
            $can_repeat_channel_ids = [230,];
            $can_repeat_channel_codes = ['Tdd',];
            if ($id) {
                if(  (! in_array( $_request['channel_id'], $can_repeat_channel_ids)) && ( ! in_array($code, $can_repeat_channel_codes)) ){
                    $old = M('channel_account')->where(array('id' => $id))->find();
                    foreach (['title', 'appid', ] as $key)
                        if ($old[$key] != $papiacc[$key] ){
                            $count = M('channel_account')->where(['channel_id'=>$_request['channel_id'], $key => $papiacc[$key], ])->count();
                            if($count > 0){
                                $this->ajaxReturn(['status' => false, 'msg'=>"$key 重复"]);
                            }
                        }
                }
                //更新
                $res = M('channel_account')->where(array('id' => $id))->save($_request);
            } else {
                if( (! in_array( $_request['channel_id'], $can_repeat_channel_ids)) && ( ! in_array($code, $can_repeat_channel_codes)) ) {
                    $count = M('channel_account')->where(['channel_id' => $_request['channel_id'], [['title' => $papiacc['title'], 'appid' => $papiacc['appid'], '_logic' => 'or'], '_logic' => 'and']])->count();
                    if ($count > 0)
                        $this->ajaxReturn(['status' => false,]);
                }
                //添加
                $_request['createtime']   = time();
                $res = M('channel_account')->add($_request);
            }
            $this->ajaxReturn(['status' => $res, 'msg'=>M()->getDbError()]);
        }
    }

    //开启供应商接口
    public function editAccountStatus()
    {
        if (IS_POST) {
            $aid    = intval(I('post.aid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('channel_account')->where(['id' => $aid])->save(['status' => $isopen]);
            write_account_switch_log($aid, 0, $isopen ? 1 : 0);
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
            write_account_switch_log($aid, 1, $isopen ? 1 : 0);
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
            write_account_switch_log($aid, 2, $isopen ? 1 : 0);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //删除供应商接口
    public function delAccount()
    {
        $aid = I('post.aid', 0, 'intval');
        if ($aid) {
            $res = M('channel_account')->where(['id' => $aid])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //编辑费率
    public function editAccountRate()
    {
        if (IS_POST) {
            $pa = I('post.pa');
            $accountId = I('post.aid');
            if ($accountId) {
                $res       = M('channel_account')->where(['id' => $accountId])->save($pa);
                $pa['aid'] = $accountId;
                $this->ajaxReturn(['status' => $res, 'data' => $pa]);
            }
        } else {
            $aid = intval(I('get.aid'));
            if ($aid) {
                $data = M('channel_account')->where(['id' => $aid])->find();
            }

            $this->assign('aid', $aid);
            $this->assign('pa', $data);
            $this->display();
        }
    }

    //编辑风控
    public function editControl()
    {
        if (IS_POST) {
            $data = I('post.data', '');
            if ($data['start_time'] != 0 || $data['end_time'] != 0) {
                if ($data['start_time'] >= $data['end_time']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '交易结束时间不能小于开始时间！']);
                }
            }
            if ($data['max_money'] != 0 && $data['min_money'] != 0) {
                if ($data['min_money'] >= $data['max_money']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '最大交易金额不能小于或等于最小金额！']);
                }
            }
            $res = M('Channel')->where(['id' => $data['id']])->save($data);
            $this->ajaxReturn(['status' => $res]);
        } else {
            $pid  = I('get.pid', '');
            $info = M('Channel')->where(['id' => $pid])->find();
            $this->assign('info', $info);
            $this->assign('pid', $pid);
            $this->display();
        }
    }

    public function accountSwitchLog(){
        $aid = I('request.aid');
        $where = ['account_id'=>$aid];
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
        $this->display();
    }

    //开启心态状态
    public function editSwitchManual()
    {
        if (IS_POST) {
            $aid    = intval(I('post.aid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('channel_account')->where(['id' => $aid, ])->save(['manual_switch' => $isopen]);
            write_account_switch_log($aid, 3, $isopen ? 1 : 0);
            $this->ajaxReturn(['status' => $res]);
        }
    }

}
