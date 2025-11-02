<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Web\BaseController;
use App\Models\Log\PlayerTransferCasino;
use App\Models\Conf\PlayerSetting;
use App\Models\Def\Game as Games;
use App\Models\Def\MainGamePlat;
use App\Models\Map\CarrierGamePlat;
use App\Models\Map\CarrierGame;
use App\Models\PlayerGameAccount;
use App\Models\PlayerRecent;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\PlayerBankCard;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerGameCollect;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\GameHot;
use App\Utils\Client\IP;
use App\Game\Game;
use App\Lib\Cache\Lock;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerWithdraw;
use App\Lib\Cache\SystemCache;
use App\Lib\Cache\PlayerCache;
use App\Models\PlayerLevel;
use App\Models\GameLine;
use App\Lib\Clog;

class GameController extends BaseController
{
    public function getBalance()
    {
        $input           = request()->all();

        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        $mainGamePlat = GameCache::getGamePlatId($input['mainGamePlatCode']);

        if(!$mainGamePlat) {
            return $this->returnApiJson(config('language')[$this->language]['error22'], 0);
        }
        
        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$this->user->player_id)->first();
        
        if(!$playerGameAccount) {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['balance' => '0.00']);
        }

        if(!$playerGameAccount->exist_transfer) {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['balance' => '0.00']);
        }

        $carrierGamePlat = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',$mainGamePlat)->first();
        if($carrierGamePlat->status==2){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['balance' => '0.00']);
        }

        request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
        request()->offsetSet('password',$playerGameAccount->password);

        $game    = new Game($this->carrier,$input['mainGamePlatCode']);
        $balance = $game->getBalance();

        if(is_array($balance)){
            if($balance['success']){
                if(gettype($balance['data']['balance'])=='string'){
                    $balance['data']['balance'] = floatval($balance['data']['balance']);
                }
                $balance['data']['balance']     = number_format($balance['data']['balance'],2);
                $playerAccount                  = PlayerAccount::where('player_id',$this->user->player_id)->first();
                $platbalance                    = bcdiv($playerAccount->balance,10000,2);
                $balance['data']['platbalance'] = number_format($platbalance,2);
            } 

            return $balance;
        } else{
            return ['success' => false, 'data' => [], 'message' => config('language')[$this->language]['error285'],'code'=>200];
        }
    }

    public function horizontalElectronicList()
    {
        $input   = request()->all();
        $results = GameCache::horizontalelectronic($this->carrier->id,$input,$this->prefix); 
        $data['results'] =  $results;
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function solidifyjson()
    {
        $gamePlatIds  = CarrierGamePlat::where('carrier_id',$this->carrier->id)->pluck('game_plat_id')->toArray();

        $carrierGames = CarrierGame::select('game_id','display_name','status','sort')->whereIn('game_plat_id',$gamePlatIds)->where('game_category',2)->orderBy('sort','desc')->get()->toArray();
        $games        = Games::select('main_game_plat_code','game_id','game_icon_square_path','id')->whereIn('main_game_plat_id',$gamePlatIds)->where('game_category',2)->get();
        $data         = [];

        foreach ($games as $key => $value) {
            $row                   = [];
            $row['main_game_plat_code']   = $value->main_game_plat_code;
            $row['game_icon_square_path'] = $value->game_icon_square_path;
            $row['game_id']               = $value->id;
            $data[$value->game_id]        = $row;
        }

        foreach ($carrierGames as $key => &$v) {
           $v['main_game_plat_code']   = $data[$v['game_id']]['main_game_plat_code'];
           $v['game_icon_square_path'] = $data[$v['game_id']]['game_icon_square_path'];
           $v['game_id']               = $data[$v['game_id']]['game_id'];
        }

        return $carrierGames;
    }

    public function exitGame()
    {
        //先转出
        $transferKey        ='gametranfer_'.$this->user->player_id;
        if(cache()->has($transferKey)){
            //转帐操作
            $playerGameAccount  = PlayerGameAccount::where('player_id',$this->user->player_id)->where('main_game_plat_code',cache()->get($transferKey))->where('is_locked',0)->where('is_need_repair',0)->first();
            if($playerGameAccount){
                request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
                request()->offsetSet('password',$playerGameAccount->password);
                request()->offsetSet('mainGamePlatCode',cache()->get($transferKey));

                $transferoutGame    = new Game($this->carrier,cache()->get($transferKey));        
                $transferoutBalance = $transferoutGame->getBalance();

                if(is_array($transferoutBalance) && $transferoutBalance['success']){
                    if($transferoutBalance['data']['balance'] >= 1){
                        request()->offsetSet('price',intval($transferoutBalance['data']['balance']));
                        $output = $transferoutGame->transferTo($this->user);
                        if(is_array($output) && $output['success']){
                            cache()->forget($transferKey);
                            $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->first();
                            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($playerAccount->balance,10000,2),'frozen'=>bcdiv($playerAccount->frozen,10000,2),'agentbalance'=>bcdiv($playerAccount->agentbalance,10000,2),'agentfrozen'=>bcdiv($playerAccount->agentfrozen,10000,2)]);
                        } else{
                            return $this->returnApiJson(config('language')[$this->language]['error286'], 0);
                        }
                    } else{
                        $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->first();
                        cache()->forget($transferKey);
                        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($playerAccount->balance,10000,2),'frozen'=>bcdiv($playerAccount->frozen,10000,2),'agentbalance'=>bcdiv($playerAccount->agentbalance,10000,2),'agentfrozen'=>bcdiv($playerAccount->agentfrozen,10000,2)]);
                    }
                } else{
                    return $this->returnApiJson(config('language')[$this->language]['error286'], 0);
                }
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error287'], 0);
            }
        } else{
            $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->first();
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($playerAccount->balance,10000,2),'frozen'=>bcdiv($playerAccount->frozen,10000,2),'agentbalance'=>bcdiv($playerAccount->agentbalance,10000,2),'agentfrozen'=>bcdiv($playerAccount->agentfrozen,10000,2)]);
        }
    }

    public function transferIn()
    {
        $input           = request()->all();

        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        if(!isset($input['price']) ||empty(trim($input['price']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        if(!preg_match("/^[1-9][0-9]*$/",$input['price'])) {
            return $this->returnApiJson(config('language')[$this->language]['error23'], 0);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['mainGamePlatCode'])->first();

        if(!$mainGamePlat) {
            return $this->returnApiJson(config('language')[$this->language]['error22'], 0);
        }

        if($mainGamePlat->changeLine){
            return $this->returnApiJson(config('language')[$this->language]['error202'], 0);
        }

        $game              = new Game($this->carrier,$input['mainGamePlatCode']);
        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$this->user->player_id)->first();

        if(!$playerGameAccount) {
            request()->offsetSet('username',$this->user->user_name);
            request()->offsetSet('mainGamePlatCode',$input['mainGamePlatCode']);
            if($input['mainGamePlatCode']=='ae'){
                $playerSetting = PlayerCache::getPlayerSetting($this->user->player_id);
                request()->offsetSet('odds',$playerSetting->lottoadds);
            }

            $output = $game->createMember($this->user);
             if(isset($output['success']) && $output['success'] == true) {
                request()->offsetSet('accountUserName',$output['data']['accountUserName']);
                request()->offsetSet('password',$output['data']['password']); 
            } else {
                 return $this->returnApiJson(config('language')[$this->language]['error24'], 0);
            }
        } else {
            if($playerGameAccount->is_locked){
                return $this->returnApiJson(config('language')[$this->language]['error128'], 0);
            }
            request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
            request()->offsetSet('password',$playerGameAccount->password);
        }

        return $game->transferIn($this->user);
    }

    public function fastTransfer()
    {
        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        if($this->user->frozen_status==1){
            return $this->returnApiJson(config('language')[$this->language]['error132'], 0);
        }

        $playerTransferCasion = PlayerTransferCasino::where('player_id',$this->user->player_id)->where('type',1)->orderBy('id','desc')->first();
        $playAccount          = PlayerAccount::where('player_id',$this->user->player_id)->first();
        if(!$playerTransferCasion) {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($playAccount->balance,10000,2),'gamePlatCode'=>'ag','gamePlatCodeBalance'=>'0.00']);
        } else {
            $playerGameAccount = PlayerGameAccount::where('player_id',$this->user->player_id)->where('main_game_plat_code',$playerTransferCasion->main_game_plat_code)->first();
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($playAccount->balance,10000,2),'gamePlatCode'=>$playerGameAccount->main_game_plat_code,'gamePlatCodeBalance'=>$playerGameAccount->balance]);
        }
    }

    public function transferTo()
    {
        $input           = request()->all();

        if(!isset($input['price']) ||empty(trim($input['price']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }
        if(!preg_match("/^[1-9][0-9]*$/",$input['price'])) {
            return $this->returnApiJson(config('language')[$this->language]['error23'], 0);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['mainGamePlatCode'])->first();

        if(!$mainGamePlat) {
            return $this->returnApiJson(config('language')[$this->language]['error22'], 0);
        }

        $game              = new Game($this->carrier,$input['mainGamePlatCode']);
        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$this->user->player_id)->first();

        if(!$playerGameAccount) {
            return $this->returnApiJson(config('language')[$this->language]['error25'], 0);
        } else {
            if($playerGameAccount->is_locked){
                return $this->returnApiJson(config('language')[$this->language]['error128'], 0);
            }
            request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
            request()->offsetSet('password',$playerGameAccount->password);
        }

        return $game->transferTo($this->user);
    }

    public function transfer()
    {
        $input           = request()->all();
        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        if($this->user->frozen_status==1){
            return $this->returnApiJson(config('language')[$this->language]['error132'], 0);
        }

        if(!isset($input['price']) ||empty(trim($input['price']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }
        if(!preg_match("/^[1-9][0-9]*$/",$input['price'])) {
            return $this->returnApiJson(config('language')[$this->language]['error23'], 0);
        }

        if(!isset($input['transferToPlat']) ||empty(trim($input['transferToPlat']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        if(!isset($input['transferInPlat']) ||empty(trim($input['transferInPlat']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        $transferToPlat = MainGamePlat::where('main_game_plat_code',$input['transferToPlat'])->first();
        $transferInPlat = MainGamePlat::where('main_game_plat_code',$input['transferInPlat'])->first();

        if($transferInPlat == $transferToPlat) {
             return $this->returnApiJson(config('language')[$this->language]['error26'], 0);
        }   

        if($input['transferToPlat'] !='main' && !$transferToPlat ) {
            return $this->returnApiJson(config('language')[$this->language]['error27'], 0);
        }

        if($input['transferInPlat'] !='main' && !$transferInPlat ) {
            return $this->returnApiJson(config('language')[$this->language]['error28'], 0);
        }

        if($input['transferToPlat'] == 'main' || $input['transferInPlat'] == 'main') {

            if($input['transferToPlat'] == 'main') {
                request()->offsetSet('mainGamePlatCode',$input['transferInPlat']);
                return $this->transferIn();
            } else {
                request()->offsetSet('mainGamePlatCode',$input['transferToPlat']);
                return $this->transferTo();
            }
        } else{
            request()->offsetSet('mainGamePlatCode',$input['transferToPlat']);

            $out = $this->transferTo();

            if(is_array($out) && isset($out['success'])) {
                if($out['success'] == true) {
                    request()->offsetSet('mainGamePlatCode',$input['transferInPlat']);

                    return $this->transferIn();
                } else {
                    return $this->returnApiJson(config('language')[$this->language]['error29'], 0);
                }
            } else {
                return $this->returnApiJson(config('language')[$this->language]['error29'], 0);
            }
        }

        $game              = new Game($this->carrier,$input['mainGamePlatCode']);
        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$this->user->player_id)->first();

        if(!$playerGameAccount) {
            return $this->returnApiJson(config('language')[$this->language]['error25'], 0);
        } else {
            request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
            request()->offsetSet('password',$playerGameAccount->password);
        }
        return $game->transferTo($this->user);
    }

    public function kick()
    {
        $input           = request()->all();
        
        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['mainGamePlatCode'])->first();

        if(!$mainGamePlat) {
            return $this->returnApiJson(config('language')[$this->language]['error22'], 0);
        }

        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$this->user->player_id)->first();

        if(!$playerGameAccount) {
            return $this->returnApiJson(config('language')[$this->language]['error25'], 0);
        }

        request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
        request()->offsetSet('password',$playerGameAccount->password);

        $game = new Game($this->carrier,$input['mainGamePlatCode']);

        return $game->kick();
    }

    public function joinGame()
    {
        $enableFastKill                            = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'enable_fast_kill',$this->user->prefix);
        $enableAgentGameLimit                      = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'enable_agent_game_limit',$this->user->prefix);
        $forciblyJoinfakegameActivityid            = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'forcibly_joinfakegame_activityid',$this->user->prefix);
        $liveBroadcastAwards                       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'live_broadcast_awards',$this->user->prefix);
        $issuccessreplace                          = false;

        //查询层级用到的游戏列表
        $enablePgFactoryListArr = PlayerCache::getGameLineAllCode($this->user->player_group_id);

        $playerLevel = PlayerLevel::where('id',$this->user->player_group_id)->first();
        

        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        $input = request()->all();
        if(!isset($input['gameId']) ||trim($input['gameId'])=='') {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        //防止过快提交
        $cacheKey = $this->user->player_id."_joinGame_".$input['gameId'];
        $redisLock = Lock::addLock($cacheKey,1);

        if (!$redisLock) {
            return $this->returnApiJson(config('language')[$this->language]['error203'], 0);    
        }

        if(is_numeric($input['gameId'])){
            $input['gameId'] = GameCache::getChangeGameId($input['gameId']);
        }

        $game = Games::where('game_id',$input['gameId'])->first();

        if(!$game) {
            return $this->returnApiJson(config('language')[$this->language]['error30'], 0);
        }

        //直播号处理
        if($this->user->is_live_streaming_account ==1){
            $liveBroadcastAwardsArr = explode(',', $liveBroadcastAwards);
            if(!empty($liveBroadcastAwards) && count($liveBroadcastAwardsArr) > 0){
                if($game->main_game_plat_code=='pg'){
                    //写死了平台ID
                    $maintainGame              = CarrierGamePlat::where('game_plat_id',2)->where('status',1)->first();
                    if($maintainGame && isset(config('main')['fakegamemap']['jp5'][$game->game_code])){
                        $gameExist             = Games::where('main_game_plat_code','jp6')->where('game_code',config('main')['fakegamemap']['jp5'][$game->game_code])->first();
                        if($gameExist && in_array($gameExist->game_id,$liveBroadcastAwardsArr)){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }
                    
                } elseif($game->main_game_plat_code=='pp'){
                    $maintainGame              = CarrierGamePlat::where('game_plat_id',8)->where('status',1)->first();
                    if($maintainGame){
                        $gameExist             = Games::where('main_game_plat_code','pp6')->where('game_code',$game->game_code)->first();
                        if($gameExist && in_array($gameExist->game_id,$liveBroadcastAwardsArr)){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }
                }
            }
        }
        //直播号结束

        //限制代理是否只能进入固定分类游戏
        if($this->user->win_lose_agent && $enableAgentGameLimit && !in_array($game->game_category,[2,4,7]) ){
            return $this->returnApiJson(config('language')[$this->language]['error290'], 0);
        }

        if(!in_array($game->main_game_plat_code, ['cq95','jdb5','fc5','pp5','jp5','habanero5','jili5','jp6','pp6','cq97','pp7','jp7','habanero7','fc7','jdb7','jili7','cq98','cq99','pp8','pp9','jp8','jp9','habanero8','habanero9','fc8','fc9','jdb8','jdb9','jili8','jili9']) && $game->game_category==2){
            $gameHot = GameHot::where('game_id',$input['gameId'])->where('carrier_id',$this->carrier->id)->where('prefix',$this->user->prefix)->first();
            if($gameHot){
                $gameHot->sort = $gameHot->sort + 1;
                $gameHot->save();
            } else{
                if($game->game_category==2){
                    $gameHot                      = new GameHot();
                    $gameHot->carrier_id          = $this->carrier->id;
                    $gameHot->main_game_plat_code = $game->main_game_plat_code;
                    $gameHot->prefix              = $this->user->prefix;
                    $gameHot->game_id             = $game->game_id;
                    $gameHot->sort                = 1;
                    $gameHot->save(); 
                }
            }
        }

        //不进假PG名单
        $noFakePgPlayerids    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'no_fake_pg_playerids',$this->user->prefix);

        //素材号ID
        $materialIds          = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'materialIds',$this->user->prefix);
        $noFakePgPlayeridsArr = explode(',',$noFakePgPlayerids);
        $materialIdsArr       = explode(',',$materialIds);

        $forcibly = false;
        $latelyPlayerDepositPayLog = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->first();

        //首存1加1活动概率进入假游戏
        if($latelyPlayerDepositPayLog && !empty($latelyPlayerDepositPayLog->activityids) && ($latelyPlayerDepositPayLog->activityids==$forciblyJoinfakegameActivityid)){
            $forcibly = true;
        }

        //在点杀列表或 参加特定活动进入强杀，开启快杀
        $KillGameLineAllCodes  = PlayerCache::getKillGameLineAllCode();

        //开启快杀或活动点杀
        if(($enableFastKill || $forcibly)&& in_array($game->main_game_plat_code,config('main')['fakegamecodes']) && !in_array($this->user->player_id,$materialIdsArr) && !in_array($this->user->player_id,$noFakePgPlayeridsArr) && !$issuccessreplace){
            if($game->main_game_plat_code == 'pg' && in_array('jp7',$KillGameLineAllCodes)){
                if(isset(config('main')['fakegamemap']['jp5'][$game->game_code])){
                    $gameExist             = Games::where('main_game_plat_code','jp7')->where('game_code',config('main')['fakegamemap']['jp5'][$game->game_code])->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                }      
            } elseif($game->main_game_plat_code == 'pp' && in_array('pp7',$KillGameLineAllCodes)){
                $gameExist             = Games::where('main_game_plat_code','pp7')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            } elseif($game->main_game_plat_code == 'jili' && in_array('jili7',$KillGameLineAllCodes)){
                if(isset(config('main')['fakegamemap']['jili5'][$game->game_code])){
                    $gameExist             = Games::where('main_game_plat_code','jili7')->where('game_code',config('main')['fakegamemap']['jili5'][$game->game_code])->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                }
            } elseif($game->main_game_plat_code == 'fc' && in_array('fc7',$KillGameLineAllCodes)){
                if(isset(config('main')['fakegamemap']['fc5'][$game->game_code])){
                    $gameExist             = Games::where('main_game_plat_code','fc7')->where('game_code',config('main')['fakegamemap']['fc5'][$game->game_code])->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                }
            } elseif($game->main_game_plat_code == 'cq9' && in_array('cq97',$KillGameLineAllCodes)){
                $gameExist             = Games::where('main_game_plat_code','cq97')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            } elseif($game->main_game_plat_code == 'habanero' && in_array('habanero7',$KillGameLineAllCodes)){
                $gameExist             = Games::where('main_game_plat_code','habanero7')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            } elseif($game->main_game_plat_code == 'jdb' && in_array('jdb7',$KillGameLineAllCodes)){
                $gameExist             = Games::where('main_game_plat_code','jdb7')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            } elseif($game->main_game_plat_code == 'ky'){
                $gameExist             = Games::where('main_game_plat_code','ky1')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            }            
                
            if($issuccessreplace===true){
                $input['gameId'] = $game->game_id;
            }
        }

        //游戏概率进入假游戏
        if(!$issuccessreplace && in_array($game->main_game_plat_code,config('main')['fakegamecodes']) && !in_array($this->user->player_id,$materialIdsArr) && !in_array($this->user->player_id,$noFakePgPlayeridsArr) && $this->user->player_group_id > 0){
            $gameLineAllCodes  = PlayerCache::getGameLineAllCode($this->user->player_group_id);
            $originalMainPlat  = '';

            if(count($gameLineAllCodes)){
                if($game->main_game_plat_code == 'pg'){
                    if(in_array('jp5', $gameLineAllCodes)){
                        $originalMainPlat = 'jp5';
                    } elseif(in_array('jp7', $gameLineAllCodes)){
                        $originalMainPlat = 'jp7';
                    } elseif(in_array('jp8', $gameLineAllCodes)){
                        $originalMainPlat = 'jp8';
                    } elseif(in_array('jp9', $gameLineAllCodes)){
                        $originalMainPlat = 'jp9';
                    }

                    if(isset(config('main')['fakegamemap']['jp5'][$game->game_code]) && !empty($originalMainPlat)){
                        $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',config('main')['fakegamemap']['jp5'][$game->game_code])->first();
                        if($gameExist){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }      
                } elseif($game->main_game_plat_code == 'pp'){
                    if(in_array('pp5', $gameLineAllCodes)){
                        $originalMainPlat = 'pp5';
                    } elseif(in_array('pp7', $gameLineAllCodes)){
                        $originalMainPlat = 'pp7';
                    } elseif(in_array('pp8', $gameLineAllCodes)){
                        $originalMainPlat = 'pp8';
                    } elseif(in_array('pp9', $gameLineAllCodes)){
                        $originalMainPlat = 'pp9';
                    }
                    $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                } elseif($game->main_game_plat_code == 'jili'){
                    if(in_array('jili5', $gameLineAllCodes)){
                        $originalMainPlat = 'jili5';
                    } elseif(in_array('jili7', $gameLineAllCodes)){
                        $originalMainPlat = 'jili7';
                    } elseif(in_array('jili8', $gameLineAllCodes)){
                        $originalMainPlat = 'jili8';
                    } elseif(in_array('jili9', $gameLineAllCodes)){
                        $originalMainPlat = 'jili9';
                    }
                    if(isset(config('main')['fakegamemap']['jili5'][$game->game_code])){
                        $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',config('main')['fakegamemap']['jili5'][$game->game_code])->first();
                        if($gameExist){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }
                } elseif($game->main_game_plat_code == 'fc'){
                    if(in_array('fc5', $gameLineAllCodes)){
                        $originalMainPlat = 'fc5';
                    } elseif(in_array('fc7', $gameLineAllCodes)){
                        $originalMainPlat = 'fc7';
                    } elseif(in_array('fc8', $gameLineAllCodes)){
                        $originalMainPlat = 'fc8';
                    } elseif(in_array('fc9', $gameLineAllCodes)){
                        $originalMainPlat = 'fc9';
                    }
                    if(isset(config('main')['fakegamemap']['fc5'][$game->game_code])){
                        $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',config('main')['fakegamemap']['fc5'][$game->game_code])->first();
                        if($gameExist){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }
                } elseif($game->main_game_plat_code == 'cq9'){
                    if(in_array('cq95', $gameLineAllCodes)){
                        $originalMainPlat = 'cq95';
                    } elseif(in_array('cq97', $gameLineAllCodes)){
                        $originalMainPlat = 'cq97';
                    } elseif(in_array('cq98', $gameLineAllCodes)){
                        $originalMainPlat = 'cq98';
                    } elseif(in_array('cq99', $gameLineAllCodes)){
                        $originalMainPlat = 'cq99';
                    }
                    $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                } elseif($game->main_game_plat_code == 'habanero'){
                    if(in_array('habanero5', $gameLineAllCodes)){
                        $originalMainPlat = 'habanero5';
                    } elseif(in_array('habanero7', $gameLineAllCodes)){
                        $originalMainPlat = 'habanero7';
                    } elseif(in_array('habanero8', $gameLineAllCodes)){
                        $originalMainPlat = 'habanero8';
                    } elseif(in_array('habanero9', $gameLineAllCodes)){
                        $originalMainPlat = 'habanero9';
                    }
                    $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                } elseif($game->main_game_plat_code == 'jdb'){
                    if(in_array('jdb5', $gameLineAllCodes)){
                        $originalMainPlat = 'jdb5';
                    } elseif(in_array('jdb7', $gameLineAllCodes)){
                        $originalMainPlat = 'jdb7';
                    } elseif(in_array('jdb8', $gameLineAllCodes)){
                        $originalMainPlat = 'jdb8';
                    } elseif(in_array('jdb9', $gameLineAllCodes)){
                        $originalMainPlat = 'jdb9';
                    }
                    $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                } elseif($game->main_game_plat_code == 'ky'){
                    $gameExist             = Games::where('main_game_plat_code','ky1')->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                }         
                    
                if($issuccessreplace===true){
                    $input['gameId'] = $game->game_id;
                }
            }
        }

        if(in_array($this->user->player_id,$materialIdsArr) && $game->main_game_plat_code!='pg' && $game->main_game_plat_code!='pp'){
            return $this->returnApiJson(config('language')[$this->language]['error291'], 0);
        }

        //素材号走的流程
        if(in_array($this->user->player_id,$materialIdsArr)){
            if($game->main_game_plat_code=='pg'){
                $maintainGame              = CarrierGamePlat::where('game_plat_id',2)->where('status',1)->first();
                if($maintainGame && isset(config('main')['fakegamemap']['jp5'][$game->game_code])){
                    $gameExist             = Games::where('main_game_plat_code','jp6')->where('game_code',config('main')['fakegamemap']['jp5'][$game->game_code])->first();
                    if($gameExist){
                        $game             = $gameExist;
                    } else{
                        return $this->returnApiJson(config('language')[$this->language]['error292'], 0);
                    }
                } else{
                    return $this->returnApiJson(config('language')[$this->language]['error292'], 0);
                }
            } elseif($game->main_game_plat_code=='pp'){
                $maintainGame     = CarrierGamePlat::where('game_plat_id',8)->where('status',1)->first();
                if($maintainGame){
                    $gameExist             = Games::where('main_game_plat_code','pp6')->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                    } else{
                        return $this->returnApiJson(config('language')[$this->language]['error292'], 0);
                    }
                } 
            }
        }

        if(!empty($this->user->limitgameplat)){
            $limitgameplats = explode(',',$this->user->limitgameplat);
            if(!in_array($game->main_game_plat_id, $limitgameplats)){
                return $this->returnApiJson(config('language')[$this->language]['error197'], 0);
            }
        }

        $carrierGame = CarrierGame::where('game_id',$input['gameId'])->first();
        
        if(!$carrierGame) {
            return $this->returnApiJson(config('language')[$this->language]['error30'], 0);
        }

        if($carrierGame->status==0) {
            return $this->returnApiJson(config('language')[$this->language]['error31'], 0);
        }

        if($carrierGame->status==2) {
            return $this->returnApiJson(config('language')[$this->language]['error251'], 0);
        }

        $carrierGame->sort = $carrierGame->sort + 1;
        $carrierGame->save();

        //限制游戏分类
        $limitCategorys        = [];
        $betflowLimitCategorys = PlayerWithdrawFlowLimit::where('player_id',$this->user->player_id)->where('is_finished',0)->pluck('betflow_limit_category')->toArray();
        if(count($betflowLimitCategorys)){
            foreach ($betflowLimitCategorys as $key => $value) {
                if(!empty($value) && $value != 0){
                    $betflowLimitCategorysArr = explode(',', $value);
                    foreach ($betflowLimitCategorysArr as $k => $v) {
                        $limitCategorys[] = $v;
                    }
                }
            }
        }

        if(count($limitCategorys)){
            if(!in_array($carrierGame->game_category,$limitCategorys)){
                $limitCategorysStr = '';
                foreach ($limitCategorys as $k1 => $v1) {
                    switch ($v1) {
                        case '1':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text15'].',';
                            break;
                        case '2':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text16'].',';
                            break;
                        case '3':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text17'].',';
                            break;
                        case '4':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text19'].',';
                            break;
                        case '5':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text20'].',';
                            break;
                        case '6':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text14'].',';
                            break;
                        case '7':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text18'].',';
                            break;
                        default:
                            // code...
                            break;
                    }
                }

                $limitCategorysStr = rtrim($limitCategorysStr,',');

                return $this->returnApiJson(config('language')[$this->language]['error293'].$limitCategorysStr.config('language')[$this->language]['error294'], 0);
            }
        }

        $limitMainGamePlatIds        = [];
        $betflowLimitMainGamePlatIds = PlayerWithdrawFlowLimit::where('player_id',$this->user->player_id)->where('is_finished',0)->pluck('betflow_limit_main_game_plat_id')->toArray();
        if(count($betflowLimitMainGamePlatIds)){
            foreach ($betflowLimitMainGamePlatIds as $key => $value) {
                $betflowLimitMainGamePlatIdsArr = explode(',', $value);
                foreach ($betflowLimitMainGamePlatIdsArr as $k => $v) {
                    if(!empty($v)){
                        $limitMainGamePlatIds[] = $v;
                    }
                }
            }
        }

        //添加假PG
        if(count($limitMainGamePlatIds)){
            if(!in_array($carrierGame->game_plat_id,$limitMainGamePlatIds)){
                return $this->returnApiJson(config('language')[$this->language]['error295'], 0);
            }
        }

        $carrierGamePlat = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',$carrierGame->game_plat_id)->first();
        if($carrierGamePlat->status==0){
            return $this->returnApiJson(config('language')[$this->language]['error31'], 0);
        }

        if($carrierGamePlat->status==2){
            return $this->returnApiJson(config('language')[$this->language]['error251'], 0);
        }

        $existPlayerGameAccount = PlayerGameAccount::where('main_game_plat_code',$game->main_game_plat_code)->where('player_id',$this->user->player_id)->first();
        if($existPlayerGameAccount && $existPlayerGameAccount->is_need_repair==1){
            return $this->returnApiJson(config('language')[$this->language]['error251'], 0);
        }

        $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->first();

        //免转相关操作
        if($this->user->is_notransfer){
            //先转出
            $transferKey        = 'gametranfer_'.$this->user->player_id;
            $cacheTransferKey   = cache()->get($transferKey);
            if(cache()->has($transferKey) && $cacheTransferKey != $game->main_game_plat_code){
                //判断转出平台是否维护
                $gamePlatId      = GameCache::getGamePlatId($cacheTransferKey);
                $carrierGamePlat = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',$gamePlatId)->first();
                if($carrierGamePlat && $carrierGamePlat->status==1){
                    //转帐操作
                    $playerGameAccount  = PlayerGameAccount::where('player_id',$this->user->player_id)->where('main_game_plat_code',$cacheTransferKey)->first();
                    if($playerGameAccount  && $playerGameAccount->is_locked ==0 && $playerGameAccount->is_need_repair==0){
                        request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
                        request()->offsetSet('password',$playerGameAccount->password);
                        request()->offsetSet('mainGamePlatCode',$cacheTransferKey);

                        $transferoutGame    = new Game($this->carrier,$cacheTransferKey);        
                        $transferoutBalance = $transferoutGame->getBalance();
                        if(is_array($transferoutBalance) && $transferoutBalance['success']){
                           if($transferoutBalance['data']['balance'] >= 1){
                             request()->offsetSet('price',intval($transferoutBalance['data']['balance']));
                             $output = $transferoutGame->transferTo($this->user);
                             if(is_array($output) && $output['success']){
                                cache()->forget($transferKey);
                             } else{
                                if($cacheTransferKey =='pp5'){
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'5', 0);
                                } elseif($cacheTransferKey =='pp6'){
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'6', 0);
                                } elseif($cacheTransferKey =='pp8'){
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'8', 0);
                                } elseif($cacheTransferKey =='pp9'){
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'9', 0);
                                } elseif($cacheTransferKey =='ky1'){
                                    return $this->returnApiJson(config('language')[$this->language]['error276'].'1', 0);
                                } elseif($cacheTransferKey =='cq95'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error465'].'5', 0);
                                } elseif($cacheTransferKey =='cq98'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error465'].'8', 0);
                                } elseif($cacheTransferKey =='cq99'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error465'].'9', 0);
                                } elseif($cacheTransferKey =='jdb5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error466'].'5', 0);
                                } elseif($cacheTransferKey =='jdb8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error466'].'8', 0);
                                } elseif($cacheTransferKey =='jdb9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error466'].'9', 0);
                                } elseif($cacheTransferKey =='fc5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error467'].'5', 0);
                                } elseif($cacheTransferKey =='fc8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error467'].'8', 0);
                                } elseif($cacheTransferKey =='fc9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error467'].'9', 0);
                                } elseif($cacheTransferKey =='jp5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'5', 0);
                                } elseif($cacheTransferKey =='jp8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'8', 0);
                                } elseif($cacheTransferKey =='jp9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'9', 0);
                                } elseif($cacheTransferKey =='jp6'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'6', 0);
                                } elseif($cacheTransferKey =='habanero5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error469'].'5', 0);
                                } elseif($cacheTransferKey =='habanero8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error469'].'8', 0);
                                } elseif($cacheTransferKey =='habanero9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error469'].'9', 0);
                                } elseif($cacheTransferKey =='jili5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error473'].'5', 0);
                                } elseif($cacheTransferKey =='jili8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error473'].'8', 0);
                                } elseif($cacheTransferKey =='jili9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error473'].'9', 0);
                                } elseif($cacheTransferKey =='cq97'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error465'].'7', 0);
                                } elseif($cacheTransferKey =='pp7'){
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'7', 0);
                                } elseif($cacheTransferKey =='jp7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'7', 0);
                                } elseif($cacheTransferKey =='habanero7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error469'].'7', 0);
                                } elseif($cacheTransferKey =='fc7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error467'].'7', 0);
                                } elseif($cacheTransferKey =='jdb7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error466'].'7', 0);
                                } elseif($cacheTransferKey =='jili7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error473'].'7', 0);
                                } else{
                                    return $this->returnApiJson(config('language')[$this->language]['error277'].$cacheTransferKey.config('language')[$this->language]['error278'], 0);
                                }
                             }
                           } else{
                              cache()->forget($transferKey);
                           }
                        } else{
                            if($cacheTransferKey=='ky1'){
                                return $this->returnApiJson(config('language')[$this->language]['error304'].'1', 0);
                            } elseif($cacheTransferKey =='cq95'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error485'].'5', 0);
                            } elseif($cacheTransferKey =='cq98'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error485'].'8', 0);
                            } elseif($cacheTransferKey =='cq99'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error485'].'9', 0);
                            } elseif($cacheTransferKey =='jdb5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error486'].'5', 0);
                            } elseif($cacheTransferKey =='jdb8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error486'].'8', 0);
                            } elseif($cacheTransferKey =='jdb9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error486'].'9', 0);
                            } elseif($cacheTransferKey =='fc5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error487'].'5', 0);
                            } elseif($cacheTransferKey =='fc8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error487'].'8', 0);
                            } elseif($cacheTransferKey =='fc9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error487'].'9', 0);
                            } elseif($cacheTransferKey =='pp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'5', 0);
                            } elseif($cacheTransferKey =='pp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'8', 0);
                            } elseif($cacheTransferKey =='pp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'9', 0);
                            } elseif($cacheTransferKey =='pp6'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'6', 0);
                            } elseif($cacheTransferKey =='jp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'5', 0);
                            } elseif($cacheTransferKey =='jp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'8', 0);
                            } elseif($cacheTransferKey =='jp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'9', 0);
                            } elseif($cacheTransferKey =='jp6'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'6', 0);
                            } elseif($cacheTransferKey =='habanero5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error489'].'5', 0);
                            } elseif($cacheTransferKey =='habanero8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error489'].'8', 0);
                            } elseif($cacheTransferKey =='habanero9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error489'].'9', 0);
                            } elseif($cacheTransferKey =='jili5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error493'].'5', 0);
                            } elseif($cacheTransferKey =='jili8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error493'].'8', 0);
                            } elseif($cacheTransferKey =='jili9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error493'].'9', 0);
                            } elseif($cacheTransferKey =='cq97'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error485'].'7', 0);
                            } elseif($cacheTransferKey =='pp7'){
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'7', 0);
                            } elseif($cacheTransferKey =='jp7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'7', 0);
                            } elseif($cacheTransferKey =='habanero7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error489'].'7', 0);
                            } elseif($cacheTransferKey =='fc7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error487'].'7', 0);
                            } elseif($cacheTransferKey =='jdb7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error486'].'7', 0);
                            } elseif($cacheTransferKey =='jili7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error493'].'7', 0);
                            }else{
                                return $this->returnApiJson(config('language')[$this->language]['error308'].$cacheTransferKey.config('language')[$this->language]['error309'], 0);
                            }
                        }
                    } else{
                        if(!$playerGameAccount){
                           if($cacheTransferKey=='ky1'){
                                return $this->returnApiJson(config('language')[$this->language]['error312'].'1', 0);
                            } elseif($cacheTransferKey =='cq95'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'5', 0);
                            } elseif($cacheTransferKey =='cq98'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'8', 0);
                            } elseif($cacheTransferKey =='cq99'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'9', 0);
                            } elseif($cacheTransferKey =='jdb5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'5', 0);
                            } elseif($cacheTransferKey =='jdb8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'8', 0);
                            } elseif($cacheTransferKey =='jdb9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'9', 0);
                            } elseif($cacheTransferKey =='fc5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'5', 0);
                            } elseif($cacheTransferKey =='fc8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'8', 0);
                            } elseif($cacheTransferKey =='fc9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'9', 0);
                            } elseif($cacheTransferKey =='pp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'5', 0);
                            } elseif($cacheTransferKey =='pp6'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'6', 0);
                            } elseif($cacheTransferKey =='pp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'8', 0);
                            } elseif($cacheTransferKey =='pp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'9', 0);
                            } elseif($cacheTransferKey =='jp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'5', 0);
                            } elseif($cacheTransferKey =='jp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'8', 0);
                            } elseif($cacheTransferKey =='jp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'9', 0);
                            } elseif($cacheTransferKey =='jp6'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'6', 0);
                            } elseif($cacheTransferKey =='habanero5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error499'].'5', 0);
                            } elseif($cacheTransferKey =='habanero8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error499'].'8', 0);
                            } elseif($cacheTransferKey =='habanero9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error499'].'9', 0);
                            } elseif($cacheTransferKey =='jili5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'5', 0);
                            } elseif($cacheTransferKey =='jili8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'8', 0);
                            } elseif($cacheTransferKey =='jili9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'9', 0);
                            } elseif($cacheTransferKey =='cq97'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'7', 0);
                            } elseif($cacheTransferKey =='pp7'){
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'7', 0);
                            } elseif($cacheTransferKey =='jp7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'7', 0);
                            } elseif($cacheTransferKey =='habanero7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error501'].'7', 0);
                            } elseif($cacheTransferKey =='fc7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'7', 0);
                            } elseif($cacheTransferKey =='jdb7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'7', 0);
                            } elseif($cacheTransferKey =='jili7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'7', 0);
                            }else{
                                return $this->returnApiJson(config('language')[$this->language]['error268'].$cacheTransferKey.config('language')[$this->language]['error316'], 0);
                            }
                        } elseif($playerGameAccount->is_locked){
                            return $this->returnApiJson(config('language')[$this->language]['error317'], 0);
                        }
                    }
                //转出操作
                }
            }
            //先转出结束
            $playerAccount           = PlayerAccount::where('player_id',$this->user->player_id)->first();
            $minTraninGameplatAmount = CarrierCache::getCarrierConfigure($this->carrier->id,'min_tranin_gameplat_amount');
            $intMultiple             = bcdiv($playerAccount->balance,$minTraninGameplatAmount*10000,0);

            if($playerAccount->balance >= $minTraninGameplatAmount*10000){
                request()->offsetSet('mainGamePlatCode',$game->main_game_plat_code);
                request()->offsetSet('price',$intMultiple*$minTraninGameplatAmount);

                $output = $this->transferIn();
                if(!is_array($output) || !$output['success']){
                    if($game->main_game_plat_code=='ky1'){
                        return $this->returnApiJson(config('language')[$this->language]['error322'].'1', 0);
                    } elseif($game->main_game_plat_code =='cq95'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error505'].'5', 0);
                    } elseif($game->main_game_plat_code =='cq98'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error505'].'8', 0);
                    } elseif($game->main_game_plat_code =='cq99'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error505'].'9', 0);
                    } elseif($game->main_game_plat_code =='jdb5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error506'].'5', 0);
                    } elseif($game->main_game_plat_code =='jdb8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error506'].'8', 0);
                    } elseif($game->main_game_plat_code =='jdb9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error506'].'9', 0);
                    } elseif($game->main_game_plat_code =='fc5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error507'].'5', 0);
                    } elseif($game->main_game_plat_code =='fc8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error507'].'8', 0);
                    } elseif($game->main_game_plat_code =='fc9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error507'].'9', 0);
                    } elseif($game->main_game_plat_code =='pp5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'5', 0);
                    } elseif($game->main_game_plat_code =='pp6'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'6', 0);
                    } elseif($game->main_game_plat_code =='pp8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'8', 0);
                    } elseif($game->main_game_plat_code =='pp9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'9', 0);
                    } elseif($game->main_game_plat_code =='jp5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'5', 0);
                    } elseif($game->main_game_plat_code =='jp8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'8', 0);
                    } elseif($game->main_game_plat_code =='jp9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'9', 0);
                    } elseif($game->main_game_plat_code =='jp6'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'6', 0);
                    } elseif($game->main_game_plat_code =='habanero5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error509'].'5', 0);
                    } elseif($game->main_game_plat_code =='habanero8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error509'].'8', 0);
                    } elseif($game->main_game_plat_code =='habanero9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error509'].'9', 0);
                    } elseif($game->main_game_plat_code =='jili5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error323'].'5', 0);
                    } elseif($game->main_game_plat_code =='jili8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error323'].'8', 0);
                    } elseif($game->main_game_plat_code =='jili9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error323'].'9', 0);
                    } elseif($game->main_game_plat_code =='cq97'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error505'].'7', 0);
                    } elseif($game->main_game_plat_code =='pp7'){
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'7', 0);
                    } elseif($game->main_game_plat_code =='jp7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'7', 0);
                    } elseif($game->main_game_plat_code =='habanero7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error509'].'7', 0);
                    } elseif($game->main_game_plat_code =='fc7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error507'].'7', 0);
                    } elseif($game->main_game_plat_code =='jdb7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error506'].'7', 0);
                    } elseif($game->main_game_plat_code =='jili7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error323'].'7', 0);
                    }else{
                        return $this->returnApiJson(config('language')[$this->language]['error324'].$game->main_game_plat_code.config('language')[$this->language]['error325'], 0);
                    }
                }
            }
        }
        //免转相关操作结束
        $cgame             = new Game($this->carrier,$game->main_game_plat_code);
        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$game->main_game_plat_code)->where('player_id',$this->user->player_id)->first();
        if(!$playerGameAccount) {

            request()->offsetSet('username',$this->user->user_name);
            request()->offsetSet('mainGamePlatCode',$game->main_game_plat_code);

            $output = $cgame->createMember($this->user);

            if(is_array($output) && $output['success'] == true) {
                request()->offsetSet('accountUserName',$output['data']['accountUserName']);
                request()->offsetSet('password',$output['data']['password']);
                request()->offsetSet('gameCode',$game->game_code);

                if($game->main_game_plat_code == 'tcg' || $game->main_game_plat_code == 'vr' || $game->main_game_plat_code == 'jz') {
                    $playerSetting = PlayerCache::getPlayerSetting($this->user->player_id);
                    request()->offsetSet('odds',$playerSetting->lottoadds);
                }
            } else {
                return $this->returnApiJson(config('language')[$this->language]['error24'], 0);
            }
        } else {
            if($playerGameAccount->is_need_repair){
                return $this->returnApiJson(config('language')[$this->language]['error127'], 0);
            }

            request()->offsetSet('mainGamePlatCode',$game->main_game_plat_code);
            request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
            request()->offsetSet('password',$playerGameAccount->password);
            request()->offsetSet('gameCode',$game->game_code);

            if($game->main_game_plat_code == 'tcg' || $game->main_game_plat_code == 'vr' || $game->main_game_plat_code == 'jz') {
                $playerSetting = PlayerCache::getPlayerSetting($this->user->player_id);
                request()->offsetSet('odds',$playerSetting->lottoadds);
            }
        }
        $output =  $cgame->joinGame();
        if(is_array($output)){    
            return $output;
        } else{
            return ['success' => false, 'data' => ['url' => ''], 'message' => config('language')[$this->language]['error298'],'code'=>200];
        }
    }

    public function joinMobileGame()
    {   
        $input                                     = request()->all();
        $enableFastKill                            = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'enable_fast_kill',$this->user->prefix);
        $enableAgentGameLimit                      = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'enable_agent_game_limit',$this->user->prefix);
        $forciblyJoinfakegameActivityid            = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'forcibly_joinfakegame_activityid',$this->user->prefix);
        $liveBroadcastAwards                       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'live_broadcast_awards',$this->user->prefix);
        $issuccessreplace                          = false;

        //查询层级用到的游戏列表
        $enablePgFactoryListArr = PlayerCache::getGameLineAllCode($this->user->player_group_id);

        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        if(!isset($input['gameId']) || trim($input['gameId']) == '') {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        //防止过快提交
        $cacheKey = $this->user->player_id."_joinGame_".$input['gameId'];

        $redisLock = Lock::addLock($cacheKey,1);

        if (!$redisLock) {
            return $this->returnApiJson(config('language')[$this->language]['error203'], 0);    
        } 

        if(is_numeric($input['gameId'])){
            $input['gameId'] = GameCache::getChangeGameId($input['gameId']);
        }

        $game = Games::where('game_id',$input['gameId'])->first();

        if(!$game) {
            return $this->returnApiJson(config('language')[$this->language]['error30'], 0);
        }

        //直播号替换PG
        if($this->user->is_live_streaming_account ==1){
            $liveBroadcastAwardsArr = explode(',', $liveBroadcastAwards);
            if(!empty($liveBroadcastAwards) && count($liveBroadcastAwardsArr) > 0){
                if($game->main_game_plat_code=='pg'){
                    //写死了平台ID
                    $maintainGame              = CarrierGamePlat::where('game_plat_id',2)->where('status',1)->first();
                    if($maintainGame && isset(config('main')['fakegamemap']['jp5'][$game->game_code])){
                        $gameExist             = Games::where('main_game_plat_code','jp6')->where('game_code',config('main')['fakegamemap']['jp5'][$game->game_code])->first();
                        if($gameExist && in_array($gameExist->game_id,$liveBroadcastAwardsArr)){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }
                    
                } elseif($game->main_game_plat_code=='pp'){
                    $maintainGame              = CarrierGamePlat::where('game_plat_id',8)->where('status',1)->first();
                    if($maintainGame){
                        $gameExist             = Games::where('main_game_plat_code','pp6')->where('game_code',$game->game_code)->first();
                        if($gameExist && in_array($gameExist->game_id,$liveBroadcastAwardsArr)){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }
                }
            }
        }
        //直播号结束

        //限制代理是否只能进入固定分类游戏
        if($this->user->win_lose_agent && $enableAgentGameLimit && !in_array($game->game_category,[2,4,7]) ){
            return $this->returnApiJson(config('language')[$this->language]['error290'], 0);
        }

        if(!in_array($game->main_game_plat_code,['cq95','jdb5','fc5','pp5','jp5','habanero5','jili5','jp6','pp6','cq97','pp7','jp7','habanero7','fc7','jdb7','jili7','cq98','cq99','pp8','pp9','jp8','jp9','habanero8','habanero9','fc8','fc9','jdb8','jdb9','jili8','jili9'])&& $game->game_category==2){
            $gameHot = GameHot::where('game_id',$input['gameId'])->where('carrier_id',$this->carrier->id)->where('prefix',$this->user->prefix)->first();
            if($gameHot){
                $gameHot->sort = $gameHot->sort + 1;
                $gameHot->save();
            } else{
                if($game->game_category==2){
                    $gameHot                      = new GameHot();
                    $gameHot->carrier_id          = $this->carrier->id;
                    $gameHot->main_game_plat_code = $game->main_game_plat_code;
                    $gameHot->prefix              = $this->user->prefix;
                    $gameHot->game_id             = $game->game_id;
                    $gameHot->sort                = 1;
                    $gameHot->save(); 
                }
            }
        }
        
        //不进假PG名单
        $noFakePgPlayerids    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'no_fake_pg_playerids',$this->user->prefix);

        //素材号ID
        $materialIds          = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'materialIds',$this->user->prefix);
        $noFakePgPlayeridsArr = explode(',',$noFakePgPlayerids);
        $materialIdsArr       = explode(',',$materialIds);

        //某活动强制进入假游戏
        $forcibly = false;
        $latelyPlayerDepositPayLog = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->first();

        //首存1加1活动进入点杀
        if($latelyPlayerDepositPayLog && !empty($latelyPlayerDepositPayLog->activityids) && ($latelyPlayerDepositPayLog->activityids==$forciblyJoinfakegameActivityid)){
            $forcibly = true;
        }

        $KillGameLineAllCodes = PlayerCache::getKillGameLineAllCode();
        
        //开启快杀或活动点杀
        if(($enableFastKill || $forcibly)&& in_array($game->main_game_plat_code,config('main')['fakegamecodes']) && !in_array($this->user->player_id,$materialIdsArr) && !in_array($this->user->player_id,$noFakePgPlayeridsArr) && !$issuccessreplace){
            if($game->main_game_plat_code == 'pg' && in_array('jp7',$KillGameLineAllCodes)){
                if(isset(config('main')['fakegamemap']['jp5'][$game->game_code])){
                    $gameExist             = Games::where('main_game_plat_code','jp7')->where('game_code',config('main')['fakegamemap']['jp5'][$game->game_code])->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                }      
            } elseif($game->main_game_plat_code == 'pp' && in_array('pp7',$KillGameLineAllCodes)){
                $gameExist             = Games::where('main_game_plat_code','pp7')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            } elseif($game->main_game_plat_code == 'jili' && in_array('jili7',$KillGameLineAllCodes)){
                if(isset(config('main')['fakegamemap']['jili5'][$game->game_code])){
                    $gameExist             = Games::where('main_game_plat_code','jili7')->where('game_code',config('main')['fakegamemap']['jili5'][$game->game_code])->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                }
            } elseif($game->main_game_plat_code == 'fc' && in_array('fc7',$KillGameLineAllCodes)){
                if(isset(config('main')['fakegamemap']['fc5'][$game->game_code])){
                    $gameExist             = Games::where('main_game_plat_code','fc7')->where('game_code',config('main')['fakegamemap']['fc5'][$game->game_code])->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                }
            } elseif($game->main_game_plat_code == 'cq9' && in_array('cq97',$KillGameLineAllCodes)){
                $gameExist             = Games::where('main_game_plat_code','cq97')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            } elseif($game->main_game_plat_code == 'habanero' && in_array('habanero7',$KillGameLineAllCodes)){
                $gameExist             = Games::where('main_game_plat_code','habanero7')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            } elseif($game->main_game_plat_code == 'jdb' && in_array('jdb7',$KillGameLineAllCodes)){
                $gameExist             = Games::where('main_game_plat_code','jdb7')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            } elseif($game->main_game_plat_code == 'ky'){
                $gameExist             = Games::where('main_game_plat_code','ky1')->where('game_code',$game->game_code)->first();
                if($gameExist){
                    $game             = $gameExist;
                    $issuccessreplace = true;
                }
            }           
                
            if($issuccessreplace===true){
                $input['gameId'] = $game->game_id;
            }
        }

        //游戏概率进入假游戏
        if(!$issuccessreplace && in_array($game->main_game_plat_code,config('main')['fakegamecodes']) && !in_array($this->user->player_id,$materialIdsArr) && !in_array($this->user->player_id,$noFakePgPlayeridsArr) && $this->user->player_group_id > 0){
            $gameLineAllCodes  = PlayerCache::getGameLineAllCode($this->user->player_group_id);
            $originalMainPlat  = '';
            if(count($gameLineAllCodes)){
                if($game->main_game_plat_code == 'pg'){
                    if(in_array('jp5', $gameLineAllCodes)){
                        $originalMainPlat = 'jp5';
                    } elseif(in_array('jp7', $gameLineAllCodes)){
                        $originalMainPlat = 'jp7';
                    } elseif(in_array('jp8', $gameLineAllCodes)){
                        $originalMainPlat = 'jp8';
                    } elseif(in_array('jp9', $gameLineAllCodes)){
                        $originalMainPlat = 'jp9';
                    }

                    if(isset(config('main')['fakegamemap']['jp5'][$game->game_code]) && !empty($originalMainPlat)){
                        $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',config('main')['fakegamemap']['jp5'][$game->game_code])->first();
                        if($gameExist){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }      
                } elseif($game->main_game_plat_code == 'pp'){
                    if(in_array('pp5', $gameLineAllCodes)){
                        $originalMainPlat = 'pp5';
                    } elseif(in_array('pp7', $gameLineAllCodes)){
                        $originalMainPlat = 'pp7';
                    } elseif(in_array('pp8', $gameLineAllCodes)){
                        $originalMainPlat = 'pp8';
                    } elseif(in_array('pp9', $gameLineAllCodes)){
                        $originalMainPlat = 'pp9';
                    }
                    $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                } elseif($game->main_game_plat_code == 'jili'){
                    if(in_array('jili5', $gameLineAllCodes)){
                        $originalMainPlat = 'jili5';
                    } elseif(in_array('jili7', $gameLineAllCodes)){
                        $originalMainPlat = 'jili7';
                    } elseif(in_array('jili8', $gameLineAllCodes)){
                        $originalMainPlat = 'jili8';
                    } elseif(in_array('jili9', $gameLineAllCodes)){
                        $originalMainPlat = 'jili9';
                    }
                    if(isset(config('main')['fakegamemap']['jili5'][$game->game_code])){
                        $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',config('main')['fakegamemap']['jili5'][$game->game_code])->first();
                        if($gameExist){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }
                } elseif($game->main_game_plat_code == 'fc'){
                    if(in_array('fc5', $gameLineAllCodes)){
                        $originalMainPlat = 'fc5';
                    } elseif(in_array('fc7', $gameLineAllCodes)){
                        $originalMainPlat = 'fc7';
                    } elseif(in_array('fc8', $gameLineAllCodes)){
                        $originalMainPlat = 'fc8';
                    } elseif(in_array('fc9', $gameLineAllCodes)){
                        $originalMainPlat = 'fc9';
                    }
                    if(isset(config('main')['fakegamemap']['fc5'][$game->game_code])){
                        $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',config('main')['fakegamemap']['fc5'][$game->game_code])->first();
                        if($gameExist){
                            $game             = $gameExist;
                            $issuccessreplace = true;
                        }
                    }
                } elseif($game->main_game_plat_code == 'cq9'){
                    if(in_array('cq95', $gameLineAllCodes)){
                        $originalMainPlat = 'cq95';
                    } elseif(in_array('cq97', $gameLineAllCodes)){
                        $originalMainPlat = 'cq97';
                    } elseif(in_array('cq98', $gameLineAllCodes)){
                        $originalMainPlat = 'cq98';
                    } elseif(in_array('cq99', $gameLineAllCodes)){
                        $originalMainPlat = 'cq99';
                    }
                    $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                } elseif($game->main_game_plat_code == 'habanero'){
                    if(in_array('habanero5', $gameLineAllCodes)){
                        $originalMainPlat = 'habanero5';
                    } elseif(in_array('habanero7', $gameLineAllCodes)){
                        $originalMainPlat = 'habanero7';
                    } elseif(in_array('habanero8', $gameLineAllCodes)){
                        $originalMainPlat = 'habanero8';
                    } elseif(in_array('habanero9', $gameLineAllCodes)){
                        $originalMainPlat = 'habanero9';
                    }
                    $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                } elseif($game->main_game_plat_code == 'jdb'){
                    if(in_array('jdb5', $gameLineAllCodes)){
                        $originalMainPlat = 'jdb5';
                    } elseif(in_array('jdb7', $gameLineAllCodes)){
                        $originalMainPlat = 'jdb7';
                    } elseif(in_array('jdb8', $gameLineAllCodes)){
                        $originalMainPlat = 'jdb8';
                    } elseif(in_array('jdb9', $gameLineAllCodes)){
                        $originalMainPlat = 'jdb9';
                    }
                    $gameExist             = Games::where('main_game_plat_code',$originalMainPlat)->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                } elseif($game->main_game_plat_code == 'ky'){
                    $gameExist             = Games::where('main_game_plat_code','ky1')->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                        $issuccessreplace = true;
                    }
                }          
                    
                if($issuccessreplace===true){
                    $input['gameId'] = $game->game_id;
                }
            }
        }

        if(in_array($this->user->player_id,$materialIdsArr) && $game->main_game_plat_code!='pg' && $game->main_game_plat_code!='pp'){
            return $this->returnApiJson(config('language')[$this->language]['error291'], 0);
        }

        //素材号走的流程
        if(in_array($this->user->player_id,$materialIdsArr)){
            if($game->main_game_plat_code=='pg'){
                $maintainGame              = CarrierGamePlat::where('game_plat_id',2)->where('status',1)->first();
                if($maintainGame && isset(config('main')['fakegamemap']['jp5'][$game->game_code])){
                    $gameExist             = Games::where('main_game_plat_code','jp6')->where('game_code',config('main')['fakegamemap']['jp5'][$game->game_code])->first();
                    if($gameExist){
                        $game             = $gameExist;
                    } else{
                        return $this->returnApiJson(config('language')[$this->language]['error292'], 0);
                    }
                } else{
                    return $this->returnApiJson(config('language')[$this->language]['error292'], 0);
                }
            } elseif($game->main_game_plat_code=='pp'){
                $maintainGame     = CarrierGamePlat::where('game_plat_id',8)->where('status',1)->first();
                if($maintainGame){
                    $gameExist             = Games::where('main_game_plat_code','pp6')->where('game_code',$game->game_code)->first();
                    if($gameExist){
                        $game             = $gameExist;
                    } else{
                        return $this->returnApiJson(config('language')[$this->language]['error292'], 0);
                    }
                } 
            }
        }

        if(!empty($this->user->limitgameplat)){
            $limitgameplats = explode(',',$this->user->limitgameplat);
            if(!in_array($game->main_game_plat_id, $limitgameplats)){
                return $this->returnApiJson(config('language')[$this->language]['error197'], 0);
            }
        }

        $carrierGame = CarrierGame::where('game_id',$input['gameId'])->first();
        
        if(!$carrierGame) {
            return $this->returnApiJson(config('language')[$this->language]['error30'], 0);
        }

        if($carrierGame->status==0) {
            return $this->returnApiJson(config('language')[$this->language]['error31'], 0);
        }

        if($carrierGame->status==2) {
            return $this->returnApiJson(config('language')[$this->language]['error251'], 0);
        }

        $carrierGame->sort = $carrierGame->sort + 1;
        $carrierGame->save();


        //限制游戏分类
        $limitCategorys        = [];
        $betflowLimitCategorys = PlayerWithdrawFlowLimit::where('player_id',$this->user->player_id)->where('is_finished',0)->pluck('betflow_limit_category')->toArray();
        if(count($betflowLimitCategorys)){
            foreach ($betflowLimitCategorys as $key => $value) {
                if(!empty($value) && $value != 0){
                    $betflowLimitCategorysArr = explode(',', $value);
                    foreach ($betflowLimitCategorysArr as $k => $v) {
                        $limitCategorys[] = $v;
                    }
                }
            }
        }

        if(count($limitCategorys)){
            if(!in_array($carrierGame->game_category,$limitCategorys)){
                $limitCategorysStr = '';
                foreach ($limitCategorys as $k1 => $v1) {
                    switch ($v1) {

                        case '1':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text15'].',';
                            break;
                        case '2':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text16'].',';
                            break;
                        case '3':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text17'].',';
                            break;
                        case '4':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text19'].',';
                            break;
                        case '5':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text20'].',';
                            break;
                        case '6':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text14'].',';
                            break;
                        case '7':
                            $limitCategorysStr = $limitCategorysStr.config('language')[$this->language]['text18'].',';
                            break;
                        default:
                            // code...
                            break;
                    }
                }

                $limitCategorysStr = rtrim($limitCategorysStr,',');

                return $this->returnApiJson(config('language')[$this->language]['error293'].$limitCategorysStr.config('language')[$this->language]['error294'], 0);
            }
        }

        $limitMainGamePlatIds        = [];
        $betflowLimitMainGamePlatIds = PlayerWithdrawFlowLimit::where('player_id',$this->user->player_id)->where('is_finished',0)->pluck('betflow_limit_main_game_plat_id')->toArray();
        if(count($betflowLimitMainGamePlatIds)){
            foreach ($betflowLimitMainGamePlatIds as $key => $value) {
                $betflowLimitMainGamePlatIdsArr = explode(',', $value);
                foreach ($betflowLimitMainGamePlatIdsArr as $k => $v) {
                    if(!empty($v)){
                        $limitMainGamePlatIds[] = $v;
                    }
                }
            }
        }

        if(count($limitMainGamePlatIds)){
            if(!in_array($carrierGame->game_plat_id,$limitMainGamePlatIds)){
                return $this->returnApiJson(config('language')[$this->language]['error295'], 0);
            }
        }

        $existPlayerGameAccount = PlayerGameAccount::where('main_game_plat_code',$game->main_game_plat_code)->where('player_id',$this->user->player_id)->first();
        if($existPlayerGameAccount && $existPlayerGameAccount->is_need_repair==1){
            return $this->returnApiJson(config('language')[$this->language]['error251'], 0);
        }

        $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->first();
    
        if($this->user->is_notransfer){
            //先转出
            $transferKey        ='gametranfer_'.$this->user->player_id;
            $cacheTransferKey   = cache()->get($transferKey);
            if(cache()->has($transferKey) && $cacheTransferKey != $game->main_game_plat_code){
                $gamePlatId      = GameCache::getGamePlatId($cacheTransferKey);
                //转帐操作
                $carrierGamePlat    = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',$gamePlatId)->first();
                if($carrierGamePlat && $carrierGamePlat->status==1){
                    $playerGameAccount  = PlayerGameAccount::where('player_id',$this->user->player_id)->where('main_game_plat_code',$cacheTransferKey)->first();
                    if($playerGameAccount && $playerGameAccount->is_locked==0 && $playerGameAccount->is_need_repair==0){
                        request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
                        request()->offsetSet('password',$playerGameAccount->password);
                        request()->offsetSet('mainGamePlatCode',$cacheTransferKey);

                        $transferoutGame    = new Game($this->carrier,$cacheTransferKey);        
                        $transferoutBalance = $transferoutGame->getBalance();
                        
                        if(is_array($transferoutBalance) && $transferoutBalance['success']){
                           if($transferoutBalance['data']['balance'] >= 1){
                             request()->offsetSet('price',intval($transferoutBalance['data']['balance']));
                             $output = $transferoutGame->transferTo($this->user);
                             if(is_array($output) && $output['success']){
                                cache()->forget($transferKey);
                             } else{
                                if($cacheTransferKey=='jp6'){
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'6', 0);
                                } elseif($cacheTransferKey=='pp6'){
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'6', 0);
                                } elseif($cacheTransferKey=='ky1'){
                                    return $this->returnApiJson(config('language')[$this->language]['error276'].'1', 0);
                                } elseif($cacheTransferKey =='cq95'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error465'].'5', 0);
                                } elseif($cacheTransferKey =='cq98'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error465'].'8', 0);
                                } elseif($cacheTransferKey =='cq99'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error465'].'9', 0);
                                } elseif($cacheTransferKey =='jdb5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error466'].'5', 0);
                                } elseif($cacheTransferKey =='jdb8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error466'].'8', 0);
                                } elseif($cacheTransferKey =='jdb9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error466'].'9', 0);
                                } elseif($cacheTransferKey =='fc5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error467'].'5', 0);
                                } elseif($cacheTransferKey =='fc8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error467'].'8', 0);
                                } elseif($cacheTransferKey =='fc9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error467'].'9', 0);
                                } elseif($cacheTransferKey =='pp5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'5', 0);
                                } elseif($cacheTransferKey =='pp8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'8', 0);
                                } elseif($cacheTransferKey =='pp9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'9', 0);
                                } elseif($cacheTransferKey =='jp5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'5', 0);
                                } elseif($cacheTransferKey =='jp8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'8', 0);
                                } elseif($cacheTransferKey =='jp9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'9', 0);
                                } elseif($cacheTransferKey =='habanero5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error469'].'5', 0);
                                } elseif($cacheTransferKey =='habanero8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error469'].'8', 0);
                                } elseif($cacheTransferKey =='habanero9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error469'].'9', 0);
                                } elseif($cacheTransferKey =='jili5'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error473'].'5', 0);
                                } elseif($cacheTransferKey =='jili8'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error473'].'8', 0);
                                } elseif($cacheTransferKey =='jili9'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error473'].'9', 0);
                                } elseif($cacheTransferKey =='cq97'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error465'].'7', 0);
                                } elseif($cacheTransferKey =='pp7'){
                                    return $this->returnApiJson(config('language')[$this->language]['error275'].'7', 0);
                                } elseif($cacheTransferKey =='jp7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error274'].'7', 0);
                                } elseif($cacheTransferKey =='habanero7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error469'].'7', 0);
                                } elseif($cacheTransferKey =='fc7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error467'].'7', 0);
                                } elseif($cacheTransferKey =='jdb7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error466'].'7', 0);
                                } elseif($cacheTransferKey =='jili7'){    //////////
                                    return $this->returnApiJson(config('language')[$this->language]['error473'].'7', 0);
                                }else{
                                    return $this->returnApiJson(config('language')[$this->language]['error277'].$cacheTransferKey.config('language')[$this->language]['error278'], 0);
                                }
                             }
                           } else{
                                cache()->forget($transferKey);
                           }
                        } else{
                            if($cacheTransferKey=='jp6'){
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'6', 0);
                            } elseif($cacheTransferKey=='pp6'){
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'6', 0);
                            } elseif($cacheTransferKey=='ky1'){
                                return $this->returnApiJson(config('language')[$this->language]['error304'].'1', 0);
                            } elseif($cacheTransferKey =='cq95'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error485'].'5', 0);
                            } elseif($cacheTransferKey =='cq98'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error485'].'8', 0);
                            } elseif($cacheTransferKey =='cq99'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error485'].'9', 0);
                            } elseif($cacheTransferKey =='jdb5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error486'].'5', 0);
                            } elseif($cacheTransferKey =='jdb8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error486'].'8', 0);
                            } elseif($cacheTransferKey =='jdb9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error486'].'9', 0);
                            } elseif($cacheTransferKey =='fc5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error487'].'5', 0);
                            } elseif($cacheTransferKey =='fc8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error487'].'8', 0);
                            } elseif($cacheTransferKey =='fc9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error487'].'9', 0);
                            } elseif($cacheTransferKey =='pp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'5', 0);
                            } elseif($cacheTransferKey =='pp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'8', 0);
                            } elseif($cacheTransferKey =='pp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error303'].'9', 0);
                            } elseif($cacheTransferKey =='jp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'5', 0);
                            } elseif($cacheTransferKey =='jp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'8', 0);
                            } elseif($cacheTransferKey =='jp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'9', 0);
                            } elseif($cacheTransferKey =='habanero5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error489'].'5', 0);
                            } elseif($cacheTransferKey =='habanero8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error489'].'8', 0);
                            } elseif($cacheTransferKey =='habanero9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error489'].'9', 0);
                            } elseif($cacheTransferKey =='jili5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error493'].'5', 0);
                            } elseif($cacheTransferKey =='jili8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error493'].'8', 0);
                            } elseif($cacheTransferKey =='jili9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error493'].'9', 0);
                            } elseif($cacheTransferKey =='cq97'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'7', 0);
                            } elseif($cacheTransferKey =='pp7'){
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'7', 0);
                            } elseif($cacheTransferKey =='jp7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'7', 0);
                            } elseif($cacheTransferKey =='habanero7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error501'].'7', 0);
                            } elseif($cacheTransferKey =='fc7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'7', 0);
                            } elseif($cacheTransferKey =='jdb7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'7', 0);
                            } elseif($cacheTransferKey =='jili7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'7', 0);
                            }else{
                                return $this->returnApiJson(config('language')[$this->language]['error308'].$cacheTransferKey.config('language')[$this->language]['error309'], 0);
                            }
                        }
                    } elseif(!$playerGameAccount){
                            if($cacheTransferKey=='jp6'){
                                return $this->returnApiJson(config('language')[$this->language]['error310'].'6', 0);
                            } elseif($cacheTransferKey=='pp6'){
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'6', 0);
                            } elseif($cacheTransferKey=='ky1'){
                                return $this->returnApiJson(config('language')[$this->language]['error312'].'1', 0);
                            } elseif($cacheTransferKey =='cq95'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'5', 0);
                            } elseif($cacheTransferKey =='cq98'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'8', 0);
                            } elseif($cacheTransferKey =='cq99'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'9', 0);
                            } elseif($cacheTransferKey =='jdb5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'5', 0);
                            } elseif($cacheTransferKey =='jdb8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'8', 0);
                            } elseif($cacheTransferKey =='jdb9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'9', 0);
                            } elseif($cacheTransferKey =='fc5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'5', 0);
                            } elseif($cacheTransferKey =='fc8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'8', 0);
                            } elseif($cacheTransferKey =='fc9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'9', 0);
                            } elseif($cacheTransferKey =='pp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'5', 0);
                            } elseif($cacheTransferKey =='pp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'8', 0);
                            } elseif($cacheTransferKey =='pp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'9', 0);
                            } elseif($cacheTransferKey =='jp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'5', 0);
                            } elseif($cacheTransferKey =='jp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'8', 0);
                            } elseif($cacheTransferKey =='jp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'9', 0);
                            } elseif($cacheTransferKey =='habanero5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error499'].'5', 0);
                            } elseif($cacheTransferKey =='habanero8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error499'].'8', 0);
                            } elseif($cacheTransferKey =='habanero9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error499'].'9', 0);
                            } elseif($cacheTransferKey =='jili5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'5', 0);
                            } elseif($cacheTransferKey =='jili8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'8', 0);
                            } elseif($cacheTransferKey =='jili9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'9', 0);
                            } elseif($cacheTransferKey =='cq97'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error495'].'7', 0);
                            } elseif($cacheTransferKey =='pp7'){
                                return $this->returnApiJson(config('language')[$this->language]['error311'].'7', 0);
                            } elseif($cacheTransferKey =='jp7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error302'].'7', 0);
                            } elseif($cacheTransferKey =='habanero7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error501'].'7', 0);
                            } elseif($cacheTransferKey =='fc7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error497'].'7', 0);
                            } elseif($cacheTransferKey =='jdb7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error496'].'7', 0);
                            } elseif($cacheTransferKey =='jili7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error503'].'7', 0);
                            }else{
                                return $this->returnApiJson(config('language')[$this->language]['error268'].$cacheTransferKey.config('language')[$this->language]['error316'], 0);
                            }
                    } elseif($playerGameAccount->is_locked==1){
                        return $this->returnApiJson(config('language')[$this->language]['error317'], 0);
                    }
                    //转出操作
                }
            }
            //先转出结束
            $playerAccount           = PlayerAccount::where('player_id',$this->user->player_id)->first();
            $minTraninGameplatAmount = CarrierCache::getCarrierConfigure($this->carrier->id,'min_tranin_gameplat_amount');
            $intMultiple             = bcdiv($playerAccount->balance,$minTraninGameplatAmount*10000,0);

            if($playerAccount->balance >= $minTraninGameplatAmount*10000){
                request()->offsetSet('mainGamePlatCode',$game->main_game_plat_code);
                request()->offsetSet('price',$intMultiple*$minTraninGameplatAmount);
                $output = $this->transferIn();
                 if(!is_array($output) || !$output['success']){
                    if($game->main_game_plat_code=='jp6'){
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'6', 0);
                    } elseif($game->main_game_plat_code=='pp6'){
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'6', 0);
                    } elseif($game->main_game_plat_code=='ky1'){
                        return $this->returnApiJson(config('language')[$this->language]['error322'].'1', 0);
                    } elseif($game->main_game_plat_code =='cq95'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error505'].'5', 0);
                    } elseif($game->main_game_plat_code =='cq98'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error505'].'8', 0);
                    } elseif($game->main_game_plat_code =='cq99'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error505'].'9', 0);
                    } elseif($game->main_game_plat_code =='jdb5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error506'].'5', 0);
                    } elseif($game->main_game_plat_code =='jdb8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error506'].'8', 0);
                    } elseif($game->main_game_plat_code =='jdb9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error506'].'9', 0);
                    } elseif($game->main_game_plat_code =='fc5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error507'].'5', 0);
                    } elseif($game->main_game_plat_code =='fc8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error507'].'8', 0);
                    } elseif($game->main_game_plat_code =='fc9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error507'].'9', 0);
                    } elseif($game->main_game_plat_code =='pp5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'5', 0);
                    } elseif($game->main_game_plat_code =='pp8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'8', 0);
                    } elseif($game->main_game_plat_code =='pp9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'9', 0);
                    } elseif($game->main_game_plat_code =='jp5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'5', 0);
                    } elseif($game->main_game_plat_code =='jp8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'8', 0);
                    } elseif($game->main_game_plat_code =='jp9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'9', 0);
                    } elseif($game->main_game_plat_code =='habanero5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error509'].'5', 0);
                    } elseif($game->main_game_plat_code =='habanero8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error509'].'8', 0);
                    } elseif($game->main_game_plat_code =='habanero9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error509'].'9', 0);
                    } elseif($game->main_game_plat_code =='jili5'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error323'].'5', 0);
                    } elseif($game->main_game_plat_code =='jili8'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error323'].'8', 0);
                    } elseif($game->main_game_plat_code =='jili9'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error323'].'9', 0);
                    } elseif($game->main_game_plat_code =='cq97'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error505'].'7', 0);
                    } elseif($game->main_game_plat_code =='pp7'){
                        return $this->returnApiJson(config('language')[$this->language]['error319'].'7', 0);
                    } elseif($game->main_game_plat_code =='jp7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error318'].'7', 0);
                    } elseif($game->main_game_plat_code =='habanero7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error509'].'7', 0);
                    } elseif($game->main_game_plat_code =='fc7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error507'].'7', 0);
                    } elseif($game->main_game_plat_code =='jdb7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error506'].'7', 0);
                    } elseif($game->main_game_plat_code =='jili7'){    //////////
                        return $this->returnApiJson(config('language')[$this->language]['error323'].'7', 0);
                    }else{
                        return $this->returnApiJson(config('language')[$this->language]['error324'].$game->main_game_plat_code.config('language')[$this->language]['error325'], 0);
                    }
                } 
            }
        }

        $cgame             = new Game($this->carrier,$game->main_game_plat_code);
        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$game->main_game_plat_code)->where('player_id',$this->user->player_id)->first();

        if(!$playerGameAccount) {

            request()->offsetSet('username',$this->user->user_name);
            request()->offsetSet('mainGamePlatCode',$game->main_game_plat_code);
            $output = $cgame->createMember($this->user);
            if(is_array($output) && $output['success']) {
                request()->offsetSet('accountUserName',$output['data']['accountUserName']);
                request()->offsetSet('password',$output['data']['password']);
                request()->offsetSet('gameCode',$game->game_code);

                if($game->main_game_plat_code == 'tcg' || $game->main_game_plat_code == 'vr' || $game->main_game_plat_code == 'jz') {
                    $playerSetting = PlayerCache::getPlayerSetting($this->user->player_id);
                    request()->offsetSet('odds',$playerSetting->lottoadds);
                }
            } else {
                 return $this->returnApiJson(config('language')[$this->language]['error24'], 0);
            }
        } else {
            if($playerGameAccount->is_need_repair){
                return $this->returnApiJson(config('language')[$this->language]['error127'], 0);
            }
            request()->offsetSet('mainGamePlatCode',$game->main_game_plat_code);
            request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
            request()->offsetSet('password',$playerGameAccount->password);
            request()->offsetSet('gameCode',$game->game_code);

            if($game->main_game_plat_code == 'tcg' || $game->main_game_plat_code == 'vr' || $game->main_game_plat_code == 'jz') {
                $playerSetting = PlayerCache::getPlayerSetting($this->user->player_id);
                request()->offsetSet('odds',$playerSetting->lottoadds);
            }
        }

        $output = $cgame->joinMobileGame();
        if(is_array($output)){
            if($output['success']){
                $output['data']['displayType'] = $game->format;
            } 
            return $output;
            
        } else{
            return ['success' => false, 'data' => ['url' => ''], 'message' => config('language')[$this->language]['error298'],'code'=>200];
        }
    }

    public function joinGameLotteryLobby()
    {
        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        $carrierGame = CarrierGame::whereIn('game_id',config('game')['pub']['lotterylobby'])->first();

        if($carrierGame) {
           request()->offsetSet('gameId',$carrierGame->game_id);

           return $this->joinGame();
        } else {
            return $this->returnApiJson(config('language')[$this->language]['error24'], 0);
        }
    }

    public function joinMobileGameLotteryLobby()
    {
        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        $carrierGame = CarrierGame::whereIn('game_id',config('game')['pub']['lotterylobby'])->first();

        if($carrierGame) {
            request()->offsetSet('gameId',$carrierGame->game_id);

            return $this->joinMobileGame();
        } else {
            return $this->returnApiJson(config('language')[$this->language]['error24'], 0);
        }
    }

    public function hotGameList()
    {
        $input   = request()->all(); 
        $games   = new Games();
        $data    = $games->hotGameList($this->carrier,$input,$this->prefix);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function recomandCardList()
    {
        $input   = request()->all(); 
        $games   = new Games();
        $data    = $games->recomandcardList($this->carrier,$input);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function lotteryList()
    {
        $input   = request()->all();
        $games   = new Games();
        $data    = $games->lotteryList($this->carrier,$input,$this->prefix);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function recomandElectronicList()
    {
        $input   = request()->all();
        $games   = new Games();
        $data    = $games->recomandElectronicList($this->carrier,$input);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function electronicList()
    {
        request()->offsetSet('prefix',$this->prefix);
        $input = request()->all();
        $games = new Games();
        $user  = auth("api")->user();

        if(is_null($user) && isset($input['type']) && $input['type']=='history') {
            return returnBaseJson(config('language')[$this->language]['error32'], 0, [], 401);
        }

        if($user) {
            $data  = $games->electronicList($this->carrier,$this->prefix,$user);
        } else {
            $data  = $games->electronicList($this->carrier,$this->prefix);
        }

        $playerGameCollect = [];
        if($this->user){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('is_self',0)->pluck('game_id')->toArray();
        }

        foreach ($data['data'] as $key => &$value) {
            if(count($playerGameCollect) && in_array($value['game_id'],$playerGameCollect)){
                $value['is_collect'] =1;
            } else{
                $value['is_collect'] =0;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function electronicCategoryList()
    {
        $games = new Games();
        $data  = $games->electronicCategoryList($this->carrier,$this->prefix);
        $data = $data->toArray();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function liveList()
    {
        $input = request()->all();
        $games = new Games();
        $data  = $games->liveList($this->carrier,$input,$this->prefix);

        $playerGameCollect = [];
        if($this->user){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('is_self',0)->pluck('game_id')->toArray();
        }

        foreach ($data['data'] as $key => &$value) {
            if(count($playerGameCollect) && in_array($value['game_id'],$playerGameCollect)){
                $value['is_collect'] =1;
            } else{
                $value['is_collect'] =0;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function sportList()
    {
        $input = request()->all();
        $games = new Games();
        $data  = $games->sportList($this->carrier,$input,$this->prefix);

        $playerGameCollect = [];
        if($this->user){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('is_self',0)->pluck('game_id')->toArray();
        }

        foreach ($data['data'] as $key => &$value) {
            if(count($playerGameCollect) && in_array($value['game_id'],$playerGameCollect)){
                $value['is_collect'] =1;
            } else{
                $value['is_collect'] =0;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function esportList()
    {   
        $input = request()->all();
        $games = new Games();
        $data  = $games->esportList($this->carrier,$input);

        $playerGameCollect = [];
        if($this->user){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('is_self',0)->pluck('game_id')->toArray();
        }

        foreach ($data['data'] as $key => &$value) {
            if(count($playerGameCollect) && in_array($value['game_id'],$playerGameCollect)){
                $value['is_collect'] =1;
            } else{
                $value['is_collect'] =0;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function cardList()
    {
        $input = request()->all();
        $games = new Games();
        $data  = $games->cardList($this->carrier,$input,$this->prefix);

        $mainGamePlats = MainGamePlat::all();
        $plats         = [];
        foreach ($mainGamePlats as $key => $value) {
            $plats[$value->main_game_plat_code] = $value->short;
        }

        $playerGameCollect = [];
        if($this->user){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('is_self',0)->pluck('game_id')->toArray();
        }

        foreach ($data['data'] as $key => &$value) {
            $value['short'] = $plats[$value['main_game_plat_code']];
            if(count($playerGameCollect) && in_array($value['game_id'],$playerGameCollect)){
                $value['is_collect'] =1;
            } else{
                $value['is_collect'] =0;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function cardSubList()
    {
        $games = new Games();
        $data  = $games->cardSubList($this->carrier);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function fishList()
    {
        $input = request()->all();
        $games = new Games();
        $data  = $games->fishList($this->carrier,$input,$this->prefix);

        $mainGamePlats = MainGamePlat::all();
        $plats         = [];
        foreach ($mainGamePlats as $key => $value) {
            $plats[$value->main_game_plat_code] = $value->short;
        }

        $playerGameCollect = [];
        if($this->user){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('is_self',0)->pluck('game_id')->toArray();
        }

        foreach ($data['data'] as $key => &$value) {
            $value['short'] = $plats[$value['main_game_plat_code']];
            if(count($playerGameCollect) && in_array($value['game_id'],$playerGameCollect)){
                $value['is_collect'] =1;
            } else{
                $value['is_collect'] =0;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function platsList()
    {
        $games = new Games();
        $data  = $games->platsList($this->carrier);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function getLotteryCode()
    {
        $input = request()->all();

        if(!isset($input['mainGamePlatCode']) || empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }
        
        if(!in_array($input['mainGamePlatCode'], ['tcg','ae'])) {
            return $this->returnApiJson(config('language')[$this->language]['error33'], 0);
        }

        $games = new Game($this->carrier,$input['mainGamePlatCode']);
        $data  = $games->getLotteryCode();
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function fishCategorylist()
    {
        $gamePlatIds                = CarrierGame::where('carrier_id',$this->carrier->id)->where('game_category',7)->whereIn('status',[1,2])->groupBy('game_plat_id')->pluck('game_plat_id')->toArray();
        $carrierGamePlatPlatIds     = CarrierGamePlat::where('carrier_id',$this->carrier->id)->whereIn('status',[1,2])->whereIn('game_plat_id',$gamePlatIds)->pluck('game_plat_id')->toArray();
        $mapGamePlatStatus          = CarrierGamePlat::select('game_plat_id','status')->where('carrier_id',$this->carrier->id)->whereIn('game_plat_id',$carrierGamePlatPlatIds)->get();

        $arr = [];
        foreach($mapGamePlatStatus as $key => $value){
            $arr[$value->game_plat_id] = $value->status;
        }

        $mainGamePlats              = MainGamePlat::whereIn('main_game_plat_id',$carrierGamePlatPlatIds)->get()->toArray();
        
        $i                          = 1;

         foreach ($mainGamePlats as $k => &$v) {
            $v['template_moblie_game_icon_path'] = '/game/fish/'.$i.'.png';
            $v['template_game_icon_path']        = '/game/fish/'.$i.'.png';
            $v['game_icon_path']                 = '/game/fish/'.$v['main_game_plat_code'].'.png';
            $v['game_icon_path_moblie']          = '/game/fish/'.$v['main_game_plat_code'].'.png';
            $v['display_name']                   = $v['alias'];
            $v['game_plat_id']                   = $v['main_game_plat_id'];
            $v['status']                         = $arr[$v['main_game_plat_id']];
            if(in_array($v['main_game_plat_code'],config('main')['recommend_fish_plats'])){
                $v['is_recommend']               = 1;
            } else{
                $v['is_recommend']               = 0;
            }
            $i++;
        }

        $data['gamePlatCodes'] = $mainGamePlats;
        $data['url']           = config('main')['alicloudstore'].'0/template/';
        $data['mobileurl']     = config('main')['alicloudstore'].'0/mobiletemplate/';

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function fishSearch()
    {
        $input = request()->all();

        $query = CarrierGame::select('def_games.*')->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')->orderBy('map_carrier_games.id','desc')->whereIn('map_carrier_games.status',[1,2])->where('map_carrier_games.game_category',7);

        $carrierGames       = CarrierGame::select('game_id','game_plat_id','status')->where('carrier_id',$this->carrier->id)->where('game_category',7)->whereIn('status',[1,2])->get();
        $carrierGamesStatus = [];
        $allGamePlats       = [];

        foreach ($carrierGames as $key => $value) {
            if($value->status){
                $carrierGamesStatus[$value->game_id] =  $value->status;
            }

            $allGamePlats[] = $value->game_plat_id;
        }
        
        $allGamePlats = array_unique($allGamePlats);
        $allGamePlats = CarrierGamePlat::where('carrier_id',$this->carrier->id)->whereIn('game_plat_id',$allGamePlats)->whereIn('status',[1,2])->get();

        $carrierGamePlatsStatus = [];
        foreach ($allGamePlats as $key => $value) {
            $carrierGamePlatsStatus[$value->game_plat_id] = $value->status;
        }

        if(isset($input['main_game_plat_id']) && !empty($input['main_game_plat_id'])){
            $query->where('map_carrier_games.game_plat_id',$input['main_game_plat_id']);
        }

        if(isset($input['game_name']) && !empty($input['game_name'])){
            $query->where('map_carrier_games.display_name','like','%'.$input['game_name'].'%');
        }

        $data = $query->get()->toArray();

        $playerGameCollect = [];
        if($this->user){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('is_self',0)->pluck('game_id')->toArray();
        }

        foreach ($data as $key => &$value) {
            if(isset($carrierGamePlatsStatus[$value['main_game_plat_id']]) && isset($carrierGamesStatus[$value['game_id']]) && ($carrierGamePlatsStatus[$value['main_game_plat_id']] ==2 || $carrierGamesStatus[$value['game_id']] ==2)){
                $value['status'] = 2;
            } elseif(isset($carrierGamePlatsStatus[$value['main_game_plat_id']]) && isset($carrierGamesStatus[$value['game_id']])){
                $value['status'] = 1;
            } else{
                $value['status'] = 0;
            }
            if(count($playerGameCollect) && in_array($value['game_id'],$playerGameCollect)){
                $value['is_collect'] =1;
            } else{
                $value['is_collect'] =0;
            }
        }


        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function cardSearch()
    {
        $input = request()->all();
        
        $query = CarrierGame::select('def_games.*')->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')->orderBy('map_carrier_games.id','desc')->where('map_carrier_games.status',1)->where('map_carrier_games.game_category',4)->whereNotIn('def_games.game_id',config('main')['notdisplaycard']);

        if(isset(config('multiplefrontgame')[$this->carrier->id][$this->prefix]['card'])){
            $mainGamePlatIdArr = Games::whereIn('game_id',config('multiplefrontgame')[$this->carrier->id][$this->prefix]['card'])->pluck('main_game_plat_id')->toArray();
            $query->whereIn('map_carrier_games.game_plat_id',$mainGamePlatIdArr);
        }

        if(isset($input['main_game_plat_id']) && !empty($input['main_game_plat_id'])){
            $query->where('map_carrier_games.game_plat_id',$input['main_game_plat_id']);
        }

        if(isset($input['main_game_plat_code']) && !empty($input['main_game_plat_code'])){
            $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['main_game_plat_code'])->first();
            if($mainGamePlat){
                $query->where('map_carrier_games.game_plat_id',$mainGamePlat->main_game_plat_id);
            } else{
                $query->where('map_carrier_games.game_plat_id',0);
            }
        }

        if(isset($input['game_name']) && !empty($input['game_name'])){
            $query->where('map_carrier_games.display_name',$input['game_name']);
        }

        $data = $query->get()->toArray();

        $playerGameCollect = [];
        if($this->user){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('is_self',0)->pluck('game_id')->toArray();
        }

        foreach ($data as $key => &$value) {
            if(count($playerGameCollect) && in_array($value['game_id'],$playerGameCollect)){
                $value['is_collect'] =1;
            } else{
                $value['is_collect'] =0;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function cardBasonList()
    {
        $data    = CarrierGame::select('def_games.game_name','def_games.game_id','def_games.game_icon_square_path')->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')->where('map_carrier_games.status',1)->whereIn('map_carrier_games.game_id',config('main')['baisoncard'])->orderBy('map_carrier_games.id','desc')->get();
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function fishBasonList()
    {
        $data    = CarrierGame::select('def_games.game_name','def_games.game_id','def_games.game_icon_square_path')->leftJoin('def_games','def_games.game_id','=','map_carrier_games.game_id')->where('map_carrier_games.status',1)->whereIn('map_carrier_games.game_id',config('main')['baisonfish'])->orderBy('map_carrier_games.id','desc')->get();
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function customizeHall()
    {
        $data = [];
        $fish =[
            'type' => 'fish',
            'key'  => 'FISH',
            'api'  => '/api/fish/list',
            'zIndex' => 1,
        ];

        $lottery  =[
            'type' => 'lottery',
            'key'  => 'LOTT',
            'api'  => '/api/lottery/list',
            'zIndex' => 2,
        ];

        $sport  =[
            'type' => 'sport',
            'key'  => 'SPORT',
            'api'  => '/api/sport/list',
            'zIndex' => 3,
        ];

        $card  =[
            'type' => 'card',
            'key'  => 'PVP',
            'api'  => '/api/card/list',
            'zIndex' => 4,
        ];

        $esport  =[
            'type' => 'esport',
            'key'  => 'ESPORT',
            'api'  => '/api/esport/list',
            'zIndex' => 5,
        ];

        $electronic  =[
            'type' => 'electronic',
            'key'  => 'RNG',
            'api'  => '/api/electronic/categorylist',
            'zIndex' => 6,
        ];

        $live  =[
            'type' => 'live',
            'key'  => 'LIVE',
            'api'  => '/api/live/list',
            'zIndex' => 7,
        ];

        $hotgamelist  =[
            'type' => 'hotgamelist',
            'key'  => 'HOT',
            'api'  => '/api/hotgamelist',
            'zIndex' => 8,
        ];

        $data = [
            'fish'         => $fish,
            'lottery'      => $lottery,
            'sport'        => $sport,
            'card'         => $card,
            'esport'       => $esport,
            'electronic'   => $electronic,
            'live'         => $live,
            'hotgamelist' => $hotgamelist
        ];

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }
}