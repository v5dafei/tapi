<?php
namespace App\Observers;

use App\Models\Map\CarrierGame;
use App\Lib\Cache\GameCache;

class CarrierGameObserver
{
    public function created(CarrierGame $carrierGame)
    {
    	GameCache::flushCarrierGame($carrierGame->carrier_id);
        GameCache::flushPlatList($carrierGame->carrier_id);
    }

    public function updated(CarrierGame $carrierGame)
    {
        GameCache::flushCarrierGame($carrierGame->carrier_id);
        GameCache::flushPlatList($carrierGame->carrier_id);
    }

    public function deleted(CarrierGame $carrierGame)
    {
        GameCache::flushCarrierGame($carrierGame->carrier_id);
        GameCache::flushPlatList($carrierGame->carrier_id);
    }
}

