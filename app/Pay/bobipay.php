<?php

namespace App\Pay;

use App\Lib\Clog;

class bobipay
{
    use PayCurl;

    const PAYCODE    = 'bobipay';  

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
            'currency_id'            => 1,
            'money'                  => intval($data['amount']),
            'callback_url'           => $data['notifyUrl'],
            'cp_order_id'            => $data['orderid'],
            'mch_id'                 => $thirdPartPay['merchantNumber'],
            'time'                   => time()
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code']==0) {
                return ['action'=>'jump','url'=>$return['pay_url']];
            } else {
                return $return['msg'];
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

        $str   = $str.'pri_key='.$privateKey;

        return strtolower(md5($str));
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
        unset($input['sign']);
    
        if(isset($input['status']) && $input['status']==1){
           $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['cp_order_id'],'thirdOrderNo'=>$input['cp_order_id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
        $arr =[
            'cp_order_id'           => $data['orderNo'],
            'mch_id'                => $data['merchantNumber'],
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return    = json_decode($output['output'],true);

            if(isset($return['status']) && $return['status']==1) {

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
        echo 'ok';
    }
}