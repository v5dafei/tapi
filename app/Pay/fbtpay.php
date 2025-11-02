<?php

namespace App\Pay;

use App\Lib\Clog;

class fbtpay
{
    use PayCurl;

    const PAYCODE    = 'fbtpay';  

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
            'p1_merchantno'             => $thirdPartPay['merchantNumber'],
            'p2_amount'                 => $data['amount'].'.00',
            'p3_orderno'                => $data['orderid'],
            'p5_reqtime'                => date('YmdHis'),
            'p6_goodsname'              => 'mobile phone',
            'p8_returnurl'              => $data['returnUrl'],
            'p9_callbackurl'            => $data['notifyUrl'],
            'p4_paytype'                => $data['bankCode']
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['rspcode']) && $returnArr['rspcode']=="A0") {
                return ['action'=>'jump','url'=>$returnArr['data']];
            } else {
                return $returnArr['rspmsg'];
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

        $old   = $str;
        $str   = $str.'key='.$privateKey;
        $sign  = strtoupper(md5($str));
        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
    
        if(isset($input['p4_status']) && $input['p4_status']==2){
            unset($input['sign']);
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['p3_orderno'],'thirdOrderNo'=>$input['p9_porderno'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
            'p1_merchantno'        => $data['merchantNumber'],
            'p2_orderno'           => $data['orderNo'],
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$data['privateKey']);
        $output        = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['rspcode']=='A0' && $returnArr['status']==2) {
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