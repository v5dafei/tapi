<?php namespace App\Lib\Cache;

use App\Models\Conf\CarrierWebSite;
use App\Models\Conf\CarrierPayChannel;
use App\Models\CarrierBankCard;
use App\Models\Log\PlayerBetFlow;
use App\Models\Report\ReportGamePlatStatDay;
use App\Models\Def\PayChannel;
use App\Lib\Cache\SystemCache;
use App\Models\Carrier;
use App\Models\Player;
use App\Models\PlayerLevel;
use App\Models\CarrierPreFixDomain;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\PlayerGameAccount;

class PrefixCache
{

    public static $store    = "redis";

    static function getDefaultPlayerLevelName($prefix)
    {
        $key    = 'playerlevelname_prefix_'.$prefix;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $defaultPlayerLevel = PlayerLevel::where('prefix',$prefix)->where('is_default',1)->first();
        $cache->put($key, $defaultPlayerLevel->groupname,now()->addMinutes(10));

        return $defaultPlayerLevel;
    }

    static function forgetPlayerLevelName($prefix)
    {
        $key    = 'playerlevelname_prefix_'.$prefix;
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            $cache->forget($key);
        }

        return true;
    }
}
