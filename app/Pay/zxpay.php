<?php

namespace App\Pay;

use App\Lib\Clog;

class zxpay
{
    use PayCurl;

    const PAYCODE    = 'zxpay';  

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
            'pay_memberid'             => $thirdPartPay['merchantNumber'],
            'pay_orderid'              => $data['orderid'],
            'pay_applydate'            => date('Y-m-d H:i:s'),
            'pay_bankcode'             => $data['bankCode'],
            'pay_notifyurl'            => $data['notifyUrl'],
            'pay_callbackurl'          => $data['returnUrl'],
            'pay_amount'               =>  $data['amount']
        ]; 

        $arr['pay_md5sign']          = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr['pay_attach']           = $data['real_name'];
        $arr['pay_userid']           = $data['player_id'];

        $html ='<form action='.$thirdPartPay['merchantBindDomain'].' method="post">';

        \Log::info('提交数据',$arr);

        foreach ($arr as $key => $value) {
            $html.='<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
        }

        $html.='</form>';

        return ['action'=>'form','data'=>['html'=>$html]];
    }

    public function generateSignature($param,$privateKey)
    {

        ksort($param);

        $str = '';
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $old   = $str;
        $str   = $str.'key='.$privateKey;
        $sign  = strtoupper(md5($str));
        return $sign;
    }

    public function callback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '', $input);

        $sign = $input['sign'];
    
        if(isset($input['returncode']) && $input['returncode']=='00'){
            unset($input['sign']);
            unset($input['attach']);
            $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($newsign == $sign){

                $data = ['orderNo'=>$input['orderid'],'thirdOrderNo'=>$input['transaction_id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
                $flag = $this->checkStatus($data,$thirdPartPay);

                if($flag) {
                    return $data;
                }
            }
        }
        
        return false;
    }

    public function checkStatus($data,$thirdPartPay)
    {
        $arr =[
            'pay_memberid'        => $data['merchantNumber'],
            'pay_orderid'         => $data['orderNo'],
        ]; 

        $arr['pay_md5sign']   = $this->generateSignature($arr,$data['privateKey']);

        $output               = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if(isset($returnArr['returncode']) && $returnArr['returncode']=='00') {

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
        echo 'OK';
    }
}