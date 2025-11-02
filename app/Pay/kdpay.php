<?php

namespace App\Pay;

use App\Lib\Clog;
use App\Lib\Cache\CarrierCache;

class kdpay
{
    use PayCurl;

    const PAYCODE    = 'kdpay';  

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
            'userCode'                 => $thirdPartPay['merchantNumber'],
            'orderCode'                => $data['orderid'],
            'amount'                   => $data['amount'],
            'payType'                  => $data['bankCode']
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr['callbackUrl']          = $data['notifyUrl'];
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code']==200) {
                CarrierCache::setJdpayCache($data['orderid'],$return['data']['orderNo']);
                return ['action'=>'jump','url'=>$return['data']['url']];
            } else {
                return $return['message'];
            }
        } else {
            //返回错误
            return config('language')[$data['language']]['error126'];
        }
    }

    public function generateSignature($param,$privateKey)
    {
       return  strtoupper(md5($param['orderCode'].'&'.$param['amount'].'&'.$param['payType'].'&'.$param['userCode'].'&'.$privateKey));
    }

    public function callbackGenerateSignature($param,$privateKey)
    {
       return  strtoupper(md5($param['orderCode'].'&'.$param['amount'].'&'.$param['userCode'].'&'.$param['status'].'&'.$privateKey));
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
    
        if(isset($input['status']) && $input['status']==3){
           $newsign = $this->callbackGenerateSignature($input,$thirdPartPay['privateKey']);

            if($newsign == $sign){
                $data = ['orderNo'=>$input['orderCode'],'thirdOrderNo'=>CarrierCache::getJdpayCache($input['orderCode']),'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
        $arr                      = [];
        $arr['userCode']          = $data['merchantNumber'];
        $arr['orderCode']         = $data['thirdOrderNo'];
        $arr['customerOrderCode'] = $data['orderNo'];
        $arr['sign']              = strtoupper(md5($arr['orderCode'].'&'.$arr['customerOrderCode'].'&'.$arr['userCode'].'&'.$data['privateKey']));

        $output                   = $this->request('POST', $thirdPartPay['merchantQueryDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
            if(isset($return['code']) && $return['code']==200 && $return['data']['status']==3) {

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
        echo 'success';
    }
}