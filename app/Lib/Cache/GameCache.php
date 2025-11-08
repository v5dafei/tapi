<?php namespace App\Lib\Cache;

use App\Models\Map\CarrierGame;
use App\Models\Map\CarrierGamePlat;
use App\Models\Def\MainGamePlat;
use App\Models\Def\Game;
use App\Models\Carrier;
use App\Models\PlayerBetflowCalculate;
use App\Lib\Cache\PlayerCache;
use App\Models\Log\GameHot;
use App\Models\PlayerGameAccount;

class GameCache
{
    public static $store    = "redis";

    //后台写入抓单时间
    static function getRecordTime($platName)
    {
        $tag      = "recordTime";
        $cache    = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($platName)) {
            return $cache->tags($tag)->get($platName);
        } else {
            return false;
        }
    }

    static function setRecordTime($platName,$endDateTime)
    {
        $tag      = "recordTime";
        $cache    = cache()->store(self::$store);

        $cache->tags($tag)->put($platName, $endDateTime);
    }


    //根椐平台代码获取平台ID
    static function getGameId($platName,$gameCode)
    {
        $key      = $gameCode.'_'.'game_id';
        $tag      = $platName;
        $cache    = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $game = Game::where('main_game_plat_code',$platName)->where('record_match_code',$gameCode)->first();

            if(!$game) {
                return false;
            } else {
                $cache->tags($tag)->put($key, $game->game_id);

                return $game->game_id;
            }
        }
    }

    static function getPlatsList($carrierId,$input)
    {
        $tag      = 'platList_'.$carrierId;
        $cache    = cache()->store(self::$store);


        if(isset($input['game_category']) && !empty(trim($input['game_category']))) {
            if($cache->tags($tag)->has('platsList_'.$input['game_category'])) {
                return $cache->tags($tag)->get('platsList_'.$input['game_category']);
            }

            $platIds = CarrierGame::where('game_category',$input['game_category'])->where('carrier_id',$carrierId)->groupBy('game_plat_id')->pluck('game_plat_id')->toArray();
            $data    = MainGamePlat::select('def_main_game_plats.*')
                ->leftJoin('map_carrier_game_plats','map_carrier_game_plats.game_plat_id','=','def_main_game_plats.main_game_plat_id')
                ->whereIn('def_main_game_plats.main_game_plat_id',$platIds)
                ->whereNotIn('def_main_game_plats.main_game_plat_id',config('main')['fakegameplatids'])
                ->where('map_carrier_game_plats.carrier_id',$carrierId)
                ->orderBy('map_carrier_game_plats.sort','desc')
                ->get();

            $cache->tags($tag)->put('platsList_'.$input['game_category'], $data);

            return $data;
        } else {
            if($cache->tags($tag)->has('platsList')) {
                return $cache->tags($tag)->get('platsList');
            }

            $platIds = CarrierGame::where('carrier_id',$carrierId)->groupBy('game_plat_id')->pluck('game_plat_id')->toArray();
            $data    = MainGamePlat::select('def_main_game_plats.*')
                ->leftJoin('map_carrier_game_plats','map_carrier_game_plats.game_plat_id','=','def_main_game_plats.main_game_plat_id')
                ->whereIn('def_main_game_plats.main_game_plat_id',$platIds)
                ->whereNotIn('def_main_game_plats.main_game_plat_id',config('main')['fakegameplatids'])
                ->orderBy('map_carrier_game_plats.sort','desc')
                ->where('map_carrier_game_plats.carrier_id',$carrierId)
                ->get();
            $cache->tags($tag)->put('platsList', $data,now()->addMinutes(10));
            
            return $data;
        }
    }

    static function flushPlatList($carrierId)
    {
        $tag      = 'platList_'.$carrierId;
        $cache    = cache()->store(self::$store);
        $cache->tags($tag)->flush();

        return true;
    }

    //根椐平台代码获取平台ID
    static function getGamePlatId($platName)
    {
        $key    = 'plat_'.$platName;
        $cache  = cache()->store(self::$store);

        if($cache->has($key)) {
            return $cache->get($key);
        } else {
            $gamePlat = MainGamePlat::where('main_game_plat_code',$platName)->first();

            if(!$gamePlat) {
                return false;
            } else {
                $cache->put($key, $gamePlat->main_game_plat_id);

                return $gamePlat->main_game_plat_id;
            }
        }
    }

    static function getPlatToken($platName)
    {
        $tag      = "platToken";
        $cache    = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->tags($tag)->has($platName)) {
            return $cache->tags($tag)->get($platName);
        } else {
            return false;
        }
    }


    static function setPlatToken($platName,$data)
    {
        $tag      = "platToken";
        $cache    = cache()->store(self::$store);
        $t        = time()+$data['expires_in']-1;

        $cache->tags($tag)->put($platName, $data['token'].'____'.$t);
    }

    static function flushCarrierGame($carrierId)
    {
        $tag    = 'map_carrier_games_'.$carrierId;
        $cache  = cache()->store(self::$store);
        $cache->tags($tag)->flush();

        return true;
    }

    //获取推荐棋牌游戏
    static function recomandCard($carrierId,$input)
    {
        $tag    = 'map_carrier_games_'.$carrierId;
        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key    = 'recomandcard_mobile';
        } else {
            $key    = 'recomandcard';
        }   
        
        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {

            $plates    = CarrierGame::where('game_category',4)->where('carrier_id',$carrierId)->groupBy('game_plat_id')->where('status',1)->pluck('game_plat_id')->toArray();
            $plates    = MainGamePlat::whereIn('main_game_plat_id',$plates)->get();

            foreach ($plates as $innerkey => $value) {
                if(isset($input['is_mobile']) && $input['is_mobile']==1){
                    $gameIds                     = Game::where('main_game_plat_id',$value->main_game_plat_id)->where('game_moblie_code','<>','')->groupBy('game_moblie_code')->pluck('game_id')->toArray();
                } else {
                    $gameIds                     = Game::where('main_game_plat_id',$value->main_game_plat_id)->where('game_code','<>','')->groupBy('game_code')->pluck('game_id')->toArray();
                }
    
                $plates[$innerkey]['childs'] = CarrierGame::select('def_games.game_id','map_carrier_games.display_name','def_games.game_icon_square_path as  game_icon_path')
                    ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                    ->where('map_carrier_games.carrier_id',$carrierId)->where('map_carrier_games.status',1)
                    ->where('map_carrier_games.is_recommend',1)->where('map_carrier_games.game_plat_id',$value->main_game_plat_id)
                    ->whereIn('map_carrier_games.game_id',$gameIds)
                    ->orderBy('map_carrier_games.sort','desc')
                    ->get();
            }

            if(!$plates) {
                return [];
            } else {
                $cache->tags($tag)->put($key, $plates,now()->addMinutes(10));

                return $plates;
            }
        }
    }

    //获取推荐电子游戏
    static function recomandElectronic($carrierId,$input)
    {
        $tag    = 'map_carrier_games_'.$carrierId;

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key    = 'recomandelectronic_moblie';
        } else {
            $key    = 'recomandelectronic';
        }
        
        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            if(isset($input['is_mobile']) && $input['is_mobile']==1) {
                $gameIds = Game::where('game_category',2)->where('game_moblie_code','<>','')->pluck('game_id')->toArray();
            } else {
                $gameIds = Game::where('game_category',2)->where('game_code','<>','')->pluck('game_id')->toArray();
            }
            $data = CarrierGame::select('def_games.game_id','map_carrier_games.display_name','def_games.game_icon_square_path as game_icon_path','def_games.en_game_icon_square_path as en_game_icon_path','def_games.game_icon_square_path','def_games.en_game_icon_square_path')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->where('map_carrier_games.game_category',2)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->where('map_carrier_games.status',1)
                ->where('map_carrier_games.is_recommend',1)
                ->whereIn('map_carrier_games.game_id',$gameIds)
                ->orderBy('map_carrier_games.sort','desc')
                ->get();

            if(!$data) {
                return [];
            } else {
                $cache->tags($tag)->put($key, $data,now()->addMinutes(10));
            }
        }
        return $data;
    }

    static function electronicCategoryList($carrierId,$prefix)
    {
        $tag    = 'map_carrier_games_'.$carrierId;
        $key    = 'electronicCategory_'.$prefix;
        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $query = CarrierGamePlat::select('map_carrier_game_plats.game_plat_id','map_carrier_game_plats.status','def_main_game_plats.main_game_plat_code')
                ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','map_carrier_game_plats.game_plat_id')
                ->whereIn('def_main_game_plats.main_game_plat_code',config('game')['pub']['electronic'])->where('map_carrier_game_plats.carrier_id',$carrierId)
                ->whereIn('map_carrier_game_plats.status',[1,2]);
                
            if(isset(config('multiplefrontgame')[$carrierId][$prefix]['elegameplat'])){
                $query->whereIn('def_main_game_plats.main_game_plat_code',config('multiplefrontgame')[$carrierId][$prefix]['elegameplat']);
            }
            
            $data = $query->orderBy('map_carrier_game_plats.sort','desc')->get();

            $i = 1;
            foreach ($data as $key => $value) {
                if(in_array($value->main_game_plat_code,config('main')['recommend_electronic_plats'])){
                    $value->is_recommend = 1;
                } else{
                    $value->is_recommend = 0;
                }
                $value->moblie_game_icon_path = '/game/slots/'.$value->main_game_plat_code.'.png';
                $value->game_icon_path        = '/game/slots/'.$value->main_game_plat_code.'.png';
                $value->mobileurl             = config('main')['alicloudstore'].'0/mobiletemplate/';
                $value->template_game_icon_path = '/game/slots/'.$i.'.png';
                $value->template_moblie_game_icon_path = '/game/slots/'.$i.'.png';
                $value->display_name                   = $value->main_game_plat_code.'电子';

                $i++;
            }

            if(!$data) {
                return [];
            } else {
                $cache->tags($tag)->put($key, $data,now()->addMinutes(10));
            }
        }
        return $data;
    }

    static function hotGameList($carrierId,$input,$prefix){

        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];

        $tag            = 'map_carrier_games_'.$carrierId;

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key       = 'electronic_mobile_hot_'.$prefix;
        } else {
            $key       = 'electronic_hot_'.$prefix;
        }

        $cache          = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            if(isset($input['is_mobile']) && $input['is_mobile']==1) {
                $currCarrier   = Carrier::where('id',$carrierId)->first();
                $delCq9GameIds = Game::where('main_game_plat_code','cq9')->where('format',0)->pluck('game_id')->toArray();
                $gameIds       = Game::where('game_moblie_code','<>','')->whereNotIn('game_id',$delCq9GameIds)->whereNotIn('main_game_plat_code',config('main')['fakegameplatnames'])->where('status',1)->pluck('game_id')->toArray();
                
            } else {
                $gameIds = Game::where('game_code','<>','')->whereNotIn('main_game_plat_code',config('main')['fakegameplatnames'])->where('status',1)->pluck('game_id')->toArray();
            }


            if(isset(config('multiplefrontgame')[$carrierId][$prefix]['hotgamelist'])){
                $gameIds = config('multiplefrontgame')[$carrierId][$prefix]['hotgamelist'];
                $query   = CarrierGame::select('map_carrier_games.*','def_games.game_icon_square_path as game_icon_path','def_games.en_game_icon_square_path as en_game_icon_path','def_games.game_icon_square_path','def_games.en_game_icon_square_path')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->where('map_carrier_games.game_category',2)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->whereIn('map_carrier_games.game_id',$gameIds)
                ->orderBy('map_carrier_games.sort','desc');
            } else{
                $gameIds = GameHot::where('carrier_id',$carrierId)->where('prefix',$prefix)->limit(100)->orderBy('sort','desc')->pluck('game_id')->toArray();
                $query   = CarrierGame::select('map_carrier_games.*','def_games.game_icon_square_path as game_icon_path','def_games.en_game_icon_square_path as en_game_icon_path','def_games.game_icon_square_path','def_games.en_game_icon_square_path')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->leftJoin('log_games_hot','log_games_hot.game_id','=','map_carrier_games.game_id')
                ->where('map_carrier_games.game_category',2)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->whereIn('map_carrier_games.game_id',$gameIds)
                ->orderBy('log_games_hot.sort','desc');
            }

            $list   = $query->get();
            foreach ($list as $k => &$value) {
                $value->key = md5($value->main_game_plat_code.$value->display_name);
            }

            $data   = ['data' => $list->toArray(), 'url'=>config('main')['alicloudstore']];

            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }

    //获取电子游戏
    static function horizontalelectronic($carrierId,$input,$prefix)
    {
        $tag            = 'map_carrier_games_'.$carrierId;
        $key            = 'electronic_mobile_horizontal';

        $cache          = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            //查询是否是快杀盘  1=长期盘，0=短期盘
            $prefixType       = CarrierCache::getCarrierMultipleConfigure($carrierId,'prefix_type',$prefix);

            //短期盘如果有开启假游戏，只显示假游戏
            $allGameIds   = [];
            
            $nofakeGameList = Game::whereNotIn('main_game_plat_code',config('main')['fakegameelecodes'])->pluck('game_id')->toArray();
            if($prefixType==1){
                $fakeGame  = Game::whereIn('main_game_plat_code',config('main')['fakegameelecodemodes'])->get();
                foreach ($fakeGame as $key => $value) {
                    $allGameIds[] = self::realgameIdByfakegameId($value);
                }
            }
            $allGameIds       = array_merge(array_unique($allGameIds),$nofakeGameList);
            $currCarrier      = Carrier::where('id',$carrierId)->first();   
            $delCq9GameIds    = Game::where('game_category',2)->where('main_game_plat_code','cq9')->where('format',0)->pluck('game_id')->toArray();

            if($prefixType==1){
                $gameIds          = Game::where('game_category',2)->whereIn('game_id',$allGameIds)->where('game_moblie_code','<>','')->whereNotIn('game_id',$delCq9GameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();
            } else{
                $gameIds          = Game::where('game_category',2)->where('game_moblie_code','<>','')->whereNotIn('game_id',$delCq9GameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();
            }

            $carrierGamePlat  = CarrierGamePlat::where('carrier_id',$carrierId)->whereIn('status',[1,2])->pluck('game_plat_id')->toArray();
            $query            = CarrierGame::select('map_carrier_games.game_id')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->where('map_carrier_games.game_category',2)
                ->whereIn('map_carrier_games.status',[1,2])
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->whereIn('game_plat_id',$carrierGamePlat)
                ->whereIn('map_carrier_games.game_id',$gameIds)
                ->orderBy('map_carrier_games.created_at','desc');

            if(isset($input['main_game_plat_id']) && !empty($input['main_game_plat_id'])) {
               $query->where('map_carrier_games.game_plat_id',$input['main_game_plat_id']);
            }

            $datas   = $query->orderBy('map_carrier_games.sort','desc')->get();

            $strs = '';

            $changeIds = Game::select('id','game_id')->get();
            $arr       = [];

            foreach ($changeIds as $key => $value) {
                $arr[$value->game_id]=$value->id;
            }

            foreach ($datas as $key => $value) {
                $strs = $strs.$arr[$value->game_id].',';
            }

            $strs = rtrim($strs,',');

            $cache->tags($tag)->put($key, $strs,now()->addMinutes(10));

            return $strs;
        }
    }

    static function realgameIdByfakegameId($fakegame)
    {
        $key    = 'realgame_'.$fakegame->main_game_plat_code.'_'.$fakegame->game_id;

        $cache  = cache()->store(self::$store);

        // 存在直接返回
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $mainGamePlatCode = substr($fakegame->main_game_plat_code, 0,-1);
        if($mainGamePlatCode == 'jp'){
            if($fakegame->main_game_plat_code=='jp5' || $fakegame->main_game_plat_code=='jp6' || $fakegame->main_game_plat_code=='jp7' || $fakegame->main_game_plat_code=='jp8' || $fakegame->main_game_plat_code=='jp9'){
                $keyValueMap = array_flip(config('main')['fakegamemap']['jp5']);
                $existGame = Game::select('game_id')->where('main_game_plat_code','pg')->where('game_code',$keyValueMap[$fakegame->game_code])->first();
                if($existGame){
                    $cache->forever($key, $existGame->game_id);
                    return $existGame->game_id;
                }
            } 
        } elseif($mainGamePlatCode == 'pp'){
            if($fakegame->main_game_plat_code=='pp5' || $fakegame->main_game_plat_code=='pp6' || $fakegame->main_game_plat_code=='pp7' || $fakegame->main_game_plat_code=='pp8' || $fakegame->main_game_plat_code=='pp9'){
                $existGame = Game::select('game_id')->where('main_game_plat_code','pp')->where('game_code',$fakegame->game_code)->first();
                if($existGame){
                    $cache->forever($key, $existGame->game_id);
                    return $existGame->game_id;
                }
            }
                        
        } elseif($mainGamePlatCode == 'jili'){
            if($fakegame->main_game_plat_code=='jili5' || $fakegame->main_game_plat_code=='jili7' || $fakegame->main_game_plat_code=='jili8' || $fakegame->main_game_plat_code=='jili9'){
                $keyValueMap = array_flip(config('main')['fakegamemap']['jili5']);
                if(isset($keyValueMap[$fakegame->game_code])){
                    $existGame = Game::select('game_id')->where('main_game_plat_code','jili')->where('game_code',$keyValueMap[$fakegame->game_code])->first();
                    if($existGame){
                        $cache->forever($key, $existGame->game_id);
                        return $existGame->game_id;
                    }
                }
            }
                        
        } elseif($mainGamePlatCode == 'fc'){
            $keyValueMap = array_flip(config('main')['fakegamemap']['fc5']);
            $existGame   = Game::select('game_id')->where('main_game_plat_code','fc')->where('game_code',$keyValueMap[$fakegame->game_code])->first();
            if($existGame){
                $cache->forever($key, $existGame->game_id);
                return $existGame->game_id;
            }
        } else{
            $existGame   = Game::select('game_id')->where('main_game_plat_code',$mainGamePlatCode)->where('game_code',$fakegame->game_code)->first();
            if($existGame){
                $cache->forever($key, $existGame->game_id);
                return $existGame->game_id;
            }
        }

        return 1;
    }

    //获取电子游戏
    static function electronic($carrierId,$input,$prefix)
    {
        
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        //查询是否是快杀盘  1=长期盘，0=短期盘
        $prefixType       = CarrierCache::getCarrierMultipleConfigure($carrierId,'prefix_type',$prefix);

        //短期盘如果有开启假游戏，只显示假游戏 
        $allGameIds     = [];

        $existPlatCodes = [];
        if(isset(config('multiplefrontgame')[$carrierId][$input['prefix']]['elegameplat'])){
            $existPlatCodes = config('multiplefrontgame')[$carrierId][$input['prefix']]['elegameplat'];
        }

        $tag            = 'map_carrier_games_'.$carrierId;

        if(isset($input['merchant'])){
            unset($input['merchant']);
        }

        if(isset($input['carrier'])){
            unset($input['carrier']);
        }

        if(isset($input['language'])){
            unset($input['language']);
        }

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key       = 'electronic_mobile_'.implode('&',$input);
        } else {

            $key       = 'electronic_'.implode('&',$input);
        }

        $cache          = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            //没有假游戏的所有游戏列表
            $nofakeGameList = Game::whereNotIn('main_game_plat_code',config('main')['fakegameplatnames'])->pluck('game_id')->toArray();
            if($prefixType==1){
                $fakeGame  = Game::whereIn('main_game_plat_code',config('main')['fakegameelecodemodes'])->get();
                foreach ($fakeGame as $key => $value) {
                    $allGameIds[] = self::realgameIdByfakegameId($value);
                }
            }

            $allGameIds = array_merge(array_unique($allGameIds),$nofakeGameList);

            if(isset($input['is_mobile']) && $input['is_mobile']==1) {

                $currCarrier = Carrier::where('id',$carrierId)->first();
                $delCq9GameIds = Game::where('game_category',2)->where('main_game_plat_code','cq9')->where('format',0)->pluck('game_id')->toArray();
                if(count($existPlatCodes)){
                    $gameIds       = Game::where('game_category',2)->where('game_moblie_code','<>','')->whereIn('main_game_plat_code',$existPlatCodes)->whereNotIn('main_game_plat_code',config('main')['fakegameplatnames'])->whereNotIn('game_id',$delCq9GameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();
                } else{
                    $gameIds       = Game::where('game_category',2)->where('game_moblie_code','<>','')->whereNotIn('main_game_plat_code',config('main')['fakegameplatnames'])->whereNotIn('game_id',$delCq9GameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();
                }
                
            } else {
                if(count($existPlatCodes)){
                    $gameIds = Game::where('game_category',2)->where('game_code','<>','')->whereIn('main_game_plat_code',$existPlatCodes)->whereNotIn('main_game_plat_code',config('main')['fakegameplatnames'])->whereIn('status',[1,2])->pluck('game_id')->toArray();
                } else{
                    $gameIds = Game::where('game_category',2)->where('game_code','<>','')->whereNotIn('main_game_plat_code',config('main')['fakegameplatnames'])->whereIn('status',[1,2])->pluck('game_id')->toArray();
                }
            }
    
            $jp5tmp        = config('main')['fakegamemap']['jp5'];
            $pggamecodes   = []; 
            foreach ($jp5tmp as $key => $value) {
                $pggamecodes[] = $key;
            }

            $deletepgGameIds = Game::where('main_game_plat_code','pg')->whereNotIn('record_match_code',$pggamecodes)->pluck('game_id')->toArray();

            $jili5tmp      = config('main')['fakegamemap']['jili5'];
            $jiligamecodes = [];
            foreach ($jili5tmp as $key => $value) {
                $jiligamecodes[] = $key;
            }

            $deletejiliGameIds = Game::where('main_game_plat_code','jili')->whereNotIn('record_match_code',$jiligamecodes)->pluck('game_id')->toArray();

            $fc5tmp      = config('main')['fakegamemap']['fc5'];
            $fcgamecodes = [];
            foreach ($fc5tmp as $key => $value) {
                $fcgamecodes[] = $key;
            }

            $deletefcGameIds = Game::where('main_game_plat_code','fc')->whereNotIn('record_match_code',$fcgamecodes)->pluck('game_id')->toArray();

            $deleteGameIds   = array_merge($deletepgGameIds,$deletejiliGameIds,$deletefcGameIds);

            $gameIds         = array_diff($gameIds, $deleteGameIds);

            $carrierGamePlat = CarrierGamePlat::where('carrier_id',$carrierId)->whereIn('status',[1,2])->pluck('game_plat_id')->toArray();
            $query           = CarrierGame::select('map_carrier_games.*','def_games.game_icon_square_path as game_icon_path','def_games.format','def_games.en_game_icon_square_path as en_game_icon_path','def_games.game_icon_square_path','def_games.en_game_icon_square_path')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->where('map_carrier_games.game_category',2)
                ->whereIn('map_carrier_games.status',[1,2])
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->whereIn('game_plat_id',$carrierGamePlat)
                ->whereIn('map_carrier_games.game_id',$gameIds);
            if($prefixType==1){
                $query->whereIn('map_carrier_games.game_id',$allGameIds);
            }
                
            if(isset($input['type']) && $input['type']=='is_laster') {
                $query->orderBy('map_carrier_games.updated_at','desc');
            } else if(isset($input['type']) && $input['type']=='is_hot') {
                $query->where('map_carrier_games.is_hot',1)->orderBy('map_carrier_games.created_at','desc');
            } else  if(isset($input['type']) && $input['type'] == 'is_pool') {
                $query->where('def_games.is_pool',1)->orderBy('map_carrier_games.created_at','desc');
            } else  if(isset($input['type']) && $input['type']=='is_recommend') {
                $query->where('def_games.is_recommend',1)->orderBy('map_carrier_games.created_at','desc');
            } else {
                $query->orderBy('map_carrier_games.updated_at','desc');
            }

            if(isset($input['main_game_plat_id']) && !empty($input['main_game_plat_id'])) {
               $query->where('map_carrier_games.game_plat_id',$input['main_game_plat_id']);
            }

            if(isset($input['game_name']) && !empty($input['game_name'])) {
               $query->where('map_carrier_games.display_name','like','%'.$input['game_name'].'%');
            }

            $total  = $query->count();
            $list   = $query->skip($offset)->take($pageSize)->orderBy('map_carrier_games.sort','desc')->get();
            $data   = ['data' => $list->toArray(), 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)),'url'=>config('main')['alicloudstore']];

            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }

    //真人列表
    static function live($carrierId,$input,$prefix)
    {
        $tag    = 'map_carrier_games_'.$carrierId;

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key    = 'live_mobile_'.$prefix;
        } else {
            $key    = 'live_'.$prefix;
        }

        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            if(isset($input['is_mobile']) && $input['is_mobile']==1){
                $gameIds       = Game::getMoblieLiveLobby();
            } else {
                $gameIds       = Game::getPcLiveLobby();
            }
            if(isset(config('multiplefrontgame')[$carrierId][$prefix]['live'])){
                $gameIds = config('multiplefrontgame')[$carrierId][$prefix]['live'];
            }
            $gameIdsArray  = Game::whereIn('game_id',$gameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();
            $games         = CarrierGame::select('def_games.main_game_plat_code','map_carrier_games.id','map_carrier_games.carrier_id','map_carrier_games.game_plat_id','map_carrier_games.game_id','map_carrier_games.display_name','map_carrier_games.sort','map_carrier_games.is_recommend','map_carrier_games.is_hot','map_carrier_games.game_category','map_carrier_game_plats.status')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->leftJoin('map_carrier_game_plats','map_carrier_game_plats.game_plat_id','=','def_games.main_game_plat_id')
                ->whereIn('map_carrier_games.game_id',$gameIdsArray)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->where('map_carrier_game_plats.carrier_id',$carrierId)
                ->whereIn('map_carrier_game_plats.status',[1,2])
                ->whereIn('map_carrier_games.status',[1,2])
                ->orderBy('map_carrier_game_plats.sort','desc')
                ->orderBy('map_carrier_game_plats.id','asc')
                ->get();

            $i = 1;

            # 格式化列表
//            $games = self::formatList($games);
            foreach ($games as $key => $value) {
                if($value->main_game_plat_code=='pmzr'){
                    $value->main_game_plat_code='pm';
                }
                $value->game_icon_path                 = '/game/live/'.$value->main_game_plat_code.'.png';
                $value->game_icon_path_moblie          = '/game/live/'.$value->main_game_plat_code.'.png';
                $value->template_game_icon_path        = '/game/live/'.$i.'.png';
                $value->template_moblie_game_icon_path = '/game/live/'.$i.'.png';
                $value->display_name                   = $value->main_game_plat_code.'真人';
                $i++;
            }
            $data         =['data' => $games->toArray(),'url'=>config('main')['alicloudstore'].'0/template/','mobileurl'=>config('main')['alicloudstore'].'0/mobiletemplate/'];
            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }

    static function formatList($games) {
        $merchant = (array)request()->get('merchant');
        $lang = !empty($merchant['language']) ? $merchant['language'] : 'zh';
//        $lang = 'vi';

        $i = 1;
        foreach ($games as $key => $value) {
            $value->game_icon_path        = '/game/live/'.$value->main_game_plat_code.'.png';
            $value->game_icon_path_moblie = '/game/live/'.$value->main_game_plat_code.'.png';
            $value->template_game_icon_path = '/game/live/'.$i.'.png';
            $value->template_moblie_game_icon_path = '/game/live/'.$i.'.png';
            $i++;

            if ( $lang === 'vi' ) {
                $value->display_name = '';
            }
        }



        return $games;
    }

    //体育列表
    static function sport($carrierId,$input,$prefix)
    {
        $merchant = (array)request()->get('merchant');
        $lang = !empty($merchant['language']) ? $merchant['language'] : 'zh';

        $tag    = 'map_carrier_games_'.$carrierId;

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key    = 'sport_mobile_'.$prefix;
        } else {
            $key    = 'sport_'.$prefix;
        }

        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            if(isset($input['is_mobile']) && $input['is_mobile']==1){
                $gameIds       = Game::getMobileSportLobby();
            } else {
                $gameIds       = Game::getPcSportLobby();
            }

            if(isset(config('multiplefrontgame')[$carrierId][$prefix]['sport'])){
                $gameIds = config('multiplefrontgame')[$carrierId][$prefix]['sport'];
            }

            $gameIdsArray  = Game::whereIn('game_id',$gameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();

            $games         = CarrierGame::select('def_games.main_game_plat_code','map_carrier_games.id','map_carrier_games.carrier_id','map_carrier_games.game_plat_id','map_carrier_games.game_id','map_carrier_games.display_name','map_carrier_games.sort','map_carrier_games.is_recommend','map_carrier_games.is_hot','map_carrier_games.game_category','map_carrier_game_plats.status')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->leftJoin('map_carrier_game_plats','map_carrier_game_plats.game_plat_id','=','def_games.main_game_plat_id')
                ->whereIn('map_carrier_games.game_id',$gameIdsArray)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->where('map_carrier_game_plats.carrier_id',$carrierId)
                ->whereIn('map_carrier_games.status',[1,2])
                ->whereIn('map_carrier_game_plats.status',[1,2])
                ->orderBy('map_carrier_game_plats.sort','desc')
                ->orderBy('map_carrier_game_plats.id','asc')
                ->get();

            $i = 1;

            # 格式化列表
            foreach ($games as $key => $value) {

                $value->game_icon_path        = '/game/sport/'.$value->main_game_plat_code.'.png';
                $value->game_icon_path_moblie = '/game/sport/'.$value->main_game_plat_code.'.png';
                
                if($value->display_name == 'S128斗鸡'){
                    $value->template_game_icon_path = '/game/sport/s128.png';
                    $value->template_moblie_game_icon_path = '/game/sport/s128.png';
                } else {
                    $value->template_game_icon_path = '/game/sport/'.$i.'.png';
                    $value->template_moblie_game_icon_path = '/game/sport/'.$i.'.png';
                    $i++;
                }
            }


            $data          = ['data' => $games->toArray(),'url'=>config('main')['alicloudstore'].'0/template/','mobileurl'=>config('main')['alicloudstore'].'0/mobiletemplate/'];
            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }

    //电竞列表
    static function esport($carrierId,$input)
    {
        $tag    = 'map_carrier_games_'.$carrierId;

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key    = 'esport_mobile';
        } else {
            $key    = 'esport';
        }

        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {

            if(isset($input['is_mobile']) && $input['is_mobile']==1){
                $gameIds       = Game::getMoblieEsportLobby();
            } else {
                $gameIds       = Game::getEsportLobby();
            }

            $gameIdsArray  = Game::whereIn('game_id',$gameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();
            $games         = CarrierGame::select('def_games.main_game_plat_code','map_carrier_games.id','map_carrier_games.carrier_id','map_carrier_games.game_plat_id','map_carrier_games.game_id','map_carrier_games.display_name','map_carrier_games.sort','map_carrier_games.is_recommend','map_carrier_games.is_hot','map_carrier_games.game_category','map_carrier_game_plats.status')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->leftJoin('map_carrier_game_plats','map_carrier_game_plats.game_plat_id','=','def_games.main_game_plat_id')
                ->whereIn('map_carrier_games.game_id',$gameIdsArray)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->where('map_carrier_game_plats.carrier_id',$carrierId)
                ->whereIn('map_carrier_games.status',[1,2])
                ->whereIn('map_carrier_game_plats.status',[1,2])
                ->orderBy('map_carrier_game_plats.sort','desc')
                ->orderBy('map_carrier_game_plats.id','asc')
                ->get();

            $i = 1;
            foreach ($games as $key => $value) {
                if(count($games)>1){
                    $value->game_icon_path        = '/game/esport/'.$value->main_game_plat_code.'.png';
                } else {
                    $value->game_icon_path        = '/game/esport/esport-bg-'.$value->main_game_plat_code.'.png';
                }
                
                $value->game_icon_path_moblie = '/game/esport/'.$value->main_game_plat_code.'.png';
                $value->template_game_icon_path = '/game/esport/'.$i.'.png';
                $value->template_moblie_game_icon_path = '/game/esport/'.$i.'.png';
                $i++;
            }

            $data          = ['data' => $games->toArray(),'url'=>config('main')['alicloudstore'].'0/template/','mobileurl'=>config('main')['alicloudstore'].'0/mobiletemplate/'];
            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }

    static function fish($carrierId,$input,$prefix)
    {
        $tag    = 'map_carrier_games_'.$carrierId;
        $flag   = false;

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key    = 'fish_mobile_'.$prefix;
        } else {
            $key    = 'fish_'.$prefix;
        }

        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)){
            return $cache->tags($tag)->get($key);
        } else {

            if(isset($input['is_mobile']) && $input['is_mobile']==1){
                $gameIds       = Game::getMobileFish();
            } else {
                $gameIds       = Game::getPcFish();
            }

            if(isset(config('multiplefrontgame')[$carrierId][$prefix]['fish'])){
                $gameIds = config('multiplefrontgame')[$carrierId][$prefix]['fish'];
                $flag    = true;
            }

            $gameIdsArray  = Game::whereIn('game_id',$gameIds)->where('status',1)->pluck('game_id')->toArray();
            $games         = CarrierGame::select('def_games.main_game_plat_code','map_carrier_games.*','def_games.en_game_icon_square_path as game_icon_path','def_games.game_code')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->whereIn('map_carrier_games.game_id',$gameIdsArray)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->where('map_carrier_games.status',1)
                ->orderBy('sort','desc')
                ->orderBy('game_id','asc')
                ->get();

            $i =1;
            foreach ($games as $key => $value) {
                $value->game_icon_path        = '/game/fish/'.$value->main_game_plat_code.'.png';
                $value->game_icon_path_moblie = '/game/fish/'.$value->main_game_plat_code.'.png';
                $value->template_game_icon_path = '/game/fish/'.$i.'.png';
                $value->template_moblie_game_icon_path = '/game/fish/'.$i.'.png';
                $value->key = md5($value->main_game_plat_code.$value->display_name);
                $i++;
            }

            $items = [];
            if($flag){
                foreach ($gameIds as $key => $value) {
                    foreach ($games as $k => $v) {
                        if($value==$v->game_id){
                            $items[] = $v;
                        }
                    }
                }
            }

            if(!count($items)){
                $items = $games->toArray();
            }

            $data         = ['data' => $items,'url'=>config('main')['alicloudstore'].'0/template/','mobileurl'=>config('main')['alicloudstore'].'0/mobiletemplate/'];
            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }

    static function card($carrierId,$input,$prefix)
    {
        $tag    = 'map_carrier_games_'.$carrierId;
        $flag   = false;

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key    = 'card_mobile_'.$prefix;
        } else {
            $key    = 'card_'.$prefix;
        }

        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            if(isset($input['is_mobile']) && $input['is_mobile']==1){
                $gameIds       = Game::getMobileCardLobby();
            } else {
                $gameIds       = Game::getPcCardLobby();
            }

            if(isset(config('multiplefrontgame')[$carrierId][$prefix]['card'])){
                $gameIds = config('multiplefrontgame')[$carrierId][$prefix]['card'];
                $flag    = true;
            }

            $gameIdsArray  = Game::whereIn('game_id',$gameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();
            $games         = CarrierGame::select('def_games.main_game_plat_code','map_carrier_games.id','map_carrier_games.carrier_id','map_carrier_games.game_plat_id','map_carrier_games.game_id','map_carrier_games.display_name','map_carrier_games.sort','map_carrier_games.is_recommend','map_carrier_games.is_hot','map_carrier_games.game_category','map_carrier_game_plats.status','def_games.game_icon_square_path as game_icon_path')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->leftJoin('map_carrier_game_plats','map_carrier_game_plats.game_plat_id','=','def_games.main_game_plat_id')
                ->whereIn('map_carrier_games.game_id',$gameIdsArray)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->whereIn('map_carrier_games.status',[1,2])
                ->whereIn('map_carrier_game_plats.status',[1,2])
                ->where('map_carrier_game_plats.carrier_id',$carrierId)
                ->orderBy('map_carrier_game_plats.sort','desc')
                ->orderBy('map_carrier_games.sort','desc')
                ->get();

            $i  = 1;
            foreach ($games as $key => $value) {
                $value->game_icon_path        = '/game/card/'.$value->main_game_plat_code.'.png';
                $value->game_icon_path_moblie = '/game/card/'.$value->main_game_plat_code.'.png';
                $value->template_game_icon_path = '/game/card/'.$i.'.png';
                $value->template_moblie_game_icon_path = '/game/card/'.$i.'.png';
                $i++;
            }

            $items = [];
            if($flag){
                foreach ($gameIds as $key => $value) {
                    foreach ($games as $k => $v) {
                        if($value==$v->game_id){
                            $items[] = $v;
                        }
                    }
                }
            }

            if(!count($items)){
                $items = $games->toArray();
            }

            $data          = ['data' => $items,'url'=>config('main')['alicloudstore'].'0/template/','mobileurl'=>config('main')['alicloudstore'].'0/mobiletemplate/'];
            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }

    static function cardSubList($carrierId)
    {
        $tag    = 'map_carrier_games_'.$carrierId;
        $key    = 'subcard';
        
        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)) {
            return $cache->tags($tag)->get($key);
        } else {
            $gameIdsArray  = Game::where('game_category',4)->where('status',1)->where('record_match_code','<>','')->pluck('game_id')->toArray();
            $games         = CarrierGame::select('def_games.main_game_plat_code','map_carrier_games.*','def_games.game_icon_square_path as game_icon_path')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->whereIn('map_carrier_games.game_id',$gameIdsArray)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->where('map_carrier_games.status',1)
                ->orderBy('sort','desc')
                ->orderBy('game_id','asc')
                ->get();

            $data          = ['data' => $games->toArray(),'url'=>config('main')['alicloudstore'].'0/template/','mobileurl'=>config('main')['alicloudstore'].'0/mobiletemplate/'];
            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }
    

    //获取彩票相关列表
    static function lotteryList($carrierId,$input,$prefix)
    {
        $tag    = 'map_carrier_games_'.$carrierId;

        if(isset($input['is_mobile']) && $input['is_mobile']==1){
            $key    = 'lotteryList_mobile_'.$prefix;
        } else {
            $key    = 'lotteryList_'.$prefix;
        }

        $cache  = cache()->store(self::$store);

        if($cache->tags($tag)->has($key)){
            return $cache->tags($tag)->get($key);
        } else {

            if(isset($input['is_mobile']) && $input['is_mobile']==1){
              //  $gameIds       = config('game')['pub']['mobilelotterylobby'];
                  $gameIds       = Game::getMobileLotteryLobby();
            } else {
              //  $gameIds       = config('game')['pub']['lotterylobby'];
                  $gameIds       = Game::getPcLotteryLobby();
            }

            if(isset(config('multiplefrontgame')[$carrierId][$prefix]['lottery'])){
                $gameIds = config('multiplefrontgame')[$carrierId][$prefix]['lottery'];
            }

            $gameIdsArray  = Game::whereIn('game_id',$gameIds)->whereIn('status',[1,2])->pluck('game_id')->toArray();
            $games         = CarrierGame::select('def_games.main_game_plat_code','map_carrier_games.id','map_carrier_games.carrier_id','map_carrier_games.game_plat_id','map_carrier_games.game_id','map_carrier_games.display_name','map_carrier_games.sort','map_carrier_games.is_recommend','map_carrier_games.is_hot','map_carrier_games.game_category','map_carrier_game_plats.status','def_games.game_icon_square_path as game_icon_path')
                ->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')
                ->leftJoin('map_carrier_game_plats','map_carrier_game_plats.game_plat_id','=','def_games.main_game_plat_id')
                ->whereIn('map_carrier_games.game_id',$gameIdsArray)
                ->where('map_carrier_games.carrier_id',$carrierId)
                ->whereIn('map_carrier_games.status',[1,2])
                ->whereIn('map_carrier_game_plats.status',[1,2])
                ->where('map_carrier_game_plats.carrier_id',$carrierId)
                ->orderBy('map_carrier_game_plats.sort','desc')
                ->orderBy('map_carrier_game_plats.id','asc')
                ->get();
            $i  = 1;
            foreach ($games as $key => $value) {
                if($value->display_name=='XBB彩票'){
                    $value->game_icon_path        = '/game/lottery/xbb.png';
                    $value->game_icon_path_moblie = '/game/lottery/xbb.png';
                    $value->template_game_icon_path = '/game/lottery/xbb.png';
                } else {
                    $value->game_icon_path        = '/game/lottery/'.$value->main_game_plat_code.'.png';
                    $value->game_icon_path_moblie = '/game/lottery/'.$value->main_game_plat_code.'.png';
                    $value->template_game_icon_path = '/game/lottery/'.$i.'.png';
                    $value->template_moblie_game_icon_path = '/game/lottery/'.$i.'.png';
                    $i++;
                }
            }

            $newData       = $games->toArray();
            //$newData[]     = $selflottery;
            $data          = ['data' => $newData,'url'=>config('main')['alicloudstore'].'0/template/','mobileurl'=>config('main')['alicloudstore'].'0/mobiletemplate/'];
            $cache->tags($tag)->put($key, $data,now()->addMinutes(10));

            return $data;
        }
    }

    static function lotteryGameId()
    {
       $cache  = cache()->store(self::$store);
       $key    = 'lotteryGameId';

       if($cache->has($key)){
            return $cache->get($key);
        } else {
            $game = Game::where('main_game_plat_code','lucklottery')->first();
            return $game->game_id;
        }
    }

    static function getChangeGameId($id)
    {
       $cache  = cache()->store(self::$store);
       $key    = 'changeGameId_'.$id;

       if($cache->has($key)){
            return $cache->get($key);
        } else {
            $game = Game::where('id',$id)->first();
            if($game){
                $cache->put($key, $game->game_id);
                return $game->game_id;
            } else{
                return 0;
            }  
        }
    }

    static function getBetflowCalculate($carrierId,$playerId,$gameCategory,$prefix)
    {
        $cache  = cache()->store(self::$store);
        $tag    = 'betflowCalculate_'.$carrierId.'_'.$prefix;
        $key    = $playerId.'_'.$gameCategory;

        if($cache->tags($tag)->has($key)){
            return $cache->tags($tag)->get($key);
        } else {
            $playerBetflowCalculate = PlayerBetflowCalculate::where('player_id',$playerId)->where('game_category',$gameCategory)->first();;
            if($playerBetflowCalculate){
                $cache->tags($tag)->put($key, $playerBetflowCalculate,now()->addMinutes(10));
                return $playerBetflowCalculate;
            } else{
                return 0;
            }  
        }
    }

    static function forgetBetflowCalculate($carrierId,$playerId,$gameCategory,$prefix)
    {
        $cache  = cache()->store(self::$store);
        $tag    = 'betflowCalculate_'.$carrierId.'_'.$prefix;
        $key    = $playerId.'_'.$gameCategory;

        if ($cache->tags($tag)->has($key)) {
            $cache->tags($tag)->forget($key);
        }

        PlayerCache::forgetPlayerBetflowCalculate($carrierId,$playerId,$prefix);
    }

    static function flushBetflowCalculate($carrierId,$prefix)
    {
        $cache  = cache()->store(self::$store);
        $tag    = 'betflowCalculate_'.$carrierId.'_'.$prefix;

        $cache->tags($tag)->flush();
    }
}
