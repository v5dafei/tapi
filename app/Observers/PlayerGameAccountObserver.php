<?php
namespace App\Observers;

use App\Lib\Cache\PlayerCache;
use App\Models\PlayerGameAccount;
use App\Models\PlayerAccount;
use App\Models\Report\ReportPlayerStatDay;

class PlayerGameAccountObserver
{
    public function created(PlayerGameAccount $playerGameAccount)
    {
    
    }

    public function updated(PlayerGameAccount $playerGameAccount)
    {
        //刷新余额时更新库存
        $playerAccount          = PlayerAccount::where('player_id',$playerGameAccount->player_id)->first();
        $selfstock              = $playerAccount->balance + $playerAccount->frozen + $playerAccount->agentbalance + $playerAccount->agentfrozen;
        $balance                = PlayerGameAccount::where('player_id',$playerGameAccount->player_id)->sum('balance');
        $selfstock              = $selfstock + $balance*10000;
        $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$playerGameAccount->player_id)->where('day',date('Ymd',strtotime('-1 day')))->first();

        if($preReportPlayerStatDay){
            $changeSelfStock = $selfstock - $preReportPlayerStatDay->self_stock;
            ReportPlayerStatDay::where('player_id',$playerGameAccount->player_id)->where('day',date('Ymd'))->update(['self_stock'=>$selfstock,'change_self_stock'=>$changeSelfStock]);
        } else{
            ReportPlayerStatDay::where('player_id',$playerGameAccount->player_id)->where('day',date('Ymd'))->update(['self_stock'=>$selfstock,'change_self_stock'=>$selfstock]);
        }
    }

    public function deleted(PlayerGameAccount $playerGameAccount)
    {
        PlayerCache::forgetPlayerIdforaccountUserName($playerGameAccount->main_game_plat_code,$playerGameAccount->account_user_name);
    }
}

