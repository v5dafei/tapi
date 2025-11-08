<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlayerTransfer;
use App\Models\Player;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;

class CreateCarrierStatDayCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createCarrierStatDay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'createCarrierStatDay';

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

      $playerRids           = Player::whereIn('is_tester',[0,2])->where('login_at','>=',date('Y-m-d').' 00:00:00')->pluck('rid')->toArray();
      $playerTransferIds    = PlayerTransfer::where('day',date('Ymd'))->pluck('rid')->toArray();
      $playerBetsflowIds    = PlayerBetFlowMiddle::where('day',date('Ymd'))->groupBy('rid')->pluck('rid')->toArray();

      $playerRids           = array_merge($playerRids,$playerBetsflowIds);
      $playerRids           = array_merge($playerRids,$playerTransferIds);
      $playerRids           = array_unique($playerRids);

      $allPlayerIds         = [];

      foreach ($playerRids as $key => $value) {
         $playerIds         = explode('|',$value);
         $allPlayerIds      = array_merge($allPlayerIds,$playerIds);
      }

      $allPlayerIds         = array_unique($allPlayerIds);
      $insertData           = [];

      foreach ($allPlayerIds as $key => $value) {
         if(!PlayerCache::getExistNextPlayerStatDay($value)){
            $player                               = Player::where('player_id',$value)->first();
            $row                                  = [];
            $row['carrier_id']                    = $player->carrier_id;
            $row['rid']                           = $player->rid;
            $row['top_id']                        = $player->top_id;
            $row['parent_id']                     = $player->parent_id;
            $row['player_id']                     = $player->player_id;
            $row['is_tester']                     = $player->is_tester;
            $row['user_name']                     = $player->user_name;
            $row['level']                         = $player->level;
            $row['type']                          = $player->type;
            $row['win_lose_agent']                = $player->win_lose_agent;
            $row['prefix']                        = $player->prefix;
            $row['day']                           = date('Ymd',strtotime('+1 day'));
            $row['month']                         = date('Ym',strtotime('+1 day'));
            $row['created_at']                    = date('Y-m-d H:i:s');
            $row['updated_at']                    = date('Y-m-d H:i:s');
            $insertData[]                         = $row;
         }
      }

      \DB::table('report_player_stat_day')->insert($insertData);
   }
}