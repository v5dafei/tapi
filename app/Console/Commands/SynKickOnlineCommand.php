<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Player;

class SynKickOnlineCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kickonline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'kickonline';

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
       $players   = Player::where('is_online',1)->get();
       $time      = time();
       $playerIds = [];

       foreach ($players as $key => $value) {
           if($time - $value->requesttime>1800){
                $playerIds[] = $value->player_id;
           }
       }

       if(count($playerIds)){
            Player::whereIn('player_id',$playerIds)->update(['is_online'=>0]);
       }
    }
}