<?php

namespace App\Sms;

use App\Models\Log\CarrierSms;
use App\Lib\Clog;

class ms
{
    use SmsCurl;

    const SMSCODE    = 'ms';  

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
        $content          = config('main')['sms'][self::SMSCODE][$prefixLanguage].$verificationCode;
        $params     = [
            'msg'       => $content,
            'receiver'  => $mobile,
            'name'      => $smspassage->appcode,
            'pwd'       => $smspassage->appsecret
        ];

        $params['checksum'] = md5($content.$mobile.$smspassage->appkey);
        $url                = $smspassage->sendurl.'?'.http_build_query($params);
        $output             = $this->request('GET', $url);

        Clog::smsMsg(self::SMSCODE, '发送', $output);

         if(isset($output['httpCode']) && $output['httpCode']==200){
            $return = json_decode($output['output'],true);
            if(isset($return['IsSuccess']) && $return['IsSuccess']==true) {
                return ['success'=>true,'uniquire_id'=>$return['ID'],'msg'=>'发送成功'];
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
        $carrierSms = CarrierSms::where('uniquire_id',$input['id'])->first();

        if($carrierSms && $carrierSms->status==1 && $input['result']=='delivered'){
            $carrierSms->status = 2;
            $carrierSms->save();

            return true;
        } else {
            $carrierSms->status = 3;
            $carrierSms->save();

            return false;
        }
    }

    public function successNotice()
    {
        $data =[
            'success'=>'true'
        ];
        return json_encode($data);
    }
}