<?php
namespace App\Observers;

use App\Models\PlayerMessage;
use App\Lib\Cache\PlayerCache;

class PlayerMessageObserver
{
    public function created(PlayerMessage $playerMessage)
    {
        PlayerCache::forgetUnreadMessageNumber($playerMessage->carrier_id,$playerMessage->player_id);
    }

    public function updated(PlayerMessage $playerMessage)
    {
       PlayerCache::forgetUnreadMessageNumber($playerMessage->carrier_id,$playerMessage->player_id);
    }

    public function deleted(PlayerMessage $playerMessage)
    {
       
    }
}

