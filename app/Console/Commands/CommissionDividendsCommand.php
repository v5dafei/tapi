<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Models\PlayerCommission;
use App\Models\CarrierPreFixDomain;


//直属充值分红
class CommissionDividendsCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commissiondividends';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'commissiondividends';

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
        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains             = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $k => $v) {
                $directlyunderCommissionDividendsRate = CarrierCache::getCarrierMultipleConfigure($value->id,'directlyunder_commission_dividends_rate',$v->prefix);
                if($directlyunderCommissionDividendsRate > 0){
                    $playCommissions = PlayerCommission::where('prefix',$v->prefix)->where('day',date('Ymd',strtotime('-1 day')))->get();
                    $insertData = [];
                    foreach ($playCommissions as $k1 => $v1) {
                        $amount                                      = $v1->directlyunder_casino_commission + $v1->directlyunder_electronic_commission+ $v1->directlyunder_esport_commission+ $v1->directlyunder_fish_commission+ $v1->directlyunder_card_commission+ $v1->directlyunder_sport_commission+ $v1->directlyunder_lottery_commission;

                        $row                                         = [];
                        $row['orderid']                              = 'LJ'.$v1->player_id.time().rand('1','99');
                        $row['carrier_id']                           = $v1->carrier_id;
                        $row['player_id']                            = $v1->player_id;
                        $row['user_name']                            = $v1->user_name;
                        $row['top_id']                               = $v1->top_id;
                        $row['parent_id']                            = $v1->parent_id;
                        $row['rid']                                  = $v1->rid;
                        $row['type']                                 = 47;
                        $row['remark']                               = date('Ymd');
                        $row['amount']                               = bcdiv($amount*$directlyunderCommissionDividendsRate,100,0);
                        $row['invalidtime']                          = strtotime('+30 days');
                        $row['limitbetflow']                         = 0;
                        $row['created_at']                           = date('Y-m-d H:i:s');
                        $row['updated_at']                           = date('Y-m-d H:i:s');

                        if($row['amount']>0){
                            $insertData[]                                = $row;
                        }
                        
                        if(count($insertData)==1000){
                            \DB::table('inf_player_receive_gift_center')->insert($insertData);
                            $insertData   = [];
                       }
                    }

                    \DB::table('inf_player_receive_gift_center')->insert($insertData);
                }   
            }
        }
    }
}