<?php namespace App\Lib;

use App\Utils\File\FileHelper;
use App\Models\Player;
use App\Models\PlayerInviteCode;
use App\Lib\Cache\CarrierCache;
use App\Models\Conf\PlayerSetting;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\PlayerCommission;
use App\Models\PlayerTransfer;
use App\Models\PlayerAccount;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Report\ReportRealPlayerEarnings;
use App\Models\Map\CarrierPreFixGamePlat;
use App\Lib\Cache\PlayerCache;
use App\Models\CarrierPlayerGrade;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\PlayerGameAccount;

//代理自玩业绩算自已，代理下一级算直属。未开分红的会员推的会员也算直属。开了分红的下级推的人算团队  //分红=充值到帐金额*(1-运营费比例) -提-库存  金辉娱乐
class DevidendMode5{

    //推广赚钱
    public static function promoteAndMakeMoney($input,$user)
    {
        $data                          = [];
        $player                        = Player::select('soncount','descendantscount')->where('player_id',$user->player_id)->first();

        $subordinateDirectPlayerIds    = PlayerSetting::where('parent_id',$user->player_id)->where('guaranteed',0)->pluck('player_id')->toArray();
        $allRids                       = PlayerSetting::where('rid','like',$user->rid.'|%')->pluck('rid')->toArray();

        $grandsonPlarIds               = [];
        foreach ($allRids as $key => $value) {
            foreach ($subordinateDirectPlayerIds as $key1 => $value1) {
                if(strpos($value,strval($value1)) !== false ){
                    $arr = explode('|',$value);
                    $grandsonPlarIds[] = intval(end($arr));
                }
            }
        }

        $underDirectPlayerIds = array_merge($grandsonPlarIds,$subordinateDirectPlayerIds);
        $underDirectPlayerIds = array_unique($underDirectPlayerIds);

        //直属人数
        $data['soncount']              = count($underDirectPlayerIds);

        $totalPersonNumber             = Player::where('rid','like',$user->rid.'|%')->count();
        //下级总人数
        $data['descendantscount']      = $totalPersonNumber - $data['soncount'];

        //今日新增直属
        $data['todaysoncount']         = Player::whereIn('player_id',$underDirectPlayerIds)->where('created_at','>=',date('Y-m-d').' 00:00:00')->count() ;

        //今日新增总人数
        $todaydescendantscount         = Player::where('rid','like',$user->rid.'|%')->where('created_at','>=',date('Y-m-d').' 00:00:00')->count();
        $data['todaydescendantscount'] = $todaydescendantscount - $data['todaysoncount'];

        //推广链接
        $playerInviteCode              = PlayerInviteCode::where('player_id',$user->player_id)->first();

        $h5url                         = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'h5url',$user->prefix);

        $h5urlArr                      = explode(',',$h5url);


        if(!empty($playerInviteCode->domain)){
            $links                         = $playerInviteCode->domain.',';
        } else{
            $links                         = '';
        }

        foreach ($h5urlArr as $key => $value) {
            $links.='https://'.$playerInviteCode->code.'.'.$value.',';
            
        }
        $links = rtrim($links,',');

        //推广链接
        $data['links']   = $links;
        $data['links1']  = [];

        if(!empty($playerInviteCode->domain)){
            $links                         = $playerInviteCode->domain;
            $data['links1'][]              = $playerInviteCode->domain;
        } else{
            foreach ($h5urlArr as $key => $value) {
                $links.='https://'.$playerInviteCode->code.'.'.$value.',';

                $data['links1'][] = 'https://'.$playerInviteCode->code.'.'.$value;
            }
        }

        //自已的保底金额
        $selfPlayerSetting  = PlayerCache::getPlayerSetting($user->player_id);

        if($selfPlayerSetting->guaranteed >0){
            //今日直属业绩
            $underDirectPlayerIds[] = $user->player_id;
            $playerBetFlowMiddle   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'player_id')->where('whether_recharge',1)->where('created_at','>=',date('Y-m-d').' 00:00:00')->whereIn('player_id',$underDirectPlayerIds)->get();
        } else{
            //今日直属业绩
            $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'player_id')->where('whether_recharge',1)->where('created_at','>=',date('Y-m-d').' 00:00:00')->whereIn('player_id',$underDirectPlayerIds)->get();
        }

        //直属佣金计算
        $data['soncommission'] = 0;
        foreach ($playerBetFlowMiddle as $key => $value) {   
            $data['soncommission']+=bcdiv($value->agent_process_available_bet_amount *$selfPlayerSetting->guaranteed,10000,4);
        }

        $data['soncommission'] = bcdiv($data['soncommission'],1,2);

        //团队佣金计算
        $data['team_commission'] = 0;

        $teamPlayerSettings     = PlayerSetting::where('parent_id',$user->player_id)->get();
        $sonRateArr = [];
        foreach ($teamPlayerSettings as $key => $value) {
            $sonRateArr[$value->player_id] = $value->guaranteed;
        }

        $sonRateArr[$user->player_id] = $selfPlayerSetting->guaranteed;

        $teamPlayerIds                = PlayerSetting::where('parent_id',$user->player_id)->where('guaranteed','>',0)->pluck('player_id')->toArray();

        $playerBetFlowMiddles         = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'rid')->where('carrier_id',$user->carrier_id)->where('day',date('Ymd'))->where('whether_recharge',1)->where('rid','like',$user->rid.'|%')->groupBy('rid')->get();

        foreach ($playerBetFlowMiddles as $key => $value) {
            foreach ($teamPlayerIds as $key1 => $value1) {
                if(strpos($value,strval($value1)) !== false ){
                    $data['team_commission'] += bcdiv($value->available_bet_amount*($selfPlayerSetting->guaranteed - $sonRateArr[$value1]),10000,4);
                }
            }
        }

        $data['team_commission']      = bcdiv($data['team_commission'],1,2);

        //今日YU估金
        $data['today_commission']     = bcdiv($data['soncommission']+$data['team_commission'],1,2);

        //昨日佣金
        $playerCommission             = PlayerCommission::where('player_id',$user->player_id)->where('day',date('Ymd',strtotime('-1 day')))->first();

        if($playerCommission){
            $data['yesterday_commission'] = $playerCommission->amount;
        } else{
            $data['yesterday_commission'] = 0;
        }

        $data['yesterday_commission'] = bcdiv($data['yesterday_commission'],10000,2);

        //可领取佣金
        $playerCommission             = PlayerCommission::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->where('status',0)->first();

        if($playerCommission){
            $data['available_commission'] = $playerCommission->amount;
        } else{
            $data['available_commission'] = 0;
        }

        $data['available_commission'] = bcdiv($data['available_commission'],10000,2);

        //本周佣金
        $weekTime       = getWeekStartEnd(date('Y-m-d'));
        $startDate      = $weekTime[2];
        $endDate        = $weekTime[3];

        $playerCommission             = PlayerCommission::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if($playerCommission){
            $data['weekly_commission'] = $playerCommission->amount;
        } else{
            $data['weekly_commission'] = 0;
        }

        $data['weekly_commission'] = bcdiv($data['weekly_commission'],10000,2);

        return $data;
    }

    //我的直属
    public static function myDirectlyunder($input,$user)
    {
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(!isset($input['startDate']) && !isset($input['endDate'])){
            $input['startDate'] = date('Y-m-d');
            $input['endDate']   = date('Y-m-d');
        }

        $noWalletPassageRate = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'no_wallet_passage_rate',$user->prefix);
        $walletPassageRate   = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'wallet_passage_rate',$user->prefix);

        $query          = Player::select('player_id','extend_id','player_level_id','is_online','user_name','frozen_status','day','login_at','descendantscount','carrier_id')->where('parent_id',$user->player_id);

        if(isset($input['player_id']) && !empty($input['player_id'])){
            if(strlen($input['player_id'])==8){
                $query->where('player_id',$input['player_id']);
            }else{
                $query->where('extend_id',$input['player_id']);
            }   
        }

        $total      = count($query->get());
        $items      = $query->skip($offset)->take($pageSize)->get();

        $playerIds = [];
        foreach ($items as $key => $value) {
            $playerIds[] = $value->player_id;
        }

        $allPlayerSettings = PlayerSetting::whereIn('player_id',$playerIds)->get();
        $allDividends      = [];
        $allGuaranteed     = [];
        foreach ($allPlayerSettings as $key => $value) {
            $allGuaranteed[$value->player_id] = $value->guaranteed;
            $allDividends[$value->player_id]  = $value->earnings;
        }

        $query1 = PlayerTransfer::select('player_id',\DB::raw('sum(amount) as amount'))->whereIn('player_id',$playerIds)->where('type','recharge');
        $query2 = PlayerTransfer::select('player_id',\DB::raw('sum(amount) as amount'))->whereIn('player_id',$playerIds)->where('type','withdraw_finish');
        $query3 = PlayerDepositPayLog::select('player_id',\DB::raw('sum(amount) as amount'))->where('is_wallet_recharge',0)->where('status',1)->whereIn('player_id',$playerIds); 
        $query4 = PlayerDepositPayLog::select('player_id',\DB::raw('sum(amount) as amount'))->where('is_wallet_recharge',1)->where('status',1)->whereIn('player_id',$playerIds); 

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query1->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query2->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query3->where('review_time','>=',strtotime($input['startDate']));
            $query4->where('review_time','>=',strtotime($input['startDate']));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query1->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query2->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query3->where('review_time','<',strtotime($input['endDate'])+86400);
            $query4->where('review_time','<',strtotime($input['endDate'])+86400);
        }

        $playerRecharges                     = $query1->groupBy('player_id')->get();
        $playerWithdraws                     = $query2->groupBy('player_id')->get();
        $directlyunderNoWalletRecharges      = $query3->groupBy('player_id')->get();
        $directlyunderWalletRecharges        = $query4->groupBy('player_id')->get();

        $playerRechargesArr = [];

        foreach ($playerRecharges as $key => $value) {
            $playerRechargesArr[$value->player_id] = $value->amount;
        }

        foreach ($directlyunderNoWalletRecharges as $key => $value) {
            $playerRechargesArr[$value->player_id]     -= bcdiv($value->amount*$noWalletPassageRate,100,0);
        }

        foreach ($directlyunderWalletRecharges as $key => $value) {
            $playerRechargesArr[$value->player_id]     -= bcdiv($value->amount*$walletPassageRate,100,0);
        }

        $playerWithdrawArr  = [];
        foreach ($playerWithdraws as $key => $value) {
            $playerWithdrawArr[$value->player_id] = $value->amount;
        }

        $query1 = PlayerBetFlowMiddle::select('player_id',\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'))->where('whether_recharge',1)->whereIn('player_id',$playerIds);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query1->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query1->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $playerBetFlows = $query1->groupBy('player_id')->get();

        $playerBetFlowArr = [];
        foreach ($playerBetFlows as $key => $value) {
            $playerBetFlowArr[$value->player_id] = $value->agent_process_available_bet_amount;
        }

        $playerGrades                  = CarrierPlayerGrade::where('carrier_id',$user->carrier_id)->orderBy('sort','asc')->get();
        $levelNameArr                  = [];

        foreach ($playerGrades as $key => $value) {
            $levelNameArr[$value->id] = $value->level_name;
        }

        $playerAccounts    = PlayerAccount::whereIn('player_id',$playerIds)->get();
        $playerAccountArr  = [];
        foreach ($playerAccounts as $key => $value) {
            $playerAccountArr[$value->player_id] = $value->balance + $value->frozen + $value->agentbalance + $value->agentfrozen;
        }

        //三方钱包余额
        $gameBalances         = PlayerGameAccount::select('player_id',\DB::raw('sum(balance) as balance'))->whereIn('player_id',$playerIds)->groupBy('player_id')->get();
        $gameWallets          = [];

        foreach ($gameBalances as $key => $value) {
            $gameWallets[$value->player_id] = $value->balance;
        }

        foreach ($items as $k => &$v) {
            if(isset($playerBetFlowArr[$v->player_id])){
                $v->agent_process_available_bet_amount = $playerBetFlowArr[$v->player_id];
            } else{
                $v->agent_process_available_bet_amount = 0;
            }

            if(isset($playerRechargesArr[$v->player_id])){
                $v->recharge_amount = bcdiv($playerRechargesArr[$v->player_id],10000,0);
            } else{
                $v->recharge_amount = 0;
            }

            if(isset($playerWithdrawArr[$v->player_id])){
                $v->withdraw_amount = bcdiv($playerWithdrawArr[$v->player_id],10000,0);
            } else{
                $v->withdraw_amount = 0;
            }

            if(!isset($gameWallets[$v->player_id])){
                $gameWallets[$v->player_id] = 0;
            }

            $v->balance    = bcdiv($playerAccountArr[$v->player_id],10000,0) + $gameWallets[$v->player_id];
            $v->guaranteed = $allGuaranteed[$v->player_id];
            $v->earnings   = $allDividends[$v->player_id];
            $v->extend_id  = PlayerCache::getExtendIdByplayerId($v->carrier_id,$v->player_id);
            $v->level      = $levelNameArr[$v->player_level_id];
            $len           = strlen($v->user_name);
            $v->user_name  = substr($v->user_name,0,$len-5).'***';

            $v->day       = date('Y-m-d',strtotime($v->day));
            $v->login_at  = date('Y-m-d',strtotime($v->login_at));
        }

        return ['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    //我的团队
    public static function myTeam($input,$user)
    {
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(!isset($input['startDate']) && !isset($input['endDate'])){
            $input['startDate'] = date('Y-m-d');
            $input['endDate']   = date('Y-m-d');
        }

        $directSubordinateIds    = Player::where('parent_id',$user->player_id)->pluck('player_id')->toArray();
        $allSubordinatePlayerIds = Player::where('rid','like',$user->rid.'|%')->pluck('player_id')->toArray(); 
        $noWalletPassageRate     = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'no_wallet_passage_rate',$user->prefix);
        $walletPassageRate       = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'wallet_passage_rate',$user->prefix);

        $query                = Player::select('player_id','extend_id','user_name','day','descendantscount','is_online')->where('parent_id',$user->player_id);

        if(isset($input['player_id']) && !empty($input['player_id'])){
            if(strlen($input['player_id'])==8){
                $query->where('player_id',$input['player_id']);
            }else{
               $searchPlaeyrId = PlayerCache::getPlayerIdByExtentId($user->prefix,$input['player_id']);
               $query->where('player_id',$searchPlaeyrId);
            }  
        }

        $total      = count($query->get());
        $items      = $query->skip($offset)->take($pageSize)->get();

        $playerIds  = $directSubordinateIds;

        $carrierPlayerGrades = CarrierPlayerGrade::where('prefix',$user->prefix)->get();
        $carrierPlayerGradesArr = [];
        foreach ($carrierPlayerGrades as $key => $value) {
            $carrierPlayerGradesArr[$value->id] = $value->level_name;
        }

        $players            = Player::whereIn('player_id',$playerIds)->get();
        $playersLevel       = [];
        
        foreach ($players as $key => $value) {
            $playersLevel[$value->player_id]       = $carrierPlayerGradesArr[$value->player_level_id];
        }

        $playerTransfersQuery = PlayerTransfer::select('player_id','rid',\DB::raw('sum(amount) as amount'))->where('type','recharge')->whereIn('player_id',$allSubordinatePlayerIds);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $playerTransfersQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $playerTransfersQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $playerTransfers = $playerTransfersQuery->groupBy('player_id')->get();
        $playerRecharges = [];
        foreach ($playerTransfers as $key => $value) {
            foreach ($directSubordinateIds as $key1 => $value1) {
                if($value1 != $value->player_id && strpos($value->rid,strval($value1)) !== false){
                    if(isset($playerRecharges[$value1])){
                        $playerRecharges[$value1] += $value->amount;
                    } else{
                        $playerRecharges[$value1] = $value->amount;
                    }
                }
            }
        }

        //非钱包充值金额
        $directlyunderNoWalletRechargeAmountQuery   =  PlayerDepositPayLog::select('player_id','rid',\DB::raw('sum(amount) as amount'))->where('is_wallet_recharge',0)->where('status',1)->whereIn('player_id',$allSubordinatePlayerIds);

        //钱包充值金额
        $directlyunderWalletRechargeAmountQuery   =  PlayerDepositPayLog::select('player_id','rid',\DB::raw('sum(amount) as amount'))->where('is_wallet_recharge',1)->where('status',1)->whereIn('player_id',$allSubordinatePlayerIds);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $directlyunderNoWalletRechargeAmountQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $directlyunderWalletRechargeAmountQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $directlyunderNoWalletRechargeAmountQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $directlyunderWalletRechargeAmountQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $directlyunderNoWalletRechargeAmount   = $directlyunderNoWalletRechargeAmountQuery->groupBy('player_id')->get();

        foreach ($directlyunderNoWalletRechargeAmount as $key => $value) {
            foreach ($directSubordinateIds as $key1 => $value1) {
                if($value1 != $value->player_id && strpos($value->rid,strval($value1)) !== false){
                    if(isset($playerRecharges[$value1])){
                        $playerRecharges[$value1] -= bcdiv($value->amount*$noWalletPassageRate,100,0);;
                    }
                }
            }
        }

        $directlyunderWalletRechargeAmount   = $directlyunderWalletRechargeAmountQuery->groupBy('player_id')->get();

        foreach ($directlyunderWalletRechargeAmount as $key => $value) {
            foreach ($directSubordinateIds as $key1 => $value1) {
                if($value1 != $value->player_id && strpos($value->rid,strval($value1)) !== false){
                    if(isset($playerRecharges[$value1])){
                        $playerRecharges[$value1] -= bcdiv($value->amount*$walletPassageRate,100,0);;
                    }
                }
            }
        }

        $playerTransfersQuery = PlayerTransfer::select('player_id','rid',\DB::raw('sum(amount) as amount'))->where('type','withdraw_finish')->whereIn('player_id',$allSubordinatePlayerIds);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $playerTransfersQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $playerTransfersQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $playerTransfers = $playerTransfersQuery->groupBy('player_id')->get();

        $playerWithdraws = [];
        foreach ($playerTransfers as $key => $value) {
            foreach ($directSubordinateIds as $key1 => $value1) {
                if($value1 != $value->player_id && strpos($value->rid,strval($value1)) !== false){
                    if(isset($playerWithdraws[$value1])){
                        $playerWithdraws[$value1] += $value->amount;
                    } else{
                        $playerWithdraws[$value1] = $value->amount;
                    }
                }
            }
        }


        $playerTransfersQuery = PlayerBetFlowMiddle::select('player_id','rid',\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'))->where('whether_recharge',1)->whereIn('player_id',$allSubordinatePlayerIds);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $playerTransfersQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $playerTransfersQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $playerTransfers = $playerTransfersQuery->groupBy('player_id')->get();

        $playerBetFlows = [];
        foreach ($playerTransfers as $key => $value) {
            foreach ($directSubordinateIds as $key1 => $value1) {
                if($value1 != $value->player_id && strpos($value->rid,strval($value1)) !== false){
                    if(isset($playerBetFlows[$value1])){
                        $playerBetFlows[$value1] += $value->agent_process_available_bet_amount;
                    } else{
                        $playerBetFlows[$value1] = $value->agent_process_available_bet_amount;
                    }
                }
            }
        }

        $playerTransfers      = PlayerAccount::select('rid','player_id','balance','frozen','agentbalance','agentfrozen')->whereIn('player_id',$allSubordinatePlayerIds)->get();
        
        $balanceArr           = [];

        foreach ($playerTransfers as $key => $value) {
            foreach ($directSubordinateIds as $key1 => $value1) {
                if($value1 != $value->player_id && strpos($value->rid,strval($value1)) !== false){
                    if(isset($balanceArr[$value1])){
                        $balanceArr[$value1] = $balanceArr[$value1] + $value->balance + $value->frozen + $value->agentbalance + $value->agentfrozen;
                    } else{
                        $balanceArr[$value1] = $value->balance + $value->frozen + $value->agentbalance + $value->agentfrozen;
                    }
                }
            }
        }

        //三方钱包余额
        $gameBalances         = PlayerGameAccount::select('rid',\DB::raw('sum(balance) as balance'))->whereIn('player_id',$allSubordinatePlayerIds)->groupBy('rid')->get();
        
        $gameWallets          = [];

        foreach ($gameBalances as $key => $value) {
            foreach ($directSubordinateIds as $key1 => $value1) {
                if(strpos($value->rid,strval($value1)) !== false){
                    if(isset($gameWallets[$value1])){
                        $gameWallets[$value1] = $gameWallets[$value1] + $value->balance;
                    } else{
                        $gameWallets[$value1] = $value->balance;
                    }
                }
            }
        }

        $playerSettings = PlayerSetting::whereIn('player_id',$playerIds)->get();
        $earningsArr    = [];
        $guaranteedArr  = [];

        foreach ($playerSettings as $key => $value) {
            $earningsArr[$value->player_id]   = $value->earnings;
            $guaranteedArr[$value->player_id] = $value->guaranteed;
        }

        foreach ($items as $k => &$v) {
            if(isset($playerRecharges[$v->player_id])){
                $v->rechargesAmount = bcdiv($playerRecharges[$v->player_id],10000,2);
            } else{
                $v->rechargesAmount = 0;
            }

            if(isset($playerWithdraws[$v->player_id])){
                $v->withdrawAmount = bcdiv($playerWithdraws[$v->player_id],10000,2);
            } else{
                $v->withdrawAmount = 0;
            }

            $v->level      = $playersLevel[$v->player_id];
            $v->day        = date('Y-m-d',strtotime($v->day));
            $v->earnings   = $earningsArr[$v->player_id];
            $v->guaranteed = $guaranteedArr[$v->player_id];

            $userNameLen    = strlen($v->user_name);
            $v->user_name   = substr($v->user_name,0,$userNameLen-5).'***';

            if(!isset($balanceArr[$v->player_id])){
                $balanceArr[$v->player_id] = 0;
            }

            if(!isset($gameWallets[$v->player_id])){
                $gameWallets[$v->player_id] = 0;
            }

            $v->balance     = $balanceArr[$v->player_id] + $gameWallets[$v->player_id]*10000;
            $v->agent_process_available_bet_amount = isset($playerBetFlows[$v->player_id]) ? $playerBetFlows[$v->player_id] :0;
        }

        return ['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }
    //业绩查询
    public static function performanceinQuiry($input,$user)
    {
        $startDate = null;
        $endDate   = null;

        $rows = [
            '1'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'selfPerformance'          => 0,
                'commission'               => 0
            ],
            '2'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'selfPerformance'          => 0,
                'commission'               => 0
            ],
            '3'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'selfPerformance'          => 0,
                'commission'               => 0
            ],
            '4'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'selfPerformance'          => 0,
                'commission'               => 0
            ],
            '5'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'selfPerformance'          => 0,
                'commission'               => 0
            ],
            '6'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'selfPerformance'          => 0,
                'commission'               => 0
            ],
            '7'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'selfPerformance'          => 0,
                'commission'               => 0
            ],
        ];

        $totalperformance         = 0;
        $directlyunderPerformance = 0;
        $teamPerformance          = 0;
        $commission               = 0;
        $directlyunderCommission  = 0;
        $teamCommission           = 0; 
        $selfCommission           = 0;
        $selfPerformance          = 0;

        //获取自已的保底
        $selfPlayerSetting   = PlayerCache::getPlayerSetting($user->player_id);

        //查询自已的业绩
        if($selfPlayerSetting->guaranteed>0){
            //查询子用户团队业绩
            $selfPerformanceQuery                          = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'game_category')->where('player_id',$user->player_id)->where('whether_recharge',1)->groupBy('game_category');

            if(isset($input['startDate']) && strtotime($input['startDate'])){
                $startDate = date('Ymd',strtotime($input['startDate']));
                $selfPerformanceQuery->where('day','>=',$startDate);
            }

            if(isset($input['endDate']) && strtotime($input['endDate'])){
                $endDate   = date('Ymd',strtotime($input['endDate']));
                $selfPerformanceQuery->where('day','<=',$endDate);
            }

            $selfPerformanceQuery          = $selfPerformanceQuery->get();

            foreach ($selfPerformanceQuery as $k => $v) {
                $rows[$v->game_category]['totalPerformance'] += $v->agent_process_available_bet_amount;
                $rows[$v->game_category]['teamPerformance']  += $v->agent_process_available_bet_amount;
                $rows[$v->game_category]['commission']       += bcdiv($v->agent_process_available_bet_amount*$selfPlayerSetting->guaranteed,10000,4);

                $totalperformance                            += $v->agent_process_available_bet_amount;
                $selfPerformance                             += $v->agent_process_available_bet_amount;
                $commission                                  += bcdiv($v->agent_process_available_bet_amount*$selfPlayerSetting->guaranteed,10000,4);
                $selfCommission                              += bcdiv($v->agent_process_available_bet_amount*$selfPlayerSetting->guaranteed,10000,4);
            }
        }

        //影射处理
        $subordinatePlayerSettings = PlayerSetting::where('parent_id',$user->player_id)->get();
        $allSubordinatePlayers     = Player::where('rid','like',$user->rid.'|%')->get();

        $playerMap                 = [];
        $subordinateGuaranteeds    = [];

        foreach ($subordinatePlayerSettings as $key => $value) {
            $subordinateGuaranteeds[$value->player_id] = $value->guaranteed;
            foreach ($allSubordinatePlayers as $k => $v) {
                if(strpos($v->rid,$value->rid) !== false ){
                    $playerMap[$v->player_id] = $value->player_id;
                }
            }
        }

        //查询所有团队
        $subordinateTeamPlayerIds      = PlayerSetting::where('parent_id',$user->player_id)->where('guaranteed','>',0)->pluck('player_id')->toArray();
        $allRids                       = PlayerSetting::where('rid','like',$user->rid.'|%')->pluck('rid')->toArray();

        $grandsonPlarIds               = [];
        foreach ($allRids as $key => $value) {
            foreach ($subordinateTeamPlayerIds as $key1 => $value1) {
                if(strpos($value,strval($value1)) !== false ){
                    $arr = explode('|',$value);
                    $grandsonPlarIds[] = intval(end($arr));
                }
            }
        }

        $allTeamPlayerIds = array_merge($grandsonPlarIds,$subordinateTeamPlayerIds);
        $allTeamPlayerIds = array_unique($allTeamPlayerIds);

        $totalPerformanceQuery  = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'game_category','player_id')->whereIn('player_id',$allTeamPlayerIds)->where('whether_recharge',1)->groupBy('game_category','player_id');

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $startDate = date('Ymd',strtotime($input['startDate']));
            $totalPerformanceQuery->where('day','>=',$startDate);    
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $endDate   = date('Ymd',strtotime($input['endDate']));
            $totalPerformanceQuery->where('day','<=',$endDate);    
        }

        $totalPerformanceQuery          = $totalPerformanceQuery->get();

        foreach ($totalPerformanceQuery as $k => $v) {
            $rows[$v->game_category]['totalPerformance'] += $v->agent_process_available_bet_amount;
            $rows[$v->game_category]['teamPerformance']  += $v->agent_process_available_bet_amount;
            $rows[$v->game_category]['commission']       += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed -$subordinateGuaranteeds[$playerMap[$v->player_id]]),10000,4);

            $totalperformance                            += $v->agent_process_available_bet_amount;
            $teamPerformance                             += $v->agent_process_available_bet_amount;
            $commission                                  += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed -$subordinateGuaranteeds[$playerMap[$v->player_id]]),10000,4);
            $teamCommission                              += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed -$subordinateGuaranteeds[$playerMap[$v->player_id]]),10000,4);
        }
        //查询所有团队结束

        //查询所有的直属
        $subordinateDirectPlayerIds    = PlayerSetting::where('parent_id',$user->player_id)->where('guaranteed',0)->pluck('player_id')->toArray();

        $grandsonPlarIds               = [];
        foreach ($allRids as $key => $value) {
            foreach ($subordinateDirectPlayerIds as $key1 => $value1) {
                if(strpos($value,strval($value1)) !== false ){
                    $arr = explode('|',$value);
                    $grandsonPlarIds[] = intval(end($arr));
                }
            }
        }

        $underDirectPlayerIds = array_merge($grandsonPlarIds,$subordinateDirectPlayerIds);
        $allDirectPlayerIds = array_unique($underDirectPlayerIds);

        $directlyundersPerformanceQuery                 = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'game_category','player_id')->whereIn('player_id',$allDirectPlayerIds)->where('whether_recharge',1)->groupBy('game_category','player_id');

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $startDate = date('Ymd',strtotime($input['startDate']));
            $directlyundersPerformanceQuery->where('day','>=',$startDate);
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $endDate   = date('Ymd',strtotime($input['endDate']));
            $directlyundersPerformanceQuery->where('day','<=',$endDate);
        }

        $directlyundersPerformanceQuery = $directlyundersPerformanceQuery->get();

        foreach ($directlyundersPerformanceQuery as $k => $v) {
            $rows[$v->game_category]['totalPerformance']          += $v->agent_process_available_bet_amount;
            $rows[$v->game_category]['directlyunderPerformance']  += $v->agent_process_available_bet_amount;
            $rows[$v->game_category]['commission']                += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed-$subordinateGuaranteeds[$playerMap[$v->player_id]]),10000,4);

            $totalperformance                                     += $v->agent_process_available_bet_amount;
            $directlyunderPerformance                             += $v->agent_process_available_bet_amount;
            $commission                                           += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed -$subordinateGuaranteeds[$playerMap[$v->player_id]]),10000,4);
            $directlyunderCommission                              += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed -$subordinateGuaranteeds[$playerMap[$v->player_id]]),10000,4);
        }

        //查询所有的直属结束
        foreach ($rows as $key => &$value) {
            $value['commission']               = (string)bcdiv($value['commission'],1,2);
            $value['directlyunderPerformance'] = (string)bcdiv($value['directlyunderPerformance'],1,2);
            $value['totalPerformance']         = (string)bcdiv($value['totalPerformance'],1,2);
            $value['teamPerformance']         = (string)bcdiv($value['teamPerformance'],1,2);
        }

        $data                                                         = [];
        $data['rows']                                                 = $rows;
        $data['totalperformance']                                     = (string)bcdiv($totalperformance,1,2);
        $data['directlyunderPerformance']                             = (string)bcdiv($directlyunderPerformance,1,2);
        $data['teamPerformance']                                      = (string)bcdiv($teamPerformance,1,2);
        $data['commission']                                           = (string)bcdiv($commission,1,2);
        $data['directlyunderCommission']                              = (string)bcdiv($directlyunderCommission,1,2);
        $data['teamCommission']                                       = (string)bcdiv($teamCommission,1,2);
        $data['selfCommission']                                       = (string)bcdiv($selfCommission,1,2);
        $data['selfPerformance']                                      = (string)bcdiv($selfPerformance,1,2);
        
        return $data;
    }

    //实时分红
    public static function calculateDividend($user,$startDate=null,$endDate=null)
    {
        $playerRealTimeDividendsStartDay                   = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'player_realtime_dividends_start_day',$user->prefix);
        $operatingExpenses                                 = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'operating_expenses',$user->prefix);
        $operatingExpenses                                 = bcdiv(100-$operatingExpenses,100,2);

        if(!is_null($startDate)){
            $startTime                                 = date('Y-m-d',strtotime($startDate)).' 00:00:00';
            $endTime                                   = date('Y-m-d',strtotime($endDate)).'23:59:59';
        } else{
            $startDate                                 = date('Ymd',strtotime($playerRealTimeDividendsStartDay));
            $endDate                                   = date('Ymd');
            $startTime                                 = date('Y-m-d',strtotime($playerRealTimeDividendsStartDay)).' 00:00:00';
            $endTime                                   = date('Y-m-d').'23:59:59';
        }

        $stock                                     = 0;
        $teamstock                                 = 0;
        $teamStockDiff                             = 0;
        $directlyunderRechargeAmount               = 0;
        $directlyunderRechargeAmount1              = 0;
        $directlyunderWithdrawAmount               = 0;
        $directlyundervenueFee                     = 0;
        $directlyunderGift                         = 0;
        $directlyunderCommission                   = 0;
        $directlyunderCompanyWinAmount             = 0;
        $directlyunderDividend                     = 0;
        $directlyunderNotReceived                  = 0;
        $teamRechargeAmount                        = 0;
        $teamRechargeAmount1                       = 0;
        $teamWithdrawAmount                        = 0;
        $teamvenueFee                              = 0;
        $teamGift                                  = 0;
        $teamCommission                            = 0;
        $teamCompanyWinAmount                      = 0;
        $teamDividend                              = 0;
        $selfNotReceived                           = 0;
        $selfRechargeAmount                        = 0;
        $selfRechargeAmount1                       = 0;
        $selfWithdrawAmount                        = 0;
        $selfvenueFee                              = 0;
        $selfGift                                  = 0;
        $selfCommission                            = 0;
        $selfCompanyWinAmount                      = 0;
        $selfDividend                              = 0;
        $data                                      = [];
        $data['team_stock_change']                 = 0;
        $data['self_stock_change']                 = 0;
        $data['directlyunder_stock_change']        = 0;
        $data['selfRecharge']                      = 0;
        $data['selfWithdraw']                      = 0;

        //游戏平台点位
        $carrierGamePlats         = CarrierPreFixGamePlat::where('carrier_id',$user->carrier_id)->where('prefix',$user->prefix)->get();
        $gamePlatPoints           = [];

        foreach ($carrierGamePlats as $key => $value) {
            $gamePlatPoints[$value->game_plat_id] = $value->point;
        }

        $selfPlayerSetting               = PlayerCache::getPlayerSetting($user->player_id);
        $reportPlayerEarnings            = ReportPlayerEarnings::where('player_id',$user->player_id)->orderBy('id','desc')->first();
                        
        if($reportPlayerEarnings){
            $lastaccumulation    = $reportPlayerEarnings->accumulation;
            $isAllowFastGrant    = $reportPlayerEarnings->is_allow_fast_grant;
        } else {
            $lastaccumulation    = 0;
            $isAllowFastGrant    = 1;
        }

        $playerBetFlowCommission  = PlayerCommission::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if(is_null($playerBetFlowCommission->amount)){
            $directlyundervenueFee   = 0;
        } else{
            $directlyundervenueFee   = $playerBetFlowCommission->amount;
        }

        //自已的充提
        $selfRecharge =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->where('type','recharge')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if($selfRecharge && !is_null($selfRecharge->amount)){
            $selfRechargeAmount        = $selfRecharge->amount;
        }

        //自已的充提1
        $selfRecharge1 =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->whereIn('type',['dividend_from_parent','agent_reimbursement'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if($selfRecharge1 && !is_null($selfRecharge1->amount)){
            $selfRechargeAmount1        = $selfRecharge1->amount;
        }

        $data['selfRecharge']          = $selfRechargeAmount*$operatingExpenses +  $selfRechargeAmount1;


        $selfWithdraw =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->where('type','withdraw_finish')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if($selfWithdraw && !is_null($selfWithdraw->amount)){
            $selfWithdrawAmount        = $selfWithdraw->amount;
        }

        $data['selfWithdraw'] = $selfWithdrawAmount;

        //未领佣金
        $playerCommissionGift       = PlayerCommission::select(\DB::raw('sum(amount) as amount'))->where('day','>=',$startDate)->where('day','<=',$endDate)->where('status',0)->where('player_id',$user->player_id)->first();

        //如果结束时间大于当前时间
        if(date('Ymd')<$endDate){
            //直属提现
            $endReportPlayerStatDay =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->where('player_id',$user->player_id)->where('day',date('Ymd'))->first();
        } else{
            $endReportPlayerStatDay =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->where('player_id',$user->player_id)->where('day',$endDate)->first();
        }

        $startReportPlayerStatDay   =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->where('player_id',$user->player_id)->where('day',date('Ymd',strtotime($startDate)-86400))->first();

        if($startReportPlayerStatDay && !is_null($startReportPlayerStatDay->self_stock)){
            $selfStockChange        = $endReportPlayerStatDay->self_stock - $startReportPlayerStatDay->self_stock;
        } else{
            $selfStockChange        = $endReportPlayerStatDay->self_stock;
        }

        if($playerCommissionGift && !is_null($playerCommissionGift->amount)){
            $selfNotReceived = $playerCommissionGift->amount;
        }

        $data['self_stock_change'] = $selfStockChange; 
        $selfDividend              = bcdiv(($selfRechargeAmount*$operatingExpenses + $selfRechargeAmount1- $selfWithdrawAmount- $data['self_stock_change'] - $selfNotReceived)*$selfPlayerSetting->earnings,100,2) ;

        //查询所有的直属
        $allRids                       = PlayerSetting::where('rid','like',$user->rid.'|%')->pluck('rid')->toArray();
        $subordinateDirectPlayerIds    = PlayerSetting::where('parent_id',$user->player_id)->where('earnings',0)->pluck('player_id')->toArray();

        $grandsonPlarIds               = [];
        foreach ($allRids as $key => $value) {
            foreach ($subordinateDirectPlayerIds as $key1 => $value1) {
                if(strpos($value,strval($value1)) !== false ){
                    $arr = explode('|',$value);
                    $grandsonPlarIds[] = intval(end($arr));
                }
            }
        }

        $underDirectPlayerIds     = array_merge($grandsonPlarIds,$subordinateDirectPlayerIds);
        $directlyunderPlayerIds   = array_unique($underDirectPlayerIds);

        $directlyunderRecharge  =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$directlyunderPlayerIds)->where('type','recharge')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if($directlyunderRecharge && !is_null($directlyunderRecharge->amount)){
            $directlyunderRechargeAmount        = $directlyunderRecharge->amount;
        }

        $directlyunderRecharge1  =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$directlyunderPlayerIds)->whereIn('type',['dividend_from_parent','agent_reimbursement'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if($directlyunderRecharge1 && !is_null($directlyunderRecharge1->amount)){
            $directlyunderRechargeAmount1       = $directlyunderRecharge1->amount;
        }

        $directlyunderRechargePeopleNumber            = PlayerTransfer::where('type','recharge')->where('day','>=',$startDate)->where('day','<=',$endDate)->whereIn('player_id',$directlyunderPlayerIds)->pluck('player_id')->toArray();
        $data['directlyunder_recharge_people_number'] = count(array_unique($directlyunderRechargePeopleNumber));

        $registerPeopleNumber                           = Player::where('day','>=',$startDate)->where('day','<=',$endDate)->whereIn('player_id',$directlyunderPlayerIds)->pluck('player_id')->toArray();
        $data['register_people_number']                 = count(array_unique($registerPeopleNumber));
        $data['directlyunder_people_number']            = $user->soncount;

        //直属提现
        $directlyunderWithdraw =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$directlyunderPlayerIds)->where('type','withdraw_finish')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        //直属提现金额
        if($directlyunderWithdraw && !is_null($directlyunderWithdraw->amount)){
            $directlyunderWithdrawAmount        += $directlyunderWithdraw->amount;
        } 

        //直属库存
        if(date('Ymd') < $endDate){
            $reportPlayerEndStatDay = ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->whereIn('player_id',$directlyunderPlayerIds)->where('day',date('Ymd'))->first();
        }else{
            $reportPlayerEndStatDay = ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->whereIn('player_id',$directlyunderPlayerIds)->where('day',$endDate)->first();
        }

        $reportPlayerStartStatDay = ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->whereIn('player_id',$directlyunderPlayerIds)->where('day',date('Ymd',strtotime($startDate)-86400))->first();

        if($reportPlayerStartStatDay && !is_null($reportPlayerStartStatDay->self_stock)){
            $data['directlyunder_stock_change'] = $data['directlyunder_stock_change'] + $reportPlayerEndStatDay->self_stock - $reportPlayerStartStatDay->self_stock;
        } else{
            $data['directlyunder_stock_change'] = $data['directlyunder_stock_change'] + $reportPlayerEndStatDay->self_stock;
        }

        //未领佣金
        $playerCommissionGift              = PlayerCommission::select(\DB::raw('sum(amount) as amount'))->where('day','>=',$startDate)->where('day','<=',$endDate)->where('status',0)->whereIn('player_id',$directlyunderPlayerIds)->first();
        if($playerCommissionGift && !is_null($playerCommissionGift->amount)){
            $directlyunderNotReceived  =  $playerCommissionGift->amount;
        }

        $directlyunderDividend              = bcdiv(($directlyunderRechargeAmount*$operatingExpenses + $directlyunderRechargeAmount1 - $directlyunderWithdrawAmount- $data['directlyunder_stock_change'] - $directlyunderNotReceived)*$selfPlayerSetting->earnings,100,2) ;

        //查询所有的直属
        $subordinateTeamPlayerIds    = PlayerSetting::where('parent_id',$user->player_id)->where('earnings','>',0)->pluck('player_id')->toArray();

        $grandsonPlarIds               = [];
        $playerSons                    = [];
        foreach ($allRids as $key => $value) {
            foreach ($subordinateTeamPlayerIds as $key1 => $value1) {
                if(strpos($value,strval($value1)) !== false ){
                    $arr               = explode('|',$value);
                    $tmp               = intval(end($arr));
                    $grandsonPlarIds[] = $tmp;
                    $playerSons[$tmp]  = $value1;
                }
            }
        }

        $teamPlayerIds     = array_merge($grandsonPlarIds,$subordinateTeamPlayerIds);
        $teamPlayerIds     = array_unique($teamPlayerIds);

        //直属充值
        $summaryTeamRecharge             =  PlayerTransfer::select('amount','player_id')->whereIn('player_id',$teamPlayerIds)->where('type','recharge')->where('day','>=',$startDate)->where('day','<=',$endDate)->get();

        //直属充值1
        $summaryTeamRecharge1             =  PlayerTransfer::select('amount','player_id')->whereIn('player_id',$teamPlayerIds)->whereIn('type',['dividend_from_parent','agent_reimbursement'])->where('day','>=',$startDate)->where('day','<=',$endDate)->get();

        //直属提现
        $summaryTeamWithdraw             =  PlayerTransfer::select('amount','player_id')->whereIn('player_id',$teamPlayerIds)->where('type','withdraw_finish')->where('day','>=',$startDate)->where('day','<=',$endDate)->get();

        //未领佣金
        $playerCommissionGift              = PlayerCommission::select(\DB::raw('sum(amount) as amount'),'player_id')->where('day','>=',$startDate)->where('day','<=',$endDate)->where('status',0)->whereIn('player_id',$teamPlayerIds)->groupBy('player_id')->get();

        if(date('Ymd') <= $endDate){
            $summaryTeamEndStock          =  ReportPlayerStatDay::select('self_stock','player_id')->where('day',date('Ymd'))->whereIn('player_id',$teamPlayerIds)->get();
        } else{
            $summaryTeamEndStock          =  ReportPlayerStatDay::select('self_stock','player_id')->where('day',$endDate)->whereIn('player_id',$teamPlayerIds)->get();
        } 

        $summaryTeamStartStock            =  ReportPlayerStatDay::select('self_stock','player_id')->where('day',date('Ymd',strtotime($startDate)-86400))->whereIn('player_id',$teamPlayerIds)->get();
        
        //团队数据开始
        $directlyUnderPlayerIds          = Player::where('parent_id',$user->player_id)->where('win_lose_agent',1)->pluck('player_id')->toArray();
        $directlyUnderPlayerSettings     = PlayerSetting::whereIn('player_id',$directlyUnderPlayerIds)->get();
        foreach ($directlyUnderPlayerSettings as $k3 => $v3) {
            $tempRechargeAmount   = 0;
            $tempRechargeAmount1  = 0;
            $tempWithdrawAmount   = 0;
            $tempGift             = 0;
            $tempCommission       = 0;
            $tempCompanyWinAmount = 0;
            $tempStock            = 0;
            $tempStartStock       = 0;
            $tempNotReceived      = 0;

            //直属充值
            foreach ($summaryTeamRecharge as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $teamRechargeAmount        += $value->amount;
                    $tempRechargeAmount        += $value->amount;
                }
            }

            //直属充值
            foreach ($summaryTeamRecharge1 as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $teamRechargeAmount1        += $value->amount;
                    $tempRechargeAmount1        += $value->amount;
                }
            }

            //直属提现
            foreach ($summaryTeamWithdraw as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $teamWithdrawAmount        += $value->amount;
                    $tempWithdrawAmount        += $value->amount;
                }
            }

            //未领取佣金
            foreach ($playerCommissionGift as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $tempNotReceived        += $value->amount;
                }
            }

            //直属库存
            foreach ($summaryTeamEndStock as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    //团队库存
                    $tempStock               += $value->self_stock;
                }
            }

            //直属开始库存
            foreach ($summaryTeamStartStock as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    //团队库存
                    $tempStartStock             += $value->self_stock;
                }
            }

            //团队库存变化   = （团队存款 + 团队活动礼金 + 团队保底  -团队提现 + 团队游戏输赢）*自已的分红比例
            $teamstock                  = $tempStock - $tempStartStock; 
            $data['team_stock_change'] += $teamstock;
            $teamDividend              += bcdiv(($tempRechargeAmount*$operatingExpenses + $tempRechargeAmount1- $tempWithdrawAmount- $teamstock - $tempNotReceived)*($selfPlayerSetting->earnings - $v3->earnings),100,2); 
        }

        $currDate = date('Ymd');
        if($endDate > $currDate){
            $reportPlayerStatDay              = ReportPlayerStatDay::where('player_id',$user->player_id)->where('day',$currDate)->first();
        } else{
            $reportPlayerStatDay              = ReportPlayerStatDay::where('player_id',$user->player_id)->where('day',$endDate)->first();
        }
        
        $data['directlyunderRecharge']    = $directlyunderRechargeAmount*$operatingExpenses + $directlyunderRechargeAmount1+$selfRechargeAmount*$operatingExpenses + $selfRechargeAmount1;
        $data['teamRecharge']             = $teamRechargeAmount*$operatingExpenses + $teamRechargeAmount1;
        $data['directlyunderWithdraw']    = $directlyunderWithdrawAmount + $selfWithdrawAmount;
        $data['teamWithdraw']             = $teamWithdrawAmount;
        $data['totalCommission']          = $teamDividend + $directlyunderDividend  + $selfDividend - $directlyundervenueFee;
        $data['teamDividend']             = $teamDividend;
        $data['directlyunderDividend']    = $directlyunderDividend + $selfDividend;
        $data['directlyunder_stock_change'] += $data['self_stock_change'];
        $data['earnings']                 = $selfPlayerSetting->earnings;
        $data['lastaccumulation']         = $lastaccumulation;
        if($reportPlayerStatDay){
            $data['directlyunder_stock']      = $reportPlayerStatDay->stock+$reportPlayerStatDay->self_stock;
            $data['team_stock']               = $reportPlayerStatDay->team_stock;
        } else{
            $data['directlyunder_stock']      = 0;
            $data['team_stock']               = 0;
        }

        $data['venue_fee']                = $directlyundervenueFee;
        $data['directlyunderVenuesFee']   = 0;
        $data['teamVenuesFee']            = 0;
        $data['is_allow_fast_grant']      = $isAllowFastGrant;

        return $data;
    }

    //计算方式3的分红
    public static function stockCalculateDividend($maxLevel,$minLevel,$allPlayers,$carrierPreFixDomain)
    {
        $playerDividendsStartDay               = CarrierCache::getCarrierMultipleConfigure($carrierPreFixDomain->carrier_id,'player_dividends_start_day',$carrierPreFixDomain->prefix);
        $playerDividendsDay                    = CarrierCache::getCarrierMultipleConfigure($carrierPreFixDomain->carrier_id,'player_dividends_day',$carrierPreFixDomain->prefix);
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
        $level                                 = $minLevel;
        $data                                  = [];


        do{
            $cyclePlayers = Player::where('level',$level)->whereIn('player_id',$allPlayers)->where('win_lose_agent',1)->orderby('player_id','asc')->get();
            foreach ($cyclePlayers as $k => $v) {
            $result                               = self::calculateDividend($v,$startDay,$endDay);
            $row                                  = [];
            $row['carrier_id']                    = $v->carrier_id;
            $row['rid']                           = $v->rid;
            $row['top_id']                        = $v->top_id;
            $row['parent_id']                     = $v->parent_id;
            $row['player_id']                     = $v->player_id;
            $row['is_tester']                     = $v->is_tester;
            $row['inviteplayerid']                = $v->inviteplayerid;
            $row['user_name']                     = $v->user_name;
            $row['level']                         = $v->level;
            $row['descendantscount']              = $v->descendantscount;
            $row['prefix']                        = $v->prefix;
            $row['created_at']                    = date('Y-m-d H:i:s');
            $row['updated_at']                    = date('Y-m-d H:i:s');
            $row['activepersonacount']            = 0;
            $row['availableadd']                  = 0;
            $row['availableadd']                  = 0;
            $row['directlyunder_recharge_amount'] = $result['directlyunderRecharge'];
            $row['directlyunder_withdraw_amount'] = $result['directlyunderWithdraw'];
            $row['team_recharge_amount']          = $result['teamRecharge'];
            $row['team_withdraw_amount']          = $result['teamWithdraw'];
            $row['team_stock']                    = $result['team_stock'];
            $row['team_stock_change']             = $result['team_stock_change'];
            $row['directlyunder_stock']           = $result['directlyunder_stock'];
            $row['directlyunder_stock_change']    = $result['directlyunder_stock_change'];
            $row['init_time']                     = time();
            $row['earnings']                      = $result['earnings'];
            $row['venue_fee']                     = $result['venue_fee'];
            $row['amount']                        = $result['totalCommission'] + $result['lastaccumulation'];
            $row['lastaccumulation']              = $result['lastaccumulation'];
            $row['from_day']                      = $startDay;
            $row['end_day']                       = $endDay;
            $row['directlyunderVenuesFee']        = $result['directlyunderVenuesFee'];
            $row['teamVenuesFee']                 = $result['teamVenuesFee'];
            $row['direct_commission']             = $result['directlyunderDividend'];
            $row['team_commission']               = $result['teamDividend'];
            $row['is_allow_fast_grant']           = $result['is_allow_fast_grant'];

            $data[]                               = $row;
            }
            if(count($data)){
                \DB::table('report_player_earnings')->insert($data);
                $data = [];
            }
            $level ++;
        }while($level <= $maxLevel);
    }


    public static function singleStockCalculateByday($player, $number=0) 
    {
        $changeStock        = 0;
        $directlyUnderStock = 0;
        $changeTeamStock    = 0;
        $teamStock          = 0;
        
        $allPlayerNoAgentIds = Player::where('parent_id',$player->player_id)->where('win_lose_agent',0)->pluck('player_id')->toArray();
        $allPlayerIds        = Player::where('rid','like',$player->rid.'|%')->pluck('rid')->toArray();

        $allSonPlayerIds    = [];
        foreach ($allPlayerNoAgentIds as $key7 => $value7) {
            foreach ($allPlayerIds as $key8 => $value8) {
                $arr = explode('|', $value8);
                $tmp = intval(end($arr));
                if(strpos($value8,strval($value7))!==false && $tmp != $value7){
                    
                    $allSonPlayerIds[] = $tmp;
                }
            }
        }

       if(!$number){
            $directlyunderReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$allSonPlayerIds)->where('day',date('Ymd'))->first();
            
       } elseif($number==1){
            $directlyunderReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$allSonPlayerIds)->where('day',date('Ymd',strtotime('-1 day')))->first();

       } else{
            $directlyunderReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$allSonPlayerIds)->where('day',date('Ymd',strtotime(-$number.' days')))->first();
       }


       $directlyUnderChangeStock = 0;
       if($directlyunderReportPlayerStatDay && !is_null($directlyunderReportPlayerStatDay->change_self_stock)){
            $directlyUnderChangeStock = $directlyunderReportPlayerStatDay->change_self_stock;
       }
      
       //计算团队库存
        $allPlayerNoAgentIds = Player::where('parent_id',$player->player_id)->where('win_lose_agent',1)->pluck('player_id')->toArray();
        $allPlayerIds        = Player::where('rid','like',$player->rid.'|%')->pluck('rid')->toArray();

        $allSonPlayerIds    = [];
        foreach ($allPlayerNoAgentIds as $key7 => $value7) {
            foreach ($allPlayerIds as $key8 => $value8) {
                $arr = explode('|', $value8);
                $tmp = intval(end($arr));
                if(strpos($value8,strval($value7))!==false && $tmp != $value7){
                    
                    $allSonPlayerIds[] = $tmp;
                }
            }
        }

        if(!$number){
            $teamReportPlayerStatDay         = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$allSonPlayerIds)->where('day',date('Ymd'))->first();
        } elseif($number==1){
            $teamReportPlayerStatDay         = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$allSonPlayerIds)->where('day',date('Ymd',strtotime('-1 day')))->first();
        } else{
            $teamReportPlayerStatDay         = ReportPlayerStatDay::select(\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$allSonPlayerIds)->where('day',date('Ymd',strtotime(-$number.' day')))->first();
        }

        $teamChangeStock = 0;
        if($teamReportPlayerStatDay && !is_null($teamReportPlayerStatDay->change_self_stock)){
            $teamChangeStock = $teamReportPlayerStatDay->change_self_stock;
        }

       if(!$number){
            $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$player->player_id)->where('day',date('Ymd',strtotime('-1 day')))->first();
        } else{
            $number                 = $number+1;
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

    //实时计算方式3的分红
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
                $reportRealPlayerEarnings->direct_commission                    = $result['directlyunderDividend'] ;
                $reportRealPlayerEarnings->team_commission                      = $result['teamDividend'];   
                $reportRealPlayerEarnings->directlyunder_recharge_amount        = $result['directlyunderRecharge'];
                $reportRealPlayerEarnings->directlyunder_withdraw_amount        = $result['directlyunderWithdraw'];
                $reportRealPlayerEarnings->team_recharge_amount                 = $result['teamRecharge'];
                $reportRealPlayerEarnings->team_withdraw_amount                 = $result['teamWithdraw'];
                $reportRealPlayerEarnings->team_stock                           = $result['team_stock'];
                $reportRealPlayerEarnings->team_stock_change                    = $result['team_stock_change'];
                $reportRealPlayerEarnings->directlyunder_stock                  = $result['directlyunder_stock'];
                $reportRealPlayerEarnings->directlyunder_stock_change           = $result['directlyunder_stock_change'];
                $reportRealPlayerEarnings->earnings                             = $result['earnings'];
                $reportRealPlayerEarnings->venue_fee                            = $result['venue_fee'];
                $reportRealPlayerEarnings->lastaccumulation                     = $result['lastaccumulation'];
                $reportRealPlayerEarnings->amount                               = $result['totalCommission'] + $result['lastaccumulation'];
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
