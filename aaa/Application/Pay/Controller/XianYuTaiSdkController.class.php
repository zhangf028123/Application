<?php

namespace Pay\Controller;

use \Org\Util\HttpClient;
use Think\Cache;
use Think\Exception;
use Think\Log;

class XianYuTaiSdkController extends PayController
{
    public function Pay($array){
        $body        = I('request.pay_productname');
        $parameter = [
            'code'      => 'XianYuTaiSdk',
            'title'     => '咸鱼代付(tai-sdk)',
            'exchange'  => 1,
            'gateway'   => '',
            'orderid'   => '',
            'out_trade_id'  =>  I("request.pay_orderid"),
            'body'          =>  $body,
            'channel'      => $array,
        ];
        $return = $this->orderadd($parameter);

        $params = [
            'money' =>  sprintf('%.2f', $return["amount"]),
            'outTradeNo' =>  $return['orderid'],
            'userAgent' =>  "AlipayClient",
            'appId' =>  $return['mch_id'],
            'notifyUrl' =>  $return['notifyurl'],
            'channelType'=> $return['appid'],
            //'channelType' =>  "tb",
            'returnType' => "app"
        ];
        $sign = $this -> getSign($params);
        if (false === $sign){
            return json_encode(array('code'=>2001,'msg'=>'签名失败'));
        }
        $params['sign'] = $sign;
        $url = $return['gateway'];
        $res = $this -> curl_request($url, $params);
        if (!$res){
            return json_encode(array('code'=>2001,'msg'=>'请求失败'));
        }
        Log::record('XianYuTaiSdk pay url='.$return['gateway'].',data='.json_encode($params).',response='.$res,'ERR',true);

        $res = json_decode($res, true);
        $res = $res['result'];
        Log::record('tesing...'.json_encode($res), 'ERR', true);
        # 验签
        if ( isset($res['url']) && !empty($res['url'])) {
            $return = [
                'result' => 'ok',
                //'url' => $response['pay_url'],
                'orderStr' => $res['url']
            ];
            $this->ajaxReturn($return);
            //header("location: {$res['url']}");
        }
        echo json_encode($res);
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_REQUEST;
        $clientip = $_SERVER['REMOTE_ADDR'];
        Log::record(" XianYuTaiSdk \$response=".json_encode($response),'ERR',true);
        $ip = getIP(); // 可能是伪造的
        if($clientip != $ip){
            Log::record("伪造的ip，XianYuTaiSdk notifyurl， clientip={$clientip}, getIP = $ip",'ERR',true);
            die("not ok1");
        }

        # 验签
        if (isset($_POST['sign']) && (2 === intval($_POST['status']) || 10 === intval($_POST['status']) || 11 === intval($_POST['status']))) {
            $resSign = $_POST['sign'];
            unset($_POST['sign']);
            # 验签
            $res = $this -> _verify($_POST, $resSign);

            if (!$res) {
                Log::record('XianYuTaiSdk回调失败,验签失败：'.$res,'ERR',true);
                exit('error:check sign Fail!');
            }
            try{
                $Order      = M("Order");
                $o = $Order->where(['pay_orderid' => $_REQUEST["outTradeNo"]])->find();
                if(!$o){
                    Log::record('XianYuTaiSdk回调失败,找不到订单：'.json_encode($response),'ERR',true);
                    exit('error:order not fount'.$_REQUEST["outTradeNo"] );
                }

                $pay_amount = $o['pay_amount'] ;
                $diff = $response['money'] - $pay_amount;
                if($diff <= -1 || $diff >= 1 ){ // 允许误差一块钱
                    Log::record("XianYuTaiSdk回调失败,金额不等：{$response['money'] } != {$pay_amount},".json_encode($response),'ERR',true);
                    exit('error: amount error!');
                }
                $old_order = $Order->where(['upstream_order'=>$response['orderSn']])->find();
                if( $old_order && $old_order['pay_orderid'] != $response["orderSn"]){
                    Log::record("XianYuTaiSdk回调失败,重复流水号  ：".json_encode($response).'旧订单号'.$old_order['pay_orderid'],'ERR',true);
                    //die("not ok2");
                }
                $Order->where(['pay_orderid' => $response["outTradeNo"]])->save([ 'upstream_order'=>$response['orderSn']]);
                $this->EditMoney($response['outTradeNo'], '', 0);
                exit("success");
            }catch (Exception $e){
                Log::record('XianYuTaiSdk回调回调失败,发生异常：'.$e->getMessage(),'ERR',true);
                exit("Exception");
            }
        }

    }

    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");

        $pay_status = $Order->where(['pay_orderid' => $_REQUEST["orderid"]])->getField("pay_status");
        if ($pay_status > 0) {
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        } else {
            exit("error");
        }
    }

    # 加签
    protected function getSign($params)
    {
        if (!$params || !is_array($params))
            return false;

        $params['charset'] = "utf-8";
        ksort($params);
        $privateKeyHeader = "-----BEGIN RSA PRIVATE KEY-----\n";
        # TODO 私钥
        $privateKeyContent   = 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCO9TEJ5lbDzUBVXomP2RyZbfGQJUAESzDhrmnfvlIbo3YW0a9Y/FE5RRkp0TG2h5Q9wFJTHUtKNcd67EG98eCCnMgO1PGfMCRHReLv/ubFCF7hanuJ0xxIV572QvEJM5Cmm9aqprcOgAdxzRLwW4j0hvxPOaV54AfDNnETkZEY8iucKrFJb5r6tB+X6M7h9F2UTYI1PtnH4gVoAvGbI6GeMDYROZDSWx4xCMhBlQehDveaFsfo+KhtTO/Nj9GWTSmTC1WALDkgPHdiEfTwv31W/u65glNSn/2RnLiIAqQXnzUG6qZ19gE+HDj6G5YJZI27F3QujNPGwX9LCEOJcOLRAgMBAAECggEAM10feHodNI67jbDA5kFQyEO7Zlor7R8C+iNLgQuFvs51cG94fBV0LYqJJwA1x2phNeqy6Y/97DT0blMSr+eHDga8zq9t/Q80CbOvfSFt0t9WD9HNf1CtucRZpguZRQAU+ffGIlxyKzCO/DF9m4SX6ZvSbbi9ntOCln4qFKjZpEdcjTeVMgp+On7qQToi2WKqVQzxCPF0pWtq/UVP8BbifIydiuhKx1EkLfyG41xhXhHow3PJ6SIOY/0y1VbG0f/yRsok93EimsScLylvmI8V4v6BuXL0asyl/E7yeWfocoR2fQPeP+8JUan0jKt2aPkdVyASTIaFlDyRM+yJ6wox/QKBgQDSs10SzRnmN37E6nnu9h25GKF/8a07I21zAZDzO2zkqIbFfj1SVfa8YlAABQKc4Ic6G2bW7G6t3BSHqFFUqMytlJ418+NWCnoN9qCd0G6x3Y4ZYY7kcrzQ4XYgme/Kozgq9+uYsl0u0N9wsJmWE1Q5ht9WNZyBpk302V4qjW0nWwKBgQCtsV213ULMB0lZrPus7S5Txf0y9dcpITLTQRjdH5JaxWD3gppsIVoXhMN+CYwdQKFpF7PwMZVNNDG2MFZv34BfZJAckA5fxotsIRgYg+I5GDheMDTyzrLA6mnydMsxy3+APdQV0n/KzEP3Y18yt5wroKaacumJHowS5wSsQmiiQwKBgFVJhUM3AmTN0eKjhvfTZ/Sko7rGePiXPpZu692JEGr+TI9kqi3J3jYjFX7siEI4IITE0rvsuGBn1WeUGlfKfA9HOUNdlYgedYlxa1J2LFiS9hIQC2ctioCCwu4vYYEE0JeUAymsCUwKAGf9FC5LZKxki8K5sMBKMQQLeLKAzM5BAoGAdezY0dPCOSGtVDwUt0hkzq26hXHRvfPzPOiSh/aaeBRQDFtYmpdeQl96FsVGosmxjNdx7F7NnhgUtDxHQzc8QgfVq/7GM5JqMGSbpnFMEzV1d8VE/aEGTeIZQmq3VjxyeVABATvkVXyvnHOUO6vbVOeClZP1KIpb0a2VR5rizV8CgYAekKCtsD4wdTrCsaCVwaU4+xCR5qmlnwGPVGTRKOIUI/jO1lJYmPKvjx8rMsMoIz8WRcMVDiu1gAt2n3K5w/KOfz95wdSXsvpkWSkICzT70ijbqOcNscFtHeaV9QuXVbpdxd0MXHMB7cOEIfn2+iAd7G3i1NLnQpgKncXnTG8Tmw==';

        //$privateKeyContent = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDJxEdAZY823O4BlKeMO2EqV2yTUhz/BtcntTGbaLkdfX7bdoyw/yDe3t46PXlQ21YXx13rGRugrIKjdGrd9i082iZ4mDVU7w9al+pNc4qu1QNFCPTEoMW3a/KlRsNi5BsDmDlSIfZgySXem6xf8oEMtl4vSU65A/pev18wG/VaOBM7Dqpy3TthnUrNjKbdC82Svk4VPcIz8ULybGwN+BeLQlRwWchttsfZh+s0ZNzR6cJwjU8FcopQzdtevs9zcH6yNU7FttgIBFr6tXdrNhAHDDnZk/MIl/35/OePscle7xotPUKu3/lsZhB/LHJInzs1Z1IOyE4bnC5r7STBE2v1AgMBAAECggEAcfgf8z0qz2euPVBV7/1enkcxummonVmk0PYlpk5aOlE4BgmJ4TTLwXiMeMNespCiqd3grDrLg0/OnzvUXbp/xR7ImlgH0AkVWCv1mnhIfwctyKINeMADzt73+enl1gdZRweGYWFzxSn57StsC+p0gNTGkNlM0W9wznI093zjc2i2WmZLRpbg5rEn3hqaljvAdrB7ej+f4EqM04+gLMHUsOGQRw+2wXZ4z2itnAKz0+IMcbqBZJnajs2Q1wmnJ9kG22BQCZR4r60AKLKN8X5p3lsSn6dn0+7NqbsGjCXkezfZ7ELpoQC3EBEz/YD1WNSyD+g8DzUTbu+vtYchAM2IIQKBgQDtjkZy2OZD9f1szf2Yi/Gg5z4ypMcIl3zS2u9MVP1UWI/8qK7lK46psBT8r+a1CGy9NBp8/6fKwDlE9wHDNoSZsdWpBFrdtRTXnCEEUToD9xBb51qXz36pvhSrze80sxk8DCe3FMZhHIa5ao2c9gqgIqT7FHdfB0WqbyGry4OviQKBgQDZbqZjs5eNQHHMz9GayjAgPzL/6wKiyoBWoOvxy4M/fWwfhqnDBIvO0NiLxnFHthWujbRV9AndH5imOTHTh0uD7z//+VQpYAdPYb3/56w7/pQKL4kWUqkjDFhtKn2DOttUcYgiO6sStywe/7q+p8xbslmWA+Zgovk3RFQYgtTyDQKBgANF7ZSRhoKzNf20zOS4MOkdpo6+FHv60l55Y8iydxcdbUZ28In50qpl4bQlnFEsP4tP13mXcDFr+p4HpT+SVbtkZuRAShw57YKXBSFxGRKK6e/KPUZQNpeSJejEGqKoQRXBuR5dq+oUoYqWfEMIZNcWAx3uh0KI8B3MEDO0xyi5AoGBAMBBEma9+ZcwEG+kohVYCc25lAKtbhs7KtduWrHl3RtabyxBCgIiFuy7xX1x7eAWpH0/VuFZcw9OTM9Naz06OA9kkRZpA33CJzSNnE6WLritlYCcH37ZgCw5UkbUdz594El+SXzbfZyC0kRmolzKSewaexQmRqHT+MBhbmpt5JRRAoGAVy9wBOFHW9rdffIglNzHHmfcABs9f/Z+CpCAp1N2ycbMNBCvzZe6aKe0VdXApdZCYVJm8JmEEszX6OIGj3+ZsNisa8v1G25ZkfvUvJzRLUFmS9Cy8GU93JSJqFSrLIIwK8brVco6e8YVWtPIPh8ejZsFjHtdDpORr+m0usVXg8k=';
        //$privateKeyContent = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCKzDAkaL5VTVPazqNUWs9DNTV5OTl1iquWC6JHGUu8igqXnExwVtp/Pg5In1+RVkBXcL5nIuryqvRP9f51xUcYC91fb1mhW/EwGiws4FZXnRPg6xS0pCcswC1NHnY+sG12zigu8JxzEXYNHmU9VON60XhZpf2CnJI/CkeLHEAHq1hCsRrjZEpfa4Z9T4+FSk8pYjLX090Fb6lLGFdutYxXZMVyf81hk6Jpb+OZQbk4EsO/SyMtmevDsYqftM7xXgvhBlMySG3kKIBjqo+WwwgRRdFGh5fLkzosAgb6mQUW14yXW2f9uI1yTC0uvgGR9idz1ODmw/+rb8PfEMZLfYaLAgMBAAECggEAfZ7Ol7VhRq0lDu1k7+z8l5Rp4FyFf7fREo1XA0iGvo6lhZCFsks5mF5e6SWthkntE/bLrWbyBE3rrw8yjf7OGJpscnrCQYOIaIPXpYopO0TEgBXj+JZ1ilAo8Bp45MYjXJq7zbghsReFo9v1vyu8cyVw6pqMNpFk3796/xHDdewoIsN5gNQYs1s2s2tqGe3P8OzV+kq4jwOEDQTCn5XTWYCRlmiZBuoPu1RyQBwMm+cniUS729FW4rc08rReqsBzmjq37MvETP7oAftDSH/AoeZUFnE7KMtV9PgXaIaoawDLWkULJnFeBv45lwm7ShiT+WYYmTIxOqfm8T/p6+RMGQKBgQDj8Y5jP9GHXb61PvvuGDsI3Q1f55+F1by6OmfBS+Spa7q9BeavFvSlrYDanDPn6SIbirqfDo8odjypqIdXUPAIb0uW284bAr04oGLZcW7jWTrXSgIiu8T2ReKDUIqyS5hCTSpvz3SJq00Vt9zDEp3jvt0A1N3WMoMZfZKRQy8aDQKBgQCb4apJ234J47xawDtO2P9VAyjsMu4hJuKvZOYfeNf6T8FpdsLoXsfJkAkYuNW953L7zaMFtZTInGYmYRJ4L+4Pwk6BEsrR520Lp7r1e7dlhABeNBrdKGVUGp0FLb1EF0sNzTebBfuXW/6cLavm6jDWiz7qaN7YOtFJdh+yKlP09wKBgQCyMIM+0wTY7U/IIBSRbB+6LhPmhQsPdKa0bjPtx4ZZav3wBkzvUuKtIZU5UCLDiFxBa5G4BYmj98o0Vqp2d68s6NQOeoYEV3/UZIzhrusgjwCQjCpfPKocW0VXof7Koo3ERs6F2Ycgl+kKsVDCrir/fpUFsWI5sAMMSj3pkLuhgQKBgBJDJ+4rNuL1uVVwfAJKze8pxZuWg+uCYMBns8YwJC2BNomijkcnA4ULyOUKkpADE6eIefbrRRkTqawJZ16JTvytqQDaMMTStiXNasvjyKKuClT/Cb6rQuPNkmPO7gOSLwrY5hDVbJpY/sVRXHhbK4mXGrHfw2vVyDsxCZmX9+oVAoGBAMtFa3/TQZ4xMWE7OQaoVvISLzZOvWUbLI+qocg4SZAfdelzYstwU+Ae8+IIY1GaiwvBqjo+MtZEB3RGokMjAubm8DoMsfIeNuMbiG7oDmZWVUNcN4zJrwXKpbague8P4Ug1+FuaBDgEMSbxauLmI1tukG8onKfEJHPGa4M3nFoh';
        //$privateKeyContent = "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDcrvo7YZqx+0u1AGBpqj+lPEXdTKlciTFXKc0/R2GtTB2Bz/fhP2w7LayXwBfuPu7RkRaB9B+6ZYecpgZxm8atIXWbG+Ovln5SNGf0uhkZ8Ne/N9docHknaV6bDw3ox3L7LaCcQdN74HboIqy2wSUq0rDZx7NYckE9gIKiwiiMoS3twfPuoz/yR1VoatbKhna7o0TbZIsNqNBDIUigvBV1tD+6/dag857vtgyPVVBHL3UsI1eTK3II1SN1yWq6Z9QKouB2yPkyjU2kKMkQW7ctN2NpnY+GrDMI3bCOlVYvGswEH19/RniWXTCR3rjkd761PMRQVyKHVmVeOF//W0jdAgMBAAECggEAZsmFObVLdUzDxWmIfo7SmCbLr7iuRbCM77lDpQ6dWzdRow33uc1tnt62PfQ18T0UxT2byymmwY3arUbTR4Uz853bBkTmNvmUmrtYFC+900xPYPwkc7u3Ynj8nKJVay/CyWVGIqGyXEd/er3zzieGJTz/LGCMk0ycQIYj0qU2d9EZe9GhFJx0jmO0H1+dGVFDg8DvNyfbnLhF8a/mXcYxIowlUU1FBKT59qQWy9W1vi/evBilsNTFMfobK20gycG4P8jpiVU8fF9OjT4DbUeingcsJZO1JLfQK99iRbwecHt7R19WBgolgFLvUQI1ndQLHLhN8JCWCr3Yvs0XGh/AzQKBgQDyJ9FASQgWkOmfKIILFD7UlgGNS1TDPkTHafUUeQ42lBy5P3AQ/3HfAaDp7mjaOIbnwKYLygilElVB9v0CTrSyvNz6YPmqia+MxOJF9xbW0+XsE4p40LJnC5xE5N9aWCldJ//H+yqoxmIpG3DUtgYuDwHQ/HmXieYr3bytNx5aPwKBgQDpTOVdK8UZ+bsmmHCwD+VJFwKZVBJoTrMoAThHqjYgwoi5YqYuv7T4RAtgxhJp9vYR8/6RgfJmypwvLPW35I3m7dZ1pPhtmdj9j6gxeENgQ3+GEtLqwvvTypq6WD+03hBfg39UYOAPsoX2zInDVtyArux0f6WzN7WNYyX65X/94wKBgHHHRamH5xX7RtnyhY/BFnh6dwY1lAEc2+I6pd3Vt1jrpMdhyD0kWROoRzxDOd722mQ//4Tgi8lL4OVasKyJ4Jtu5AF82TJI+UQSKiMnowKTk5+l2tVDcd+689TnGQ7xB5Zuud7z46pjdDHOY+h+Ek9H29mfF16de5m22PUmRcuvAoGAHXYMcNuaJFGZBKKzeEhJmBVDysEHIgzuKtsclFA2TaTDR0Xh3n1vCPruxOW+ZYs+P74kI5PZXImxd3OiA/rkwIjAUFLo3E5VTI/bmYOqXnmnnPK7l9HPo0suhuGmCd0RDD660Zj6MoFE0Ym6y+QoLEfqrDuCXUNntdcTcL+SjikCgYEAswSbrgkIn7en9kXpFrxjJGFzNqRk51+6pOhHjoijTuXVoRGWsXIaAJSTG8YYmAjYTHzKtBbZBYWJHBKQU/RZQtR3S6XY3E7l1MAGGpCJfZf86qebeYpvug18MIjdHBMSM2AuA+OSwl6Nf9rig8VrTsW8zxMzR+f8zlnrdi8wkkY=";
        $privateKeyContent = wordwrap($privateKeyContent, 64, "\n", true);
        $privateKeyEnd = "\n-----END RSA PRIVATE KEY-----";

        $privateKey = $privateKeyHeader . $privateKeyContent . $privateKeyEnd;


        $keyStr = '';
        foreach ($params as $key => $value) {
            if (empty($keyStr))
                $keyStr = $key . '=' . $value;
            else
                $keyStr .= '&' . $key . '=' . $value;
        }

        if (!$keyStr)
            return false;

        try {
            $key = openssl_get_privatekey($privateKey);
            openssl_sign($keyStr, $signature, $key, "SHA256");
            openssl_free_key($key);
            $sign = base64_encode($signature);
            return $sign ? $sign : false;
        } catch (\Exception $e) {

            return false;
        }
    }


    //验签
    protected function _verify($params, $returnSign){

        if (!$params || !is_array($params))
            return false;

        $params['charset'] = "utf-8";
        ksort($params);
        $publicKeyHeader = "-----BEGIN PUBLIC KEY-----\n";
        # TODO 公钥
        $publicKeyContent   = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAt3luIDngKRrNcYPqBt/CwH0L2r37irT9lpB/qOFKo83FsH/D4RFqvMFVLAa/M1mX5OTJoP2nMC/rzdRGf6NylKMHSzYHAF07TlPpkkRBJNRR29SZ29YX7Zl/coQBYCFK+DUgIItw6ZAQvlle09isa+TJLJKVUcIHuJwbfooi/qxfeBPp3Q+p5szTIE87QBmViFnL5NQAHA0pFcBEZxpKlmLK0gM2zcL4drcTmMJJ3vvual5j1xH5dtKK2QZjQwIfcdsTERxBN/HjQ2kPr7zqTp+KqiVxjhF78oE0YnUObf4Z64y9BSuvRy/XuFy/8uCruUSi27sEXnSOUfJmDAifLwIDAQAB';

        //$publicKeyContent = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAt3luIDngKRrNcYPqBt/CwH0L2r37irT9lpB/qOFKo83FsH/D4RFqvMFVLAa/M1mX5OTJoP2nMC/rzdRGf6NylKMHSzYHAF07TlPpkkRBJNRR29SZ29YX7Zl/coQBYCFK+DUgIItw6ZAQvlle09isa+TJLJKVUcIHuJwbfooi/qxfeBPp3Q+p5szTIE87QBmViFnL5NQAHA0pFcBEZxpKlmLK0gM2zcL4drcTmMJJ3vvual5j1xH5dtKK2QZjQwIfcdsTERxBN/HjQ2kPr7zqTp+KqiVxjhF78oE0YnUObf4Z64y9BSuvRy/XuFy/8uCruUSi27sEXnSOUfJmDAifLwIDAQAB';
        //$publicKeyContent = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAiswwJGi+VU1T2s6jVFrPQzU1eTk5dYqrlguiRxlLvIoKl5xMcFbafz4OSJ9fkVZAV3C+ZyLq8qr0T/X+dcVHGAvdX29ZoVvxMBosLOBWV50T4OsUtKQnLMAtTR52PrBtds4oLvCccxF2DR5lPVTjetF4WaX9gpySPwpHixxAB6tYQrEa42RKX2uGfU+PhUpPKWIy19PdBW+pSxhXbrWMV2TFcn/NYZOiaW/jmUG5OBLDv0sjLZnrw7GKn7TO8V4L4QZTMkht5CiAY6qPlsMIEUXRRoeXy5M6LAIG+pkFFteMl1tn/biNckwtLr4BkfYnc9Tg5sP/q2/D3xDGS32GiwIDAQAB';
        //$publicKeyContent = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAt3luIDngKRrNcYPqBt/CwH0L2r37irT9lpB/qOFKo83FsH/D4RFqvMFVLAa/M1mX5OTJoP2nMC/rzdRGf6NylKMHSzYHAF07TlPpkkRBJNRR29SZ29YX7Zl/coQBYCFK+DUgIItw6ZAQvlle09isa+TJLJKVUcIHuJwbfooi/qxfeBPp3Q+p5szTIE87QBmViFnL5NQAHA0pFcBEZxpKlmLK0gM2zcL4drcTmMJJ3vvual5j1xH5dtKK2QZjQwIfcdsTERxBN/HjQ2kPr7zqTp+KqiVxjhF78oE0YnUObf4Z64y9BSuvRy/XuFy/8uCruUSi27sEXnSOUfJmDAifLwIDAQAB";
        $publicKeyContent = wordwrap($publicKeyContent, 64, "\n", true);
        $publicKeyEnd = "\n-----END PUBLIC KEY-----";

        $publicKey = $publicKeyHeader . $publicKeyContent . $publicKeyEnd;

        $keyStr = '';
        foreach ($params as $key => $value) {
            if (empty($keyStr))
                $keyStr = $key . '=' . $value;
            else
                $keyStr .= '&' . $key . '=' . $value;
        }

        if (!$keyStr)
            return false;

        try {
            $key = openssl_get_publickey($publicKey);

            $ok = openssl_verify($keyStr,base64_decode($returnSign), $key, 'SHA256');
            openssl_free_key($key);
            return $ok;
        } catch (\Exception $e) {
            return false;
        }
    }

    //参数1：访问的URL，参数2：post数据
    protected function curl_request($url,$post=[]){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $data = curl_exec($curl);
        if (curl_errno($curl)) {
            return curl_error($curl);
        }
        curl_close($curl);
        return $data;
    }

}