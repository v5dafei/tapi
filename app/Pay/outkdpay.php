<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Clog;

class outkdpay
{
    use PayCurl;

    const PAYCODE    = 'outkdpay'; 

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
            'userCode'        => $thirdPartPay['merchantNumber'],
            'orderCode'       => $withdraw->pay_order_number,
            'amount'          => bcdiv($withdraw->real_amount,10000,0).'.00',
            'address'         => $withdraw->player_digital_address
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code'] ==200){
                return ['status'=>'success','order'=>$return['data']['orderNo']];
            } else{
                return ['status'=>'fail','message'=>$return['message']];
            }
        } else {
            //返回错误
            return ['status'=>'fail'];
        }
    }

    public function generateSignature($data,$privateKey)
    {
        return  strtoupper(md5($data['orderCode'].'&'.$data['amount'].'&'.$data['address'].'&'.$data['userCode'].'&'.$privateKey));
    }
}