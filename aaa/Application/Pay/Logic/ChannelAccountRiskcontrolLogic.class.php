<?php

namespace Pay\Logic;

//渠道风控类
use Think\Log;

class ChannelAccountRiskcontrolLogic extends RiskcontrolLogic
{

    protected $autoCheckFields = false;

    protected $m_Channel;

    public function __construct($pay_amount = 0.00)
    {
        parent::__construct();
        $this->m_ChannelAccount = M('ChannelAccount');
        $this->pay_amount       = $pay_amount;
    }

    //监测数据
    public function monitoringData()
    {
        if ($this->config_info) {
            //基本风控规则
            $base_judge = parent::monitoringData();
            if ($base_judge !== true) {
                return $base_judge;
            }

            //判断交易总量
            $the_total_volume_judge = $this->theTotalVolume(function () {
                //如果是新一天，渠道交易量清零,防止定时任务不执行
                $where = ['id' => $this->config_info['account_id']];
                $data  = ['paying_money' => 0.00,  ];
                $res   = $this->m_ChannelAccount->where($where)->save($data);
                Log::record("m_ChannelAccount 1 新一天交易量清0：id={$this->config_info['account_id']},res=$res, error={$this->m_ChannelAccount->getError()},dberror={$this->m_ChannelAccount->getDbError()} ",'ERR',true);
            });
            if ($the_total_volume_judge !== true) {
                // TODO 收款码，额度满，自动下线
                return $the_total_volume_judge;
            }

            //----------------单位时间判断交易操作-----------------
            $unit_timeoperate_judge = $this->unitTimeOperate(function () {
                //如果支付间隔不在单位时间内，将商户风控数据重置

                $data = [
                    'unit_paying_number'     => 0,
                    'unit_paying_amount'     => 0,
                    'unit_frist_paying_time' => time(),
                ];
                $where = ['id' => $this->config_info['account_id']];
                $res   = $this->m_ChannelAccount->where($where)->save($data);
                Log::record("m_ChannelAccount 2 新一天交易量清0：id={$this->config_info['account_id']},res=$res, error={$this->m_ChannelAccount->getError()},dberror={$this->m_ChannelAccount->getDbError()} ",'ERR',true);
            });
            if ($unit_timeoperate_judge !== true) {
                return $unit_timeoperate_judge;
            }
        }
        return true;
    }

}
