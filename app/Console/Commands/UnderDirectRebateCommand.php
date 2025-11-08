<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlayerBetflowCalculate;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Models\Carrier;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\CarrierGuaranteed;
use App\Models\CarrierCapitationFeeSetting;
use App\Models\Conf\CarrierPayChannel;
use App\Models\PayChannelGroup;
use App\Models\CarrierPreFixDomain;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Player;

class UnderDirectRebateCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'underdirectrebate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'underdirectrebate';

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
        $carriers                   = Carrier::all();
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains             = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $enableInviteGradientRebate = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'enable_invite_gradient_rebate',$value1->prefix);
                $videoInviteGradientRebate  = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'video_invite_gradient_rebate',$value1->prefix);
                $eleInviteGradientRebate    = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'ele_invite_gradient_rebate',$value1->prefix);
                $esportInviteGradientRebate = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'esport_invite_gradient_rebate',$value1->prefix);
                $cardInviteGradientRebate   = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'card_invite_gradient_rebate',$value1->prefix);
                $sportInviteGradientRebate  = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'sport_invite_gradient_rebate',$value1->prefix);
                $fishInviteGradientRebate   = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'fish_invite_gradient_rebate',$value1->prefix);
                $lottInviteGradientRebate   = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'lott_invite_gradient_rebate',$value1->prefix);

                $videoInviteGradientRebate  = json_decode($videoInviteGradientRebate,true);
                $eleInviteGradientRebate    = json_decode($eleInviteGradientRebate,true);
                $esportInviteGradientRebate = json_decode($esportInviteGradientRebate,true);
                $cardInviteGradientRebate   = json_decode($cardInviteGradientRebate,true);
                $sportInviteGradientRebate  = json_decode($sportInviteGradientRebate,true);
                $fishInviteGradientRebate   = json_decode($fishInviteGradientRebate,true);
                $lottInviteGradientRebate   = json_decode($lottInviteGradientRebate,true);

                $rebate                     = [];

                if($enableInviteGradientRebate){
                    $playerBetFlowMiddles =  PlayerBetFlowMiddle::select('parent_id',\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'game_category')->where('carrier_id',$value1->carrier_id)->where('prefix',$value1->prefix)->where('day',date('Ymd',strtotime('-1 day')))->where('whether_recharge',1)->groupBy('parent_id','game_category')->get();
        
                    foreach ($playerBetFlowMiddles as $key2 => $value2) {
                        switch ($value2->game_category) {
                            case 1:
                                $bonus  = 0;
                                $player = Player::where('player_id',$value2->parent_id)->first();
                                foreach ($videoInviteGradientRebate as $key3 => $value3) {
                                    if($value2->agent_process_available_bet_amount >= $value3['probability']){
                                        $bonus = $value3['bonus'];
                                    }
                                }

                                if(isset($rebate[$value2->parent_id])){
                                    $rebate[$value2->parent_id]['directlyunder_casino_performance'] = $value2->agent_process_available_bet_amount;
                                    $rebate[$value2->parent_id]['directlyunder_casino_commission']  = $value2->agent_process_available_bet_amount*$bonus*100;
                                    $rebate[$value2->parent_id]['amount']                           = $rebate[$value2->parent_id]['amount'] + $rebate[$value2->parent_id]['directlyunder_casino_commission'];
                                } else{
                                    $row =[
                                        'carrier_id'                            => $value1->carrier_id,
                                        'rid'                                   => $player->rid,
                                        'top_id'                                => $player->top_id,
                                        'parent_id'                             => $player->parent_id,
                                        'player_id'                             => $player->player_id,
                                        'is_tester'                             => $player->is_tester,
                                        'user_name'                             => $player->user_name,
                                        'level'                                 => $player->level,
                                        'prefix'                                => $player->prefix,
                                        'team_casino_performance'               => 0,
                                        'team_electronic_performance'           => 0,
                                        'team_esport_performance'               => 0,
                                        'team_fish_performance'                 => 0,
                                        'team_card_performance'                 => 0,
                                        'team_sport_performance'                => 0,
                                        'team_lottery_performance'              => 0,
                                        'self_casino_performance'               => 0,
                                        'self_electronic_performance'           => 0,
                                        'self_esport_performance'               => 0,
                                        'self_fish_performance'                 => 0,
                                        'self_card_performance'                 => 0,
                                        'self_sport_performance'                => 0,
                                        'self_lottery_performance'              => 0,
                                        'init_time'                             => time(),
                                        'day'                                   => date('Ymd',strtotime('-1 day')),
                                        'directlyunder_casino_performance'      => $value2->agent_process_available_bet_amount,
                                        'directlyunder_electronic_performance'  => 0,
                                        'directlyunder_esport_performance'      => 0,
                                        'directlyunder_fish_performance'        => 0,
                                        'directlyunder_card_performance'        => 0,
                                        'directlyunder_sport_performance'       => 0,
                                        'directlyunder_lottery_performance'     => 0,
                                        'directlyunder_casino_commission'       => $value2->agent_process_available_bet_amount*$bonus*100,
                                        'directlyunder_electronic_commission'   => 0,
                                        'directlyunder_esport_commission'       => 0,
                                        'directlyunder_fish_commission'         => 0,
                                        'directlyunder_card_commission'         => 0,
                                        'directlyunder_sport_commission'        => 0,
                                        'directlyunder_lottery_commission'      => 0,
                                        'amount'                                => $value2->agent_process_available_bet_amount*$bonus*100
                                    ];
                                    $rebate[$value2->parent_id] = $row;
                                }

                                break;
                            case 2:
                                $bonus  = 0;
                                $player = Player::where('player_id',$value2->parent_id)->first();
                                foreach ($eleInviteGradientRebate as $key3 => $value3) {
                                    if($value2->agent_process_available_bet_amount >= $value3['probability']){
                                        $bonus = $value3['bonus'];
                                    }
                                }

                                if(isset($rebate[$value2->parent_id])){
                                    $rebate[$value2->parent_id]['directlyunder_electronic_performance'] = $value2->agent_process_available_bet_amount;
                                    $rebate[$value2->parent_id]['directlyunder_electronic_commission']  = $value2->agent_process_available_bet_amount*$bonus*100;
                                    $rebate[$value2->parent_id]['amount']                               = $rebate[$value2->parent_id]['amount'] + $rebate[$value2->parent_id]['directlyunder_electronic_commission'];
                                } else{
                                    $row =[
                                        'carrier_id'                            => $value1->carrier_id,
                                        'rid'                                   => $player->rid,
                                        'top_id'                                => $player->top_id,
                                        'parent_id'                             => $player->parent_id,
                                        'player_id'                             => $player->player_id,
                                        'is_tester'                             => $player->is_tester,
                                        'user_name'                             => $player->user_name,
                                        'level'                                 => $player->level,
                                        'prefix'                                => $player->prefix,
                                        'team_casino_performance'               => 0,
                                        'team_electronic_performance'           => 0,
                                        'team_esport_performance'               => 0,
                                        'team_fish_performance'                 => 0,
                                        'team_card_performance'                 => 0,
                                        'team_sport_performance'                => 0,
                                        'team_lottery_performance'              => 0,
                                        'self_casino_performance'               => 0,
                                        'self_electronic_performance'           => 0,
                                        'self_esport_performance'               => 0,
                                        'self_fish_performance'                 => 0,
                                        'self_card_performance'                 => 0,
                                        'self_sport_performance'                => 0,
                                        'self_lottery_performance'              => 0,
                                        'init_time'                             => time(),
                                        'day'                                   => date('Ymd',strtotime('-1 day')),
                                        'directlyunder_casino_performance'      => 0,
                                        'directlyunder_electronic_performance'  => $value2->agent_process_available_bet_amount,
                                        'directlyunder_esport_performance'      => 0,
                                        'directlyunder_fish_performance'        => 0,
                                        'directlyunder_card_performance'        => 0,
                                        'directlyunder_sport_performance'       => 0,
                                        'directlyunder_lottery_performance'     => 0,
                                        'directlyunder_casino_commission'       => 0,
                                        'directlyunder_electronic_commission'   => $value2->agent_process_available_bet_amount*$bonus*100,
                                        'directlyunder_esport_commission'       => 0,
                                        'directlyunder_fish_commission'         => 0,
                                        'directlyunder_card_commission'         => 0,
                                        'directlyunder_sport_commission'        => 0,
                                        'directlyunder_lottery_commission'      => 0,
                                        'amount'                                => $value2->agent_process_available_bet_amount*$bonus*100
                                    ];
                                    $rebate[$value2->parent_id] = $row;
                                }
                                break;
                            case 3:
                                $bonus  = 0;
                                $player = Player::where('player_id',$value2->parent_id)->first();
                                foreach ($esportInviteGradientRebate as $key3 => $value3) {
                                    if($value2->agent_process_available_bet_amount >= $value3['probability']){
                                        $bonus = $value3['bonus'];
                                    }
                                }

                                if(isset($rebate[$value2->parent_id])){
                                    $rebate[$value2->parent_id]['directlyunder_esport_performance'] = $value2->agent_process_available_bet_amount;
                                    $rebate[$value2->parent_id]['directlyunder_esport_commission']  = $value2->agent_process_available_bet_amount*$bonus*100;
                                    $rebate[$value2->parent_id]['amount']                           = $rebate[$value2->parent_id]['amount'] + $rebate[$value2->parent_id]['directlyunder_esport_commission'];
                                } else{
                                    $row =[
                                        'carrier_id'                            => $value1->carrier_id,
                                        'rid'                                   => $player->rid,
                                        'top_id'                                => $player->top_id,
                                        'parent_id'                             => $player->parent_id,
                                        'player_id'                             => $player->player_id,
                                        'is_tester'                             => $player->is_tester,
                                        'user_name'                             => $player->user_name,
                                        'level'                                 => $player->level,
                                        'prefix'                                => $player->prefix,
                                        'team_casino_performance'               => 0,
                                        'team_electronic_performance'           => 0,
                                        'team_esport_performance'               => 0,
                                        'team_fish_performance'                 => 0,
                                        'team_card_performance'                 => 0,
                                        'team_sport_performance'                => 0,
                                        'team_lottery_performance'              => 0,
                                        'self_casino_performance'               => 0,
                                        'self_electronic_performance'           => 0,
                                        'self_esport_performance'               => 0,
                                        'self_fish_performance'                 => 0,
                                        'self_card_performance'                 => 0,
                                        'self_sport_performance'                => 0,
                                        'self_lottery_performance'              => 0,
                                        'init_time'                             => time(),
                                        'day'                                   => date('Ymd',strtotime('-1 day')),
                                        'directlyunder_casino_performance'      => 0,
                                        'directlyunder_electronic_performance'  => 0,
                                        'directlyunder_esport_performance'      => $value2->agent_process_available_bet_amount,
                                        'directlyunder_fish_performance'        => 0,
                                        'directlyunder_card_performance'        => 0,
                                        'directlyunder_sport_performance'       => 0,
                                        'directlyunder_lottery_performance'     => 0,
                                        'directlyunder_casino_commission'       => 0,
                                        'directlyunder_electronic_commission'   => 0,
                                        'directlyunder_esport_commission'       => $value2->agent_process_available_bet_amount*$bonus*100,
                                        'directlyunder_fish_commission'         => 0,
                                        'directlyunder_card_commission'         => 0,
                                        'directlyunder_sport_commission'        => 0,
                                        'directlyunder_lottery_commission'      => 0,
                                        'amount'                                => $value2->agent_process_available_bet_amount*$bonus*100
                                    ];
                                    $rebate[$value2->parent_id] = $row;
                                }
                                break;
                            case 4:
                                $bonus  = 0;
                                $player = Player::where('player_id',$value2->parent_id)->first();
                                foreach ($cardInviteGradientRebate as $key3 => $value3) {
                                    if($value2->agent_process_available_bet_amount >= $value3['probability']){
                                        $bonus = $value3['bonus'];
                                    }
                                }

                                if(isset($rebate[$value2->parent_id])){
                                    $rebate[$value2->parent_id]['directlyunder_card_performance'] = $value2->agent_process_available_bet_amount;
                                    $rebate[$value2->parent_id]['directlyunder_card_commission']  = $value2->agent_process_available_bet_amount*$bonus*100;
                                    $rebate[$value2->parent_id]['amount']                           = $rebate[$value2->parent_id]['amount'] + $rebate[$value2->parent_id]['directlyunder_card_commission'];
                                } else{
                                    $row =[
                                        'carrier_id'                            => $value1->carrier_id,
                                        'rid'                                   => $player->rid,
                                        'top_id'                                => $player->top_id,
                                        'parent_id'                             => $player->parent_id,
                                        'player_id'                             => $player->player_id,
                                        'is_tester'                             => $player->is_tester,
                                        'user_name'                             => $player->user_name,
                                        'level'                                 => $player->level,
                                        'prefix'                                => $player->prefix,
                                        'team_casino_performance'               => 0,
                                        'team_electronic_performance'           => 0,
                                        'team_esport_performance'               => 0,
                                        'team_fish_performance'                 => 0,
                                        'team_card_performance'                 => 0,
                                        'team_sport_performance'                => 0,
                                        'team_lottery_performance'              => 0,
                                        'self_casino_performance'               => 0,
                                        'self_electronic_performance'           => 0,
                                        'self_esport_performance'               => 0,
                                        'self_fish_performance'                 => 0,
                                        'self_card_performance'                 => 0,
                                        'self_sport_performance'                => 0,
                                        'self_lottery_performance'              => 0,
                                        'init_time'                             => time(),
                                        'day'                                   => date('Ymd',strtotime('-1 day')),
                                        'directlyunder_casino_performance'      => 0,
                                        'directlyunder_electronic_performance'  => 0,
                                        'directlyunder_esport_performance'      => 0,
                                        'directlyunder_fish_performance'        => 0,
                                        'directlyunder_card_performance'        => $value2->agent_process_available_bet_amount,
                                        'directlyunder_sport_performance'       => 0,
                                        'directlyunder_lottery_performance'     => 0,
                                        'directlyunder_casino_commission'       => 0,
                                        'directlyunder_electronic_commission'   => 0,
                                        'directlyunder_esport_commission'       => 0,
                                        'directlyunder_fish_commission'         => 0,
                                        'directlyunder_card_commission'         => $value2->agent_process_available_bet_amount*$bonus*100,
                                        'directlyunder_sport_commission'        => 0,
                                        'directlyunder_lottery_commission'      => 0,
                                        'amount'                                => $value2->agent_process_available_bet_amount*$bonus*100
                                    ];
                                    $rebate[$value2->parent_id] = $row;
                                }
                                break;
                            case 5:
                                $bonus  = 0;
                                $player = Player::where('player_id',$value2->parent_id)->first();
                                foreach ($sportInviteGradientRebate as $key3 => $value3) {
                                    if($value2->agent_process_available_bet_amount >= $value3['probability']){
                                        $bonus = $value3['bonus'];
                                    }
                                }

                                if(isset($rebate[$value2->parent_id])){
                                    $rebate[$value2->parent_id]['directlyunder_sport_performance'] = $value2->agent_process_available_bet_amount;
                                    $rebate[$value2->parent_id]['directlyunder_sport_commission']  = $value2->agent_process_available_bet_amount*$bonus*100;
                                    $rebate[$value2->parent_id]['amount']                          = $rebate[$value2->parent_id]['amount'] + $rebate[$value2->parent_id]['directlyunder_sport_commission'];
                                } else{
                                    $row =[
                                        'carrier_id'                            => $value1->carrier_id,
                                        'rid'                                   => $player->rid,
                                        'top_id'                                => $player->top_id,
                                        'parent_id'                             => $player->parent_id,
                                        'player_id'                             => $player->player_id,
                                        'is_tester'                             => $player->is_tester,
                                        'user_name'                             => $player->user_name,
                                        'level'                                 => $player->level,
                                        'prefix'                                => $player->prefix,
                                        'team_casino_performance'               => 0,
                                        'team_electronic_performance'           => 0,
                                        'team_esport_performance'               => 0,
                                        'team_fish_performance'                 => 0,
                                        'team_card_performance'                 => 0,
                                        'team_sport_performance'                => 0,
                                        'team_lottery_performance'              => 0,
                                        'self_casino_performance'               => 0,
                                        'self_electronic_performance'           => 0,
                                        'self_esport_performance'               => 0,
                                        'self_fish_performance'                 => 0,
                                        'self_card_performance'                 => 0,
                                        'self_sport_performance'                => 0,
                                        'self_lottery_performance'              => 0,
                                        'init_time'                             => time(),
                                        'day'                                   => date('Ymd',strtotime('-1 day')),
                                        'directlyunder_casino_performance'      => 0,
                                        'directlyunder_electronic_performance'  => 0,
                                        'directlyunder_esport_performance'      => 0,
                                        'directlyunder_fish_performance'        => 0,
                                        'directlyunder_card_performance'        => 0,
                                        'directlyunder_sport_performance'       => $value2->agent_process_available_bet_amount,
                                        'directlyunder_lottery_performance'     => 0,
                                        'directlyunder_casino_commission'       => 0,
                                        'directlyunder_electronic_commission'   => 0,
                                        'directlyunder_esport_commission'       => 0,
                                        'directlyunder_fish_commission'         => 0,
                                        'directlyunder_card_commission'         => 0,
                                        'directlyunder_sport_commission'        => $value2->agent_process_available_bet_amount*$bonus*100,
                                        'directlyunder_lottery_commission'      => 0,
                                        'amount'                                => $value2->agent_process_available_bet_amount*$bonus*100
                                    ];
                                    $rebate[$value2->parent_id] = $row;
                                }
                                break;
                            case 6:
                                $bonus  = 0;
                                $player = Player::where('player_id',$value2->parent_id)->first();
                                foreach ($lottInviteGradientRebate as $key3 => $value3) {
                                    if($value2->agent_process_available_bet_amount >= $value3['probability']){
                                        $bonus = $value3['bonus'];
                                    }
                                }

                                if(isset($rebate[$value2->parent_id])){
                                    $rebate[$value2->parent_id]['directlyunder_lottery_performance'] = $value2->agent_process_available_bet_amount;
                                    $rebate[$value2->parent_id]['directlyunder_lottery_commission']  = $value2->agent_process_available_bet_amount*$bonus*100;
                                    $rebate[$value2->parent_id]['amount']                          = $rebate[$value2->parent_id]['amount'] + $rebate[$value2->parent_id]['directlyunder_lottery_commission'];
                                } else{
                                    $row =[
                                        'carrier_id'                            => $value1->carrier_id,
                                        'rid'                                   => $player->rid,
                                        'top_id'                                => $player->top_id,
                                        'parent_id'                             => $player->parent_id,
                                        'player_id'                             => $player->player_id,
                                        'is_tester'                             => $player->is_tester,
                                        'user_name'                             => $player->user_name,
                                        'level'                                 => $player->level,
                                        'prefix'                                => $player->prefix,
                                        'team_casino_performance'               => 0,
                                        'team_electronic_performance'           => 0,
                                        'team_esport_performance'               => 0,
                                        'team_fish_performance'                 => 0,
                                        'team_card_performance'                 => 0,
                                        'team_sport_performance'                => 0,
                                        'team_lottery_performance'              => 0,
                                        'self_casino_performance'               => 0,
                                        'self_electronic_performance'           => 0,
                                        'self_esport_performance'               => 0,
                                        'self_fish_performance'                 => 0,
                                        'self_card_performance'                 => 0,
                                        'self_sport_performance'                => 0,
                                        'self_lottery_performance'              => 0,
                                        'init_time'                             => time(),
                                        'day'                                   => date('Ymd',strtotime('-1 day')),
                                        'directlyunder_casino_performance'      => 0,
                                        'directlyunder_electronic_performance'  => 0,
                                        'directlyunder_esport_performance'      => 0,
                                        'directlyunder_fish_performance'        => 0,
                                        'directlyunder_card_performance'        => 0,
                                        'directlyunder_sport_performance'       => 0,
                                        'directlyunder_lottery_performance'     => $value2->agent_process_available_bet_amount,
                                        'directlyunder_casino_commission'       => 0,
                                        'directlyunder_electronic_commission'   => 0,
                                        'directlyunder_esport_commission'       => 0,
                                        'directlyunder_fish_commission'         => 0,
                                        'directlyunder_card_commission'         => 0,
                                        'directlyunder_sport_commission'        => 0,
                                        'directlyunder_lottery_commission'      => $value2->agent_process_available_bet_amount*$bonus*100,
                                        'amount'                                => $value2->agent_process_available_bet_amount*$bonus*100
                                    ];
                                    $rebate[$value2->parent_id] = $row;
                                }
                                break;
                            case 7:
                                $bonus  = 0;
                                $player = Player::where('player_id',$value2->parent_id)->first();
                                foreach ($lottInviteGradientRebate as $key3 => $value3) {
                                    if($value2->agent_process_available_bet_amount >= $value3['probability']){
                                        $bonus = $value3['bonus'];
                                    }
                                }

                                if(isset($rebate[$value2->parent_id])){
                                    $rebate[$value2->parent_id]['directlyunder_fish_performance'] = $value2->agent_process_available_bet_amount;
                                    $rebate[$value2->parent_id]['directlyunder_fish_commission']  = $value2->agent_process_available_bet_amount*$bonus*100;
                                    $rebate[$value2->parent_id]['amount']                          = $rebate[$value2->parent_id]['amount'] + $rebate[$value2->parent_id]['directlyunder_fish_commission'];
                                } else{
                                    $row =[
                                        'carrier_id'                            => $value1->carrier_id,
                                        'rid'                                   => $player->rid,
                                        'top_id'                                => $player->top_id,
                                        'parent_id'                             => $player->parent_id,
                                        'player_id'                             => $player->player_id,
                                        'is_tester'                             => $player->is_tester,
                                        'user_name'                             => $player->user_name,
                                        'level'                                 => $player->level,
                                        'prefix'                                => $player->prefix,
                                        'team_casino_performance'               => 0,
                                        'team_electronic_performance'           => 0,
                                        'team_esport_performance'               => 0,
                                        'team_fish_performance'                 => 0,
                                        'team_card_performance'                 => 0,
                                        'team_sport_performance'                => 0,
                                        'team_lottery_performance'              => 0,
                                        'self_casino_performance'               => 0,
                                        'self_electronic_performance'           => 0,
                                        'self_esport_performance'               => 0,
                                        'self_fish_performance'                 => 0,
                                        'self_card_performance'                 => 0,
                                        'self_sport_performance'                => 0,
                                        'self_lottery_performance'              => 0,
                                        'init_time'                             => time(),
                                        'day'                                   => date('Ymd',strtotime('-1 day')),
                                        'directlyunder_casino_performance'      => 0,
                                        'directlyunder_electronic_performance'  => 0,
                                        'directlyunder_esport_performance'      => 0,
                                        'directlyunder_fish_performance'        => $value2->agent_process_available_bet_amount,
                                        'directlyunder_card_performance'        => 0,
                                        'directlyunder_sport_performance'       => 0,
                                        'directlyunder_lottery_performance'     => 0,
                                        'directlyunder_casino_commission'       => 0,
                                        'directlyunder_electronic_commission'   => 0,
                                        'directlyunder_esport_commission'       => 0,
                                        'directlyunder_fish_commission'         => $value2->agent_process_available_bet_amount*$bonus*100,
                                        'directlyunder_card_commission'         => 0,
                                        'directlyunder_sport_commission'        => 0,
                                        'directlyunder_lottery_commission'      => 0,
                                        'amount'                                => $value2->agent_process_available_bet_amount*$bonus*100
                                    ];
                                    $rebate[$value2->parent_id] = $row;
                                }
                                break;
                            
                            default:
                                // code...
                                break;
                        }
                    }

                    $insertData = [];
                    foreach ($rebate as $key2 => $value2) {
                        $insertData[] = $value2;
                    }

                    \DB::table('report_player_commission')->insert($insertData);
                }
            }
        }
    }
}