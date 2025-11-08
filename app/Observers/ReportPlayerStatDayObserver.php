<?php
namespace App\Observers;

use App\Lib\Cache\CarrierCache;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\PlayerLevel;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Player;


class ReportPlayerStatDayObserver
{
    public function created(ReportPlayerStatDay $reportPlayerStatDay)
    {
    
    }

    public function updated(ReportPlayerStatDay $reportPlayerStatDay)
    {
        $systemPlayerLevelids = CarrierCache::getCarrierPlayerLevel(1,$reportPlayerStatDay->prefix);
        $player               = Player::where('player_id',$reportPlayerStatDay->player_id)->first();
        $flag                 = 0;

        if(!in_array($player->player_group_id,$systemPlayerLevelids)){
            $playerLevels               = CarrierCache::getCarrierPlayerLevel(2,$reportPlayerStatDay->prefix);
            $statReportPlayerStatDay    = ReportPlayerStatDay::select(\DB::raw('sum(recharge_count) as recharge_count'),\DB::raw('sum(recharge_amount) as recharge_amount'))->where('player_id',$player->player_id)->first();
            $maxRecharge                = PlayerDepositPayLog::where('player_id',$player->player_id)->where('status',1)->max('amount');
            foreach ($playerLevels as $key => $v) {
                if($v->rechargenumber>0){
                    if($statReportPlayerStatDay->recharge_count < $v->rechargenumber){
                        return;
                    }
                }

                if($v->accumulation_recharge>0){
                    if($statReportPlayerStatDay->recharge_amount < $v->accumulation_recharge*10000){
                        return;
                    }
                }

                if($v->single_maximum_recharge>0){
                    if($maxRecharge < $v->single_maximum_recharge*10000){
                        return;
                    }
                }

                $firstPlayerLevel = $playerLevels[0];
                if($player->player_group_id == -1){

                    $player->player_group_id = $firstPlayerLevel->id;
                    $flag                    = 1;
                } else{
                    $player->player_group_id = $v->id;
                    $flag                    = 1;
                }
            }
        }

        //更新下级人数
        if($flag == 1){
            $player->save();
        }
    }

    public function deleted(ReportPlayerStatDay $reportPlayerStatDay)
    {
        
    }
}