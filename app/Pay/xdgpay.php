<?php

namespace App\Pay;

use App\Lib\Clog;
use App\Pay\Rsa;
use App\Pay\Random;

class xdgpay
{
    use PayCurl;

    const PAYCODE    = 'xdgpay';  

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
            'merId'                     => $thirdPartPay['merchantNumber'],
            'orderId'                   => $data['orderid'],
            'orderAmt'                  => $data['amount'].'.00',
            'channel'                   => $data['bankCode'],
            'desc'                      => 'replenisher',
            'smstyle'                   => 1,
            'ip'                        => real_ip(),
            'notifyUrl'                 => $data['notifyUrl'],
            'returnUrl'                 => $data['returnUrl'],
            'nonceStr'                  => Random::alnum('32')          
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey'],$thirdPartPay['rsaPrivateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['data']['payurl'])) {

                return ['action'=>'jump','url'=>$returnArr['data']['payurl']];
            } else {
                return config('language')[$data['language']]['error126'];
            }
        } else {
            //返回错误
            return config('language')[$data['language']]['error126'];
        }
    }

    public function generateSignature($param,$md5Key,$privateKey)
    {

        ksort($param);
        reset($param);

        $str = '';
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $old   = $str;
        $str   = $str.'key='.$md5Key;
        $sign  = strtoupper(md5($str));
        $rsa   = new Rsa('', $privateKey);

        return $rsa->sign($sign);
    }

    public function ungenerateSignature($data,$md5Key,$pubKey)
    {
        //验签
        ksort($data);
        reset($data);
        $arg = '';
        foreach ($data as $key => $val) {
            //空值不参与签名
            if ($val == '' || $key == 'sign') {
                continue;
            }
            $arg .= ($key . '=' . $val . '&');
        }
        $arg = $arg . 'key=' . $md5Key;
        $signData = strtoupper(md5($arg));
        $rsa = new Rsa($pubKey, '');
        if ($rsa->verify($signData, $data['sign']) == 1) {
            return true;
        }
        return false;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
    
        if(isset($input['status']) && $input['status']==1){
            $flag = $this->ungenerateSignature($input,$thirdPartPay['privateKey'],$thirdPartPay['rsaPublicKey']);
            if($flag){
                \Log::info('验签通过');
                $data = ['orderNo'=>$input['orderId'],'thirdOrderNo'=>$input['sysOrderId'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey'],'rsaPrivateKey'=>$thirdPartPay['rsaPrivateKey']];
                $flag = $this->checkStatus($data,$thirdPartPay);

                if($flag) {
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
            'merId'             => $data['merchantNumber'],
            'orderId'           => $data['orderNo'],
            'nonceStr'          => Random::alnum('32')
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$data['privateKey'],$data['rsaPrivateKey']);
        $output        = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($arr), 1);
        \Log::info('查询返回',$output);
        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['code']) && $returnArr['data']['status']==1) {
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
        echo 'success';
    }
}