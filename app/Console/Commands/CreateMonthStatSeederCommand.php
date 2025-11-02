<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Report\ReportCarrierMonthStat;
use App\Models\Carrier;
use App\Models\Player;


class CreateMonthStatSeederCommand extends Command {

    protected $signature = 'createMonthStat';

    protected $description = 'createMonthStat';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
       $carriers      = Carrier::all();
       $last          = strtotime("-1 month",time());
       $last_lastday  = date('Y-m-t',$last);
       $last_firstday = date('Y-m-01',$last);


       foreach ($carriers as $key => $value) {
          $playerIds    = Player::where('carrier_id',$value->id)->where('level',2)->where('is_tester',0)->where('is_live_streaming_account',0)->pluck('player_id')->toArray();
          $monthStats    = ReportPlayerStatDay::select('prefix',
            \DB::raw('sum(team_first_register) as team_first_register'),
            \DB::raw('sum(team_have_bet) as team_have_bet'),
            \DB::raw('sum(team_recharge_amount) as team_recharge_amount'),
            \DB::raw('sum(team_recharge_count) as team_recharge_count'),
            \DB::raw('sum(team_first_recharge_count) as team_first_recharge_count'),
            \DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),
            \DB::raw('sum(team_available_bets) as team_available_bets'),
            \DB::raw('sum(team_win_amount) as team_win_amount'),
            \DB::raw('sum(team_lottery_available_bets) as team_lottery_available_bets'),
            \DB::raw('sum(team_lottery_winorloss) as team_lottery_winorloss'),
            \DB::raw('sum(team_dividend) as team_dividend'),
            \DB::raw('sum(team_gift) as team_gift'))
            ->where('carrier_id',$value->id)
            ->whereIn('player_id',$playerIds)
            ->where('day','>=',date('Ymd',strtotime($last_firstday.' 23:59:59')))
            ->where('day','<=',date('Ymd',strtotime($last_lastday.' 23:59:59')))
            ->groupBy('prefix')
            ->get();

            $exist = ReportCarrierMonthStat::where('carrier_id',$value->id)->where('day_m',date('Ym',$last))->first();

            if(!$exist){

               foreach ($monthStats as $key => $monthStat) {
                  $reportCarrierMonthStat                             = new ReportCarrierMonthStat();
                  $reportCarrierMonthStat->carrier_id                 = $value->id;
                  $reportCarrierMonthStat->team_available_bets        = is_null($monthStat->team_available_bets) ? 0 : $monthStat->team_available_bets;
                  $reportCarrierMonthStat->team_dividend              = is_null($monthStat->team_dividend) ? 0 : $monthStat->team_dividend;
                  $reportCarrierMonthStat->team_first_recharge_count  = is_null($monthStat->team_first_recharge_count) ? 0 : $monthStat->team_first_recharge_count;
                  $reportCarrierMonthStat->team_first_register        = is_null($monthStat->team_first_register) ? 0 : $monthStat->team_first_register;
                  $reportCarrierMonthStat->team_gift                  = is_null($monthStat->team_gift) ? 0 : $monthStat->team_gift ;
                  $reportCarrierMonthStat->team_have_bet              = is_null($monthStat->team_have_bet) ? 0 : $monthStat->team_have_bet;
                  $reportCarrierMonthStat->team_lottery_available_bets= is_null($monthStat->team_lottery_available_bets) ? 0 : $monthStat->team_lottery_available_bets;
                  $reportCarrierMonthStat->team_lottery_winorloss     = is_null($monthStat->team_lottery_winorloss) ? 0 : $monthStat->team_lottery_winorloss;
                  $reportCarrierMonthStat->team_recharge_amount       = is_null($monthStat->team_recharge_amount) ? 0 : $monthStat->team_recharge_amount ;
                  $reportCarrierMonthStat->team_recharge_count        = is_null($monthStat->team_recharge_count) ? 0 : $monthStat->team_recharge_count;
                  $reportCarrierMonthStat->team_win_amount            = is_null($monthStat->team_win_amount) ? 0 : $monthStat->team_win_amount;
                  $reportCarrierMonthStat->team_withdraw_amount       = is_null($monthStat->team_withdraw_amount) ? 0 : $monthStat->team_withdraw_amount;
                  $reportCarrierMonthStat->day_m                      = date('Ym',$last);
                  $reportCarrierMonthStat->prefix                     = $monthStat->prefix;
                  $reportCarrierMonthStat->save();
               }
            }
       }

       return true;
    }
}