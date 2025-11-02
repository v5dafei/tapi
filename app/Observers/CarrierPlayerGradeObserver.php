<?php
namespace App\Observers;

use App\Lib\Cache\PlayerCache;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierPlayerGrade;
use App\Models\Conf\PlayerSetting;
use App\Models\PlayerInviteCode;
use App\Models\Player;

class CarrierPlayerGradeObserver
{
    public function created(CarrierPlayerGrade $carrierPlayerGrade)
    {
        $this->updatePlayerSetting($carrierPlayerGrade);
    }

    public function updated(CarrierPlayerGrade $carrierPlayerGrade)
    {
       $this->updatePlayerSetting($carrierPlayerGrade);
    }

    public function deleted(CarrierPlayerGrade $carrierPlayerGrade)
    {
        $playerIds               = Player::where('carrier_id',$carrierPlayerGrade->carrier_id)->where('player_level_id',$carrierPlayerGrade->id)->pluck('player_id')->toArray();
        $prevCarrierPlayerGrade  = CarrierPlayerGrade::where('carrier_id',$carrierPlayerGrade->carrier_id)->where('prefix',$carrierPlayerGrade->prefix)->where('sort','<',$carrierPlayerGrade->sort)->orderBy('sort','desc')->first();

        Player::whereIn('player_id',$playerIds)->update(['player_level_id'=>$prevCarrierPlayerGrade->id]);

        $this->updatePlayerSetting($carrierPlayerGrade);
    }

    private function updatePlayerSetting(CarrierPlayerGrade $carrierPlayerGrade)
    {
        
    }
}

