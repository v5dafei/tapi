<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Models\Log\RankingList;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierPreFixDomain;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\PlayerGameAccount;
use App\Lib\Cache\Lock;
use App\Lib\Clog;

class StatSiteStockCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statsitestock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'statsitestock';

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
        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains             = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $agentSingleBackground = CarrierCache::getCarrierMultipleConfigure($value->id,'agent_single_background',$value1->prefix);
                $amount                = 0;

                if($agentSingleBackground==1){
                    $playerIds      = Player::where('prefix',$value1->prefix)->where('win_lose_agent',0)->pluck('player_id')->toArray();
                    $playerAccounts = PlayerAccount::where('prefix',$value1->prefix)->whereIn('player_id',$playerIds)->get();
                    foreach ($playerAccounts as $key2 => $value2) {
                        $amount = $value2->balance + $value2->frozen + $value2->agentbalance+ $value2->agentfrozen + $amount;
                    }
                } else{
                    $playerAccounts = PlayerAccount::where('prefix',$value1->prefix)->get();
                    foreach ($playerAccounts as $key2 => $value2) {
                        $amount = $value2->balance + $value2->frozen + $value2->agentbalance+ $value2->agentfrozen + $amount;
                    }
                }

                $balance = PlayerGameAccount::where('prefix',$value1->prefix)->sum('balance');
                $amount  = bcdiv($amount, 10000,2) + $balance;
                CarrierMultipleFront::where('prefix',$value1->prefix)->where('sign','site_stock')->update(['value'=>$amount]);
                CarrierCache::flushCarrierMultipleConfigure($value->id,$value1->prefix);
            }
        }
    }
}