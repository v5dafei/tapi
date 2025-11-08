<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Carrier;
use App\Models\CarrierPreFixDomain;
use App\Models\Player;
use App\Lib\Cache\CarrierCache;
use App\Lib\DevidendMode2;
use App\Lib\DevidendMode3;
use App\Lib\DevidendMode5;
use App\Lib\DevidendMode4;

class StockMemberStatCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'StockMemberStat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stock Member Stat';


    //重置用户数据库
    public   $deleteall = false;
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
            $carrierPreFixDomains = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $k => $v) {
                //分红计算方式
                $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_method',$v->prefix);

                //分红方式2是独立后台。没有库存
                if($playerDividendsMethod==2){
                    $allPlayers           = Player::where('carrier_id',$value->id)->where('prefix',$v->prefix)->where('win_lose_agent',0)->pluck('player_id')->toArray();
                } else{
                    $allPlayers           = Player::where('carrier_id',$value->id)->where('prefix',$v->prefix)->pluck('player_id')->toArray();
                }
                    
                $maxLevel             = Player::whereIn('player_id',$allPlayers)->max('level');
                $minLevel             = Player::whereIn('player_id',$allPlayers)->min('level');
                $level                = $minLevel;

                do{
                    $cyclePlayers         = Player::where('level',$level)->whereIn('player_id',$allPlayers)->orderby('player_id','asc')->get();
                    foreach ($cyclePlayers as $k1 => $v1) {
                        if($playerDividendsMethod==2){
                            $result = DevidendMode2::singleStockCalculateMemberByday($v1,1);        
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd',strtotime('-1 day')))->update($result);
                        } else if($playerDividendsMethod==3){
                            $result = DevidendMode3::singleStockCalculateMemberByday($v1,1);        
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd',strtotime('-1 day')))->update($result);
                        } else if($playerDividendsMethod==5){
                            $result = DevidendMode5::singleStockCalculateMemberByday($v1,1);        
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd',strtotime('-1 day')))->update($result);
                        } else if($playerDividendsMethod==4){
                            $result = DevidendMode4::singleStockCalculateMemberByday($v1,1);        
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd',strtotime('-1 day')))->update($result);
                        }
                    }

                    $level ++;
                }while($level <= $maxLevel);
            }
        }
    }
}