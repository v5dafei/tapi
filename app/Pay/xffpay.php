<?php

namespace App\Pay;

use App\Lib\Clog;

class xffpay
{
    use PayCurl;

    const PAYCODE    = 'xffpay';  

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
            'mchId'                  => $thirdPartPay['merchantNumber'],
            'wayCode'                => intval($data['bankCode']),
            'subject'                => '手机或模式',
            'outTradeNo'             => $data['orderid'],
            'amount'                 => $data['amount']*100,
            'clientIp'               => real_ip(),
            'notifyUrl'              => $data['notifyUrl'],
            'reqTime'                => time()*1000,
        ];

        if($data['bankCode'] == 112){
            $arr['memberId']             = $data['player_id'];
        }
        
        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],json_encode($arr), 2);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
            if(isset($return['code']) && $return['code']==0) {
                return ['action'=>'jump','url'=>$return['data']['payUrl']];
            } else {
                return $return['message'];
            }
        } else {
            //返回错误
            \Log::info('新汇丰支付异常，请求参数是',['a'=>$arr]);
            return config('language')[$data['language']]['error126'];
        }
    }

    public function generateSignature($param,$privateKey)
    {
        ksort($param);
        reset($param);

        $str = '';
        foreach ($param as $key => $value) {
            if(!is_null($value) && $value!=''){
                $str.= $key.'='.$value.'&';
            } else{
                continue;
            }
        }

        $str   = $str.'key='.$privateKey;
        $sign  = md5($str);

        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
        unset($input['sign']);
    
        if(isset($input['state']) && $input['state']==1){
           $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['outTradeNo'],'thirdOrderNo'=>$input['originTradeNo'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
            'mchId'      => $data['merchantNumber'],
            'outTradeNo' => $data['orderNo'],
            'reqTime'    => time()*1000
        ];

        $arr['sign']     = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output          = $this->request('POST', $thirdPartPay['merchantQueryDomain'],json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);
            if(isset($return['code']) && $return['code']==0) {
                if($return['data']['state']==1){
                    return true;
                } else{
                    return false;
                }
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