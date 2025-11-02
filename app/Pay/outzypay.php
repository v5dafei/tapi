<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Clog;

class outzypay
{
    use PayCurl;

    const PAYCODE    = 'outzypay'; 

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
            'Amount'                => bcdiv($withdraw->real_amount,10000,2),
            'BankCardBankName'      => $withdrawinfoArr[0],
            'BankCardNumber'        => $withdrawinfoArr[1],
            'BankCardRealName'      => $withdrawinfoArr[2],
            'MerchantId'            => $thirdPartPay['merchantNumber'],
            'MerchantUniqueOrderId' => $withdraw->pay_order_number,
            'NotifyUrl'             => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'Timestamp'             => date('YmdHis'),
            'WithdrawTypeId'        => 0
        ];

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['Code']) && $return['Code'] =='0') {
                return ['status'=>'submitsuccess'];
            } else {        
                return ['status'=>'fail','message'=>$return['Message']];
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
        $str   = rtrim($str,'&').$privateKey;
        $sign  = md5($str);

        return $sign;
    }

    public function checked($thirdPartPay,$recvid)
    {
        $arr =[
            'MerchantId'             => $thirdPartPay['merchantNumber'],
            'MerchantUniqueOrderId'  => $recvid,
            'Timestamp'              => date('YmdHis')
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantQueryDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
    
            if(isset($return['Code']) && $return['Code']==0) {
                if($return['WithdrawOrderStatus'] == 100){
                    return true;
                } else if($return['WithdrawOrderStatus'] == -90 || $return['WithdrawOrderStatus'] == -10){
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

        $sign = $input['Sign'];
        unset($input['Sign']);

        if(isset($input['Status'])){
            $playerWithdraw = PlayerWithdraw::where('pay_order_number',$input['MerchantUniqueOrderId'])->first();
            $newSign        = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($sign == $newSign) {
                $result = $this->checked($thirdPartPay,$input['MerchantUniqueOrderId']);
                //验签成功
                if($input['Status'] ==100 && $result===true) {
                    //代付成功
                    return ['status'=>'success','orderNo'=>$input['MerchantUniqueOrderId'],'thirdOrderNo'=>$input['WithdrawOrderId']];
                } else if($input['Status'] ==-90 && $result=== false){
                    //代付失败
                    return ['status'=>'fail','orderNo'=>$input['MerchantUniqueOrderId'],'thirdOrderNo'=>$input['WithdrawOrderId']];
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
        echo 'SUCCESS';
    }
}