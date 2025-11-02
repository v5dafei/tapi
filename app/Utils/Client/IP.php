<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 2019/5/26/0026
 * Time: 16:01
 */

namespace App\Utils\Client;

class IP
{
    /**
     * @title 是否允许
     * @param       $ip
     * @param array $list
     */
    public static function isAllow ( $ip, $list = [] ) {

    }

    /**
     * @title 是否拒绝
     * @param       $ip
     * @param array $list
     */
    public static function isDeny ( $ip, $list = [] ) {

    }

    public static function ipLocation ( $ip, $province = [] ) {
        $area = self::convertIP($ip);
        foreach ( $province as $p ) {
            if ( preg_match("/" . $p . "/", $area) ) {
                $area = $p;
            }
        }
        return $area;
    }

    /**
     * 获取客户端IP
     *
     * @param bool $ip2long
     * @return int|mixed|string
     * @author Michael
     * @time   2019/8/8 14:23
     */
    public static function getClientIp ( $ip2long = false ) {

        if ( isset($_SERVER['HTTP_CLIENT_IP']) )
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) )
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        elseif ( isset($_SERVER['REMOTE_ADDR']) )
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = '0.0.0.0';

        if ( strrpos(',', $ip) >= 0 ) {
            $ip = explode(',', $ip, 2);
            $ip = current($ip);
        }

        return $ip2long ? ip2long($ip) : $ip;
    }

    /**
     * 转换ip到地区
     *
     * @param $ip
     * @return string|null
     */
    public static function convertIP ( $ip ) {
        $dbFile = app_path() . '/Lib/ip/ip2region.db';
        if ( !filter_var($ip, FILTER_VALIDATE_IP) ) {
            return "IP Address Error[$ip]";
        }
        $dbBinStr          = NULL;
        $firstIndexPtr     = 0;
        $lastIndexPtr      = 0;
        $indexBlockLength  = 12;
        $totalHeaderLenght = 8192;

        /**
         * 从字节读取长整型
         *
         * @param string $b
         * @param int    $offset
         */
        $getLong = function ( $b, $offset ) {
            $val = (
                (ord($b[$offset++])) |
                (ord($b[$offset++]) << 8) |
                (ord($b[$offset++]) << 16) |
                (ord($b[$offset]) << 24)
            );

            // convert signed int to unsigned int if on 32 bit operating system
            if ( $val < 0 && PHP_INT_SIZE == 4 ) {
                $val = sprintf("%u", $val);
            }

            return $val;
        };

        /**
         * 32位系统的话将有符号int转换为无符号int
         *
         * @param string ip
         */
        $safeIp2long = function ( $ip ) {
            $ip = ip2long($ip);

            // convert signed int to unsigned int if on 32 bit operating system
            if ( $ip < 0 && PHP_INT_SIZE == 4 ) {
                $ip = sprintf("%u", $ip);
            }

            return $ip;
        };

        //check and load the binary string for the first time
        if ( $dbBinStr == NULL ) {
            $dbBinStr = file_get_contents($dbFile);
            if ( $dbBinStr == false ) {
                return ("Fail to open the db file");
            }

            $firstIndexPtr = $getLong($dbBinStr, 0);
            $lastIndexPtr  = $getLong($dbBinStr, 4);
            $totalBlocks   = ($lastIndexPtr - $firstIndexPtr) / $indexBlockLength + 1;
        }

        if ( is_string($ip) ) $ip = $safeIp2long($ip);

        //binary search to define the data
        $l       = 0;
        $h       = $totalBlocks;
        $dataPtr = 0;
        while ( $l <= $h ) {
            $m   = (($l + $h) >> 1);
            $p   = $firstIndexPtr + $m * $indexBlockLength;
            $sip = $getLong($dbBinStr, $p);
            if ( $ip < $sip ) {
                $h = $m - 1;
            } else {
                $eip = $getLong($dbBinStr, $p + 4);
                if ( $ip > $eip ) {
                    $l = $m + 1;
                } else {
                    $dataPtr = $getLong($dbBinStr, $p + 8);
                    break;
                }
            }
        }

        //not matched just stop it here
        if ( $dataPtr == 0 ) return NULL;

        //get the data
        $dataLen = (($dataPtr >> 24) & 0xFF);
        $dataPtr = ($dataPtr & 0x00FFFFFF);
        $loc     = explode('|', substr($dbBinStr, $dataPtr + 4, $dataLen - 4));
        unset($dbBinStr);
        return sprintf('%s %s %s', $loc[0], $loc[2], $loc[3]);
    }

}