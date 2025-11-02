<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-11
 * Time: 14:44
 */

namespace App\Utils\Security;


class CryptoDemo
{
    public function __construct () { }

    /**
     * @title
     * @throws \Exception
     */
    public function run() {
        $aes = Crypto::getInstance( Crypto::MODE_AES, 'des_key' );
        var_dump($aes->encrypt('abcdefg'));
        var_dump( $aes->decrypt('GlU3LwJlug19WbslKWcG') );
        echo "<hr />";
        $des = Crypto::getInstance( Crypto::MODE_DES, '12345678' );
        var_dump($des->encrypt('abcdefg'));
        var_dump( $des->decrypt('7vi5Zfc+qQ4='));
        echo "<hr />";
        $des3 = Crypto::getInstance( Crypto::MODE_3DES, '&*(&Q13S', "3)(!U1#!" );
        var_dump( $des3->encrypt('abcdefg'));
        var_dump( $des3->decrypt('Et14K5NpEgY=') );
    }
}