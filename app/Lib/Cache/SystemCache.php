<?php namespace App\Lib\Cache;

use App\Models\Conf\CarrierPayChannel;
use App\Models\Def\PayChannel;
use App\Models\Def\Banks;
use App\Models\Def\MainGamePlat;
use App\Models\CarrierBankCard;
use App\Models\Area;
use App\Models\Def\Development;


class SystemCache
{
    public static $store    = "redis";

    static function getChannelMap() 
    {
        $key    = 'def_pay_channel_list';
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $paychannels = PayChannel::all();
        $data        = [];

        foreach ($paychannels as $key => $value) {
            $data[$value->id] = $value->channel_name;
        }

        $cache->put($key, $data);

        return $data;
    }

    static function forgetChannelMap()
    {
        $tag      = 'def_pay_channel_list';
        $cache    = cache()->store(self::$store);

        $cache->flush();
    }

    static function getBank($bankid) 
    {
        $key    = 'def_bank_'.$bankid;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $bank = Banks::where('id',$bankid)->first();

        $cache->put($key, $bank);

        return $bank;
    }

    static function forgetBank($bankid) 
    {
        $key    = 'def_bank_'.$bankid;
        $cache  = cache()->store(self::$store);

        $cache->flush();
    }

    static function getAreaList()
    {
        $key    = 'def_area';
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $provinces =  Area::select('id','name','parent_id')->where('type',2)->orderBy('id','asc')->get();
        foreach ($provinces as $key => &$value) {
            $value->citys = Area::select('id','name','parent_id')->where('type',3)->where('parent_id',$value->id)->orderBy('id','asc')->get();
        }

        $cache->put($key, $provinces);

        return $provinces;
    }
    
    static function getMainGamePlat($mainGamePlatCode)
    {
        $key    = 'mainGamePlatCode_'.$mainGamePlatCode;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $mainGamePlat  = MainGamePlat::where('main_game_plat_code',$mainGamePlatCode)->first();

        $cache->put($key, $mainGamePlat);

        return $mainGamePlat;
    }

    static function forgetMainGamePlat($mainGamePlatCode) 
    {
        $key    = 'mainGamePlatCode_'.$mainGamePlatCode;
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            $cache->forget($key);
        }
    }

    static function getAddMoneySign()
    {
        $key    = 'addMoney';
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $sign  = Development::where('type',1)->pluck('sign')->toArray();

        $cache->put($key, $sign);

        return $sign;
    }
}
