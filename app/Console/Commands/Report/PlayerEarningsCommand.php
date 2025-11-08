<?php

namespace App\Console\Commands\Report;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Conf\CarrierWebSite;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\Carrier;
use App\Models\Player;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerCommission;
use App\Lib\Cache\PlayerCache;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierPreFixDomain;
use App\Models\Conf\CarrierMultipleFront;
use App\Lib\DevidendMode1;
use App\Lib\DevidendMode2;
use App\Lib\DevidendMode3;
use App\Lib\DevidendMode5;
use App\Lib\DevidendMode4;
use App\Models\Log\PlayerRealDividendTongbao;

class PlayerEarningsCommand extends Command {
  
    protected $signature          = 'playerEarnings';

    protected $description        = 'Player Earnings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        $carriers       = Carrier::where('is_forbidden',0)->orderBy('id','asc')->get();
        $currDate       = date('Ymd');
        $preDate        = strtotime($currDate) - 86400;

        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains             = CarrierPreFixDomain::all();
            $defaultUserName                  = CarrierCache::getCarrierConfigure($value->id,'default_user_name');

            //查询对冲表数据结束
            foreach ($carrierPreFixDomains as $k8 => $v8) {
                $defaultPlayerId                  = PlayerCache::getPlayerId($value->id,$defaultUserName,$v8->prefix);
                $playerDividendsMethod            = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_method',$v8->prefix);
                $playerDividendsDay               = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_day',$v8->prefix);
                $playerDividendsStartDay          = CarrierCache::getCarrierMultipleConfigure($value->id,'player_dividends_start_day',$v8->prefix);
                if($playerDividendsDay==1){
                    $time = 345600;
                    $endDate  = date('Ymd',strtotime($playerDividendsStartDay)+$time);
                } elseif($playerDividendsDay==2){
                    $time = 518400;
                    $endDate  = date('Ymd',strtotime($playerDividendsStartDay)+$time);
                } elseif($playerDividendsDay==3){
                    $time = 172800;
                    $endDate  = date('Ymd',strtotime($playerDividendsStartDay)+$time);
                } elseif($playerDividendsDay==4){
                    $endDate  = date('Ymd',strtotime('-1 day'));
                } elseif($playerDividendsDay==5){
                    $day = date('d');
                    if($day>15){
                        $endDate  = date('Ym').'15';
                    } else{
                        $endDate  = date('Ymt',strtotime('-1 month'));
                    }
                }

                if($endDate != date('Ymd',strtotime('-1 day'))){
                    continue;
                }

                //默认代理
                $playerIds                        = Player::where('carrier_id',$value->id)->where('win_lose_agent',1)->where('prefix',$v8->prefix)->where('day','<=',$endDate)->groupBy('rid')->pluck('rid')->toArray();
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

                if(!count($allPlayers)){
                    continue;
                }

                $maxLevel             = Player::where('carrier_id',$value->id)->whereIn('player_id',$allPlayers)->max('level');
                $minLevel             = Player::where('carrier_id',$value->id)->whereIn('player_id',$allPlayers)->min('level');

                switch ($playerDividendsMethod) {
                    case 1:
                        DevidendMode1::stockCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                        break;
                    case 2:
                        DevidendMode2::stockCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                        break;
                    case 3:
                        DevidendMode3::stockCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                        break;
                    case 5:
                        DevidendMode5::stockCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                        break;
                    case 4:
                        DevidendMode4::stockCalculateDividend($maxLevel,$minLevel,$allPlayers,$v8);
                        break;
                    default:
                        // code...
                        break;
                }

                //删除前一天的实时分红记录
                PlayerRealDividendTongbao::where('prefix',$v8)->where('day','<',date('Ymd'))->delete();
                CarrierMultipleFront::where('prefix',$v8->prefix)->where('sign','player_dividends_start_day')->update(['value'=>date('Y-m-d')]);
                CarrierMultipleFront::where('prefix',$v8->prefix)->where('sign','player_realtime_dividends_start_day')->update(['value'=>date('Y-m-d')]);
                CarrierCache::flushCarrierMultipleConfigure($v8->carrier_id,$v8->prefix);
            }
        }

        //分红通宝处理
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains = CarrierPreFixDomain::where('carrier_id',$value->id)->get();
            foreach ($carrierPreFixDomains as $k => $v) {

                $maxLevel                      = ReportPlayerEarnings::where('carrier_id',$value->id)->where('end_day',date('Ymd',$preDate))->where('prefix',$v->prefix)->max('level');
                $minLevel                      = ReportPlayerEarnings::where('carrier_id',$value->id)->where('end_day',date('Ymd',$preDate))->where('prefix',$v->prefix)->min('level');

                $enableDividendsTongbaoMethod  = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_dividends_tongbao_method',$v->prefix);
                $tongbaoDividendsRate          = CarrierCache::getCarrierMultipleConfigure($value->id,'tongbao_dividends_rate',$v->prefix);
                $tongbaoDividendsRate          = bcdiv($tongbaoDividendsRate, 100,2);

                if(!is_null($minLevel)){
                    $level                         = $minLevel;
                    if($enableDividendsTongbaoMethod){
                        do{
                            $reportPlayerEarnings = ReportPlayerEarnings::where('carrier_id',$value->id)->where('level',$level)->where('end_day',date('Ymd',$preDate))->where('prefix',$v->prefix)->orderby('player_id','asc')->get();
                            foreach ($reportPlayerEarnings as $k1 => &$v1) {
                                $totalTongbaoDividends   = 0;
                                $insertData              = [];
                                $subReportPlayerEarnings =  ReportPlayerEarnings::where('end_day',date('Ymd',$preDate))->where('rid','like',$v1->rid.'|%')->get();
                                foreach ($subReportPlayerEarnings as $k2 => $v2) {
                                    if($v2->amount!=0){
                                        //添加进分红
                                        $partRid                     = str_replace($v1->rid.'|','',$v2->rid);
                                        $partRidArr                  = explode('|',$partRid);
                                        $number                      = count($partRidArr);
                                        $tongbaoRealDividendsRate    = pow($tongbaoDividendsRate, $number);
                                        $tongbaoDividends            = $tongbaoRealDividendsRate*$v2->amount;

                                        //写入详情表
                                        $row                                            = [];
                                        $row['carrier_id']                              = $v1->carrier_id;
                                        $row['prefix']                                  = $v1->prefix;
                                        $row['player_id']                               = $v2->player_id;
                                        $row['rid']                                     = $v2->rid;
                                        $row['parent_id']                               = $v2->parent_id;
                                        $row['performance']                             = $v2->amount;
                                        $row['scale']                                   = $tongbaoRealDividendsRate;
                                        $row['receive_player_id']                       = $v1->player_id;
                                        $row['amount']                                  = $tongbaoDividends;
                                        $row['day']                                     = $v2->end_day;
                                        $row['created_at']                              = date('Y-m-d H:i:s');
                                        $row['updated_at']                              = date('Y-m-d H:i:s');
                                        $insertData[]                                   = $row;

                                        $totalTongbaoDividends                          += $tongbaoDividends;
                                    }
                                }

                                $v1->tongbao_dividends  = $totalTongbaoDividends;
                                $v1->amount             = $v1->amount + $v1->tongbao_dividends;
                                $v1->save();

                                if(count($insertData)){
                                    \DB::table('log_player_dividends_tongbao')->insert($insertData);
                                }
                            }
                            $level ++;
                        }while($level <= $maxLevel);
                    }
                }
            }
        }
        //分红通宝处理结束
    }
}