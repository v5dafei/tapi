<?php

namespace App\Pay;

use App\Lib\Clog;

class mpay
{
    use PayCurl;

    const PAYCODE    = 'mpay'; 

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
            'amount'             => $data['amount'],
            'bank_code'          => '0155',
            'callback_url'       => $data['notifyUrl'],
            'hashed_mem_id'      => md5(real_ip()),
            'merchant_order_no'  => $data['orderid'],
            'merchant_code'      => $thirdPartPay['merchantNumber'],
            'merchant_user'      => $data['transfer_name'],
            'platform'           => 'PC',
            'risk_level'         => '1',
            'service_type'       => '22',
        ]; 

        $arr['sign']          = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output               = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['status'] == 1) {
                return ['action'=>'jump','url'=>$returnArr['transaction_url']];
            } else {
                return $returnArr['error_msg'];
            }
        } else {
            //返回错误
            return config('language')[$data['language']]['error126'];
        }
    }


    public function generateSignature($param,$privateKey)
    {
        $str = '';
        ksort($param);
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $str   = $str.'key='.$privateKey;
        \Log::info('加密前的值是'.$str);
        $str   = hash('sha256',$str);

        return $str;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
        $sign = $input['sign'];
        
        if(isset($input['status']) && $input['status']=='1'){
            unset($input['sign']);
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign) {
                $data = ['orderNo'=>$input['bill_number'],'thirdOrderNo'=>$input['bill_number'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privatekey'=>$thirdPartPay['privateKey'],'queryDomain'=>$thirdPartPay['merchantQueryDomain']];
                $flag = $this->checkStatus($data);
                if($flag==true){
                    $data['status'] = true;
                    return $data;
                }
            } 
        }
        
        return false;
    }

    public function successNotice()
    {
        ob_end_clean();
        echo 'OK';
    }

    public function checkStatus($data)
    {
       $arr =[
            'bill_number'           => $data['orderNo'],
            'client_id'             => $data['merchantNumber'],
            'timestamp'             => date('Y-m-d H:i:s')
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$data['privatekey']);
        $output        = $this->request('POST', $data['queryDomain'], json_encode($arr), 2);
        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['code']==0 && $returnArr['status']=='已完成') {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
}