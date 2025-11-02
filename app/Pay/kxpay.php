<?php

namespace App\Pay;

use App\Lib\Clog;

class kxpay
{
    use PayCurl;

    const PAYCODE    = 'kxpay';  

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
            'app_id'                 => $thirdPartPay['merchantNumber'],
            'out_trade_no'           => $data['orderid'],
            'subject'                => 'mobile',
            'amount'                 => $data['amount'],
            'channel'                => $data['bankCode'],
            'client_ip'              => real_ip(),
            'return_url'             =>'http://www.baidu.com',
            'notify_url'             => $data['notifyUrl'],
        ];

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['return_code']) && $return['return_code']=='SUCCESS') {
                return ['action'=>'jump','url'=>$return['credential']];
            } else {
                return $return['return_msg'];
            }
        } else {
            return '对不起，此通道暂时没有反应';
        }
    }

    public function generateSignature($param,$privateKey)
    {
        ksort($param);
        reset($param);
        $str = '';

        foreach ($param as $key => $value) {
            if(!empty($value)){
                $str.= $key.'='.$value.'&';
            } else{
                continue;
            }
        }

        $str   = $str.'key='.$privateKey;
        return strtoupper(md5($str));
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
        unset($input['sign']);
    
        if(isset($input['trade_state']) && $input['trade_state']=='SUCCESS'){
           $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($newsign == $sign){
                $data = ['orderNo'=>$input['out_trade_no'],'thirdOrderNo'=>$input['trade_no'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
            'app_id'       => $data['merchantNumber'],
            'out_trade_no' => $data['orderNo'],
        ];

        $arr['sign']     = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output          = $this->request('POST', $thirdPartPay['merchantQueryDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['return_code']) && $return['return_code']=='SUCCESS') {
                if($return['trade_state']=='SUCCESS'){
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