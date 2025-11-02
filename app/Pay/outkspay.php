<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Clog;

class outkspay
{
    use PayCurl;

    const PAYCODE    = 'outkspay';  

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

        $arr =[
            'mchid'        => $thirdPartPay['merchantNumber'],
            'out_trade_no'  => $withdraw->pay_order_number,
            'money'         => bcdiv($withdraw->real_amount,10000,2),
            'notifyurl'     => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'bankname'      => $withdrawinfoArr[0],
            'subbranch'     => '支行',
            'accountname'   => $withdrawinfoArr[2],
            'cardnumber'    => $withdrawinfoArr[1],
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantBindDomain'], $arr, 3);

        \Log::info('刘帮代付请求的值是',$arr);

        if(isset($output['httpCode']) && $output['httpCode']==200){
           $return = json_decode($output['output'],true);
            if(isset($return['status']) && $return['status']=='success' && !empty($return['out_trade_no'])) {
               $flag = $this->checked($thirdPartPay,$return['out_trade_no']);
                \Log::info('查询的返回值是',$flag);
               if(is_array($flag)){
                 return $flag;
               } else if($flag){
                  return $return['out_trade_no'];
               } else {
                  return false;
               }
            }
        } else {
            //返回错误
            return false;
        }
    }

    public function generateSignature($data,$privateKey)
    {
        Ksort($data);
        $str = '';

        foreach ($data as $key => $value) {
            $str.=$key.'='.$value.'&';
        }

        return strtoupper(md5($str.'key='.$privateKey));
    }

    public function checked($thirdPartPay,$recvid)
    {
        $arr =[
            'mchid'        => $thirdPartPay['merchantNumber'],
            'out_trade_no' => $recvid,
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantQueryDomain'], $arr, 3);

        if(isset($output['httpCode']) && $output['httpCode']==200){
           $return = json_decode($output['output'],true);

            if(isset($return['status']) && $return['status'] == 'success') {
                if($return['refCode']==3){
                    return true;
                }else if($return['refCode']==2){
                    return ['order'=>$recvid];
                } else {
                    return false;
                }
            }

            if(isset($return['status']) && $return['status'] == 'success' && ($return['refCode']==3 || $return['refCode']==2) ) {
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
        Clog::payMsg(self::PAYCODE.'反查', '', $input);
        if(isset($input['refCode']) && !empty(trim($input['refCode']))) {
            $sign = $input['sign'];
            unset($input['sign']);
            unset($input['success_time']);

            $playerWithdraw = PlayerWithdraw::where('pay_order_number',$input['out_trade_no'])->first();

            \Log::Info('加密的值是',$input);
            $newSign        = $this->generateSignature($input,$thirdPartPay['privateKey']);
            if($sign == $newSign) {
                \Log::info('进入此方法21',$input);
                if($input['refCode'] ==3) {
                    return ['status'=>true,'orderNumber'=>$input['out_trade_no'],'thirdOrderNumber'=>$input['out_trade_no']];
                } else {
                    return false;
                }
            } 
        } 
    }

    public function successNotice()
    {
        echo 'success';
    }
}