<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierPreFixDomain;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerWithdraw;
use App\Models\PlayerTransfer;

class UpdateRewardRateCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updaterewardrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'updaterewardrate';

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
        $weeks               = getWeekStartEnd();
        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
            $arr = CarrierMultipleFront::where('carrier_id',$value->id)->where('sign','player_dividends_method')->where('value',4)->pluck('prefix')->toArray();

            $carrierPreFixDomains             = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $k1 => $v1) {
                if(!in_array($v1->prefix,$arr)){
                    //结算周期
                    $playerRealtimeDividendsStartDay = CarrierCache::getCarrierMultipleConfigure($value->id,'player_realtime_dividends_start_day',$v1->prefix);

                    $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->where('prefix',$v1->prefix)->first();
                    if($playerBetFlowMiddle && !is_null($playerBetFlowMiddle->available_bet_amount) && $playerBetFlowMiddle->available_bet_amount > 0){
                        CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('sign','replace_curr_cw_rate')->update(['value'=>bcdiv($playerBetFlowMiddle->available_bet_amount-$playerBetFlowMiddle->company_win_amount, $playerBetFlowMiddle->available_bet_amount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('sign','replace_curr_cw_rate')->update(['value'=>0]);
                    }

                    //变更今日返奖率
                    $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->where('prefix',$v1->prefix)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->first();
                    if($playerBetFlowMiddle && !is_null($playerBetFlowMiddle->available_bet_amount) && $playerBetFlowMiddle->available_bet_amount >0 ){
                        CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('sign','replace_today_curr_cw_rate')->update(['value'=>bcdiv($playerBetFlowMiddle->available_bet_amount-$playerBetFlowMiddle->company_win_amount, $playerBetFlowMiddle->available_bet_amount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('sign','replace_today_curr_cw_rate')->update(['value'=>0]);
                    }

                    //更新pg返奖率             
                    $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->where('main_game_plat_id',17)->whereIn('prefix',$arr)->first();
                    if($playerBetFlowMiddle && !is_null($playerBetFlowMiddle->available_bet_amount) && $playerBetFlowMiddle->available_bet_amount >0){
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','pg_replace_curr_cw_rate')->update(['value'=>bcdiv($playerBetFlowMiddle->available_bet_amount-$playerBetFlowMiddle->company_win_amount, $playerBetFlowMiddle->available_bet_amount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','pg_replace_curr_cw_rate')->update(['value'=>0]);
                    }


                    //变更今日返奖率
                    $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->where('main_game_plat_id',17)->whereIn('prefix',$arr)->where('day','>=',$weeks[2])->first();
                    if($playerBetFlowMiddle && $playerBetFlowMiddle->available_bet_amount ){
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','pg_replace_today_curr_cw_rate')->update(['value'=>bcdiv($playerBetFlowMiddle->available_bet_amount-$playerBetFlowMiddle->company_win_amount, $playerBetFlowMiddle->available_bet_amount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','pg_replace_today_curr_cw_rate')->update(['value'=>0]);
                    }

                    //显示出款比
                    $rechargeAmount = PlayerDepositPayLog::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('status',1)->sum('amount');
                    $withdrawAmount = PlayerWithdraw::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->whereIn('status',[1,2])->sum('amount');

                    if($rechargeAmount){
                        CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('sign','recharge_withdraw_proportion')->update(['value'=>bcdiv($withdrawAmount,$rechargeAmount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('sign','recharge_withdraw_proportion')->update(['value'=>0]);
                    }

                    $rechargeCycleAmount = PlayerDepositPayLog::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('status',1)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->sum('amount');
                    $withdrawCycleAmount = PlayerWithdraw::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->whereIn('status',[1,2])->where('created_at','>=',$playerRealtimeDividendsStartDay.' 00:00:00')->sum('amount');

                    if($rechargeCycleAmount){
                        CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('sign','cycle_recharge_withdraw_proportion')->update(['value'=>bcdiv($withdrawCycleAmount,$rechargeCycleAmount, 4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$v1->prefix)->where('sign','cycle_recharge_withdraw_proportion')->update(['value'=>0]);
                    }

                } 
                CarrierCache::flushCarrierMultipleConfigure($value->id,$v1->prefix);
            }

            if(count($arr)){
                
                $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->whereIn('prefix',$arr)->first();
                    if($playerBetFlowMiddle && !is_null($playerBetFlowMiddle->available_bet_amount) && $playerBetFlowMiddle->available_bet_amount >0){
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','replace_curr_cw_rate')->update(['value'=>bcdiv($playerBetFlowMiddle->available_bet_amount-$playerBetFlowMiddle->company_win_amount, $playerBetFlowMiddle->available_bet_amount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','replace_curr_cw_rate')->update(['value'=>0]);
                    }


                    //变更今日返奖率
                    $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->whereIn('prefix',$arr)->where('day','>=',$weeks[2])->first();
                    if($playerBetFlowMiddle && $playerBetFlowMiddle->available_bet_amount ){
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','replace_today_curr_cw_rate')->update(['value'=>bcdiv($playerBetFlowMiddle->available_bet_amount-$playerBetFlowMiddle->company_win_amount, $playerBetFlowMiddle->available_bet_amount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','replace_today_curr_cw_rate')->update(['value'=>0]);
                    }

                    //更新pg返奖率             
                    $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->where('main_game_plat_id',17)->whereIn('prefix',$arr)->first();
                    if($playerBetFlowMiddle && !is_null($playerBetFlowMiddle->available_bet_amount) && $playerBetFlowMiddle->available_bet_amount >0){
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','pg_replace_curr_cw_rate')->update(['value'=>bcdiv($playerBetFlowMiddle->available_bet_amount-$playerBetFlowMiddle->company_win_amount, $playerBetFlowMiddle->available_bet_amount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','pg_replace_curr_cw_rate')->update(['value'=>0]);
                    }


                    //变更今日返奖率
                    $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->where('main_game_plat_id',17)->whereIn('prefix',$arr)->where('day','>=',$weeks[2])->first();
                    if($playerBetFlowMiddle && $playerBetFlowMiddle->available_bet_amount ){
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','pg_replace_today_curr_cw_rate')->update(['value'=>bcdiv($playerBetFlowMiddle->available_bet_amount-$playerBetFlowMiddle->company_win_amount, $playerBetFlowMiddle->available_bet_amount,4)*100]);
                    } else{
                        CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','pg_replace_today_curr_cw_rate')->update(['value'=>0]);
                    }


                //显示出款比
                $rechargeAmount = PlayerDepositPayLog::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('status',1)->sum('amount');
                $withdrawAmount = PlayerWithdraw::where('carrier_id',$value->id)->whereIn('prefix',$arr)->whereIn('status',[1,2])->sum('amount');

                if($rechargeAmount){
                    CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','recharge_withdraw_proportion')->update(['value'=>bcdiv($withdrawAmount,$rechargeAmount,4)*100]);
                } else{
                    CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','recharge_withdraw_proportion')->update(['value'=>0]);
                }

                $rechargeCycleAmount = PlayerDepositPayLog::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('status',1)->where('day','>=',$weeks[2])->sum('amount');
                $withdrawCycleAmount = PlayerWithdraw::where('carrier_id',$value->id)->whereIn('prefix',$arr)->whereIn('status',[1,2])->where('created_at','>=',$weeks[0])->sum('amount');

                if($rechargeCycleAmount){
                    CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','cycle_recharge_withdraw_proportion')->update(['value'=>bcdiv($withdrawCycleAmount,$rechargeCycleAmount, 4)*100]);
                } else{
                    CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$arr)->where('sign','cycle_recharge_withdraw_proportion')->update(['value'=>0]);
                }

                foreach ($arr as $k2 => $v2) {
                    CarrierCache::flushCarrierMultipleConfigure($value->id,$v2);
                }
            }
        }
    }
}