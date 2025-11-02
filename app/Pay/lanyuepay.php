<?php

namespace App\Pay;

use App\Lib\Clog;

class lanyuepay
{
    use PayCurl;

    const PAYCODE    = 'lanyuepay';  

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
            'pay_memberid'              => $thirdPartPay['merchantNumber'],
            'pay_orderid'               => $data['orderid'],
            'pay_applydate'             => date('Y-m-d H:i:s'),
            'pay_bankcode'              => $data['bankCode'],
            'pay_notifyurl'             => $data['notifyUrl'],
            'pay_callbackurl'           => 'https://www.baidu.com',
            'pay_amount'                => $data['amount'],
        ];

        $arr['pay_md5sign']          = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr['pay_productname']      = 'mobile';
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['status']) && $return['status']==1) {
                return ['action'=>'jump','url'=>$return['h5_url']];
            } else {
                return $return['msg'];
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
        unset($input['attach']);
    
        if(isset($input['returncode']) && $input['returncode']=='00'){
           $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($newsign == $sign){
                $data = ['orderNo'=>$input['orderid'],'thirdOrderNo'=>$input['transaction_id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
            'pay_memberid'       => $data['merchantNumber'],
            'pay_orderid'     => $data['orderNo'],
        ];

        $arr['pay_md5sign']     = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                 = $this->request('POST', $thirdPartPay['merchantQueryDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['returncode']) && $return['returncode']=='00') {
                if(isset($return['trade_state']) && $return['trade_state']=='SUCCESS'){
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
        echo 'OK';
    }
}