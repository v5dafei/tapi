<?php
/**
 * URL辅助类
 * Date: 2019/8/1
 * Time: 22:57
 */
namespace App\Utils\Client;

class Url
{
    /**
     * 获取URl前缀
     * @return string
     * @author Michael
     * @time   2019/8/1 22:59
     */
    public static function getUrlPrefix() {
        return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    }
}