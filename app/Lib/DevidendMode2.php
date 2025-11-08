<?php namespace App\Lib;

use App\Utils\File\FileHelper;
use App\Models\Player;
use App\Models\PlayerInviteCode;
use App\Lib\Cache\CarrierCache;
use App\Models\Conf\PlayerSetting;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\PlayerCommission;
use App\Models\PlayerTransfer;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Report\ReportRealPlayerEarnings;

class DevidendMode2{
    //（充值*运营费-提现-库存）*分红比例
    public static function promoteAndMakeMoney($input,$user)
    {
        $data                          = [];

        //推广链接
        $playerInviteCode              = PlayerInviteCode::where('player_id',$user->player_id)->first();

        $h5url                         = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'h5url',$user->prefix);

        $h5urlArr                      = explode(',',$h5url);

        if(!empty($playerInviteCode->domain)){
            $links                         = 'https://'.$playerInviteCode->code.'.'.$playerInviteCode->domain.',';
        } else{
            $links                         = '';
        }

        foreach ($h5urlArr as $key => $value) {
            $links.='https://'.$playerInviteCode->code.'.'.$value.',';
            
        }
        $links = rtrim($links,',');

        //推广链接
        $data['links'] = $links;

        return $data;
    }

    public static function stockCalculateDividend($maxLevel,$minLevel,$allPlayers,$carrierPreFixDomain)
    {
        $playerDividendsStartDay               = CarrierCache::getCarrierMultipleConfigure($carrierPreFixDomain->carrier_id,'player_dividends_start_day',$carrierPreFixDomain->prefix);
        $playerDividendsDay                    = CarrierCache::getCarrierMultipleConfigure($carrierPreFixDomain->carrier_id,'player_dividends_day',$carrierPreFixDomain->prefix);
        $operatingExpenses                     = CarrierCache::getCarrierMultipleConfigure($carrierPreFixDomain->carrier_id,'operating_expenses',$carrierPreFixDomain->prefix);
        $operatingExpenses                     = bcdiv(100-$operatingExpenses,100,2);

        $time                                  = 0;
        if($playerDividendsDay==1){
            $time = 345600;
        } elseif($playerDividendsDay==2){
            $time = 518400;
        } elseif($playerDividendsDay==3){
            $time = 172800;
        }
        $startDay                              = date('Ymd',strtotime($playerDividendsStartDay));
        $endDay                                = date('Ymd',strtotime($playerDividendsStartDay)+$time);
        $startTime                             = date('Y-m-d',strtotime($playerDividendsStartDay)).' 00:00:00';
        $endTime                               = date('Y-m-d',strtotime($playerDividendsStartDay)+$time).'23:59:59';
        $level                                 = $minLevel;
        $data                                  = [];

        do{
            $cyclePlayers = Player::where('level',$level)->whereIn('player_id',$allPlayers)->where('win_lose_agent',1)->orderby('player_id','asc')->get();

            $cyclePlayerIds = [];
            foreach ($cyclePlayers as $key => $value) {
                $cyclePlayerIds[] = $value->player_id;
            }

            $levelPlayerSettings = PlayerSetting::whereIn('player_id',$cyclePlayerIds)->get();
            $playerSettingArr    = [];
            foreach ($levelPlayerSettings as $key => $value) {
                $playerSettingArr[$value->player_id] = $value->earnings;
            }

            foreach ($cyclePlayers as $k => $v) {

                $rows                            = [];
                $reportPlayerEarnings            = ReportPlayerEarnings::where('player_id',$v->player_id)->orderBy('id','desc')->first();                
                if($reportPlayerEarnings){
                    $rows['lastaccumulation']    = $reportPlayerEarnings->accumulation;
                    $rows['is_allow_fast_grant'] = $reportPlayerEarnings->is_allow_fast_grant;
                } else {
                    $rows['lastaccumulation']    = 0;
                    $rows['is_allow_fast_grant'] = 1;
                }

                //先计算直属
                $reportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(change_self_stock) as change_self_stock'))
                    ->where('parent_id',$v->player_id)
                    ->where('win_lose_agent',0)
                    ->where('day','>=',$startDay)
                    ->where('day','<=',$endDay)
                    ->first();

                $endReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))
                    ->where('parent_id',$v->player_id)
                    ->where('win_lose_agent',0)
                    ->where('day',$endDay)
                    ->first();
                $directlyUnderStock              = 0;
                $directlyunderStockChange        = 0;
                $directlyUnderRechargeAmount     = 0; 
                $directlyUnderWithdrawAmount     = 0;


                if($endReportPlayerStatDay && !is_null($endReportPlayerStatDay->self_stock)){
                    $directlyUnderStock = $endReportPlayerStatDay->self_stock;
                }

                if($reportPlayerStatDay && !is_null($reportPlayerStatDay->recharge_amount)){
                    $directlyUnderRechargeAmount = $reportPlayerStatDay->recharge_amount*$operatingExpenses;
                    $directlyUnderWithdrawAmount = $reportPlayerStatDay->withdraw_amount;
                    $directlyunderStockChange    = $reportPlayerStatDay->change_self_stock;
                }
                
                $rows['carrier_id']                    = $v->carrier_id;
                $rows['rid']                           = $v->rid;
                $rows['top_id']                        = $v->top_id;
                $rows['parent_id']                     = $v->parent_id;
                $rows['player_id']                     = $v->player_id;
                $rows['is_tester']                     = $v->is_tester;
                $rows['prefix']                        = $v->prefix;
                $rows['user_name']                     = $v->user_name;
                $rows['level']                         = $v->level;
                $rows['inviteplayerid']                = $v->inviteplayerid;
                $rows['descendantscount']              = $v->descendantscount;
                $rows['from_day']                      = $startDay;
                $rows['end_day']                       = $endDay;
                $rows['init_time']                     = time();
                $rows['created_at']                    = date('Y-m-d H:i:s');
                $rows['updated_at']                    = date('Y-m-d H:i:s');
                $rows['venue_fee']                     = 0;
                $rows['earnings']                      = $playerSettingArr[$v->player_id];
                $rows['directlyunder_stock']           = $directlyUnderStock;
                $rows['directlyunder_stock_change']    = $directlyunderStockChange;
                $rows['directlyunder_recharge_amount'] = $directlyUnderRechargeAmount;
                $rows['directlyunder_withdraw_amount'] = $directlyUnderWithdrawAmount;
                $rows['directlyunder_stock']           = $directlyUnderStock;
                $directlyUnderDividend                 = bcdiv(($directlyUnderRechargeAmount - $directlyUnderWithdrawAmount- $directlyunderStockChange)*$playerSettingArr[$v->player_id],100,0);
                $sonAgents                             = Player::where('parent_id',$v->player_id)->where('win_lose_agent',1)->get();

                $teamStock              = 0;
                $teamStockChange        = 0;
                $teamRechargeAmount     = 0; 
                $teamWithdrawAmount     = 0;
                $teamDividend           = 0;

                $sonAgentPlayerIds = [];
                foreach ($sonAgents as $key => $value) {
                    $sonAgentPlayerIds[] = $value->player_id;
                }

                $sonPlayerSettings    = PlayerSetting::whereIn('player_id',$sonAgentPlayerIds)->get();
                $sonPlayerSettingsArr = [];
                foreach ($sonPlayerSettings as $key => $value) {
                    $sonPlayerSettingsArr[$value->player_id] = $value->earnings;
                }


                foreach ($sonAgents as $key => $value) {
                    //团队分红
                    $reportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(team_recharge_amount) as team_recharge_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(change_team_stock) as change_team_stock'),\DB::raw('sum(change_self_stock) as change_self_stock'),\DB::raw('sum(change_stock) as change_stock'))
                        ->where('parent_id',$value->player_id)
                        ->where('day','>=',$startDay)
                        ->where('day','<=',$endDay)
                        ->first();

                    $rechangeAmount             = 0;
                    $withdrawAmount             = 0;
                    $changeStock                = 0;
                    $stock                      = 0;
                    $changeTeamStock            = 0;
                    $changeDirectlyunderStock   = 0;
                    $changeselfStock            = 0;

                    if($reportPlayerStatDay && !is_null($reportPlayerStatDay->team_recharge_amount)){
                        $rechangeAmount     = ($reportPlayerStatDay->team_recharge_amount + $reportPlayerStatDay->recharge_amount)*$operatingExpenses;
                    }

                    if($reportPlayerStatDay && !is_null($reportPlayerStatDay->team_withdraw_amount)){
                        $withdrawAmount     = $reportPlayerStatDay->team_withdraw_amount;
                    }

                    if($reportPlayerStatDay && !is_null($reportPlayerStatDay->change_stock)){
                        $changeDirectlyunderStock  = $reportPlayerStatDay->change_stock;
                    }

                    if($reportPlayerStatDay && !is_null($reportPlayerStatDay->change_team_stock)){
                        $changeTeamStock  = $reportPlayerStatDay->change_team_stock;
                    }

                    if($reportPlayerStatDay && !is_null($reportPlayerStatDay->change_self_stock)){
                        $changeselfStock  = $reportPlayerStatDay->change_self_stock;
                    }

                    $changeStock = $changeDirectlyunderStock + $changeTeamStock + $changeselfStock;

                    $endReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(team_stock) as team_stock'),\DB::raw('sum(stock) as stock'))
                        ->where('parent_id',$value->player_id)
                        ->where('win_lose_agent',1)
                        ->where('day',$endDay)
                        ->first();

                    if($endReportPlayerStatDay && !is_null($endReportPlayerStatDay->team_stock)){
                        $stock = $endReportPlayerStatDay->team_stock + $endReportPlayerStatDay->stock;
                    }

                    $teamStock          += $stock;
                    $teamStockChange    += $changeStock;
                    $teamRechargeAmount += $rechangeAmount;
                    $teamWithdrawAmount += $withdrawAmount;
                    $teamDividend       += bcdiv(($rechangeAmount - $withdrawAmount - $changeStock)*($playerSettingArr[$v->player_id] - $sonPlayerSettingsArr[$value->player_id]),100,0);

                }

                //注册人数
                $rows['registerpersoncount']     = 0;
                $rows['activepersonacount']      = 0;
                $rows['availableadd']            = 0;
                $rows['team_recharge_amount']        = $teamRechargeAmount;
                $rows['team_withdraw_amount']        = $teamWithdrawAmount;
                $rows['amount']                      = $teamDividend + $directlyUnderDividend + $rows['lastaccumulation'];
                $rows['team_stock']                  = $teamStock;
                $rows['team_stock_change']           = $teamStockChange;
                
                if($rows['amount']!=0){
                    $data[]         = $rows;
                }

                if(count($data)==100){
                    \DB::table('report_player_earnings')->insert($data);
                    $data = [];
                }
            }
            if(count($data)){
                \DB::table('report_player_earnings')->insert($data);
                $data = [];
            }
            $level ++;
        }while($level <= $maxLevel);
    }

    //计算模式2单个用户的分红
    public static function calculateDividend($player,$startDay=null,$endDay=null)
    {
        //查询当天直属
        $directlyunderPlayers = Player::where('parent_id',$player->player_id)->where('win_lose_agent',0)->get();

        foreach ($directlyunderPlayers as $key => $value) {
            self::singleStockCalculateMemberByday($value);
        }
        //查询当天代理
        $teamPlayers          = Player::where('parent_id',$player->player_id)->where('win_lose_agent',1)->get();
        foreach ($teamPlayers as $key => $value) {
            self::singleStockCalculateByday($value);
        }


        $playerRealTimeDividendsStartDay               = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'player_realtime_dividends_start_day',$player->prefix);
        $operatingExpenses                             = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'operating_expenses',$player->prefix);
        $operatingExpenses                             = bcdiv(100-$operatingExpenses,100,2);

        if(is_null($startDay)){
            $startDay                                 = date('Ymd',strtotime($playerRealTimeDividendsStartDay));
            $endDay                                   = date('Ymd');
        }

        $data                                  = [];
        $rows                                  = [];
        $selfSetting                           = PlayerSetting::where('player_id',$player->player_id)->first();
        $reportPlayerEarnings                  = ReportPlayerEarnings::where('player_id',$player->player_id)->orderBy('id','desc')->first();                
        if($reportPlayerEarnings){
            $rows['lastaccumulation']       = $reportPlayerEarnings->accumulation;
            $rows['is_allow_fast_grant']    = $reportPlayerEarnings->is_allow_fast_grant;
        } else {
            $rows['lastaccumulation']       = 0;
            $rows['is_allow_fast_grant']    = 1;
        }

        //直属充值人数
        $directlyunderRechargePeopleNumber               = PlayerTransfer::where('parent_id',$player->player_id)->where('day','>=',$startDay)->where('day','<=',$endDay)->where('type','recharge')->pluck('player_id')->toArray();
        $rows['directlyunder_recharge_people_number']    = count(array_unique($directlyunderRechargePeopleNumber));
        $registerPeopleNumber                           = Player::where('parent_id',$player->player_id)->where('win_lose_agent',0)->where('day','>=',$startDay)->where('day','<=',$endDay)->pluck('player_id')->toArray();
        $rows['register_people_number']                 = count(array_unique($registerPeopleNumber));
        $rows['directlyunder_people_number']            = $player->soncount;

        //先计算直属
        $reportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(change_self_stock) as change_self_stock'))->where('parent_id',$player->player_id)
            ->where('win_lose_agent',0)
            ->where('day','>=',$startDay)
            ->where('day','<=',$endDay)
            ->first();

        $endReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))
            ->where('parent_id',$player->player_id)
            ->where('win_lose_agent',0)
            ->where('day',$endDay)
            ->first();

        $directlyUnderStock              = 0;
        $directlyunderStockChange        = 0;
        $directlyUnderRechargeAmount     = 0; 
        $directlyUnderWithdrawAmount     = 0;

        if($endReportPlayerStatDay && !is_null($endReportPlayerStatDay->self_stock)){
            $directlyUnderStock = $endReportPlayerStatDay->self_stock;
        }

        if($reportPlayerStatDay && !is_null($reportPlayerStatDay->recharge_amount)){
            $directlyUnderRechargeAmount = $reportPlayerStatDay->recharge_amount*$operatingExpenses;
            $directlyUnderWithdrawAmount = $reportPlayerStatDay->withdraw_amount;
            $directlyunderStockChange    = $reportPlayerStatDay->change_self_stock;
        }
                
        $rows['carrier_id']                    = $player->carrier_id;
        $rows['rid']                           = $player->rid;
        $rows['top_id']                        = $player->top_id;
        $rows['parent_id']                     = $player->parent_id;
        $rows['player_id']                     = $player->player_id;
        $rows['is_tester']                     = $player->is_tester;
        $rows['prefix']                        = $player->prefix;
        $rows['user_name']                     = $player->user_name;
        $rows['level']                         = $player->level;
        $rows['inviteplayerid']                = $player->inviteplayerid;
        $rows['descendantscount']              = $player->descendantscount;
        $rows['from_day']                      = $startDay;
        $rows['end_day']                       = $endDay;
        $rows['init_time']                     = time();
        $rows['created_at']                    = date('Y-m-d H:i:s');
        $rows['updated_at']                    = date('Y-m-d H:i:s');
        $rows['venue_fee']                     = 0;
        $rows['earnings']                      = $selfSetting->earnings;
        $rows['directlyunder_stock']           = $directlyUnderStock;
        $rows['directlyunder_stock_change']    = $directlyunderStockChange;
        $rows['directlyunder_recharge_amount'] = $directlyUnderRechargeAmount;
        $rows['directlyunder_withdraw_amount'] = $directlyUnderWithdrawAmount;
        $directlyUnderDividend                 = bcdiv(($directlyUnderRechargeAmount - $directlyUnderWithdrawAmount- $directlyunderStockChange)*$selfSetting->earnings,100,0);
        $sonAgents                             = Player::where('parent_id',$player->player_id)->where('win_lose_agent',1)->get();

        $teamStock                             = 0;
        $teamStockChange                       = 0;
        $teamRechargeAmount                    = 0; 
        $teamWithdrawAmount                    = 0;
        $teamDividend                          = 0;

        $sonAgentPlayerIds = [];
        foreach ($sonAgents as $key => $value) {
            $sonAgentPlayerIds[] = $value->player_id;
        }

        $sonPlayerSettings    = PlayerSetting::whereIn('player_id',$sonAgentPlayerIds)->get();
        $sonPlayerSettingsArr = [];
        foreach ($sonPlayerSettings as $key => $value) {
            $sonPlayerSettingsArr[$value->player_id] = $value->earnings;
        }

        foreach ($sonAgents as $key => $value) {
            //团队分红
            $reportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(team_recharge_amount) as team_recharge_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(change_team_stock) as change_team_stock'),\DB::raw('sum(change_stock) as change_stock'),\DB::raw('sum(change_self_stock) as change_self_stock'))
                ->where('parent_id',$value->player_id)
                ->where('day','>=',$startDay)
                ->where('day','<=',$endDay)
                ->first();

            $rechangeAmount  = 0;
            $withdrawAmount  = 0;
            $changeStock     = 0;
            $stock           = 0;
            $selfstock       = 0;

            if($reportPlayerStatDay && !is_null($reportPlayerStatDay->team_recharge_amount)){
                $rechangeAmount     = ($reportPlayerStatDay->team_recharge_amount + $reportPlayerStatDay->recharge_amount)*$operatingExpenses;
                $withdrawAmount     = $reportPlayerStatDay->team_withdraw_amount;
                $changeStock        = $reportPlayerStatDay->change_team_stock + $reportPlayerStatDay->change_stock + $reportPlayerStatDay->change_self_stock;
            }

            $endReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(team_stock) as team_stock'),\DB::raw('sum(stock) as stock'))
                ->where('parent_id',$value->player_id)
                ->where('win_lose_agent',1)
                ->where('day',$endDay)
                ->first();

            if($endReportPlayerStatDay && !is_null($endReportPlayerStatDay->team_stock)){
                $stock = $endReportPlayerStatDay->team_stock + $endReportPlayerStatDay->stock;
            }

            $teamStock          += $stock;
            $teamStockChange    += $changeStock;
            $teamRechargeAmount += $rechangeAmount;
            $teamWithdrawAmount += $withdrawAmount;
            $teamDividend       += bcdiv(($rechangeAmount - $withdrawAmount - $changeStock)*($selfSetting->earnings - $sonPlayerSettingsArr[$value->player_id]),100,0);
        }

        //注册人数
        $rows['registerpersoncount']         = 0;
        $rows['directlyunder_dividend']      = $directlyUnderDividend;
        $rows['team_dividend']               = $teamDividend;
        $rows['activepersonacount']          = 0;
        $rows['availableadd']                = 0;
        $rows['team_recharge_amount']        = $teamRechargeAmount;
        $rows['team_withdraw_amount']        = $teamWithdrawAmount;
        $rows['amount']                      = $teamDividend + $directlyUnderDividend + $rows['lastaccumulation'];
        $rows['team_stock']                  = $teamStock;
        $rows['team_stock_change']           = $teamStockChange;

        return $rows;
    }

    //更新单个用户直属与团队库存
    public static function singleStockCalculateByday($player, $number=0) 
    {
        $directlyUnderPlayerIds           = Player::where('parent_id',$player->player_id)->where('win_lose_agent',0)->pluck('player_id')->toArray();

       if(!$number){
            $directlyUnderReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$directlyUnderPlayerIds)->where('day',date('Ymd'))->first();
       } elseif($number==1){
            $directlyUnderReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$directlyUnderPlayerIds)->where('day',date('Ymd',strtotime('-1 day')))->first();
       } else{
            $directlyUnderReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$directlyUnderPlayerIds)->where('day',date('Ymd',strtotime(-$number.' days')))->first();
       }
      
        //直属库存
       $directlyUnderChangeStock = 0;
       if($directlyUnderReportPlayerStatDay && !is_null($directlyUnderReportPlayerStatDay->change_self_stock)){
            $directlyUnderChangeStock = $directlyUnderReportPlayerStatDay->change_self_stock;
       }

        //团队库存
        $teamPlayerIds           = Player::where('parent_id','!=',$player->player_id)->where('rid','like',$player->rid.'|%')->where('win_lose_agent',0)->pluck('player_id')->toArray();
        if(!$number){
            $teamReportPlayerStatDay     = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$teamPlayerIds)->where('day',date('Ymd'))->first();
        } elseif($number==1){
            $teamReportPlayerStatDay     = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$teamPlayerIds)->where('day',date('Ymd',strtotime('-1 day')))->first();
        } else{
            $teamReportPlayerStatDay     = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$teamPlayerIds)->where('day',date('Ymd',strtotime(-$number.' day')))->first();
        }

        $teamChangeStock = 0;
        if($teamReportPlayerStatDay && !is_null($teamReportPlayerStatDay->change_self_stock)){
            $teamChangeStock = $teamReportPlayerStatDay->change_self_stock;
        }

        if(!$number){
            $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$player->player_id)->where('day',date('Ymd',strtotime('-1 day')))->first();
        } else{
            $number = $number+1;
            $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$player->player_id)->where('day',date('Ymd',strtotime(-$number.' days')))->first();
        }
        
        if($preReportPlayerStatDay){
            $stock            = $directlyUnderChangeStock + $preReportPlayerStatDay->stock;
        } else{
            $stock            = $directlyUnderChangeStock;
        }

        if($preReportPlayerStatDay){
            $teamStock        = $teamChangeStock + $preReportPlayerStatDay->team_stock;
        } else{
            $teamStock        = $teamChangeStock;
        }

        return ['stock'=>$stock,'change_stock'=>$directlyUnderChangeStock,'team_stock'=>$teamStock,'change_team_stock'=>$teamChangeStock];
    }

    //计算单个用户自已本身库存number=0,统计今天 $number=1 昨天  number每加1时间向前推一天
    public static function singleStockCalculateMemberByday($player, $number=0) 
    {
        //团队库存
        if(!$number){
            $reportPlayerStatDay    = ReportPlayerStatDay::where('player_id',$player->player_id)->where('day',date('Ymd'))->first();
            $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$player->player_id)->where('day',date('Ymd',strtotime('-1 day')))->first();

            if($preReportPlayerStatDay){
                $changeStock  = $reportPlayerStatDay->self_stock - $preReportPlayerStatDay->self_stock;
                return ['self_stock'=>$reportPlayerStatDay->self_stock,'change_self_stock'=>$changeStock];
            } else{
                return ['self_stock'=>$reportPlayerStatDay->self_stock,'change_self_stock'=>$reportPlayerStatDay->self_stock];
            }
        } else{
            
            $reportPlayerStatDay    = ReportPlayerStatDay::where('player_id',$player->player_id)->where('day',date('Ymd',strtotime(-$number.' days')))->first();
            $number                 = $number+1;
            $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$player->player_id)->where('day',date('Ymd',strtotime(-$number.' days')))->first();
            
            if($preReportPlayerStatDay){
                $changeStock  = $reportPlayerStatDay->self_stock - $preReportPlayerStatDay->self_stock;
                return ['self_stock'=>$reportPlayerStatDay->self_stock,'change_self_stock'=>$changeStock];
            } else{
                return ['self_stock'=>$reportPlayerStatDay->self_stock,'change_self_stock'=>$reportPlayerStatDay->self_stock];
            }
        } 
    }

    public static function stockRealCalculateDividend($maxLevel,$minLevel,$allPlayers,$carrierPreFixDomain)
    {
        $playerDividendsStartDay               = CarrierCache::getCarrierMultipleConfigure($carrierPreFixDomain->carrier_id,'player_realtime_dividends_start_day',$carrierPreFixDomain->prefix);
        $playerDividendsDay                    = CarrierCache::getCarrierMultipleConfigure($carrierPreFixDomain->carrier_id,'player_dividends_day',$carrierPreFixDomain->prefix);

        $time                                  = 0;
        if($playerDividendsDay==1){
            $time = 345600;
        } elseif($playerDividendsDay==2){
            $time = 518400;
        } elseif($playerDividendsDay==3){
            $time = 172800;
        }

        if(in_array($playerDividendsDay,[1,2,3,4])){
            $startDay                              = date('Ymd',strtotime($playerDividendsStartDay));
            $endDay                                = date('Ymd',strtotime($playerDividendsStartDay)+$time);
        } elseif($playerDividendsDay ==5){
            $day = date('d');
            if($day>15){
                $startDay                        = date('Ym').'16';
                $endDay                          = date('Ymt');
            } else{
                $startDay                        = date('Ym').'01';
                $endDay                          = date('Ym').'15';
            }
        }

        $level                                 = $minLevel;
        $data                                  = [];

        ReportRealPlayerEarnings::where('prefix',$carrierPreFixDomain->prefix)->where('end_day','!=',$endDay)->delete();

        do{
            $cyclePlayers = Player::where('level',$level)->whereIn('player_id',$allPlayers)->where('win_lose_agent',1)->orderby('player_id','asc')->get();
            foreach ($cyclePlayers as $k => $v) {
                $result                               = self::calculateDividend($v,$startDay,$endDay);

                $reportRealPlayerEarnings             = ReportRealPlayerEarnings::where('player_id',$v->player_id)->where('from_day',$startDay)->where('end_day',$endDay)->first();
                if(!$reportRealPlayerEarnings){
                    $reportRealPlayerEarnings         = new ReportRealPlayerEarnings();
                }

                $reportRealPlayerEarnings->carrier_id                    = $v->carrier_id;
                $reportRealPlayerEarnings->rid                           = $v->rid;
                $reportRealPlayerEarnings->top_id                        = $v->top_id;
                $reportRealPlayerEarnings->parent_id                     = $v->parent_id;
                $reportRealPlayerEarnings->player_id                     = $v->player_id;
                $reportRealPlayerEarnings->is_tester                     = $v->is_tester;
                $reportRealPlayerEarnings->inviteplayerid                = $v->inviteplayerid;
                $reportRealPlayerEarnings->user_name                     = $v->user_name;
                $reportRealPlayerEarnings->level                         = $v->level;
                $reportRealPlayerEarnings->descendantscount              = $v->descendantscount;
                $reportRealPlayerEarnings->prefix                        = $v->prefix;

                $reportRealPlayerEarnings->direct_gift                   = 0;       //直属礼金
                $reportRealPlayerEarnings->direct_venue_fee              = 0;       //直属场馆费
                $reportRealPlayerEarnings->direct_winloss                = 0;       //直属游戏输赢
                $reportRealPlayerEarnings->team_gift                     = 0;       //团队礼金
                $reportRealPlayerEarnings->team_venue_fee                = 0;       //团队场馆费
                $reportRealPlayerEarnings->team_winloss                  = 0;       //团队游戏输赢
                $reportRealPlayerEarnings->fish_venue_fee                = 0;       //捕鱼场馆费
                $reportRealPlayerEarnings->electronic_venue_fee          = 0;       //电子场馆费
                $reportRealPlayerEarnings->electronic_winloss            = 0;       //电子游戏输赢
                $reportRealPlayerEarnings->fish_winloss                  = 0;       //捕鱼游戏输赢
                $reportRealPlayerEarnings->activepersonacount            = 0;
                $reportRealPlayerEarnings->availableadd                  = 0;

                $reportRealPlayerEarnings->day                                  = date('Ymd');
                $reportRealPlayerEarnings->direct_commission                    = $result['directlyunder_dividend'] ;
                $reportRealPlayerEarnings->team_commission                      = $result['team_dividend'];   
                $reportRealPlayerEarnings->directlyunder_recharge_amount        = $result['directlyunder_recharge_amount'];
                $reportRealPlayerEarnings->directlyunder_withdraw_amount        = $result['directlyunder_withdraw_amount'];
                $reportRealPlayerEarnings->team_recharge_amount                 = $result['team_recharge_amount'];
                $reportRealPlayerEarnings->team_withdraw_amount                 = $result['team_withdraw_amount'];
                $reportRealPlayerEarnings->team_stock                           = $result['team_stock'];
                $reportRealPlayerEarnings->team_stock_change                    = $result['team_stock_change'];
                $reportRealPlayerEarnings->directlyunder_stock                  = $result['directlyunder_stock'];
                $reportRealPlayerEarnings->directlyunder_stock_change           = $result['directlyunder_stock_change'];
                $reportRealPlayerEarnings->earnings                             = $result['earnings'];
                $reportRealPlayerEarnings->venue_fee                            = 0;
                $reportRealPlayerEarnings->lastaccumulation                     = $result['lastaccumulation'];
                $reportRealPlayerEarnings->amount                               = $result['amount'];
                $reportRealPlayerEarnings->directlyunder_recharge_people_number = $result['directlyunder_recharge_people_number'];
                $reportRealPlayerEarnings->register_people_number               = $result['register_people_number'];
                $reportRealPlayerEarnings->directlyunder_people_number          = $result['directlyunder_people_number'];
                $reportRealPlayerEarnings->from_day                             = $startDay;
                $reportRealPlayerEarnings->end_day                              = $endDay;
                $reportRealPlayerEarnings->save();
            }
            $level ++;
        }while($level <= $maxLevel);
    }
}
