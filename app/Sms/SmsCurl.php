<?php
namespace App\Sms;

trait SmsCurl
{

    static function request($method = 'GET', $url, $param = [], $contentType = 1,$otherHeaders=[])
    {
        $ch = curl_init($url); //请求的URL地址
        if($contentType == 1) {
            $headersArray[]   = "Content-Type: application/x-www-form-urlencoded";
            if(count($otherHeaders)){
                foreach ($otherHeaders as $key => $value) {
                    $headersArray[]=$value;
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArray);

        } else if($contentType == 2){
            $headers[]   = "Content-Type: application/json";
            if(count($otherHeaders)){
                foreach ($otherHeaders as $key => $value) {
                    $headers[]=$value;
                }
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        } elseif ($contentType == 3) {
            $headers[]   = "Content-Type: multipart/form-data";
            if(count($otherHeaders)){
                foreach ($otherHeaders as $key => $value) {
                    $headers[]=$value;
                }
            }
        }

        if($method == 'POST' && ($contentType == 1 || $contentType ==2 || $contentType ==3)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $param); //$post_data JSON类型字符串
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //        curl_setopt($ch, CURLOPT_INTERFACE,config('game')['pub']['AddressIp']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $output    = curl_exec($ch);
        $error     = curl_error($ch);
        $httpCode  = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!empty($error))
        {
            \Log::info('错误信息是'.$error);
            \Log::info('错误信息是',['httpCode'=>$httpCode,'output'=>false]);
            return ['httpCode'=>$httpCode,'output'=>false];
        } else {
            return ['httpCode'=>$httpCode,'output'=>$output];
        }
    }
}
