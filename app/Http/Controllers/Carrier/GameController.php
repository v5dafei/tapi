<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Models\Log\PlayerBetFlow;
use App\Models\Def\MainGamePlat;
use App\Models\PlayerGameAccount;
use App\Models\PlayerAccount;
use App\Models\Player;
use App\Models\CarrierReturnWaterBlack;
use App\Game\Game;

class GameController extends BaseController
{
    public function betflowList() 
    {
        $res = PlayerBetFlow::betflowList($this->carrier);
        if(is_array($res)) {
             return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

     public function getBalance($player_id=0)
    {
        $input  = request()->all();

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$player_id)->first();

        if(!$player) {
        	return $this->returnApiJson("对不起, 此用户不存在!", 0);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson("对不起, 缺少mainGamePlatCode参数!", 0);
        }

        $mainGamePlat = GameCache::getGamePlatId($input['mainGamePlatCode']);

        if(!$mainGamePlat) {
            return $this->returnApiJson("对不起, 此游戏平台不存在!", 0);
        }

        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$player_id)->first();
        if(!$playerGameAccount) {
            return $this->returnApiJson("查询成功", 1, ['balance' => '0.00']);
        }

        request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
        request()->offsetSet('password',$playerGameAccount->password);
       
        $game = new Game($this->carrier,$input['mainGamePlatCode']);

        return $game->getBalance();
    }

    public function transferIn($player_id=0)
    {
        $input = request()->all();

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$player_id)->first();

        if(!$player) {
        	return $this->returnApiJson("对不起, 此用户不存在!", 0);
        }

        $currency =CarrierCache::getCurrencyByPrefix($player->prefix);

        $playerAccount = PlayerAccount::where('carrier_id',$this->carrier->id)->where('player_id',$player_id)->first();
        $price         = bcdiv($playerAccount->balance,10000,4);
        $price         = intval($price);

        if($currency=='VND'){
            $residue = $price % 1000;
            $price   = $price - $residue;
        }

        if($price<CarrierCache::getCarrierConfigure($this->carrier->id,'min_tranin_gameplat_amount')) {
        	return $this->returnApiJson("对不起, 帐户余额不足!", 0);
        } else {
        	request()->offsetSet('price',$price);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson("对不起, 缺少mainGamePlatCode参数!", 0);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['mainGamePlatCode'])->first();

        if(!$mainGamePlat) {
            return $this->returnApiJson("对不起, 此游戏平台不存在!", 0);
        }

        $game = new Game($this->carrier,$input['mainGamePlatCode']);

        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$player_id)->first();
        if(!$playerGameAccount) {
            request()->offsetSet('username',$player->user_name);
            request()->offsetSet('mainGamePlatCode',$input['mainGamePlatCode']);

            $output = $game->createMember($player);
             if($output['success'] == true) {
                request()->offsetSet('accountUserName',$output['data']['accountUserName']);
                request()->offsetSet('password',$output['data']['password']); 
            } else {
                return $this->returnApiJson("对不起, 创建游戏帐号失败!", 0);
            }
        } else {
        	if($playerGameAccount->is_locked) {
        		return $this->returnApiJson("对不起, 此游戏帐号已被转帐锁定!", 0);
        	}
            request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
            request()->offsetSet('password',$playerGameAccount->password);
        }
        
        return $game->transferIn($player);
    }

    public function transferTo($player_id=0)
    {
        $input = request()->all();

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$player_id)->first();

        if(!$player) {
        	return $this->returnApiJson("对不起, 此用户不存在!", 0);
        }

        $output = $this->getBalance($player_id);
        if(is_array($output) && $output['success']==true) {
        	$price = intval($output['data']['balance']);
        	if($price<1) {
        		return $this->returnApiJson("对不起, 此平台金额少于1元无法转出!", 0);
        	}
    		request()->offsetSet('price',$price);
        } else{
        	return $this->returnApiJson("对不起, 查询余额失败!", 0);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson("对不起, 缺少mainGamePlatCode参数!", 0);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['mainGamePlatCode'])->first();

        if(!$mainGamePlat) {
            return $this->returnApiJson("对不起, 此游戏平台不存在!", 0);
        }

        $game = new Game($this->carrier,$input['mainGamePlatCode']);

        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$player_id)->first();
        if(!$playerGameAccount) {
            return $this->returnApiJson("对不起, 当前用户还没有此游戏平台帐号!", 0);
        } else {
        	if($playerGameAccount->is_locked) {
        		return $this->returnApiJson("对不起, 此游戏帐号已被转帐锁定!", 0);
        	}
            request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
            request()->offsetSet('password',$playerGameAccount->password);
        }

        return $game->transferTo($player);
    }

    public function kick($player_id=0)
    {
        $input = request()->all();

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$player_id)->first();

        if(!$player) {
        	return $this->returnApiJson("对不起, 此用户不存在!", 0);
        }
        
        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson("对不起, 缺少mainGamePlatCode参数!", 0);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['mainGamePlatCode'])->first();

        if(!$mainGamePlat) {
            return $this->returnApiJson("对不起, 此游戏平台不存在!", 0);
        }

        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$player_id)->first();
        if(!$playerGameAccount) {
            return $this->returnApiJson("对不起, 当前用户还有此游戏平台帐号!", 0);
        }
        request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
        request()->offsetSet('password',$playerGameAccount->password);

        $game = new Game($this->carrier,$input['mainGamePlatCode']);
        return $game->kick();
    }

    public function changeLock($player_id=0)
    {
    	$input = request()->all();

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$player_id)->first();

        if(!$player) {
        	return $this->returnApiJson("对不起, 此用户不存在!", 0);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson("对不起, 缺少mainGamePlatCode参数!", 0);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['mainGamePlatCode'])->first();

        if(!$mainGamePlat) {
            return $this->returnApiJson("对不起, 此游戏平台不存在!", 0);
        }

        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$player_id)->first();
        if(!$playerGameAccount) {
            return $this->returnApiJson("对不起, 当前用户还有此游戏平台帐号!", 0);
        }
        $playerGameAccount->is_locked = $playerGameAccount->is_locked ? 0 :1 ;
        $playerGameAccount->save();

        return $this->returnApiJson("操作成功!", 1);
    }

    public function changeRepair($player_id=0)
    {
    	$input = request()->all();

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$player_id)->first();

        if(!$player) {
        	return $this->returnApiJson("对不起, 此用户不存在!", 0);
        }

        if(!isset($input['mainGamePlatCode']) ||empty(trim($input['mainGamePlatCode']))) {
            return $this->returnApiJson("对不起, 缺少mainGamePlatCode参数!", 0);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_code',$input['mainGamePlatCode'])->first();

        if(!$mainGamePlat) {
            return $this->returnApiJson("对不起, 此游戏平台不存在!", 0);
        }

        $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$input['mainGamePlatCode'])->where('player_id',$player_id)->first();
        if(!$playerGameAccount) {
            return $this->returnApiJson("对不起, 当前用户还有此游戏平台帐号!", 0);
        }
        $playerGameAccount->is_need_repair = $playerGameAccount->is_need_repair ? 0 :1 ;
        $playerGameAccount->save();

        return $this->returnApiJson("操作成功!", 1);
    }
}
