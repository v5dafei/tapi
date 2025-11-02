<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\PlayerLevel;

class UpdaePlayerGradeCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatePlayerGrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'updatePlayerGrade';

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
        $noUpdateLevelIds     = PlayerLevel::where('is_system',1)->where('is_default',0)->pluck('id')->toArray();

        $playerIds            = PlayerTransfer::where('type','recharge')->where('created_at','>=',date('Y-m-d H:i:s',time()-600))->pluck('player_id')->toArray();
        $playerIds            = array_unique($playerIds);

        $players               = Player::whereIn('player_id',$playerIds)->whereNotIn('player_group_id',$noUpdateLevelIds)->get();

        foreach ($players as $key => $value) {
            $systemPlayerLevelids = CarrierCache::getCarrierPlayerLevel(1,$value->prefix);
            $playerLevels         = CarrierCache::getCarrierPlayerLevel(2,$value->prefix);

            if(!in_array($value->player_group_id,$systemPlayerLevelids)){
                \Log::info('用户ID是'.$value->player_id.'原层级是'.$value->player_group_id);
                
                $playerTransfer             = PlayerTransfer::select(\DB::raw('sum(amount) as recharge_amount'),\DB::raw('count(amount) as recharge_count'),\DB::raw('max(amount) as maxRecharge'))->where('type','recharge')->where('player_id',$value->player_id)->first();

                foreach ($playerLevels as $key => $v) {
                    if($v->rechargenumber>0){
                        if($playerTransfer->recharge_count < $v->rechargenumber){
                            return;
                        }
                    }

                    if($v->accumulation_recharge>0){
                        if($playerTransfer->recharge_amount < $v->accumulation_recharge*10000){
                            return;
                        }
                    }

                    if($v->single_maximum_recharge>0){
                        if($playerTransfer->maxRecharge < $v->single_maximum_recharge*10000){
                            return;
                        }
                    }
            
                    $value->player_group_id = $v->id;
                    $value->save();
                }
            }
        }
    }
}