<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerTransfer;
use App\Models\Player;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\Carrier;
use App\Models\CarrierPreFixDomain;
use App\Models\Log\PlayerDepositPayLog;


class StatRegisterCodeCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statregistercode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'statregistercode';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $playerId  = '';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $playerIds = PlayerDepositPayLog::where('carrier_id',$value->id)->where('prefix',$value1->prefix)->where('status',1)->where('review_time','>=',time()-600)->where('is_first_recharge',1)->pluck('player_id')->toArray();
                $count     = PlayerTransfer::whereIn('player_id',$playerIds)->where('type','register_gift')->count();
                if($count >0){
                    $registerCodeRecharge = CarrierCache::getCarrierMultipleConfigure($value->id,'register_code_recharge',$value1->prefix);
                    CarrierMultipleFront::where('carrier_id',$value->id)->where('prefix',$value1->prefix)->where('sign','register_code_recharge')->update(['value'=> $registerCodeRecharge + $count]);
                    CarrierCache::flushCarrierMultipleConfigure($value->id,$value1->prefix);
                }
            }
        }
    }
}