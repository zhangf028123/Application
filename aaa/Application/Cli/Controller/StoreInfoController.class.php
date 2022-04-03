<?php


namespace Cli\Controller;

use Think\Log;

class StoreInfoController
{
    public function index()
    {
        echo "[" . date('Y-m-d H:i:s'). "] 自动获取店铺流水任务触发\n";
        Log::record("自动通店铺流水任务触发", Log::INFO);
        $time = $_SERVER['REQUEST_TIME'];
        $this->doJob($time);
        Log::record("自动店铺流水任务结束", Log::INFO);
        echo "[" . date('Y-m-d H:i:s'). "] 自动店铺流水任务结束\n";
    }

    private function doJob($time){
        $channels = M('Channel');
        $channel_accounts = M('ChannelAccount');
        $pdd_channels = $channels->where(['code'=>'Pdd'])->select();
        foreach ($pdd_channels as $channel){
            $url = $channel['gateway'];
            $arr = parse_url($url);
            $port = empty($arr['port'])?80:$arr['port'];
            $url = $arr['scheme'].'://'.$arr['host'].':'.$port;
            $response = $this->http($url.'/Store/GetAllStoreAmount');
            //echo "[" . date('Y-m-d H:i:s'). "] 返回内容 $response \n";
            Log::record("自动店铺流水任务结束 $response", Log::INFO);
            $response = json_decode($response, true);
            if($response['result']=='ok'){
                foreach ($response['list'] as $item){
                    $channel_accounts->where(['appid'=> $item['StoreId'] ])->save(['pdd_amount'=>$item['Amount']]);
                }
            }else{
                Log::record("获取拼多多店铺流水错误", Log::INFO);
                echo "[" . date('Y-m-d H:i:s'). "] 获取拼多多店铺流水错误\n";
            }
        }
    }

    protected function post($url,$parac = []){
        $postdata=http_build_query($parac);
        $options=array(
            'http'=>array(
                'method'=>'POST',
                'header'=>'Content-type:application/x-www-form-urlencoded',
                'content'=>$postdata,));
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }

    private static function http($url, $method = 'GET', $postfields = NULL, $headers = array( 'Accept-Charset: utf-8'))
    {
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_ENCODING, "");
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, FALSE);

        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
        }

        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);

        $response = curl_exec($ci);
        $httpCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $httpInfo = curl_getinfo($ci);

        curl_close($ci);
        return $response;
    }
}