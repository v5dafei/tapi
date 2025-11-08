<?php
namespace App\Observers;

use App\Lib\Cache\CarrierCache;
use App\Models\Conf\CarrierPayChannel;

class CarrierPayChannelObserver
{
    public function created(CarrierPayChannel $carrierPayChannel)
    {
    }

    public function updated(CarrierPayChannel $carrierPayChannel)
    {
        CarrierCache::forgetCarrierPayChannel($carrierPayChannel->carrier_id,$carrierPayChannel->id);
    }

    public function deleted(CarrierPayChannel $carrierPayChannel)
    {
        CarrierCache::forgetCarrierPayChannel($carrierPayChannel->carrier_id,$carrierPayChannel->id);
    }
}

