<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/13
 * Time: 15:00
 */

namespace Home\Controller;


use Think\Controller;

class CommonController extends Controller
{
    // 获取商户开放的支付产品
    public function memberProductids($memberid){
        $data = ['code'=>0,'msg'=>'error',];
        $memberid -= 10000;
        $result = array_column( M('product_user')->where(['userid'=>$memberid, 'status'=>1])->field('pid')->select(), 'pid') ;
        if($result){
            $data['code'] = 1;
            $data['msg'] = 'ok';
            $data['data'] = $result;
        }
        $this->ajaxReturn($data,'JSON');
    }
}
