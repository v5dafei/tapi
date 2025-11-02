<?php

namespace App\Sms;

use App\Models\Log\CarrierSms;
use App\Lib\Clog;

class gcx
{
    use SmsCurl;

    const SMSCODE    = 'gcx';  

    public function __get($key)
    {
        return $this->$key;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function sendData($mobile, $carrier,$prefixLanguage, $siteTitle,$smspassage,$verificationCode)
    {

        $content          = config('main')['sms'][self::SMSCODE][$prefixLanguage];

        if($prefixLanguage=='zh'){
            $content          = str_replace('【】','【'.$siteTitle.'】',$content);
        } else {
            $content          = str_replace('[]','['.$siteTitle.']',$content);
        }

        $content    = str_replace('888888',$verificationCode,$content);
        $params     = [
            'appkey'    => $smspassage->appkey,
            'appsecret' => $smspassage->appsecret,
            'phone'     => $mobile,
            'msg'       => $content
        ];

        $url        = $smspassage->sendurl.'/sms/batch/v2?'.http_build_query($params);
        $output     = $this->request('GET', $url);

        Clog::smsMsg(self::SMSCODE, '发送', $output);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
            if(isset($return['code']) && $return['code']=='00000') {
                return ['success'=>true,'uniquire_id'=>$return['uid'],'msg'=>'发送成功'];
            } else {
                return ['success'=>false,'msg'=>config('language')[$prefixLanguage]['error211']];
            }
        } else {
            //返回错误
            return ['success'=>false,'msg'=>config('language')[$prefixLanguage]['error211']];
        }
    }

    public function callback($input,$smspassage)
    {
        Clog::smsMsg(self::SMSCODE, '回调', $input);

        if($input[0]['appkey']==$smspassage->appkey){
            $carrierSms = CarrierSms::where('mobile',$input[0]['phone'])->where('uniquire_id',$input[0]['uid'])->where('sms_passage_id',$smspassage->id)->first();

            if($carrierSms && $carrierSms->status==1 && $input[0]['status']==0){

                $carrierSms->status = 2;
                $carrierSms->save();

                return true;
            } else {
                $carrierSms->status = 3;
                $carrierSms->save();

                return false;
            }
        }
    }

    public function successNotice()
    {
        $data =[
            'code'=>'00000'
        ];
        return json_encode($data);
    }
}