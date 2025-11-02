<?php
/**
 * Created by PhpStorm.
 * User: thor
 * Date: 2022/7/26
 * Time: 20:41
 */

namespace Utils\Hash;


class AesHelper
{

    public static function encode($key, $iv, $data)
    {
        return rtrim(strtr(base64_encode(openssl_encrypt(serialize($data),'aes-256-xts',$key,OPENSSL_RAW_DATA,$iv)),'+/', '-_'),'=');
    }

    public static function decode($key, $iv, $string)
    {
        return unserialize(openssl_decrypt(base64_decode(str_pad(strtr($string,'-_','+/'), strlen($string) % 4,'=',STR_PAD_RIGHT)),'aes-256-xts',$key,OPENSSL_RAW_DATA,$iv));
    }

}