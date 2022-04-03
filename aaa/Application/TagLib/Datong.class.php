<?php


namespace TagLib;

defined('THINK_PATH') or exit();

use Think\Template\TagLib;

class Datong extends TagLib
{

    protected $tags = [
        // 管理员安全码
        'safecode'  => [
            'attr'  => 'name',
            'close'=>0,
        ],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function _safecode($tag, $content){
        $name = isset($tag['name']) ? $tag['name'] : 'safecode';
        $str = <<<PHP
				<div class="layui-form-item">
					<label class="layui-form-label">安全密码：</label>
					<div class="layui-input-inline">
						<input type="password" class="layui-input" name="$name" id="$name"  value="" placeholder="安全密码">
					</div>
				</div>
PHP;
        return $str;
    }
}
