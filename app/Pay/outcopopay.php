<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Cache\CarrierCache;
use App\Lib\Clog;

class outcopopay
{
    use PayCurl;

    const PAYCODE    = 'outcopopay';  

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
            'accessType'    => 1,
            'merchantId'    => $thirdPartPay['merchantNumber'],
            'notifyUrl'     => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'language'      => 'zh-CN',
            'orderNo'       => $withdraw->pay_order_number,
            'bankId'        => '000',
            'bankName'      => '数字币',
            'bankNo'        => $withdraw->player_digital_address,
            'orderAmount'   => $withdrawinfoArr[2],
            'playerId'      => md5($withdraw->player_id),
            'defrayName'    => '数字币',
            'currency'      => 'USDT',
            'payTypeSubNo'  => '1',
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantBindDomain'],json_encode($arr), 2);

        \Log::info('copo的返回值是',$output);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['respCode']) && $return['respCode'] =='000') {
                if(isset($return['orderStatus']) && $return['orderStatus'] == 1){
                     $flag = $this->checked($thirdPartPay,$return['orderNo']);
                     if($flag){
                        return ['status'=>'success','order'=>$return['orderNo']];
                     } else {
                        return ['status'=>'submitsuccess','order'=>$return['orderNo']];
                     }
                } else if(isset($return['orderStatus']) && $return['orderStatus'] == 2){
                    return ['status'=>'fail','message'=>$return['respMsg']];
                } else {
                    return ['status'=>'submitsuccess','order'=>$return['orderNo']];
                }
            } else {
                return ['status'=>'fail'];
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

        $old   = $str;
        $str   = $str.'Key='.$privateKey;
        $sign  = md5($str);

        return $sign;
    }

    public function checked($thirdPartPay,$recvid)
    {
        $arr =[
            'accessType'    => 1,
            'merchantId'    => $thirdPartPay['merchantNumber'],
            'orderNo'       => $recvid,
            'language'      => 'zh-CN'
        ];

        $arr['sign']        = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output             = $this->request('POST', $thirdPartPay['merchantQueryDomain'],json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
    
            if(isset($return['orderStatus']) && $return['orderStatus']==1) {
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
        Clog::payMsg(self::PAYCODE.'代付回调', '', $input);

        $sign = $input['sign'];
        unset($input['sign']);

        if(isset($input['orderStatus'])){
            $playerWithdraw = PlayerWithdraw::where('pay_order_number',$input['orderNo'])->first();
            $newSign        = $this->generateSignature($input,$thirdPartPay['privateKey']);

            if($sign == $newSign) {
                //验签成功
                if($input['orderStatus'] ==1) {
                    //代付成功
                    return ['status'=>'success','orderNo'=>$input['orderNo'],'thirdOrderNo'=>$input['payOrderNo']];
                } else if($input['orderStatus'] ==2){
                    //代付失败
                    return ['status'=>'fail','orderNo'=>$input['orderNo'],'thirdOrderNo'=>$input['payOrderNo']];
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