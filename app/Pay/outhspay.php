<?php

namespace App\Pay;

use App\Lib\Clog;

class outhspay
{
    use PayCurl;

    const PAYCODE    = 'outhspay';
     
    public $transfer = 1; 

    public function __get($key)
    {
        return $this->$key;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

   public function paymentOnBehalf($withdraw, $thirdPartPay,$playerBankCard,$carrierPayChannelId,$bankType)
    {
        $withdrawinfoArr     = explode('|',$withdraw->collection);

        $arr =[
            'merchantId'            => $thirdPartPay['merchantNumber'],
            'version'               => '1.0.0',
            'merchantOrderNo'       => $withdraw->pay_order_number,
            'amount'                => bcdiv($withdraw->real_amount,10000,2),
            'bankCode'              => $bankType,
            'bankcardAccountNo'     => $withdrawinfoArr[1],
            'bankcardAccountName'   => $withdrawinfoArr[2],
            'notifyUrl'             => config('main.behalfUrl').'/'.$carrierPayChannelId
        ];

        $sign                        = $this->splicing($arr);
        $privateKeyStr               = $this->privateKeyStr($thirdPartPay['rsaPrivateKey']);
        $arr['sign']                 = $this->generateSignature($sign,$privateKeyStr);
        $output                      = $this->request('POST', $thirdPartPay['merchantBindDomain'],json_encode($arr,JSON_UNESCAPED_UNICODE), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);

            if(isset($return['code']) && $return['code'] =='0') {
                if($return['status']==0){
                    return ['status'=>'submitsuccess'];
                } elseif($return['status'] ==2){
                    return ['status'=>'fail','message'=>$return['msg']];
                } elseif($return['status'] ==1){
                    return ['status'=>'success','order'=>$return['merchantOrderNo']];
                }
            } else {
                \Log::info('恒生代付异常的返回值是',['a'=>$return]);        
                return ['status'=>'fail','message'=>$return['msg']];
            }
        } else {
            return ['status'=>'fail'];
        }
    }

    public function splicing($arr)
    {
        ksort($arr);
        $signmd5="";        
        foreach($arr as $x=>$x_value)
        {
            if(!$x_value==""||$x_value==0){
                if($signmd5==""){
                    $signmd5 =$signmd5.$x .'='. $x_value;
                }else{
                    $signmd5 = $signmd5.'&'.$x .'='. $x_value;
                }
            }
        }
        return $signmd5;
    }

    public function publicKeyStr($publicStr){
        //公钥
        $public_key = "-----BEGIN PUBLIC KEY-----\r\n";
        foreach (str_split($publicStr,64) as $str){
            $public_key .= $str . "\r\n";
        }
        $public_key .="-----END PUBLIC KEY-----";

        return $public_key;

    }

    //拼接私钥字符串
    public function privateKeyStr($privatekey){

        $private_key = "-----BEGIN PRIVATE KEY-----\r\n";
        foreach (str_split($privatekey,64) as $str){
            $private_key .= $str . "\r\n";
        }
        $private_key .="-----END PRIVATE KEY-----";

        return $private_key;
    }

    public function verify($plainText, $sign, $path)
    {   
        $resource = openssl_pkey_get_public($path);
        $result = openssl_verify($plainText, base64_decode($sign), $resource);
        openssl_free_key($resource);  
        return $result;
    }

    public function generateSignature($plainText,$path)
    {
        $resource = openssl_pkey_get_private($path);       
        $result = openssl_sign($plainText, $sign, $resource);
        openssl_free_key($resource);
        return base64_encode($sign);
    }

    public function checked($thirdPartPay,$recvid)
    {
        $arr =[
            'merchantId'             => $thirdPartPay['merchantNumber'],
            'version'                => '1.0.0',
            'merchantOrderNo'        => $recvid,
            'submitTime'             => date('YmdHis')
        ];

        $sign                        = $this->splicing($arr);
        $privateKeyStr               = $this->privateKeyStr($thirdPartPay['rsaPrivateKey']);
        $arr['sign']                 = $this->generateSignature($sign,$privateKeyStr);
        $output                      = $this->request('POST', $thirdPartPay['merchantQueryDomain'],json_encode($arr), 2);

        if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
    
            if(isset($return['code']) && $return['code']==0) {
                if($return['status'] == 1){
                    return true;
                } else if($return['status'] ==2){
                    return false;
                } else {
                    return 'unknow';
                }
            } else {
                return 'unknow';
            }
        } else {
            //返回错误
            return 'unknow';
        }
    }

    public function behalfCallback($input,$thirdPartPay)
    {
        Clog::payMsg(self::PAYCODE.'代付回调', '', $input);

        $sign = $input['sign'];
        unset($input['sign']);

        if(isset($input['status'])){
            $inputStr       = $this->splicing($input);
            $rsaPublicStr   = $this->publicKeyStr($thirdPartPay['rsaPublicKey']);
            $flag           = $this->verify($inputStr,$sign,$rsaPublicStr);

            if($flag) {
                $result = $this->checked($thirdPartPay,$input['merchantOrderNo']);
                //验签成功
                if($input['status'] ==1 && $result===true) {
                    //代付成功
                    return ['status'=>'success','orderNo'=>$input['merchantOrderNo'],'thirdOrderNo'=>$input['merchantOrderNo']];
                } else if($input['status'] ==2 && $result=== false){
                    //代付失败
                    return ['status'=>'fail','orderNo'=>$input['merchantOrderNo'],'thirdOrderNo'=>$input['merchantOrderNo']];
                } else {
                    exit;
                }
            } else {
                \Log::info('验签不正确');
                exit;
            }
        } 
    }

    public function successNotice()
    {
        ob_end_clean();
        echo 'success';
    }
}