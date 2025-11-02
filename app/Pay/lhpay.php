<?php

namespace App\Pay;

use App\Lib\Clog;

class lhpay
{
    use PayCurl;

    const PAYCODE    = 'lhpay';  

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
            'mchId'                  => $thirdPartPay['merchantNumber'],
            'appId'                  => $thirdPartPay['rsaPrivateKey'],
            'productId'              => $data['bankCode'],
            'mchOrderNo'             => $data['orderid'],
            'currency'               => 'cny',
            'amount'                 => $data['amount']*100,
            'notifyUrl'              => $data['notifyUrl'],
            'subject'                => '测试商品1',
            'body'                   => '测试商品描述1',
            'extra'                  => 'extra'
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$thirdPartPay['privateKey']);

        $output  = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr),1);
        $output  = json_decode($output['output'],true);

        if(isset($output['retCode']) && $output['retCode']=='SUCCESS'){
            return ['action'=>'jump','url'=>$output['payParams']['payUrl']];
        } else {
            return config('language')[$data['language']]['error126'];
        }
    }

    public function generateSignature($param,$privateKey)
    {
        ksort($param);

        $str = '';
        foreach ($param as $key => $value) {
            if(!is_null($value) && !empty($value)){
                $str.= $key.'='.$value.'&';
            }
        }
        $str   = $str.'key='.$privateKey;
        $sign  = strtoupper(md5($str));

        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
        $sign = $input['sign'];
        if(isset($input['status'])==2) {
            unset($input['sign']);
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['mchOrderNo'],'thirdOrderNo'=>$input['channelOrderNo'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey'],'merchantQueryDomain'=>$thirdPartPay['merchantQueryDomain'],'rsaPrivateKey'=>$thirdPartPay['rsaPrivateKey']];
                $flag = $this->checkStatus($data);

                if($flag) {
                    return $data;
                }
            }
        }
        return false;
    }

    public function checkStatus($data)
    {
        $arr =[
            'mchId'             => $data['merchantNumber'],
            'appId'             => $data['rsaPrivateKey'],
            'mchOrderNo'        => $data['orderNo']
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$data['privateKey']);
        $output  = $this->request('POST', $data['merchantQueryDomain'], http_build_query($arr),1);
        $output  = json_decode($output['output'],true);
        if(isset($output['retCode']) && $output['retCode']=='SUCCESS'){
            return true;
        } else {
            //返回错误
            return false;
        }
    }

    public function successNotice()
    {
        echo 'success';
    }
}