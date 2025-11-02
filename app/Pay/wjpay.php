<?php

namespace App\Pay;

use App\Lib\Clog;

class wjpay
{
    use PayCurl;

    const PAYCODE    = 'wjpay'; 
    const QUERYURL   = 'https://api.999wanjia.com/pay/query';

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
            'mch_id'           => $thirdPartPay['merchantNumber'],
            'trade_type'       => $data['bankCode'],
            'nonce'            => uniqid(),
            'timestamp'        => time(),
            'subject'          => '话费',
            'out_trade_no'     => $data['orderid'],
            'total_fee'        => $data['amount']*100,
            'spbill_create_ip' => real_ip(),
            'notify_url'       => $data['notifyUrl'],
            'sign_type'        => 'MD5'
        ]; 

        $param   = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $items   = explode('&',$param);
        $postArr = [];

        foreach($items as $val)
        {
            $kv              = explode('=',$val);
            $postArr[$kv[0]] = $kv[1];
        }

        $data_string = json_encode($postArr);
        $output      = $this->request('POST', $thirdPartPay['merchantBindDomain'], $data_string, 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['result_code'] == 'SUCCESS') {
                return ['action'=>'jump','url'=>$returnArr['pay_url']];
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

        $older   = $str;
        $str   = $str.'key='.$privateKey;
        $sign  = md5($str);
        $str   = $older.'sign='.strtoupper($sign);

        return $str;
    }

    public function ungenerateSignature($param,$privateKey)
    {
        ksort($param);

        $str = '';
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $str   = $str.'key='.$privateKey;
        $sign  = md5($str);

        return strtoupper($sign);
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
        $sign = $input['sign'];
        if(isset($input['out_trade_no']) && ! empty($input['out_trade_no'])) {
            if(isset($input['result_code']) && $input['result_code']=='SUCCESS'){
                unset($input['sign']);
                $newsign = $this->ungenerateSignature($input,$thirdPartPay['privateKey']);

                if($newsign == $sign) {
                    $data = ['orderNo'=>$input['out_trade_no'],'thirdOrderNo'=>$input['trade_no'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privatekey'=>$thirdPartPay['privateKey']];
                    $flag = $this->checkStatus($data);

                    if($flag==true){
                        return $data;
                    }
                } 
            }
        }
        return false;
    }

    public function successNotice()
    {
        echo 'SUCCESS';
    }

    public function checkStatus($data)
    {
        $arr =[
            'mch_id'             => $data['merchantNumber'],
            'out_trade_no'       => $data['orderNo'],
            'sign_type'          => 'MD5'
        ]; 

        $param   = $this->generateSignature($arr,$data['privatekey']);
        $items   = explode('&',$param);
        $postArr = [];

        foreach($items as $val)
        {
            $kv              = explode('=',$val);
            $postArr[$kv[0]] = $kv[1];
        }

        $data_string = json_encode($postArr);
        $output      = $this->request('POST', self::QUERYURL, $data_string, 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['trade_status']==1) {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
}