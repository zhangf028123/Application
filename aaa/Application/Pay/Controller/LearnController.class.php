<?php

namespace Pay\Controller;

class LearnController
{

    public function dumpUserRisk($memberid){
        $l_UserRiskcontrol = new \Pay\Logic\UserRiskcontrolLogic(10, $memberid); //用户风控类
        $error_msg         = $l_UserRiskcontrol->monitoringData();
        dump($l_UserRiskcontrol);dump($error_msg);
        if ($error_msg !== true) {
            $this->showmessage('商户：' . $error_msg);
        }
    }
}