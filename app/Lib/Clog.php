<?php namespace App\Lib;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Clog  
{
    static function payMsg($paychannelcode, $msg, $data = []) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/{$paychannelcode}-pay_msg.log";
        $msg     = "支付平台 {$paychannelcode} - 回调: " . $msg;
        self::writeLog($logFile, $msg, $data);
    }

    static function smsMsg($smscode, $msg, $data = []) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/{$smscode}-sms_msg.log";
        $msg     = "SMS平台 {$smscode} - : " . $msg;
        self::writeLog($logFile, $msg, $data);
    }

    static function debugMsg($msg, $data = []) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/debug_msg.log";
        $msg     = "debug- : " . $msg;
        self::writeLog($logFile, $msg, $data);
    }

    static function writeLog($path, $msg, $context) {
        $logger = new Logger('custom_log');
        $logger->pushHandler(new StreamHandler(storage_path($path)));
        if (is_array($context)) {
            $logger->info($msg, $context);
        } else {
            $logger->info($msg, [$context]);
        }
    }

    static function jobAbnormal($msg) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/jobAbnormal.log";
        self::writeLog($logFile, $msg, []);
    }

    static function updateUserLog($msg) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/updateUser.log";
        self::writeLog($logFile, $msg, []);
    }

    static function sensitive($msg,$data=[]) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/sensitive.log";
        self::writeLog($logFile, $msg, $data);
    }

    static function realearning($msg,$data=[]) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/realearning.log";
        self::writeLog($logFile, $msg, $data);
    }

    static function realcommission($msg,$data=[]) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/realcommission.log";
        self::writeLog($logFile, $msg, $data);
    }

    static function recordabnormal($msg,$data=[]) {
        $dateStr = date("Y-m-d");
        $logFile = "logs/{$dateStr}/abnormal.log";
        self::writeLog($logFile, $msg, $data);
    }
}
