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

class CarrierCache
{

    public static $store    = "redis";

    static function getCarrierIds()
    {
        $key    = 'carrierIds';
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $carrierIds = Carrier::pluck('id')->toArray();
        $cache->put($key, $carrierIds);

        return $carrierIds;
    }

    static function forgetCarrierIds()
    {
        $key    = 'carrierIds';
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            $cache->forget($key);
        }

        return true;
    }

    static function updateCarrierIds()
    {
        $key        = 'carrierIds';
        $cache      = cache()->store(self::$store);
        $carrierIds = Carrier::pluck('id')->toArray();
        $cache->put($key, $carrierIds);

        return  true;
    }

     static function getExistCarrierGamePlatDay($carrierId, $day, $mainPlatId) 
     {
        $key    = 'exist_carrier_gameplat_day_'.$carrierId.'_'.$day.'_'.$mainPlatId;

        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $reportGamePlatStatDay = ReportGamePlatStatDay::where('carrier_id',$carrierId)->where('day',$day)->where('main_game_plat_id',$mainPlatId)->first();
        if($reportGamePlatStatDay){
            $cache->forever($key, true);
            return true;
        } else{
            $cache->forever($key, false);
            return false;
        }
    }

     static function setExistCarrierGamePlatDay($carrierId, $day, $mainPlatId) 
     {
        $key    = 'exist_carrier_gameplat_day_'.$carrierId.'_'.$day.'_'.$mainPlatId;
        $cache  = cache()->store(self::$store);
        
        $cache->forever($key, true);
    }

    static function getCarrierGamePlatDay($carrierId, $day, $mainPlatId) 
     {
        $key    = 'carrier_gameplat_day_'.$carrierId.'_'.$day.'_'.$mainPlatId;

        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $reportGamePlatStatDay = ReportGamePlatStatDay::where('carrier_id',$carrierId)->where('day',$day)->where('main_game_plat_id',$mainPlatId)->first();

        $cache->forever($key, $reportGamePlatStatDay);

        return $reportGamePlatStatDay;
    }

    static function setCarrierGamePlatDay($carrierId, $day, $mainPlatId,$value) 
     {
        $key    = 'carrier_gameplat_day_'.$carrierId.'_'.$day.'_'.$mainPlatId;

        $cache  = cache()->store(self::$store);

        $cache->forever($key, $value);

        return true;
    }

     static function getCarrierConfigure($carrierId, $key) 
     {
        $tag    = 'conf_carrier_web_site_'.$carrierId;

        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierConfig = CarrierWebSite::getConfigByKey($carrierId, $key);
        $cache->tags($tag)->forever($key, $carrierConfig);

        return $carrierConfig;
    }

    static function getExistCarrierMultipleFront($carrierId,$prefix)
    {
        $key    = 'exist_'.$carrierId.'_'.$prefix;
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $carrierMultipleFront = CarrierMultipleFront::where('carrier_id',$carrierId)->where('prefix',$prefix)->first();
        if($carrierMultipleFront){
            $cache->forever($key, true);
            return true;
        } else{
            return false;   
        }
    }

    static function flushCarrierConfigure($carrierId) 
    {
        $tag    = 'conf_carrier_web_site_'.$carrierId;
        $cache  = cache()->store(self::$store);

        $cache->tags($tag)->flush();
    }

    static function getCarrierMultipleConfigure($carrierId, $key,$prefix) 
     {
        $tag    = 'conf_carrier_web_site_'.$prefix.'_'.$carrierId;

        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierConfig = CarrierMultipleFront::where('carrier_id',$carrierId)->where('prefix',$prefix)->where('sign',$key)->first();
        $cache->tags($tag)->forever($key, $carrierConfig->value);

        return $carrierConfig->value;
    }

    static function flushCarrierMultipleConfigure($carrierId,$prefix) 
    {
        $tag    = 'conf_carrier_web_site_'.$prefix.'_'.$carrierId;
        $cache  = cache()->store(self::$store);

        $cache->tags($tag)->flush();
    }

    static function getCarrierPlayerLevel($issystem,$prefix){

        $tag    = 'carrier_player_level';
        $key    = 'player_level_'.$issystem.'_'.$prefix;

        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else{
            if($issystem==1){
                $playerLevels = PlayerLevel::where('is_system',1)->where('prefix',$prefix)->where('is_default',0)->pluck('id')->toArray();
            } else {
                $playerLevels = PlayerLevel::where('is_system',2)->where('prefix',$prefix)->orderBy('sort','asc')->get();
            }
                $cache->tags($tag)->put($key, $playerLevels,now()->addMinutes(10));
                return $playerLevels;
        }
     }

    static function flushCarrierPlayerLevel($carrierId) 
    {
        $tag    = 'carrier_player_level_'.$carrierId;
        $cache  = cache()->store(self::$store);

        $cache->flush();
    }

    static function getDefaultAgent($carrierId)
    {
        $key    = 'carrier_Agent_'.$carrierId;

        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $defaultAgent   = Player::where('carrier_id',$carrierId)->where('user_name',self::getCarrierConfigure($carrierId,'default_user_name'))->first();
        $cache->put($key, $defaultAgent);

        return $defaultAgent;
    }

    static function forgetDefaultAgent($carrierId) 
    {
        $key    = 'carrier_Agent_'.$carrierId;
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            $cache->forget($key);
        }

        return true;
    }

    static function flushCarrierCache($carrierId,$tag) 
    {
        $_tag    = $tag.'_'.$carrierId;
        $cache   = cache()->store(self::$store);
        
        $cache->tags($_tag)->flush();

        return true;
    }

    static function getPayoutTop($carrierId)
    {
        $key    = 'payoutTop_'.$carrierId;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $playerBetFlowIds = PlayerBetFlow::where('carrier_id',$carrierId)->where('game_status',1)->where('company_win_amount','<',0)->orderBy('id','desc')->limit(2000)->pluck('id')->toArray();

        $playerBetFlows = PlayerBetFlow::select('game_name','player_id','company_win_amount')
            ->whereIn('id',$playerBetFlowIds)
            ->orderBy('company_win_amount','asc')
            ->limit(30)
            ->get();

        foreach ($playerBetFlows as $key => $value) {
            $allUserName                  = PlayerCache::getPlayerUserName($value->player_id);
            $star                         = strlen($allUserName)-2;
            $str                          = '';
            for($i=0;$i<$star;$i++){
                $str.='*';
            }
            
            $value->user_name             = substr($allUserName,0,1).$str.substr($allUserName,-1);
            $value->company_win_amount    = abs(bcdiv($value->company_win_amount,1,2));
        }

        $cache->put($key, $playerBetFlows,now()->addMinutes(10));
        
        return $playerBetFlows;
    }

    static function getCarrierBankCard($carrierId,$carrierBankcardId) 
    {
        $tag    = 'carrier_bank_'.$carrierId;
        $key    = 'inf_player_bank_cards_'.$carrierBankcardId;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierBankCard = CarrierBankCard::where('id',$carrierBankcardId)->first();

        $cache->tags($tag)->put($key, $carrierBankCard);

        return $carrierBankCard;
    }

    static function forgetCarrierBankCard($carrierId,$carrierBankcardId) 
    {
        $tag    = 'carrier_bank_'.$carrierId;
        $key    = 'inf_player_bank_cards_'.$carrierBankcardId;
        $cache  = cache()->store(self::$store);

        if ($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }

        return true;
    }

    static function flushCarrierInit($carrierId) 
    {
        $tag     = 'carrier_init_'.$carrierId;
        $cache   = cache()->store(self::$store);
        
        $cache->tags($tag)->flush();

        return true;
    }

    static function getCarrierPayChannel($carrierId,$carrierPaychannelId)
    {
        $tag    = 'carrier_pay_channel_'.$carrierId;
        $key    = 'inf_carrier_pay_channel_'.$carrierPaychannelId;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierPayChannel = CarrierPayChannel::where('id',$carrierPaychannelId)->first();

        $cache->tags($tag)->put($key, $carrierPayChannel);

        return $carrierPayChannel;
    }

    static function forgetCarrierPayChannel($carrierId,$carrierPaychannelId) 
    {
        $tag    = 'carrier_pay_channel_'.$carrierId;
        $key    = 'inf_carrier_pay_channel_'.$carrierPaychannelId;
        $cache  = cache()->store(self::$store);

        if ($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }

        return true;
    }

    //域名获取前辍
    static function getPreFixByDomain($domain)
    {
        $tag    = 'carrier_domain';
        $key    = md5($domain);
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierPreFixDomains = CarrierPreFixDomain::all();
        foreach ($carrierPreFixDomains as $key => $value) {
            $domains = explode(',',$value->domain);
            if(in_array($domain,$domains)){
                 $cache->tags($tag)->put($key, $value->prefix);
                 return $value->prefix;
            }
        }
    }

    static function forgetPreFix() 
    {
        $tag    = 'carrier_domain';
        $cache  = cache()->store(self::$store);

        $cache->tags($tag)->flush();
        return true;
    }

    //获取语言
    static function getLanguageByDomain($domain)
    {
        $tag    = 'carrier_language';
        $key    = md5($domain);
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierPreFixDomains = CarrierPreFixDomain::all();
        foreach ($carrierPreFixDomains as $key => $value) {
            $domains = explode(',',$value->domain);
            if(in_array($domain,$domains)){
                 $cache->tags($tag)->put($key, $value->language);
                 return $value->language;
            }
        }
    }

    //根椐前辍获取语言
    static function getLanguageByPrefix($prefix)
    {
        $tag    = 'carrier_language';
        $key    = md5($prefix);
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierPreFixDomain = CarrierPreFixDomain::where('prefix',$prefix)->first();
        $cache->tags($tag)->put($key, $carrierPreFixDomain->language);

        return $carrierPreFixDomain->language;
    }

    static function forgetLanguage() 
    {
        $tag    = 'carrier_language';
        $cache  = cache()->store(self::$store);

        $cache->tags($tag)->flush();
        return true;
    }

    //获取短信发送信息
    static function getSmsPassageIdByDomain($domain)
    {
        $tag    = 'carrier_smspassageid';
        $key    = md5($domain);
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierPreFixDomains = CarrierPreFixDomain::all();
        foreach ($carrierPreFixDomains as $key => $value) {
            $domains = explode(',',$value->domain);
            if(in_array($domain,$domains)){
                 $cache->tags($tag)->put($key, $value->sms_passage_id);
                 return $value->sms_passage_id;
            }
        }
    }

    static function forgetSmsPassageId() 
    {
        $tag    = 'carrier_smspassageid';
        $cache  = cache()->store(self::$store);

        $cache->tags($tag)->flush();
        return true;
    }

    //根椐帐号信息获取前辍
    static function getPrefixByGameAcoount($mainGamePlatCode,$accountUserName,$password)
    {
        $key    = $mainGamePlatCode.'_'.$accountUserName.'_'.$password;
        $key    = md5($key);
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $playerGameAccount   =  PlayerGameAccount::where('main_game_plat_code',$mainGamePlatCode)->where('account_user_name',$accountUserName)->where('password',$password)->first();

        $cache->put($key, $playerGameAccount->prefix);
        return $playerGameAccount->prefix;
    }

    //域名获取币种
    static function getCurrencyByDomain($domain)
    {
        $tag    = 'carrier_currency';
        $key    = md5($domain);
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierPreFixDomains = CarrierPreFixDomain::all();
        foreach ($carrierPreFixDomains as $key => $value) {
            $domains = explode(',',$value->domain);
            if(in_array($domain,$domains)){
                 $cache->tags($tag)->put($key, $value->currency);
                 return $value->currency;
            }
        }
    }

    //前辍获取币种
    static function getCurrencyByPrefix($prefix)
    {
        $tag    = 'carrier_currency';
        $key    = md5($prefix);
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierPreFixDomain = CarrierPreFixDomain::where('prefix',$prefix)->first();
        $cache->tags($tag)->put($key, $carrierPreFixDomain->currency);

        return $carrierPreFixDomain->currency;
    }

    static function forgetCurrency() 
    {
        $tag    = 'carrier_currency';
        $cache  = cache()->store(self::$store);

        $cache->tags($tag)->flush();
        return true;
    }


    //根椐域名获取商户
    static function getCarrierByDomain($domain)
    {
        $tag    = 'carrier_domain';
        $key    = md5($domain);
        $cache  = cache()->store(self::$store);
        $data   = [];

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $carrierPreFixDomains = CarrierPreFixDomain::all();

        foreach ($carrierPreFixDomains as $key => $value) {
            $domains = explode(',',$value->domain);
            if(in_array($domain,$domains)){
                $cache->put($key,$value->carrier_id);
                return $value->carrier_id;
            }
        }

        return false;
    }

    //根椐SIGN获取商户
    static function getCarrierBySign($sign)
    {
        $key    = 'carrier_sign_'.$sign;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $currCarrier = Carrier::where('sign', strtoupper($sign))->first();

        $cache->put($key, $currCarrier);

        return $currCarrier;
    }

    static function forgetCarrier($sign) 
    {
        $key    = 'carrier_sign_'.$sign;
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            $cache->forget($key);
        }
        return true;
    }

    static function setJdpayCache($orderId,$thirdOrderId)
    {
        $key    = 'jdpay_'.$orderId;

        $cache  = cache()->store(self::$store);

        $cache->put($key, $thirdOrderId);

        return true;
    }

    static function getJdpayCache($orderId)
    {
        $key    = 'jdpay_'.$orderId;

        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            return $cache->get($key);
        } else{
            return false;
        }
    }

    static function getCarrierById($carrierId)
    {
        $key    = 'carrier_by_id_'.$carrierId;

        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            return $cache->get($key);
        } 

        $carrier = Carrier::where('id',$carrierId)->first();

        $cache->put($key, $carrier);

        return $carrier;
    }
}
