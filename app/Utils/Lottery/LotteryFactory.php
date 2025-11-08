<?php
/**
 * 彩票工厂类
 * Date: 2019/7/17
 * Time: 15:42
 */
namespace App\Utils\Lottery;

use App\Exceptions\ErrMsg;

class LotteryFactory
{
    public static function create($gameType) {
        $gameType = strtoupper($gameType);
        $className = '\App\Utils\Lottery\\'. $gameType;
        if (! class_exists($className)) {
            throw new ErrMsg('彩种类不存在:'. $gameType);
        }

        return new $className();
    }
}