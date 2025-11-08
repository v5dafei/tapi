<?php namespace App\Lib;

include_once ROOT_PATH . '/app/Lib/aliyun-php-sdk-core/Config.php';
use afs\Request\V20180112 as Afs;

//滑动验证  nc_register   nc_register_h5
//智能验证  ic_register   ic_register_h5
//无痕验证  nvc_register  nvc_register_h5

class Behavioralcaptcha{
    static function captcha($input)
    {
        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", config('main')['aliyunaccesskey'], config('main')['aliyunaccesssecret']);
        $client         = new \DefaultAcsClient($iClientProfile);

        \DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", "afs", "afs.aliyuncs.com");

        if(isset($input['dataInfo'])){
            //滑动验证码
            $request        = new Afs\AuthenticateSigRequest();
            $request->setSessionId($input['dataInfo']['SessionId']);// 必填参数，从前端获取，不可更改，android和ios只传这个参数即可
            $request->setToken($input['dataInfo']['Token']);// 必填参数，从前端获取，不可更改
            $request->setSig($input['dataInfo']['Sig']);// 必填参数，从前端获取，不可更改
            $request->setScene($input['dataInfo']['Scene']);// 必填参数，从前端获取，不可更改
            $request->setAppKey(config('main')['aliyunappkey']);//必填参数，后端填写
            $request->setRemoteIp(real_ip());//必填参数，后端填写

            return $client->getAcsResponse($request);//返回code 100表示验签通过，900表示验签失败
        }
    }
}
