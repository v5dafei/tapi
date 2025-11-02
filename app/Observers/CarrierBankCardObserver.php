<?php
namespace App\Observers;

use App\Lib\Cache\CarrierCache;
use App\Models\CarrierBankCard;

class CarrierBankCardObserver
{
    public function created(CarrierBankCard $carrierBankCard)
    {
    }

    public function updated(CarrierBankCard $carrierBankCard)
    {
        CarrierCache::forgetCarrierBankCard($carrierBankCard->carrier_id,$carrierBankCard->id);
    }

    public function deleted(CarrierBankCard $carrierBankCard)
    {
        CarrierCache::forgetCarrierBankCard($carrierBankCard->carrier_id,$carrierBankCard->id);
    }
}

