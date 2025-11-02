<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Clog;

class outokpay
{
    use PayCurl;

    const PAYCODE    = 'outokpay'; 

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
        $arr =[
            'sendid'        => $thirdPartPay['merchantNumber'],
            'orderid'       => $withdraw->pay_order_number,
            'amount'        => bcdiv($withdraw->real_amount,10000,0).'.00',
            'address'       => $withdraw->player_digital_address,
            'notifyurl'     => config('main.behalfUrl').'/'.$carrierPayChannelId,
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);

            if(isset($returnArr['code']) && $returnArr['code'] ==1){
                $return    = json_decode($returnArr['data'],true);
                $flag      = $this->checked($thirdPartPay,$return['id']);

                if($return['state']== 4 && $flag){
                    return ['status'=>'success','order'=>$return['id']];
                }
            } else{
                return ['status'=>'fail','message'=>$returnArr['msg']];
            }
        } else {
            //返回错误
            return ['status'=>'fail'];
        }
    }

    public function generateSignature($data,$privateKey)
    {
        return  md5($data['sendid'].$data['orderid'].$data['amount'].$privateKey);
    }

    public function checked($thirdPartPay,$recvid)
    {
        $output        = $this->request('GET', $thirdPartPay['merchantQueryDomain'].'?id='. $recvid);

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

    public function queryGenerateSignature($data,$privateKey)
    {
        return  md5($data['sendid'].$data['orderid'].$data['amount'].$privateKey);
    }

    public function behalfCallback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE.'反查', '', $input);exit;

        if(isset($input['out_trade_no']) && !empty(trim($input['out_trade_no']))) {
            $sign = $input['sign'];
            unset($input['sign']);

            $playerWithdraw = PlayerWithdraw::where('pay_order_channel_trade_number',$input['id'])->first();

            $newSign = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($sign == $newSign) {
                if($input['status'] ==2) {
                   // $flag = $this->checked($input['out_trade_no'],$thirdPartPay);
                  //  if($flag==true){
                    return ['status'=>true,'orderNumber'=>$input['out_trade_no'],'thirdOrderNumber'=>$input['orderid']];
                   // } else {
                    //    return false;
                   // }
                }
            } 
        } 
    }

    public function successNotice()
    {
        echo 'OK';
    }
}