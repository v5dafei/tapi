<?php

namespace App\Pay;

use App\Lib\Clog;

class outpppay
{
    use PayCurl;

    const PAYCODE    = 'outpppay';
     
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
            'sendid'                => $thirdPartPay['merchantNumber'],
            'orderid'               => $withdraw->pay_order_number,
            'amount'                => bcdiv($withdraw->real_amount,10000,2),
            
            'paytypes'              => $withdrawinfoArr[0],
            'notifyurl'             => config('main.behalfUrl').'/'.$carrierPayChannelId,
            'bankinfo'              => json_encode(['bank'=>$withdrawinfoArr[0],'account'=>$withdrawinfoArr[1],'name'=>$withdrawinfoArr[2]]),
            'memuid'                => md5($withdrawinfoArr[2])
        ];

        $arr['sign']                 = md5($arr['sendid'].$arr['orderid'].$arr['amount'].$arr['bankinfo'].$thirdPartPay['privateKey']);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
            if(isset($return['code']) && $return['code']==1){
                $returndata = json_decode($return['data'],true);
                if(isset($returndata['state']) && $returndata['state'] ==1) {
                    return ['status'=>'submitsuccess'];
                } else if(isset($returndata['state']) && $returndata['state'] ==4){
                    return ['status'=>'success','order'=>$returndata['id']];
                } else{ 
                    return ['status'=>'fail','message'=>'代付失败'];
                }
            } else{
                return ['status'=>'fail','message'=>$return['msg']];
            }
        } else {
            return ['status'=>'fail','message'=>'对不起,网络异常'];
        }
    }

    public function checked($thirdPartPay,$recvid)
    {
        $output          = $this->request('POST', $thirdPartPay['merchantQueryDomain'].'?id='.$recvid,[], 1);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return   = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code']==1) {
                $data = json_decode($return['data'],true);
                if($data['state']==4){
                    return true;
                } else if($data['state']==8){
                    return false;
                }
            } else{
                return 'unknow';
            }
           
        } else {
            //返回错误
            return 'unknow';
        }
    }

    public function generateSignature($param,$privateKey)
    {
        return md5($param['recvid'].$param['orderid'].$param['amount'].$privateKey);
    }

    public function behalfCallback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE.'代付回调', '', $input);

        $sign           = $input['sign'];
        $newSign        = md5($sign.$thirdPartPay['privateKey']);

        if($input['retsign'] == $newSign) {
            if(isset($input['state'])){
                $result = $this->checked($thirdPartPay,$input['id']);
                if($input['state']==4 && $result===true){
                    return ['status'=>'success','orderNo'=>$input['orderid'],'thirdOrderNo'=>$input['orderid']];
                } else if($input['state']==8 && $result===false){
                    return ['status'=>'fail','orderNo'=>$input['orderid'],'thirdOrderNo'=>$input['orderid']];
                }
            }
        } 
        return false;
    }

    public function successNotice()
    {
        ob_end_clean();
        echo 'susscce';
    }
}