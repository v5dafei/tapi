<?php

namespace App\Pay;

use App\Models\Log\PlayerWithdraw;
use App\Lib\Clog;

class outebpay
{
    use PayCurl;

    const PAYCODE    = 'outebpay'; 

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
            'merchantNo'      => $thirdPartPay['merchantNumber'],
            'merchantOrderId' => $withdraw->pay_order_number,
            'userName'        => md5(real_ip()),
            'orderType'       => 2182,
            'payAmount'       => bcdiv($withdraw->real_amount,10000,2),
            'bankNum'         => $withdraw->player_digital_address,
        ];

       $arr['sign']                 = $this->generateSignature($arr,$thirdPartPay['privateKey']);
       $arr['deviceType']            = 9;
       $arr['userIp']                = real_ip();
       $arr['notifyUrl']             = config('main.behalfUrl').'/'.$carrierPayChannelId;
       $arr['amountType']            = 1;
       $arr['virtualProtocol']       = 0;

       $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code'] == 200){
                return ['status'=>'submitsuccess','order'=>$return['data']['orderId']];
            } else{
                return ['status'=>'fail','message'=>$return['msg']];
            }
        } else {
            //返回错误
            return ['status'=>'fail'];
        }
    }

    public function generateSignature($param,$privateKey)
    {
        $str = '';
        foreach ($param as $key => $value) {
            $str.= $key.'='.$value.'&';
        }

        $old   = $str;
        $str   = $str.'key='.$privateKey;
        $sign  = md5($str);
        return $sign;
    }

    public function checked($thirdPartPay,$recvid)
    {
        $output        = $this->request('GET', $thirdPartPay['merchantQueryDomain'].'?id='. $recvid);
        $arr           = [
            'merchantNo'      => $thirdPartPay['merchantNumber'],
            'merchantOrderId' => $recvid
        ];

        $arr['sign']   = $this->generateSignature($arr,$thirdPartPay['privateKey']);
        $output        = $this->request('POST', $thirdPartPay['merchantQueryDomain'], http_build_query($arr), 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
    
            if(isset($return['code']) && $return['code']==200) {
                if($return['data']['orderStatus']==1){
                    return true;
                } else if($return['data']['orderStatus']==2){
                    return false;
                }
            }
            
        } else {
            //返回错误
            return 'unknown';
        }
    }

    public function behalfCallback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE.'回调', '', $input);
        if(isset($input['orderStatus']) && ($input['orderStatus']==1 || $input['orderStatus']==2)){
            if(isset($input['merchantOrderId']) && !empty(trim($input['merchantOrderId']))) {

                $sign           = $input['sign'];
                $playerWithdraw = PlayerWithdraw::where('pay_order_channel_trade_number',$input['orderNo'])->first();

                $newSign        = 'orderNo='.$input['orderNo'].'&merchantOrderId='.$input['merchantOrderId'].'&merchantNo='.$input['merchantNo'].'&payTypeId='.$input['payTypeId'].'&orderStatus='.$input['orderStatus'].'&orderAmount='.$input['orderAmount'].'&paidAmount='.$input['paidAmount'].'&key='.$thirdPartPay['privateKey'];
                $newSign        = md5($newSign);

                if($sign == $newSign) {
                    
                    $flag = $this->checked($thirdPartPay,$input['merchantOrderId']);

                    if($input['orderStatus'] ==1) {
                       // $flag = 
                        if($flag===true){
                            return ['status'=>'success','orderNo'=>$input['merchantOrderId'],'thirdOrderNo'=>$input['orderNo']];
                        } else{
                            //不一致退出
                            exit;
                        }
                    } elseif($input['orderStatus'] ==2){
                        if(!$flag){
                            return ['status'=>'fail','orderNo'=>$input['merchantOrderId'],'thirdOrderNo'=>$input['orderNo']];
                        } else{
                            exit;
                        }
                    }
                } 
            } 
        }
    }

    public function successNotice()
    {
        ob_end_clean();

        $data =[
            'code'=>200,
            'msg'=>'成功'
        ];
        echo json_encode($data);
    }
}