<?php


namespace Cli\Controller;


use Think\Log;

class AutoNofityController
{
    public function index()
    {
        echo "[" . date('Y-m-d H:i:s'). "] 自动通知下游任务触发\n";
        Log::record("自动通知下游任务触发", Log::INFO);
        //$time = $_SERVER['REQUEST_TIME'];
        $time = time();
        $this->doJob($time);
        Log::record("自动通知下游任务结束", Log::INFO);
        echo "[" . date('Y-m-d H:i:s'). "] 自动通知下游任务结束\n";
    }

    private function doJob($time){
        $Order = M('Order');
        $Notify = M('Notify');
        $list = $Notify->select();
        foreach ($list as $item){
            Log::record("通知订单号：".$item['pay_orderid'], Log::INFO);
            $order = $Order->where(['pay_orderid'=>$item['pay_orderid']])->find();
            $starttime = $time - 10 * 60;
            if(!$order || $order['pay_status'] != 1 || $order['num'] > 60 || $order['pay_successdate'] < $starttime){
                $Notify->where(['pay_orderid'=>$item['pay_orderid']])->delete();
                continue;
            }
            echo("自动通知下游:{$item['pay_orderid']}");
            Log::record("自动通知下游:{$item['pay_orderid']}", Log::INFO);
            $key = 'itxmEHU8f6FASrwurOD';
            R('Pay/Pay/autoNotify', ['trans_id'=>$item['pay_orderid'], 'sign'=>md5($item['pay_orderid'].$key)]);
        }
    }
}
