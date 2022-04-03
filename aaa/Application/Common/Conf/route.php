<?php
return array(
    'URL_ROUTER_ON' => true, // 开启路由
    'URL_ROUTE_RULES'=>array(
        // PayHelper6.6.7 收款精灵登录
        'getui/login'   => 'Pay/Mqali/login',           // 登陆
        'getui/receive' => 'Pay/Mqali/receive_qrcode',  // 上传二维码，已经弃用了
        'getui/bind'    => 'Pay/Mqali/bind_cid',        // 绑定clientid，然后app启动
        'getui/notify'  => 'Pay/Mqali/notify',          // 收到订单的回调
    )
)
;
?>