<?php
/**
 * Created by PhpStorm.
 * User: Jaylee
 * Date: 16/3/02
 * Time: 23:14
 */

namespace App\Utils\Security\Adapter;

class Des3Encrypt {

    private $_secureKey = '';

    private $_iv = '#(2$)!1';
    
    public function __construct($key , $iv = null){

        if ( empty($key) ){
            throw new \InvalidArgumentException("3des key 非法, key = %s", $key);
        }

        if ( !empty($iv) ){
            $this->_iv = $iv;
        }

        $this->_secureKey = $this->_padding(trim($key));
    }

    public function encrypt( $str ){

        //使用MCRYPT_3DES算法,cbc模式
        $td = mcrypt_module_open( MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        
        mcrypt_generic_init($td, $this->_secureKey, $this->_iv);
        
        //初始处理
        $data = mcrypt_generic($td, $str);
        
        //清理加密模块
        mcrypt_generic_deinit($td);
        
        //结束
        mcrypt_module_close($td);
        
        return $this->_removeBr(base64_encode($data));
    }

    public function decrypt( $str ){
        
        $str = base64_decode($str);

        //使用MCRYPT_3DES算法,cbc模式
        $td = mcrypt_module_open( MCRYPT_3DES,'',MCRYPT_MODE_CBC,'');
        
        mcrypt_generic_init($td, $this->_secureKey, $this->_iv);
        
        //初始处理
        $str = mdecrypt_generic($td, $str);
        
        //清理加密模块
        mcrypt_generic_deinit($td);
        
        //结束
        mcrypt_module_close($td);
        
        return $this->_removePadding($str);
    }
    
    //填充密码，填充至8的倍数
    private function _padding( $str ) {
        $len = 8 - strlen( $str ) % 8;
        
        for ( $i = 0; $i < $len; $i++ ) {
            $str .= chr( 0 );
        }
        
        return $str;
    }

    //删除回车和换行
    private function _removeBr($str) {
        $len = strlen( $str );
        $newStr = "";
        $str = str_split($str);
        
        for ($i = 0; $i < $len; $i++ ) {
            if ($str[$i] != '\n' and $str[$i] != '\r') {
                $newStr .= $str[$i];
            }
        }
        return $newStr;
    }

    //删除填充符
    private function _removePadding( $str ) {
        $len = strlen( $str );
        $newStr = "";
        $str = str_split($str);
        
        for ($i = 0; $i < $len; $i++ ) {
            if ($str[$i] != chr( 0 )) {
                $newStr .= $str[$i];
            }
        }
        
        return $newStr;
    }
}