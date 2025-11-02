<?php
namespace App\Observers;

use App\Lib\Cache\PlayerCache;
use App\Models\Report\ReportPlayerEarnings;

class ReportPlayerEarningsObserver
{
    public function created(ReportPlayerEarnings $reportPlayerEarnings)
    {
        
    }

    public function updated(ReportPlayerEarnings $reportPlayerEarnings)
    {
       if($reportPlayerEarnings->wasChanged('status') && $reportPlayerEarnings->status == 1){
            if(PlayerCache::getIswhetherRecharge($reportPlayerEarnings->player_id) == 0){
                PlayerCache::flushIswhetherRecharge($reportPlayerEarnings->player_id);
            }
       }
    }
}