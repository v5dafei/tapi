<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Models\Log\CarrierBankCardType;
use App\Models\PlayerBankCard;
use App\Models\Map\CarrierWithdrawBankcode;
use App\Lib\Clog;

class out77pay
{
    use PayCurl;

    const PAYCODE    = 'out77pay';  

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

        if(count($withdrawinfoArr)!=4){
            return false;
        }

        if(!empty($withdraw->player_bank_id)){
            $payerBankCard = PlayerBankCard::where('id',$withdraw->player_bank_id)->first();
        } else {
            return false;
        }

        $arr =[
            'userid'                 => $thirdPartPay['merchantNumber'],
            'orderid'                => $withdraw->pay_order_number,
            'amount'                 => bcdiv($withdraw->real_amount,10000,4),
            'notifyurl'              => config('main.behalfUrl').'/'.$carrierPayChannelId
        ]; 

        $payload = [
            'cardname'  => $withdrawinfoArr[2],
            'cardno'    => $withdrawinfoArr[1],
            'bankname'  => $withdrawinfoArr[0]
        ];

        $existCarrierWithdrawBankcode  =  CarrierWithdrawBankcode::where('carrier_id',$withdraw->carrier_id)->where('third_part_pay_id',$thirdPartPay['thirdPartPayId'])->where('carrier_bank_type_id',$payerBankCard->bank_Id)->first();
        if($existCarrierWithdrawBankcode){
            $payload['bankid'] = $existCarrierWithdrawBankcode->third_bank_code;
        } else {
            $payload['bankid'] = 10000;
        }

        $arr['payload']     = json_encode($payload);
        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        //\Log::info('代付请求的参数是',$arr);exit;
        $output             = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);

            if(isset($returnArr['code']) && $returnArr['code']==1) {
                 $output = json_decode($returnArr['data'],true);

                  return ['processing'=>1,'order'=>$output['ticket']];
               if($flag){
                  return $return['id'];
               } else {
                  return false;
               }
            }
        } else {
            //返回错误
            return false;
        }
    }

    public function generateSignature($param,$privateKey)
    {
        $str   = $privateKey.$param['orderid'].$param['amount'];
        $sign  = md5(strtolower($str));
        return $sign;
    }

    public function checked($ticket,$thirdPartPay)
    {
        $output        = $this->request('GET', $thirdPartPay['merchantQueryDomain'].'?ticket='.$ticket, [], 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $returnArr = json_decode($output['output'],true);
            if($returnArr['code']==1) {
                $output = json_decode($returnArr['data'],true);
                if($output['ispay']==1){
                    return true;
                } else {
                    return false;
                }
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
        Clog::payMsg(self::PAYCODE.'反查', '', $input);

        if(isset($input['code']) && $input['code']==1) {
            $output = json_decode($input['data'],true);
            $sign   = $output['sign'];

            $playerWithdraw = PlayerWithdraw::where('pay_order_channel_trade_number',$output['ticket'])->first();
            $newSign        = $this->generateSignature($output,$thirdPartPay['privateKey']);

            if($sign == $newSign) {
                $flag = $this->checked($output['ticket'],$thirdPartPay);
                if($flag==true){
                    return ['status'=>true,'orderNumber'=>$output['orderid'],'thirdOrderNumber'=>$output['ticket']];
                } else {
                    return false;
                }
                
            } 
        } 
    }

    public function successNotice()
    {
        ob_end_clean();
        echo 'success';
    }
}