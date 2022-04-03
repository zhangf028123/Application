<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace User\Controller;
use Org\Util\Date;
use Think\Verify;
use Think\Page;

/**
 * 用户中心首页控制器
 * Class IndexController
 * @package User\Controller
 */

class IndexController extends UserController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 首页
     */
    public function index()
    {
        $module = strtolower(trim(__MODULE__, '/'));
        $module = trim($module, './');
        $loginout = U($module . "/Login/loginout");
        $this->assign('loginout', $loginout);
        $this->display();
    }

    public function main()
    {
        $firstday = date('Y-m-01', time());
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));

        //成交金额
        $sql = "SELECT SUM( pay_actualamount ) AS total, FROM_UNIXTIME( pay_successdate,  '%Y-%m-%d' ) AS DATETIME
FROM pay_order WHERE pay_successdate >= UNIX_TIMESTAMP(  '".$firstday."' ) AND pay_successdate < UNIX_TIMESTAMP(  '".
            $lastday."' ) AND pay_status>=1 AND pay_memberid=".($this->fans['memberid'])."  GROUP BY DATETIME";
        $ordertotal = M('Order')->query($sql);

        //成交订单数
        $sql = "SELECT COUNT( id ) AS num, FROM_UNIXTIME( pay_successdate,  '%Y-%m-%d' ) AS DATETIME
FROM pay_order WHERE pay_successdate >= UNIX_TIMESTAMP(  '".$firstday."' ) AND pay_successdate < UNIX_TIMESTAMP(  '".
            $lastday."' ) AND pay_status>=1 AND pay_memberid=".($this->fans['memberid'])."  GROUP BY DATETIME";
        $ordernum = M('Order')->query($sql);
        foreach ($ordernum as $key=>$item){
            $category[] = date('Ymd',strtotime($item['datetime']));
            $dataone[] = $item['num'];
            $datatwo[] = $ordertotal[$key]['total'];
        }
        $this->assign('category','['.implode(',',$category).']');
        $this->assign('dataone','['.implode(',',$dataone).']');
        $this->assign('datatwo','['.implode(',',$datatwo).']');
        //文章默认最新2条
        $Article = M("Article");
        if($this->fans['groupid'] == 4) {
            $gglist = $Article->where(['status'=> 1, 'groupid'=>['in','0,1']])->limit(2)->order("id desc")->select();
        } else {
            $gglist = $Article->where(['status'=> 1, 'groupid'=>['in','0,2']])->limit(2)->order("id desc")->select();
        }
        $this->assign("gglist", $gglist);
        //获取最近两次登录记录
        $loginlog = M("Loginrecord")->where(['userid' => $this->fans['uid']])->order('id desc')->limit(2)->select();
        $lastlogin = '';
        if(isset($loginlog[1])) {
            $lastlogin = $loginlog[1];
        }
        if (trim($this->fans['login_ip'])) {
            $ipItem = explode("\r\n", $this->fans['login_ip']);
        }
        // 今天时间
        $todayBegin = date('Y-m-d').' 00:00:00';
        $todyEnd = date('Y-m-d').' 23:59:59';
        $yesterdayBegin = (new Date($todayBegin))->dateAdd(-1)->format();
        $yesterdayEnd = (new Date($todyEnd))->dateAdd(-1)->format();
        if(2 == $this->fans['collect_type']){   // 供码商户
            // 我的所有子账号id
            $my_account_ids = array_column(M('channel_account')->where(['memberid'=>$this->fans['uid']])->field('id')->select() , 'id');
            //今日总订单数
            $stat['todayordercount'] = M('Order')->where(['account_id'=>['in', $my_account_ids],'pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]]])->count();
            //今日已付订单数
            $stat['todayorderpaidcount'] = M('Order')->where(['account_id'=>['in', $my_account_ids],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->count();
            //今日总订单流水
            $stat['todayordersum'] = M('Order')->where(['account_id'=>['in', $my_account_ids], 'pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],])->sum('pay_amount');
            //今日总付款流水
            $stat['todayorderpaidsum'] = M('Order')->where(['account_id'=>['in', $my_account_ids],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],  'pay_status'=>['in', '1,2']])->sum('pay_amount');

            // 今日分润
            $stat['todayprofit'] = M('Order')->where(['account_id'=>['in', $my_account_ids],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],  'pay_status'=>['in', '1,2']])->sum('pay_profit');
            $stat['todaycost'] = M('Order')->where(['account_id'=>['in', $my_account_ids],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],  'pay_status'=>['in', '1,2']])->sum('provider_cost');    // 供码商的成本
            $stat['todayprofit'] -= $stat['todaycost']; // 供码商的实际利润
            $where = [
                'from_id'   => $this->fans['uid'],
                'status'    => ['in', [2, 4,]],
                'cldatetime'  => ['between', [$todayBegin, $todyEnd]],
            ];
            $stat['todaytk'] = M('Tklist')->where([$where])->sum('tkmoney');
            $where['cldatetime'] = ['between', [$yesterdayBegin, $yesterdayEnd]];
            $stat['yesterdaytk'] = M('Tklist')->where([$where])->sum('tkmoney');

            // 昨日分润
            $stat['yesterdayprofit'] = M('Order')->where(['account_id'=>['in', $my_account_ids],'pay_successdate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]],  'pay_status'=>['in', '1,2']])->sum('pay_profit');
            $stat['yesterdaycost'] = M('Order')->where(['account_id'=>['in', $my_account_ids],'pay_successdate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]],  'pay_status'=>['in', '1,2']])->sum('provider_cost');    // 供码商的成本
            $stat['yesterdayprofit'] -= $stat['yesterdaycost']; // 供码商的实际利润

            // 店铺总限额
            $stat['shop_all_money'] = M('channel_account')->where(['memberid'=>$this->fans['uid']])->sum('all_money');
            //$stat['shop_paying_money'] = M('channel_account')->where(['memberid'=>$this->fans['uid'], 'last_paying_time' =>['between', [strtotime($todayBegin), strtotime($todyEnd)]]])->sum('paying_money');
            $stat['shop_paying_money'] = M('Order')->where(['account_id'=>['in', $my_account_ids],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],  'pay_status'=>['in', '1,2']])->sum('pay_amount');
            $stat['shop_all_money_open'] = M('channel_account')->where(['memberid'=>$this->fans['uid'], 'status' => '1', 'test_status' => '1', 'heartbeat_switch' => '1', 'manual_switch' => '1' ])->sum('all_money');
            $stat['shop_paying_money_open'] = M('channel_account')->where(['memberid'=>$this->fans['uid'], 'status' => '1', 'test_status' => '1', 'heartbeat_switch' => '1', 'manual_switch' => '1', 'last_paying_time' =>['between', [strtotime($todayBegin), strtotime($todyEnd)]]])->sum('paying_money');


        }else{  // 默认商户
            //今日总订单数
            $stat['todayordercount'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]]])->count();
            //今日已付订单数
            $stat['todayorderpaidcount'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->count();
            //今日未付订单数
            //$stat['todayordernopaidcount'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>0])->count();
            //今日提交金额
            //$stat['todayordersum'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>0])->sum('pay_amount');
            //今日总订单流水
            $stat['todayordersum'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],])->sum('pay_amount');
            //今日总付款流水
            $stat['todayorderpaidsum'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],  'pay_status'=>['in', '1,2']])->sum('pay_amount');

            //今日实付金额
            $stat['todayorderactualsum'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->sum('pay_actualamount');


            //昨日总订单数
            $stat['yesterdayordercount'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_applydate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]]])->count();
            //昨日已付订单数
            $stat['yesterdayorderpaidcount'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_successdate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]], 'pay_status'=>['in', '1,2']])->count();
            //昨日总订单流水
            $stat['yesterdayordersum'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_applydate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]],])->sum('pay_amount');
            //昨日总付款流水
            $stat['yesterdayorderpaidsum'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_successdate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]],  'pay_status'=>['in', '1,2']])->sum('pay_amount');

            //昨日实付金额
            $stat['yesterdayorderactualsum'] = M('Order')->where(['pay_memberid'=>10000+$this->fans['uid'],'pay_successdate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]], 'pay_status'=>['in', '1,2']])->sum('pay_actualamount');

            //投诉保证金
            $stat['complaints_deposit'] = M('complaints_deposit')->where(['user_id'=>$this->fans['uid'], 'status'=>0])->sum('freeze_money');

            // 下家id
            $level1_ids = array_column( M('Member')->where(['parentid'=>$this->fans['uid']])->field('id')->select(), 'id') ?: [];
            $level2_ids = array_column( M('Member')->where(['parentid'=>['in', $level1_ids]])->field('id')->select(), 'id') ?: [];
            $level3_ids = array_column( M('Member')->where(['parentid'=>['in', $level2_ids]])->field('id')->select(), 'id') ?: [];
            $subordinate_ids = array_map(function($v){return $v+10000;}, array_merge($level1_ids, $level2_ids, $level3_ids));
            // 今日代理流水
            $stat['todaydlamountsum'] = M('Order')->where(['pay_memberid'=>['in', $subordinate_ids],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],  'pay_status'=>['in', '1,2']])->sum('pay_amount');
            // 今日佣金
            $yj = M('moneychange')->where(['userid'=>$this->fans['uid'],'datetime'=>['between', [$todayBegin, $todyEnd]],'lx'=>9])->sum('money');
            $stat['today_yongjin'] = $yj;
            //今日收入
            $stat['today_income'] = $stat['todayorderactualsum'] + $yj;

            // 昨日代理流水
            $stat['yesterdaydlamountsum'] = M('Order')->where(['pay_memberid'=>['in', $subordinate_ids],'pay_successdate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]],  'pay_status'=>['in', '1,2']])->sum('pay_amount');
            // 昨日佣金
            $yj = M('moneychange')->where(['userid'=>$this->fans['uid'],'datetime'=>['between', [$yesterdayBegin, $yesterdayEnd]],'lx'=>9])->sum('money');
            $stat['yesterday_yongjin'] = $yj;

        }

        foreach($stat as $k => $v) {
            $stat[$k] = $v+0;
        }
        $this->assign('stat', $stat);
        $this->assign('ipItem',$ipItem);
        $this->assign('lastlogin', $lastlogin);
        $this->assign('user', $this->fans);
        $this->display();
    }

    public function showcontent()
    {
        $id = I("get.id", 0, 'intval');
        if($id<=0) {
            $this->error('参数错误');
        }
        $Article = M("Article");
        if($this->fans['groupid'] == 4) {
            $find = $Article->where(['id'=>$id,'status'=> 1,'groupid'=>['in','0,1']])->find();
        } else {
            $find = $Article->where(['id'=>$id,'status'=> 1,'groupid'=>['in','0,2']])->find();
        }
        $this->assign("find", $find);
        $this->display();
    }

    public function gonggao()
    {
        $where['status'] = 1;
        if($this->fans['groupid'] == 4) {
            $where['groupid'] = ['in','0,1'];
            $count = M('Article')->where($where)->count();
            $page           = new Page($count, 5);
            $list = M('Article')->where($where)->limit($page->firstRow . ',' . $page->listRows)->order("id desc")->select();
        } else {
            $where['groupid'] = ['in','0,2'];
            $count = M('Article')->where($where)->count();
            $page           = new Page($count, 5);
            $list = M('Article')->where($where)->limit($page->firstRow . ',' . $page->listRows)->order("id desc")->select();
        }

        $this->assign("list", $list);
        $this->assign('page', $page->show());
        $this->display();
    }

    public function google()
    {
        if(IS_POST) {
            $google_secret_key = M('Member')->where(array('id'=> $this->fans['uid']))->getField('google_secret_key');
            if($google_secret_key == '') {
                $this->error("您未绑定谷歌身份验证器");
            }
            $res = check_auth_error($this->fans['uid'], 4);
            if(!$res['status']) {
                $this->ajaxReturn(['status' => 0, 'msg' => $res['msg']]);
            }
            $code = I('code');
            $ga = new \Org\Util\GoogleAuthenticator();
            if(false === $ga->verifyCode($google_secret_key, $code, C('google_discrepancy'))) {
                log_auth_error($this->fans['uid'],4);
                $this->error("谷歌安全码错误");
            } else {
                clear_auth_error($this->fans['uid'],4);
                session('user_google_auth', $code);
                $this->success("验证通过，正在进入商户中心...", U('Index/index'));
            }
        } else {
            $this->display();
        }
    }
}