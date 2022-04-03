<?php
return array(
	// 码支付配置文件 start
    'CODE_PARAM'	=> '', 		// 自定义参数
    'CODE_MIN'		=> 0.01, 	// 最低金额限制
    'CODE_ACT'		=> 0, 		// 是否启用免挂机模式 1为启用. 未开通请勿更改否则资金无法及时到账
    'CODE_OUTTIME'	=> 360, 	// 二维码超时设置 //360秒=6分钟 最小值60  不建议太长 否则会影响其他人支付
    'CODE_PAGE'		=> 4, 		// 订单创建返回JS 或者JSON //支付页面展示方式
    'CODE_STYLE'	=> 1, 		// 付款页面风格
    'CODE_PAY_TYPE'	=> 1, 		// 启用支付宝官方接口 会员版授权后生效
    'CODE_CHART'	=> 'utf8', 	// 字符编码方式
    // 码支付配置文件 end
);
?>
