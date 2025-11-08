<?php

namespace App\Pay;

use App\Lib\Clog;

class dmppay
{
    use PayCurl;

    const PAYCODE    = 'dmppay'; 

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
            'client_id'        => $thirdPartPay['merchantNumber'],
            'bill_number'      => $data['orderid'],
            'type'             => $data['bankCode'],
            'amount'           => $data['amount'],
            'depositor_name'   => $data['transfer_name'],
            'notify_url'       => $data['notifyUrl'],
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output        = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['code'] == 0) {
                return ['action'=>'jump','url'=>$returnArr['url']];
            } else {
                return $returnArr['message'];
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
        $str   = md5($str);

        return $str;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
        $sign = $input['sign'];
        
        if(isset($input['status']) && $input['status']=='已完成'){
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