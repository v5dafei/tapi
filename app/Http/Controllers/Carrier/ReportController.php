<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use App\Models\BaseModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Authenticatable;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Report\ReportGamePlatStatDay;
use App\Models\Report\ReportCarrierMonthStat;
use App\Models\Report\ReportRealPlayerEarnings;
use App\Models\Log\PlayerBetFlow;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Map\CarrierGamePlat;
use App\Models\PlayerTransfer;
use App\Models\PlayerAccount;
use App\Models\Carrier;
use App\Models\Player;
use App\Models\Report\ReportCardPlayerEarnings;
use App\Models\PlayerCommission;
use App\Models\Conf\PlayerSetting;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierPreFixDomain;
use App\Lib\Cache\Lock;
use App\Lib\DevidendMode1;
use App\Lib\DevidendMode2;
use App\Lib\DevidendMode3;
use App\Lib\DevidendMode5;
use App\Lib\DevidendMode4;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\PlayerRealDividendTongbao;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Lib\Clog;


class ReportController extends BaseController
{
    use Authenticatable;

    public function statdayList()
    {
        $input = request()->all();

        $defaultUserName = CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');
        $defaultPlayerId = PlayerCache::getPlayerId($this->carrier->id,$defaultUserName);

        if(isset($input['player_id']) && in_array($input['player_id'],$defaultPlayerId)){
            $query = ReportPlayerStatDay::where('is_tester',0)->where('win_lose_agent',0)->orderBy('id','desc');
        } elseif(isset($input['win_lose_agent']) && $input['win_lose_agent']==1){
            $query = ReportPlayerStatDay::where('is_tester',0)->where('win_lose_agent',1)->orderBy('id','desc');
        } else{
            $query = ReportPlayerStatDay::where('is_tester',0)->where('win_lose_agent',0)->orderBy('id','desc');
        }
        
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['user_name']) && trim($input['user_name']) != '') {
            $query->where('user_name',$input['user_name']);
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['parent_id']) && trim($input['parent_id']) != '') {
            $query->where('parent_id',$input['parent_id']);
        }

        if(isset($input['startDate']) && trim($input['startDate']) != '') {
            if(!strtotime($input['startDate'])) {
                return returnApiJson('对不起，开始日期格式不正确', 0);
            }
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        } else {
            $query->where('day','>=',date('Ymd'));
        }

        if(isset($input['endDate']) && trim($input['endDate']) != '') {
            if(!strtotime($input['endDate'])) {
                return returnApiJson('对不起，结束日期格式不正确', 0);
            }
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
        } else {
            $query->where('day','<=',date('Ymd'));
        }

        $total  = $query->count();
        $data   = $query->skip($offset)->take($pageSize)->get();

        return returnApiJson('操作成功', 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }


    public function agentStatdayList()
    {
        $input                        = request()->all();
        $currentPage                  = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize                     = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset                       = ($currentPage - 1) * $pageSize;

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return returnApiJson('对不起，站点取值不正确', 0);
        }

        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_dividends_method',$input['prefix']);

        $query                        = Player::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('win_lose_agent',1)->where('is_tester',0);
        if(isset($input['user_name']) && trim($input['user_name']) != '') {
            $query->where('user_name',$input['user_name']);
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('player_id',$input['player_id']);
        }

        $total    = $query->count();
        $datas    = $query->skip($offset)->take($pageSize)->get();
        $data     = [];

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->first();

        foreach ($datas as $key => $value) {
            $row = [];

            switch ($playerDividendsMethod) {
                case 1:
                $row = DevidendMode1::calculateDividend($value,null,null,1);
                     if(is_array($row)){
                        $row['player_id']                     = $value->player_id;
                        $row['user_name']                     = $value->user_name;
                        $row['team_recharge_amount']          = $row['teamRecharge'];
                        $row['team_withdraw_amount']          = $row['teamWithdraw'];
                        $row['directlyunder_recharge_amount'] = $row['directlyunderRecharge'];
                        $row['directlyunder_withdraw_amount'] = $row['directlyunderWithdraw'];
                        $row['amount']                        = $row['totalCommission'];
                     }
                    break;
                case 3:
                     $row = DevidendMode3::calculateDividend($value);
                     if(is_array($row)){
                        $row['player_id']                     = $value->player_id;
                        $row['user_name']                     = $value->user_name;
                        $row['team_recharge_amount']          = $row['teamRecharge'];
                        $row['team_withdraw_amount']          = $row['teamWithdraw'];
                        $row['directlyunder_recharge_amount'] = $row['directlyunderRecharge'];
                        $row['directlyunder_withdraw_amount'] = $row['directlyunderWithdraw'];
                        $row['amount']                        = $row['totalCommission'];
                     }
                    break;
                case 5:
                     $row = DevidendMode5::calculateDividend($value);
                     if(is_array($row)){
                        $row['player_id']                     = $value->player_id;
                        $row['user_name']                     = $value->user_name;
                        $row['team_recharge_amount']          = $row['teamRecharge'];
                        $row['team_withdraw_amount']          = $row['teamWithdraw'];
                        $row['directlyunder_recharge_amount'] = $row['directlyunderRecharge'];
                        $row['directlyunder_withdraw_amount'] = $row['directlyunderWithdraw'];
                        $row['amount']                        = $row['totalCommission'];
                     }
                    break;
                case 2:
                    $row = DevidendMode2::calculateDividend($value);
                    if(is_array($row)){
                        $row['player_id']                     = $value->player_id;
                        $row['user_name']                     = $value->user_name;
                        $row['team_recharge_amount']          = $row['teamRecharge'];
                        $row['team_withdraw_amount']          = $row['teamWithdraw'];
                        $row['directlyunder_recharge_amount'] = $row['directlyunderRecharge'];
                        $row['directlyunder_withdraw_amount'] = $row['directlyunderWithdraw'];
                        $row['amount']                        = $row['totalCommission'];
                     }
                    break;
                case 4:
                     $row = DevidendMode4::calculateDividend($value);
                     if(is_array($row)){
                        $row['player_id']                     = $value->player_id;
                        $row['user_name']                     = $value->user_name;
                        $row['team_recharge_amount']          = $row['teamRecharge'];
                        $row['team_withdraw_amount']          = $row['teamWithdraw'];
                        $row['directlyunder_recharge_amount'] = $row['directlyunderRecharge'];
                        $row['directlyunder_withdraw_amount'] = $row['directlyunderWithdraw'];
                        $row['amount']                        = $row['totalCommission'];
                     }
                    break;
                
                default:
                    // code...
                    break;
            }
            
            $row['multiple_name'] = $carrierPreFixDomain->name;
            $data[]               = $row;
        }
  
        return returnApiJson('操作成功', 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function totalStatdayList()
    {
        $input = request()->all();

        $query = ReportPlayerStatDay::select(
            \DB::raw('sum(recharge_amount) as recharge_amount'),
            \DB::raw('sum(recharge_count) as recharge_count'),
            \DB::raw('sum(withdraw_amount) as withdraw_amount'),
            \DB::raw('sum(casino_available_bets) as casino_available_bets'),
            \DB::raw('sum(casino_winorloss) as casino_winorloss'),
            \DB::raw('sum(electronic_available_bets) as electronic_available_bets'),
            \DB::raw('sum(electronic_winorloss) as electronic_winorloss'),
            \DB::raw('sum(esport_available_bets) as esport_available_bets'),
            \DB::raw('sum(esport_winorloss) as esport_winorloss'),
            \DB::raw('sum(fish_available_bets) as fish_available_bets'),
            \DB::raw('sum(fish_winorloss) as fish_winorloss'),
            \DB::raw('sum(card_available_bets) as card_available_bets'),
            \DB::raw('sum(card_winorloss) as card_winorloss'),
            \DB::raw('sum(sport_available_bets) as sport_available_bets'),
            \DB::raw('sum(sport_winorloss) as sport_winorloss'),
            \DB::raw('sum(lottery_available_bets) as lottery_available_bets'),
            \DB::raw('sum(lottery_winorloss) as lottery_winorloss'),
            \DB::raw('sum(available_bets) as available_bets'),
            \DB::raw('sum(win_amount) as win_amount'),
            \DB::raw('sum(dividend) as dividend'),
            \DB::raw('sum(gift) as gift'),
            \DB::raw('sum(casino_commission) as casino_commission'),
            \DB::raw('sum(electronic_commission) as electronic_commission'),
            \DB::raw('sum(esport_commission) as esport_commission'),
            \DB::raw('sum(fish_commission) as fish_commission'),
            \DB::raw('sum(card_commission) as card_commission'),
            \DB::raw('sum(sport_commission) as sport_commission'),
            \DB::raw('sum(lottery_commission) as lottery_commission'),
            \DB::raw('sum(commission) as commission'),
            \DB::raw('sum(team_casino_commission) as team_casino_commission'),
            \DB::raw('sum(team_electronic_commission) as team_electronic_commission'),
            \DB::raw('sum(team_esport_commission) as team_esport_commission'),
            \DB::raw('sum(team_fish_commission) as team_fish_commission'),
            \DB::raw('sum(team_card_commission) as team_card_commission'),
            \DB::raw('sum(team_sport_commission) as team_sport_commission'),
            \DB::raw('sum(team_lottery_commission) as team_lottery_commission'),
            \DB::raw('sum(team_commission) as team_commission'),
            \DB::raw('sum(team_first_register) as team_first_register'),
            \DB::raw('sum(team_have_bet) as team_have_bet'),
            \DB::raw('sum(team_recharge_amount) as team_recharge_amount'),
            \DB::raw('sum(team_recharge_count) as team_recharge_count'),
            \DB::raw('sum(team_first_recharge_count) as team_first_recharge_count'),
            \DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),
            \DB::raw('sum(team_casino_available_bets) as team_casino_available_bets'),
            \DB::raw('sum(team_casino_winorloss) as team_casino_winorloss'),
            \DB::raw('sum(team_electronic_available_bets) as team_electronic_available_bets'),
            \DB::raw('sum(team_electronic_winorloss) as team_electronic_winorloss'),
            \DB::raw('sum(team_esport_available_bets) as team_esport_available_bets'),
            \DB::raw('sum(team_esport_winorloss) as team_esport_winorloss'),
            \DB::raw('sum(team_fish_available_bets) as team_fish_available_bets'),
            \DB::raw('sum(team_fish_winorloss) as team_fish_winorloss'),
            \DB::raw('sum(team_card_available_bets) as team_card_available_bets'),
            \DB::raw('sum(team_card_winorloss) as team_card_winorloss'),
            \DB::raw('sum(team_sport_available_bets) as team_sport_available_bets'),
            \DB::raw('sum(team_sport_winorloss) as team_sport_winorloss'),
            \DB::raw('sum(team_available_bets) as team_available_bets'),
            \DB::raw('sum(team_win_amount) as team_win_amount'),
            \DB::raw('sum(team_lottery_available_bets) as team_lottery_available_bets'),
            \DB::raw('sum(team_lottery_winorloss) as team_lottery_winorloss'),
            \DB::raw('sum(team_dividend) as team_dividend'),
            \DB::raw('sum(team_gift) as team_gift'),'player_id','parent_id','top_id','level','user_name')
            ->where('carrier_id',$this->carrier->id)
            ->where('is_tester',0)
            ->groupBy('player_id')->orderBy('id','desc');


        $defaultUserName = CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');
        $defaultPlayerId = PlayerCache::getPlayerId($this->carrier->id,$defaultUserName);

        if(isset($input['player_id']) && in_array($input['player_id'],$defaultPlayerId)){
            $query->where('win_lose_agent',0);
        } elseif(isset($input['win_lose_agent']) && $input['win_lose_agent']==1){
            $query->where('win_lose_agent',1);
        } else{
            $query->where('win_lose_agent',0);
        }

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['user_name']) && trim($input['user_name']) != ''){
            $query->where('user_name',$input['user_name']);
        }

        if(isset($input['player_id']) && trim($input['player_id']) != ''){
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['parent_id']) && trim($input['parent_id']) != ''){
            $query->where('parent_id',$input['parent_id']);
        }

        if(isset($input['startDate']) && trim($input['startDate']) != ''){
            if(!strtotime($input['startDate'])) {
                return returnApiJson('对不起，开始日期格式不正确', 0);
            }
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && trim($input['endDate']) != ''){
            if(!strtotime($input['endDate'])) {
                return returnApiJson('对不起，结束日期格式不正确', 0);
            }
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
        }

        $total  = $query->get()->count();
        $data   = $query->skip($offset)->take($pageSize)->get();

        return $this->returnApiJson('操作成功', 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function agenttotalstatdaylist()
    {
        $input = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(!isset($input['prefix']) && empty($input['prefix'])){
            return returnApiJson('对不起，站点取值不正确', 0);
        }

        $query= ReportPlayerEarnings::select(
            \DB::raw('sum(activepersonacount) as activepersonacount'),
            \DB::raw('sum(availableadd) as availableadd'),
            \DB::raw('sum(venue_fee) as venue_fee'),
            \DB::raw('sum(team_recharge_amount) as team_recharge_amount'),
            \DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),
            \DB::raw('sum(directlyunder_recharge_amount) as directlyunder_recharge_amount'),
            \DB::raw('sum(directlyunder_withdraw_amount) as directlyunder_withdraw_amount'),
            \DB::raw('sum(directlyunder_stock_change) as directlyunder_stock_change'),
            \DB::raw('sum(team_stock_change) as team_stock_change'),
            'player_id','user_name')
            ->where('prefix',$input['prefix'])
            ->groupBy('player_id')
            ->orderBy('id','desc');

        $query1= ReportPlayerEarnings::select(
            \DB::raw('sum(real_amount) as real_amount'),
            'player_id','user_name')
            ->where('prefix',$input['prefix'])
            ->where('status',1)
            ->groupBy('player_id')
            ->orderBy('id','desc');

        if(isset($input['user_name']) && trim($input['user_name']) != ''){
            $query->where('user_name',$input['user_name']);
            $query1->where('user_name',$input['user_name']);
        }

        if(isset($input['player_id']) && trim($input['player_id']) != ''){
            $query->where('player_id',$input['player_id']);
            $query1->where('player_id',$input['player_id']);
        }

        if(isset($input['startDate']) && trim($input['startDate']) != ''){
            if(!strtotime($input['startDate'])) {
                return returnApiJson('对不起，开始日期格式不正确', 0);
            }
            $query->where('send_day','>=',date('Ymd',strtotime($input['startDate'])));
            $query1->where('send_day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && trim($input['endDate']) != ''){
            if(!strtotime($input['endDate'])) {
                return returnApiJson('对不起，结束日期格式不正确', 0);
            }
            $query->where('send_day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
            $query1->where('send_day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
        }

        $total  = $query->get()->count();
        $data   = $query->skip($offset)->take($pageSize)->get();
        $data1  = $query1->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        $realAmountArr = [];
        foreach ($data1 as $key => $value) {
            $realAmountArr[$value->player_id] = $value->real_amount;
        }

        foreach ($data as $k => $v) {
            $v->real_amount   = isset($realAmountArr[$v->player_id]) ? $realAmountArr[$v->player_id] : 0;
            $v->multiple_name = $carrierPreFixDomainArr[$input['prefix']];
        }


        return $this->returnApiJson('操作成功', 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function gameplatList()
    {
        $input  = request()->all();
        $query  = ReportGamePlatStatDay::select('def_main_game_plats.alias',\DB::raw('sum(report_gameplat_stat_day.personcount) as personcount'),\DB::raw('sum(report_gameplat_stat_day.account) as account'),\DB::raw('sum(report_gameplat_stat_day.available_bet_amount) as available_bet_amount'),\DB::raw('sum(report_gameplat_stat_day.company_win_amount) as company_win_amount'))
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','report_gameplat_stat_day.main_game_plat_id')
            ->where('carrier_id',$this->carrier->id)
            ->where('report_gameplat_stat_day.main_game_plat_id','<>',0)
            ->orderBy('report_gameplat_stat_day.id','desc')
            ->groupBy('report_gameplat_stat_day.main_game_plat_id');

        if(isset($input['startDate']) && trim($input['startDate']) != '') {
            if(!strtotime($input['startDate'])) {
                return returnApiJson('对不起，开始日期格式不正确', 0);
            }
            $query->where('report_gameplat_stat_day.day','>=',date('Ymd',strtotime($input['startDate'])));
        } else {
            $query->where('report_gameplat_stat_day.day','>=',date('Ymd'));
        }

        if(isset($input['endDate']) && trim($input['endDate']) != ''){
            if(!strtotime($input['endDate'])) {
                return returnApiJson('对不起，结束日期格式不正确', 0);
            }
            $query->where('report_gameplat_stat_day.day','<=',date('Ymd',strtotime($input['endDate'])));
        } else {
            $query->where('report_gameplat_stat_day.day','<=',date('Ymd'));
        }

        $datas   = $query->get();

        foreach ($datas as $key => &$value) {
            if($value->personcount){
                $value->perperson  = bcdiv($value->available_bet_amount,$value->personcount,4);
            } else {
                $value->perperson  = 0.0000;
            }

            if($value->personcount){
                $value->peraccount = bcdiv($value->account,$value->personcount,2);
            } else {
                $value->peraccount = 0.00;
            }
        }
        return returnApiJson('操作成功', 1,['items' => $datas]);
    }

    public function winAndLoseList()
    {
       $input        = request()->all();
       $prefixArr    = CarrierMultipleFront::where('sign','agent_single_background')->where('value',1)->pluck('prefix')->toArray();


       if(isset($input['prefix']) && trim($input['prefix']) != ''){
            //素材号ID
            $materialIds          = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'materialIds',$input['prefix']);
            $materialIdsArr       = explode(',',$materialIds);

            $playerIds = Player::where('carrier_id',$this->carrier->id)->where('level',2)->where('is_tester',0)->where('prefix',$input['prefix'])->whereNotIn('player_id',$materialIdsArr)->pluck('player_id')->toArray();
        } else{
            $playerIds = Player::where('carrier_id',$this->carrier->id)->where('level',2)->where('is_tester',0)->pluck('player_id')->toArray();
        }

       $query     = ReportPlayerStatDay::select('day',
            \DB::raw('sum(team_first_register) as team_first_register'),
            \DB::raw('sum(team_have_bet) as team_have_bet'),
            \DB::raw('sum(team_recharge_amount) as team_recharge_amount'),
            \DB::raw('sum(team_recharge_count) as team_recharge_count'),
            \DB::raw('sum(team_first_recharge_count) as team_first_recharge_count'),
            \DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),
            \DB::raw('sum(team_available_bets) as team_available_bets'),
            \DB::raw('sum(team_win_amount) as team_win_amount'),
            \DB::raw('sum(team_lottery_commission) as team_lottery_commission'),
            \DB::raw('sum(team_commission) as team_commission'),
            \DB::raw('sum(team_lottery_available_bets) as team_lottery_available_bets'),
            \DB::raw('sum(team_lottery_winorloss) as team_lottery_winorloss'),
            \DB::raw('sum(team_dividend) as team_dividend'),
            \DB::raw('sum(team_gift) as team_gift'))
            ->where('carrier_id',$this->carrier->id)
            ->whereIn('player_id',$playerIds)
            ->groupBy('day')->orderBy('day','desc');

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = 31;
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['startDate']) && trim($input['startDate']) != '') {
            if(!strtotime($input['startDate'])) {
                return returnApiJson('对不起，开始日期格式不正确', 0);
            }
          $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        } else {
          $query->where('day','>=',date('Ym').'01');
        }

        if(isset($input['endDate']) && trim($input['endDate']) != '') {
            if(!strtotime($input['endDate'])) {
                return returnApiJson('对不起，结束日期格式不正确', 0);
            }
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
        } else {
            $query->where('day','<=',date('Ymd'));
        }

        $prefix = '';
        if(isset($input['prefix']) && trim($input['prefix']) != ''){
            $query->where('prefix',$input['prefix']);
            $prefix = $input['prefix'];
        }

        $total  = $query->get()->count();
        $data   = $query->skip($offset)->take($pageSize)->get();

        //保底
        $playerCommissionsQuery = PlayerCommission::select('day',\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id);

        if(isset($input['prefix']) && trim($input['prefix']) != ''){
            $playerCommissions = $playerCommissionsQuery->where('prefix',$input['prefix'])->groupBy('day')->get();
        } else{
            $playerCommissions = $playerCommissionsQuery->groupBy('day')->orderBy('day','desc')->get();
        }

        $playerCommissionArrs = [];
        foreach ($playerCommissions as $k => $v) {
            $playerCommissionArrs[$v->day]      =  $v->amount;
        }

        foreach ($data as $key => &$value) {
            if(isset($input['prefix']) && trim($input['prefix']) != ''){
                $rechangePersons                = PlayerDepositPayLog::where('carrier_id',$this->carrier->id)->where('status',1)->where('day',$value->day)->where('prefix',$input['prefix'])->pluck('player_id')->toArray();
            } else{
                $rechangePersons                = PlayerDepositPayLog::where('carrier_id',$this->carrier->id)->where('status',1)->where('day',$value->day)->pluck('player_id')->toArray();
            }
            
            $value->rechangePersons         = count(array_unique($rechangePersons));
            $value->commission              = isset($playerCommissionArrs[$value->day]) ? $playerCommissionArrs[$value->day] :0;

            if(isset($input['prefix']) && trim($input['prefix']) != ''){
                $agentPlayerWithdrawAmount      = PlayerWithdraw::where('is_agent',1)->whereIn('prefix',$prefixArr)->where('prefix',$input['prefix'])->whereIn('status',[1,2])->where('review_two_time','>=',strtotime($value->day))->where('review_two_time','<',strtotime($value->day)+86400)->sum('amount');
            } else{
                $agentPlayerWithdrawAmount      = PlayerWithdraw::where('is_agent',1)->whereIn('prefix',$prefixArr)->whereIn('status',[1,2])->where('review_two_time','>=',strtotime($value->day))->where('review_two_time','<',strtotime($value->day)+86400)->sum('amount');
            }
            
            $value->team_withdraw_amount    += $agentPlayerWithdrawAmount;
        }

        return returnApiJson('操作成功', 1,['prefix'=>$prefix,'data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function earnlingList()
    {
        $input    = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query     = ReportPlayerEarnings::select('report_player_earnings.*')->where('report_player_earnings.carrier_id',$this->carrier->id)->orderBy('report_player_earnings.id','desc');

        $query1    =  ReportPlayerEarnings::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('amount','>=',0);         //应发
        $query2    =  ReportPlayerEarnings::select(\DB::raw('sum(real_amount) as real_amount'))->where('carrier_id',$this->carrier->id)->where('status',1);    //实发
        $query3    =  ReportPlayerEarnings::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('status',2);              //取消
        $query4    =  ReportPlayerEarnings::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('status',3);              //累积

        if(isset($input['type']) && in_array($input['type'],[-1,0,1])){
            if($input['type']==-1){
                $query->where('report_player_earnings.amount','<',0);
                $query1->where('amount','<',0);
                $query2->where('amount','<',0);
                $query3->where('amount','<',0);
                $query4->where('amount','<',0);
            } elseif($input['type']==1){
                $query->where('report_player_earnings.amount','>',0);
                $query1->where('amount','>',0);
                $query2->where('amount','>',0);
                $query3->where('amount','>',0);
                $query4->where('amount','>',0);
            } else{
                $query->where('report_player_earnings.amount',0);
                $query1->where('amount',0);
                $query2->where('amount',0);
                $query3->where('amount',0);
                $query4->where('amount',0);
            }
        }
       
        if(isset($input['startDate']) && !empty($input['startDate'])) {
            if(!strtotime($input['startDate'])) {
                return returnApiJson('对不起，发放开始日期格式不正确', 0);
            }
            $query->where('report_player_earnings.send_day','>=',date('Ymd',strtotime($input['startDate'].' 00:00:00')));
            $query1->where('send_day','>=',date('Ymd',strtotime($input['startDate'].' 00:00:00')));
            $query2->where('send_day','>=',date('Ymd',strtotime($input['startDate'].' 00:00:00')));
            $query3->where('send_day','>=',date('Ymd',strtotime($input['startDate'].' 00:00:00')));
            $query4->where('send_day','>=',date('Ymd',strtotime($input['startDate'].' 00:00:00')));
        }

        if(isset($input['endDate']) && !empty($input['endDate'])) {
            if(!strtotime($input['endDate'])) {
                return returnApiJson('对不起，发放结束日期格式不正确', 0);
            }
            $query->where('report_player_earnings.send_day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
            $query1->where('send_day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
            $query2->where('send_day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
            $query3->where('send_day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
            $query4->where('send_day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
        }

        if(isset($input['startTime']) && !empty($input['startTime']) && strtotime($input['startTime'])){
            $query->where('report_player_earnings.created_at','>=',$input['startTime']);
            $query1->where('created_at','>=',$input['startTime']);
            $query2->where('created_at','>=',$input['startTime']);
            $query3->where('created_at','>=',$input['startTime']);
            $query4->where('created_at','>=',$input['startTime']);
        }

        if(isset($input['endTime']) && !empty($input['endTime']) && strtotime($input['endTime'])){
            $query->where('report_player_earnings.created_at','<=',$input['endTime'].' 23:59:59');
            $query1->where('created_at','<=',$input['endTime'].' 23:59:59');
            $query2->where('created_at','<=',$input['endTime'].' 23:59:59');
            $query3->where('created_at','<=',$input['endTime'].' 23:59:59');
            $query4->where('created_at','<=',$input['endTime'].' 23:59:59');
        }

        if(isset($input['user_name']) && !empty($input['user_name'])) {
            $query->where('report_player_earnings.user_name','like','%'.$input['user_name'].'%');
            $query1->where('user_name','like','%'.$input['user_name'].'%');
            $query2->where('user_name','like','%'.$input['user_name'].'%');
            $query3->where('user_name','like','%'.$input['user_name'].'%');
            $query4->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['prefix']) && !empty($input['prefix'])) {
            $query->where('report_player_earnings.prefix',$input['prefix']);
            $query1->where('prefix',$input['prefix']);
            $query2->where('prefix',$input['prefix']);
            $query3->where('prefix',$input['prefix']);
            $query4->where('prefix',$input['prefix']);
        }

        if(isset($input['status']) && in_array($input['status'],[0,1,2,3])) {
            $query->where('report_player_earnings.status',$input['status']);
            $query1->where('status',$input['status']);
            $query2->where('status',$input['status']);
            $query3->where('status',$input['status']);
            $query4->where('status',$input['status']);
        }

        if(isset($input['extend_id']) && !empty($input['extend_id'])) {
            $playerIds = Player::where('extend_id',$input['extend_id'])->pluck('player_id')->toArray();
            $query->whereIn('report_player_earnings.player_id',$playerIds);
            $query1->whereIn('player_id',$playerIds);
            $query2->whereIn('player_id',$playerIds);
            $query3->whereIn('player_id',$playerIds);
            $query4->whereIn('player_id',$playerIds);
        }

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('report_player_earnings.player_id',$input['player_id']);
            $query1->where('player_id',$input['player_id']);
            $query2->where('player_id',$input['player_id']);
            $query3->where('player_id',$input['player_id']);
            $query4->where('player_id',$input['player_id']);
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();
        $earnlingtotal  = $query1->first();
        $earnlingtota2  = $query2->first();
        $earnlingtota3  = $query3->first();
        $earnlingtota4  = $query4->first();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($items as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        $earnlingtotal  = [
            'amount'              => is_null($earnlingtotal->amount) ?  0:$earnlingtotal->amount,               //应发金额
            'real_amount'         => is_null($earnlingtota2->real_amount) ?  0:$earnlingtota2->real_amount,      //实发金额
            'cancel_amount'       => is_null($earnlingtota3->amount) ?  0:$earnlingtota3->amount,      //取消金额
            'accumulation_amount' => is_null($earnlingtota4->amount) ?  0:$earnlingtota4->amount,      //累积金额
        ];

        return returnApiJson('操作成功', 1, ['earnlingtotal'=>$earnlingtotal,'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function realEarnlingList()
    {
        $input    = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query     = ReportRealPlayerEarnings::where('carrier_id',$this->carrier->id)->orderBy('id','desc');
        $query1    = ReportRealPlayerEarnings::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('amount','>=',0);         //应发

        if(isset($input['type']) && in_array($input['type'],[-1,0,1])){
            if($input['type']==-1){
                $query->where('amount','<',0);
                $query1->where('amount','<',0);
            } elseif($input['type']==1){
                $query->where('amount','>',0);
                $query1->where('amount','>',0);
            } else{
                $query->where('amount',0);
                $query1->where('amount',0);
            }
        }

        if(isset($input['recharge_people_number']) && is_numeric($input['recharge_people_number']) && intval($input['recharge_people_number']) == $input['recharge_people_number']) {
            $query->where('directlyunder_recharge_people_number','>=',$input['recharge_people_number']);
            $query1->where('directlyunder_recharge_people_number','>=',$input['recharge_people_number']);
        }

        if(isset($input['register_people_number']) && is_numeric($input['register_people_number']) && intval($input['register_people_number']) == $input['register_people_number']) {
            $query->where('register_people_number','>=',$input['register_people_number']);
            $query1->where('register_people_number','>=',$input['register_people_number']);
        }

        if(isset($input['directlyunder_people_number']) && is_numeric($input['directlyunder_people_number']) && intval($input['directlyunder_people_number']) == $input['directlyunder_people_number']) {
            $query->where('directlyunder_people_number','>=',$input['directlyunder_people_number']);
            $query1->where('directlyunder_people_number','>=',$input['directlyunder_people_number']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])) {
            $query->where('user_name','like','%'.$input['user_name'].'%');
            $query1->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['user_name']) && !empty($input['user_name'])) {
            $query->where('user_name','like','%'.$input['user_name'].'%');
            $query1->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['prefix']) && !empty($input['prefix'])) {
            $query->where('prefix',$input['prefix']);
            $query1->where('prefix',$input['prefix']);
        }

        if(isset($input['status']) && in_array($input['status'],[0,1,2,3])) {
            $query->where('status',$input['status']);
            $query1->where('status',$input['status']);
        }

        if(isset($input['extend_id']) && !empty($input['extend_id'])) {
            $playerIds = Player::where('extend_id',$input['extend_id'])->pluck('player_id')->toArray();
            $query->whereIn('player_id',$playerIds);
            $query1->whereIn('player_id',$playerIds);
        }

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('player_id',$input['player_id']);
            $query1->where('player_id',$input['player_id']);
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();
        $earnlingtotal  = $query1->first();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($items as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
            $v->recharge_people_number = $v->directlyunder_recharge_people_number;
        }

        $earnlingtotal  = [
            'amount'              => is_null($earnlingtotal->amount) ?  0:$earnlingtotal->amount,               //应发金额
        ];

        $startTime = date('Y-m-d');
        $endTime   = date('Y-m-d').' 02:00:00';

        if(time()>=strtotime($startTime) && time()<=strtotime($endTime)){
            return returnApiJson('操作成功', 1, ['earnlingtotal'=>['amount'=>0],'data' => [], 'total' => 0, 'currentPage' => 1, 'totalPage' => 1]);
        } else{
            return returnApiJson('操作成功', 1, ['earnlingtotal'=>$earnlingtotal,'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
        }

        
    }

    public function realearnlingDesc($id)
    {
        $input    = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerRealDividendTongbao::select(\DB::raw('sum(amount) as amount'),'scale','player_id')->where('carrier_id',$this->carrier->id)->where('receive_player_id',$id)->groupBy('player_id')->orderBy('id','desc');
        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->scale  = bcdiv($value->scale*100,1,2);
        }

        return returnApiJson('操作成功', 1, ['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);        
    }

    public function cardEarnlingList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query     = ReportCardPlayerEarnings::select('report_player_earnings.*','inf_carrier_player_level.groupname')->leftJoin('inf_carrier_player_level','inf_carrier_player_level.id','=','report_player_earnings.player_group_id')->orderBy('report_player_earnings.id','desc');

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('report_player_earnings.player_id',$input['player_id']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('report_player_earnings.user_name',$input['user_name']);
        }

        if(isset($input['parent_id']) && !empty($input['parent_id'])){
            $query->where('report_player_earnings.parent_id',$input['parent_id']);
        }

        if(isset($input['status']) && in_array($input['status'],[0,1])){
            $query->where('report_player_earnings.status',$input['status']);
        }

        if(isset($input['startTime']) && strtotime($input['startTime'])){
            $query->where('report_player_earnings.created_at','>=',$input['startTime'].' 00:00:00');
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])){
            $query->where('report_player_earnings.created_at','<=',$input['endTime'].' 23:59:59');
        }

        //发放日期
        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('report_player_earnings.send_day','>=',$input['startDate']);

        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('report_player_earnings.send_day','<=',$input['endDate']);
        }


        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        return returnApiJson('操作成功', 1, ['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);

    }

    public function carrierMonthStatList()
    {
        $input    = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query          =  ReportCarrierMonthStat::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->orderBy('day_m','desc');
        } else{
            $query          =  ReportCarrierMonthStat::where('carrier_id',$this->carrier->id)->orderBy('day_m','desc');
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        return returnApiJson('操作成功', 1, ['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
        
    }

    public function cancelSendEarnling()
    {
        $input    = request()->all();

        if(!isset($input['id']) || empty($input['id'])) {
            return returnApiJson('对不起，ID为空或不存在', 0);
        }

        $reportPlayerEarning = ReportPlayerEarnings::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
        if(!$reportPlayerEarning) {
            return returnApiJson('对不起，此条数据不存在', 0);
        }

        $reportPlayerEarning->status      = 2;
        $reportPlayerEarning->send_day    = date('Ymd');
        $reportPlayerEarning->admin_id    = $this->carrierUser->id;
        $reportPlayerEarning->save();

        return returnApiJson('操作成功', 1);
    }

    public function accumulationNext()
    {
        $input    = request()->all();

        if(!isset($input['id']) || empty($input['id'])) {
            return returnApiJson('对不起，ID为空或不存在', 0);
        }

        $reportPlayerEarning = ReportPlayerEarnings::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
        if(!$reportPlayerEarning) {
            return returnApiJson('对不起，此条数据不存在', 0);
        }

        if(!isset($input['real_amount']) || !is_numeric($input['real_amount']) ) {
            return returnApiJson('对不起，累积金额不存在或格式不正确', 0);
        }

        if($reportPlayerEarning->earnings!=0){
            $reportPlayerEarning->accumulation           = $input['real_amount'];
        } else{
            $reportPlayerEarning->positive_accumulation  = $reportPlayerEarning->netprofitloss;
        }
        
        $reportPlayerEarning->send_day      = date('Ymd');
        $reportPlayerEarning->status        = 3;
        $reportPlayerEarning->admin_id      = $this->carrierUser->id;
        $reportPlayerEarning->save();

        return returnApiJson('操作成功', 1);
    }

    public function sendEarnling()
    {
        $input    = request()->all();

        if(!isset($input['id']) || empty($input['id'])) {
            return returnApiJson('对不起，ID为空或不存在', 0);
        }

        $reportPlayerEarning = ReportPlayerEarnings::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
        if(!$reportPlayerEarning) {
            return returnApiJson('对不起，此条数据不存在', 0);
        }

        if(!isset($input['real_amount']) || !is_numeric($input['real_amount']) || $input['real_amount']< 0 ) {
            return returnApiJson('对不起，实付金额不存在或格式不正确', 0);
        }

        if($input['real_amount']*10000>$reportPlayerEarning->amount ) {
            return returnApiJson('对不起，实付金额不能大于应付金额', 0);
        }

        $dividendsReceiveMethod      = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'dividends_receive_method',$reportPlayerEarning->prefix);

        $cacheKey = "player_" .$reportPlayerEarning->player_id;
        $redisLock = Lock::addLock($cacheKey,10);

        if (!$redisLock) {
                return returnApiJson('对不起，系统异常', 0);
        } else {
            try {
                \DB::beginTransaction();
                $reportPlayerEarning->real_amount         = $input['real_amount']*10000;
                $reportPlayerEarning->send_day            = date('Ymd');
                $reportPlayerEarning->send_time           = time();
                if($dividendsReceiveMethod==1){
                    $reportPlayerEarning->status              = 1;
                } else{
                    $reportPlayerEarning->status              = 4;
                }
                
                $reportPlayerEarning->is_allow_fast_grant = 1;
                $reportPlayerEarning->admin_id            = $this->carrierUser->id;
                $reportPlayerEarning->save();

                if($dividendsReceiveMethod==1){
                    $playerAccount                                   = PlayerAccount::where('player_id',$reportPlayerEarning->player_id)->lockForUpdate()->first();
                    $player                                          = PlayerAccount::where('player_id',$reportPlayerEarning->player_id)->first();
                    $enableSafeBox                                   = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'enable_safe_box',$reportPlayerEarning->prefix);
                    $agentSingleBackground                           = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'agent_single_background',$reportPlayerEarning->prefix);          

                    $playerTransfer                                  = new PlayerTransfer();
                    $playerTransfer->prefix                          = $player->prefix;
                    $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                    $playerTransfer->rid                             = $playerAccount->rid;
                    $playerTransfer->top_id                          = $playerAccount->top_id;
                    $playerTransfer->parent_id                       = $playerAccount->parent_id;
                    $playerTransfer->player_id                       = $playerAccount->player_id;
                    $playerTransfer->is_tester                       = $playerAccount->is_tester;
                    $playerTransfer->level                           = $playerAccount->level;
                    $playerTransfer->user_name                       = $playerAccount->user_name;
                    $playerTransfer->mode                            = 1;
                    $playerTransfer->type                            = 'dividend_from_parent';
                    $playerTransfer->type_name                       = '分红';
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $input['real_amount']*10000;

                    if($enableSafeBox==1 || $agentSingleBackground==1){
                        $playerTransfer->before_balance                  = $playerAccount->balance;
                        $playerTransfer->balance                         = $playerAccount->balance;
                        $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                        $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                        $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                = $playerAccount->agentbalance +$input['real_amount']*10000;
                        $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;
                        $playerAccount->agentbalance                  = $playerTransfer->agent_balance;
                    } else{
                        $playerTransfer->before_balance                  = $playerAccount->balance;
                        $playerTransfer->balance                         = $playerAccount->balance +$input['real_amount']*10000;
                        $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                        $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                        $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                        $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                        $playerAccount->balance                      = $playerTransfer->balance;
                    }

                    $playerTransfer->save();
                    $playerAccount->save();

                    //添加流水限制
                    $playerWithdrawFlowLimit               = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id   = $playerAccount->carrier_id;
                    $playerWithdrawFlowLimit->top_id       = $playerAccount->top_id;
                    $playerWithdrawFlowLimit->parent_id    = $playerAccount->parent_id;
                    $playerWithdrawFlowLimit->rid          = $playerAccount->rid;
                    $playerWithdrawFlowLimit->player_id    = $playerAccount->player_id;
                    $playerWithdrawFlowLimit->user_name    = $playerAccount->user_name;
                    $playerWithdrawFlowLimit->limit_amount = $playerTransfer->amount;
                    $playerWithdrawFlowLimit->limit_type   = 54;
                    $playerWithdrawFlowLimit->operator_id  = 0;
                    $playerWithdrawFlowLimit->save();
                }

                \DB::commit();
                Lock::release($redisLock);
                return returnApiJson('操作成功', 1);
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('发放分红异常:'.$e->getMessage());   
                return returnApiJson('对不起，出现异常'.$e->getMessage(), 0);
            }
        }
    }

    public function commissionList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerCommission::where('carrier_id',$this->carrier->id)->orderBy('id','desc');

        if(isset($input['status']) && in_array($input['status'], [0,1])){
            $query->where('status',$input['status']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('user_name',$input['user_name']);
        }

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('player_id',$input['player_id']);
        }

        if(isset($input['startTime']) && strtotime($input['startTime'])){
            $query->where('created_at','>=',$input['startTime'].' 00:00:00');
        } else{
            $query->where('created_at','>=',date('Y-m-d').' 00:00:00');
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])){
           $query->where('created_at','<=',$input['endTime'].' 23:59:59');
        }

        $total   = $query->count();
        $items   = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            if($value->send_time){
                $value->send_time = date('Y-m-d H:i:s',$value->send_time);
            } else{
                $value->send_time = '';
            }
            
            $value->init_time = date('Y-m-d H:i:s',$value->init_time);
        }

        return returnApiJson('操作成功', 1,['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function sendCommission()
    {
        $input    = request()->all();

        if(!isset($input['id']) || empty($input['id'])) {
            return returnApiJson('对不起，ID为空或不存在', 0);
        }

        $reportPlayerCommission = PlayerCommission::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
        if(!$reportPlayerCommission) {
            return returnApiJson('对不起，此条数据不存在', 0);
        }

        $cacheKey = "player_" .$reportPlayerCommission->player_id;
        $redisLock = Lock::addLock($cacheKey,10);

        if (!$redisLock) {
                return returnApiJson('对不起，系统异常', 0);
        } else {
            try {
                \DB::beginTransaction();

                $playerAccount                                   = PlayerAccount::where('player_id',$reportPlayerCommission->player_id)->lockForUpdate()->first();
                $player                                          = Player::where('player_id',$reportPlayerCommission->player_id)->first();
                $rids                                            = explode('|',$reportPlayerCommission->rid);

                $reportPlayerCommission->send_time               = time();
                $reportPlayerCommission->status                  = 1;
                $reportPlayerCommission->admin_id                = $this->carrierUser->id;
                $reportPlayerCommission->save();

                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $player->prefix;
                $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                $playerTransfer->rid                             = $playerAccount->rid;
                $playerTransfer->top_id                          = $playerAccount->top_id;
                $playerTransfer->parent_id                       = $playerAccount->parent_id;
                $playerTransfer->player_id                       = $playerAccount->player_id;
                $playerTransfer->is_tester                       = $playerAccount->is_tester;
                $playerTransfer->level                           = $playerAccount->level;
                $playerTransfer->user_name                       = $playerAccount->user_name;
                $playerTransfer->mode                            = 1;
                $playerTransfer->type                            = 'commission_from_child';
                $playerTransfer->type_name                       = '下级返佣';
                $playerTransfer->day_m                           = date('Ym',time());
                $playerTransfer->day                             = date('Ymd',time());
                $playerTransfer->amount                          = $reportPlayerCommission->amount;

                $playerTransfer->before_balance                  = $playerAccount->balance;
                $playerTransfer->balance                         = $playerAccount->balance;
                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                $playerTransfer->frozen_balance                  = $playerAccount->frozen;


                $playerTransfer->before_agent_balance         = $playerAccount->before_agent_balance;
                $playerTransfer->agent_balance                = $playerAccount->agent_balance +$playerTransfer->amount;
                $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;;
                $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;;

                $playerTransfer->save();

                $playerAccount->agentbalance                  = $playerTransfer->agent_balance;
                $playerAccount->save();

                //写入统计表
                $reportPlayerStatDay                         = ReportPlayerStatDay::where('carrier_id',$this->carrier->id)->where('player_id',$reportPlayerCommission->player_id)->where('day',date('Ymd'))->first();
                $reportPlayerStatDay->casino_commission      = $reportPlayerCommission->casino_commission;
                $reportPlayerStatDay->electronic_commission  = $reportPlayerCommission->electronic_commission;
                $reportPlayerStatDay->esport_commission      = $reportPlayerCommission->esport_commission;
                $reportPlayerStatDay->fish_commission        = $reportPlayerCommission->fish_commission;
                $reportPlayerStatDay->card_commission        = $reportPlayerCommission->card_commission;
                $reportPlayerStatDay->sport_commission       = $reportPlayerCommission->sport_commission;
                $reportPlayerStatDay->lottery_commission     = $reportPlayerCommission->lottery_commission;
                $reportPlayerStatDay->commission             = $reportPlayerCommission->commission;
                $reportPlayerStatDay->save();


                //更新团队统计
                $update['team_casino_commission']            = \DB::raw('team_casino_commission +'.$reportPlayerStatDay->casino_commission);
                $update['team_electronic_commission']        = \DB::raw('team_electronic_commission +'.$reportPlayerStatDay->electronic_commission);
                $update['team_esport_commission']            = \DB::raw('team_esport_commission +'.$reportPlayerStatDay->esport_commission);
                $update['team_fish_commission']              = \DB::raw('team_fish_commission +'.$reportPlayerStatDay->fish_commission);
                $update['team_card_commission']              = \DB::raw('team_card_commission +'.$reportPlayerStatDay->card_commission);
                $update['team_sport_commission']             = \DB::raw('team_sport_commission +'.$reportPlayerStatDay->sport_commission);
                $update['team_lottery_commission']           = \DB::raw('team_lottery_commission +'.$reportPlayerStatDay->lottery_commission);
                $update['team_commission']                   = \DB::raw('team_commission +'.$reportPlayerStatDay->commission);

                ReportPlayerStatDay::whereIn('rid',$rids)->update($update);

                //添加流水限制
                $playerWithdrawFlowLimit               = new PlayerWithdrawFlowLimit();
                $playerWithdrawFlowLimit->carrier_id   = $reportPlayerStatDay->carrier_id;
                $playerWithdrawFlowLimit->top_id       = $reportPlayerStatDay->top_id;
                $playerWithdrawFlowLimit->parent_id    = $reportPlayerStatDay->parent_id;
                $playerWithdrawFlowLimit->rid          = $reportPlayerStatDay->rid;
                $playerWithdrawFlowLimit->player_id    = $reportPlayerStatDay->player_id;
                $playerWithdrawFlowLimit->user_name    = $reportPlayerStatDay->user_name;
                $playerWithdrawFlowLimit->limit_amount = $playerTransfer->amount;
                $playerWithdrawFlowLimit->limit_type   = 49;
                $playerWithdrawFlowLimit->operator_id  = 0;
                $playerWithdrawFlowLimit->save();

                \DB::commit();
                Lock::release($redisLock);
                return returnApiJson('操作成功', 1);
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('发放下级返佣异常:'.$e->getMessage());   
                return returnApiJson('对不起，出现异常'.$e->getMessage(), 0);
            }
        }
    }

    public function cancelCommission()
    {
        $input    = request()->all();

        if(!isset($input['id']) || empty($input['id'])) {
            return returnApiJson('对不起，ID为空或不存在', 0);
        }

        $reportPlayerCommission = PlayerCommission::where('carrier_id',$this->carrier->id)->where('id',$input['id'])->first();
        if(!$reportPlayerCommission) {
            return returnApiJson('对不起，此条数据不存在', 0);
        }

        $reportPlayerCommission->send_time               = time();
        $reportPlayerCommission->status                  = 2;
        $reportPlayerCommission->admin_id                = $this->carrierUser->id;
        $reportPlayerCommission->save();

        return returnApiJson('操作成功', 1);
    }

    public function sendAllEarnling()
    {
        $input  = request()->all();

        if(!isset($input['prefix']) || empty($input['prefix']) ){
            return returnApiJson('对不起，站点不存在', 0);
        }

        $agentSingleBackground       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'agent_single_background',$input['prefix']);
        $enableCleanLoss             = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'enable_clean_loss',$input['prefix']);
        $clean_lossAmountCycle       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'clean_loss_amount_cycle',$input['prefix']);
        $cleanLossAmount             = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'clean_loss_amount',$input['prefix']);
        $dividendsReceiveMethod      = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'dividends_receive_method',$input['prefix']);
        $isNotAutoDividendPlayerIds  = Player::where('carrier_id',$this->carrier->id)->where('prefix',$input['prefix'])->where('is_auto_dividend',0)->pluck('player_id')->toArray();

        $cleanPlayerIdsArr     = [];

        //开启了清除亏损数据  代理号号里有钱且大于100不清后台
        if($enableCleanLoss && $clean_lossAmountCycle > 0 && $cleanLossAmount >=0){
            $endDaysArr = ReportPlayerEarnings::where('prefix',$input['prefix'])->groupBy('end_day')->orderBy('end_day','desc')->pluck('end_day')->toArray();

            for ($i=1; $i<=$clean_lossAmountCycle && $i<=count($endDaysArr); $i++){
                if($i==1){
                    $cleanPlayerIds =  ReportPlayerEarnings::where('prefix',$input['prefix'])->where('amount','<=',-$cleanLossAmount*10000)->where('status',0)->where('end_day',$endDaysArr[$i-1])->pluck('player_id')->toArray();
                } else{
                    $cleanPlayerIds =  ReportPlayerEarnings::where('prefix',$input['prefix'])->where('amount','<=',-$cleanLossAmount*10000)->where('status',3)->where('end_day',$endDaysArr[$i-1])->pluck('player_id')->toArray();
                }
                
               $cleanPlayerIdsArr = array_merge($cleanPlayerIds,$cleanPlayerIdsArr);
            }

            $numbers = array_count_values($cleanPlayerIdsArr);

            $cleanPlayerIds = [];
            foreach ($numbers as $key => $value) {
                if($value >= $clean_lossAmountCycle){
                    $cleanPlayerIds[] =  $key;
                }
            }

            $agentIds          = PlayerSetting::where('user_name','like','%_'.$input['prefix'])->where('earnings','>',0)->pluck('player_id')->toArray();
            $playerAccounts    = PlayerAccount::whereIn('player_id',$agentIds)->get();

            $supplementaryDataPlayerIds = Player::whereIn('player_id',$cleanPlayerIds)->where('is_supplementary_data',1)->pluck('player_id')->toArray();
            $cleanPlayerIds             = array_diff($cleanPlayerIds, $supplementaryDataPlayerIds);

            ReportPlayerEarnings::where('prefix',$input['prefix'])->whereIn('player_id',$cleanPlayerIds)->where('status',0)->update(['status'=>2,'send_day'=>date('Ymd'),'is_allow_fast_grant'=>0]);
        }

        //取消发送
        ReportPlayerEarnings::where('prefix',$input['prefix'])->where('amount',0)->where('status',0)->update(['status'=>2,'send_day'=>date('Ymd')]);

        //累积分红
        ReportPlayerEarnings::where('prefix',$input['prefix'])->where('amount','<',0)->where('status',0)->update(['status'=>3,'accumulation'=>\DB::raw('amount'),'send_day'=>date('Ymd')]);

        //一键发放
        $reportPlayerEarnings = ReportPlayerEarnings::where('prefix',$input['prefix'])->where('amount','>',0)->where('status',0)->where('is_allow_fast_grant',1)->whereNotIn('player_id',$isNotAutoDividendPlayerIds)->get();

        foreach ($reportPlayerEarnings as $key => $value) {
            $cacheKey = "player_" .$value->player_id;
            $redisLock = Lock::addLock($cacheKey,10);

            if (!$redisLock) {
                    return returnApiJson('对不起，系统异常', 0);
            } else {
                try {

                    \DB::beginTransaction();
                    $value->real_amount = $value->amount;
                    $value->send_day    = date('Ymd');
                    $value->send_time   = time();

                    if($dividendsReceiveMethod==1){
                        $value->status      = 1;
                    } else{
                        $value->status      = 4;
                    }
                    
                    $value->save();

                    if($dividendsReceiveMethod==1){
                        $playerAccount                                   = PlayerAccount::where('player_id',$value->player_id)->lockForUpdate()->first();
                        $player                                          = PlayerAccount::where('player_id',$value->player_id)->first();
                        $enableSafeBox                                   = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'enable_safe_box',$value->prefix);

                        $playerTransfer                                  = new PlayerTransfer();
                        $playerTransfer->prefix                          = $player->prefix;
                        $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                        $playerTransfer->rid                             = $playerAccount->rid;
                        $playerTransfer->top_id                          = $playerAccount->top_id;
                        $playerTransfer->parent_id                       = $playerAccount->parent_id;
                        $playerTransfer->player_id                       = $playerAccount->player_id;
                        $playerTransfer->is_tester                       = $playerAccount->is_tester;
                        $playerTransfer->level                           = $playerAccount->level;
                        $playerTransfer->user_name                       = $playerAccount->user_name;
                        $playerTransfer->mode                            = 1;
                        $playerTransfer->type                            = 'dividend_from_parent';
                        $playerTransfer->type_name                       = '分红';
                        $playerTransfer->day_m                           = date('Ym',time());
                        $playerTransfer->day                             = date('Ymd',time());
                        $playerTransfer->amount                          = $value->amount;

                        if($enableSafeBox==1 || $agentSingleBackground==1){
                            $playerTransfer->before_balance                  = $playerAccount->balance;
                            $playerTransfer->balance                         = $playerAccount->balance;
                            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                            $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                            $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                            $playerTransfer->agent_balance                   = $playerAccount->agentbalance +$playerTransfer->amount;
                            $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                            $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                            $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                        } else{
                            $playerTransfer->before_balance                  = $playerAccount->balance;
                            $playerTransfer->balance                         = $playerAccount->balance +$playerTransfer->amount;
                            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                            $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                            $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                            $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                            $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                            $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                            $playerAccount->balance                          = $playerTransfer->balance;
                        }

                        $playerTransfer->save();
                        $playerAccount->save();

                        //添加流水限制
                        $playerWithdrawFlowLimit               = new PlayerWithdrawFlowLimit();
                        $playerWithdrawFlowLimit->carrier_id   = $playerAccount->carrier_id;
                        $playerWithdrawFlowLimit->top_id       = $playerAccount->top_id;
                        $playerWithdrawFlowLimit->parent_id    = $playerAccount->parent_id;
                        $playerWithdrawFlowLimit->rid          = $playerAccount->rid;
                        $playerWithdrawFlowLimit->player_id    = $playerAccount->player_id;
                        $playerWithdrawFlowLimit->user_name    = $playerAccount->user_name;
                        $playerWithdrawFlowLimit->limit_amount = $playerTransfer->amount;
                        $playerWithdrawFlowLimit->limit_type   = 54;
                        $playerWithdrawFlowLimit->operator_id  = 0;
                        $playerWithdrawFlowLimit->save();
                    }

                    \DB::commit();
                    Lock::release($redisLock);
                    
                } catch (\Exception $e) {
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('用户'.$value->user_name.'一键发放分红异常:'.$e->getMessage());
                }
            }
        }

        return returnApiJson('操作成功', 1);
    }
}
