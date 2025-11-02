<?php

namespace App\Http\Controllers\Carrier;


use App\Http\Controllers\Carrier\BaseController;
use Illuminate\Auth\Authenticatable;
use App\Models\CarrierNotice;
use App\Models\Player;
use App\Lib\Cache\CarrierCache;
use App\Models\Report\ReportPlayerStatDay;

class HomeController extends BaseController
{
    use Authenticatable;

    public function toolList()
    {
        $tools = config('main')['tools'];
        $data  = [];
        foreach ($tools as $key => $value) {
          $row          = [];
          $row['key']   = $key;
          $row['value'] = $value;
          $data[]       = $row;
        }

        return  returnApiJson('获取成功',1, $data);
    }

    public function lotterywebsiteList()
    {
        $websitelists = config('main')['lotterywebsites'];

         $data  = [];
        foreach ($websitelists as $key => $value) {
          $row          = [];
          $row['key']   = $key;
          $row['value'] = $value;
          $data[]       = $row;
        }
        return  returnApiJson('获取成功',1, $data);
    }

    public function statReport()
    {
        $input = request()->all();
        if(!isset($input['type']) || !in_array($input['type'], [1,2])){
           return  returnApiJson('对不起,类型取值不正确',0);
        }

        $playerIds       = Player::where('carrier_id',$this->carrier->id)->where('type',1)->where('is_tester',0)->pluck('player_id')->toArray();
        $defaultUserName = CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');


        if($input['type']==1){
          $data     = ReportPlayerStatDay::select('day',
            \DB::raw('sum(team_first_register) as team_first_register'),
            \DB::raw('sum(team_have_bet) as team_have_bet'),
            \DB::raw('sum(team_withdraw_person_number) as team_withdraw_person_number'),
            \DB::raw('sum(team_recharge_person_number) as team_recharge_person_number'),
            \DB::raw('sum(team_recharge_amount) as team_recharge_amount'),
            \DB::raw('sum(team_recharge_count) as team_recharge_count'),
            \DB::raw('sum(team_first_recharge_count) as team_first_recharge_count'),
            \DB::raw('sum(team_first_recharge_amount) as team_first_recharge_amount'),
            \DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),
            \DB::raw('sum(team_available_bets) as team_available_bets'),
            \DB::raw('sum(team_win_amount) as team_win_amount'),
            \DB::raw('sum(team_lottery_available_bets) as team_lottery_available_bets'),
            \DB::raw('sum(team_lottery_winorloss) as team_lottery_winorloss'),
            \DB::raw('sum(team_dividend) as team_dividend'),
            \DB::raw('sum(team_gift) as team_gift'))
            ->where('carrier_id',$this->carrier->id)
            ->whereIn('player_id',$playerIds)
            ->where('day','<=',date('Ymd'))
            ->groupBy('day')
            ->orderBy('day','asc')
            ->get();

        } else if($input['type']==2){
          $data     = ReportPlayerStatDay::select('month',
            \DB::raw('sum(team_first_register) as team_first_register'),
            \DB::raw('sum(team_withdraw_person_number) as team_withdraw_person_number'),
            \DB::raw('sum(team_recharge_person_number) as team_recharge_person_number'),
            \DB::raw('sum(team_have_bet) as team_have_bet'),
            \DB::raw('sum(team_recharge_amount) as team_recharge_amount'),
            \DB::raw('sum(team_recharge_count) as team_recharge_count'),
            \DB::raw('sum(team_first_recharge_count) as team_first_recharge_count'),
            \DB::raw('sum(team_first_recharge_amount) as team_first_recharge_amount'),
            \DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),
            \DB::raw('sum(team_available_bets) as team_available_bets'),
            \DB::raw('sum(team_win_amount) as team_win_amount'),
            \DB::raw('sum(team_lottery_available_bets) as team_lottery_available_bets'),
            \DB::raw('sum(team_lottery_winorloss) as team_lottery_winorloss'),
            \DB::raw('sum(team_dividend) as team_dividend'),
            \DB::raw('sum(team_gift) as team_gift'))
            ->where('carrier_id',$this->carrier->id)
            ->whereIn('player_id',$playerIds)
            ->where('day','<=',date('Ymd'))
            ->groupBy('month')
            ->orderBy('month','asc')
            ->get();
        }

        return  returnApiJson('获取成功',1,$data);
    }

}
