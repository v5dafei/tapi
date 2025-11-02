<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Carrier;
use App\Models\CarrierPreFixDomain;
use App\Lib\Cache\CarrierCache;
use App\Models\Conf\CarrierMultipleFront;


class UpdateRealTimeDividendsCommand extends Command {
  
    protected $signature          = 'updateRealTimeDividends';

    protected $description        = 'updateRealTimeDividends';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
           $carrierPreFixDomains = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
           foreach ($carrierPreFixDomains as $k => $v) {
               $playerRealtimeDividendsStartDay = CarrierCache::getCarrierMultipleConfigure($value->id,'player_realtime_dividends_start_day',$v->prefix);
               $playerDividendsStartDay         = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_start_day',$v->prefix);
               $playerDividendsDay              = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_day',$v->prefix);

               //1= 5天一结, 2=一周一结，3=3天一结，1=一天一结
               switch ($playerDividendsDay) {
                    case 1:
                       $time = 345600;
                       break;
                    case 2:
                       $time = 518400;
                       break;
                    case 3:
                       $time = 172800;
                       break;
                    case 4:
                       $time = 0;
                       break;
                   
                   default:
                       // code...
                       break;
               }
               if(in_array($playerDividendsDay,[1,2,3,4])){
                    if(date('Ymd',strtotime($playerDividendsStartDay)+ $time) <date('Ymd')){
                      CarrierMultipleFront::where('prefix',$v->prefix)->where('sign','player_realtime_dividends_start_day')->update(['value'=>date('Y-m-d')]);
                      CarrierCache::flushCarrierMultipleConfigure($v->carrier_id,$v->prefix);
                    }
               } elseif($playerDividendsDay==5 && (date('d')=='16' || date('d')=='01')){
                    CarrierMultipleFront::where('prefix',$v->prefix)->where('sign','player_realtime_dividends_start_day')->update(['value'=>date('Y-m-d')]);
                    CarrierCache::flushCarrierMultipleConfigure($v->carrier_id,$v->prefix);
               }
           }
        }
    }
}