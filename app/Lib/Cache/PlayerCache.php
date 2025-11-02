<?php namespace App\Lib\Cache;

use App\Models\Conf\PlayerSetting;
use App\Models\PlayerGameAccount;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\PlayerLevel;
use App\Models\PlayerBetflowCalculate;
use App\Models\Player;
use App\Models\PlayerMessage;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\PlayerCommission;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerFingerprint;
use App\Models\Log\PlayerLogin;
use App\Models\GameLine;
use App\Models\Def\MainGamePlat;
use App\Models\Map\CarrierGamePlat;

class PlayerCache
{
    public static $store    = "redis";

    static function getPlayerBetflowCalculate($carrierId,$playerId,$prefix)
    {
        $cache  = cache()->store(self::$store);
        $tag    = 'betflowCalculate_'.$carrierId.'_'.$prefix;
        $key    = 'playerBetflowCalculate_'.$playerId;
        
        $data   = [];

        // 存在直接返回
        if ($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        }

        $playerBetflowCalculate = PlayerBetflowCalculate::where('player_id',$playerId)->get();
        if(count($playerBetflowCalculate)){
            foreach ($playerBetflowCalculate as $key => $value) {
                $data[$value->game_category] = $value->betflow_calculate_rate;
            }
            $cache->tags($tag)->put($key, $data,now()->addMinutes(60));
            return $data;
        } else{
            $cache->tags($tag)->put($key, false);
            return false;
        }
    }

    static function forgetPlayerBetflowCalculate($carrierId,$playerId,$prefix)
    {
        $cache    = cache()->store(self::$store);
        $tag      = 'betflowCalculate_'.$carrierId.'_'.$prefix;
        $key      = 'playerBetflowCalculate_'.$playerId;
        
        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->forget($key);
        }
    }

    static function getPlayerIdforPlatCode($platName,$accountUserName)
    {
        $tag      = $platName.'_player';
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($accountUserName)) {
            return $cache->tags($tag)->get($accountUserName);
        } else {
            $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$platName)->where('account_user_name',$accountUserName)->first();

            if(!$playerGameAccount) {
                return false;
            } else {
                $cache->tags($tag)->put($accountUserName, $playerGameAccount->player_id);

                return $playerGameAccount->player_id;
            }
        }
    }

    static function getExtendIdByplayerId($carrierId,$playerId){
        $key    = 'extendId_'.$carrierId.'_'.$playerId;
        $cache  = cache()->store(self::$store);

      //  // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $player = Player::where('carrier_id',$carrierId)->where('player_id',$playerId)->first();

        $cache->put($key, $player->extend_id);

        return $player->extend_id;
    }

    static function getPlayerIdByExtentId($prefix,$extentId){
        $key    = 'playerId_'.$prefix.'_'.$extentId;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $player = Player::where('prefix',$prefix)->where('extend_id',$extentId)->first();
        if($player){
            $cache->put($key, $player->player_id);

            return $player->player_id;
        } else{
            return false;
        }
    }

    static function getMaterialPlayerIds()
    {
        $key = 'material';
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $playerIds = Player::where('user_name','like','material0%')->pluck('player_id')->toArray();

        $cache->put($key, $playerIds);

        return $playerIds;
    }

    static function forgetMaterialPlayerIds()
    {
        $key = 'material';
        $cache  = cache()->store(self::$store);
        
        if($cache->has($key)) {
            return $cache->forget($key);
        }
    }

    static function getPlayerId($carrierId,$username,$prefix=null)
    {
        if($prefix){
            $key    = 'user_name_'.$carrierId.'_'.$username.'_'.$prefix;
        } else{
            $key    = 'user_name_'.$carrierId.'_'.$username.'_null';
        }
        
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        if($prefix){
            $player    = Player::where('carrier_id',$carrierId)->where('prefix',$prefix)->where('user_name',$username)->first();
            $playerIds = $player->player_id;
            $cache->put($key, $player->player_id);
        } else{
            $playerIds = Player::where('carrier_id',$carrierId)->where('user_name',$username)->pluck('player_id')->toArray();
            $cache->put($key, $playerIds);
        }

        return $playerIds;
    }

    static function forgetPlayerId($platName,$accountUserName)
    {
        $tag      = $platName.'_player';
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($accountUserName)) {
            return $cache->tags($tag)->forget($accountUserName);
        }
    }

    static function getGameLineAllCode($playerGroupId)
    {
        $key    = 'groupId_gameline_'.$playerGroupId;
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $playerLevel = PlayerLevel::where('id',$playerGroupId)->first();
        if(!$playerLevel->game_line_id){
            return [];
        }

        $gameLine        = GameLine::where('id',$playerLevel->game_line_id)->first();
        $gameLineArr     = explode(',',$gameLine->main_game_plat_code);
        $gamePlatCodeIds = MainGamePlat::whereIn('main_game_plat_code',$gameLineArr)->pluck('main_game_plat_id')->toArray();
        $gamePlatCodeIds = CarrierGamePlat::whereIn('game_plat_id',$gamePlatCodeIds)->where('status',1)->pluck('game_plat_id')->toArray();
        $gameLineCodes   = MainGamePlat::whereIn('main_game_plat_id',$gamePlatCodeIds)->pluck('main_game_plat_code')->toArray();

        $cache->put($key, $gameLineCodes,now()->addMinutes(10));

        return $gameLineCodes;
    }

    static function flushGameLineAllCode($playerGroupId)
    {
        $key    = 'groupId_gameline_'.$playerGroupId;
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            $cache->forget($key);
        }

        return true;
    }

    static function getKillGameLineAllCode()
    {
        $key    = 'groupId_killgameline_';
        $cache  = cache()->store(self::$store);

        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $gameLines         = GameLine::where('is_point_kill',1)->first();
        $killMainPlatCodes = explode(',',$gameLines->main_game_plat_code);
        $gamePlatCodeIds   = MainGamePlat::whereIn('main_game_plat_code',$killMainPlatCodes)->pluck('main_game_plat_id')->toArray();
        $gamePlatCodeIds   = CarrierGamePlat::whereIn('game_plat_id',$gamePlatCodeIds)->where('status',1)->pluck('game_plat_id')->toArray();
        $killMainPlatCodes = MainGamePlat::whereIn('main_game_plat_id',$gamePlatCodeIds)->pluck('main_game_plat_code')->toArray();

        $cache->put($key, $killMainPlatCodes);

        return $killMainPlatCodes;
        
    }

    static function getisWinLoseAgent($playerid)
    {
        $key    = 'win_lose_agent_'.$playerid;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $player = Player::where('player_id',$playerid)->first();

        $cache->put($key, $player->win_lose_agent);

        return $player->win_lose_agent;
    }

    static function forgetisWinLoseAgent($playerid)
    {
        $key    = 'win_lose_agent_'.$playerid;
        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->forget($key);
        }
    }

    static function getPlayerRid($carrierId,$playerId)
    {
        $tag      = 'variable_player_'.$carrierId;
        $key      = 'playerRid_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player) {
                return false;
            } else {
                $cache->tags($tag)->put($key, $player->rid);

                return $player->rid;
            }
        }
    }

    static function  forgetPlayerRid($carrierId,$playerId)
    {
        $tag      = 'variable_player_'.$carrierId;
        $key      = 'playerRid_'.$playerId;
        $cache    = cache()->store(self::$store);
        
        if($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }

        return true;
    }

    static function flushPlayerRid($carrierId)
    {
        $tag      = 'variable_player_'.$carrierId;
        $cache    = cache()->store(self::$store);

        $cache->tags($tag)->flush();        

        return true;
    }

    static function getPlayerUserName($playerId)
    {
        $tag    = 'playerUserName_'.$playerId;
        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($playerId)) {
            return $cache->tags($tag)->get($playerId);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player) {
                return false;
            } else {
                $cache->tags($tag)->put($playerId, $player->user_name);

                return $player->user_name;
            }
        }
    }

    static function forgetPlayerUserName($playerId)
    {
        $tag    = 'playerUserName_'.$playerId;
        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($playerId)) {
            $cache->tags($tag)->forget($playerId);
        }

        return true;
    }  


    static function getPlayerParentId($playerId)
    {
        
        $key      = 'playerParentId_'.$playerId;
        $cache    = cache()->store(self::$store);
        if($cache->has($key)) {
            return $cache->get($key);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player) {
                return false;
            } else {
                $cache->put($key, $player->parent_id);

                return $player->parent_id;
            }
        }
    }

    static function forgetParentId($playerId)
    {
        $key    = 'playerParentId_'.$playerId;
        $cache  = cache()->store(self::$store);

        if($cache->has($key)) {
            $cache->forget($key);
        }

        return true;
    }      

    static function getPlayerTester($playerId)
    {
        $tag    = 'playerTester_'.$playerId;
        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($playerId)) {
            return $cache->tags($tag)->get($playerId);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player) {
                return false;
            } else {
                $cache->tags($tag)->put($playerId, $player->is_tester);

                return $player->is_tester;
            }
        }
    }

    static function forgetPlayerTester($playerId)
    {
        $tag    = 'playerTester_'.$playerId;
        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($playerId)) {
            $cache->tags($tag)->forget($playerId);
        }
        return true;
    }

    static function getPlayerLevel($carrierId,$playerId)
    {
        $tag      = 'variable_player_'.$carrierId;
        $key      = 'playerLevel_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player) {
                return false;
            } else {
                $cache->tags($tag)->put($key, $player->level);

                return $player->level;
            }
        }
    }

    static function forgetPlayerLevel($carrierId,$playerId)
    {
        $tag      = 'variable_player_'.$carrierId;
        $key      = 'playerLevel_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }
        return true;
    }

    static function getPlayerType($carrierId,$playerId)
    {
        $tag      = 'variable_player_'.$carrierId;
        $key      = 'playerTyep_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player) {
                return false;
            } else {
                $cache->tags($tag)->put($key, $player->type);

                return $player->type;
            }
        }
    }

    static function forgetPlayerType($carrierId,$playerId)
    {
        $tag      = 'variable_player_'.$carrierId;
        $key      = 'playerType_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }
        return true;
    }

    static function getPlayerSetting($playerId)
    {
        $tag      = 'conf_player_setting';
        $key      = 'playerSetting_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)){
            return $cache->tags($tag)->get($key);
        } else {
            $playerSetting = PlayerSetting::where('player_id',$playerId)->first();

            if(!$playerSetting){
                return false;
            } else {
                $cache->tags($tag)->put($key, $playerSetting);

                return $playerSetting;
            }
        }
    }

    static function forgetPlayerSetting($playerId)
    {
        $tag      = 'conf_player_setting';
        $key      = 'playerSetting_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)){
            $cache->tags($tag)->forget($key);
        }

        return true;
    }

    static function forgetAllPlayerSetting()
    {
        $tag      = 'conf_player_setting';
        $cache    = cache()->store(self::$store);
        $cache->tags($tag)->flush();
    }

    static function getPlayerIdforaccountUserName($platName,$accountUserName)
    {
        $tag      = $platName.'_password_player';
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($accountUserName)){
            return $cache->tags($tag)->get($accountUserName);
        } else {
            $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$platName)->where('account_user_name',$accountUserName)->first();

            if(!$playerGameAccount){
                return false;
            } else {
                $cache->tags($tag)->put($accountUserName, $playerGameAccount->player_id);

                return $playerGameAccount->player_id;
            }
        }
    }

    static function forgetPlayerIdforaccountUserName($platName,$accountUserName)
    {
        $tag      = $platName.'_password_player';
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($accountUserName)){
            $cache->tags($tag)->forget($accountUserName);
        }
        return true;
    }

    static function getCarrierId($playerId)
    {
        $key      = 'carrer_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)) {
            return $cache->get($key);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player){
                return false;
            } else {
                $cache->put($key, $player->carrier_id);
                
                return $player->carrier_id;
            }
        }
    }

    static function forgetCarrierId($playerId)
    {
        $key      = 'carrer_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)) {
            $cache->forget($key);
        }
        return true;
    }

    static function getPrefix($playerId)
    {
        $key      = 'prefix_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)) {
            return $cache->get($key);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player){
                return false;
            } else {
                $cache->put($key, $player->prefix);
                
                return $player->prefix;
            }
        }
    }

    static function getIsLiveStreamingAccount($playerId)
    {
        $key      = 'is_live_streaming_account_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)) {
            return $cache->get($key);
        } else {
            $player = Player::where('player_id',$playerId)->first();

            if(!$player){
                return false;
            } else {
                $cache->put($key, $player->is_live_streaming_account);
                
                return $player->is_live_streaming_account;
            }
        }
    }

    static function forgetIsLiveStreamingAccount($playerId)
    {
        $key      = 'is_live_streaming_account_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)) {
            $cache->forget($key);
        }
        return true;
    }

    static function forgetUnreadMessageNumber($carrierId,$playerId)
    {
        $tag      = 'carrier_unmessage_'.$carrierId;
        $key      = $playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }
        return true;
    }

    static function getUnreadMessageNumber($carrierId,$playerId)
    {
        $tag      = 'carrier_unmessage_'.$carrierId;
        $key      = $playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $unreadCount = PlayerMessage::where('player_id',$playerId)->where('is_read',0)->count();

            $cache->tags($tag)->put($key, $unreadCount);
                
            return $unreadCount;
        }
    }

    static function getDefalutGroupId($carrierId,$prefix)
    {
        $key      = 'default_group_'.$carrierId.'_'.$prefix;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)) {
            return $cache->get($key);
        } else {

            $playerLevel = PlayerLevel::where('carrier_id',$carrierId)->where('prefix',$prefix)->where('is_default',1)->first();

            $cache->put($key, $playerLevel->id);
                
            return $playerLevel->id;
        }
    }

    static function getFingerprint($prefix,$playerId)
    {
        $tag      = 'fingerprint_'.$prefix;
        $key      = $playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $fingerprints = PlayerFingerprint::where('player_id',$playerId)->pluck('fingerprint')->toArray();

            $cache->tags($tag)->put($key, $fingerprints);
                
            return $fingerprints;
        }
    }

    static function flushFingerprint($prefix)
    {
        $tag      = 'fingerprint_'.$prefix;
        $cache    = cache()->store(self::$store);
        $cache->tags($tag)->flush();
    }

    static function forgetFingerprint($prefix,$playerId)
    {
        $tag      = 'fingerprint_'.$prefix;
        $key      = $playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }
    }

    static function getIps($prefix,$playerId)
    {
        $tag      = 'ips_'.$prefix;
        $key      = $playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $loginIps = PlayerLogin::where('player_id',$playerId)->pluck('login_ip')->toArray();

            $cache->tags($tag)->put($key, $loginIps);
                
            return $loginIps;
        }
    }

    static function flushIps($prefix)
    {
        $tag      = 'ips_'.$prefix;
        $cache    = cache()->store(self::$store);
        $cache->tags($tag)->flush();
    }

    static function forgetIps($prefix,$playerId)
    {
        $tag      = 'ips_'.$prefix;
        $key      = $playerId;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }
    }


    static function getIswhetherRecharge($playerId)
    {
        $key      = 'whether_recharge_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)) {
            return $cache->get($key);
        } else {
            //分红记录
            $reportPlayerEarnings                         = ReportPlayerEarnings::where('player_id',$playerId)->where('status',1)->where('amount','>',0)->first();
            if($reportPlayerEarnings){
                $cache->put($key, 1);
                return  1;
            }
                            
            //保底记录
            $playerCommissions                            =  PlayerCommission::where('player_id',$playerId)->where('status',1)->where('amount','>',0)->first();
            if($playerCommissions){
                $cache->put($key, 1);
                return  1;
            }                 

            //充值记录
            $rechargePlayerIds                            = PlayerTransfer::where('player_id',$playerId)->where('type','recharge')->first();
            if($rechargePlayerIds){
                $cache->put($key, 1);
                return  1;
            }
            
            $cache->put($key, 0);
            return 0;
        }
    }

    static function flushIswhetherRecharge($playerId)
    {
        $key      = 'whether_recharge_'.$playerId;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)) {
            $cache->forget($key);
        }
        return true;
    }

    static function createPlayerStatDay($playerId,$day,$bool=false)
    {
        $key      = 'createPlayerStatDay_'.$playerId.'_'.$day;
        $cache    = cache()->store(self::$store);

        if($cache->has($key)){
            return true;
        } else {
            $existSuperior = ReportPlayerStatDay::where('player_id',$playerId)->where('day',$day)->first();
            if(!$existSuperior){
                $player                                                         = Player::where('player_id',$playerId)->first();
                $createReportPlayerStatDay                                      = new ReportPlayerStatDay();
                $createReportPlayerStatDay->carrier_id                          = $player->carrier_id;
                $createReportPlayerStatDay->rid                                 = $player->rid;
                $createReportPlayerStatDay->top_id                              = $player->top_id;
                $createReportPlayerStatDay->parent_id                           = $player->parent_id;
                $createReportPlayerStatDay->player_id                           = $player->player_id;
                $createReportPlayerStatDay->is_tester                           = $player->is_tester;
                $createReportPlayerStatDay->user_name                           = $player->user_name;
                $createReportPlayerStatDay->level                               = $player->level;
                $createReportPlayerStatDay->type                                = $player->type;
                $createReportPlayerStatDay->prefix                              = $player->prefix;
                $createReportPlayerStatDay->win_lose_agent                      = self::getisWinLoseAgent($player->player_id);
                $createReportPlayerStatDay->day                                 = $day;
                $createReportPlayerStatDay->month                               = bcdiv($day,100,0);
                if($bool){
                    $createReportPlayerStatDay->first_register                  = 1;
                    $createReportPlayerStatDay->team_first_register             = 1;
                }
                $createReportPlayerStatDay->save();
            }

            $cache->put($key, 1);
            return true;
        }
    }

    static function getExistNextPlayerStatDay($playerId)
    {
        $key      = 'getExistNextPlayerStatDay_'.$playerId.'_'.date('Ymd',strtotime('+1 day'));
        $cache    = cache()->store(self::$store);

        if($cache->has($key)){
            return true;
        } else {
            $existSuperior = ReportPlayerStatDay::where('player_id',$playerId)->where('day',date('Ymd',strtotime('+1 day')))->first();
            if(!$existSuperior){
                return false;
            } else{
                $cache->put($key, 1,now()->addDays(1));
                return true;
            }
        }
    }
}
