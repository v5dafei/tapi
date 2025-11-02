<?php

namespace App\Pay;

use App\Lib\Clog;

class outmgopay
{
    use PayCurl;

    const PAYCODE    = 'outmgopay';
     
    public $transfer = 0; 

    public function __get($key)
    {
        return $this->$key;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

   public function paymentOnBehalf($withdraw, $thirdPartPay,$playerBankCard,$carrierPayChannelId)
    {
        $withdrawinfoArr     = explode('|',$withdraw->collection);
        $arr =[
            'platform_id'      => $thirdPartPay['merchantNumber'],
            'service_id'       => 'SVC0004',
            'payout_cl_id'     => $withdraw->pay_order_number,
            'amount'           => bcdiv($withdraw->real_amount,100,2),
            'notify_url'       => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'bank_name'        => $withdrawinfoArr[0],
            'name'             => $withdrawinfoArr[2],
            'number'           => $withdrawinfoArr[1],
            'request_time'     => time(),
            
        ]; 

        $arr['sign']   = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output        = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['error_code'] == '0000') {
                return ['status'=>'submitsuccess'];
            } else {
                return ['status'=>'fail','message'=>$returnArr['error_msg']];
            }
        } else {
            //返回错误
            return ['status'=>'fail'];
        }
    }

   public function generateSignature($param,$privateKey)
    {
        $str = '';
        ksort($param);
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $str   = $str.$privateKey;
        $str   = md5($str);
        return $str;
    }

    public function checked($thirdPartPay,$recvid)
    {
        $arr =[
            'payout_cl_id'  => $recvid,
        ];

        $url           = $thirdPartPay['merchantQueryDomain'].'?'.http_build_query($arr);
        $header[]      = 'Authorization: '.$thirdPartPay['rsaPrivateKey'];
        $output        = $this->request('GET', $url, [], 1,$header);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
        
            if(isset($return['error_code']) && $return['error_code']=='0000') {
                if($return['data'][0]['status'] == 3){
                    return true;
                } else if($return['data'][0]['status'] == 4 || $return['data'][0]['status'] == 5){
                    return false;
                } else {
                    return 'unknow';
                }
            } else {
                return 'unknow';
            }
        } else {
            //返回错误
            return 'unknow';
        }
    }

    public function behalfCallback($input,$thirdPartPay)
    {

        Clog::payMsg(self::PAYCODE.'代付回调', '', $input);

        $sign = $input['sign'];
        unset($input['sign']);

        if(isset($input['status'])){
            $newSign        = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($sign == $newSign) {
                $result = $this->checked($thirdPartPay,$input['payout_cl_id']);
                //验签成功
                if($input['status'] ==3 && $result===true) {
                    //代付成功
                    return ['status'=>'success','orderNo'=>$input['payout_cl_id'],'thirdOrderNo'=>$input['payout_id']];
                } else if(($input['status'] ==4 || $input['status'] == 5 ) && $result=== false){
                    //代付失败
                    return ['status'=>'fail','orderNo'=>$input['payout_cl_id'],'thirdOrderNo'=>$input['payout_id']];
                } else {
                    exit;
                }
            } else {
                \Log::info('验签不正确');
                exit;
            }
        } 
    }

    public function successNotice()
    {
        ob_end_clean();
        echo '0000';
    }
}