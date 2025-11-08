<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Log\PlayerBetFlow;
use App\Jobs\UpdateVipJob;


class UpdaePlayerLevelCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatePlayerLevel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'updatePlayerLevel';

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

        //更新用户等级
       $playerIds      = PlayerBetFlow::where('created_at','>=',date('Y-m-d H:i:s',time()-600))->groupBy('player_id')->pluck('player_id')->toArray();

        dispatch(new UpdateVipJob(array_unique($playerIds)));
    }
}