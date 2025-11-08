<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierPreFixDomain;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerCapitationFee;
use App\Models\Player;

class CreateCapitationFeeCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createcapitationfee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'createcapitationfee';

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
        $carrierPreFixDomains             = CarrierPreFixDomain::all();

            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $enableCapitationFee = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'enable_capitation_fee',$value1->prefix);

                if($enableCapitationFee){
                    //0=不需要审核
                    //1=需要审核

                    $capitationFeeType                            = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_fee_type',$value1->prefix);
                    $capitationFeeRechargeAmount                  = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_fee_recharge_amount',$value1->prefix);
                    $capitationFeeBetFlow                         = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_fee_bet_flow',$value1->prefix);
                    $capitationFeeGiftAmount                      = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_fee_gift_amount',$value1->prefix);
                    $capitationFeeDepositDays                     = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_fee_deposit_days',$value1->prefix);
                    $isCapitationFirstDepositCalculate            = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'is_capitation_first_deposit_calculate',$value1->prefix);
                    $capitationFirstDepositCalculateActivityid    = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_first_deposit_calculate_activityid',$value1->prefix);
                    $capitationFeeCycle                           = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_fee_cycle',$value1->prefix);
                    $capitationFeeRule                            = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_fee_rule',$value1->prefix);
                    $playerRealtimeDividendsStartDay              = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'player_realtime_dividends_start_day',$value1->prefix);
                    $siteOnlineTime                               = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'site_online_time',$value1->prefix);
                    $capitationFeeSingleRechargeAmount            = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'capitation_fee_single_recharge_amount',$value1->prefix);

                    //1=同分红周期 2=永久
                    if($capitationFeeCycle==1){
                        $startDay = date('Ymd',strtotime($playerRealtimeDividendsStartDay));
                    } else{
                        $startDay = date('Ymd',strtotime($siteOnlineTime));
                    }

                    if(!$capitationFeeRule){
                        $betFlowPlayerIds = PlayerBetFlowMiddle::where('prefix',$value1->prefix)->where('win_lose_agent',0)->where('day','>=',$startDay)->groupBy('player_id')->having(\DB::raw('sum(agent_process_available_bet_amount)'),'>=',$capitationFeeBetFlow)->pluck('player_id')->toArray();
                        if($capitationFeeSingleRechargeAmount >0){
                            $singleRechargePlayerIds = PlayerDepositPayLog::where('status',1)->where('prefix',$value1->prefix)->where('is_agent',0)->where('day','>=',$startDay)->where('amount','>=',$capitationFeeSingleRechargeAmount*10000)->pluck('player_id')->toArray();
                            $singleRechargePlayerIds = array_unique($singleRechargePlayerIds);

                            $depositPlayerIds = PlayerDepositPayLog::where('status',1)->where('prefix',$value1->prefix)->where('is_agent',0)->where('day','>=',$startDay)->whereIn('player_id',$singleRechargePlayerIds)->groupBy('player_id')->having(\DB::raw('sum(amount)'),'>=',$capitationFeeRechargeAmount*10000)->pluck('player_id')->toArray();
                        } else{
                            $depositPlayerIds = PlayerDepositPayLog::where('status',1)->where('prefix',$value1->prefix)->where('is_agent',0)->where('day','>=',$startDay)->groupBy('player_id')->having(\DB::raw('sum(amount)'),'>=',$capitationFeeRechargeAmount*10000)->pluck('player_id')->toArray(); 
                        }
                        
                    } else{
                        $betFlowPlayerIds = PlayerBetFlowMiddle::where('prefix',$value1->prefix)->where('day','>=',$startDay)->groupBy('player_id')->having(\DB::raw('sum(agent_process_available_bet_amount)'),'>=',$capitationFeeBetFlow)->pluck('player_id')->toArray();
                        if($capitationFeeSingleRechargeAmount >0){
                            $singleRechargePlayerIds = PlayerDepositPayLog::where('status',1)->where('prefix',$value1->prefix)->where('is_agent',0)->where('day','>=',$startDay)->where('amount','>=',$capitationFeeSingleRechargeAmount*10000)->pluck('player_id')->toArray();
                            $singleRechargePlayerIds = array_unique($singleRechargePlayerIds);

                            $depositPlayerIds = PlayerDepositPayLog::where('status',1)->where('prefix',$value1->prefix)->where('day','>=',$startDay)->whereIn('player_id',$singleRechargePlayerIds)->groupBy('player_id')->having(\DB::raw('sum(amount)'),'>=',$capitationFeeRechargeAmount*10000)->pluck('player_id')->toArray(); 
                        } else{
                            $depositPlayerIds = PlayerDepositPayLog::where('status',1)->where('prefix',$value1->prefix)->where('day','>=',$startDay)->groupBy('player_id')->having(\DB::raw('sum(amount)'),'>=',$capitationFeeRechargeAmount*10000)->pluck('player_id')->toArray(); 
                        }
                        
                    }

                    $deletePlayerIds  = [];

                    //首存不参与计算
                    if(!$isCapitationFirstDepositCalculate){
                        if(!$capitationFeeRule){
                            $playerDepositPayLogs = PlayerDepositPayLog::select(\DB::raw('sum(amount) as amount'),'player_id')->where('status',1)->where('prefix',$value1->prefix)->where('is_agent',0)->where('day','>=',$startDay)->groupBy('player_id')->having(\DB::raw('sum(amount)'),'>=',$capitationFeeRechargeAmount*10000)->get(); 
                        } else{
                            $playerDepositPayLogs = PlayerDepositPayLog::select(\DB::raw('sum(amount) as amount'),'player_id')->where('status',1)->where('prefix',$value1->prefix)->where('day','>=',$startDay)->groupBy('player_id')->having(\DB::raw('sum(amount)'),'>=',$capitationFeeRechargeAmount*10000)->get(); 
                        }
                        
                        foreach ($playerDepositPayLogs as $key2 => $value2) {
                            if($capitationFirstDepositCalculateActivityid >0){
                                $fistPlayerDepositPay = PlayerDepositPayLog::select('amount','player_id')->where('status',1)->where('player_id',$value2->player_id)->where('activityids',$capitationFirstDepositCalculateActivityid)->where('day','>=',$startDay)->orderBy('id','asc')->first();
                                if($fistPlayerDepositPay && $value2->amount - $fistPlayerDepositPay->amount < $capitationFeeRechargeAmount*10000){
                                    $deletePlayerIds[] = $value2->player_id;
                                } 
                            } else{
                                $fistPlayerDepositPay = PlayerDepositPayLog::select('amount','player_id')->where('status',1)->where('player_id',$value2->player_id)->where('day','>=',$startDay)->orderBy('id','asc')->first();
                                if($value2->amount - $fistPlayerDepositPay->amount < $capitationFeeRechargeAmount*10000){
                                    $deletePlayerIds[] = $value2->player_id;
                                }
                            }
                        }
                    } 
                    
                    if(count($deletePlayerIds)){
                        $depositPlayerIds = array_diff($depositPlayerIds, $deletePlayerIds);
                    }

                    $existPlayerIds   = PlayerCapitationFee::where('prefix',$value1->prefix)->pluck('player_id')->toArray();
                    $playerIds        = array_intersect($betFlowPlayerIds,$depositPlayerIds);
                    $playerIds        = array_diff($playerIds,$existPlayerIds);
                    $players          = Player::whereIn('player_id',$playerIds)->get();
                    $insertData       = [];

                    foreach ($players as $key2 => $value2) {
                        $playerDepositPayLog = PlayerDepositPayLog::where('player_id',$value2->player_id)->where('status',1)->where('day','>=',$startDay)->groupBy('day')->get();
                        if(count($playerDepositPayLog) >= $capitationFeeDepositDays){
                            $row                    = [];
                            $row['carrier_id']      = $value2->carrier_id;
                            $row['prefix']          = $value2->prefix;
                            $row['top_id']          = $value2->top_id;
                            $row['parent_id']       = $value2->parent_id;
                            $row['rid']             = $value2->rid;
                            $row['player_id']       = $value2->player_id;
                            $row['user_name']       = $value2->user_name;
                            $row['day']             = 0;
                            $row['amount']          = $capitationFeeGiftAmount;
                            $row['created_at']      = date('Y-m-d H:i:s');
                            $row['updated_at']      = date('Y-m-d H:i:s');
                            $row['status']          = $capitationFeeType ? 0:1;
                        
                            $insertData []          = $row;
                        }
                    }

                    \DB::table('log_player_capitation_fee')->insert($insertData);
                    
                }
            }

    }
}