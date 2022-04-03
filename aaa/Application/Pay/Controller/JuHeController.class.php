<?php
namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use \Think\Log;

class JuHeController extends PayController{

    public function Pay($array)
    {
        $memberid = I("request.pay_memberid", 0, "intval");
        if (!$memberid) {
            $this->showmessage('商户ID不存在！');
        }
        $where['id'] = intval($memberid) - 10000;  // 得到真实的 pay_member.id
        $member = M("member")->where($where)->find();
        if (!$member) {
            $this->showmessage('商户不存在！');
        }
        if ($member['groupid'] != 4) {
            $this->showmessage('商户无权限下单!');
        }
        //支付产品
        $products = M('Product')->where(['isdisplay' => 1, 'status' => 1])->select();
        // 过滤没开启的
        $pids = array_column(M("product_user")->where(['userid' => $member['id'], 'status' => 1])->field('pid')->select(), 'pid') ?: [];
        foreach ($products as $key => $product) {
            if (!in_array($product['id'], $pids) || $product['id'] == 900 ) unset($products[$key]);
        }
        if (!$products) {
            $this->showmessage('用户未分配！');
        }
        //验签
        $requestarray = array(
            'pay_memberid' => I('request.pay_memberid', 0, 'intval'),
            'pay_orderid' => I('request.pay_orderid', ''),
            'pay_amount' => I('request.pay_amount', ''),
            'pay_applydate' => I('request.pay_applydate', ''),
            'pay_bankcode'    => I('request.pay_bankcode', ''),
            'pay_notifyurl' => I('request.pay_notifyurl', ''),
            'pay_callbackurl' => I('request.pay_callbackurl', ''),
        );
        $md5key = $member['apikey'];
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        if ($md5keysignstr != I('request.pay_md5sign')) {
            $this->showmessage('验签失败！');
        }

        //创建新的md5
        $requestarraynew = array(
            'pay_memberid' => I('request.pay_memberid', 0, 'intval'),
            'pay_orderid' => I('request.pay_orderid', ''),
            'pay_amount' => I('request.pay_amount', ''),
            'pay_applydate' => I('request.pay_applydate', ''),
 //           'pay_bankcode'    => I('request.pay_bankcode', ''),
            'pay_notifyurl' => I('request.pay_notifyurl', ''),
            'pay_callbackurl' => I('request.pay_callbackurl', ''),
        );
 //       $md5key = $member['apikey'];
        $md5keysignstrnew = $this->createSign($md5key, $requestarraynew);
        $postargs = array(
            'pay_memberid' => I('request.pay_memberid'),
            'pay_orderid' => I('request.pay_orderid'),
            'pay_applydate' => I('request.pay_applydate'),
            //       'pay_bankcode' => I('request.pay_bankcode'),
            'pay_notifyurl' => I('request.pay_notifyurl'),
            'pay_callbackurl' => I('request.pay_callbackurl'),
            'pay_amount' => I('request.pay_amount'),
            'pay_md5sign' => $md5keysignstrnew, //服务器生成 减少一个bankcode
            'pay_productname' => I('request.pay_productname'),
            'pay_productnum' => I('request.pay_productnum'),
            'pay_productdesc' => I('request.pay_productdesc'),
            'pay_producturl' => I('request.pay_producturl'),
            'pay_attach' => I('request.pay_attach'),
            'content_type' => I('request.content_type'),
        );

        $this->assign('products', $products);
        $this->assign('postargs', $postargs);
        if (isMobile()) {
            $this->display('JuHe/index_mobile');
        } else {
            $this->display('JuHe/index_pc');
        }
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