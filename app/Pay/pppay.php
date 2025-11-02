<?php

namespace App\Pay;

use App\Lib\Clog;

class pppay
{
    use PayCurl;

    const PAYCODE    = 'pppay';  

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
            'recvid'                 => $thirdPartPay['merchantNumber'],
            'orderid'                => $data['orderid'],
            'amount'                 => $data['amount'],
            'paytypes'               => $data['bankCode'],
            'notifyurl'              => $data['notifyUrl'],
            'memuid'                 => md5($data['transfer_name'])            
        ];

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code']==1) {
                $data = json_decode($return['data'],true);
                return ['action'=>'jump','url'=>$data['navurl']];
            } else {
                return $return['msg'];
            }
        } else {
            return '对不起，此通道暂时没有反应';
        }
    }

    public function generateSignature($param,$privateKey)
    {
        return md5($param['recvid'].$param['orderid'].$param['amount'].$privateKey);
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);
    
        if(isset($input['state']) && $input['state']==4){
           $newsign = md5($input['sign'].$thirdPartPay['privateKey']);

            if($newsign == $input['retsign']){
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
        $output          = $this->request('POST', $thirdPartPay['merchantQueryDomain'].'?id='.$data['thirdOrderNo'],[], 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code']==1) {
                $data = json_decode($return['data'],true);
                if($data['state']==4){
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