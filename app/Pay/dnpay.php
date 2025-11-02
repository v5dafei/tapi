<?php

namespace App\Pay;

use App\Lib\Clog;
use App\Pay\Rsa;
use App\Pay\Random;

class dnpay
{
    use PayCurl;

    const PAYCODE    = 'dnpay';  
    const APPID      = '643f585be5934180b9fab79c1a897fbf';

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
            'mchId'                     => $thirdPartPay['merchantNumber'],
            'appId'                     => self::APPID,
            'mchOrderNo'                => $data['orderid'],
            'productId'                 => $data['bankCode'],
            'amount'                    => $data['amount']*100,
            'currency'                  => 'cny',
            'clientIp'                  => real_ip(),
            'device'                    => 'WEB',
            'subject'                   => 'mobilephone',
            'body'                      => 'black',
            'notifyUrl'                 => $data['notifyUrl']
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $params                      =[
            'params' => json_encode($arr)
        ];

        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($params), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['retCode']) && $returnArr['retCode']=='SUCCESS') {
                return ['action'=>'jump','url'=>$returnArr['qrcode']];
            } else {
                return $returnArr['retMsg'];
            }
        } else {
            //返回错误
           return config('language')[$data['language']]['error126'];
        }
    }

    public function generateSignature($param,$md5Key)
    {

        ksort($param);
        reset($param);

        $str = '';
        foreach ($param as $key => $value) {
            if($value=='null' || $value== null){
                continue;
            } 
            $str.= $key.'='.$value.'&';
        }

        $old   = $str;
        $str   = $str.'key='.$md5Key;
        $sign  = strtoupper(md5($str));

        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
        unset($input['sign']);
    
        if(isset($input['status']) && $input['status']==2){
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['mchOrderNo'],'thirdOrderNo'=>$input['payOrderId'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
                $flag = $this->checkStatus($data,$thirdPartPay);

                if($flag) {
                    $data['status'] = true;
                    return $data;
                }
            } else {
                return false;
            }
        }
        
        return false;
    }

    public function checkStatus($data,$thirdPartPay)
    {
        $arr =[
            'mchId'             => $data['merchantNumber'],
            'appId'             => self::APPID,
            'mchOrderNo'        => $data['orderNo'],
            'payOrderId'        => $data['thirdOrderNo'],
            'executeNotify'     => 'true'
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$data['privateKey']);

        $params                      =[
            'params' => json_encode($arr)
        ];
        $output        = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($params), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['retCode']) && $returnArr['retCode']=='SUCCESS' && $returnArr['status']==2) {
                
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
        echo 'SUCCESS';
    }
}