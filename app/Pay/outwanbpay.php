<?php

namespace App\Pay;

use App\Lib\Clog;

class outwanbpay
{
    use PayCurl;

    const PAYCODE    = 'outwanbpay'; 

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
            'userid'        => $thirdPartPay['merchantNumber'],
            'amount'        => bcdiv($withdraw->real_amount,10000,0).'.00',
            'orderid'       => $withdraw->pay_order_number,
            'account'       => $withdraw->player_digital_address,
            'currency'      => 'wanb',
            'notifyurl'     => config('main.behalfUrl').'/'.$carrierPayChannelId
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
        return  md5($data['userid'].$data['amount'].$data['orderid'].$data['account'].$privateKey);
    }

    public function checked($thirdPartPay,$recvid)
    {
        $sign          = md5($recvid.$thirdPartPay['privateKey']);
        $output        = $this->request('GET', $thirdPartPay['merchantQueryDomain'].'?id='. $recvid.'&sign='.$sign);

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

    public function behalfCallback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE, '反查', $input);

        $sign = $input['sign'];
        

        if(isset($input['state']) && $input['state']==4){
            $newsign    = md5($input['userid'].$input['amount'].$input['orderid'].$thirdPartPay['privateKey']);
            if($newsign == $sign){
                $data = ['orderNo'=>$input['orderid'],'thirdOrderNo'=>$input['id'],'merchantNumber'=>$thirdPartPay['merchantNumber'],'privateKey'=>$thirdPartPay['privateKey']];
                $flag = $this->checked($thirdPartPay,$input['id']);

                if($flag) {
                    $data['status'] = true;
                    return $data;
                }
            }
        }
        return false;
    }

    public function successNotice()
    {
        echo 'OK';
    }
}