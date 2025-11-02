<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SynGamePlatBalanceJob;

class QueryGameBalanceCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'querybalance {mainGamePlatCode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'querybalance';

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
        $mainGamePlatCode   = $this->argument('mainGamePlatCode');
        \Log::info('查询的平台是'.$mainGamePlatCode);
        dispatch(new SynGamePlatBalanceJob($mainGamePlatCode));
    }
}