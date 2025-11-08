<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Models\Log\RankingList;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierPreFixDomain;
use App\Lib\Cache\PlayerCache;

class RefreshRankCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refreshrank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'refreshrank';

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

            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $enableRankings                     = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_rankings',$value1->prefix);
                $rankingsType                       = CarrierCache::getCarrierMultipleConfigure($value->id,'rankings_type',$value1->prefix);
                $rankingsCycle                      = CarrierCache::getCarrierMultipleConfigure($value->id,'rankings_cycle',$value1->prefix);
                $rankingsPerformanceLow             = CarrierCache::getCarrierMultipleConfigure($value->id,'rankings_performance_low',$value1->prefix);
                $playerDividendsDay                 = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_day',$value1->prefix);
                $playerRealtimeDividendsStartDay    = CarrierCache::getCarrierMultipleConfigure($value->id,'player_realtime_dividends_start_day',$value1->prefix);

                if($enableRankings){
                    if($rankingsType==1){
                        //流水揸行榜
                        if($rankingsCycle==1){
                            //排行榜周期1天
                            $playerBetFlowMiddles = PlayerBetFlowMiddle::select(\DB::raw('sum(process_available_bet_amount) as process_available_bet_amount'),'player_id','prefix')->where('carrier_id',$value->id)->where('day',date('Ymd'))->where('prefix',$value1->prefix)->groupby('player_id')->having(\DB::raw('sum(process_available_bet_amount)'),'>=',$rankingsPerformanceLow)->get()->toArray();
                        } else{
                            //排行榜周期同分红周期
                            $playerBetFlowMiddles = PlayerBetFlowMiddle::select(\DB::raw('sum(process_available_bet_amount) as process_available_bet_amount'),'player_id','prefix')->where('carrier_id',$value->id)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->where('prefix',$value1->prefix)->groupby('player_id')->having(\DB::raw('sum(process_available_bet_amount)'),'>=',$rankingsPerformanceLow)->get()->toArray();
                        }
                    } else{
                        //业绩排行榜
                        if($rankingsCycle==1){
                            //排行榜周期1天
                            $playerBetFlowMiddles = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),\DB::raw('parent_id as player_id'),'prefix')->where('carrier_id',$value->id)->where('day',date('Ymd'))->where('prefix',$value1->prefix)->groupby('parent_id')->having(\DB::raw('sum(process_available_bet_amount)'),'>=',$rankingsPerformanceLow)->get()->toArray();
                        } else{
                            //排行榜周期1天
                            $playerBetFlowMiddles = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),\DB::raw('parent_id as player_id'),'prefix')->where('carrier_id',$value->id)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->where('prefix',$value1->prefix)->groupby('parent_id')->having(\DB::raw('sum(process_available_bet_amount)'),'>=',$rankingsPerformanceLow)->get()->toArray();
                        }
                    }

                    
                    if($rankingsCycle==1){
                        $currRankingList = RankingList::where('carrier_id',$value->id)->where('day',date('Ymd'))->where('prefix',$value1->prefix)->first();
                    } else{
                        $currRankingList = RankingList::where('carrier_id',$value->id)->where('day',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->where('prefix',$value1->prefix)->first();
                    }

                    $oldReRots       = [];
                    if($currRankingList){
                        $oldeContens = json_decode($currRankingList->content,true);
                        foreach ($oldeContens as $key2 => $value2) {
                            if($value2['robot']==1){
                                $row = [];
                                $row['ranking']     = $value2['ranking'];
                                $row['user_name']   = $value2['user_name'];
                                $row['performance'] = intval($value2['performance']);
                                $row['robot']       = 1;
                                $oldReRots[]        = $row;
                            }
                        }
                        
                    }

                    RankingList::where('carrier_id',$value->id)->where('day',date('Ymd'))->where('prefix',$value1->prefix)->delete();

                    if($rankingsCycle==1){
                        RankingList::where('carrier_id',$value->id)->where('day',date('Ymd'))->where('prefix',$value1->prefix)->delete();
                    } else{
                        RankingList::where('carrier_id',$value->id)->where('day',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->where('prefix',$value1->prefix)->delete();
                    }
                
                    if(count($playerBetFlowMiddles)){
                        $flag = [];
                        foreach ($playerBetFlowMiddles as $key => $v) {
                            $flag[] = $v['process_available_bet_amount']; 
                        }
                        array_multisort($flag, SORT_DESC, $playerBetFlowMiddles);
                        $playerBetFlowMiddles = array_slice($playerBetFlowMiddles,0,10);
                    }

                        $data          = [];
                        $names         = [];
                        $prize         = [1288,888,588,388,288,188,58,58,58,58];
                        $randName      = config('main')['randName'];
                        $randNameArr   = explode(',',$randName);
                        $randNameCount = count($randNameArr);
                        $i             = 0;

                        if(isset($playerBetFlowMiddles[0]) &&  count($playerBetFlowMiddles) <10){

                            $startData         = is_null($playerBetFlowMiddles[0]['process_available_bet_amount'])? 0 : intval($playerBetFlowMiddles[0]['process_available_bet_amount']);
                            $realPlayerCount   = count($playerBetFlowMiddles);
                            $endData           = is_null($playerBetFlowMiddles[$realPlayerCount-1]['process_available_bet_amount']) ? 0 : intval($playerBetFlowMiddles[$realPlayerCount-1]['process_available_bet_amount']);

                            if(count($oldReRots) + $realPlayerCount < 10){
                                for($i=1;$i<=10-$realPlayerCount;$i++){
                                    if($endData != $startData){
                                        $max                 = $startData - $endData;
                                    } else{
                                        $max                 = 5000;
                                    }
                                    
                                    $randNumber          = rand(1,$max);
                                    $seed                = rand(0, $randNameCount-1);
                                    $rows                = [];
                                    $rows['ranking']     = $i;
                                    $rows['user_name']   = $randNameArr[$seed];
                                    $rows['performance'] = $startData + $randNumber ;
                                    $rows['robot']       = 1;
                                    $data[]              = $rows;
                                }
                            }
                            
                            foreach ($playerBetFlowMiddles as $k1 => $v1) {
                                $rows                = [];
                                $rows['ranking']     = $i;
                                $rows['user_name']   = PlayerCache::getPlayerUserName($v1['player_id']);
                                $rows['performance'] = intval($v1['process_available_bet_amount']);
                                $rows['robot']       = 0;
                                $data[]              = $rows;
                                $i++;
                            }

                            if(count($oldReRots)){
                                $data             = array_merge($oldReRots,$data);
                            }

                            $flag = [];
                            foreach ($data as $key4 => $value4) {
                                $flag[] = $value4['performance']; 
                            }
                            array_multisort($flag, SORT_DESC, $data);

                            $finaldata = [];
                            for($i=1;$i<=10;$i++){
                                $rows                = [];
                                $rows['ranking']     = $i;
                                $rows['user_name']   = $data[$i-1]['user_name'];
                                $rows['performance'] = $data[$i-1]['performance'];;
                                $rows['robot']       = $data[$i-1]['robot'];
                                $index               =$i-1;
                                $rows['bonus']       = $prize[$index];
                                $finaldata[]         = $rows;
                            }

                            $rankingList              = new RankingList();
                            $rankingList->carrier_id  = $value->id;
                            $rankingList->content     = json_encode($finaldata);
                            if($rankingsCycle==1){
                                $rankingList->day         = date('Ymd');
                                $rankingList->endday      = date('Ymd');
                            } else{
                                $rankingList->day         = date('Ymd',strtotime($playerRealtimeDividendsStartDay));

                                //分红结算周期2=一周，3=3天，4=1天
                                switch ($playerDividendsDay) {
                                    case 1:
                                        $rankingList->endday = date('Ymd',strtotime($playerRealtimeDividendsStartDay)+345600);
                                        break;
                                    case 2:
                                        $rankingList->endday = date('Ymd',strtotime($playerRealtimeDividendsStartDay)+518400);
                                        break;
                                    case 3:
                                        $rankingList->endday = date('Ymd',strtotime($playerRealtimeDividendsStartDay)+172800);
                                        break;
                                    case 4:
                                        $rankingList->endday = $rankingList->day;
                                        break;
                                    case 5:
                                        if(date('d',strtotime($playerRealtimeDividendsStartDay))==16){
                                            $rankingList->endday = date('Ymt');
                                        } else{
                                            $rankingList->endday = date('Ym').'15';
                                        }
                                        
                                        break;
                                    
                                    default:
                                        break;
                                }
                                
                            }
                            
                            $rankingList->status      = 1;
                            $rankingList->prefix      = $value1->prefix;
                            $rankingList->save();
                            
                        } else{
                            //纯机器人
                            for($i=1;$i<=10;$i++){
                                $randNumber          = rand(1,10000);
                                $seed                = rand(0, $randNameCount-1);
                                $rows                = [];
                                $rows['ranking']     = $i;
                                $rows['user_name']   = $randNameArr[$seed];
                                $rows['robot']       = 1;
                                $performance         = intval($rankingsPerformanceLow + $randNumber);
                                $rows['performance'] = $performance;
                                $index               =$i-1;
                                $rows['bonus']       = $prize[$index];
                                $data[]              = $rows;
                            }

                            $flag = [];
                            foreach ($data as $key4 => $value4) {
                                $flag[] = $value4['performance']; 
                            }
                            array_multisort($flag, SORT_DESC, $data);

                            $finaldata = [];
                            for($i=1;$i<=10;$i++){
                                $rows                = [];
                                $rows['ranking']     = $i;
                                $rows['user_name']   = $data[$i-1]['user_name'];
                                $rows['performance'] = $data[$i-1]['performance'];;
                                $rows['robot']       = $data[$i-1]['robot'];
                                $index               =$i-1;
                                $rows['bonus']       = $prize[$index];
                                $finaldata[]         = $rows;
                            }

                            $rankingList              = new RankingList();
                            $rankingList->carrier_id  = $value->id;
                            $rankingList->content     = json_encode($finaldata);
                            if($rankingsCycle==1){
                                $rankingList->day         = date('Ymd');
                                $rankingList->endday      = date('Ymd');
                            } else{
                                $rankingList->day         = date('Ymd',strtotime($playerRealtimeDividendsStartDay));

                                //分红结算周期2=一周，3=3天，4=1天
                                switch ($playerDividendsDay) {
                                    case 1:
                                        $rankingList->endday = date('Ymd',strtotime($playerRealtimeDividendsStartDay)+345600);
                                        break;
                                    case 2:
                                        $rankingList->endday = date('Ymd',strtotime($playerRealtimeDividendsStartDay)+518400);
                                        break;
                                    case 3:
                                        $rankingList->endday = date('Ymd',strtotime($playerRealtimeDividendsStartDay)+172800);
                                        break;
                                    case 4:
                                        $rankingList->endday = $rankingList->day;
                                        break;
                                    case 5:
                                        if(date('d',strtotime($playerRealtimeDividendsStartDay))==16){
                                            $rankingList->endday = date('Ymt');
                                        } else{
                                            $rankingList->endday = date('Ym').'15';
                                        }
                                        
                                        break;
                                    
                                    default:
                                        break;
                                }
                                
                            }
                            $rankingList->status      = 1;
                            $rankingList->prefix      = $value1->prefix;
                            $rankingList->save();
                        }
                }
            }
        }
    }
}