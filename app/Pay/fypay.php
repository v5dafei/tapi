<?php

namespace App\Pay;

use App\Lib\Clog;

class fypay
{
    use PayCurl;

    const PAYCODE    = 'fypay';  
    const QUERYURL   = 'https://mnhbr8.topfy666.com/api/order/GetMerchantOrderStatus';

    public $bankCode = ['FY_ONLINEPAY'=>222];

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
            'uid'                     => $thirdPartPay['merchantNumber'],
            'totalAmount'             => $data['amount'].'.00',
            'merchantTransNo'         => $data['orderid'],
            'paymentType'             => $this->bankCode['FY_ONLINEPAY']
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr['callBackUrl']          = $data['notifyUrl'];
        $arr['returnUrl']            = $data['returnUrl'];
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['Status']) && $returnArr['Status']==1) {
                return ['action'=>'jump','url'=>$returnArr['Data']];
            } else {
                return config('language')[$data['language']]['error126'];
            }
        } else {
            //返回错误
            return config('language')[$data['language']]['error126'];
        }
    }

    public function generateSignature($param,$privateKey)
    {

        ksort($param);

        $str = '';
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $old   = $str;
        $str   = $str.'key='.$privateKey;
        $sign  = md5($str);
        return $sign;
    }

    public function ungenerateSignature($param,$privateKey)
    {
         ksort($param);

        $str = '';
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $old   = $str;
        $str   = $str.'key='.$privateKey;
        $sign  = md5(md5($str));
        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['signature'];
        if(isset($input['transNo']) && ! empty($input['transNo'])) {
            if(isset($input['orderStatus']) && $input['orderStatus']=='ok'){
                unset($input['signature']);
                $newsign = $this->ungenerateSignature($input,$thirdPartPay['privateKey']);

                if($newsign == $sign){
                    $data = ['orderNo'=>$input['transNo'],'thirdOrderNo'=>$input['transNo'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
                    $flag = $this->checkStatus($data);

                    if($flag) {
                        return $data;
                    }
                }
            }
        }
        return false;
    }

    public function checkStatus($data)
    {
        $arr =[
            'uid'                  => $data['merchantNumber'],
            'merchantTranNo'       => $data['orderNo'],
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$data['privateKey']);
        $output        = $this->request('GET', self::QUERYURL.'?'.http_build_query($arr));

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['Status']==1 && $returnArr['Data']['Status']==4) {
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
        echo 1;
    }


    //{"payType":"PAYTYPE_COPY_BANK","handlingFee":"7.20","submitNo":"CZ2020101916002150725","rate":"0.01800","createTime":"2020-10-19 16:00:54","price":"400.00","sign":"b398e315b1dca2eb71c754673c27446a","orderStatus":"ORDER_STATUS_COMPLETE","completeTime":"2020-10-19 16:01:31","orderCode":"220101916005444462","arrivalPrice":"392.79"}
}