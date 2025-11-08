<?php

namespace App\Pay;

use App\Lib\Clog;

class wdpay
{
    use PayCurl;

    const PAYCODE    = 'wdpay'; 

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
            'amount'           => $data['amount'].'.00',
            'channelId'        => $data['bankCode'],
            'noticeUrl'        => $data['notifyUrl'],
            'orderId'          => $data['orderid'],
            'returnUrl'        => $data['returnUrl'],
            'user'             => $thirdPartPay['merchantNumber']
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr           = http_build_query($arr);
        $output        = $this->request('POST', $thirdPartPay['merchantBindDomain'], $arr, 3);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['code'] == 0) {
                return ['action'=>'jump','url'=>$returnArr['url']];
            } else {
                return $returnArr['msg'];
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

        $str   = $str.'token='.$privateKey;
        $str   = strtoupper(md5($str));

        return $str;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
        $sign = $input['sign'];
        
        if(isset($input['payStatus']) && $input['payStatus']==2){
            unset($input['sign']);
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($newsign == $sign) {
                $data = ['orderNo'=>$input['orderId'],'thirdOrderNo'=>$input['orderId'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privatekey'=>$thirdPartPay['privateKey'],'queryDomain'=>$thirdPartPay['merchantQueryDomain']];
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
        echo 'success';
    }

    public function checkStatus($data)
    {
       $arr =[
            'orderId'          => $data['orderNo'],
            'user'             => $data['merchantNumber']
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$data['privatekey']);
        $arr           = http_build_query($arr);
        $output        = $this->request('POST', $data['queryDomain'], $arr, 3);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['payStatus']==2) {
                return true;
            }
            return false;
        } else {
            return false;
        }
    }
}