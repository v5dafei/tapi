<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Clog;

class outhypay
{
    use PayCurl;

    const PAYCODE    = 'outhypay'; 

    public $transfer = 1; 

    public function __get($key)
    {
        return $this->$key;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

   public function paymentOnBehalf($withdraw, $thirdPartPay,$playerBankCard,$carrierPayChannelId,$bankType)
    {

        //配对字段
        $withdrawinfoArr     = explode('|',$withdraw->collection);

        $arr =[
            'merchantId'            => $thirdPartPay['merchantNumber'],
            'merchantOrderId'       => $withdraw->pay_order_number,
            'orderAmount'           => floatval(bcdiv($withdraw->real_amount,10000,2)),
            'payType'               => 1,
            'accountHolderName'     => $withdrawinfoArr[2],
            'accountNumber'         => $withdrawinfoArr[1],
            'bankType'              => intval($bankType),
            'notifyUrl'             => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'reverseUrl'            => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'submitIp'              => \Yaconf::get(YACONF_PRO_ENV.'.AddressIp', '13.228.137.106')
        ];
        
        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $arr['subBranch']            = $withdrawinfoArr[0];
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(array_key_exists('ErrorCode',$return) && array_key_exists('ErrorMessage',$return) && is_null($return['ErrorCode']) && is_null($return['ErrorMessage'])) {
                return ['status'=>'submitsuccess'];
            } else {        
                return ['status'=>'fail','message'=>$return['ErrorMessage']];
            }
        } else {
            return ['status'=>'fail'];
        }
    }

    public function generateSignature($param,$privateKey)
    {
        $str = '';
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $str   = rtrim($str,'&').$privateKey;
        $sign  = md5($str);

        return $sign;
    }

    public function checked($thirdPartPay,$recvid,$amount)
    {
        $arr =[
            'merchantId'             => $thirdPartPay['merchantNumber'],
            'merchantOrderId'        => $recvid,
            'orderAmount'            => $amount
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantQueryDomain'],http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
            \Log::info('查询的返回值是',$output);
    
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

        $sign = $input['sign'];
        unset($input['sign']);

        if(isset($input['status'])){
            $playerWithdraw = PlayerWithdraw::where('pay_order_number',$input['merchantOrderId'])->first();
            $newSign        = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($sign == $newSign) {
                $result = $this->checked($thirdPartPay,$input['merchantOrderId'],$input['orderAmount']);
                //验签成功
                if($input['status'] ==3) {
                    //代付成功
                    return ['status'=>'success','orderNo'=>$input['merchantOrderId'],'thirdOrderNo'=>$input['systemOrderId']];
                } else if($input['status'] ==4){
                    //代付失败
                    return ['status'=>'fail','orderNo'=>$input['merchantOrderId'],'thirdOrderNo'=>$input['systemOrderId']];
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
        echo 'OK';
    }
}