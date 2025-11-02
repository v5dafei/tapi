<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Models\Log\RankingList;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierPreFixDomain;
use App\Lib\Cache\PlayerCache;
use App\Models\PlayerGameAccount;
use App\Jobs\AutoTransferToGameAccountJob;

class AutoTransferToCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autoTransferTo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'autoTransferTo';

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
        $startTime = time()-3600;
        $endTime   = time()-3540;

        $playerGameAccounts = PlayerGameAccount::where('balance','>',1)->where('exist_transfer',1)->where('updated_at','>=',date('Y-m-d H:i:s',$startTime))->where('updated_at','<=',date('Y-m-d H:i:s',$endTime))->get();
        if($playerGameAccounts){
            dispatch(new AutoTransferToGameAccountJob($playerGameAccounts));
        }
    }
}