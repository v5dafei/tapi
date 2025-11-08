<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Player;
use App\Lib\Cache\PlayerCache;
use App\Models\Conf\PlayerSetting;
use App\Models\CarrierPreFixDomain;
use App\Models\PlayerCommission;
use App\Models\CarrierGuaranteed;

class ReturnCommissionCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'returncommiss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'returncommiss';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $returncommissKey ='returncommiss_'.date('Ymd');
        cache()->put($returncommissKey,1);

        $carriers       = Carrier::where('is_forbidden',0)->orderBy('id','asc')->get();
        $day      = date('Ymd',time()-86400);
        
        foreach ($carriers as $key => $value) {

            //默认代理
            $defaultUserName            = CarrierCache::getCarrierConfigure($value->id,'default_user_name');
            
            $carrierPreFixDomains       = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $defaultPlayerId            = PlayerCache::getPlayerId($value->id,$defaultUserName,$value1->prefix);
                $enableAutoGuaranteedUpgrade = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_auto_guaranteed_upgrade',$value1->prefix);
                $carrierGuaranteeds          = CarrierGuaranteed::where('carrier_id',$value->id)->where('prefix',$value1->prefix)->orderBy('sort','desc')->get();

                $playerDividendsMethod       = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_method',$value1->prefix);
                $enableTongbaoMethod         = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_tongbao_method',$value1->prefix);
                $playerIds                   = PlayerBetFlowMiddle::where('carrier_id',$value->id)->where('day',$day)->where('whether_recharge',1)->where('prefix',$value1->prefix)->groupBy('rid')->pluck('rid')->toArray();

                $allPlayers                  = [];
                $defaultPlayerArr            = [];

                foreach ($playerIds as $k => $v) {
                    $playerIdArr = explode('|',$v);
                    foreach ($playerIdArr as $k1 => $v1) {
                        $allPlayers[] = $v1;
                    }
                    $allPlayers = array_unique($allPlayers);
                }

                $intersectPlayerIds   = $allPlayers;
                $defaultPlayerArr[]   = $defaultPlayerId;
                $allPlayers           = array_diff($allPlayers, $defaultPlayerArr);

                if(!count($allPlayers)){
                    continue;
                }

                $maxLevel             = Player::where('carrier_id',$value->id)->whereIn('player_id',$allPlayers)->max('level');
                $minLevel             = Player::where('carrier_id',$value->id)->whereIn('player_id',$allPlayers)->min('level');
                $level                = $minLevel;
                $data                 = [];

                do{
                    $cyclePlayers = Player::where('level',$level)->whereIn('player_id',$allPlayers)->orderby('player_id','asc')->get();
                    foreach ($cyclePlayers as $k => $v) {
                        $row                     = [];
                        $row['carrier_id']       = $v->carrier_id;
                        $row['rid']              = $v->rid;
                        $row['top_id']           = $v->top_id;
                        $row['prefix']           = $v->prefix;
                        $row['parent_id']        = $v->parent_id;
                        $row['player_id']        = $v->player_id;
                        $row['is_tester']        = $v->is_tester;
                        $row['user_name']        = $v->user_name;
                        $row['level']            = $v->level;
                        $selfBetFlows            = null;

                        //查询自已的参数
                        $selfPlayerSetting                          = PlayerCache::getPlayerSetting($v->player_id);

                        //直属投注
                        $directlyUnderCategroies = [];

                        if($playerDividendsMethod==5){
                            $directlyUnderIds             = [];
                            $subordinateDirectlyUnderRids = PlayerSetting::where('parent_id',$v->player_id)->where('guaranteed',0)->pluck('player_id')->toArray();
                            $allSubordinate               = PlayerSetting::where('rid','like',$v->rid.'|%')->pluck('rid')->toArray();

                            foreach ($subordinateDirectlyUnderRids as $key4 => $value4) {
                                foreach ($allSubordinate as $key5 => $value5) {
                                    if(strpos($value5,strval($value4))!==false){
                                        $arr = explode('|',$value5);
                                        $directlyUnderIds[] = intval(end($arr));
                                    }
                                }
                            }

                            $directlyUnderBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('day',$day)->whereIn('player_id',$directlyUnderIds)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();

                            if($selfPlayerSetting->guaranteed > 0){
                                $selfBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('day',$day)->where('player_id',$v->player_id)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();
                            }
                        }else{
                            $directlyUnderBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('day',$day)->where('parent_id',$v->player_id)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();
                        }

                        //启用了自动升级
                        if($enableAutoGuaranteedUpgrade == 1 && $selfPlayerSetting->guaranteed==0){
                            $upgradeBetFlows   = PlayerBetFlowMiddle::where('day',$day)->where('rid','like',$v->rid.'|%')->where('whether_recharge',1)->sum('agent_process_available_bet_amount');
                            if(is_null($upgradeBetFlows)){
                                $upgradeBetFlows = 0;
                            }
                            foreach ($carrierGuaranteeds as $k1 => $v1) {
                                if($upgradeBetFlows >= $v1->performance){
                                    $selfPlayerSetting->guaranteed = $v1->quota;
                                }
                            }
                        }

                        //查询所有直属的保底
                        $directlyUnderPlayerSettingArr = [];
                        $directlyUnderPlayerSetting    = PlayerSetting::where('parent_id',$v->player_id)->get();
                        foreach ($directlyUnderPlayerSetting as $k1 => $v1) {
                            if($enableAutoGuaranteedUpgrade == 1 && $v1->guaranteed==0){
                                $upgradeBetFlows   = PlayerBetFlowMiddle::where('day',$day)->where('rid','like',$v1->rid.'|%')->where('whether_recharge',1)->sum('agent_process_available_bet_amount');
                                if(is_null($upgradeBetFlows)){
                                    $upgradeBetFlows = 0;
                                }
                                foreach ($carrierGuaranteeds as $k2 => $v2) {
                                    if($upgradeBetFlows >= $v2->performance){
                                        $directlyUnderPlayerSettingArr[$v1->player_id] = $v2->quota;
                                    }
                                }
                            } else{
                                $directlyUnderPlayerSettingArr[$v1->player_id] = $v1->guaranteed;
                            }
                        }

                        $row['directlyunder_casino_commission']     = 0;
                        $row['directlyunder_electronic_commission'] = 0;
                        $row['directlyunder_esport_commission']     = 0;
                        $row['directlyunder_fish_commission']       = 0;
                        $row['directlyunder_card_commission']       = 0;
                        $row['directlyunder_sport_commission']      = 0;
                        $row['directlyunder_lottery_commission']    = 0;
                        $row['team_casino_commission']              = 0;
                        $row['team_electronic_commission']          = 0;
                        $row['team_esport_commission']              = 0;
                        $row['team_fish_commission']                = 0;
                        $row['team_card_commission']                = 0;
                        $row['team_sport_commission']               = 0;
                        $row['team_lottery_commission']             = 0;
                        $row['self_casino_commission']              = 0;
                        $row['self_electronic_commission']          = 0;
                        $row['self_esport_commission']              = 0;
                        $row['self_fish_commission']                = 0;
                        $row['self_card_commission']                = 0;
                        $row['self_sport_commission']               = 0;
                        $row['self_lottery_commission']             = 0;

                        $row['directlyunder_casino_performance']     = 0;
                        $row['directlyunder_electronic_performance'] = 0;
                        $row['directlyunder_esport_performance']     = 0;
                        $row['directlyunder_fish_performance']       = 0;
                        $row['directlyunder_card_performance']       = 0;
                        $row['directlyunder_sport_performance']      = 0;
                        $row['directlyunder_lottery_performance']    = 0;
                        $row['team_casino_performance']              = 0;
                        $row['team_electronic_performance']          = 0;
                        $row['team_esport_performance']              = 0;
                        $row['team_fish_performance']                = 0;
                        $row['team_card_performance']                = 0;
                        $row['team_sport_performance']               = 0;
                        $row['team_lottery_performance']             = 0;
                        $row['self_casino_performance']              = 0;
                        $row['self_electronic_performance']          = 0;
                        $row['self_esport_performance']              = 0;
                        $row['self_fish_performance']                = 0;
                        $row['self_card_performance']                = 0;
                        $row['self_sport_performance']               = 0;
                        $row['self_lottery_performance']             = 0;

                        if(!is_null($selfBetFlows)){
                            foreach ($selfBetFlows as $k1 => $v1) {
                                switch ($v1->game_category) {
                                    case '1':
                                        $row['self_casino_commission']     += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                        $row['self_casino_performance']    += $v1->available_bet_amount;
                                        break;
                                    case '2':
                                        $row['self_electronic_commission'] += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                        $row['self_electronic_performance']+= $v1->available_bet_amount;
                                        break;
                                    case '3':
                                        $row['self_esport_commission']     += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                        $row['self_esport_performance']    += $v1->available_bet_amount;
                                        break;
                                    case '4':
                                        $row['self_card_commission']       += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                        $row['self_card_performance']      += $v1->available_bet_amount;
                                        break;
                                    case '5':
                                        $row['self_sport_commission']      += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                        $row['self_sport_performance']     += $v1->available_bet_amount;
                                        break;
                                    case '6':
                                        $row['self_lottery_commission']    += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                        $row['self_lottery_performance']   += $v1->available_bet_amount;
                                        break;
                                    case '7':
                                        $row['self_fish_commission']       += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                        $row['self_fish_performance']      += $v1->available_bet_amount;
                                        break;
                                    
                                    default:
                                        break;
                                }
                            }
                        }

                        foreach ($directlyUnderBetFlows as $k1 => $v1) {
                            switch ($v1->game_category) {
                                case '1':
                                    $row['directlyunder_casino_commission']     += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                    $row['directlyunder_casino_performance']    += $v1->available_bet_amount;
                                    break;
                                case '2':
                                    $row['directlyunder_electronic_commission'] += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                    $row['directlyunder_electronic_performance']+= $v1->available_bet_amount;
                                    break;
                                case '3':
                                    $row['directlyunder_esport_commission']     += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                    $row['directlyunder_esport_performance']    += $v1->available_bet_amount;
                                    break;
                                case '4':
                                    $row['directlyunder_card_commission']       += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                    $row['directlyunder_card_performance']      += $v1->available_bet_amount;
                                    break;
                                case '5':
                                    $row['directlyunder_sport_commission']      += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                    $row['directlyunder_sport_performance']     += $v1->available_bet_amount;
                                    break;
                                case '6':
                                    $row['directlyunder_lottery_commission']    += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                    $row['directlyunder_lottery_performance']   += $v1->available_bet_amount;
                                    break;
                                case '7':
                                    $row['directlyunder_fish_commission']       += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                                    $row['directlyunder_fish_performance']      += $v1->available_bet_amount;
                                    break;
                                
                                default:
                                    break;
                            }
                        }

                        //查询所有的直属下级
                        $directlyunders = PlayerSetting::where('parent_id',$v->player_id)->get();
                        foreach ($directlyunders as $k2 => $v2) {
                            if($playerDividendsMethod==5){
                                if($v2->guaranteed>0){
                                    $teamBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('day',$day)->where('whether_recharge',1)->where('rid','like',$v2->rid.'%')->groupBy('player_id','main_game_plat_id','game_category')->get();
                                    foreach ($teamBetFlows as $k1 => $v1) {
                                        switch ($v1->game_category) {
                                            case '1':
                                                $row['team_casino_commission']     += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                                $row['team_casino_performance']    += $v1->available_bet_amount;
                                                break;
                                            case '2':
                                                $row['team_electronic_commission'] += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                                $row['team_electronic_performance']+= $v1->available_bet_amount;
                                                break;
                                            case '3':
                                                $row['team_esport_commission']     += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                                $row['team_esport_performance']    += $v1->available_bet_amount;
                                                break;
                                            case '4':
                                                $row['team_card_commission']       += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                                $row['team_card_performance']      += $v1->available_bet_amount;
                                                break;
                                            case '5':
                                                $row['team_sport_commission']      += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                                $row['team_sport_performance']     += $v1->available_bet_amount;
                                                break;
                                            case '6':
                                                $row['team_lottery_commission']    += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                                $row['team_lottery_performance']   += $v1->available_bet_amount;
                                                break;
                                            case '7':
                                                $row['team_fish_commission']       += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                                $row['team_fish_performance']      += $v1->available_bet_amount;
                                                break;
                                            
                                            default:
                                                break;
                                        }
                                    }
                                }
                            }else{
                                $teamBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('day',$day)->where('whether_recharge',1)->where('rid','like',$v2->rid.'|%')->groupBy('player_id','main_game_plat_id','game_category')->get();

                                foreach ($teamBetFlows as $k1 => $v1) {
                                    switch ($v1->game_category) {
                                        case '1':
                                            $row['team_casino_commission']     += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                            $row['team_casino_performance']    += $v1->available_bet_amount;
                                            break;
                                        case '2':
                                            $row['team_electronic_commission'] += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                            $row['team_electronic_performance']+= $v1->available_bet_amount;
                                            break;
                                        case '3':
                                            $row['team_esport_commission']     += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                            $row['team_esport_performance']    += $v1->available_bet_amount;
                                            break;
                                        case '4':
                                            $row['team_card_commission']       += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                            $row['team_card_performance']      += $v1->available_bet_amount;
                                            break;
                                        case '5':
                                            $row['team_sport_commission']      += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                            $row['team_sport_performance']     += $v1->available_bet_amount;
                                            break;
                                        case '6':
                                            $row['team_lottery_commission']    += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                            $row['team_lottery_performance']   += $v1->available_bet_amount;
                                            break;
                                        case '7':
                                            $row['team_fish_commission']       += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v2->player_id]);
                                            $row['team_fish_performance']      += $v1->available_bet_amount;
                                            break;
                                        
                                        default:
                                            break;
                                    }
                                }
                            }
                        }

                        $row['init_time']  = time();
                        $row['day']        = date('Ymd',strtotime('-1 day'));     
                        $row['created_at'] = date('Y-m-d H:i:s');
                        $row['updated_at'] = date('Y-m-d H:i:s');

                        //计算对充佣金
                        $row['amount']     = $row['team_casino_commission']+$row['team_electronic_commission']+$row['team_esport_commission']+$row['team_card_commission']+$row['team_sport_commission']+$row['team_lottery_commission']+$row['team_fish_commission']+$row['directlyunder_casino_commission']+$row['directlyunder_electronic_commission']+$row['directlyunder_esport_commission']+$row['directlyunder_card_commission']+$row['directlyunder_sport_commission']+$row['directlyunder_lottery_commission']+$row['directlyunder_fish_commission'] + $row['self_casino_commission'] + $row['self_electronic_commission'] + $row['self_esport_commission'] + $row['self_fish_commission'] + $row['self_card_commission'] + $row['self_sport_commission'] + $row['self_lottery_commission'];

                        if($enableTongbaoMethod==0){
                            if($row['amount']>0){
                                $data[]            = $row;
                            }
                        } else{
                            $data[]            = $row;
                        }  

                        if(count($data)==100){
                            \DB::table('report_player_commission')->insert($data);
                            $data = [];
                        }
                        
                    }
                    
                    if(count($data)){
                        \DB::table('report_player_commission')->insert($data);
                        $data = [];
                    }
                    

                    $level ++;

                }while($level <= $maxLevel);
            }
        }

        //开始计算通宝模式的佣金
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $enableTongbaoMethod = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_tongbao_method',$value1->prefix);
                if($enableTongbaoMethod ==1){
                    //启用保底通宝模式
                    $tongbaoRate = CarrierCache::getCarrierMultipleConfigure($value->id,'tongbao_rate',$value1->prefix);
                    $tongbaoRate = bcdiv($tongbaoRate,100,2);

                    $maxLevel    = PlayerCommission::where('prefix',$value1->prefix)->where('day',date('Ymd',strtotime('-1 day')))->max('level');
                    $minLevel    = PlayerCommission::where('prefix',$value1->prefix)->where('day',date('Ymd',strtotime('-1 day')))->min('level');
                    if(!is_null($minLevel)){
                        $level       = $minLevel;
                        do{
                            $playerCommissions = PlayerCommission::where('prefix',$value1->prefix)->where('level',$level)->where('day',date('Ymd',strtotime('-1 day')))->orderby('player_id','asc')->get();
                            foreach ($playerCommissions as $k => $v) {
                                $totalTongbaoCommission          = 0;
                                $subordinateCommissionStats = PlayerCommission::where('prefix',$v->prefix)->where('day',date('Ymd',strtotime('-1 day')))->where('amount','>',0)->where('rid','like',$v->rid.'|%')->get();
                                $insertData                       = [];
                                foreach ($subordinateCommissionStats as $k1 => $v1) {
                                    $partRid            = str_replace($v->rid.'|','',$v1->rid);
                                    $partRidArr         = explode('|',$partRid);
                                    $number             = count($partRidArr);
                                    $tongbaoRealRate    = pow($tongbaoRate, $number);
                                    $tongbaoCommission  = $tongbaoRealRate*$v1->amount;

                                    $row                                            = [];
                                    $row['carrier_id']                              = $v1->carrier_id;
                                    $row['prefix']                                  = $v1->prefix;
                                    $row['player_id']                               = $v1->player_id;
                                    $row['rid']                                     = $v1->rid;
                                    $row['parent_id']                               = $v1->parent_id;
                                    $row['performance']                             = $v1->amount;
                                    $row['scale']                                   = $tongbaoRealRate;
                                    $row['receive_player_id']                       = $v->player_id;
                                    $row['amount']                                  = $tongbaoCommission;
                                    $row['day']                                     = $v->day;
                                    $row['created_at']                              = date('Y-m-d H:i:s');
                                    $row['updated_at']                              = date('Y-m-d H:i:s');
                                    $insertData[]                                   = $row;

                                    $totalTongbaoCommission                         += $tongbaoCommission;
                                }

                                $v->tongbao_commission = $totalTongbaoCommission;
                                $v->amount             =  $v->amount + $v->tongbao_commission;
                                $v->save();

                                \DB::table('log_player_commission_tongbao')->insert($insertData);
                            }

                            $level ++;
                        }while($level <= $maxLevel);
                    }
                }
            }
        }

        cache()->forget($returncommissKey);
    }
}