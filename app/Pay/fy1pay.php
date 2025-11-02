<?php

namespace App\Pay;

use App\Lib\Clog;

class fy1pay
{
    use PayCurl;

    const PAYCODE    = 'fy1pay';  

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
            'timestamp'              => time()*1000,
            'userId'                 => md5($data['player_id']),
            'customerNo'             => $thirdPartPay['merchantNumber'],
            'payTypeId'              => $data['bankCode'],
            'amount'                 => $data['amount'],
            'orderNo'                => $data['orderid'],
            'customerCallbackUrl'    => $data['notifyUrl']
        ]; 

        if($data['has_realname']){
            $arr['payerName']              = $data['transfer_name'];
        }

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);

        \Log::info('进入富盈支付的值是',$arr);

        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            \Log::info('请求支付通道的返回错误的返回值是',$output);
            $return = json_decode($output['output'],true);

            if(isset($return['success']) && $return['success']) {
                return ['action'=>'jump','url'=>$return['data']['url']];
            } else {
                return config('language')[$data['language']]['error126'];
            }
        } else {
            //返回错误
            \Log::info('请求支付通道的返回错误1的返回值是',$output);
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
        $str   = $str.'key='.$privateKey;
        \Log::info('签名的字符串是'.$str);
        $sign  = strtolower(md5($str));
        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
    
        if(isset($input['success']) && $input['success']==true){

           $newsign = $this->generateSignature($input['data'],$thirdPartPay['privateKey']);

            if($newsign == $sign){
                $data = ['orderNo'=>$input['data']['orderNo'],'thirdOrderNo'=>$input['data']['transactionalNumber'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
            'timestamp'  => time()*1000,
            'customerNo' => $data['merchantNumber'],
            'orderNo'    => $data['orderNo']
        ];

        $arr['sign']     = $this->generateSignature($arr,$thirdPartPay['privateKey']);

        $output          = $this->request('POST', $thirdPartPay['merchantQueryDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['success']) && $return['success']) {

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