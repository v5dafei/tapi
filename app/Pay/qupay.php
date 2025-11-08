<?php

namespace App\Pay;

use App\Lib\Clog;

class qupay
{
    use PayCurl;

    const PAYCODE    = 'qupay';  

    public function __get($key)
    {
        return $this->$key;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function sendData($data, $thirdPartPay, $carrier)
    {
        $arr =[
            'fxid'                  => $thirdPartPay['merchantNumber'],
            'fxddh'                 => $data['orderid'],
            'fxdesc'                => 'mobile',
            'fxfee'                 => $data['amount'],
            'fxnotifyurl'           => $data['notifyUrl'],
            'fxbackurl'             => 'http://www.baidu.com',
            'fxpay'                 => $data['bankCode'],
            'fxip'                  => real_ip(),
            'fxuserid'              => md5(real_ip())         
        ];

        $arr['fxsign']               = md5($arr['fxid'].$arr['fxddh'].$arr['fxfee'].$arr['fxnotifyurl'].$thirdPartPay['privateKey']);
        $arr['url']                  = $thirdPartPay['merchantBindDomain'];
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['status']) && $return['status']==1) {
                return ['action'=>'jump','url'=>$return['payurl']];
            } else {
                return $return['error'];
            }
        } else {
            return '对不起，此通道暂时没有响应';
        }
        
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
    
        if(isset($input['fxstatus']) && $input['fxstatus']==1){

           $newsign = md5($input['fxstatus'].$input['fxid'].$input['fxddh'].$input['fxfee'].$thirdPartPay['privateKey']);

            if($newsign == $input['fxsign']){
                $data = ['orderNo'=>$input['fxddh'],'thirdOrderNo'=>$input['fxorder'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
                $flag = $this->checkStatus($data,$thirdPartPay);

                if($flag) {
                    $data['status'] = true;
                    return $data;
                }
            }
        }
        
        return false;
    }

    public function checkStatus($data,$thirdPartPay)
    {
        $arr =[
            'fxid'    => $data['merchantNumber'],
            'fxtype'  => 1,
            'fxorder' => $data['orderNo']
        ];

        $arr['sign']    = md5($arr['fxid'].$data['orderNo'].$arr['fxtype'].$data['privateKey']);

        $output          = $this->request('POST', $thirdPartPay['merchantQueryDomain'].'?'.http_build_query($arr),[], 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['status']) && $return['status']==1) {
                return true;
            }
            return false;
        } else {
            //返回错误
            return false;
        }
    }

    public function successNotice()
    {
        ob_end_clean();
        echo 'success';
    }
}