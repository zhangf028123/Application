<?php


namespace User\Controller;



class StatisticsController extends UserController
{
    //充值排名
    public function chargeRank() {

        $successtime = urldecode(I("request.successtime", ''));
        if(!$successtime) {//默认今日
            $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
            $successtime = $_GET['successtime'] = date('Y-m-d H:i:s', $beginToday). ' | '.date('Y-m-d H:i:s', $endToday);
        }
        list($cstime, $cetime)  = explode('|', $successtime);

        $where['pay_successdate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        $where['pay_status'] = ['gt',0];
        $where['parentid'] = $this->fans['uid'];
        //$where['ddlx'] = 1;
        // 下一级
        $list = M('Order')
            ->join('LEFT JOIN __MEMBER__ ON (__MEMBER__.id + 10000) = __ORDER__.pay_memberid')
            ->field('pay_member.id as userid,pay_member.username,pay_member.realname,sum(pay_amount) as total_charge')
            ->where($where)
            ->group('pay_memberid')
            ->select() ?: [];
        foreach ($list as &$item){
            $one = M('Member')->where(['parentid'=>$item['userid']])->select() ?:[];
            $two = M('Member')->where(['parentid'=>['in', $one]])->select() ?:[];
            $third = M('Member')->where(['parentid'=>['in', $two]])->select() ?:[];
            $userids = array_merge($one, $two, $third);
            $userids = array_map(function ($v){return 10000 + $v;}, $userids);
            $item['total_charge'] += M("Order")->where(['pay_memberid'=>['in', $userids], 'pay_successdate'=>['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]], 'pay_status'=>['gt',0], ])->field('sum(pay_amount) as total_charge')->find();
        }
        uasort($list, function($a, $b){
            return $a['total_charge'] > $b['total_charge'] ? -1 : 1;
        });

        foreach($list as $k => $v) {
            $list[$k]['rank'] = $k+1;
        }

        $this->assign("list", $list);
        C('TOKEN_ON', false);
        $this->display("Admin@Statistics:chargeRank");
    }
}
