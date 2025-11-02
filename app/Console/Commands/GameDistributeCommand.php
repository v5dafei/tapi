<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Map\CarrierGamePlat;
use App\Models\Map\CarrierGame;
use App\Models\Def\Game;

class GameDistributeCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamedistribute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'gamedistribute';

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
        if(!config('main')['is_live_streaming']){
            $carrierGamePlats = CarrierGamePlat::all();
            $data             = [];
            foreach ($carrierGamePlats as $key => $value) {
                $existGameIds = CarrierGame::where('game_plat_id',$value->game_plat_id)->pluck('game_id')->toArray();
                $allGameIds   = Game::where('main_game_plat_id',$value->game_plat_id)->pluck('game_id')->toArray();
                $addGameIds   = array_diff($allGameIds, $existGameIds);
                $addGameIds   = array_unique($addGameIds);
                $addGames     = Game::whereIn('game_id',$addGameIds)->get();
                
                foreach ($addGames as $k => $v) {
                    $row                      = [];
                    $row['carrier_id']        = $value->carrier_id;
                    $row['game_plat_id']      = $value->game_plat_id;
                    $row['game_id']           = $v->game_id;
                    $row['display_name']      = $v->game_name;
                    $row['game_category']     = $v->game_category;
                    $row['created_at']        = date('Y-m-d H:i:s');
                    $row['updated_at']        = date('Y-m-d H:i:s');
                    
                    //LGD不添加
                    if($value->game_plat_id !=37 ){
                        $data[]                   = $row; 
                    }           
                }
            }

            if(count($data)){
                \DB::table('map_carrier_games')->insert($data);
            }
        }
    }
}