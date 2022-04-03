<?php
/**
 * Created by PhpStorm.
 * User: luofei
 * Date: 2019/4/15
 * Time: 13:43
 */

namespace Admin\Controller;


class SupplementController extends BaseController
{
    //private $merKey = "PLP092D5N88W556561FF3";

    public function index(){
        die;
    }

    // 补单
    public function bd(){
        $merReqNo = I('merReqNo');  // 平台订单号merReqNo
        $tradeNo  = I('tradeNo');   // 支付宝订单号merReqNo
        $data = ['status'=>0, 'msg'=>'失败',];
        $order = M('Order');
        $order_info = $order->where(['pay_orderid'=>$merReqNo])->find();
        if(!$order_info){
            $data['msg'] = '订单'.$merReqNo.'不存在';
            $this->ajaxReturn($data);
        }
        if(0 != $order_info['pay_status']){
            $data['msg'] = '订单'.$merReqNo.'已支付';
            $this->ajaxReturn($data);
        }

        $res = R('Pay/Pay/EditMoney', [$order_info['pay_orderid'], '', 0, ]);
        if ($res) {
            //$this->ajaxReturn(['status' => 1, 'msg' => "设置成功！"]);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => "设置失败"]);
        }
        // 保存支付宝流水号
        $order->where(['id'=>$order_info['id']])->save(['upstream_order'=>$tradeNo]);
        $data['status'] = 1;
        $data['msg'] = '补单成功';
        $this->ajaxReturn($data);
    }

}

