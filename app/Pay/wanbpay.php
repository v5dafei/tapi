<?php

namespace App\Pay;

use App\Lib\Clog;

class wanbpay
{
    use PayCurl;

    const PAYCODE    = 'wanbpay';  

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
            'userid'                 => $thirdPartPay['merchantNumber'],
            'amount'                 => $data['amount'].'.00',
            'orderid'                => $data['orderid'],
            'notifyurl'              => $data['notifyUrl'],
            'currency'               => 'wanb'
        ]; 

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            $return    = json_decode($returnArr['data'],true);

            if(isset($return['navigateurl'])) {
                return ['action'=>'jump','url'=>$return['navigateurl']];
            } else {
                return $returnArr['msg'];
            }
        } else {
            //返回错误
            return config('language')[$data['language']]['error126'];
        }
    }

    public function generateSignature($param,$privateKey)
    {
       return  md5($param['userid'].$param['amount'].$param['orderid'].$param['notifyurl'].$privateKey);
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
    
        if(isset($input['state']) && $input['state']==4){
            $param   =[
                'userid'    => $input['userid'],
                'amount'    => $input['amount'],
                'orderid'   => $input['orderid'],
                'notifyurl' => $input['notifyurl']
            ];

            $newsign    = $this->generateSignature($param,$thirdPartPay['privateKey']);
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
        $sign          = md5($data['thirdOrderNo'].$thirdPartPay['privateKey']);
        $output        = $this->request('GET', $thirdPartPay['merchantQueryDomain'].'?id='. $data['thirdOrderNo'].'&sign='.$sign);

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