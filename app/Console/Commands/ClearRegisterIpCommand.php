<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlayerBetflowCalculate;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierPreFixDomain;
use App\Models\Log\PlayerFingerprint;
use App\Models\Player;
use App\Models\Log\PlayerLogin;

class ClearRegisterIpCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clearregisterip';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clearregisterip';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $time      = date('YmdHi');
        $carrierPreFixDomains       = CarrierPreFixDomain::all();
        foreach ($carrierPreFixDomains as $k => $v) {
            $siteOnlineTime   = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'site_online_time',$v->prefix);
            if(!empty($siteOnlineTime) &&  date('YmdHi',strtotime($siteOnlineTime)) == $time){
                $playerIds =Player::where('prefix',$v->prefix)->pluck('player_id')->toArray();
                Player::whereIn('player_id',$playerIds)->update(['register_ip'=>'','login_ip'=>'']);
                PlayerLogin::whereIn('player_id',$playerIds)->delete();
                PlayerFingerprint::whereIn('player_id',$playerIds)->delete();
            }
        }
    }
}