<?php

namespace App\Models;

use App\Utils\Arr\ArrHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Models\Map\CarrierPlayerLevelBankCard;
use App\Models\Map\CarrierPlayerLevelBankCardMap;
use App\Models\Conf\CarrierPayChannel;
use App\Models\CarrierBankCard;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Def\PayChannel;

class PlayerLevel extends Model
{
    public $table    = 'inf_carrier_player_level';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';

    public $fillable = [
       
    ];

    protected $casts = [
    ];

    public $rules = [
        
    ];

    public $messages = [
        
    ];

    public function playerLevelThirdpayList() 
    {
        $input                    = request()->all();
        if(!isset($input['prefix']) || empty($input['prefix'])){
            return '对不起，站点不能为空';
        }

        $data['selectChannleIds'] = CarrierPlayerLevelBankCardMap::where('player_level_id',$this->id)->pluck('carrier_channle_id')->toArray();
        $data['allChannles']      = CarrierPayChannel::select('inf_carrier_pay_channel.*','def_pay_factory_list.factory_name')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('inf_carrier_pay_channel.carrier_id',$this->carrier_id)
            ->where('inf_carrier_pay_channel.prefix',$input['prefix'])
            ->where('def_pay_channel_list.type','<>',2)
            ->where('inf_carrier_pay_channel.status',1)
            ->get();

        return $data;
    }

    public function playerlevelThirdpayupdate()
    {
        $carrierPayChannelId = request()->get('carrier_pay_channle_id',[]);

        if(!isset($carrierPayChannelId) || !is_array($carrierPayChannelId)) {
            return '对不起,参数不正确';
        }

        $prefix                    = request()->get('prefix');
        $payChannelIds             = PayChannel::where('type',1)->pluck('id')->toArray();
        $carrierThirdPartPayIds    = CarrierThirdPartPay::whereIn('def_pay_channel_id',$payChannelIds)->pluck('id')->toArray();
        $carrierPayChannelIds      = CarrierPayChannel::where('carrier_id',$this->carrier_id)->where('prefix',$prefix)->whereIn('binded_third_part_pay_id',$carrierThirdPartPayIds)->pluck('id')->toArray();


        $selectChannleIds          = CarrierPlayerLevelBankCardMap::where('player_level_id',$this->id)->whereIn('carrier_channle_id',$carrierPayChannelIds)->pluck('carrier_channle_id')->toArray();
        $addCarrierPayChannelId    = array_diff($carrierPayChannelId,$selectChannleIds);
        $removeCarrierPayChannelId = array_diff($selectChannleIds,$carrierPayChannelId);
        
        foreach ($addCarrierPayChannelId as  $value) {
             $carrierGamePlat                      = new CarrierPlayerLevelBankCardMap();
             $carrierGamePlat->carrier_channle_id  = $value;
             $carrierGamePlat->player_level_id     = $this->id;
             $carrierGamePlat->carrier_id          = $this->carrier_id;
             $carrierGamePlat->save();
        }

        CarrierPlayerLevelBankCardMap::where('carrier_id',$this->carrier_id)->whereIn('carrier_channle_id',$removeCarrierPayChannelId)->where('player_level_id',$this->id)->delete();
        
        return true;
    }

    public function playerLevelCarrierBankList() 
    {
        
        $data['selectChannleIds'] = CarrierPlayerLevelBankCard::leftJoin('inf_carrier_bankcard','inf_carrier_bankcard.bank_id','=','map_carrier_player_level_bank.carrier_bank_id')
            ->where('map_carrier_player_level_bank.player_level_id',$this->id)
            ->pluck('map_carrier_player_level_bank.carrier_bank_id')
            ->toArray();

        $data['allChannles']      = CarrierBankCard::select('inf_carrier_bankcard.*','inf_carrier_bank_type.bank_name')
            ->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_carrier_bankcard.bank_id')
            ->where('inf_carrier_bankcard.carrier_id',$this->carrier_id)
            ->where('inf_carrier_bankcard.status',1)
            ->get();

        return $data;
    }

    public function playerlevelCarrierBankupdate()
    {
        $carrierPayChannelId = request()->get('carrier_bankcard_id',[]);

        if(!isset($carrierPayChannelId) || !is_array($carrierPayChannelId)) {
            return '对不起,参数不正确';
        }

        $selectChannleIds          = CarrierPlayerLevelBankCard::where('player_level_id',$this->id)->pluck('carrier_bank_id')->toArray();
        $addCarrierPayChannelId    = array_diff($carrierPayChannelId,$selectChannleIds);
        $removeCarrierPayChannelId = array_diff($selectChannleIds,$carrierPayChannelId);
        
        foreach ($addCarrierPayChannelId as  $value) {
             $carrierGamePlat                      = new CarrierPlayerLevelBankCard();
             $carrierGamePlat->carrier_bank_id     = $value;
             $carrierGamePlat->player_level_id     = $this->id;
             $carrierGamePlat->carrier_id          = $this->carrier_id;
             $carrierGamePlat->save();
        }

        CarrierPlayerLevelBankCard::where('carrier_id',$this->carrier_id)->whereIn('carrier_bank_id',$removeCarrierPayChannelId)->where('player_level_id',$this->id)->delete();
        
        return true;
    }

    public function playerLevelDel()
    {   
        $player       = Player::where('carrier_id',$this->carrier_id)->where('player_group_id',$this->id)->first();
        $defaultLevel = self::where('carrier_id',$this->carrier_id)->where('prefix',$player->prefix)->where('is_default',1)->first();
        Player::where('carrier_id',$this->carrier_id)->where('player_group_id',$this->id)->update(['player_group_id'=>$defaultLevel->id]);

        PlayerCache::flushGameLineAllCode($this->id);
        CarrierPlayerLevelBankCard::where('carrier_id',$this->carrier_id)->where('player_level_id',$this->id)->delete();
        CarrierPlayerLevelBankCardMap::where('carrier_id',$this->carrier_id)->where('player_level_id',$this->id)->delete();

        $this->delete();
        return true;
    }

    public function playerGradeAdd($carrier)
    {
        $input     = request()->all();
        if(!array_key_exists('groupname', $input) || empty($input['groupname'])){
            return '对不起，层级名称不能为空';
        }

        if(!isset($input['rechargenumber']) || !is_numeric($input['rechargenumber']) || $input['rechargenumber']<0){
           
            return '对不起，充值次数不正确';
        }

        if(!isset($input['prefix'])){
            return '对不起，站点取值不正确';
        }

        if(!isset($input['single_maximum_recharge']) || !is_numeric($input['single_maximum_recharge']) || $input['single_maximum_recharge']<0){
            return '对不起，单次最大充值金额不正确';
        }

        if(!isset($input['accumulation_recharge']) || !is_numeric($input['accumulation_recharge']) || $input['accumulation_recharge']<0){
           return '对不起，累积充值金额不正确';
        }

        if($this->is_system == 1){

        } else {
            if(!$this->id){
                $this->is_system =2;
            }

            if($this->id){
                $sameSort  = self::where('carrier_id',$carrier->id)->where('is_system',2)->where('sort',$input['sort'])->first();
                if($sameSort && $sameSort->id != $this->id){
                    return '对不起，非系统分组不能有相同的排序字段';
                }
            } else {
                if($sameSort){
                    return '对不起，非系统分组不能有相同的排序字段';
                }
            }

            $pre  = self::where('carrier_id',$carrier->id)->where('is_system',2)->where('prefix',$this->prefix)->where('sort','<',$input['sort'])->orderBy('sort','desc')->first();
            $next = self::where('carrier_id',$carrier->id)->where('is_system',2)->where('prefix',$this->prefix)->where('sort','>',$input['sort'])->orderBy('sort','asc')->first();

            if($pre){
                if($input['rechargenumber'] < $pre->rechargenumber || $input['accumulation_recharge'] < $pre->accumulation_recharge  || $input['single_maximum_recharge'] < $pre->single_maximum_recharge){
                    return '对不起，充值次数，单次最高充值，累积充值金额，盈亏金额都必须大于等于下级';
                }
            }

            if($next){
                if($input['rechargenumber'] > $next->rechargenumber || $input['accumulation_recharge'] > $next->accumulation_recharge  || $input['single_maximum_recharge'] > $next->single_maximum_recharge){
                    return '对不起，充值次数，单次最高充值，累积充值金额，盈亏金额都必须小于等于上级';
                }
            }
        }

        $this->prefix                   = $input['prefix'];
        $this->rechargenumber           = $input['rechargenumber'];
        $this->accumulation_recharge    = $input['accumulation_recharge'];
        $this->single_maximum_recharge  = $input['single_maximum_recharge'];
        $this->groupname                = $input['groupname'];
        $this->remark                   = isset($input['remark']) && !empty($input['remark']) ? $input['remark']:'';
        $this->sort                     = (int)$input['sort'];
        $this->carrier_id               = $carrier->id;

        if(isset($input['game_line_id']) && !empty($input['game_line_id'])){
            $this->game_line_id               = $input['game_line_id'];
        } else{
            $this->game_line_id               = 0;
        }
        
        $this->save();

        PlayerCache::flushGameLineAllCode($this->id);
        CarrierCache::flushCarrierPlayerLevel($carrier->id);

        return true;
    }


    /**
     * 获取层级列表
     * @param $merId
     * @return array
     */
    public static function getKvList ($merId) {
        $res  = PlayerLevel::where('carrier_id',$merId)->orderBy('created_at','asc')->get()->toArray();

//        $res  = PlayerLevel::where('carrier_id',$merId)->get()->toArray();
        return !empty($res) ? ArrHelper::genIndexList($res, 'id') : [];
    }



    /**
     * 获取站点层级列表
     * @param $merId
     * @return array
     */
    public static function getPrefixKvList ($merId,$prefix) {
        $res  = PlayerLevel::where('carrier_id',$merId)->where('prefix',$prefix)->orderBy('created_at','asc')->get()->toArray();

        return !empty($res) ? ArrHelper::genIndexList($res, 'id') : [];
    }



    /**
     * 格式化列表
     */
    public static function formatAdminList ($list) {
        if ( !empty($list) ) {
            $list = $list->toArray();
        }

        $timeColumns = ['time'];
        $ipColumns   = ['ip'];

//        var_dump($list);die;

        foreach ( $list as $k => &$row ) {
            # 时间字段格式化
            foreach ( $timeColumns as $column ) {
                if ( isset($row[$column]) ) {
                    $row[$column] = !empty($row[$column]) ? date('Y-m-d H:i:s', $row[$column]) : '--';
                }
            }

            # 用户数据
            $row['user_count'] = Player::select('id')->where('player_group_id',$row['id'])->count();
        }

        return $list;
    }

}
