<?php
namespace App\Observers;

use App\Models\Log\PlayerBetFlow;
use App\Lib\Cache\PlayerCache;

class PlayerBetFlowObserver
{
    public function created(PlayerBetFlow $playerBetFlow)
    {
        if($playerBetFlow->is_tester==0){
            $rid       = PlayerCache::getPlayerRid($playerBetFlow->carrier_id,$playerBetFlow->player_id);
            $playerIds = explode('|',$rid);

            foreach ($playerIds as $key => $value) {
                PlayerCache::createPlayerStatDay($value,date('Ymd'));
            }
        }
    }
}

