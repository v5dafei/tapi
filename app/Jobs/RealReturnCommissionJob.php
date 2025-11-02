<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\PlayerRealCommission;
use App\Models\Def\MainGamePlat;
use App\Models\PlayerGameAccount;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Game\Game;
use App\Models\Log\PlayerRealCommissionTongbao;
use App\Models\CarrierPreFixDomain;
use App\Models\Carrier;


class RealReturnCommissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $insertPlayerBetFlowMiddleArr      = null;

    public function __construct($insertPlayerBetFlowMiddleArr) {
        $this->insertPlayerBetFlowMiddleArr   = $insertPlayerBetFlowMiddleArr;
    }

    public function handle()
    {
        $this->calculateRealReturnCommission();
    }

    public function calculateRealReturnCommission()
    {
        $day                        = date('Ymd');

        foreach ($this->insertPlayerBetFlowMiddleArr as $key => $value) {
            if($value['whether_recharge']==1){
                $playerDividendsMethod      = CarrierCache::getCarrierMultipleConfigure($value['carrier_id'],'player_dividends_method',$value['prefix']);
                $enableTongbaoMethod        = CarrierCache::getCarrierMultipleConfigure($value['carrier_id'],'enable_tongbao_method',$value['prefix']);
                $playerSetting              = PlayerCache::getPlayerSetting($value['player_id']);

                //默认代理
                $defaultUserName            = CarrierCache::getCarrierConfigure($value['carrier_id'],'default_user_name');
                $defaultPlayerId            = PlayerCache::getPlayerId($value['carrier_id'],$defaultUserName,$value['prefix']);

                //查询实时保底记录
                $str                        = str_replace($defaultPlayerId.'|','',str_replace('|'.$value['player_id'],'',$value['rid']));
                $str                        = str_replace($defaultPlayerId,'',$str);
                
                if(empty($str)){
                    $playerIds                  = [];
                } else{
                    $playerIds                  = explode('|', $str);
                }

                $insertData                 = [];
                $guaranteedArr              = [];
                $diffGuaranteedArr          = [];
                $diffGuarantPre             = '';
                $reversePlayerIds           = array_reverse($playerIds);

                foreach ($reversePlayerIds as $k => $v) {
                    $currplayerSetting                           = PlayerCache::getPlayerSetting($v);
                    if($v==$value['parent_id']){
                        $diffGuaranteedArr[$v] = $currplayerSetting->guaranteed - $playerSetting->guaranteed;
                        $diffGuarantPre        = $currplayerSetting->guaranteed;
                    } else{
                        $diffGuaranteedArr[$v] = $currplayerSetting->guaranteed - $diffGuarantPre;
                    }
                }

                foreach ($playerIds as $k => $v) {
                        $existPlayerRealCommission = PlayerRealCommission::where('player_id',$v)->where('day',$day)->first();
                        $currplayerSetting                           = PlayerCache::getPlayerSetting($v);
                        $guaranteedArr[$v]                           = $currplayerSetting->guaranteed;

                        if(!$existPlayerRealCommission){
                            $row                                         = [];
                            $row['carrier_id']                           = $currplayerSetting->carrier_id;
                            $row['rid']                                  = $currplayerSetting->rid;
                            $row['top_id']                               = $currplayerSetting->top_id;
                            $row['prefix']                               = $currplayerSetting->prefix;
                            $row['parent_id']                            = $currplayerSetting->parent_id;
                            $row['player_id']                            = $currplayerSetting->player_id;
                            $row['is_tester']                            = $currplayerSetting->is_tester;
                            $row['user_name']                            = $currplayerSetting->user_name;
                            $row['level']                                = $currplayerSetting->level;
                            $row['amount']                               = 0;
                            $row['team_casino_commission']               = 0;
                            $row['team_electronic_commission']           = 0;
                            $row['team_esport_commission']               = 0;
                            $row['team_fish_commission']                 = 0;
                            $row['team_card_commission']                 = 0;
                            $row['team_sport_commission']                = 0;
                            $row['team_lottery_commission']              = 0;
                            $row['directlyunder_casino_commission']      = 0;
                            $row['directlyunder_electronic_commission']  = 0;
                            $row['directlyunder_esport_commission']      = 0;
                            $row['directlyunder_fish_commission']        = 0;
                            $row['directlyunder_card_commission']        = 0;
                            $row['directlyunder_sport_commission']       = 0;
                            $row['directlyunder_lottery_commission']     = 0;
                            $row['day']                                  = $day;
                            $row['self_casino_commission']               = 0;
                            $row['self_electronic_commission']           = 0;
                            $row['self_esport_commission']               = 0;
                            $row['self_fish_commission']                 = 0;
                            $row['self_card_commission']                 = 0;
                            $row['self_sport_commission']                = 0;
                            $row['self_lottery_commission']              = 0;
                            $row['team_casino_performance']              = 0;
                            $row['team_electronic_performance']          = 0;
                            $row['team_esport_performance']              = 0;
                            $row['team_fish_performance']                = 0;
                            $row['team_card_performance']                = 0;
                            $row['team_sport_performance']               = 0;
                            $row['team_lottery_performance']             = 0;
                            $row['directlyunder_casino_performance']     = 0;
                            $row['directlyunder_electronic_performance'] = 0;
                            $row['directlyunder_esport_performance']     = 0;
                            $row['directlyunder_fish_performance']       = 0;
                            $row['directlyunder_card_performance']       = 0;
                            $row['directlyunder_sport_performance']      = 0;
                            $row['directlyunder_lottery_performance']    = 0;
                            $row['self_casino_performance']              = 0;
                            $row['self_electronic_performance']          = 0;
                            $row['self_esport_performance']              = 0;
                            $row['self_fish_performance']                = 0;
                            $row['self_card_performance']                = 0;
                            $row['self_sport_performance']               = 0;
                            $row['self_lottery_performance']             = 0;
                            $row['tongbao_commission']                   = 0;
                            $row['created_at']                           = date('Y-m-d H:i:s');
                            $row['updated_at']                           = date('Y-m-d H:i:s');
                            $insertData[]                                = $row;
                        }
                }

                \DB::table('report_real_player_commission')->insert($insertData);

                if($playerDividendsMethod==5){
                        
                        if($playerSetting->guaranteed>0){
                            //自已投注保底算自已
                            $playerRealCommission                    = PlayerRealCommission::where('player_id',$v)->where('day',$day)->first();
                            if($value['game_category']==1){
                                $playerRealCommission->self_casino_performance = $playerRealCommission->self_casino_performance + $value['agent_process_available_bet_amount'];
                                $playerRealCommission->self_casino_commission  = $playerRealCommission->self_casino_commission + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                                $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                            } elseif($value['game_category']==2){
                                $playerRealCommission->self_electronic_performance = $playerRealCommission->self_electronic_performance + $value['agent_process_available_bet_amount'];
                                $playerRealCommission->self_electronic_commission  = $playerRealCommission->self_electronic_commission + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                                $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                            } elseif($value['game_category']==3){
                                $playerRealCommission->self_esport_performance = $playerRealCommission->self_esport_performance + $value['agent_process_available_bet_amount'];
                                $playerRealCommission->self_esport_commission  = $playerRealCommission->self_esport_commission + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                                $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                            } elseif($value['game_category']==4){
                                $playerRealCommission->self_card_performance = $playerRealCommission->self_card_performance + $value['agent_process_available_bet_amount'];
                                $playerRealCommission->self_card_commission  = $playerRealCommission->self_card_commission + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                                $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                            } elseif($value['game_category']==5){
                                $playerRealCommission->self_sport_performance = $playerRealCommission->self_sport_performance + $value['agent_process_available_bet_amount'];
                                $playerRealCommission->self_sport_commission = $playerRealCommission->self_sport_commission + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                                $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                            } elseif($value['game_category']==6){
                                $playerRealCommission->self_lottery_performance = $playerRealCommission->self_lottery_performance + $value['agent_process_available_bet_amount'];
                                $playerRealCommission->self_lottery_commission = $playerRealCommission->self_lottery_commission + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                                $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                            } elseif($value['game_category']==7){
                                $playerRealCommission->self_fish_performance = $playerRealCommission->self_fish_performance + $value['agent_process_available_bet_amount'];
                                $playerRealCommission->self_fish_commission = $playerRealCommission->self_fish_commission + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                                $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$playerSetting->guaranteed;
                            }

                            $playerRealCommission->save();
                        }

                        foreach ($playerIds as $k => $v) {
                            //变更团队的数据
                            $playerRealCommission                                       = PlayerRealCommission::where('player_id',$v)->where('day',$day)->first();
                            if($value['game_category']==1){
                                $playerRealCommission->team_casino_performance = $playerRealCommission->team_casino_performance + $value['agent_process_available_bet_amount'];
                                if(isset($diffGuaranteedArr[$v])){
                                    $playerRealCommission->team_casino_commission  = $playerRealCommission->team_casino_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                }
                            } elseif($value['game_category']==2){
                                $playerRealCommission->team_electronic_performance = $playerRealCommission->team_electronic_performance + $value['agent_process_available_bet_amount'];
                                if(isset($diffGuaranteedArr[$v])){
                                    $playerRealCommission->team_electronic_commission  = $playerRealCommission->team_electronic_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                }
                            } elseif($value['game_category']==3){
                                $playerRealCommission->team_esport_performance = $playerRealCommission->team_esport_performance + $value['agent_process_available_bet_amount'];
                                if(isset($diffGuaranteedArr[$v])){
                                    $playerRealCommission->team_esport_commission  = $playerRealCommission->team_esport_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                }
                            } elseif($value['game_category']==4){
                                $playerRealCommission->team_card_performance = $playerRealCommission->team_card_performance + $value['agent_process_available_bet_amount'];
                                if(isset($diffGuaranteedArr[$v])){
                                    $playerRealCommission->team_card_commission  = $playerRealCommission->team_card_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                }
                            } elseif($value['game_category']==5){
                                $playerRealCommission->team_sport_performance = $playerRealCommission->team_sport_performance + $value['agent_process_available_bet_amount'];
                                if(isset($diffGuaranteedArr[$v])){
                                    $playerRealCommission->team_sport_commission = $playerRealCommission->team_sport_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                }
                            } elseif($value['game_category']==6){
                                $playerRealCommission->team_lottery_performance = $playerRealCommission->team_lottery_performance + $value['agent_process_available_bet_amount'];
                                if(isset($diffGuaranteedArr[$v])){
                                    $playerRealCommission->team_lottery_commission = $playerRealCommission->team_lottery_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                }
                            } elseif($value['game_category']==7){
                                $playerRealCommission->team_fish_performance = $playerRealCommission->team_fish_performance + $value['agent_process_available_bet_amount'];
                                if(isset($diffGuaranteedArr[$v])){
                                    $playerRealCommission->team_fish_commission = $playerRealCommission->team_fish_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                }
                            }

                            $playerRealCommission->save();
                        }
                } else{
                        foreach ($playerIds as $k => $v) {
                            if($value['parent_id']==$v){
                                //变更自已的数据
                                $playerRealCommission                                       = PlayerRealCommission::where('player_id',$v)->where('day',$day)->first();
                                if($value['game_category']==1){
                                    $playerRealCommission->directlyunder_casino_performance = $playerRealCommission->directlyunder_casino_performance + $value['agent_process_available_bet_amount'];
                                    $playerRealCommission->directlyunder_casino_commission  = $playerRealCommission->directlyunder_casino_commission + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                } elseif($value['game_category']==2){
                                    $playerRealCommission->directlyunder_electronic_performance = $playerRealCommission->directlyunder_electronic_performance + $value['agent_process_available_bet_amount'];
                                    $playerRealCommission->directlyunder_electronic_commission  = $playerRealCommission->directlyunder_electronic_commission + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                } elseif($value['game_category']==3){
                                    $playerRealCommission->directlyunder_esport_performance = $playerRealCommission->directlyunder_esport_performance + $value['agent_process_available_bet_amount'];
                                    $playerRealCommission->directlyunder_esport_commission  = $playerRealCommission->directlyunder_esport_commission + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                } elseif($value['game_category']==4){
                                    $playerRealCommission->directlyunder_card_performance = $playerRealCommission->directlyunder_card_performance + $value['agent_process_available_bet_amount'];
                                    $playerRealCommission->directlyunder_card_commission  = $playerRealCommission->directlyunder_card_commission + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                } elseif($value['game_category']==5){
                                    $playerRealCommission->directlyunder_sport_performance = $playerRealCommission->directlyunder_sport_performance + $value['agent_process_available_bet_amount'];
                                    $playerRealCommission->directlyunder_sport_commission = $playerRealCommission->directlyunder_sport_commission + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                } elseif($value['game_category']==6){
                                    $playerRealCommission->directlyunder_lottery_performance = $playerRealCommission->directlyunder_lottery_performance + $value['agent_process_available_bet_amount'];
                                    $playerRealCommission->directlyunder_lottery_commission = $playerRealCommission->directlyunder_lottery_commission + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                } elseif($value['game_category']==7){
                                    $playerRealCommission->directlyunder_fish_performance = $playerRealCommission->directlyunder_fish_performance + $value['agent_process_available_bet_amount'];
                                    $playerRealCommission->directlyunder_fish_commission = $playerRealCommission->directlyunder_fish_commission + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                    $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$guaranteedArr[$v];
                                }

                                $playerRealCommission->save();
                            } else{
                                //变更团队的数据
                                $playerRealCommission                                       = PlayerRealCommission::where('player_id',$v)->where('day',$day)->first();
                                if($value['game_category']==1){
                                    $playerRealCommission->team_casino_performance = $playerRealCommission->team_casino_performance + $value['agent_process_available_bet_amount'];
                                    if(isset($diffGuaranteedArr[$v])){
                                        $playerRealCommission->team_casino_commission  = $playerRealCommission->team_casino_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                        $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    }
                                } elseif($value['game_category']==2){
                                    $playerRealCommission->team_electronic_performance = $playerRealCommission->team_electronic_performance + $value['agent_process_available_bet_amount'];
                                    if(isset($diffGuaranteedArr[$v])){
                                        $playerRealCommission->team_electronic_commission  = $playerRealCommission->team_electronic_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                        $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    }
                                } elseif($value['game_category']==3){
                                    $playerRealCommission->team_esport_performance = $playerRealCommission->team_esport_performance + $value['agent_process_available_bet_amount'];
                                    if(isset($diffGuaranteedArr[$v])){
                                        $playerRealCommission->team_esport_commission  = $playerRealCommission->team_esport_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                        $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    }
                                } elseif($value['game_category']==4){
                                    $playerRealCommission->team_card_performance = $playerRealCommission->team_card_performance + $value['agent_process_available_bet_amount'];
                                    if(isset($diffGuaranteedArr[$v])){
                                        $playerRealCommission->team_card_commission  = $playerRealCommission->team_card_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                        $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    }
                                } elseif($value['game_category']==5){
                                    $playerRealCommission->team_sport_performance = $playerRealCommission->team_sport_performance + $value['agent_process_available_bet_amount'];
                                    if(isset($diffGuaranteedArr[$v])){
                                        $playerRealCommission->team_sport_commission = $playerRealCommission->team_sport_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                        $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    }
                                } elseif($value['game_category']==6){
                                    $playerRealCommission->team_lottery_performance = $playerRealCommission->team_lottery_performance + $value['agent_process_available_bet_amount'];
                                    if(isset($diffGuaranteedArr[$v])){
                                        $playerRealCommission->team_lottery_commission = $playerRealCommission->team_lottery_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                        $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    }
                                } elseif($value['game_category']==7){
                                    $playerRealCommission->team_fish_performance = $playerRealCommission->team_fish_performance + $value['agent_process_available_bet_amount'];
                                    if(isset($diffGuaranteedArr[$v])){
                                        $playerRealCommission->team_fish_commission = $playerRealCommission->team_fish_commission + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                        $playerRealCommission->amount = $playerRealCommission->amount + $value['agent_process_available_bet_amount']*$diffGuaranteedArr[$v];
                                    }
                                }

                                $playerRealCommission->save();
                            }
                        }
                }
            }   
        }
            
        $carriers                   = Carrier::where('is_forbidden',0)->orderBy('id','asc')->get();
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
    }
}
