<?php


namespace Home\Controller;

use Think\Cache;
use Think\Controller;

class MyyController extends Controller
{
    // 支付宝
    public function xd($pdx){
        $cache      =   Cache::getInstance('redis');
        echo $cache->get($pdx);
    }
    // 微信
    public function llq($pdx){

    }
}