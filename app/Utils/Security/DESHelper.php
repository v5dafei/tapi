<?php
namespace App\Utils\Security;

class DESHelper
{
    const BLOCK_SIZE = 8;
    
    protected $key = '';

    public function __construct($key)
    {
        $this->key = substr($key, 0, self::BLOCK_SIZE);
    }

    public function encrypt($input)
    {
        $data = openssl_encrypt($input, 'des-ecb', $this->key, OPENSSL_RAW_DATA);
        $data = base64_encode($data);           
        return preg_replace("/\s*/", '',$data);
    }

    public function decrypt($encrypted)
    {
        $encrypted = base64_decode($encrypted);
        return openssl_decrypt($encrypted, 'des-ecb', $this->key, OPENSSL_RAW_DATA);
    }
}
