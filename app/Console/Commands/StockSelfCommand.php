<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Models\CarrierPreFixDomain;
use App\Models\Player;
use App\Models\PlayerGameAccount;
use App\Lib\Cache\CarrierCache;
use App\Lib\DevidendMode2;
use App\Lib\DevidendMode3;
use App\Lib\DevidendMode5;
use App\Jobs\SynGameAccountJob;


class StockSelfCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'StockSelf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stock Self';


    //重置用户数据库
    public   $deleteall = false;
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
        $playerGameAccounts           = PlayerGameAccount::where('exist_transfer',1)->where('updated_at','>=',date('Y-m-d H:i:s',strtotime('-1 hour')))->orderBy('updated_at','desc')->groupBy('player_id')->get();
        //$playerGameAccounts           = PlayerGameAccount::where('exist_transfer',1)->whereIn('player_id',$playerIds)->orderBy('updated_at','desc')->groupBy('player_id')->get();
        dispatch(new SynGameAccountJob($playerGameAccounts));
    }
}