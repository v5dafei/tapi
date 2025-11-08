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
use App\Models\PlayerRealCommission;
use App\Models\Log\PlayerCommissionTongbao;
use App\Models\Log\PlayerRealCommissionTongbao;
use App\Lib\Cache\Lock;
use App\Lib\Clog;

class RealReturnCommissionCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'realreturncommiss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'realreturncommiss';

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
        if(date('i')!='40'){
            return;
        }

        $cacheKey = "realreturncommiss";
        $redisLock = Lock::addLock($cacheKey,3600);
        $globePlayers = [];
        if (!$redisLock) {
            \Log::info('实时佣金加锁失败');
            Clog::realcommission('实时佣金加锁失败', ['失败时间' =>date('Y-m-d H:i:s')]);
        } else {
            try{
                \Log::info('实时佣金加锁成功');
                $carriers                   = Carrier::where('is_forbidden',0)->orderBy('id','asc')->get();
                $day                        = date('Ymd');
                
                foreach ($carriers as $key => $value) {
                    $carrierPreFixDomains     = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
                    foreach ($carrierPreFixDomains as $k => $v) {
                        $playerDividendsMethod      = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_method',$v->prefix);
                        $enableTongbaoMethod        = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_tongbao_method',$v->prefix);

                        //查询半个小时之内有投注记录的玩家
                        $playerIds                  = PlayerBetFlowMiddle::where('carrier_id',$value->id)->where('day',$day)->where('prefix',$v->prefix)->where('whether_recharge',1)->where('created_at','>=',date('Y-m-d H:i:s',time()-2100))->groupBy('rid')->pluck('rid')->toArray();

                        //默认代理
                        $defaultUserName            = CarrierCache::getCarrierConfigure($value->id,'default_user_name');
                        $defaultPlayerId            = PlayerCache::getPlayerId($value->id,$defaultUserName,$v->prefix);

                        $allPlayers                 = [];
                        $defaultPlayerArr           = [];

                        foreach ($playerIds as $k => $v) {
                            $playerIdArr = explode('|',$v);
                            foreach ($playerIdArr as $k1 => $v1) {
                                $allPlayers[] = $v1;
                            }
                        }

                        $allPlayers           = array_unique($allPlayers);
                        $intersectPlayerIds   = $allPlayers;
                        $defaultPlayerArr[]   = $defaultPlayerId;
                        $allPlayers           = array_diff($allPlayers, $defaultPlayerArr);

                        if(!count($allPlayers)){
                            continue;
                        }

                        $globePlayers         = array_merge($globePlayers,$allPlayers);
                        $maxLevel             = Player::where('carrier_id',$value->id)->whereIn('player_id',$allPlayers)->max('level');
                        $minLevel             = Player::where('carrier_id',$value->id)->whereIn('player_id',$allPlayers)->min('level');
                        $level                = $minLevel;
                        $data                 = [];

                        do{
                            $cyclePlayers = Player::where('level',$level)->whereIn('player_id',$allPlayers)->orderby('player_id','asc')->get();
                            foreach ($cyclePlayers as $k2 => $v2) {
                                $row                     = [];
                                $row['carrier_id']       = $v2->carrier_id;
                                $row['rid']              = $v2->rid;
                                $row['top_id']           = $v2->top_id;
                                $row['prefix']           = $v2->prefix;
                                $row['parent_id']        = $v2->parent_id;
                                $row['player_id']        = $v2->player_id;
                                $row['is_tester']        = $v2->is_tester;
                                $row['user_name']        = $v2->user_name;
                                $row['level']            = $v2->level;

                                //查询自已的参数
                                $selfPlayerSetting                          = PlayerCache::getPlayerSetting($v2->player_id);

                                //直属投注
                                $directlyUnderCategroies = [];

                                if($playerDividendsMethod==5){
                                    $subordinateDirectlyUnderRids = PlayerSetting::where('parent_id',$v2->player_id)->where('guaranteed',0)->pluck('player_id')->toArray();
                                    $allSubordinateIds            = Player::where('rid','like',$v2->rid.'|%')->pluck('rid')->toArray();
                                    $directlyUnderIds             = []; 

                                    foreach ($subordinateDirectlyUnderRids as $key2 => $value2) {
                                        foreach ($allSubordinateIds as $key1 => $value1) {
                                            if(strpos($value2,strval($value1))!== false){
                                                $playerIdsArr = explode('|',$value1);
                                                $directlyUnderIds[] = intval(end($playerIdsArr));
                                            }
                                        }
                                    }

                                    $directlyUnderBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('carrier_id',$value->id)->where('day',$day)->whereIn('player_id',$directlyUnderIds)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();

                                    if($selfPlayerSetting->guaranteed > 0){

                                        $selfBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('carrier_id',$value->id)->where('day',$day)->where('player_id',$v2->player_id)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();
                                    }
                                }else{
                                    $directlyUnderBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('carrier_id',$value->id)->where('day',$day)->where('parent_id',$v2->player_id)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();
                                }

                                //查询所有直属的保底
                                $directlyUnderPlayerSettingArr = [];
                                $directlyUnderPlayerSetting    = PlayerSetting::where('parent_id',$v2->player_id)->get();
                                foreach ($directlyUnderPlayerSetting as $k1 => $v1) {
                                    $directlyUnderPlayerSettingArr[$v1->player_id] = $v1->guaranteed;
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

                                if(isset($selfBetFlows)){
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
                                $directlyunders = PlayerSetting::where('parent_id',$v2->player_id)->get();
                                foreach ($directlyunders as $k3 => $v3) {
                                    if($playerDividendsMethod==5){
                                        if($v3->guaranteed>0){
                                            $teamBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('carrier_id',$value->id)->where('day',$day)->where('whether_recharge',1)->where('rid','like',$v3->rid.'%')->groupBy('player_id','main_game_plat_id','game_category')->get();
                                            foreach ($teamBetFlows as $k4 => $v4) {
                                                switch ($v4->game_category) {
                                                    case '1':
                                                        $row['team_casino_commission']     += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                        $row['team_casino_performance']    += $v4->available_bet_amount;
                                                        break;
                                                    case '2':
                                                        $row['team_electronic_commission'] += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                        $row['team_electronic_performance']+= $v4->available_bet_amount;
                                                        break;
                                                    case '3':
                                                        $row['team_esport_commission']     += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                        $row['team_esport_performance']    += $v4->available_bet_amount;
                                                        break;
                                                    case '4':
                                                        $row['team_card_commission']       += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                        $row['team_card_performance']      += $v4->available_bet_amount;
                                                        break;
                                                    case '5':
                                                        $row['team_sport_commission']      += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                        $row['team_sport_performance']     += $v4->available_bet_amount;
                                                        break;
                                                    case '6':
                                                        $row['team_lottery_commission']    += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                        $row['team_lottery_performance']   += $v4->available_bet_amount;
                                                        break;
                                                    case '7':
                                                        $row['team_fish_commission']       += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                        $row['team_fish_performance']      += $v4->available_bet_amount;
                                                        break;
                                                    
                                                    default:
                                                        break;
                                                }
                                            }
                                        }
                                    }else{
                                        $teamBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('carrier_id',$value->id)->where('day',$day)->where('whether_recharge',1)->where('rid','like',$v3->rid.'|%')->groupBy('player_id','main_game_plat_id','game_category')->get();

                                        foreach ($teamBetFlows as $k4 => $v4) {
                                            switch ($v4->game_category) {
                                                case '1':
                                                    $row['team_casino_commission']     += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                    $row['team_casino_performance']    += $v4->available_bet_amount;
                                                    break;
                                                case '2':
                                                    $row['team_electronic_commission'] += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                    $row['team_electronic_performance']+= $v4->available_bet_amount;
                                                    break;
                                                case '3':
                                                    $row['team_esport_commission']     += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                    $row['team_esport_performance']    += $v4->available_bet_amount;
                                                    break;
                                                case '4':
                                                    $row['team_card_commission']       += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                    $row['team_card_performance']      += $v4->available_bet_amount;
                                                    break;
                                                case '5':
                                                    $row['team_sport_commission']      += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                    $row['team_sport_performance']     += $v4->available_bet_amount;
                                                    break;
                                                case '6':
                                                    $row['team_lottery_commission']    += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                    $row['team_lottery_performance']   += $v4->available_bet_amount;
                                                    break;
                                                case '7':
                                                    $row['team_fish_commission']       += $v4->available_bet_amount*($selfPlayerSetting->guaranteed - $directlyUnderPlayerSettingArr[$v3->player_id]);
                                                    $row['team_fish_performance']      += $v4->available_bet_amount;
                                                    break;
                                                
                                                default:
                                                    break;
                                            }
                                        }
                                    }
                                }

                                $row['init_time']  = time();
                                $row['day']        = date('Ymd');     
                                $row['created_at'] = date('Y-m-d H:i:s');
                                $row['updated_at'] = date('Y-m-d H:i:s');

                                //计算对充佣金
                                $row['amount']     = $row['team_casino_commission']+$row['team_electronic_commission']+$row['team_esport_commission']+$row['team_card_commission']+$row['team_sport_commission']+$row['team_lottery_commission']+$row['team_fish_commission']+$row['directlyunder_casino_commission']+$row['directlyunder_electronic_commission']+$row['directlyunder_esport_commission']+$row['directlyunder_card_commission']+$row['directlyunder_sport_commission']+$row['directlyunder_lottery_commission']+$row['directlyunder_fish_commission'] + $row['self_casino_commission'] + $row['self_electronic_commission'] + $row['self_esport_commission'] + $row['self_fish_commission'] + $row['self_card_commission'] + $row['self_sport_commission'] + $row['self_lottery_commission'];

                                    $existPlayerRealCommission = PlayerRealCommission::where('player_id',$row['player_id'])->where('day',$day)->first();
                                    if(!$existPlayerRealCommission){
                                        $existPlayerRealCommission = new PlayerRealCommission();
                                    }

                                    $existPlayerRealCommission->carrier_id                                = $row['carrier_id'];
                                    $existPlayerRealCommission->rid                                       = $row['rid'];
                                    $existPlayerRealCommission->top_id                                    = $row['top_id'];
                                    $existPlayerRealCommission->parent_id                                 = $row['parent_id'];
                                    $existPlayerRealCommission->player_id                                 = $row['player_id'];
                                    $existPlayerRealCommission->is_tester                                 = $row['is_tester'];
                                    $existPlayerRealCommission->user_name                                 = $row['user_name'];
                                    $existPlayerRealCommission->level                                     = $row['level'];
                                    $existPlayerRealCommission->amount                                    = $row['amount'];
                                    $existPlayerRealCommission->team_casino_commission                    = $row['team_casino_commission'];
                                    $existPlayerRealCommission->team_electronic_commission                = $row['team_electronic_commission'];
                                    $existPlayerRealCommission->team_esport_commission                    = $row['team_esport_commission'];
                                    $existPlayerRealCommission->team_fish_commission                      = $row['team_fish_commission'];
                                    $existPlayerRealCommission->team_card_commission                      = $row['team_card_commission'];
                                    $existPlayerRealCommission->team_sport_commission                     = $row['team_sport_commission'];
                                    $existPlayerRealCommission->team_lottery_commission                   = $row['team_lottery_commission'];
                                    $existPlayerRealCommission->directlyunder_casino_commission           = $row['directlyunder_casino_commission'];
                                    $existPlayerRealCommission->directlyunder_electronic_commission       = $row['directlyunder_electronic_commission'];
                                    $existPlayerRealCommission->directlyunder_esport_commission           = $row['directlyunder_esport_commission'];
                                    $existPlayerRealCommission->directlyunder_fish_commission             = $row['directlyunder_fish_commission'];
                                    $existPlayerRealCommission->directlyunder_card_commission             = $row['directlyunder_card_commission'];
                                    $existPlayerRealCommission->directlyunder_sport_commission            = $row['directlyunder_sport_commission'];
                                    $existPlayerRealCommission->directlyunder_lottery_commission          = $row['directlyunder_lottery_commission'];
                                    $existPlayerRealCommission->day                                       = $row['day'];
                                    $existPlayerRealCommission->prefix                                    = $row['prefix'];
                                    $existPlayerRealCommission->self_casino_commission                    = $row['self_casino_commission'];
                                    $existPlayerRealCommission->self_electronic_commission                = $row['self_electronic_commission'];
                                    $existPlayerRealCommission->self_esport_commission                    = $row['self_esport_commission'];
                                    $existPlayerRealCommission->self_fish_commission                      = $row['self_fish_commission'];
                                    $existPlayerRealCommission->self_card_commission                      = $row['self_card_commission'];
                                    $existPlayerRealCommission->self_sport_commission                     = $row['self_sport_commission'];
                                    $existPlayerRealCommission->self_lottery_commission                   = $row['self_lottery_commission'];
                                    $existPlayerRealCommission->team_casino_performance                   = $row['team_casino_performance'];
                                    $existPlayerRealCommission->team_electronic_performance               = $row['team_electronic_performance'];
                                    $existPlayerRealCommission->team_esport_performance                   = $row['team_esport_performance'];
                                    $existPlayerRealCommission->team_fish_performance                     = $row['team_fish_performance'];
                                    $existPlayerRealCommission->team_card_performance                     = $row['team_card_performance'];
                                    $existPlayerRealCommission->team_sport_performance                    = $row['team_sport_performance'];
                                    $existPlayerRealCommission->team_lottery_performance                  = $row['team_lottery_performance'];
                                    $existPlayerRealCommission->directlyunder_casino_performance          = $row['directlyunder_casino_performance'];
                                    $existPlayerRealCommission->directlyunder_electronic_performance      = $row['directlyunder_electronic_performance'];
                                    $existPlayerRealCommission->directlyunder_esport_performance          = $row['directlyunder_esport_performance'];
                                    $existPlayerRealCommission->directlyunder_fish_performance            = $row['directlyunder_fish_performance'];
                                    $existPlayerRealCommission->directlyunder_card_performance            = $row['directlyunder_card_performance'];
                                    $existPlayerRealCommission->directlyunder_sport_performance           = $row['directlyunder_sport_performance'];
                                    $existPlayerRealCommission->directlyunder_lottery_performance         = $row['directlyunder_lottery_performance'];
                                    $existPlayerRealCommission->self_casino_performance                   = $row['self_casino_performance'];
                                    $existPlayerRealCommission->self_electronic_performance               = $row['self_electronic_performance'];
                                    $existPlayerRealCommission->self_esport_performance                   = $row['self_esport_performance'];
                                    $existPlayerRealCommission->self_fish_performance                     = $row['self_fish_performance'];
                                    $existPlayerRealCommission->self_card_performance                     = $row['self_card_performance'];
                                    $existPlayerRealCommission->self_sport_performance                    = $row['self_sport_performance'];
                                    $existPlayerRealCommission->self_lottery_performance                  = $row['self_lottery_performance'];

                                    if($enableTongbaoMethod==0){
                                        if($row['amount']>0){
                                            $existPlayerRealCommission->save();
                                        }
                                    } else{
                                        $existPlayerRealCommission->save();
                                    }  
                            }

                            $level ++;

                        }while($level <= $maxLevel);
                    }
                }
                \Log::info('实时佣金分红计算完成'.date('Y-m-d H:i:s'));

                //开始计算通宝模式的佣金
                foreach ($carriers as $key => $value) {
                    $carrierPreFixDomains = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
                    foreach ($carrierPreFixDomains as $key1 => $value1) {
                        $enableTongbaoMethod = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_tongbao_method',$value1->prefix);
                        if($enableTongbaoMethod ==1){
                            //启用保底通宝模式
                            $tongbaoRate = CarrierCache::getCarrierMultipleConfigure($value->id,'tongbao_rate',$value1->prefix);
                            $tongbaoRate = bcdiv($tongbaoRate,100,2);

                            $maxLevel    = PlayerRealCommission::where('carrier_id',$value->id)->where('day',$day)->where('prefix',$value1->prefix)->max('level');
                            $minLevel    = PlayerRealCommission::where('carrier_id',$value->id)->where('day',$day)->where('prefix',$value1->prefix)->min('level');
                            if(!is_null($minLevel)){
                                $level       = $minLevel;
                                do{
                                    $playerCommissions = PlayerRealCommission::where('carrier_id',$value->id)->where('day',$day)->where('prefix',$value1->prefix)->where('level',$level)->whereIn('player_id',$globePlayers)->orderby('player_id','asc')->get();
                                    foreach ($playerCommissions as $k => $v) {
                                        $totalTongbaoCommission          = 0;
                                        $subordinateCommissionStats = PlayerRealCommission::where('day',$day)->where('amount','>',0)->where('rid','like',$v->rid.'|%')->get();
                                        
                                        foreach ($subordinateCommissionStats as $k1 => $v1) {
                                            $partRid            = str_replace($v->rid.'|','',$v1->rid);
                                            $partRidArr         = explode('|',$partRid);
                                            $number             = count($partRidArr);
                                            $tongbaoRealRate    = pow($tongbaoRate, $number);
                                            $tongbaoCommission  = $tongbaoRealRate*$v1->amount;
                                            $totalTongbaoCommission  += $tongbaoCommission;

                                            $existPlayerCommissionTongbao = PlayerRealCommissionTongbao::where('player_id',$v1->player_id)->where('receive_player_id',$v->player_id)->where('day',$v->day)->first();
                                            if(!$existPlayerCommissionTongbao){
                                                $existPlayerCommissionTongbao = new PlayerRealCommissionTongbao();
                                            }

                                            $existPlayerCommissionTongbao->carrier_id           = $v1->carrier_id;
                                            $existPlayerCommissionTongbao->prefix               = $v1->prefix;
                                            $existPlayerCommissionTongbao->player_id            = $v1->player_id;
                                            $existPlayerCommissionTongbao->rid                  = $v1->rid;
                                            $existPlayerCommissionTongbao->parent_id            = $v1->parent_id;
                                            $existPlayerCommissionTongbao->performance          = $v1->amount;
                                            $existPlayerCommissionTongbao->scale                = $tongbaoRealRate;
                                            $existPlayerCommissionTongbao->receive_player_id    = $v->player_id;
                                            $existPlayerCommissionTongbao->amount               = $tongbaoCommission;
                                            $existPlayerCommissionTongbao->day                  = $v->day;
                                            $existPlayerCommissionTongbao->save();
                                            
                                        }

                                        $v->tongbao_commission = $totalTongbaoCommission;
                                        $v->amount             =  $v->amount + $v->tongbao_commission;
                                        $v->save();
                                    }

                                    $level ++;
                                }while($level <= $maxLevel);
                            }
                        }
                    }
                }

                Lock::release($redisLock);
                \Log::info('实时佣金分红解锁成功1'.date('Y-m-d H:i:s'));
            } catch(\Exception $e){
                Lock::release($redisLock);
                Clog::recordabnormal('实时佣金异常:'.$e->getMessage());
            }
        }
    }
}