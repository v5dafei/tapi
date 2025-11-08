<?php

namespace App\Http\Controllers\Carrier;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Carrier\BaseController;
use App\Models\CarrierPlayerGrade;
use App\Models\PlayerLevel;
use App\Models\Player;
use App\Models\CarrierPreFixDomain;


class PlayerLevelController extends BaseController
{
    use Authenticatable;

    // 登录
    public function playerLevelList()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $query           = CarrierPlayerGrade::orderBy('sort','asc');

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $total      = $query->count();
        $items      = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($items as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
            $upgrade_rule     = unserialize($v->upgrade_rule);
            $v->availablebet  = $upgrade_rule['availablebet'];
        }

        return returnApiJson('操作成功', 1, ['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function playerLevelAdd()
    {
        $input = request()->all();
        if(isset($input['id'])) {
            $carrierPlayerLevel = CarrierPlayerGrade::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
            if(!$carrierPlayerLevel) {
                return returnApiJson('对不起, 此会员等级不存在', 0);
            }
        } else {
            $carrierPlayerLevel = new CarrierPlayerGrade();
        }

        $res = $carrierPlayerLevel->playerLevelAdd($this->carrier);
        if($res === true)
        {
            return returnApiJson("操作成功", 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function playerLevelDel($playLevelId = 0)
    {
        $carrierPlayerLevel = CarrierPlayerGrade::where('carrier_id',$this->carrier->id)->where('id',$playLevelId)->first();
        if(!$carrierPlayerLevel) {
            return returnApiJson('对不起, 此会员等级不存在', 0);
        }

        $res = $carrierPlayerLevel->playerLevelDel();
        if($res === true) {
            return returnApiJson("操作成功", 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    // 登录
    public function playerGradeList()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $query           = PlayerLevel::orderBy('sort','asc')->orderBy('id','asc');

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('prefix',$input['prefix']);
        }

        $total      = $query->count();
        $items      = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ( $items as $k => &$row ) {
            # 用户数据
            $row->user_count    = Player::select('id')->where('player_group_id',$row->id)->count();
            $row->multiple_name = $carrierPreFixDomainArr[$row->prefix];
        }

        return returnApiJson('操作成功', 1, ['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function playerGradeAdd()
    {
        $input = request()->all();
        if(isset($input['id'])) {
            $carrierPlayerLevel = PlayerLevel::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
            if(!$carrierPlayerLevel) {
                return returnApiJson('对不起, 此会员层级不存在', 0);
            }
        } else {
            $carrierPlayerLevel = new PlayerLevel();
        }

        $res = $carrierPlayerLevel->playerGradeAdd($this->carrier);
        if($res === true)
        {
            return returnApiJson("操作成功", 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function playerGradeDel($id)
    {
        $playerLevel = PlayerLevel::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$playerLevel) {
            return returnApiJson('对不起, 此会员层级不存在', 0);
        }

        if($playerLevel->is_system==1){
            return returnApiJson('对不起, 系统层级不能删除', 0);
        }

        $res = $playerLevel->playerLevelDel();
        if($res === true) {
            return returnApiJson("操作成功", 1);
        } else {
            return returnApiJson($res, 0);
        }
    }

    public function playerLevelThirdpayList($playLevelId = 0)
    {
        $carrierPlayerLevel = PlayerLevel::where('carrier_id',$this->carrier->id)->where('id',$playLevelId)->first();
        if(!$carrierPlayerLevel) {
            return returnApiJson('对不起, 此会员层级不存在', 0 );
        }

        $res = $carrierPlayerLevel->playerLevelThirdpayList();
        if(is_array($res)){
            return returnApiJson("操作成功", 1, $res);
        } else{
            return returnApiJson($res, 0 );
        }
    }

    public function playerlevelThirdpayupdate($playLevelId = 0)
    {
        $carrierPlayerLevel = PlayerLevel::where('carrier_id',$this->carrier->id)->where('id',$playLevelId)->first();
        if(!$carrierPlayerLevel) {
           return returnApiJson('对不起, 此会员层级不存在', 0 );
        }

        $res = $carrierPlayerLevel->playerlevelThirdpayupdate();
        if($res === true) {
            return returnApiJson("操作成功", 1);
        } else{
            return returnApiJson($res, 0);
        }
    }

    public function playerLevelCarrierBankList($playLevelId = 0)
    {
        $carrierPlayerLevel = PlayerLevel::where('carrier_id',$this->carrier->id)->where('id',$playLevelId)->first();
        if(!$carrierPlayerLevel) {
           return returnApiJson('对不起, 此会员层级不存在', 0 );
        }

        $res = $carrierPlayerLevel->playerLevelCarrierBankList();
       
        return returnApiJson("操作成功", 1, $res);
    }

    public function playerlevelCarrierBankupdate($playLevelId = 0)
    {
        $carrierPlayerLevel = PlayerLevel::where('carrier_id',$this->carrier->id)->where('id',$playLevelId)->first();
        if(!$carrierPlayerLevel) {
           return returnApiJson('对不起, 此会员层级不存在', 0 );
        }

        $res = $carrierPlayerLevel->playerlevelCarrierBankupdate();
        if($res === true) {
            return returnApiJson("操作成功", 1);
        } else{
            return returnApiJson($res, 0);
        }
    }
}
