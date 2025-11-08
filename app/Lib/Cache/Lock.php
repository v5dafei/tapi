<?php namespace App\Lib\Cache;

use Illuminate\Support\Facades\Redis;
use Illuminate\Cache\RedisLock;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Def\PayChannel;
use App\Models\Def\Banks;
use App\Models\Def\MainGamePlat;
use App\Models\CarrierBankCard;
use App\Models\Area;


class Lock
{
    public static $store    = "redis";

    static function addLock($key,$time=60)
    {
        $redis     = Redis::connection();
        $redisLock = new RedisLock($redis, $key . '_lock', $time);

        if($redisLock->acquire()){
            return $redisLock;
        } else{
            return false;
        }
    }

    static function release($redisLock)
    {
        $redisLock->release();
        return true;
    }
}
