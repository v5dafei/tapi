<?php
namespace App\Observers;

use App\Lib\Cache\PlayerCache;
use App\Models\Conf\PlayerSetting;

class PlayerSettingObserver
{
    public function created(PlayerSetting $playerSetting)
    {
    
    }

    public function updated(PlayerSetting $playerSetting)
    {
        PlayerCache::forgetPlayerSetting($playerSetting->player_id);
    }

    public function deleted(PlayerSetting $playerSetting)
    {
        PlayerCache::forgetPlayerSetting($playerSetting->player_id);
    }
}

