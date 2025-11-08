<?php

namespace App\Pay;

use App\Lib\Clog;

class mgopay
{
    use PayCurl;

    const PAYCODE    = 'mgopay'; 

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
            'platform_id'      => $thirdPartPay['merchantNumber'],
            'service_id'       => $data['bankCode'],
            'payment_cl_id'    => $data['orderid'],
            'amount'           => $data['amount']*100,
            'notify_url'       => $data['notifyUrl'],
            'request_time'     => time(),
            'name'             => $data['transfer_name']
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output        = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['error_code'] == '0000') {
                return ['action'=>'jump','url'=>$returnArr['data']['link']];
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

        $str   = $str.$privateKey;
        $str   = md5($str);
        return $str;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
        $sign = $input['sign'];
        
        if(isset($input['status']) && $input['status']==2){
            unset($input['sign']);
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($newsign == $sign) {
                $data = ['orderNo'=>$input['payment_cl_id'],'thirdOrderNo'=>$input['payment_id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privatekey'=>$thirdPartPay['privateKey'],'queryDomain'=>$thirdPartPay['merchantQueryDomain'],'rsaPrivateKey'=>$thirdPartPay['rsaPrivateKey']];
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
        echo json_encode(array("error_code" => "0000"));
    }

    public function checkStatus($data)
    {
       $arr =[
            'payment_cl_id'          => $data['orderNo']
        ]; 

        $url           = $data['queryDomain'].'?'.http_build_query($arr);
        $header[]      = 'Authorization: '.$data['rsaPrivateKey'];
        $output        = $this->request('GET', $url, [], 1,$header);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['data'][0]['status']==2) {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
}