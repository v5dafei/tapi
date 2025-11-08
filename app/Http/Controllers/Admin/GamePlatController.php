<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseController;
use Illuminate\Auth\Authenticatable;
use App\Models\Def\MainGamePlat;
use App\Models\Map\CarrierGamePlat;
use App\Models\Carrier;


class GamePlatController extends BaseController
{
    use Authenticatable;

    public function gamePlatList() 
    {
        $data       = MainGamePlat::orderBy('main_game_plat_id','asc')->get();

        return returnApiJson('操作成功', 1, $data);
    }

    public function gamePlatChangeStatus($gamePlatId)
    {
        $gamePlat    = MainGamePlat::find($gamePlatId);
        if(!$gamePlat) {
            return returnApiJson("对不起, 此游戏平台不存在!", 0);
        }

        $gamePlat->gamePlatChangeStatus();

        return returnApiJson('操作成功', 1);
    }

    public function gameplatDel($gamePlatId = 0)
    {
        $gamePlat    = MainGamePlat::find($gamePlatId);
        if(!$gamePlat) {
            return returnApiJson("对不起, 此游戏平台不存在!", 0);
        }

        $res = $gamePlat->gameplatDel();
        if($res ===true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function gameplatAdd($gamePlatId = 0)
    {
        if($gamePlatId) {
            $gamePlat    = MainGamePlat::find($gamePlatId);
            if(!$gamePlat) {
                 return returnApiJson("对不起, 此游戏平台不存在!", 0);
            }
        } else {
            $gamePlat = new MainGamePlat();
        }

        $res = $gamePlat->saveItem();
        if($res === true) {
            return returnApiJson('操作成功', 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function gameplatScaleList($carrierId=0)
    {
        $carrierGamePlat = CarrierGamePlat::select('map_carrier_game_plats.*','def_main_game_plats.alias')->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','map_carrier_game_plats.game_plat_id')->where('map_carrier_game_plats.carrier_id',$carrierId)->get();
        return returnApiJson('操作成功', 1,$carrierGamePlat);
    }

    public function updateScale($mapCarrierGamePlatid=0)
    {
        $input           =  request()->all();
        $carrierGamePlat = CarrierGamePlat::where('id',$mapCarrierGamePlatid)->first();
        if(!$carrierGamePlat){
            return returnApiJson('对不起，此条数据不存在', 0);
        }

        if(!isset($input['point']) || !is_numeric($input['point']) || $input['point']<=0 || $input['point']>20){
            return returnApiJson('对不起，游戏点位取值不正确', 0);
        }
        $carrierGamePlat->point = $input['point'];
        $carrierGamePlat->save();
        return returnApiJson('操作成功', 1);
    }
}
