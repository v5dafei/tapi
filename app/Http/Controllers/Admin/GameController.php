<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Admin\BaseController;
use App\Models\Conf\SpecialGameMatch;
use Illuminate\Auth\Authenticatable;
use App\Models\Def\MainGamePlat;
use App\Models\Def\Game;
use App\Models\Carrier;
use App\Jobs\SynGameJob;
use App\Jobs\ChangeLineJob;
use App\Game\Game as Games; 

class GameController extends BaseController
{
    use Authenticatable;

    public function gameList() 
    {
        $game = Game::getList();
        return  returnApiJson('操作成功', 1, $game);
    }

    public function gameChangeStatus($gameId)
    {
        $game    = Game::find($gameId);
        if(!$game) {
            return returnApiJson("对不起, 此游戏不存在!", 0);
        }

        $game->gameChangeStatus();

        return returnApiJson('操作成功', 1);
    }

    public function gameAdd($gameId = 0)
    {
        if($gameId) {
            $game    = Game::find($gameId);
            if(!$game) {
                return returnApiJson("对不起, 此游戏不存在!", 0);
            }
        } else {
            $game = new Game();
        }

        $res = $game->saveItem();
        if($res === true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function gameCarriers($gameId = 0)
    {
        $game    = Game::find($gameId);
        if(!$game) {
            return returnApiJson("对不起, 此游戏不存在!", 0);
        }
        $data = $game->gameCarriers();
        return returnApiJson('操作成功',1, $data);
    }

    public function gameCarriersSave($gameId = 0)
    {
        $game    = Game::find($gameId);
        if(!$game) {
            return returnApiJson("对不起, 此游戏不存在!", 0);
        }
        $res = $game->gameCarriersSave();

        if($res === true) {
            return returnApiJson('操作成功',1);
        } else {
            return returnApiJson($res,0);
        }
    }

    public function syncGame()
    {
        dispatch(new SynGameJob());

        return returnApiJson("同步请求已发出!", 1);       
    }

    public function changeLine()
    {
        $input = request()->all();
        if(!isset($input['main_game_plat_id']) || empty($input['main_game_plat_id'])){
            return returnApiJson("对不起，游戏ID参数不正确!", 1);
        }

        $mainGamePlat = MainGamePlat::where('main_game_plat_id',$input['main_game_plat_id'])->first();
        if(!$mainGamePlat){
            return returnApiJson("对不起，此游戏平台不存在!", 1);
        }

        dispatch(new ChangeLineJob($input['main_game_plat_id']));

        return returnApiJson("换线请求已发出!", 1);  
    }
}
