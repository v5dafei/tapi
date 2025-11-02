<?php

namespace App\Pay;

class outphpgopay
{
    use PayCurl;

    const PAYCODE    = 'outphpgopay';  

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
            'merchant'      => $thirdPartPay['merchantNumber'],
            'total_amount'  => bcdiv($withdraw->real_amount,10000,2),
            'callback_url'  => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'order_id'      => $withdraw->pay_order_number
        ];

        if($withdraw->type==5){
            $arr['bank']               = 'gcash';
            $arr['bank_card_name']     = $withdraw->player_digital_address;
            $arr['bank_card_account']  = $withdraw->player_digital_address;
            $arr['bank_card_remark']   = $withdraw->player_digital_address;
        } else {

            $withdrawinfoArr     = explode('|',$withdraw->collection);
            if(count($withdrawinfoArr)!=4){
                return false;
            }

            $arr['bank']              = $this->getbankCode($withdraw->player_bank_id,self::PAYCODE);
            $arr['bank_card_name']    = $withdrawinfoArr[2];
            $arr['bank_card_account'] = $withdrawinfoArr[1];
            $arr['bank_card_remark']  = 'no';
        }

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], json_encode($arr), 2);

        \Log::info('代付提交的返回值是',$output);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['status']) && $return['status']==1) {
                return ['status'=>'submitsuccess'];
            } else {
                return ['status'=>'fail'];
            }
        } else {
            return ['status'=>'submitsuccess'];
        }
    }


    public function behalfCallback($input,$thirdPartPay)
    {
        $sign = $input['sign'];
        unset($input['sign']);
        $newsign = $this->generateSignature($input,$thirdPartPay['privateKey']);
        if($newsign == $sign){
            if(isset($input['status']) && $input['status']==5){
                return ['status'=>'success','orderNo'=>$input['order_id'],'thirdOrderNo'=>$input['order_id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
            } else if(isset($input['status']) && $input['status']==3){
                return ['status'=>'fail','orderNo'=>$input['order_id'],'thirdOrderNo'=>$input['order_id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
            }
        }
        
        return false;
    }

    public function successNotice()
    {
        echo 'SUCCESS';
    }
}