<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportGamePlatStatDay;
use App\Models\Map\CarrierGamePlat;

class CreateGamePlatStatDayCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createGamePlatStatDay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'createGamePlatStatDay';

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
          $curr             = [];
          $carrierGamePlats = CarrierGamePlat::all();
          foreach ($carrierGamePlats as $k => $v) {
            $reportGamePlatStatDay = ReportGamePlatStatDay::where('main_game_plat_id',$v->game_plat_id)->where('day',date('Ymd',time()+86400))->first();
            if(!$reportGamePlatStatDay){
               $arr = [];
               $arr['carrier_id']                          = $v->carrier_id;
               $arr['main_game_plat_id']                   = $v->game_plat_id;
               $arr['day']                                 = date('Ymd',time()+86400);
               $arr['created_at']                          = date('Y-m-d H:i:s');
               $arr['updated_at']                          = date('Y-m-d H:i:s');
               $curr[]                                     = $arr;
            }
          }
          if(count($curr)){
            \DB::table('report_gameplat_stat_day')->insert($curr);
          } 
    }
}