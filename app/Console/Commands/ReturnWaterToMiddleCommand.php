<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportGamePlatStatDay;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerBetFlow;
use App\Models\PlayerBetflowCalculate;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Lib\Cache\GameCache;
use App\Models\PlayerTransfer;
use App\Models\Carrier;
use App\Models\Player;
use App\Models\PlayerCommission;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\CarrierPreFixDomain;
use App\Lib\Cache\Lock;
use App\Lib\Clog;
use App\Jobs\RealReturnCommissionJob;

class ReturnWaterToMiddleCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'returnWaterToMiddle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'returnWaterToMiddle';

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
        $carriers = Carrier::where('is_forbidden',0)->get();
        $cacheKey = "returnWaterToMiddle";

        $redisLock = Lock::addLock($cacheKey,300);

        if (!$redisLock) {
            \Log::info('returnWaterToMiddle上锁失败');
            return false;
        } else {
            try {
                $carrierGamePlatDayParame     = [];
                $insertPlayerBetFlowMiddleArr = [];
                $unqueArr                     = [];
                $j                            = 0;

                foreach ($carriers as $key => $carriervalue) {
                    $pageSize                       = 1000 ;
                    $flag                           = false;

                    $carrierPreFixDomains           = CarrierPreFixDomain::where('carrier_id',$carriervalue->id)->get();
                    foreach ($carrierPreFixDomains as $key1 => $value1) {
                        $enabelAgentCommissionflowSingle     = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'enabel_agent_commissionflow_single',$value1->prefix);
                        $agentCasinoBetflowCalculateRate     = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'agent_casino_betflow_calculate_rate',$value1->prefix);
                        $agentElectronicBetflowCalculateRate = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'agent_electronic_betflow_calculate_rate',$value1->prefix);
                        $agentEsportBetflowCalculateRate     = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'agent_esport_betflow_calculate_rate',$value1->prefix);
                        $agentFishBetflowCalculateRate       = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'agent_fish_betflow_calculate_rate',$value1->prefix);
                        $agentCardBetflowCalculateRate       = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'agent_card_betflow_calculate_rate',$value1->prefix);
                        $agentLotteryBetflowCalculateRate    = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'agent_lottery_betflow_calculate_rate',$value1->prefix);
                        $agentSportBetflowCalculateRate      = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'agent_sport_betflow_calculate_rate',$value1->prefix);

                        $casinoBetflowCalculateRate     = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'casino_betflow_calculate_rate',$value1->prefix);
                        $electronicBetflowCalculateRate = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'electronic_betflow_calculate_rate',$value1->prefix);
                        $esportBetflowCalculateRate     = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'esport_betflow_calculate_rate',$value1->prefix);
                        $fishBetflowCalculateRate       = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'fish_betflow_calculate_rate',$value1->prefix);
                        $cardBetflowCalculateRate       = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'card_betflow_calculate_rate',$value1->prefix);
                        $lotteryBetflowCalculateRate    = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'lottery_betflow_calculate_rate',$value1->prefix);
                        $sportBetflowCalculateRate      = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'sport_betflow_calculate_rate',$value1->prefix);

                        $isLossWriteBetflow             = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'is_loss_write_betflow',$value1->prefix);

                        //刷水套利控制
                        $arbitrageGameList              = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'arbitrage_game_list',$value1->prefix);
                        $arbitrageGameFlowConvert       = CarrierCache::getCarrierMultipleConfigure($carriervalue->id,'arbitrage_game_flow_convert',$value1->prefix);

                        $arbitrageGamesArr   = [];
                        if(!empty($arbitrageGameList)){
                            $arbitrageGamesArr = explode(',',$arbitrageGameList);
                        }
                       
                        do{
                            $j++;

                            $playerBetFlowIds  =  PlayerBetFlow::where('game_status',1)
                                ->where('stat_time',0)
                                ->where('prefix',$value1->prefix)
                                ->where('is_material',0)
                                ->orderBy('id','asc')
                                ->limit($pageSize)
                                ->pluck('id')
                                ->toArray();

                            $playerBetFlow  =  PlayerBetFlow::select('is_loss','player_id','day','game_id','game_category',\DB::raw('count(id) as account'),\DB::raw('sum(bet_amount) as bet_amount'),\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'),'main_game_plat_id')
                                ->groupBy(['day','player_id','game_category','main_game_plat_id','is_loss','game_id'])
                                ->whereIn('id',$playerBetFlowIds)
                                ->get();
                            
                            foreach ($playerBetFlow as $key => $value) {
                               $maxBetTime                       = PlayerBetFlow::where('player_id',$value->player_id)->whereIn('id',$playerBetFlowIds)->max('bet_time');
                               $subBetFlowIds                    = PlayerBetFlow::where('player_id',$value->player_id)->whereIn('id',$playerBetFlowIds)->where('player_id',$value->player_id)->where('game_category',$value->game_category)->where('day',$value->day)->where('is_loss',$value->is_loss)->where('main_game_plat_id',$value->main_game_plat_id)->pluck('id')->toArray();

                               $prefix                           = PlayerCache::getPrefix($value->player_id);
                               $row                              = [];
                               $row['player_id']                 = $value->player_id;
                               $row['carrier_id']                = $carriervalue->id;
                               $row['is_live_streaming_account'] = PlayerCache::getIsLiveStreamingAccount($value->player_id);
                               $row['prefix']                    = $prefix;
                               $row['rid']                       = PlayerCache::getPlayerRid($carriervalue->id,$value->player_id);
                               $row['parent_id']                 = PlayerCache::getPlayerParentId($value->player_id);
                               $row['day']                       = $value->day;
                               $row['game_category']             = $value->game_category;
                               $row['bet_amount']                = $value->bet_amount;

                               if($isLossWriteBetflow){
                                if($value->is_loss ==1){
                                    $row['available_bet_amount']      = $value->available_bet_amount;
                                } else{
                                    $row['available_bet_amount']      = 0;
                                }

                               } else{
                                  $row['available_bet_amount']      = $value->available_bet_amount;
                               }

                               $row['company_win_amount']        = $value->company_win_amount;
                               $row['main_game_plat_id']         = $value->main_game_plat_id;
                               $row['bet_time']                  = $maxBetTime;
                               $row['number']                    = $value->account;
                               $row['win_lose_agent']            = PlayerCache::getisWinLoseAgent($value->player_id);
                               $row['whether_recharge']          = PlayerCache::getIswhetherRecharge($value->player_id);
                               $row['created_at']                = date('Y-m-d H:i:s');
                               $row['updated_at']                = date('Y-m-d H:i:s');
                               $row['bet_flow_ids']              = json_encode($subBetFlowIds);

                               //有效流水处理
                               $playerBetflowCalculate         = PlayerCache::getPlayerBetflowCalculate($carriervalue->id,$value->player_id,$prefix);

                               switch ($value->game_category) {
                                   case 1:
                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category]*$arbitrageGameFlowConvert,10000,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$casinoBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$casinoBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           }
                                        } else{
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category],100,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$casinoBetflowCalculateRate,100,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$casinoBetflowCalculateRate,100,4);
                                           }
                                        }

                                       if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentCasinoBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                       } else{
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentCasinoBetflowCalculateRate,100,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                       }
                                       
                                       break;
                                    case 2:
                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category]*$arbitrageGameFlowConvert,10000,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$electronicBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$electronicBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           }
                                        } else{
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category],100,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$electronicBetflowCalculateRate,100,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$electronicBetflowCalculateRate,100,4);
                                           }
                                        }
                                       

                                       if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentElectronicBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                       } else{
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentElectronicBetflowCalculateRate,100,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                       }

                                       break;
                                    case 3:
                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category]*$arbitrageGameFlowConvert,10000,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$esportBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$esportBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           }
                                        } else{
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category],100,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$esportBetflowCalculateRate,100,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$esportBetflowCalculateRate,100,4);
                                           }
                                        }

                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentEsportBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                        } else{
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentEsportBetflowCalculateRate,100,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                        }

                                       break;
                                    case 4:
                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category]*$arbitrageGameFlowConvert,10000,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$cardBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$cardBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           }
                                        } else{
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category],100,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$cardBetflowCalculateRate,100,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$cardBetflowCalculateRate,100,4);
                                           }
                                        }
                                       
                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentCardBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                        } else{
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentCardBetflowCalculateRate,100,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                        }

                                       break;
                                    case 5:
                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category]*$arbitrageGameFlowConvert,10000,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$sportBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$sportBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           }
                                        } else{
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category],100,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$sportBetflowCalculateRate,100,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$sportBetflowCalculateRate,100,4);
                                           }
                                        }
                                       

                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentSportBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                        } else{
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentSportBetflowCalculateRate,100,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                        }

                                       break;
                                    case 6:
                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category]*$arbitrageGameFlowConvert,10000,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$lotteryBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$lotteryBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           }
                                        } else{
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category],100,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$lotteryBetflowCalculateRate,100,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$lotteryBetflowCalculateRate,100,4);
                                           }
                                        }
                                       

                                       if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentLotteryBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                       } else{
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentLotteryBetflowCalculateRate,100,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                       }

                                       break;
                                    case 7:
                                        if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category]*$arbitrageGameFlowConvert,10000,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$fishBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$fishBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           }
                                        } else{
                                            if($playerBetflowCalculate){
                                                if(isset($playerBetflowCalculate[$value->game_category])){
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$playerBetflowCalculate[$value->game_category],100,4);
                                                } else{
                                                    $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$fishBetflowCalculateRate,100,4);
                                                }
                                           } else {
                                              $row['process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$fishBetflowCalculateRate,100,4);
                                           }
                                        }
                                       
                                       if(in_array($value->game_id,$arbitrageGamesArr)){
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentFishBetflowCalculateRate*$arbitrageGameFlowConvert,10000,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                       } else{
                                            if($enabelAgentCommissionflowSingle){
                                                $row['agent_process_available_bet_amount'] = bcdiv($row['available_bet_amount']*$agentFishBetflowCalculateRate,100,4);
                                           } else{
                                                $row['agent_process_available_bet_amount'] = $row['process_available_bet_amount'];
                                           }
                                       }
                                   
                                   default:
                                       // code...
                                       break;
                               }
                               //有效流水处理结束
                               $insertPlayerBetFlowMiddleArr[] = $row;
                            }

                            if(count($playerBetFlowIds)){
                                PlayerBetFlow::whereIn('id',$playerBetFlowIds)->update(['stat_time'=>time()]);
                            }

                            //开始处理游戏统计
                            $playerBetFlow  =  PlayerBetFlow::select('player_id','day','main_game_plat_id',\DB::raw('count(id) as account'),\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))
                                ->groupBy(['day','player_id','main_game_plat_id'])
                                ->whereIn('id',$playerBetFlowIds)
                                ->get();

                            foreach ($playerBetFlow as $key => $value) {
                                $existCarrierGamePlatDay = CarrierCache::getExistCarrierGamePlatDay($carriervalue->id, $value->day, $value->main_game_plat_id);
                                if(!$existCarrierGamePlatDay){
                                    $reportGamePlatStatDay                     = new ReportGamePlatStatDay();
                                    $reportGamePlatStatDay->carrier_id         = $carriervalue->id;
                                    $reportGamePlatStatDay->main_game_plat_id  = $value->main_game_plat_id;
                                    $reportGamePlatStatDay->day                = $value->day;
                                    $reportGamePlatStatDay->save();
                                    CarrierCache::setExistCarrierGamePlatDay($carriervalue->id, $value->day, $value->main_game_plat_id);
                                }

                                $carrierGamePlatDay                       = CarrierCache::getCarrierGamePlatDay($carriervalue->id, $value->day, $value->main_game_plat_id);
                                $carrierGamePlatDay->account              = $carrierGamePlatDay->account + $value->account;
                                $carrierGamePlatDay->available_bet_amount = bcadd($carrierGamePlatDay->available_bet_amount,$value->available_bet_amount,4);
                                $carrierGamePlatDay->company_win_amount   = bcadd($carrierGamePlatDay->company_win_amount,$value->company_win_amount,4);

                                if(empty($carrierGamePlatDay->playerIds)){
                                    $playerIdsArr                    = [];
                                    $playerIdsArr[]                  = $value->player_id;
                                    $carrierGamePlatDay->playerIds   = serialize($playerIdsArr);
                                    $carrierGamePlatDay->personcount = 1;
                                } else {
                                    $playerIdsArr   = unserialize($carrierGamePlatDay->playerIds);
                                    if(!in_array($value->player_id, $playerIdsArr)){
                                        array_push($playerIdsArr,$value->player_id);
                                        $carrierGamePlatDay->personcount  = count($playerIdsArr);
                                        $carrierGamePlatDay->playerIds    = serialize($playerIdsArr);
                                    }
                                }

                                $carrierGamePlatDay->perperson   = bcdiv($carrierGamePlatDay->available_bet_amount,$carrierGamePlatDay->personcount,4);
                                $carrierGamePlatDay->peraccount  = bcdiv($carrierGamePlatDay->account,$carrierGamePlatDay->personcount,2);
                                $carrierGamePlatDay->save();

                                $unqueArr[] = $carriervalue->id.'_'.$value->day.'_'.$value->main_game_plat_id;
                                CarrierCache::setCarrierGamePlatDay($carriervalue->id, $value->day, $value->main_game_plat_id,$carrierGamePlatDay);
                            }

                            if(count($playerBetFlowIds)==$pageSize){
                                $flag = true;
                            } else {
                                $flag = false;
                            }
                           
                        } while($flag);   
                    }
                }

                //插入中间表
                \DB::table('log_player_bet_flow_middle')->insert($insertPlayerBetFlowMiddleArr);

                dispatch(new RealReturnCommissionJob($insertPlayerBetFlowMiddleArr));

                //开始更新数据
                $unqueArr = array_unique($unqueArr);
                foreach ($unqueArr as $key => $value) {
                    $arr   = explode('_',$value);
                    $model = CarrierCache::getCarrierGamePlatDay($arr[0], $arr[1], $arr[2]);
                    $model->save();
                }
            
                Lock::release($redisLock);
            }catch (\Exception $e) {
                Lock::release($redisLock);
                Clog::recordabnormal('返水计入中间表操作异常：:'.$e->getMessage());
            }
        }
    }
}