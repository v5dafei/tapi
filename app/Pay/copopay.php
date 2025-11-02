<?php

namespace App\Pay;

use App\Lib\Clog;

class copopay
{
    use PayCurl;

    const PAYCODE    = 'copopay';  

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
            'accessType'             => 1,
            'merchantId'             => $thirdPartPay['merchantNumber'],
            'notifyUrl'              => $data['notifyUrl'],
            'language'               => 'zh-CN',
            'orderNo'                => $data['orderid'],
            'orderAmount'            => $data['amount'],
            'payType'                => $data['bankCode'],
            'playerId'               => md5($data['player_id']),
            'orderName'              => '下单',
            'userIp'                 => real_ip()
        ];

        if($data['bankCode']=='UT' || $data['bankCode']=='UE' ){
            $arr['currency'] = 'USDT';
        } else{
            $arr['currency'] = 'CNY';
        } 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],json_encode($arr), 2);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['respCode']) && $return['respCode']=='000') {
                return ['action'=>'jump','url'=>$return['info']];
            } else {
                return $return['respMsg'];
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
            $str.= $key.'='.$value.'&';
        }

        $old   = $str;
        $str   = $str.'Key='.$privateKey;
        $sign  = md5($str);

        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
        unset($input['sign']);

        if(isset($input['orderStatus']) && in_array($input['orderStatus'],[1,3])){
           $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['orderNo'],'thirdOrderNo'=>$input['payOrderId'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
            'accessType'  => 1,
            'merchantId' => $data['merchantNumber'],
            'language'   => 'zh-CN',
            'orderNo'    => $data['orderNo']
        ];

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantQueryDomain'],json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['orderStatus']) && in_array($return['orderStatus'],[1,3])) {

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