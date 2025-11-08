<?php
namespace App\Observers;

use App\Models\PlayerAccount;
use App\Lib\Cache\PlayerCache;
use App\Jobs\SynGameAccountJob;
use App\Models\PlayerGameAccount;
use App\Models\Report\ReportPlayerStatDay;

class PlayerAccountObserver
{
    public function created(PlayerAccount $playerAccount)
    {

    }

    public function updated(PlayerAccount $playerAccount)
    {
        if($playerAccount->is_tester==0){
            $playerIds = explode('|',$playerAccount->rid);
            foreach ($playerIds as $key => $value) {
                PlayerCache::createPlayerStatDay($value,date('Ymd'));
            }

            //开始更新库存
            $playerGameAccounts           = PlayerGameAccount::where('exist_transfer',1)->where('player_id',$playerAccount->player_id)->orderBy('updated_at','desc')->groupBy('player_id')->get();
            if(count($playerGameAccounts)>0){
                dispatch(new SynGameAccountJob($playerGameAccounts));
            } else{
                $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$playerAccount->player_id)->where('day',date('Ymd',strtotime('-1 day')))->first();
                $selfstock              = $playerAccount->balance + $playerAccount->frozen + $playerAccount->agentbalance + $playerAccount->agentfrozen;

                if($preReportPlayerStatDay){
                    $changeSelfStock = $selfstock - $preReportPlayerStatDay->self_stock;
                    ReportPlayerStatDay::where('player_id',$playerAccount->player_id)->where('day',date('Ymd'))->update(['self_stock'=>$selfstock,'change_self_stock'=>$changeSelfStock]);
                } else{
                    ReportPlayerStatDay::where('player_id',$playerAccount->player_id)->where('day',date('Ymd'))->update(['self_stock'=>$selfstock,'change_self_stock'=>$selfstock]);
                }
            }
        }
    }
}

