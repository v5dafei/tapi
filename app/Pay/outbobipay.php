<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Clog;

class outbobipay
{
    use PayCurl;

    const PAYCODE    = 'outbobipay'; 

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
        $amount                 = bcdiv($withdraw->real_amount,10000,0);
        $arr =[
            'callback_url'        => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'user_adress'         => $withdraw->player_digital_address,
            'money'               => intval($amount),
            'cp_order_id'         => $withdraw->pay_order_number,
            'mch_id'              => $thirdPartPay['merchantNumber'],
            'currency_id'         => 1
            
        ];

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code'] ==0){
                if($return['status']==1){
                    $flag      = $this->checked($thirdPartPay,$withdraw->pay_order_number);
                    if($flag === true){
                        return ['status'=>'success','order'=>$return['order_id']];
                    } else{
                        return ['status'=>'submitsuccess','order'=>$return['data']['orderId']];
                    }
                } else if($return['status']==2){
                    return ['status'=>'fail','message'=>$return['msg']];
                } else{
                    return ['status'=>'submitsuccess'];
                }
                
            } else{
                return ['status'=>'fail'];
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

        $str   = $str.'pri_key='.$privateKey;

        return strtolower(md5($str));
    }

    public function checked($thirdPartPay,$recvid)
    {
        $arr =[
            'cp_order_id'         => $recvid,
            'mch_id'              => $thirdPartPay['merchantNumber'],
        ];

        $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return    = json_decode($output['output'],true);
    
            if(isset($return['status']) && $return['status']==1) {
                return true;
            } else if(isset($return['status']) && $return['status']==2){
                return false;
            } else{
                return 'unknow';
            }
            
        } else {
            //返回错误
            return 'unknow';
        }
    }

    public function queryGenerateSignature($data,$privateKey)
    {
        return  md5($data['sendid'].$data['orderid'].$data['amount'].$privateKey);
    }

    public function behalfCallback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE.'代付回调', '', $input);
        $sign = $input['sign'];
        unset($input['sign']);
        $newSign = $this->generateSignature($input,$thirdPartPay['privateKey']);

        if($sign == $newSign) {
            $flag = $this->checked($thirdPartPay,$input['cp_order_id']);
            if($input['status'] ==1 && $flag === true) {
                return ['status'=>true,'orderNo'=>$input['cp_order_id'],'thirdOrderNo'=>$input['cp_order_id']];
            } else if($input['status'] ==2 && $flag === false){
                return ['status'=>'fail','orderNo'=>$input['cp_order_id'],'thirdOrderNo'=>$input['cp_order_id']];
            } else{
                exit;
            }
        } else{
            exit;
        }
    }

    public function successNotice()
    {
        ob_end_clean();
        echo 'ok';
    }
}