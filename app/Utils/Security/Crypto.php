<?php
/**
 * 加解密套件
 *
 * @link https://github.com/sjclijie/php-des (来自github)
 * User: Administrator
 * Date: 2019-06-11
 * Time: 14:36
 */

namespace App\Utils\Security;

use App\Utils\Security\Adapter\AesEncrypt;
use App\Utils\Security\Adapter\Des3Encrypt;
use App\Utils\Security\Adapter\DesEncrypt;

class Crypto
{
    /** AES **/
    const MODE_AES = 1;
    /** DES **/
    const MODE_DES = 2;
    /** 3DES **/
    const MODE_3DES = 3;

    private static $_HANDLE_ARRAY = [];

    private function __construct () { }

    private function __clone () { }

    /**
     * @desc 获取句柄标识
     * @param $params
     * @return mixed
     */
    private static function _getHandleKey ( $params ) {
        ksort($params);
        return md5(implode('_', $params));
    }

    /**
     * @desc 创建一个加密对象
     * @param int     $mode      加密类型
     * @param  string $secretKey 密钥
     * @param null    $iv        IV
     * @return mixed
     * @throws \Exception
     */
    public static function getInstance ( $mode, $secretKey, $iv = null ) {
        if ( empty($secretKey) ) {
            throw new \Exception(sprintf("Fail, aesKey不能为空."));
        }
        $handle_key = self::_getHandleKey([
            'mode'      => $mode,
            'secretKey' => $secretKey
        ]);
        if ( !isset(self::$_HANDLE_ARRAY[$handle_key]) ) {
            switch ( $mode ) {
                case self::MODE_DES:
                    $obj = new DesEncrypt($secretKey);
                    break;
                case self::MODE_3DES:
                    $obj = new Des3Encrypt($secretKey, $iv);
                    break;
                case self::MODE_AES:
                default:
                    $obj = new AesEncrypt($secretKey);
                    break;
            }
            self::$_HANDLE_ARRAY[$handle_key] = $obj;
        }
        return self::$_HANDLE_ARRAY[$handle_key];
    }

}