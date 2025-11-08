<?php
namespace App\Observers;

use App\Lib\Cache\PlayerCache;
use App\Models\PlayerCommission;

class PlayerCommissionObserver
{
    public function created(PlayerCommission $playerCommission)
    {
       
    }

    public function updated(PlayerCommission $playerCommission)
    {
       if($playerCommission->wasChanged('status') && $playerCommission->status == 1){
            if(PlayerCache::getIswhetherRecharge($playerCommission->player_id) == 0){
                PlayerCache::flushIswhetherRecharge($playerCommission->player_id);
            }
       }
    }
}