<?php

namespace App\Pay;

use App\Lib\Clog;

class ebpay
{
    use PayCurl;

    const PAYCODE    = 'ebpay';  

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
            'merchantNo'            => $thirdPartPay['merchantNumber'],
            'merchantOrderId'       => $data['orderid'],
            'userName'              => $data['orderid'],
            'deviceType'            => 9,
            
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr['loginIp']               = real_ip();
        $arr['payAmount']             = bcdiv($data['amount'],1,2);
        $arr['payTypeId']             = intval($data['bankCode']);
        $arr['depositNotifyUrl']      = $data['notifyUrl'];
        $arr['virtualProtocol']       = 0;

        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

        \Log::info('EBPAY请求的返回值是',$output);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
            if(isset($return['code'])) {
                return ['action'=>'jump','url'=>$return['data']['url']];
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
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $old   = $str;
        $str   = $str.'key='.$privateKey;
        $sign  = md5($str);
        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
        $sign = $input['sign'];
    
        if(isset($input['orderStatus']) && $input['orderStatus']==1){
           $newsign = 'orderNo='.$input['orderNo'].'&merchantOrderId='.$input['merchantOrderId'].'&merchantNo='.$input['merchantNo'].'&payTypeId='.$input['payTypeId'].'&orderStatus='.$input['orderStatus'].'&orderAmount='.$input['orderAmount'].'&paidAmount='.$input['paidAmount'].'&key='.$thirdPartPay['privateKey'];
           $newsign = md5($newsign);

            if($newsign == $sign){
                $data = ['orderNo'=>$input['merchantOrderId'],'thirdOrderNo'=>$input['orderNo'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
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
            'merchantNo'      => $data['merchantNumber'],
            'merchantOrderId' => $data['orderNo']
        ];
        $arr['sign']   = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output        = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return    = json_decode($output['output'],true);

            if(isset($return['data']['orderStatus']) && $return['data']['orderStatus']==1) {

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
        $data =[
            'code'=>200,
            'msg'=>'成功'
        ];
        echo json_encode($data);
    }
}