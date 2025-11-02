<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Carrier;
use App\Models\CarrierPreFixDomain;
use App\Models\Player;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Lib\DevidendMode2;
use App\Lib\DevidendMode3;
use App\Lib\DevidendMode5;
use App\Lib\DevidendMode4;

class RealTimeStockCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'realtimestock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Real Time Stock';


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
        //更新个人库存
        $carriers   = Carrier::all();
        $allPlayers = [];
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $k => $v) {
                //分红计算方式
                $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_method',$v->prefix);

                //分红方式2是独立后台。没有库存
                if($playerDividendsMethod==2){
                    $isNoAgents = Player::where('carrier_id',$value->id)->where('prefix',$v->prefix)->where('win_lose_agent',0)->pluck('player_id')->toArray();
                    $playerIds  = PlayerTransfer::where('carrier_id',$value->id)->where('prefix',$v->prefix)->whereIn('player_id',$isNoAgents)->where('stat_time','>=',time()-1800)->pluck('player_id')->toArray();
                    $playerIds1 = PlayerBetFlowMiddle::where('carrier_id',$value->id)->where('prefix',$v->prefix)->whereIn('player_id',$isNoAgents)->where('stat_time','>=',time()-1800)->pluck('player_id')->toArray();
                } else{
                    $playerIds  = PlayerTransfer::where('carrier_id',$value->id)->where('prefix',$v->prefix)->where('stat_time','>=',time()-1800)->pluck('player_id')->toArray();
                    $playerIds1 = PlayerBetFlowMiddle::where('carrier_id',$value->id)->where('prefix',$v->prefix)->where('stat_time','>=',time()-1800)->pluck('player_id')->toArray();
                }

                $allPlayers           = array_merge($playerIds,$playerIds1);
                $allPlayers           = array_unique($allPlayers);
                $maxLevel             = Player::whereIn('player_id',$allPlayers)->max('level');
                $minLevel             = Player::whereIn('player_id',$allPlayers)->min('level');
                $level                = $minLevel;

                do{
                    $cyclePlayers         = Player::where('level',$level)->whereIn('player_id',$allPlayers)->orderby('player_id','asc')->get();
                    foreach ($cyclePlayers as $k1 => $v1) {
                        if($playerDividendsMethod==2){
                            $result = DevidendMode2::singleStockCalculateMemberByday($v1,0);        
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd'))->update($result);
                        } else if($playerDividendsMethod==3){
                            $result = DevidendMode3::singleStockCalculateMemberByday($v1,0);        
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd'))->update($result);
                        } elseif($playerDividendsMethod==5){
                            $result = DevidendMode5::singleStockCalculateMemberByday($v1,0);        
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd'))->update($result);
                        } elseif($playerDividendsMethod==4){
                            $result = DevidendMode4::singleStockCalculateMemberByday($v1,0);        
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd'))->update($result);
                        }
                    }

                    $level ++;
                }while($level <= $maxLevel);
            }
        }

        $relatedPlayers =[]; 
        //筛选所有关联的用户
        foreach ($allPlayers as $key => $value) {
            $playerIdArrs = explode('|', $value);
            foreach ($playerIdArrs as $k => $v) {
                $relatedPlayers[] = $v;
            }
        }

        $relatedPlayers = array_unique($relatedPlayers);

        //更新团队库存
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $k => $v) {
                $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_method',$v->prefix);
                
                //分红计算方式
                $allAgentsPlayers     = Player::where('carrier_id',$value->id)->where('prefix',$v->prefix)->whereIn('player_id',$relatedPlayers)->pluck('player_id')->toArray();

                //鼎博2计算方式        
                $maxLevel             = Player::whereIn('player_id',$allAgentsPlayers)->max('level');
                $minLevel             = Player::whereIn('player_id',$allAgentsPlayers)->min('level');
                $level                = $minLevel;

                do{
                    $cyclePlayers         = Player::where('level',$level)->whereIn('player_id',$allAgentsPlayers)->orderby('player_id','asc')->get();
                    foreach ($cyclePlayers as $k1 => $v1) {
                        if($playerDividendsMethod==2){
                            $result = DevidendMode2::singleStockCalculateByday($v1,0);
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd'))->update($result);
                        } elseif($playerDividendsMethod==3){
                            $result = DevidendMode3::singleStockCalculateByday($v1,0);
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd'))->update($result);
                        } elseif($playerDividendsMethod==5){
                            $result = DevidendMode5::singleStockCalculateByday($v1,0);
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd'))->update($result);
                        } elseif($playerDividendsMethod==4){
                            $result = DevidendMode4::singleStockCalculateByday($v1,0);
                            ReportPlayerStatDay::where('player_id',$v1->player_id)->where('day',date('Ymd'))->update($result);
                        }
                    }

                    $level ++;
                }while($level <= $maxLevel);
            }
        }
    }
}