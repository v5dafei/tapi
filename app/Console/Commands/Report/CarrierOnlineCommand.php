<?php

namespace App\Console\Commands\Report;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;

class CarrierOnlineCommand extends Command {
  
    protected $signature          = 'carrierOnline';

    protected $description        = 'carrierOnline';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $carrierIds = CarrierCache::getCarrierIds();
        $day        = date('Ymd');
        foreach ($carrierIds as $key => $value) {
            $onlineCount      = Player::where('carrier_id',$value)->whereIn('is_tester',[0,2])->where('is_online',1)->count();
            $maxCarrierOnline = cache()->get('carrierOnline_'.$value.'_'.$day,0);

            if($onlineCount > $maxCarrierOnline){
                cache()->put('carrierOnline_'.$value.'_'.$day, $onlineCount, now()->addDays(1));

                $defaultUserName = CarrierCache::getCarrierConfigure($value,'default_user_name');

                DB::update('update report_player_stat_day set online_amount='.$onlineCount.' where carrier_id='.$value.' and user_name="'.$defaultUserName.'" and day='.$day);
            }
        }
    }
}