<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/7
 * Time: 13:44
 */

namespace Common\Model;


use Think\Model;

class BankcardModel extends Model
{
    public function __construct($name = '', $tablePrefix = '', $connection = '')
    {
        parent::__construct($name, $tablePrefix, $connection);

        \Think\Log::record('实例化BankcardModel','ERR',true);
        // 自动完成定义
        $this->_auto = [
            ['updatetime', 'time', self::MODEL_UPDATE, 'function'],
        ];
    }
}
