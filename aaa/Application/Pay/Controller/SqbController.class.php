<?php


namespace Pay\Controller;


class SqbController extends PayController
{
    public function Pay($array)
    {
        $parameter = [
            'code'          => 'Sqb',
            'title'         => '收钱吧',
            'exchange'      => 1,
            'out_trade_id'  => I('request.pay_orderid'),
            'body'          => I('request.pay_productname'),
            'channel'       => $array,
        ];
        $return = $this->orderadd($parameter);
        if('success' != $return['status'])
            die($return['status']);

        if ($array['pid'] == 939) { // 支付宝
            $thoroughfare = 'alipay_auto';
            $type = 1;
        }elseif ($array['pid'] == 940) { // 微信
            $thoroughfare = 'alipay_auto';
            $type = 2;
        }
        $data = [
            'account_id'    => $return['mch_id'],
            'content_type'  => 'text',
            'thoroughfare'  => $thoroughfare,
            'type'          => $type,
            'out_trade_no'  => $return['orderid'],
            'robin'         => 1,
            'keyId'         => $return['appid'],
            'amount'        => $return['amount'],
            'callback_url'  => $return['notifyurl'], // 异步回调
            'success_url'   => $return['callbackurl'],
            'error_url'     => $this->_site . 'Pay_Sqb_payerror.html', // $return['callbackurl'],
            'sign'          => $this->sign($return['signkey'], ['amount'=>$return['amount'], 'out_trade_no'=>$return['orderid'], ]),
        ];
        $this->setHtml($return['gateway'], $data);
    }

    //同步通知
    public function callbackurl()
    {
        die('支付成功');
        $Order      = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        } else {
            exit("error");
        }
    }
    public function payerror()
    {
        die('支付失败！');
    }

    public function notifyurl(){
        // 验签，处理
        file_put_contents("Data/Sqb.txt",json_encode($_REQUEST));
        //商户名称
        $account_name  = $_POST['account_name'];
        //支付时间戳
        $pay_time  = $_POST['pay_time'];
        //支付状态
        $status  = $_POST['status'];
        if ('success' != $status){
            die('error');
        }
        //支付金额
        $amount  = $_POST['amount'];
        //支付时提交的订单信息
        $out_trade_no  = $_POST['out_trade_no'];
        $key = getKey($out_trade_no); // 密钥
        //平台订单交易流水号
        $trade_no  = $_POST['trade_no'];
        //该笔交易手续费用
        $fees  = $_POST['fees'];
        //签名算法
        $sign  = $_POST['sign'];
        //回调时间戳
        $callback_time  = $_POST['callback_time'];
        //支付类型
        $type = $_POST['type'];
        //商户KEY（S_KEY）
        $account_key = $_POST['account_key'];


        //第一步，检测商户KEY是否一致
        if ($account_key != $key) exit('error:key');
        //第二步，验证签名是否一致
        if ($this->sign($key, ['amount'=>$amount,'out_trade_no'=>$out_trade_no]) != $sign) exit('error:sign');

        //下面就可以安全的使用上面的信息给贵公司平台进行入款操作
        $Order      = M("Order");
        $o = $Order->where(['pay_orderid' => $out_trade_no])->find();
        if(!$o){
            \Think\Log::record('收钱吧回调失败,找不到订单：'.json_encode($_REQUEST),'ERR',true);
            exit('error:order not fount:'.$out_trade_no );
        }

        $pay_amount = $o['pay_amount'] ;
        if($amount < $pay_amount){
            \Think\Log::record("收钱吧回调失败,金额不等：{$amount} != {$pay_amount},".json_encode($_REQUEST),'ERR',true);
            exit('error: amount not match!');
        }
        $Order->where(['pay_orderid' => $out_trade_no])->save([ 'upstream_order'=>$_POST['sqbno']]); // 收钱吧的流水号
        $this->EditMoney($out_trade_no, '', 0);

        //支付成功后必须要返回该信息,否则默认为发送失败，补发3次，3次还未返回，则默认为发送失败

        echo 'success';
    }

    /**
     * 签名算法
     * @param string $key_id S_KEY（商户KEY）
     * @param array $array 例子：$array = array('amount'=>'1.00','out_trade_no'=>'2018123645787452');
     * @return string
     */
    function sign ($key_id, $array)
    {
        $data = md5(sprintf("%.2f", $array['amount']) . $array['out_trade_no']);
        $key[] ="";
        $box[] ="";
        $pwd_length = strlen($key_id);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($key_id[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;

            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return md5($cipher);
    }

    public function heartbeat(){
        $key_id0 = I('request.key_id0');
        $key_id1 = I('request.key_id1');
        M('ChannelAccount')->where(['appid'=>['in', [$key_id0, $key_id1]]])->save(['last_monitor'=>time()]);
    }

}
