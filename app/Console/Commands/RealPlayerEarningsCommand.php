<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Report\ReportRealPlayerEarnings;
use App\Models\Log\PlayerRealDividendTongbao;
use App\Models\Conf\CarrierWebSite;
use App\Models\Carrier;
use App\Models\Player;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerCommission;
use App\Lib\Cache\PlayerCache;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierPreFixDomain;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\PlayerTransfer;
use App\Lib\Cache\Lock;

use App\Lib\DevidendMode1;
use App\Lib\DevidendMode2;
use App\Lib\DevidendMode3;
use App\Lib\DevidendMode5;
use App\Lib\DevidendMode4;
use App\Lib\Clog;

class RealPlayerEarningsCommand extends Command {
  
    protected $signature          = 'realplayerEarnings';

    protected $description        = 'Real Player Earnings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if(date('i')!='20' && date('i')!='50'){
            return;
        }

        $cacheKey = "realplayerEarnings";
        $redisLock = Lock::addLock($cacheKey,2100);
    
        if (!$redisLock) {
            \Log::info('实时分红加锁失败');
            Clog::realearning('实时分红加锁失败', ['失败时间' =>date('Y-m-d H:i:s')]);
        } else {
            try{
                $carriers       = Carrier::where('is_forbidden',0)->orderBy('id','asc')->get();
                $globalPlayers  = [];

                foreach ($carriers as $key => $value) {
                    $carrierPreFixDomains             = CarrierPreFixDomain::all();
                    $defaultUserName                  = CarrierCache::getCarrierConfigure($value->id,'default_user_name');

                    //查询对冲表数据结束
                    foreach ($carrierPreFixDomains as $k8 => $v8) {
                        $defaultPlayerId                  = PlayerCache::getPlayerId($value->id,$defaultUserName,$v8->prefix);
                        $playerDividendsMethod            = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_method',$v8->prefix);
                        $playerDividendsDay               = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_day',$v8->prefix);
                        $playerDividendsStartDay          = CarrierCache::getCarrierMultipleConfigure($value->id,'player_realtime_dividends_start_day',$v8->prefix);

                        if($playerDividendsDay==1){
                            $time     = 345600;
                            $endDate  = date('Ymd',strtotime($playerDividendsStartDay)+$time);
                        } elseif($playerDividendsDay==2){
                            $time     = 518400;
                            $endDate  = date('Ymd',strtotime($playerDividendsStartDay)+$time);
                        } elseif($playerDividendsDay==3){
                            $time     = 172800;
                            $endDate  = date('Ymd',strtotime($playerDividendsStartDay)+$time);
                        } elseif($playerDividendsDay==4){
                            $endDate  = date('Ymd');
                        } elseif($playerDividendsDay==5){
                            $day = date('d');
                            if($day>15){
                                $endDate  = date('Ymt');
                            } else{
                                $endDate  = date('Ym').'15';
                            }
                        }

                        //只统计有帐变记录及有投注记录的用户
                        $accountChangePlayerIds       = PlayerTransfer::where('prefix',$v8->prefix)->where('created_at','>=',date('Y-m-d H:i:s',time()-2100))->pluck('player_id')->toArray();
                        $playerBetFlowMiddlePlayerIds = PlayerBetFlowMiddle::where('prefix',$v8->prefix)->where('created_at','>=',date('Y-m-d H:i:s',time()-2100))->pluck('player_id')->toArray();
                        $allPlayerIds                 = array_merge($accountChangePlayerIds,$playerBetFlowMiddlePlayerIds);
                        $allPlayerIds                 = array_unique($allPlayerIds);

                        $playerIds                    = Player::whereIn('player_id',$allPlayerIds)->pluck('rid')->toArray();                      

                        $allPlayers                       = [];
                        $defaultPlayerArr                 = [];

                        foreach ($playerIds as $k => $v) {
                            $playerIdArr = explode('|',$v);
                            foreach ($playerIdArr as $k3 => $v3) {
                                $allPlayers[] = $v3;
                            }
                            $allPlayers = array_unique($allPlayers);
                        }

                        $intersectPlayerIds   = $allPlayers;
                        $defaultPlayerArr[]   = $defaultPlayerId;
                        $allPlayers           = array_diff($allPlayers, $defaultPlayerArr);
                        $allPlayers           = Player::where('win_lose_agent',1)->whereIn('player_id',$allPlayers)->pluck('player_id')->toArray();

                        if(!count($allPlayers)){
                            continue;
                        }

                        $maxLevel             = Player::where('carrier_id',$value->id)->whereIn('player_id',$allPlayers)->max('level');
                        $minLevel             = Player::where('carrier_id',$value->id)->whereIn('player_id',$allPlayers)->min('level');

                        switch ($playerDividendsMethod) {
                            case 1:
                                DevidendMode1::stockRealCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                                $globalPlayers = array_merge($globalPlayers,$allPlayers);
                                break;
                            case 2:
                                DevidendMode2::stockRealCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                                $globalPlayers = array_merge($globalPlayers,$allPlayers);
                                break;
                            case 3:
                                DevidendMode3::stockRealCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                                $globalPlayers = array_merge($globalPlayers,$allPlayers);
                                break;
                            case 5:
                                DevidendMode5::stockRealCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                                $globalPlayers = array_merge($globalPlayers,$allPlayers);
                                break;
                            case 4:
                                DevidendMode4::stockRealCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                                $globalPlayers = array_merge($globalPlayers,$allPlayers);
                                break;
                            default:
                                // code...
                                break;
                        }
                    }
                }

                //分红通宝处理
                foreach ($carriers as $key => $value) {
                    $carrierPreFixDomains = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
                    foreach ($carrierPreFixDomains as $k => $v) {
                        $maxLevel                      = ReportRealPlayerEarnings::where('carrier_id',$value->id)->where('day',date('Ymd'))->where('prefix',$v->prefix)->whereIn('player_id',$globalPlayers)->max('level');
                        $minLevel                      = ReportRealPlayerEarnings::where('carrier_id',$value->id)->where('day',date('Ymd'))->where('prefix',$v->prefix)->whereIn('player_id',$globalPlayers)->min('level');

                        $enableDividendsTongbaoMethod  = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_dividends_tongbao_method',$v->prefix);
                        $tongbaoDividendsRate          = CarrierCache::getCarrierMultipleConfigure($value->id,'tongbao_dividends_rate',$v->prefix);
                        $tongbaoDividendsRate          = bcdiv($tongbaoDividendsRate, 100,2);

                        if(!is_null($minLevel)){
                            $level                         = $minLevel;
                            if($enableDividendsTongbaoMethod){
                                do{
                                    $reportPlayerEarnings = ReportRealPlayerEarnings::where('carrier_id',$value->id)->where('level',$level)->where('day',date('Ymd'))->where('prefix',$v->prefix)->whereIn('player_id',$globalPlayers)->orderby('player_id','asc')->get();
                                    foreach ($reportPlayerEarnings as $k1 => &$v1) {
                                        $totalTongbaoDividends   = 0;
                                        $insertData              = [];

                                        $subReportPlayerEarnings =  ReportRealPlayerEarnings::where('rid','like',$v1->rid.'|%')->where('day',date('Ymd'))->get();
                                        foreach ($subReportPlayerEarnings as $k2 => $v2) {
                                            if($v2->amount !=0){
                                                //添加进分红
                                                $partRid                        = str_replace($v1->rid.'|','',$v2->rid);
                                                $partRidArr                     = explode('|',$partRid);
                                                $number                         = count($partRidArr);
                                                $tongbaoRealDividendsRate       = pow($tongbaoDividendsRate, $number);
                                                $tongbaoDividends               = $tongbaoRealDividendsRate*$v2->amount;

                                                $playerRealDividendTongbao = PlayerRealDividendTongbao::where('player_id',$v2->player_id)->where('day',$v2->end_day)->first();
                                                if(!$playerRealDividendTongbao){
                                                    $playerRealDividendTongbao = new PlayerRealDividendTongbao();
                                                }

                                                $playerRealDividendTongbao->carrier_id        = $v1->carrier_id;
                                                $playerRealDividendTongbao->prefix            = $v1->prefix;
                                                $playerRealDividendTongbao->player_id         = $v2->player_id;
                                                $playerRealDividendTongbao->rid               = $v2->rid;
                                                $playerRealDividendTongbao->parent_id         = $v2->parent_id;
                                                $playerRealDividendTongbao->performance       = $v2->amount;
                                                $playerRealDividendTongbao->scale             = $tongbaoRealDividendsRate;
                                                $playerRealDividendTongbao->receive_player_id = $v1->player_id;
                                                $playerRealDividendTongbao->amount            = $tongbaoDividends;
                                                $playerRealDividendTongbao->day               = $v2->end_day;
                                                $playerRealDividendTongbao->save();

                                                $totalTongbaoDividends                          += $tongbaoDividends;
                                            }
                                        }

                                        $v1->tongbao_dividends  = $totalTongbaoDividends;
                                        $v1->amount             = $v1->amount + $v1->tongbao_dividends;
                                        $v1->save();
                                    }
                                    $level ++;
                                }while($level <= $maxLevel);
                            }
                        }
                    }
                }
                //分红通宝处理结束
                Lock::release($redisLock);
            } catch(\Exception $e){
                Lock::release($redisLock);
                Clog::recordabnormal('实时分红异常:'.$e->getMessage());
            }
        }
    }
}