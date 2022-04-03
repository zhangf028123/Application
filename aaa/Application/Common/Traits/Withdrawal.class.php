<?php

namespace Common\Traits;

trait Withdrawal
{
    /**
     * 生成订单号
     *
     * @return string
     */
    private function getOrderId()
    {
        $year_code = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $i         = intval(date('Y')) - 2010 - 1;

        return $year_code[$i] . date('md') .
            substr(time(), -5) . substr(microtime(), 2, 5) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT);
    }

    /// 获取一个可以提款的码商Member
    private function GetMashangMember($tkmoney){
        // 选码商
        $mashang_members = M('Member')->where(['amount_water'=>['egt', $tkmoney]])->select();
        if(!$mashang_members){
            $this->ajaxReturn(['status' => 0, 'msg' => "系统当前暂无提款额度1！"]);
        }
        // 随机选一个
        $mashang_member = $mashang_members[mt_rand(0, count($mashang_members) - 1)];
        if(!$mashang_member){
            $this->ajaxReturn(['status' => 0, 'msg' => "系统错误2！"]);
        }
        return $mashang_member;
    }
}
