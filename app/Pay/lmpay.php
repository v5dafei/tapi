<?php

namespace App\Pay;

use App\Lib\Clog;

class lmpay
{
    use PayCurl;

    const PAYCODE    = 'lmpay';  

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
            'merchantCode'           => $thirdPartPay['merchantNumber'],
            'merchantOrderId'        => $data['orderid'],
            'paymentTypeCode'        => $data['bankCode'],
            'amount'                 => $data['amount'],
            'successUrl'             => $data['notifyUrl'],
            'merchantMemberId'       => md5($data['player_id']),
            'merchantMemberIp'       => real_ip()
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr['currencyCode']         = 'CNY';
        $arr['mp']                   = 'mp';
        $arr['payerName']            = $data['transfer_name'];

        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],json_encode($arr), 2);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['result']) && $return['result']) {
                return ['action'=>'jump','url'=>$return['data']['httpsUrl']];
            } else {
                return $return['errorMsg']['descript'];
            }
        } else {
            //返回错误
            return config('language')[$data['language']]['error126'];
        }
    }

    public function generateSignature($param,$privateKey)
    {
        ksort($param);
        reset($param);

        $str = '';
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'|';
        }

        $str   = rtrim($str,'|');
        $str   = $str.$privateKey;
        $sign  = md5($str);

        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
        unset($input['sign']);
    
        if(isset($input['status']) && $input['status']=='Success'){
           $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($newsign == $sign){
                $data = ['orderNo'=>$input['merchantOrderId'],'thirdOrderNo'=>$input['gamerOrderId'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
                $flag = $this->checkStatus($data,$thirdPartPay);

                if($flag) {
                    $data['status'] = true;
                    return $data;
                }
            }
        }
        
        return false;
    }

    public function checkStatus($data,$thirdPartPay)
    {
        $arr = [
            'merchantCode'    => $data['merchantNumber'],
            'merchantOrderId' => $data['orderNo']
        ];

        $arr['sign']     = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output          = $this->request('POST', $thirdPartPay['merchantQueryDomain'],json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['data']['status']) && $return['data']['status']=='Success') {

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
        echo 'SUCCESS';
    }
}