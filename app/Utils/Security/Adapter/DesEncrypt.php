<?php
/**
 * Created by PhpStorm.
 * User: Jaylee
 * Date: 16/3/02
 * Time: 23:14
 */

namespace App\Utils\Security\Adapter;

class DesEncrypt {

    private $_secureKey = '';

    public function __construct( $key, $iv = null ) {

        if ( empty( $key ) || strlen( $key ) != 8 ){
            throw new \InvalidArgumentException(sprintf("des key 非法, key = %s", $key));
        }

        $this->_secureKey = trim($key);
    }

    /**
     * @desc 加密
     * @param $str
     * @return mixed
     */
    public function encrypt( $str ){

        $ivArray=array(0x12, 0x34, 0x56, 0x78, 0x90, 0xAB, 0xCD, 0xEF);
        $iv = '';

        foreach ($ivArray as $element) {
            $iv .= chr($element);
        }

        $size   = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC );
        
        $str = $this->_pkcs5Pad($str, $size);

        $data =  mcrypt_encrypt(MCRYPT_DES, $this->_secureKey, $str, MCRYPT_MODE_CBC, $iv);
        
        return base64_encode($data);
    }

    /**
     * @desc 解密
     * @param $str
     * @return bool
     */
    public function decrypt( $str ){
        
        $ivArray=array(0x12, 0x34, 0x56, 0x78, 0x90, 0xAB, 0xCD, 0xEF);
        $iv = '';
        
        foreach ($ivArray as $element) {
            $iv .= chr($element);
        }

        $str = base64_decode($str);
        
        $result = mcrypt_decrypt(MCRYPT_DES, $this->_secureKey, $str, MCRYPT_MODE_CBC, $iv);
        
        $result = $this->_pkcs5Unpad( $result );
        
        return $result;
    }

    private function _pkcs5Pad($text, $blockSize) {
        $pad = $blockSize - (strlen( $text ) % $blockSize);
        return $text . str_repeat( chr( $pad ), $pad );
    }

    private function _pkcs5Unpad($text) {
        $pad = ord( $text {strlen( $text ) - 1} );

        if ($pad > strlen( $text ))
            return false;

        if ( strspn( $text, chr( $pad ), strlen( $text ) - $pad ) != $pad)
            return false;

        return substr ( $text, 0, - 1 * $pad );
    }

}