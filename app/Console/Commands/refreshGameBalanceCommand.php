<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Log\PlayerBetFlow;
use App\Lib\Clog;
use App\Jobs\SynBalanceJob;

class refreshGameBalanceCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refreshGameBalance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'refreshGameBalance';

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
        $accountUserNames = PlayerBetFlow::where('created_at','>=',date('Y-m-d H:i:s',time()-900))->pluck('account_user_name')->unique()->toArray();
        dispatch(new SynBalanceJob($accountUserNames));
    }
}