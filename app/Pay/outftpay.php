<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Clog;

class outftpay
{
    use PayCurl;

    const PAYCODE    = 'outftpay'; 

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
            'username'              => $thirdPartPay['merchantNumber'],
            'amount'                => bcdiv($withdraw->real_amount,10000,0).'.00',
            'order_number'          => $withdraw->pay_order_number,
            'notify_url'            => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'bank_card_holder_name' => $withdrawinfoArr[2],
            'bank_card_number'      => $withdrawinfoArr[1],
            'bank_name'             => $withdrawinfoArr[0],
        ];

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
            if(isset($return['http_status_code']) && $return['http_status_code'] ==201) {
                if(isset($return['data']['system_order_number'])){
                    return ['status'=>'submitsuccess','order'=>$return['data']['system_order_number']];
                } else {
                    return ['status'=>'fail','message'=>$return['message']];
                }
                
            } else {        
                return ['status'=>'fail','message'=>$return['message']];
            }
        } else {
            return ['status'=>'fail'];
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
        $str   = $str.'secret_key='.$privateKey;
        $sign  = md5($str);

        return $sign;
    }

    public function checked($thirdPartPay,$recvid)
    {
        $arr =[
            'username'             => $thirdPartPay['merchantNumber'],
            'order_number'         => $recvid,
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantQueryDomain'],json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
    
            if(isset($return['http_status_code']) && $return['http_status_code']==201) {

                if(isset($return['data']['status']) && in_array($return['data']['status'],[4,5])){
                    return true;
                } else if(isset($return['data']['status']) && in_array($return['data']['status'],[6,7,8])){
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

        if(isset($input['http_status_code']) && ($input['http_status_code']==200) || $input['http_status_code']==201){
            $sign = $input['data']['sign'];
            unset($input['data']['sign']);

            $playerWithdraw = PlayerWithdraw::where('pay_order_number',$input['data']['order_number'])->first();
            $newSign        = $this->generateSignature($input['data'],$thirdPartPay['privateKey']);

            if($sign == $newSign) {
                $result = $this->checked($thirdPartPay,$input['data']['order_number']);
                    //验签成功
                if(in_array($input['data']['status'],[4,5]) && $result===true) {
                    //代付成功
                        return ['status'=>'success','orderNo'=>$input['data']['order_number'],'thirdOrderNo'=>$input['data']['system_order_number']];
                    } else if(in_array($input['data']['status'],[6,7,8]) && $result=== false){
                        //代付失败
                        return ['status'=>'fail','orderNo'=>$input['data']['order_number'],'thirdOrderNo'=>$input['data']['system_order_number']];
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
        echo 'success';
    }
}