<?php
/**
 * 加密函数工具集
 */

namespace App\Utils\Hash;

class HashHelper
{
    /**
     * @title 唯一ID生成
     * @param int   $len
     * @param array $info
     * @return string
     */
    public static function uuid ( $len = 24, $info = [] ) {
        $requestInfo = !empty($info) ? $info : $_SERVER;
        PHP_VERSION < '4.2.0' ? mt_srand((double)microtime() * 1000000) : mt_srand();
        $seed = base_convert(md5(print_r($requestInfo, 1) . microtime()), 16, 35);
        $seed = $seed . 'zZ' . strtoupper($seed);
        $hash = '';
        $max  = strlen($seed) - 1;
        for ( $i = 0; $i < $len; $i++ ) {
            $hash .= $seed[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * @title 客户端ID生成
     */
    public static function clientId($len = 20) {
        return strtolower(self::uuid($len));
    }

    /**
     * @title  订单ID生成
     * @return string
     * @author benjamin
     */
    public static function orderId ($uniqid = null) {
        $microtime = explode('.', microtime(true));

        $orderTime = date('YmdHis');
        if (isset($microtime[1])) {
            $orderTime .= $microtime[1];
        }

        return $orderTime . ($uniqid ? $uniqid : mt_rand(1000, 9999));
    }

    /**
     * @title  数据签名
     */
    public static function dataSign ( $type, $data ) {
        //if (!extension_loaded('swoole_loader')) return '';

        $signAry  = $data;
        $signType = $type;
//        require FRAMEWORK_PATH . 'Config/config.key.gen.php';

        return key_gen($type, $data);
    }
}