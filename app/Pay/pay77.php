<?php

namespace App\Pay;

use App\Lib\Clog;

class pay77
{
    use PayCurl;

    const PAYCODE    = 'pay77';  

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
            'userid'                 => $thirdPartPay['merchantNumber'],
            'orderid'                => $data['orderid'],
            'type'                   => $data['bankCode'],
            'amount'                 => $data['amount'].'.0000',
            'notifyurl'              => $data['notifyUrl'],
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['code']) && $returnArr['code']==1) {
                $output = json_decode($returnArr['data'],true);

                return ['action'=>'jump','url'=>$output['pageurl'],'ticket'=>$output['ticket']];
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
        $str   = $privateKey.$param['orderid'].$param['amount'];
        $sign  = md5(strtolower($str));
        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
    
        if(isset($input['code']) && $input['code']==1){
            $input = json_decode($input['data'],true);
            $sign  = $input['sign'];
            unset($input['sign']);
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($newsign == $sign){
                $data = ['orderNo'=>$input['orderid'],'thirdOrderNo'=>$input['ticket'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
                $flag = $this->checkStatus($data,$thirdPartPay);

                if($flag) {
                    return $data;
                }
            }
        }
        
        return false;
    }

    public function checkStatus($data,$thirdPartPay)
    {
        $arr =[
            'ticket '        => $data['thirdOrderNo'],
        ]; 

        $output        = $this->request('GET', $thirdPartPay['merchantQueryDomain'].'?ticket='.$data['thirdOrderNo'], [], 1);
        \Log::info('ticket的值是'.$data['thirdOrderNo']);
        
        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['code']==1) {
                $output = json_decode($returnArr['data'],true);
                if($output['ispay']==1){
                    return true;
                } else {
                    return false;
                }
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