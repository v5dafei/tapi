<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlayerRealCommission;
use App\Models\Log\PlayerRealCommissionTongbao;

class DeleteRealReturnCommissionCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleterealreturncommiss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'deleterealreturncommiss';

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
        PlayerRealCommissionTongbao::truncate();
        PlayerRealCommission::truncate();
    }
}