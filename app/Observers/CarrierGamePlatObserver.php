<?php
namespace App\Observers;

use App\Lib\Cache\SystemCache;
use App\Models\Def\PayChannel;
use App\Models\Map\CarrierGamePlat;
use App\Models\Report\ReportGamePlatStatDay;
use App\Models\Def\Game;

class CarrierGamePlatObserver
{
    public function created(CarrierGamePlat $carrierGamePlat)
    {
       $reportGamePlatStatDay = ReportGamePlatStatDay::where('carrier_id',$carrierGamePlat->carrier_id)->where('main_game_plat_id',$carrierGamePlat->game_plat_id)->where('day',date('Ymd',time()))->first();
       
       if(!$reportGamePlatStatDay){
            $curr = [];
            $arr  = [];
            $arr['carrier_id']                          = $carrierGamePlat->carrier_id;
            $arr['main_game_plat_id']                   = $carrierGamePlat->game_plat_id;
            $arr['day']                                 = date('Ymd',time());
            $arr['created_at']                          = date('Y-m-d H:i:s');
            $arr['updated_at']                          = date('Y-m-d H:i:s');
            $curr[]                                     = $arr;

            $arr['carrier_id']                          = $carrierGamePlat->carrier_id;
            $arr['main_game_plat_id']                   = $carrierGamePlat->game_plat_id;
            $arr['day']                                 = date('Ymd',time()+86400);
            $arr['created_at']                          = date('Y-m-d H:i:s');
            $arr['updated_at']                          = date('Y-m-d H:i:s');
            $curr[]                                     = $arr;

            \DB::table('report_gameplat_stat_day')->insert($curr); 
       }
    }
}

