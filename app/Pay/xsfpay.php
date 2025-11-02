<?php

namespace App\Pay;

use App\Lib\Clog;
use App\Pay\Rsa;
use App\Pay\Random;

class xsfpay
{
    use PayCurl;

    const PAYCODE    = 'xsfpay';  

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
            'productId'                 => $data['bankCode'],
            'mchOrderNo'                => $data['orderid'],
            'amount'                    => $data['amount']*100,
            'currency'                  => 'cny',
            'notifyUrl'                 => $data['notifyUrl'],
            'subject'                   => 'xxpay测试商品1',
            'body'                      => 'xxpay测试商品描述',
            'reqTime'                   => date('YmdHis'),
            'version'                   => '1.0',       
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['retCode']) && $returnArr['retCode']==0 && isset($returnArr['payUrl'])) {

                return ['action'=>'jump','url'=>$returnArr['payUrl']];
            } else {
                return config('language')[$data['language']]['error126'];
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
            if($value=='null'){
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
    
        if(isset($input['status']) && ($input['status']==2 || $input['status']==3)){
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($sign){
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
            'payOrderId'        => $data['thirdOrderNo'],
            'mchOrderNo'        => $data['orderNo'],
            'reqTime'           => date('YmdHis'),
            'version'           => '1.0',
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$data['privateKey']);
        $output        = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($arr), 1);


        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['retCode']) && $returnArr['retCode']==0 && $returnArr['status']==2) {
                \Log::info('xsf查询的成功返回');
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