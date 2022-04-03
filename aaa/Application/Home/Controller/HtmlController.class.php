<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 19:28
 */

namespace Home\Controller;


use Org\Util\HttpClient;
use Think\Controller;

class HtmlController extends Controller
{
    public function ip(){
        echo get_client_ip();
    }
    public function index(){
        echo $this->get_outer();
        $this->display();
        echo 'f';
    }
    public function get_outer()
    {
        $a  = HttpClient::get('http://120.78.200.245:39501/Html_ip', []);    // 还是curl靠谱
        echo $a;
        die();
    }

    public function list1(){
        $list = M('order')->limit(10)->select();
        $this->assign('list', $list)->display();
    }

    public function test1(){
        echo 'test1:';
        $this->display();
    }

    public function testsql(){
        $url = "http://www.baidu.com";
        $tbh5="taobao://";
        $append="www.alipay.com/?appId=10000007&qrcode=".urlencode($url);   // 浏览器唤起淘宝app，再唤起支付宝app
        header("location: $tbh5$append");
    }

}
