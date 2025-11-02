<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlayerBetflowCalculate;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierPreFixDomain;

class ClearIntelligentFlowCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clearintelligentFlow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clearintelligentFlow';

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
        $day      = date('Ymd');
        $carrierPreFixDomains       = CarrierPreFixDomain::all();

        foreach ($carrierPreFixDomains as $k => $v) {
            $clearIntelligentFlowType   = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'clear_intelligent_flow_type',$v->prefix);

            if($clearIntelligentFlowType){
                GameCache::flushBetflowCalculate($v->carrier_id,$v->prefix);
            }
        }
    }
}