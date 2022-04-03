<?php
/**
 * Created by PhpStorm.
 * User: luofei
 * Date: 2019/3/27
 * Time: 14:43
 */

namespace TagLib;

/// 自定义标签库
use Think\Template\TagLib;

class Common extends TagLib
{
    public function __construct()
    {
        parent::__construct();

        // 标签定义
        $this->tags = [
            'paging'    => [],
            'refresh_button'    => ['close'=>0],
        ];
    }

    /// 分页
    public function _paging($attr, $content){
        return <<<EOB
            <div class="page">
                <form class="layui-form" action="" method="get">                
                    {$page}
                    <select name="rows" style="height: 29px;" class="pageList" lay-ignore onchange="this.parentNode.submit();">
                        <option value="">显示条数</option>
                        <option <eq name="Think.get.rows" value="30">selected</eq> value="30">30条</option>
                        <option <eq name="Think.get.rows" value="50">selected</eq> value="50">50条</option>
                        <option <eq name="Think.get.rows" value="80">selected</eq> value="80">80条</option>
                        <option <eq name="Think.get.rows" value="100">selected</eq> value="100">100条</option>
                        <option <eq name="Think.get.rows" value="1000">selected</eq> value="1000">1000条</option>
                    </select>
                </form>
            </div>
        </div>
EOB;
    }

    /// 刷新按钮
    public function _refresh_button(){
        return <<<EOB
        <div class="ibox-tools">
            <i class="layui-icon" onclick="location.replace(location.href);" title="刷新" style="cursor: pointer;">刷新ဂ</i>
        </div>
EOB;
    }

}