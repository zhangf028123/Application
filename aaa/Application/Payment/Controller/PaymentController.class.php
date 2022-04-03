<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Payment\Controller;

/**
 * 代付
 * Class PaymentController
 * @package Payment\Controller
 */
use Think\Controller;

class PaymentController extends Controller
{
	protected $verify_data_ = [
				'code'=>'请选择代付方式！', // Controller
				'id'=>'请选择代付订单！', 
				'opt' => '操作方式错误！', // Action
			];

	public function __construct(){
	    parent::__construct();
	}

	/// 查找代付通道
	protected function findPaymentType($code='default'){
		$where['status'] = 1;
		if($code == 'default'){
			$where['is_default'] = 1;
		}else{
			$where['id'] = $code;
		}		
		$list = M('PayForAnother')->where($where)->find();
		$list || showError('支付方式错误');
		return $list;
	}

	protected function selectOrder($where){
		
		$lists = M('Wttklist')->where($where)->select();
		$lists || showError('无该代付订单或订单当前状态不允许该操作！');
		foreach($lists as $k => $v){
			$lists[$k]['additional'] = json_decode($v['additional'],true);
		}
		return $lists;
	}

	protected function checkMoney($uid,$money){
		$where = ['id' => $uid];
		$balance = M('Member')->where($where)->getField('balance');
		$balance < $money && showError('支付金额错误'); 
	}

	protected function handle($id, $status=1, $return){
	    
	    //处理成功返回的数据
        $data = array();
        if($status == 1){
           $data['status'] = 1; // 1:申请成功！,2:代付成功,4:代付失败！
           $data['memo'] = '申请成功！';
        }else if ($status == 2) {
           $data['status'] = 2; // 1:申请成功！,2:代付成功,4:代付失败！
           $data['cldatetime'] = date('Y-m-d H:i:s', time());
           $data['memo'] = '代付成功';
        }else if($status == 3){
            $data['status'] = 4;    // 1:申请成功！,2:代付成功,4:代付失败！
			$data['memo'] = isset($return['memo'])?$return['memo']:'代付失败！';
        }
        if(in_array($status, [1,2,3])){
        	$data = array_merge($data, $return);
        	$where = ['id'=>$id];
        	M('Wttklist')->where($where)->save($data);
        	if($status == 2) {  // 打款成功，减额度
                $withdraw = M('Wttklist')->where($where)->find();
                $this->SubDjAmountWaterOrder($withdraw);
            }
        }
   
	}

    /// 打款成功，减少码商冻结额度
    private function SubDjAmountWaterOrder($withdraw){
        $own_member = M('Member')->where(['id'=>$withdraw['from_id']])->find();
        if(!$own_member){
            $this->ajaxReturn(['status' => 0, 'msg' => '系统错误，无码商']);
        }
        if( !M('Member')->where(['id' => $withdraw['from_id']])->save(['dj_amount_water'=>['exp', 'dj_amount_water-'.$withdraw['tkmoney']]])
        ){
            $this->ajaxReturn(['status' => 0, 'msg' => '系统错误，不能确认收款']);
        }

        // 额度变更记录
        $arrayRedo = array(
            'user_id'  => $own_member['id'],
            'ymoney'   => $own_member['dj_amount_water'],   // 旧值
            'money'    => $withdraw['tkmoney'],
            "gmoney"   => $own_member['dj_amount_water'] - $withdraw['tkmoney'],    // 新值
            'type'     => 2, // 1增加，2减少，3订单成功增加
            'remark'   => 'Payment/SubDjAmountWaterOrder:码商给商户打款:减少dj_amount_water',  // 备注
            'ctime'    => time(),
        );
        $res = M('AmountWaterOrder')->add($arrayRedo);
        return $res;
    }

}