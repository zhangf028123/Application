<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Home\Controller;
use Boris\Config;
use Org\Util\Date;
use Think\Page;
/**
 * 网站入口控制器
 * Class IndexController
 * @package Home\Controller
 * @author 22691513@qq.com
 */
class IndexController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        // api和sdk需要登录
        //验证登录
        $user_auth = session("user_auth");
        ksort($user_auth); //排序
        $code = http_build_query($user_auth); //url编码并生成query字符串
        $sign = sha1($code);
        $needLogin = in_array(ACTION_NAME, ['sdk', 'document', ]);  // 这两个需要登录
        if($needLogin && ($sign != session('user_auth_sign') || !$user_auth['uid'])){
            $module = strtolower(trim(__MODULE__, '/'));
            $module = trim($module, './');
            header("Location: ".U('userLogin')); // 跳转到登陆
        }
        /*/用户信息
        $this->fans = M('Member')->where(['id'=>$user_auth['uid']])->field('`id` as uid, `username`, `password`, `groupid`, `parentid`,`salt`,`balance`, `blockedbalance`, `email`, `realname`, `authorized`, `apidomain`, `apikey`, `status`, `mobile`, `receiver`, `agent_cate`,`df_api`,`login_ip`,`open_charge`,`google_secret_key`,`session_random`,`regdatetime`,`collect_type`,td_balance,amount_water,dj_amount_water,open_channel_account')->find();
        $this->fans['memberid'] = $user_auth['uid']+10000;
        if(session('user_auth') && $this->fans['google_secret_key'] &&  !session('user_google_auth')) {
            if(!(CONTROLLER_NAME == 'Account' && ACTION_NAME == 'unbindGoogle')
                &&!(CONTROLLER_NAME == 'Index' && ACTION_NAME == 'google')
                &&!(CONTROLLER_NAME == 'Login' && ACTION_NAME == 'verifycode')
                &&!(CONTROLLER_NAME == 'Account' && ACTION_NAME == 'unbindGoogleSend')
            ) {
                if(IS_AJAX){
                    $this->error('请进行谷歌身份验证', 'User/Index/google');
                }else{
                    $this->redirect('User/Index/google');
                }
            }
        }
        if(!session('user_auth.session_random') && $this->fans['session_random'] && session('user_auth.session_random') !=  $this->fans['session_random']) {
            session('user_auth', null);
            session('user_auth_sign', null);
            session('user_google_auth', null);
            $this->error('您的账号在别处登录，如非本人操作，请立即修改登录密码！','index.html');
        }
        $groupId = $this->groupId =  C('GROUP_ID');
        //获取用户的代理等级信息
        foreach($groupId as $k => $v){
            if($k>=$this->fans['groupid'])  // 不能读取比自己高的代理的信息
                unset($groupId[$k]);
        }
        $this->assign('groupId',$groupId);
        $this->assign('fans',$this->fans);*/
    }

    public function index()
    {
        $this->display();
    }

    public function rate()
    {
        $this->display();
    }

    public function download()
    {
        $this->display();
    }

    public function contact()
    {
        $this->display();
    }

    public function vhash()
    {
        echo C('vhash');
    }
	
	 /**
     * 生成二维码
     */
    public function generateQrcode()
    {
        $str     =html_entity_decode(urldecode(I('str','')));
        if(!$str){
            exit('请输入要生成二维码的字符串！');
        }
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        header('Content-type: image/png');
        \QRcode::png($str, false, "L", 10, 1);
        die;
    }

    public function test(){

        $map['userid'] = 158;
        $map['datetime'] = ['between',['2018-06-18 00:00:00','2018-06-23 23:59:59']];
        $list = M('moneychange')->where($map)->order('datetime DESC')->select();
        $ymoney = '';
        foreach($list as $k => $v) {
            if($ymoney!='') {
                if ($ymoney != $v['gmoney'] && $v['lx'] == 6) {
                    echo 'ID：' . $v['id'] . '<br>';
                }
            }
            $ymoney = $v['ymoney'];

        }
        echo 'completed';
    }

    public function test2() {
        $map['pay_status'] = ['in','1,2'];
        //$map['pay_successdate'] = ['between',[1530460800,1530547199]];
        $list = M('Order')->where($map)->select();
		$count = 0;
        foreach($list as $k => $v) {
            $profit = $v['pay_poundage'] - $v['cost'];
            $yj = M('moneychange')->where(['lx'=>9, 'transid'=>$v['pay_orderid']])->sum('money');
            if($profit<0) {
				$count++;
                echo '订单'.$v['pay_orderid'].'利润小于0<br>';
            }
        }
        echo 'ok';die;
    }

public function test3(){
    echo $this->getDateBalance('158', '2018-07-14');
}
    /*
  * 根据日期获取用户期初余额
  */
    private function getDateBalance($userid, $date) {

        $log = M('Moneychange')->where(['userid'=>$userid, 'datetime'=>array('elt', $date), 't'=>['neq', 1], 'lx' => ['not in', '3,4']])->order('datetime DESC,id DESC')->find();   // 排除管理员手动变更余额的最新那条记录
        if(empty($log)) {
            $money = 0;
        } else {
            $yesterdayTime = date("Y-m-d",strtotime($date)-1);
            $yesterdayRedAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$userid,'date'=>$yesterdayTime, 'ctime'=>['gt', strtotime($log['datetime'])]])->sum('money');
            $yesterdayRedReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$userid,'date'=>$yesterdayTime, 'ctime'=>['gt', strtotime($log['datetime'])]])->sum('money');
            $money = $log['gmoney'] + $yesterdayRedAddSum - $yesterdayRedReduceSum + 0;
        }
        return $money;
    }

    public function test4() {
        $map['pay_status'] = ['in','1,2'];
        $map['pay_memberid'] = 10020;
        $list = M('Order')->where($map)->select();
        foreach($list as $k => $v) {
            $log = M('Moneychange')->where(['lx'=>1,'userid'=>20,'transid'=>$v['pay_orderid']])->find();
            if(empty($log)) {
                echo '异常流水：'.$v['pay_orderid'].'<br>';
            } else {
                if($log['money'] != $v['pay_actualamount']) {
                    echo '金额异常：'.$v[pay_orderid].', 订单金额：'.$v['pay_actualamount'].',流水金额：'.$log['money'].'<br>';
                }
            }
        }
        echo 'compeleted';
    }

    // 获取测试商户号的 pay_channel
    public function test5(){
        $userid = 30;
        // select * from pay_product_user where userid=30 and `status`=1;
        $list = M('ProductUser')->where(['userid'=>$userid, 'status'=>1])->field('channel,weight')->select();
        $return = [];
        foreach ($list as $k => $v){
            if($v['polling']){
                if($v['weight']){
                    $temp_weights = explode('|', $v['weight']); // channelid1:weight1|channelid2:weight2
                    foreach ($temp_weights as $k1 => $v1) {
                        list($pid, $weight) = explode(':', $v1);
                        if($pid)$return[] = $pid;
                    }
                }
            }else if($v['channel'])$return[] = $v['channel'];   // 单独
        }
        dump($return);
    }

    public function test6($userid){
        $level1_ids = array_column( M('Member')->where(['parentid'=>$userid])->field('id')->select(), 'id') ?: [];
        $level2_ids = array_column( M('Member')->where(['parentid'=>['in', $level1_ids]])->field('id')->select(), 'id') ?: [];
        $level3_ids = array_column( M('Member')->where(['parentid'=>['in', $level2_ids]])->field('id')->select(), 'id') ?: [];
        $subordinate_ids = array_map(function($v){return $v+10000;}, array_merge($level1_ids, $level2_ids, $level3_ids));
        dump($level1_ids); dump($level2_ids); dump($level3_ids); dump($subordinate_ids);

        $todayBegin = date('Y-m-d').' 00:00:00';
        $todyEnd = date('Y-m-d').' 23:59:59';
        $yesterdayBegin = (new Date($todayBegin))->dateAdd(-1)->format();
        $yesterdayEnd = (new Date($todyEnd))->dateAdd(-1)->format();
        echo $yesterdayBegin.'<br>';echo $yesterdayEnd.'<br>';

        //昨日总订单数
        dump( M('Order')->where(['pay_memberid'=>10000+58,'pay_applydate'=>['between', [strtotime($yesterdayBegin), strtotime($yesterdayEnd)]]])->field('id,pay_memberid')->select());
    }

    public function testwx(){
        echo '<a href="weixin://">唤起微信</a>';
    }

    public function debug(){
        \Think\Log::record('DEBUG:'.json_encode($_REQUEST),'DEBUG',true);
    }
    public function ip(){

        $ip = get_client_ip();
        $location             = \Org\Net\NIpLocation::find($ip); //返回式一个数组，索引0 国家 1省份 2城市
        $rows['loginip']      = $ip;
        $rows['loginaddress'] = $location[1] . "-" . $location[2];
        echo $ip;
        dump($rows);
    }
}
