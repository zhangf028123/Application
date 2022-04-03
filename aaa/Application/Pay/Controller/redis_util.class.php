<?php
/* *
 * 类名：redis 金额检测类
 * 功能：判断金额
 */

class MoneyCheck {

    public $redis;

    function __construct(){
        $redis = new \Redis();
        $redis->connect('127.0.0.1',6379);
        $this->redis = $redis;
    }

    public function checkAccountMoney($id,$amount1){
        if(empty($amount1)) return false;
        $amount = sprintf('%.2f',$amount1);
        $redisKey = md5($id."_".$amount);
        $keyValueJson = $this->redis->exists($redisKey);
        if(!$keyValueJson){
            return  true;
        }
        else{
            return false;
        }
    }

    public function setAccountKey($id,$amount1, $value = false){
        if(empty($amount1)) return false;
        $amount = sprintf('%.2f',$amount1);
        $redisKey = md5($id."_".$amount);
        $keyValueJson = $this->redis->exists($redisKey);
        if(!$keyValueJson){
            $result = $this->redis->set($redisKey,$value?:'y',299);
            return  $result;
        }
        else{
            return false;
        }
    }

    /// @return false | string
    public function getAccountKey($id,$amount1){
        if(empty($amount1)) return false;
        $amount = sprintf('%.2f',$amount1);
        $redisKey = md5($id."_".$amount);
        return $this->redis->get($redisKey);
    }

    public function deletAccountKey($id,$amount1){
        if(empty($amount1)) return false;
        $amount = sprintf('%.2f',$amount1);
        $redisKey = md5($id."_".$amount);
        $keyValueJson = $this->redis->exists($redisKey);
        if($keyValueJson){
            $result = $this->redis->del($redisKey);
            return  $result;
        }
        else{
            return false;
        }
    }
}
