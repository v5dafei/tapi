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
use App\Models\CarrierPlayerGrade;
use App\Lib\Cache\PlayerCache;

class DevidendMode1{
    //业绩查询
    //（充值*运营费-提现）*分红比例 - 保底*比例
    public static function performanceinQuiry($input,$user)
    {
        $startDate = null;
        $endDate   = null;

        $rows = [
            '1'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'commission'               => 0
            ],
            '2'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'commission'               => 0
            ],
            '3'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'commission'               => 0
            ],
            '4'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'commission'               => 0
            ],
            '5'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'commission'               => 0
            ],
            '6'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'commission'               => 0
            ],
            '7'=>[
                'totalPerformance'         => 0,
                'directlyunderPerformance' => 0,
                'teamPerformance'          => 0,
                'commission'               => 0
            ],
        ];

        $totalperformance         = 0;
        $directlyunderPerformance = 0;
        $teamPerformance          = 0;
        $commission               = 0;
        $directlyunderCommission  = 0;
        $teamCommission           = 0; 

        //获取自已的保底
        $selfPlayerSetting        = PlayerCache::getPlayerSetting($user->player_id);

        //查询所有的直属
        $directlyUnderRids        = PlayerSetting::where('parent_id',$user->player_id)->pluck('rid')->toArray();
        $directlyUnderPlayerIds   = PlayerSetting::where('parent_id',$user->player_id)->pluck('player_id')->toArray();
        $directlyunders           = PlayerSetting::where('parent_id',$user->player_id)->get();

        //查询所有的团队
        $teamRids                 = PlayerSetting::where('rid','like',$user->rid.'|%')->where('parent_id','!=',$user->player_id)->pluck('rid')->toArray();
        $directTeams              = PlayerSetting::where('rid','like',$user->rid.'|%')->where('parent_id','!=',$user->player_id)->get();

        $teamPlayerIds            = PlayerSetting::where('rid','like',$user->rid.'|%')->where('parent_id','!=',$user->player_id)->pluck('player_id')->toArray();
        $teams                    = PlayerSetting::where('rid','like',$user->rid.'|%')->where('parent_id','!=',$user->player_id)->get();
        $playerMap                = [];

        foreach ($directlyunders as $key => $value) {
            foreach ($teams as $k => $v) {
                if(strpos($v->rid,$value->rid) !== false ){
                    $playerMap[$v->player_id] = $value->player_id;
                }
            }
        }

        $totalPerformanceQuery                          = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'game_category','player_id')->whereIn('player_id',$teamPlayerIds)->where('whether_recharge',1)->groupBy('game_category');

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $startDate = date('Ymd',strtotime($input['startDate']));
            $totalPerformanceQuery->where('day','>=',$startDate);    
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $endDate   = date('Ymd',strtotime($input['endDate']));
            $totalPerformanceQuery->where('day','<=',$endDate);    
        }

        //查询出所有的团队业绩
        $totalPerformanceQuery          = $totalPerformanceQuery->get();

        $allDirect                      = PlayerSetting::where('parent_id',$user->player_id)->get();

        //直属的保底
        $directlyunderArr = [];
        foreach ($allDirect as $k => $v) {
            $directlyunderArr[$v->player_id] = $v->guaranteed;
        }

        foreach ($totalPerformanceQuery as $k => $v) {
            $rows[$v->game_category]['totalPerformance'] += $v->agent_process_available_bet_amount;
            $rows[$v->game_category]['teamPerformance']  += $v->agent_process_available_bet_amount;
            $rows[$v->game_category]['commission']       += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed -$directlyunderArr[$playerMap[$v->player_id]]),10000,4);

            $totalperformance                            += $v->agent_process_available_bet_amount;
            $teamPerformance                             += $v->agent_process_available_bet_amount;
            $commission                                  += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed -$directlyunderArr[$playerMap[$v->player_id]]),10000,4);
            $teamCommission                              += bcdiv($v->agent_process_available_bet_amount*($selfPlayerSetting->guaranteed -$directlyunderArr[$playerMap[$v->player_id]]),10000,4);
        }

        //团队处理结束
        $directlyundersPerformanceQuery                 = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'game_category')->whereIn('player_id',$directlyUnderPlayerIds)->where('whether_recharge',1)->groupBy('game_category');

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
            $rows[$v->game_category]['commission']                += bcdiv($v->agent_process_available_bet_amount*$selfPlayerSetting->guaranteed,10000,4);

            $totalperformance                                     += $v->agent_process_available_bet_amount;
            $directlyunderPerformance                             += $v->agent_process_available_bet_amount;
            $commission                                           += bcdiv($v->agent_process_available_bet_amount*$selfPlayerSetting->guaranteed,10000,4);
            $directlyunderCommission                              += bcdiv($v->agent_process_available_bet_amount*$selfPlayerSetting->guaranteed,10000,4);
        }
        //直属优化结束
        foreach ($rows as $key => &$value) {
            $value['commission'] = (string)bcdiv($value['commission'],1,2);
        }

        $data                                                         = [];
        $data['rows']                                                 = $rows;
        $data['totalperformance']                                     = $totalperformance;
        $data['directlyunderPerformance']                             = $directlyunderPerformance;
        $data['teamPerformance']                                      = $teamPerformance;
        $data['commission']                                           = (string)bcdiv($commission,1,2);
        $data['directlyunderCommission']                              = (string)bcdiv($directlyunderCommission,1,2);
        $data['teamCommission']                                       = (string)bcdiv($teamCommission,1,2);
        
        return $data;
    }

    //推广赚钱
    public static function promoteAndMakeMoney($input,$user)
    {
        $data                    = [];
        $underPlayerIds          = PlayerSetting::where('parent_id',$user->player_id)->pluck('player_id')->toArray();

        //直属人数
        $data['soncount']       = count($underPlayerIds);
        $teamPlayerIds          = PlayerSetting::where('parent_id','!=',$user->player_id)->where('rid','like',$user->rid.'|%')->pluck('player_id')->toArray();

        //团队人数
        $data['descendantscount']      = count($teamPlayerIds);

        //今日新增直属
        $data['todaysoncount']         = Player::whereIn('player_id',$underPlayerIds)->where('created_at','>=',date('Y-m-d').' 00:00:00')->count() ;

        //今日新增总人数
        $data['todaydescendantscount'] = Player::whereIn('player_id',$teamPlayerIds)->where('created_at','>=',date('Y-m-d').' 00:00:00')->count();

        //推广链接
        $playerInviteCode              = PlayerInviteCode::where('player_id',$user->player_id)->first();

        $h5url                         = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'h5url',$user->prefix);

        $h5urlArr                      = explode(',',$h5url);
        $links                         = '';

        $data['links1']                = [];

        if(!empty($playerInviteCode->domain)){
            $links                         = $playerInviteCode->domain;
            $data['links1'][]              = $playerInviteCode->domain;
        } else{
            foreach ($h5urlArr as $key => $value) {
                $links.='https://'.$playerInviteCode->code.'.'.$value.',';

                $data['links1'][] = 'https://'.$playerInviteCode->code.'.'.$value;
            }
        }

        $carrierPlayerGrades = CarrierPlayerGrade::where('prefix',$user->prefix)->orderBy('sort','asc')->get();
        $carrierPlayerGradesArr = [];
        foreach ($carrierPlayerGrades as $k => $v) {
            //$carrierPlayerGradesArr[$value->id] = $value->level_name;
            $row                      = [];
            $row['key']               = $v->id;
            $row['value']             = $v->level_name;
            $row['key1']               = $v->sort;
            $row['value1']             = $v->level_name;
            $carrierPlayerGradesArr[] = $row;
        }

        //推广链接
        $data['links']        = rtrim($links,',');
        $data['playergrades'] = $carrierPlayerGradesArr;
        $data['level']        = $user->player_level_id;

        //自已的保底金额
        $selfPlayerSetting   = PlayerCache::getPlayerSetting($user->player_id);

        //今日直属业绩
        $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'),'player_id')->whereIn('player_id',$underPlayerIds)->where('created_at','>=',date('Y-m-d').' 00:00:00')->where('whether_recharge',1)->groupBy('player_id')->get();

        //查询所有直属的保底金额
        $sonRateArr    = [];
        $palyerSetting = PlayerSetting::where('parent_id',$user->player_id)->get();

        foreach ($palyerSetting as $key => $value) {
            $sonPlayers = Player::select('player_id')->where('rid','like',$value->rid.'%')->get();
            foreach ($sonPlayers as $key1 => $value1) {
                $sonRateArr[$value1->player_id] = $value->guaranteed;
            }
        }

        //直属佣金计算
        $data['soncommission'] = 0;
        foreach ($playerBetFlowMiddle as $key => $value) {   
            $data['soncommission']+=bcdiv($value->agent_process_available_bet_amount *$selfPlayerSetting->guaranteed,10000,4);
        }

        $data['soncommission']    = bcdiv($data['soncommission'],1,2);
        $data['selfcommission']   = 0;
        //团队佣金计算
        $data['team_commission']  = 0;

        $teamBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id')->where('carrier_id',$user->carrier_id)->where('day',date('Ymd'))->whereIn('player_id',$teamPlayerIds)->where('whether_recharge',1)->groupBy('player_id')->get();
            
        $teamCategroies = [];

        foreach ($teamBetFlows as $k1 => $v1) {

            $data['team_commission'] += bcdiv($v1->available_bet_amount*($selfPlayerSetting->guaranteed - $sonRateArr[$v1->player_id]),10000,4);
        }

        $data['team_commission']      = bcdiv($data['team_commission'],1,2);
        //今日YU估金
        $data['today_commission']     = bcdiv($data['soncommission']+$data['team_commission'] + $data['selfcommission'],1,2);

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

    //直属成员
    public static function newMyDirectlyunder($input,$user)
    {
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $total          = 0;

        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return '对不起，开始时间不正确';
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return '对不起，结束时间不正确';
        }

        if(!isset($input['sort']) || !in_array($input['sort'],['rechargeAmount','withdrawAmount','performancein'])){
            return '对不起，排序字段取值不正确';
        }

        $input['startDate']           = date('Ymd',strtotime($input['startDate']));
        $input['endDate']             = date('Ymd',strtotime($input['endDate']));

        $playerSettings               = PlayerSetting::where('parent_id',$user->player_id)->get();
        $playerSettingsGuaranteedArr  = [];

        foreach ($playerSettings as $key => $value) {
            $playerSettingsGuaranteedArr[$value->player_id] = $value->guaranteed;
        }

        //下级直属
        $subordinateDirectPlayerIds = PlayerSetting::where('parent_id',$user->player_id)->pluck('player_id')->toArray();
        $query                      = Player::select('player_id','extend_id','user_name','rid','created_at','carrier_id')->whereIn('player_id',$subordinateDirectPlayerIds);

        if(isset($input['player_id']) && !empty($input['player_id'])){
            if(strlen($input['player_id'])==8){
                $players = $query->where('player_id',$input['player_id']);
            } else{
                $players = $query->where('extend_id',$input['player_id']);
            }
        } 

        $players = $query->get();
        $total   = count($players);

        $rows    = [];

        foreach ($players as $key => $value) {
            $row                     = [];
            $row['player_id']        = $value->player_id;
            $row['extend_id']        = $value->extend_id;
            $row['user_name']        = substr($value->user_name,0,-2);
            $row['created_at']       = date('Y-m-d',strtotime($value->created_at));

            $row['soncount']         = Player::where('parent_id',$value->player_id)->count();
            $row['descendantscount'] = Player::where('rid','like',$value->rid.'|%')->count();
            $rechargeQuery           = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('prefix',$value->prefix)->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->where('rid','like',$value->rid.'%')->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->first();

            $directlyunderRecharge   = 0;

            if($rechargeQuery && !is_null($rechargeQuery->amount)){
                $directlyunderRecharge = $rechargeQuery->amount;
            }

            $row['rechargeAmount']   = bcdiv($directlyunderRecharge,10000,2);
            $row['guaranteed']       = $playerSettingsGuaranteedArr[$value->player_id];
           
            $rows[]                  = $row;
        }
        
        //人数单独处理
        $flag = [];
        
        foreach ($rows as $key => $value) {
            $flag[] = $value['rechargeAmount']; 
        }
        array_multisort($flag, SORT_DESC, $rows);

        $selfPlayerSetting   = PlayerCache::getPlayerSetting($user->player_id);

        $playerIds = [];
        foreach ($rows as $key => $value) {
            $playerIds[] = $value['player_id'];
        }

        $playerIds          = array_slice($playerIds, $offset,$pageSize);
        $data               = [];
        foreach ($rows as $key => $value) {
            if(in_array($value['player_id'],$playerIds)){
                $data[] = $value;
            }
        }

        $data['rows']       = $data;
        $data['guaranteed'] = $selfPlayerSetting->guaranteed;
        $data['earnings']   = $selfPlayerSetting->earnings;

        return ['item' => $data,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    //我的直属
    public static function myDirectlyunder($input,$user)
    {
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $total          = 0;

        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return '对不起，开始时间不正确';
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return '对不起，结束时间不正确';
        }

        if(!isset($input['sort']) || !in_array($input['sort'],['rechargeAmount','withdrawAmount','performancein'])){
            return '对不起，排序字段取值不正确';
        }

        $input['startDate'] = date('Ymd',strtotime($input['startDate']));
        $input['endDate']   = date('Ymd',strtotime($input['endDate']));

        //下级直属
        $subordinateDirectPlayerIds = PlayerSetting::where('parent_id',$user->player_id)->pluck('player_id')->toArray();

        $query = Player::select('player_id','extend_id','user_name','soncount','descendantscount','rid')->whereIn('player_id',$subordinateDirectPlayerIds);

        if(isset($input['player_id']) && !empty($input['player_id'])){

            if(strlen($input['player_id'])==8){
                $players = Player::select('player_id','extend_id','user_name','soncount','descendantscount','rid','created_at','carrier_id')->whereIn('player_id',$subordinateDirectPlayerIds)->where('player_id',$input['player_id'])->get();
            } else{
                $players = Player::select('player_id','extend_id','user_name','soncount','descendantscount','rid','created_at','carrier_id')->whereIn('player_id',$subordinateDirectPlayerIds)->where('extend_id',$input['player_id'])->get();
            }

            $total   = count($players);
        } else{
            $allSonPlayers     = PlayerSetting::where('parent_id',$user->player_id)->get();
            $allSonPlayerIds   = $subordinateDirectPlayerIds;

            $sortArr = [];
            if($input['sort']=='rechargeAmount'){
                $rechargePlayerTransfer = PlayerTransfer::select('amount','player_id')->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->whereIn('player_id',$allSonPlayerIds)->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->get();

                foreach ($rechargePlayerTransfer as $key => $value) {
                    if(isset($sortArr[$value->player_id])){
                        $sortArr[$value->player_id] += $value->amount;
                    } else{
                        $sortArr[$value->player_id] =  $value->amount;
                    }
                }
                arsort($sortArr);
            } elseif($input['sort']=='withdrawAmount'){
                $withdrawPlayerTransfer = PlayerTransfer::select('amount','player_id')->where('type','withdraw_finish')->whereIn('player_id',$allSonPlayerIds)->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->get();
                foreach ($withdrawPlayerTransfer as $key => $value) {
                    if(isset($sortArr[$value->player_id])){
                        $sortArr[$value->player_id] += $value->amount;
                    } else{
                        $sortArr[$value->player_id] =  $value->amount;
                    }
                }
                arsort($sortArr);
            } elseif($input['sort']=='performancein'){
                $playerBetFlow = PlayerBetFlowMiddle::select('agent_process_available_bet_amount','player_id')->whereIn('player_id',$allSonPlayerIds)->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->get();
                foreach ($playerBetFlow as $key => $value) {
                    if(isset($sortArr[$value->player_id])){
                        $sortArr[$value->player_id] += $value->amount;
                    } else{
                        $sortArr[$value->player_id] =  $value->amount;
                    }
                }
                arsort($sortArr);
            }

            $allplayerids     = array_keys($sortArr);
            $tempPlayerIds    = array_diff($subordinateDirectPlayerIds, $allplayerids);
            $allsortPlayerIds = array_merge($allplayerids,$tempPlayerIds);

            $total            = count($allsortPlayerIds);
            $items            = array_slice($allsortPlayerIds, $offset,$pageSize);

            $players = Player::select('player_id','extend_id','user_name','soncount','descendantscount','rid','created_at','carrier_id')->whereIn('player_id',$items)->get();
        }

        $rows    = [];

        foreach ($players as $key => $value) {
            $row                     = [];
            $row['player_id']        = $value->player_id;
            $row['extend_id']        = $value->extend_id;
            $row['user_name']        = substr($value->user_name,0,-2);
            $row['created_at']       = date('Y-m-d',strtotime($value->created_at));
            $row['soncount']         = $value->soncount;
            $row['descendantscount'] = $value->descendantscount-$value->soncount;

            $rechargeQuery           = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$value->player_id)->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->first();

            $directlyunderRecharge      = 0;

            if($rechargeQuery && !is_null($rechargeQuery->amount)){
                $directlyunderRecharge = $rechargeQuery->amount;
            }

            $row['rechargeAmount']      = bcdiv(($directlyunderRecharge),10000,2);

            $withdrawQuery           = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$value->player_id)->where('type','withdraw_finish')->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->first();
            $row['withdrawAmount']   = bcdiv($withdrawQuery->amount,10000,2);

            $performanceinQuery      = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'))->where('player_id',$value->player_id)->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->where('whether_recharge',1)->first();
            $playerSetting   = PlayerCache::getPlayerSetting($value->player_id);
            $row['performancein']    = is_null($performanceinQuery->agent_process_available_bet_amount) ? 0: bcdiv($performanceinQuery->agent_process_available_bet_amount,1,2);
            $row['guaranteed']       = $playerSetting->guaranteed;
            $row['earnings']         = $playerSetting->earnings;

            $playerAccount           = PlayerAccount::where('player_id',$value->player_id)->first();
            $row['balance']          = bcdiv($playerAccount->agentbalance + $playerAccount->agentfrozen + $playerAccount->balance + $playerAccount->frozen,10000,2);
            $rows[]                  = $row;
        }
        
        //人数单独处理
        $flag = [];
        
        foreach ($rows as $key => $value) {
            $flag[] = $value[$input['sort']]; 
        }
        array_multisort($flag, SORT_DESC, $rows);

        $selfPlayerSetting   = PlayerCache::getPlayerSetting($user->player_id);

        $data['rows']       = $rows;
        $data['guaranteed'] = $selfPlayerSetting->guaranteed;
        $data['earnings']   = $selfPlayerSetting->earnings;

        return ['item' => $data,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    //我的团队
    public static function myTeam($input,$user)
    {
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $total          = 0;

        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return '对不起，开始时间不正确';
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return '对不起，结束时间不正确';
        }

        if(!isset($input['sort']) || !in_array($input['sort'],['rechargeAmount','withdrawAmount','performancein'])){
            return '对不起，排序字段取值不正确';
        }

        $input['startDate'] = date('Ymd',strtotime($input['startDate']));
        $input['endDate']   = date('Ymd',strtotime($input['endDate']));

        if(isset($input['player_id']) && !empty($input['player_id'])){
            if(strlen($input['player_id'])==8){
                $players = Player::where('parent_id',$user->player_id)->where('player_id',$input['player_id'])->get();
            }else{
                $players = Player::where('parent_id',$user->player_id)->where('extend_id',$input['player_id'])->get();
            }
            
            $total   = count($players);
        } else{
            $allPlayerNoAgents   = Player::where('parent_id',$user->player_id)->orderBy('player_id','asc')->get();
            $allPlayerNoAgentIds = Player::where('parent_id',$user->player_id)->pluck('player_id')->toArray();

            $allSonPlayers     = Player::where('parent_id','!=',$user->player_id)->where('rid','like',$user->rid.'|%')->get();
            $allSonPlayerIds   = Player::where('parent_id','!=',$user->player_id)->where('rid','like',$user->rid.'|%')->pluck('player_id')->toArray();

            $playerMap         = [];
            $sonPlayerIds      = [];
            foreach ($allPlayerNoAgents as $key => $value) {
                $sonPlayerIds[] = $value->player_id;
                foreach ($allSonPlayers as $k => $v) {
                    if(strpos($v->rid,$value->rid) !== false ){
                        $playerMap[$v->player_id] = $value->player_id;
                    }
                }
            }

            $sortArr = [];
            if($input['sort']=='rechargeAmount'){
                $rechargePlayerTransfer = PlayerTransfer::select('amount','player_id')->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->whereIn('player_id',$allSonPlayerIds)->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->get();
                foreach ($rechargePlayerTransfer as $key => $value) {

                    $directlyunderPlayerId =  $playerMap[$value->player_id];
                    if(isset($sortArr[$directlyunderPlayerId])){
                        $sortArr[$directlyunderPlayerId] += $value->amount;
                    } else{
                        $sortArr[$directlyunderPlayerId] =  $value->amount;
                    }
                }
                arsort($sortArr);
            } elseif($input['sort']=='withdrawAmount'){
                $withdrawPlayerTransfer = PlayerTransfer::select('amount','player_id')->where('type','withdraw_finish')->whereIn('player_id',$allSonPlayerIds)->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->get();
                foreach ($withdrawPlayerTransfer as $key => $value) {

                    $directlyunderPlayerId =  $playerMap[$value->player_id];
                    if(isset($sortArr[$directlyunderPlayerId])){
                        $sortArr[$directlyunderPlayerId] += $value->amount;
                    } else{
                        $sortArr[$directlyunderPlayerId] =  $value->amount;
                    }
                }
                arsort($sortArr);
            } elseif($input['sort']=='performancein'){
                $playerBetFlow = PlayerBetFlowMiddle::select('agent_process_available_bet_amount','player_id')->whereIn('player_id',$allSonPlayerIds)->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->get();
                foreach ($playerBetFlow as $key => $value) {
                    $directlyunderPlayerId =  $playerMap[$value->player_id];
                    if(isset($sortArr[$directlyunderPlayerId])){
                        $sortArr[$directlyunderPlayerId] += $value->agent_process_available_bet_amount;
                    } else{
                        $sortArr[$directlyunderPlayerId] =  $value->agent_process_available_bet_amount;
                    }
                }
                arsort($sortArr);
            }

            $allplayerids     = array_keys($sortArr);
            $tempPlayerIds    = array_diff($allPlayerNoAgentIds, $allplayerids);
            $allsortPlayerIds = array_merge($allplayerids,$tempPlayerIds);

            $total            = count($allSonPlayerIds);
            $items            = array_slice($allsortPlayerIds, $offset,$pageSize);
            $players          = Player::whereIn('player_id',$items)->get();
        }

        $rows    = [];

        foreach ($players as $key => $value) {
            $playerSetting           = PlayerSetting::where('player_id',$value->player_id)->first();
            $playerIds               = Player::where('rid','like',$value->rid.'|%')->pluck('player_id')->toArray();
            $row                     = [];
            $row['player_id']        = $value->player_id;
            $row['extend_id']        = $value->extend_id;
            $row['earnings']         = $playerSetting->earnings;
            $row['guaranteed']       = $playerSetting->guaranteed;
            $row['user_name']        = substr($value->user_name,0,-2);
            $row['created_at']       = date('Y-m-d',strtotime($playerSetting->created_at));

            //查询下级有对冲的
            $row['descendantscount'] = $value->descendantscount ;
            $row['soncount']         = $row['descendantscount'];

            $rechargeQuery           = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$playerIds)->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->first();


            $directlyunderRecharge      = 0;

            if($rechargeQuery && !is_null($rechargeQuery->amount)){
                $directlyunderRecharge = $rechargeQuery->amount;
            }

            $row['rechargeAmount']      = bcdiv($directlyunderRecharge,10000,2);

            $withdrawQuery             = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$playerIds)->where('type','withdraw_finish')->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->first();
            
            $row['withdrawAmount']   = bcdiv($withdrawQuery->amount ,10000,2);

            $performanceinQuery      = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as agent_process_available_bet_amount'))->whereIn('player_id',$playerIds)->where('day','>=',$input['startDate'])->where('day','<=',$input['endDate'])->where('whether_recharge',1)->first();
            $row['performancein']    = is_null($performanceinQuery->agent_process_available_bet_amount) ? 0:$performanceinQuery->agent_process_available_bet_amount;
            $row['performancein']    = bcdiv(bcdiv($performanceinQuery->agent_process_available_bet_amount,1,2),1,2);

            $playerAccount           = PlayerAccount::select(\DB::raw('sum(agentbalance) as agentbalance'),\DB::raw('sum(agentfrozen) as agentfrozen'),\DB::raw('sum(balance) as balance'),\DB::raw('sum(frozen) as frozen'))->whereIn('player_id',$playerIds)->first();
            $row['balance']          = bcdiv($playerAccount->agentbalance + $playerAccount->agentfrozen + $playerAccount->balance + $playerAccount->frozen,10000,2);

            $rows[]                  = $row;
        }


        $flag = [];
        
        foreach ($rows as $key => $value) {
            $flag[] = $value[$input['sort']]; 
        }
        array_multisort($flag, SORT_DESC, $rows);

        $selfPlayerSetting   = PlayerCache::getPlayerSetting($user->player_id);

        $data['rows']       = $rows;

        return ['item' => $data,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    //实时分红
    public static function calculateDividend($user,$startDate=null,$endDate=null)
    {
        $playerRealTimeDividendsStartDay               = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'player_realtime_dividends_start_day',$user->prefix);
        $operatingExpenses                             = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'operating_expenses',$user->prefix);
        $operatingExpenses                             = bcdiv(100-$operatingExpenses,100,2);

        if(is_null($startDate)){
            $startDate                                 = date('Ymd',strtotime($playerRealTimeDividendsStartDay));
            $endDate                                   = date('Ymd');
        }

        $stock                                     = 0;
        $teamstock                                 = 0;
        $teamStockDiff                             = 0;
        $directlyunderRechargeAmount               = 0;
        $directlyunderWithdrawAmount               = 0;
        $directlyundervenueFee                     = 0;
        $directlyunderGift                         = 0;
        $directlyunderCommission                   = 0;
        $directlyunderCompanyWinAmount             = 0;
        $directlyunderDividend                     = 0;
        $teamRechargeAmount                        = 0;
        $teamWithdrawAmount                        = 0;
        $teamvenueFee                              = 0;
        $teamGift                                  = 0;
        $teamCommission                            = 0;
        $teamCompanyWinAmount                      = 0;
        $selfWithdrawAmount                        = 0;
        $selfvenueFee                              = 0;
        $selfGift                                  = 0;
        $selfCommission                            = 0;
        $selfCompanyWinAmount                      = 0;
        $selfDividend                              = 0;
        $data                                      = [];
        $data['teamStockChange']                   = 0;
        $data['selfStockChange']                   = 0;
        $data['directlyunderStockChange']          = 0;
        $data['teamDividend']                      = 0;

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

        //直属即为未开代理的直属下级
        //直属充值
        $directlyunderPlayerIds = PlayerSetting::where('parent_id',$user->player_id)->pluck('player_id')->toArray();
        $directlyunderRecharge  = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->where('day','>=',$startDate)->where('day','<=',$endDate)->whereIn('player_id',$directlyunderPlayerIds)->first();


        $directlyunderRechargePeopleNumber              = PlayerTransfer::where('type','recharge')->where('day','>=',$startDate)->where('day','<=',$endDate)->whereIn('player_id',$directlyunderPlayerIds)->pluck('player_id')->toArray();
        $data['directlyunder_recharge_people_number']   = count(array_unique($directlyunderRechargePeopleNumber));

        $registerPeopleNumber                           = Player::where('day','>=',$startDate)->where('day','<=',$endDate)->whereIn('player_id',$directlyunderPlayerIds)->pluck('player_id')->toArray();
        $data['register_people_number']                 = count(array_unique($registerPeopleNumber));
        $data['directlyunder_people_number']            = $user->soncount;


        if($directlyunderRecharge && !is_null($directlyunderRecharge->amount)){
            $directlyunderRechargeAmount        = $directlyunderRecharge->amount;
        } 

        //直属提现
        $directlyunderWithdraw =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$directlyunderPlayerIds)->where('type','withdraw_finish')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        //直属提现金额
        if($directlyunderWithdraw && !is_null($directlyunderWithdraw->amount)){
            $directlyunderWithdrawAmount        += $directlyunderWithdraw->amount;
        } 

        //如果结束时间大于当前时间
        if(date('Ymd')<$endDate){
            //直属提现
            $endReportPlayerStatDay =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->whereIn('player_id',$directlyunderPlayerIds)->where('day',date('Ymd'))->first();
        } else{
            $endReportPlayerStatDay =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->whereIn('player_id',$directlyunderPlayerIds)->where('day',$endDate)->first();
        }

        $startReportPlayerStatDay   =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->whereIn('player_id',$directlyunderPlayerIds)->where('day',date('Ymd',strtotime($startDate)-86400))->first();
        if($startReportPlayerStatDay && !is_null($startReportPlayerStatDay->self_stock)){
            $directlyunderStockChange        = $endReportPlayerStatDay->self_stock - $startReportPlayerStatDay->self_stock;
        } else{
            $directlyunderStockChange        = $endReportPlayerStatDay->self_stock;
        }

        //直属库存变化
        $data['directlyunderStockChange']   = $directlyunderStockChange;

        $directlyunderDividend              = bcdiv(($directlyunderRechargeAmount*$operatingExpenses - $directlyunderWithdrawAmount)*$selfPlayerSetting->earnings,100,2) ;
        
        //团队数据开始
        $subordinateTeamPlayers             = PlayerSetting::where('parent_id',$user->player_id)->get();
        foreach ($subordinateTeamPlayers as $k3 => $v3) {

            $tempRechargeAmount   = 0;
            $tempWithdrawAmount   = 0;
           
            //团队充值
            $teamRecharge =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('rid','like',$v3->rid.'|%')->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

            //直属提现
            $teamWithdraw =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('rid','like',$v3->rid.'|%')->where('type','withdraw_finish')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

            if($teamRecharge && !is_null($teamRecharge->amount)){
                $teamRechargeAmount        += $teamRecharge->amount;
                $tempRechargeAmount        = $teamRecharge->amount;
            }

            if($teamWithdraw && !is_null($teamWithdraw->amount)){
                $teamWithdrawAmount        += $teamWithdraw->amount;
                $tempWithdrawAmount         = $teamWithdraw->amount;
            }

            //如果结束时间大于当前时间
            if(date('Ymd')<$endDate){
                //直属提现
                $endReportPlayerStatDay =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->where('rid','like',$v3->rid.'|%')->where('day',date('Ymd'))->first();
            } else{
                $endReportPlayerStatDay =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->where('rid','like',$v3->rid.'|%')->where('day',$endDate)->first();
            }

            $startReportPlayerStatDay   =  ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))->where('rid','like',$v3->rid.'|%')->where('day',date('Ymd',strtotime($startDate)-86400))->first();
            if($startReportPlayerStatDay && !is_null($startReportPlayerStatDay->self_stock)){
                $tempTeamStockChange        = $endReportPlayerStatDay->self_stock - $startReportPlayerStatDay->self_stock;
            } else{
                $tempTeamStockChange        = $endReportPlayerStatDay->self_stock;
            }

            //团队库存变化   = （团队存款 + 团队活动礼金 + 团队保底  -团队提现 + 团队游戏输赢）*自已的分红比例
            $data['teamStockChange']   += $tempTeamStockChange;
            $data['teamDividend']      += bcdiv(($tempRechargeAmount*$operatingExpenses - $tempWithdrawAmount)*($selfPlayerSetting->earnings - $v3->earnings),100,2); 
        }

        $reportPlayerStatDay              = ReportPlayerStatDay::where('player_id',$user->player_id)->where('day',$endDate)->first();

        $data['directlyunderRecharge']    = $directlyunderRechargeAmount ;
        $data['directlyunderWithdraw']    = $directlyunderWithdrawAmount ;
        $data['directlyunderDividend']    = $directlyunderDividend ;
        $data['directlyunderStock']       = isset($reportPlayerStatDay->stock) ? $reportPlayerStatDay->stock :0;

        $data['selfRecharge']             = 0;
        $data['selfWithdraw']             = 0;
        $data['selfDividend']             = 0;
        $data['selfStock']                = 0;

        $data['teamRecharge']             = $teamRechargeAmount;
        $data['teamWithdraw']             = $teamWithdrawAmount;
        $data['teamStock']                = isset($reportPlayerStatDay->team_stock) ? $reportPlayerStatDay->team_stock :0;

        $data['totalCommission']          = $data['teamDividend'] + $data['directlyunderDividend'] - bcdiv($directlyundervenueFee*$selfPlayerSetting->earnings,100,2);
        $data['earnings']                 = $selfPlayerSetting->earnings;
        $data['lastaccumulation']         = $lastaccumulation;
        $data['isAllowFastGrant']         = $isAllowFastGrant;
        $data['venue_fee']                = $directlyundervenueFee;
        
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
            $row['is_allow_fast_grant']           = $result['isAllowFastGrant'];

            $teamStock                            = 0;
            $teamStockChange                      = 0;
            $directlyunderStock                   = 0;
            $directlyunderStockChange             = 0;

            if(isset($result['team_stock'])){
                $teamStock = $result['team_stock'];
            }

            if(isset($result['teamStock'])){
                $teamStock = $result['teamStock'];
            }

            if(isset($result['team_stock_change'])){
                $teamStockChange = $result['team_stock_change'];
            }

            if(isset($result['teamStockChange'])){
                $teamStockChange = $result['teamStockChange'];
            }

            if(isset($result['directlyunder_stock'])){
                $directlyunderStock = $result['directlyunder_stock'];
            }

            if(isset($result['directlyunderStock'])){
                $directlyunderStock = $result['directlyunderStock'];
            }

            if(isset($result['directlyunder_stock_change'])){
                $directlyunderStockChange = $result['directlyunder_stock_change'];
            }

            if(isset($result['directlyunderStockChange'])){
                $directlyunderStockChange = $result['directlyunderStockChange'];
            }

            $row['team_stock']                    = $teamStock;
            $row['team_stock_change']             = $teamStockChange;
            $row['directlyunder_stock']           = $directlyunderStock;
            $row['directlyunder_stock_change']    = $directlyunderStockChange;

            $row['init_time']                     = time();
            $row['earnings']                      = $result['earnings'];
            $row['venue_fee']                     = $result['venue_fee'];
            $row['amount']                        = $result['totalCommission'] + $result['lastaccumulation'];
            $row['lastaccumulation']              = $result['lastaccumulation'];
            $row['from_day']                      = $startDay;
            $row['end_day']                       = $endDay;

            $data[]                               = $row;
            }
            if(count($data)){
                \DB::table('report_player_earnings')->insert($data);
                $data = [];
            }
            $level ++;
        }while($level <= $maxLevel);
    }

    public static function realtimePerformance($v,$day)
    {
        //查询自已的参数
        $selfPlayerSetting       = PlayerCache::getPlayerSetting($v->player_id);
        $row                     = [];
        $row['carrier_id']       = $v->carrier_id;
        $row['rid']              = $v->rid;
        $row['top_id']           = $v->top_id;
        $row['prefix']           = $v->prefix;
        $row['parent_id']        = $v->parent_id;
        $row['player_id']        = $v->player_id;
        $row['is_tester']        = $v->is_tester;
        $row['user_name']        = $v->user_name;
        $row['level']            = $v->level;

        //直属投注
        $directlyUnderCategroies = [];
        //直属下级保底为0
        $directlyUnderPlayerIds  = PlayerSetting::where('parent_id',$v->player_id)->pluck('player_id')->toArray();
        $directlyUnderBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->where('day',$day)->whereIn('player_id',$directlyUnderPlayerIds)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();
                        
        //查询所有直属的保底
        $directlyUnderPlayerSettingArr = [];
        $directlyUnderPlayerSetting    = PlayerSetting::where('parent_id',$v->player_id)->get();
        foreach ($directlyUnderPlayerSetting as $k1 => $v1) {
            $directlyUnderPlayerSettingArr[$v1->player_id] = $v1->guaranteed;
        }

        $row['directlyunder_casino_commission']     = 0;
        $row['directlyunder_electronic_commission'] = 0;
        $row['directlyunder_esport_commission']     = 0;
        $row['directlyunder_fish_commission']       = 0;
        $row['directlyunder_card_commission']       = 0;
        $row['directlyunder_sport_commission']      = 0;
        $row['directlyunder_lottery_commission']    = 0;
        $row['team_casino_commission']              = 0;
        $row['team_electronic_commission']          = 0;
        $row['team_esport_commission']              = 0;
        $row['team_fish_commission']                = 0;
        $row['team_card_commission']                = 0;
        $row['team_sport_commission']               = 0;
        $row['team_lottery_commission']             = 0;
        $row['self_casino_commission']              = 0;
        $row['self_electronic_commission']          = 0;
        $row['self_esport_commission']              = 0;
        $row['self_fish_commission']                = 0;
        $row['self_card_commission']                = 0;
        $row['self_sport_commission']               = 0;
        $row['self_lottery_commission']             = 0;

        $row['directlyunder_casino_performance']     = 0;
        $row['directlyunder_electronic_performance'] = 0;
        $row['directlyunder_esport_performance']     = 0;
        $row['directlyunder_fish_performance']       = 0;
        $row['directlyunder_card_performance']       = 0;
        $row['directlyunder_sport_performance']      = 0;
        $row['directlyunder_lottery_performance']    = 0;
        $row['team_casino_performance']              = 0;
        $row['team_electronic_performance']          = 0;
        $row['team_esport_performance']              = 0;
        $row['team_fish_performance']                = 0;
        $row['team_card_performance']                = 0;
        $row['team_sport_performance']               = 0;
        $row['team_lottery_performance']             = 0;
        $row['self_casino_performance']              = 0;
        $row['self_electronic_performance']          = 0;
        $row['self_esport_performance']              = 0;
        $row['self_fish_performance']                = 0;
        $row['self_card_performance']                = 0;
        $row['self_sport_performance']               = 0;
        $row['self_lottery_performance']             = 0;

        foreach ($directlyUnderBetFlows as $k1 => $v1) {
            switch ($v1->game_category) {
                case '1':
                    $row['directlyunder_casino_commission']     += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_casino_performance']    += $v1->available_bet_amount;
                    break;
                case '2':
                    $row['directlyunder_electronic_commission'] += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_electronic_performance']+= $v1->available_bet_amount;
                    break;
                case '3':
                    $row['directlyunder_esport_commission']     += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_esport_performance']    += $v1->available_bet_amount;
                    break;
                case '4':
                    $row['directlyunder_card_commission']       += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_card_performance']      += $v1->available_bet_amount;
                    break;
                case '5':
                    $row['directlyunder_sport_commission']      += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_sport_performance']     += $v1->available_bet_amount;
                    break;
                case '6':
                    $row['directlyunder_lottery_commission']    += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_lottery_performance']   += $v1->available_bet_amount;
                    break;
                case '7':
                    $row['directlyunder_fish_commission']       += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_fish_performance']      += $v1->available_bet_amount;
                    break;
                            
                default:
                    break;
            }
        }


        $teamCategroies          = [];
        $teamPlayerSettingArr    = [];
        //查询所有的直属下级
        $teamPlayerIds = PlayerSetting::where('parent_id','!=',$v->player_id)->where('rid','like',$v->rid.'|%')->pluck('player_id')->toArray();
        $teamPlayers   = PlayerSetting::where('parent_id','!=',$v->player_id)->where('rid','like',$v->rid.'|%')->get();

        foreach ($directlyUnderPlayerSetting as $k1 => $v1) {
            foreach ($teamPlayers as $k2 => $v2) {
                if(strpos($v2->rid,$v1->rid) !== false){
                    $teamPlayerSettingArr[$v2->player_id] = $v1->guaranteed;
                }
            }
        }

        //团队游戏分类投注金额                
        $teamBetFlows            = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id','rid')->where('carrier_id',$v->carrier_id)->where('day',$day)->whereIn('player_id',$teamPlayerIds)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();

        if(isset($teamBetFlows)){
            foreach ($teamBetFlows as $k1 => $v1) {
                switch ($v1->game_category) {
                    case '1':
                        $row['team_casino_commission']     += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                        $row['team_casino_performance']    += $v1->available_bet_amount;
                        break;
                    case '2':
                        $row['team_electronic_commission'] += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                        $row['team_electronic_performance']+= $v1->available_bet_amount;
                        break;
                    case '3':
                        $row['team_esport_commission']     += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                        $row['team_esport_performance']    += $v1->available_bet_amount;
                        break;
                    case '4':
                        $row['team_card_commission']       += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                        $row['team_card_performance']      += $v1->available_bet_amount;
                        break;
                    case '5':
                        $row['team_sport_commission']      += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                        $row['team_sport_performance']     += $v1->available_bet_amount;
                        break;
                    case '6':
                        $row['team_lottery_commission']    += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                        $row['team_lottery_performance']   += $v1->available_bet_amount;
                        break;
                    case '7':
                        $row['team_fish_commission']       += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                        $row['team_fish_performance']      += $v1->available_bet_amount;
                        break;
                                    
                    default:
                        break;
                }
            }  
        }
                    
        $row['init_time']  = time();
        $row['day']        = date('Ymd',strtotime('-1 day'));     
        $row['created_at'] = date('Y-m-d H:i:s');
        $row['updated_at'] = date('Y-m-d H:i:s');

        //计算对充佣金
        $row['amount']     = $row['team_casino_commission']+$row['team_electronic_commission']+$row['team_esport_commission']+$row['team_card_commission']+$row['team_sport_commission']+$row['team_lottery_commission']+$row['team_fish_commission']+$row['directlyunder_casino_commission']+$row['directlyunder_electronic_commission']+$row['directlyunder_esport_commission']+$row['directlyunder_card_commission']+$row['directlyunder_sport_commission']+$row['directlyunder_lottery_commission']+$row['directlyunder_fish_commission'] + $row['self_casino_commission'] + $row['self_electronic_commission'] + $row['self_esport_commission'] + $row['self_fish_commission'] + $row['self_card_commission'] + $row['self_sport_commission'] + $row['self_lottery_commission'];
        return $row;
    }

    public static function realtimePerformanceDesc($v,$day)
    {
        $row                                         = [];
        $row['directlyunder_casino_commission']      = 0;
        $row['directlyunder_electronic_commission']  = 0;
        $row['directlyunder_esport_commission']      = 0;
        $row['directlyunder_card_commission']        = 0;
        $row['directlyunder_sport_commission']       = 0;
        $row['directlyunder_lottery_commission']     = 0;
        $row['directlyunder_fish_commission']        = 0;
        $row['directlyunder_casino_performance']     = 0;
        $row['directlyunder_electronic_performance'] = 0;
        $row['directlyunder_esport_performance']     = 0;
        $row['directlyunder_fish_performance']       = 0;
        $row['directlyunder_card_performance']       = 0;
        $row['directlyunder_sport_performance']      = 0;
        $row['directlyunder_lottery_performance']    = 0;

        $row['team_casino_commission']               = 0;
        $row['team_electronic_commission']           = 0;
        $row['team_esport_commission']               = 0;
        $row['team_fish_commission']                 = 0;
        $row['team_card_commission']                 = 0;
        $row['team_sport_commission']                = 0;
        $row['team_lottery_commission']              = 0;
        $row['team_casino_performance']              = 0;
        $row['team_electronic_performance']          = 0;
        $row['team_esport_performance']              = 0;
        $row['team_fish_performance']                = 0;
        $row['team_card_performance']                = 0;
        $row['team_sport_performance']               = 0;
        $row['team_lottery_performance']             = 0;


        $row['self_casino_performance']              = 0;
        $row['self_electronic_performance']          = 0;
        $row['self_esport_performance']              = 0;
        $row['self_fish_performance']                = 0;
        $row['self_card_performance']                = 0;
        $row['self_sport_performance']               = 0;
        $row['self_lottery_performance']             = 0;
        $row['self_casino_commission']               = 0;
        $row['self_electronic_commission']           = 0;
        $row['self_esport_commission']               = 0;
        $row['self_fish_commission']                 = 0;
        $row['self_card_commission']                 = 0;
        $row['self_sport_commission']                = 0;
        $row['self_lottery_commission']              = 0;

        $selfPlayerSetting                 = PlayerCache::getPlayerSetting($v->player_id);
        $subordinateUnderPlayerIds         = PlayerSetting::where('parent_id',$v->player_id)->pluck('player_id')->toArray();

        //直属临时
        $directlyUnderBetFlows   = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->whereIn('player_id',$subordinateUnderPlayerIds)->where('day',$day)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();

        foreach ($directlyUnderBetFlows as $k1 => $v1) {
            switch ($v1->game_category) {
                case '1':
                    $row['directlyunder_casino_commission']     += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_casino_performance']    += $v1->available_bet_amount;
                    break;
                case '2':
                    $row['directlyunder_electronic_commission'] += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_electronic_performance']+= $v1->available_bet_amount;
                    break;
                case '3':
                    $row['directlyunder_esport_commission']     += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_esport_performance']    += $v1->available_bet_amount;
                    break;
                case '4':
                    $row['directlyunder_card_commission']       += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_card_performance']      += $v1->available_bet_amount;
                    break;
                case '5':
                    $row['directlyunder_sport_commission']      += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_sport_performance']     += $v1->available_bet_amount;
                    break;
                case '6':
                    $row['directlyunder_lottery_commission']    += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_lottery_performance']   += $v1->available_bet_amount;
                    break;
                case '7':
                    $row['directlyunder_fish_commission']       += $v1->available_bet_amount*$selfPlayerSetting->guaranteed;
                    $row['directlyunder_fish_performance']      += $v1->available_bet_amount;
                    break;
                                
                default:
                    break;
            }
        }


        //查询所有团队的保底
        $directlyUnderPlayerSetting   = PlayerSetting::where('parent_id',$v->player_id)->get();
        $teamPlayerIds                = PlayerSetting::where('parent_id','!=',$v->player_id)->where('rid','like',$v->rid.'|%')->pluck('player_id')->toArray();
        $teamPlayers                  = PlayerSetting::where('parent_id','!=',$v->player_id)->where('rid','like',$v->rid.'|%')->get();
        $teamPlayerSettingArr         = [];

        foreach ($directlyUnderPlayerSetting as $k1 => $v1) {
            foreach ($teamPlayers as $k2 => $v2) {
                if(strpos($v2->rid,$v1->rid) !== false){
                    $teamPlayerSettingArr[$v2->player_id] = $v1->guaranteed;
                }
            }
        }

        $teamBetFlows  = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as available_bet_amount'),'game_category','player_id','main_game_plat_id')->whereIn('player_id',$teamPlayerIds)->where('day',$day)->where('whether_recharge',1)->groupBy('player_id','main_game_plat_id','game_category')->get();


        foreach ($teamBetFlows as $k1 => $v1) {
            switch ($v1->game_category) {
                case '1':
                    $row['team_casino_commission']     += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                    $row['team_casino_performance']    += $v1->available_bet_amount;
                    break;
                case '2':
                    $row['team_electronic_commission'] += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                    $row['team_electronic_performance']+= $v1->available_bet_amount;
                    break;
                case '3':
                    $row['team_esport_commission']     += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                    $row['team_esport_performance']    += $v1->available_bet_amount;
                    break;
                case '4':
                    $row['team_card_commission']       += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                    $row['team_card_performance']      += $v1->available_bet_amount;
                    break;
                case '5':
                    $row['team_sport_commission']      += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                    $row['team_sport_performance']     += $v1->available_bet_amount;
                    break;
                case '6':
                    $row['team_lottery_commission']    += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                    $row['team_lottery_performance']   += $v1->available_bet_amount;
                    break;
                case '7':
                    $row['team_fish_commission']       += $v1->available_bet_amount*($selfPlayerSetting->guaranteed - $teamPlayerSettingArr[$v1->player_id]);
                    $row['team_fish_performance']      += $v1->available_bet_amount;
                    break;
                                
                default:
                    break;
            }
        }
      
        return $row;
    }

    //实时分红
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
                $reportRealPlayerEarnings->direct_commission                    = $result['directlyunderDividend']; //直属贡献分红
                $reportRealPlayerEarnings->team_commission                      = $result['teamDividend'];   //团队贡献分红
                $reportRealPlayerEarnings->directlyunder_recharge_amount        = $result['directlyunderRecharge'];
                $reportRealPlayerEarnings->directlyunder_withdraw_amount        = $result['directlyunderWithdraw'];
                $reportRealPlayerEarnings->team_recharge_amount                 = $result['teamRecharge'];
                $reportRealPlayerEarnings->team_withdraw_amount                 = $result['teamWithdraw'];
                $reportRealPlayerEarnings->team_stock                           = $result['teamStock'];
                $reportRealPlayerEarnings->team_stock_change                    = 0;
                $reportRealPlayerEarnings->directlyunder_stock                  = $result['directlyunderStock'];
                $reportRealPlayerEarnings->directlyunder_stock_change           = 0;
                $reportRealPlayerEarnings->earnings                             = $result['earnings'];
                $reportRealPlayerEarnings->venue_fee                            = $result['venue_fee'];
                $reportRealPlayerEarnings->lastaccumulation                     = $result['lastaccumulation'];
                $reportRealPlayerEarnings->amount                               = $result['totalCommission'] + $reportRealPlayerEarnings->lastaccumulation;
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
