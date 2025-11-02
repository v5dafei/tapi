<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Player;

class DeleteUnipayOrderCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteunipayorder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deleteunipayorder';

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
           $delunpaidday = CarrierCache::getCarrierConfigure($value->id,'delunpaidday');
           if($delunpaidday){

                PlayerDepositPayLog::where('carrier_id',$value->id)->where('status',0)->where('created_at','<=',date('Y-m-d H:i:s',time()-3600*$delunpaidday))->delete();
           }
       }
    }
}