<?php

namespace App\Pay;

use App\Lib\Clog;

class nopay
{
    use PayCurl;

    const PAYCODE    = 'nopay';  

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
            'appId'                  => $thirdPartPay['merchantNumber'],
            'merchantMemberNo'       => md5($data['user_name']),
            'merchantOrderNo'        => $data['orderid'],
            'amount'                 => $data['amount'],
            'paymentMethod'          => 12,
            'notifyUr'               => $data['notifyUrl'],
            'timestamp'              => time()
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);

        \Log::info('nopay请求参数是',$arr);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2,['appId'=>$thirdPartPay['merchantNumber']]);
        \Log::info('nopay拉单请求的返回值是',$output);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return    = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code']==0) {
                return ['action'=>'jump','url'=>$return['url']];
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

        $str   = $str.'key='.$privateKey;
        \Log::info('加密前的值是'.$str);
        $str   = hash('sha256',$str);

        return $str;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['retsign'];
    
        if(isset($input['state']) && $input['state']==4){
           $newsign = md5($input['sign'].$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['orderid'],'thirdOrderNo'=>$input['id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
        $output        = $this->request('GET', $thirdPartPay['merchantQueryDomain'].'?id='. $data['thirdOrderNo']);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            $return    = json_decode($returnArr['data'],true);

            if(isset($return['state']) && $return['state']==4) {

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