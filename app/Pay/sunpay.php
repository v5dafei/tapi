<?php

namespace App\Pay;

use App\Lib\Clog;

class sunpay
{
    use PayCurl;

    const PAYCODE    = 'sunpay';  

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
            'mer_id'                 => $thirdPartPay['merchantNumber'],
            'order_id'               => $data['orderid'],
            'gateway'                => $data['bankCode'],
            'amount'                 => $data['amount'],
            'callback'               => 'http://www.baidu.com',
            'notify'                 => $data['notifyUrl']
        ];

        
        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr['player_ip']            = real_ip();
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code']==0) {
                return ['action'=>'jump','url'=>$return['data']];
            } else {
                return $return['message'];
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
    
        if(isset($input['status']) && $input['status']=='ok'){
           $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($newsign == $sign){
                $data = ['orderNo'=>$input['order_id'],'thirdOrderNo'=>$input['order_id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
            'mer_id'       => $data['merchantNumber'],
            'order_id'     => $data['orderNo'],
        ];

        $arr['sign']     = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output          = $this->request('POST', $thirdPartPay['merchantQueryDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code']==0) {
                if(isset($return['data']['status']) && $return['data']['status']==0){
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
        echo 'ok';
    }
}