<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Models\CarrierPlayerGrade;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\CarrierPreFixDomain;

class BirthLevelGiftCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthlevelgift';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'birthlevelgift';

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
        $reduceThirdMonth = date('Y-m-d',strtotime("-3 month"));
        $birth            = date('m-d');
        $endTime          = strtotime(date('Y-m-d').' 23:59:59');
        $insertData       = [];

        $carrierPreFixDomains = CarrierPreFixDomain::all();
        foreach ($carrierPreFixDomains as $key1 => $value1) {
            $data                  = [];
            $carrierPlayerGradeIds = [];
            $carrierPlayerGrades   = CarrierPlayerGrade::where('prefix',$value1->prefix)->get();
            foreach ($carrierPlayerGrades as $k => $v) {
                $data[$v->id] = $v->birthgift;
                if($v->birthgift>0){
                    $carrierPlayerGradeIds[] = $v->id;
                }
            }

            $players = Player::where('prefix',$value1->prefix)->whereIn('player_level_id',$carrierPlayerGradeIds)->where('birthday','like','%-'.$birth)->where('created_at','<=',$reduceThirdMonth.' 00:00:00')->get();
                
            foreach ($players as $k => $v) {
                $row                                         = [];
                $row['orderid']                              = 'LJ'.$v->player_id.time().rand('1','99');
                $row['carrier_id']                           = $v->carrier_id;
                $row['player_id']                            = $v->player_id;
                $row['user_name']                            = $v->user_name;
                $row['top_id']                               = $v->top_id;
                $row['parent_id']                            = $v->parent_id;
                $row['rid']                                  = $v->rid;
                $row['type']                                 = 38;
                $row['remark']                               = date('Ymd');
                $row['amount']                               = $data[$v->player_level_id]*10000;
                $row['invalidtime']                          = $endTime;
                $row['limitbetflow']                         = $data[$v->player_level_id]*10000;
                $row['created_at']                           = date('Y-m-d H:i:s');
                $row['updated_at']                           = date('Y-m-d H:i:s');

                $insertData[]                                = $row;
                if(count($insertData)==1000){
                    \DB::table('inf_player_receive_gift_center')->insert($insertData);
                    $insertData   = [];
                }
            }

            if(count($insertData)){
                \DB::table('inf_player_receive_gift_center')->insert($insertData);
                $insertData   = [];
            }
        }
    }
}