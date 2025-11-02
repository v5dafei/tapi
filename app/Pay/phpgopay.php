<?php

namespace App\Pay;

use App\Lib\Clog;

class phpgopay
{
    use PayCurl;

    const PAYCODE    = 'phpgopay'; 

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
            'merchant'               => $thirdPartPay['merchantNumber'],
            'payment_type'           => 3,
            'amount'                 => $data['amount'],
            'order_id'               => $data['orderid'],
            'bank_code'              => 'gcash',
            'callback_url'           => $data['notifyUrl'],
            'return_url'             => $data['returnUrl']
            
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if($return['status']==1) {
                return ['action'=>'jump','url'=>$return['redirect_url']];
            } else {
                return config('language')[$data['language']]['error126'];
            }
        } else {
            //返回错误
            return config('language')[$data['language']]['error126'];
        }
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
    
        if(isset($input['status']) && $input['status']==5){

           unset($input['sign']);
           $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['order_id'],'thirdOrderNo'=>$input['order_id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
                $flag = $this->checkStatus($data,$thirdPartPay);

                if($flag) {
                    $data['status'] = true;
                } else {
                    $data['status'] = false;
                }

                return $data;
            }
        }
        
        return false;
    }

    public function checkStatus($data,$thirdPartPay)
    {
        $arr =[
            'merchant'               => $thirdPartPay['merchantNumber'],
            'order_id'               => $data['thirdOrderNo']
        ];

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']); 
        $output                      = $this->request('POST', $thirdPartPay['merchantQueryDomain'], json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['status']) && $return['status']==5) {
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
        echo 'SUCCESS';
    }
}