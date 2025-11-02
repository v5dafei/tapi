<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Agent\BaseController;
use App\Utils\Validator;
use App\Lib\Cache\CarrierCache;
use App\Models\Map\CarrierGame;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Report\ReportPlayerStatBetFlow;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Conf\PlayerSetting;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Def\MainGamePlat;
use App\Models\Def\Development;
use App\Models\Map\CarrierGamePlat;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerLogin;
use App\Models\Log\PlayerBetFlow;
use App\Models\Log\PlayerOperate;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierBankCard;
use App\Models\PlayerTransfer;
use App\Models\PlayerInviteCode;
use App\Models\PlayerAccount;
use App\Models\CarrierPlayerGrade;
use App\Models\CarrierActivityLuckDraw;
use App\Models\CarrierActivityPlayerLuckDraw;
use App\Models\PlayerDigitalAddress;
use App\Models\PlayerMessage;
use App\Models\CarrierDigitalAddress;
use App\Models\PayChannelGroup;
use App\Models\CarrierBankCardType;
use App\Models\PlayerGameCollect;
use App\Models\Player;
use App\Models\Carrier;
use App\Models\Def\ThirdWallet;
use App\Models\Area;
use App\Lib\S3;
use App\Models\CarrierImage;
use App\Models\Conf\CarrierWebSite;
use App\Models\Def\PayChannel;
use App\Pay\Pay;
use App\Models\Log\PlayerWithdraw;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use App\Models\PlayerGameAccount;
use App\Models\PlayerHoldGiftCode;
use App\Game\Game;
use App\Models\CarrierActivityGiftCode;
use App\Lib\DevidendMode2;
use App\Lib\Cache\Lock;
use App\Lib\Cache\PlayerCache;
use App\Lib\Clog;

class AgentController extends BaseController
{
    //周期内下级数量及活跃人数
    public function subActiveStat()
    {
        //下级人数
        $data['descendantscount']          = $this->agent->descendantscount;
        $data['soncount']                  = $this->agent->soncount;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function registerFirstDepositStat()
    {
        $moths         = ReportPlayerStatDay::where('player_id',$this->agent->player_id)->groupBy('month')->orderby('month','asc')->pluck('month')->toArray();
        $sonPlayerIds  = Player::where('parent_id',$this->agent->player_id)->pluck('player_id')->toArray();
        $teamPlayerIds = Player::where('parent_id','!=',$this->agent->player_id)->where('rid','like',$this->agent->rid.'|%')->pluck('player_id')->toArray();
        $data          = [];

        if(count($moths)){
            foreach ($moths as $key => $value) {
                $startTime = date('Y-m',strtotime($value)).'-01 00:00:00';
                $endTime   = date('Y-m-d',strtotime($startTime.' +1 month -1 day')).' 23:59:59';
                $startDate = date('Ym',strtotime($value)).'01';
                $endDate   = date('Ymd',strtotime($endTime));
                $row       = [];

                $row['team_first_register']       = Player::whereIn('player_id',$teamPlayerIds)->where('created_at','>=',$startTime)->where('created_at','<=',$endTime)->count();
                $row['team_first_recharge_count'] = ReportPlayerStatDay::whereIn('player_id',$teamPlayerIds)->where('first_recharge_count',1)->where('day','>=',$startDate)->where('day','<=',$endDate)->count();

                $row['directlyunder_first_register']       = Player::whereIn('player_id',$sonPlayerIds)->where('created_at','>=',$startTime)->where('created_at','<=',$endTime)->count();
                $row['directlyunder_first_recharge_count'] = ReportPlayerStatDay::whereIn('player_id',$sonPlayerIds)->where('first_recharge_count',1)->where('day','>=',$startDate)->where('day','<=',$endDate)->count();
                $row['month']                     = $value;    
                $data []                          = $row;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function winorlossStat()
    {
       $moths         = ReportPlayerStatDay::where('player_id',$this->agent->player_id)->groupBy('month')->orderby('month','asc')->pluck('month')->toArray();
       $sonPlayerIds  = Player::where('parent_id',$this->agent->player_id)->pluck('player_id')->toArray();
       $teamPlayerIds = Player::where('parent_id','!=',$this->agent->player_id)->where('rid','like',$this->agent->rid.'|%')->pluck('player_id')->toArray();
       $data          = [];

       if(count($moths)){
            foreach ($moths as $key => $value) {
                $startTime = date('Y-m',strtotime($value)).'-01 00:00:00';
                $endTime   = date('Y-m-d',strtotime($startTime.' +1 month -1 day')).' 23:59:59';
                $startDate = date('Ym',strtotime($value)).'01';
                $endDate   = date('Ymd',strtotime($endTime));
                $row       = [];

                $directlyunderWinOrLoss = PlayerBetFlowMiddle::select(\DB::raw('sum(company_win_amount) as company_win_amount'))->whereIn('player_id',$sonPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();
                $teamWinOrLoss          = PlayerBetFlowMiddle::select(\DB::raw('sum(company_win_amount) as company_win_amount'))->whereIn('player_id',$teamPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

                $row['directlyunder_winorloss']   = is_null($directlyunderWinOrLoss->company_win_amount) ? 0: -$directlyunderWinOrLoss->company_win_amount;
                $row['team_winorloss']            = is_null($teamWinOrLoss->company_win_amount) ? 0: -$teamWinOrLoss->company_win_amount;
                $row['month']                     = $value;    
                $data []                          = $row;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);

    }

    public function depositWithdrawStat()
    {
        $moths        = ReportPlayerStatDay::where('player_id',$this->agent->player_id)->groupBy('month')->orderby('month','asc')->pluck('month')->toArray();
        $sonPlayerIds = Player::where('parent_id',$this->agent->player_id)->pluck('player_id')->toArray();
        $teamPlayerIds = Player::where('parent_id','!=',$this->agent->player_id)->where('rid','like',$this->agent->rid.'|%')->pluck('player_id')->toArray();
        $data         = [];

        if(count($moths)){
            foreach ($moths as $key => $value) {
                $startTime = date('Y-m',strtotime($value)).'-01 00:00:00';
                $endTime   = date('Y-m-d',strtotime($startTime.' +1 month -1 day')).' 23:59:59';
                $startDate = date('Ym',strtotime($value)).'01';
                $endDate   = date('Ymd',strtotime($endTime));
                $row       = [];
                $directlyunderReportPlayerStatDay               = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'))->whereIn('player_id',$sonPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

                $reportPlayerStatDay               = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'))->whereIn('player_id',$teamPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

                $row['directlyunder_recharge_amount']       = $directlyunderReportPlayerStatDay->recharge_amount;
                $row['directlyunder_withdraw_amount']       = $directlyunderReportPlayerStatDay->withdraw_amount;
                $row['team_recharge_amount']                = $reportPlayerStatDay->recharge_amount;
                $row['team_withdraw_amount']                = $reportPlayerStatDay->withdraw_amount;
                $row['month']                               = $value;    
                $data []                                    = $row;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function advlist()
    {
        $input    = request()->all();
        $language = request()->header('APP-Lang');
     
        if(!isset($input['image_category_id']) || empty(trim($input['image_category_id']))) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        //第一套模板与LOGO单独处理
        if(!isset($language) || is_null($language) || empty($language)){
            $language ='zh-cn';
        }
        
        //$data = CarrierImage::where('image_category_id',$input['image_category_id'])->where('language',$language)->where('carrier_id',$this->carrier->id)->whereIn('id',config('agentimage')[$this->carrier->id][$this->prefix])->orderBy('sort','desc')->get();

        $data = CarrierImage::where('image_category_id',$input['image_category_id'])->where('language',$language)->where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->orderBy('sort','desc')->get();

        if(!empty($data)) {
            $data = $data->toArray();
        }
        

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function balance()
    {
        $playerAccount =  PlayerAccount::where('player_id',$this->agent->player_id)->first();

        $data['agentbalance'] = $playerAccount->agentbalance;
        $data['agentfrozen']  = $playerAccount->agentfrozen;

        

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function memberDetailStat($playerId)
    {
        $data                         = [];
        $playerDividendsDay           = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_dividends_day',$this->prefix);

        $player = Player::where('player_id',$playerId)->first();

        if(!$player){
            return $this->returnApiJson(config('language')[$this->language]['error110'], 0);
        }

        if($player->parent_id!=$this->agent->player_id){
            return $this->returnApiJson(config('language')[$this->language]['error55'], 0);
        }

        switch ($playerDividendsDay) {
            case 2:
                $weeksArr           = getWeekStartEnd();
                $startDate          = $weeksArr[0];
                break;
            case 3:
                $playerDividendsStartDay           = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_realtime_dividends_start_day',$this->prefix);
                $startDate      = $playerDividendsStartDay.' 00:00:00';
                break;
            case 4:
                $startDate          = date('Y-m-d',strtotime('-1 day')).' 00:00:00';
                break;
            default:
                break;
        }

        $day                              = date('Ymd',strtotime($startDate));

        $reportPlayerStatDayStat           = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'),\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(gift) as gift'))
            ->where('player_id',$playerId)
            ->where('day','>=',$day)
            ->first();

        $data['teamrechargeamount']        = $reportPlayerStatDayStat->recharge_amount;
        $data['team_withdraw_amount']      = $reportPlayerStatDayStat->withdraw_amount;
        $data['availablebetsamount']       = $reportPlayerStatDayStat->lottery_available_bets + $reportPlayerStatDayStat->available_bets;
        $data['total_amount']              = -($reportPlayerStatDayStat->win_amount + $reportPlayerStatDayStat->lottery_winorloss);
        $data['teamGift']                  = $reportPlayerStatDayStat->gift;

       

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    } 

    public function playerDetailStat()
    {
        if($this->agent->win_lose_agent){
            $data                         = [];
            $playerDividendsDay           = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_dividends_day',$this->agent->prefix);
            $effectiveMemberDepositamount = CarrierCache::getCarrierConfigure($this->carrier->id,'effective_member_depositamount');
            $effectiveMemberAvailablebet  = CarrierCache::getCarrierConfigure($this->carrier->id,'effective_member_availablebet');
            
            switch ($playerDividendsDay) {
                case 2:
                    $weeksArr           = getWeekStartEnd();
                    $startDate          = $weeksArr[0];
                    break;
                case 3:
                    $today      = date('d',time());
                    if($today>15){
                        $startDate      = date('Y-m',time()).'-16 00:00:00';
                    } else{
                        $startDate      = date('Y-m',time()).'-01 00:00:00';    
                    }
                    break;
                case 4:
                    $monthsArr          = getMonthStartEnd();
                    $startDate          = $monthsArr[0];
                    break;
                default:
                    break;
            }

            $day                              = date('Ymd',strtotime($startDate));

            $currentPlayer = $this->agent;

            //下级人数
            $data['descendantscount']          = $currentPlayer->soncount;

            //投注用户人数
            $subordinatePlayerIds              = Player::where('parent_id','like',$currentPlayer->player_id)->pluck('player_id')->toArray();
            $playerIds                         = ReportPlayerStatDay::whereIn('player_id',$subordinatePlayerIds)->where('day','>=',$day)->where('have_bet',1)->pluck('player_id')->toArray();
            $data['betpersoncount']            = count(array_unique($playerIds));

            //新用户注册人数
            $data['registerpersoncount']       = Player::where('parent_id','like',$currentPlayer->player_id)->where('created_at','>=',$startDate)->count();

            $reportPlayerStatDayStat           = ReportPlayerStatDay::select(\DB::raw('sum(first_recharge_count) as first_recharge_count'),\DB::raw('sum(first_recharge_amount) as first_recharge_amount'),\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(casino_winorloss) as casino_winorloss'),\DB::raw('sum(electronic_winorloss) as electronic_winorloss'),\DB::raw('sum(esport_winorloss) as esport_winorloss'),\DB::raw('sum(fish_winorloss) as fish_winorloss'),\DB::raw('sum(sport_winorloss) as sport_winorloss'),\DB::raw('sum(card_winorloss) as card_winorloss'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'),\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(gift) as gift'))
                ->whereIn('player_id',$subordinatePlayerIds)
                ->where('day','>=',$day)
                ->first();

            //首存人数
            $data['firstrechargepersoncount']  = is_null($reportPlayerStatDayStat->first_recharge_count) ? 0:$reportPlayerStatDayStat->first_recharge_count ;

            //首存总金额
            $data['firstrechargeamount']       = is_null($reportPlayerStatDayStat->first_recharge_amount) ? '0.00' : $reportPlayerStatDayStat->first_recharge_amount ;

            //存款额度
            $data['teamrechargeamount']        = is_null($reportPlayerStatDayStat->recharge_amount) ? '0.00': $reportPlayerStatDayStat->recharge_amount;

            //取款金额
            $data['team_withdraw_amount']      = is_null($reportPlayerStatDayStat->withdraw_amount) ? '0.00': $reportPlayerStatDayStat->withdraw_amount ;

            //总输赢 = 公司输赢
            $data['total_amount']              = -($reportPlayerStatDayStat->win_amount + $reportPlayerStatDayStat->lottery_winorloss);

            //登录人数
            $subordinateLoginPlayerIds         = PlayerLogin::whereIn('player_id',$subordinatePlayerIds)->where('created_at','>=',$startDate)->pluck('player_id')->toArray();
            $data['loginpersonacount']         = count(array_unique($subordinateLoginPlayerIds));

            //活跃人数
            $data['activepersonacount']        = 0;                                  

            //有效投注
            $data['availablebetsamount']       = $reportPlayerStatDayStat->available_bets + $reportPlayerStatDayStat->lottery_available_bets;

            $data['availableadd']         = 0;

            $sportVenueRate         = CarrierCache::getCarrierConfigure($this->carrier->id,'sport_venue_rate');
            $casinoVenueRate        = CarrierCache::getCarrierConfigure($this->carrier->id,'casino_venue_rate');
            $electronicVenueRate    = CarrierCache::getCarrierConfigure($this->carrier->id,'electronic_venue_rate');
            $esportVenueRate        = CarrierCache::getCarrierConfigure($this->carrier->id,'esport_venue_rate');
            $fishVenueRate          = CarrierCache::getCarrierConfigure($this->carrier->id,'fish_venue_rate');
            $cardVenueRate          = CarrierCache::getCarrierConfigure($this->carrier->id,'card_venue_rate');
            $lotteryVenueRate       = CarrierCache::getCarrierConfigure($this->carrier->id,'lottery_venue_rate');

            //净盈亏计算公式
            $playerSetting  = PlayerCache::getPlayerSetting($currentPlayer->player_id);

            //游戏分钱
            $scorefee       = 0;

            if($reportPlayerStatDayStat->lottery_winorloss<0){
                $scorefee +=bcdiv($lotteryVenueRate*abs($reportPlayerStatDayStat->lottery_winorloss),100,0);
            }

            if($reportPlayerStatDayStat->casino_winorloss<0){
                $scorefee +=bcdiv($casinoVenueRate*abs($reportPlayerStatDayStat->casino_winorloss),100,0);
            }

            if($reportPlayerStatDayStat->electronic_winorloss<0){
                $scorefee +=bcdiv($electronicVenueRate*abs($reportPlayerStatDayStat->electronic_winorloss),100,0);
            }

            if($reportPlayerStatDayStat->esport_winorloss<0){
                $scorefee +=bcdiv($esportVenueRate*abs($reportPlayerStatDayStat->esport_winorloss),100,0);
            }

            if($reportPlayerStatDayStat->fish_winorloss<0){
                $scorefee +=bcdiv($fishVenueRate*abs($reportPlayerStatDayStat->fish_winorloss),100,0);
            }

            if($reportPlayerStatDayStat->sport_winorloss<0){
                $scorefee +=bcdiv($sportVenueRate*abs($reportPlayerStatDayStat->sport_winorloss),100,0);
            }

            if($reportPlayerStatDayStat->card_winorloss<0){
                $scorefee +=bcdiv($cardVenueRate*abs($reportPlayerStatDayStat->card_winorloss),100,0);
            }

            //活动礼金
            $teamGift         = $reportPlayerStatDayStat->gift;

            $data['teamGift'] = $teamGift;

            $data['scorefee']     = $scorefee;

            //(总输赢-场馆费-红利费-充提手续费-返水返佣）*佣金比例
        
            $data['netprofitloss'] = $data['total_amount'] - $scorefee - $teamGift;

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
        } else{

            $data                         = [];
            $effectiveMemberDepositamount = CarrierCache::getCarrierConfigure($this->carrier->id,'effective_member_depositamount');
            $effectiveMemberAvailablebet  = CarrierCache::getCarrierConfigure($this->carrier->id,'effective_member_availablebet');
            //开始日期
            $startDate                        = $this->agent->created_at;

            $playerCommission                 = PlayerCommission::where('player_id',$this->agent->player_id)->orderBy('id','desc')->first();

            if($playerCommission){
                $startDate = date('Y-m-d',strtotime($playerCommission->end_day)+86400).' 00:00:00';
            }

            $day                              = date('Ymd',strtotime($startDate));

            $currentPlayer                    = $this->agent;

            //下级人数
            $data['descendantscount']          = $currentPlayer->soncount;

            //投注用户人数
            $subordinatePlayerIds              = Player::where('parent_id','like',$currentPlayer->player_id)->pluck('player_id')->toArray();
            $playerIds                         = ReportPlayerStatDay::whereIn('player_id',$subordinatePlayerIds)->where('day','>=',$day)->where('have_bet',1)->pluck('player_id')->toArray();
            $data['betpersoncount']            = count(array_unique($playerIds));

            //新用户注册人数
            $data['registerpersoncount']       = Player::where('parent_id','like',$currentPlayer->player_id)->where('created_at','>=',$startDate)->count();

            $reportPlayerStatDayStat           = ReportPlayerStatDay::select(\DB::raw('sum(first_recharge_count) as first_recharge_count'),\DB::raw('sum(first_recharge_amount) as first_recharge_amount'),\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(casino_winorloss) as casino_winorloss'),\DB::raw('sum(electronic_winorloss) as electronic_winorloss'),\DB::raw('sum(esport_winorloss) as esport_winorloss'),\DB::raw('sum(fish_winorloss) as fish_winorloss'),\DB::raw('sum(sport_winorloss) as sport_winorloss'),\DB::raw('sum(card_winorloss) as card_winorloss'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'),\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(gift) as gift'))
                ->whereIn('player_id',$subordinatePlayerIds)
                ->where('day','>=',$day)
                ->first();

            //首存人数
            $data['firstrechargepersoncount']  = is_null($reportPlayerStatDayStat->first_recharge_count) ? 0:$reportPlayerStatDayStat->first_recharge_count ;

            //首存总金额
            $data['firstrechargeamount']       = is_null($reportPlayerStatDayStat->first_recharge_amount) ? '0.00' : $reportPlayerStatDayStat->first_recharge_amount ;

            //存款额度
            $data['teamrechargeamount']        = is_null($reportPlayerStatDayStat->recharge_amount) ? '0.00': $reportPlayerStatDayStat->recharge_amount;

            //取款金额
            $data['team_withdraw_amount']      = is_null($reportPlayerStatDayStat->withdraw_amount) ? '0.00': $reportPlayerStatDayStat->withdraw_amount ;

            //登录人数
            $subordinateLoginPlayerIds         = PlayerLogin::whereIn('player_id',$subordinatePlayerIds)->where('created_at','>=',$startDate)->pluck('player_id')->toArray();
            $data['loginpersonacount']         = count(array_unique($subordinateLoginPlayerIds));


            //活跃人数
            $data['activepersonacount']        = 0;                                  

            //有效投注
            $data['availablebetsamount']       = $reportPlayerStatDayStat->available_bets + $reportPlayerStatDayStat->lottery_available_bets;

            //有效新增
            $data['availableadd']         = 0;

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
        }
    }

    public function subordinateList()
    {
        $input                = request()->all();

        $operatingExpenses    = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses    = bcdiv(100-$operatingExpenses,100,2);
        $currentPage          = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize             = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset               = ($currentPage - 1) * $pageSize;

        if(!isset($input['startDate']) || empty($input['startDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error380'], 0);
        }

        if(!isset($input['endDate']) || empty($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error381'], 0);
        }

        if(time()<strtotime($input['endDate'].' 23:59:59')){
            $players = Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',1)->get();
            foreach ($players as $key => $value) {
                $result = DevidendMode2::singleStockCalculateByday($value,0);
                ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd'))->update($result);
            }
        }

        $playerIds  = Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',1)->pluck('player_id')->toArray();
        $query      = ReportPlayerStatDay::select('inf_player.created_at','report_player_stat_day.player_id','report_player_stat_day.user_name',\DB::raw('sum(report_player_stat_day.team_recharge_amount) as page_team_recharge_amount'),\DB::raw('sum(report_player_stat_day.team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(report_player_stat_day.team_member_first_register) as team_member_first_register'))->whereIn('report_player_stat_day.player_id',$playerIds)->leftJoin('inf_player','inf_player.player_id','=','report_player_stat_day.player_id')->where('report_player_stat_day.day','>=',date('Ymd',strtotime($input['startDate'])))->where('report_player_stat_day.day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('report_player_stat_day.player_id')->orderBy('inf_player.created_at','desc');

        $query1      = ReportPlayerStatDay::select('player_id','stock','team_stock')->whereIn('player_id',$playerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('player_id');

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $query->where('report_player_stat_day.user_name','like','%'.$input['user_name'].'%');
            $query1->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('report_player_stat_day.player_id',$input['player_id']);
            $query1->where('player_id',$input['player_id']);
        }

        $total          = count($query->get());
        $items          = $query->skip($offset)->take($pageSize)->get();
        $items1         = $query1->get();

        $playerSettings   = PlayerSetting::whereIn('player_id',$playerIds)->get();
        $playerSettingArr = [];
        foreach ($playerSettings as $key => $value) {
            $playerSettingArr[$value->player_id] = $value->earnings;
        }

        $stockArr   = [];
        $teamStock  = [];
        foreach ($items1 as $key => $value) {
            $stockArr[$value->player_id]  = $value->stock;
            $teamStock[$value->player_id] = $value->team_stock;
        }

        foreach ($items as $key => &$value) {
            $value->team_recharge_amount = is_null($value->page_team_recharge_amount) ? 0: $value->page_team_recharge_amount*$operatingExpenses;
            $value->team_withdraw_amount = is_null($value->team_withdraw_amount) ? 0:$value->team_withdraw_amount;
            $value->revenue              = $value->team_recharge_amount - $value->team_withdraw_amount;
            $value->earnings             = $playerSettingArr[$value->player_id];
            $value->registerNum          = $value->team_member_first_register;
            $value->user_name            = rtrim($value->user_name,'_'.$this->prefix);
            $value->extend_id            = PlayerCache::getExtendIdByplayerId($this->agent->carrier_id,$value->player_id);  

            $subReportPlayerStatDay      = ReportPlayerStatDay::select('stock','team_stock','day')->where('player_id',$value->player_id)->where('day','<=',date('Ymd',strtotime($input['endDate'])))->orderBy('day','desc')->first();
            if($subReportPlayerStatDay){
                $value->team_stock = $subReportPlayerStatDay->stock + $subReportPlayerStatDay->team_stock;
            } else{
                $value->team_stock = 0;
            }
            
        }

        

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function refillList()
    {
        $input       = request()->all();
        $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset      = ($currentPage - 1) * $pageSize;

        $query       = PlayerTransfer::where('player_id', $this->agent->player_id)->orderBy('id', 'desc');

        if(isset($input['type']) && $input['type']==2){
            //提现
            $query->where('type','withdraw_finish');
        } elseif(isset($input['type']) && $input['type']==4){
            //分红
            $query->where('type','dividend_from_parent');
        }

        if(isset($input['startDate']) && !empty(trim($input['startDate'])) && strtotime($input['startDate'])) {
            $query->where('created_at','>=',$input['startDate'].' 00:00:00');
        } else {
            $query->where('created_at','>=',date('Y-m-01 00:00:00', strtotime(date("Y-m-d"))));
        }

        if(isset($input['endDate']) && !empty(trim($input['endDate'])) && strtotime($input['endDate'])) {
            $query->where('created_at','<=',$input['endDate'].' 23:59:59');
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['item' => $items, 'total' => $total ,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    //绑定数字币地址
    public function digitalAdd($digitalId=0)
    {
        $input = request()->all();

        if($digitalId){
            $playerDigitalAddress = PlayerDigitalAddress::where('player_id',$this->agent->player_id)->where('id',$digitalId)->first();
            if(!$playerDigitalAddress){
                return $this->returnApiJson(config('language')[$this->language]['error252'], 0);
            }
        } else {
            $playerDigitalAddress = new PlayerDigitalAddress();
        }

        if(!isset($input['address']) || empty($input['address'])){
            return $this->returnApiJson(config('language')[$this->language]['error442'], 0);
        } 

        if(!isset($input['type']) || !in_array($input['type'], [1,2,3,4,6,7,8,9,10,11,12])){
            return $this->returnApiJson(config('language')[$this->language]['error443'], 0);
        } 

        $playerDigitalAddress->carrier_id     = $this->carrier->id;
        $playerDigitalAddress->player_id      = $this->agent->player_id;
        $playerDigitalAddress->address        = $input['address'];
        $playerDigitalAddress->is_default     = 1;
        $playerDigitalAddress->status         = 1;
        $playerDigitalAddress->sort           = 1;
        $playerDigitalAddress->type           = $input['type'];
        $playerDigitalAddress->prefix         = $this->prefix;
        $playerDigitalAddress->win_lose_agent = 1;
        $playerDigitalAddress->save();

        $playerOperate                        = new PlayerOperate();
        $playerOperate->carrier_id            = $this->carrier->id;
        $playerOperate->player_id             = $this->agent->player_id;
        $playerOperate->user_name             = $this->agent->user_name;
        $playerOperate->type                  = 2;

        switch ($input['address']) {
            case '1':
                $playerOperate->desc                  = '绑定Trc20地址:'.$input['address'];
                break;
            case '2':
                $playerOperate->desc                  = '绑定Erc20地址:'.$input['address'];
                break;
            case '3':
                $playerOperate->desc                  = '绑定OkPay地址:'.$input['address'];
                break;
            case '4':
                $playerOperate->desc                  = '绑定GoPay地址:'.$input['address'];
                break;
            case '6':
                $playerOperate->desc                  = '绑定ToPay地址:'.$input['address'];
                break;
            case '7':
                $playerOperate->desc                  = '绑定EbPay地址:'.$input['address'];
                break;
            case '8':
                $playerOperate->desc                  = '绑定万币地址:'.$input['address'];
                break;
            case '9':
                $playerOperate->desc                  = '绑定JdPay地址:'.$input['address'];
                break;
            case '10':
                $playerOperate->desc                  = '绑定K豆地址:'.$input['address'];
                break;
            case '11':
                $playerOperate->desc                  = '绑定NoPay地址:'.$input['address'];
                break;
            case '12':
                $playerOperate->desc                  = '绑定波币地址:'.$input['address'];
                break;
            
            default:
                // code...
                break;
        }

        $playerOperate->ip                    = ip2long(real_ip());
        $playerOperate->save();

        

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function changePassword()
    {
        $input          = request()->all();
        

        if(!isset($input['password']) || !isset($input['newpassword'])) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        //登录密码不能与支付密码相同
        if(\Hash::check($input['newpassword'], $this->agent->paypassword)) {
            return $this->returnApiJson(config('language')[$this->language]['error129'], 0);
        }

        //修改登录密码
        if(!\Hash::check($input['password'], $this->agent->password)) {
            return $this->returnApiJson(config('language')[$this->language]['error68'], 0);
        }

        $this->agent->password = bcrypt($input['newpassword']);
        $this->agent->save();

        
        $playerOperate                                    = new PlayerOperate();
        $playerOperate->carrier_id                        = $this->agent->carrier_id;
        $playerOperate->player_id                         = $this->agent->player_id;
        $playerOperate->user_name                         = $this->agent->user_name;
        $playerOperate->type                              = 3;
        $playerOperate->desc                              = '';
        $playerOperate->ip                                = ip2long(real_ip());
        $playerOperate->save();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function changePayPassword()
    {
        $input          = request()->all();
        

        if($this->agent->paypassword){
            if(!isset($input['password'])  || !isset($input['newpassword'])) {
                return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
            }

            //登录密码不能与支付密码相同
            if(\Hash::check($input['newpassword'], $this->agent->password)) {
                return $this->returnApiJson(config('language')[$this->language]['error129'], 0);
            }

            //修改资金密码
            if(!\Hash::check($input['password'], $this->agent->paypassword)) {
                return $this->returnApiJson(config('language')[$this->language]['error69'], 0);
            }

        } else {
            if(!isset($input['loginpassword'])  || !isset($input['newpassword'])) {
                return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
            }

            //登录密码不能与支付密码相同
            if(\Hash::check($input['newpassword'], $this->agent->password)) {
                return $this->returnApiJson(config('language')[$this->language]['error129'], 0);
            }

            //修改资金密码
            if(!\Hash::check($input['loginpassword'], $this->agent->password)) {
                return $this->returnApiJson(config('language')[$this->language]['error68'], 0);
            }
        }

        $this->agent->paypassword = bcrypt($input['newpassword']);
        $this->agent->save();

        
        $playerOperate                                    = new PlayerOperate();
        $playerOperate->carrier_id                        = $this->agent->carrier_id;
        $playerOperate->player_id                         = $this->agent->player_id;
        $playerOperate->user_name                         = $this->agent->user_name;
        $playerOperate->type                              = 4;
        $playerOperate->desc                              = '';
        $playerOperate->ip                                = ip2long(real_ip());
        $playerOperate->save();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function promoteLinklist()
    {
        $promotelinks    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'h5url',$this->prefix);
        $promotelinkarrs = explode(',',$promotelinks);

        $link             = [];
        $data             = [];
        $playerInviteCode = PlayerInviteCode::where('player_id',$this->agent->player_id)->first();
        if(!empty($playerInviteCode->domain)){
            $link[]        = 'https://www.'.$playerInviteCode->domain;
        } 

        foreach ($promotelinkarrs as $key => $value) {
            if(!empty($value)){
                $link[]           = 'https://'.$playerInviteCode->code.'.'.$value;
            }
        }
        $data['links'] = $link;
        $data['code']  = $playerInviteCode->code;

        return returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function betList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $playerIds      = Player::where('parent_id',$this->agent->player_id)->pluck('player_id')->toArray();

        $query =PlayerBetFlow::select('def_main_game_plats.alias','inf_player.user_name','log_player_bet_flow.id','log_player_bet_flow.game_flow_code','log_player_bet_flow.main_game_plat_id','log_player_bet_flow.user_name as game_user_name','log_player_bet_flow.game_category','log_player_bet_flow.main_game_plat_code','log_player_bet_flow.game_name','log_player_bet_flow.bet_time','log_player_bet_flow.bet_amount','log_player_bet_flow.available_bet_amount','log_player_bet_flow.company_win_amount','log_player_bet_flow.game_status','log_player_bet_flow.bet_flow_available','log_player_bet_flow.issue','log_player_bet_flow.opendata','log_player_bet_flow.bet_info')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_bet_flow.player_id')
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','log_player_bet_flow.main_game_plat_id')
            ->where('log_player_bet_flow.carrier_id',$this->carrier->id)
            ->whereIn('log_player_bet_flow.player_id',$playerIds)
            ->orderBy('log_player_bet_flow.bet_time','desc');

        $query3 = PlayerBetFlow::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(bet_amount) as bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','log_player_bet_flow.main_game_plat_id')
            ->where('log_player_bet_flow.carrier_id',$this->carrier->id)
            ->whereIn('log_player_bet_flow.player_id',$playerIds);

        $query4 =PlayerBetFlow::select('def_main_game_plats.alias','inf_player.user_name','inf_player.player_id','inf_player.extend_id','log_player_bet_flow.id','log_player_bet_flow.game_flow_code','log_player_bet_flow.main_game_plat_id','log_player_bet_flow.user_name as game_user_name','log_player_bet_flow.game_category','log_player_bet_flow.main_game_plat_code','log_player_bet_flow.game_name','log_player_bet_flow.bet_time','log_player_bet_flow.bet_amount','log_player_bet_flow.available_bet_amount','log_player_bet_flow.company_win_amount','log_player_bet_flow.game_status','log_player_bet_flow.bet_flow_available','log_player_bet_flow.issue','log_player_bet_flow.opendata','log_player_bet_flow.bet_info')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_bet_flow.player_id')
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','log_player_bet_flow.main_game_plat_id')
            ->where('log_player_bet_flow.carrier_id',$this->carrier->id)
            ->whereIn('log_player_bet_flow.player_id',$playerIds)
            ->orderBy('log_player_bet_flow.bet_time','desc');

        $query2 = PlayerBetFlow::select('log_player_bet_flow.id')->where('log_player_bet_flow.carrier_id',$this->carrier->id)->whereIn('log_player_bet_flow.player_id',$playerIds)->orderBy('log_player_bet_flow.bet_time','desc');
        $query1 = PlayerBetFlow::select('log_player_bet_flow.id')->where('log_player_bet_flow.carrier_id',$this->carrier->id)->whereIn('log_player_bet_flow.player_id',$playerIds);


        if(isset($input['player_id']) && trim($input['player_id'])){
            $query->where('inf_player.player_id',$input['player_id']);
            $query1->leftJoin('inf_player','inf_player.player_id','=','log_player_bet_flow.player_id')->where('inf_player.player_id',$input['player_id']);
            $query2->leftJoin('inf_player','inf_player.player_id','=','log_player_bet_flow.player_id')->where('inf_player.player_id',$input['player_id']);
            $query3->leftJoin('inf_player','inf_player.player_id','=','log_player_bet_flow.player_id')->where('inf_player.player_id',$input['player_id']);
        } elseif(isset($input['user_name']) && trim($input['user_name']) != '' ) {
            $query->where('inf_player.user_name','like','%'.$input['user_name'].'%');
            $query1->leftJoin('inf_player','inf_player.player_id','=','log_player_bet_flow.player_id')->where('inf_player.user_name','like','%'.$input['user_name'].'%');
            $query2->leftJoin('inf_player','inf_player.player_id','=','log_player_bet_flow.player_id')->where('inf_player.user_name','like','%'.$input['user_name'].'%');
            $query3->leftJoin('inf_player','inf_player.player_id','=','log_player_bet_flow.player_id')->where('inf_player.user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['startDate']) &&  strtotime($input['startDate'])) {
            $query->where('log_player_bet_flow.bet_time','>=',strtotime($input['startDate']));
            $query1->where('log_player_bet_flow.bet_time','>=',strtotime($input['startDate']));
            $query2->where('log_player_bet_flow.bet_time','>=',strtotime($input['startDate']));
            $query3->where('log_player_bet_flow.bet_time','>=',strtotime($input['startDate']));
        }

        if(isset($input['endDate']) && strtotime($input['endDate']) ) {
            $query->where('log_player_bet_flow.bet_time','<=',strtotime($input['endDate'].' 23:59:59'));
            $query1->where('log_player_bet_flow.bet_time','<=',strtotime($input['endDate'].' 23:59:59'));
            $query2->where('log_player_bet_flow.bet_time','<=',strtotime($input['endDate'].' 23:59:59'));
            $query3->where('log_player_bet_flow.bet_time','<=',strtotime($input['endDate'].' 23:59:59'));
        }

        if(isset($input['main_game_plat_id']) && trim($input['main_game_plat_id']) != '' ) {
            $query->where('log_player_bet_flow.main_game_plat_id',$input['main_game_plat_id']);
            $query1->where('log_player_bet_flow.main_game_plat_id',$input['main_game_plat_id']);
            $query2->where('log_player_bet_flow.main_game_plat_id',$input['main_game_plat_id']);
            $query3->where('log_player_bet_flow.main_game_plat_id',$input['main_game_plat_id']);
        }

        $logPlayerBetFlowIds = $query2->skip($offset)->take($pageSize)->pluck('id')->toArray();

        $total         = $query1->count();
        $statTotal     = $query3->first();
        $item          = $query4->whereIn('log_player_bet_flow.id',$logPlayerBetFlowIds)->get();

        $gamePlatIds   = CarrierGamePlat::where('carrier_id',$this->carrier->id)->pluck('game_plat_id')->toArray();
        $mainGamePlats = MainGamePlat::whereIn('main_game_plat_id',$gamePlatIds)->get();

        $plats         = [];

        foreach ($mainGamePlats as  $value) {
            $row                        = [];
            $row['main_game_plat_id']   = $value->main_game_plat_id;
            $row['value']               = $value->alias;
            $plats[]                    = $row;
        }

        foreach ($item as $key => &$value) {
           $value->bet_amount             = $value->bet_amount*10000;
           $value->available_bet_amount   = $value->available_bet_amount*10000;
           $value->company_win_amount     = $value->company_win_amount*10000;
        }

        if(is_null($statTotal->available_bet_amount)){
            $statTotal->available_bet_amount = 0.00;
            $statTotal->bet_amount           = 0.00;
            $statTotal->company_win_amount   = 0.00;
        }

        foreach ($item as $k => $v) {
            $v->user_name = rtrim($v->user_name,'_'.$this->prefix);
        }
        return returnApiJson(config('language')[$this->language]['success1'], 1, ['statTotal'=>$statTotal,'item' => $item,  'plats' => $plats, 'total' => $total,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function dividendList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        //$types          = ['gift_transfer_reduce','code_gift','gift_transfer_add','gift','dividend_from_parent'];
        $types          = array_unique(array_merge(config('main')['giftadd'],config('main')['giftdeduction']));

        $query          = PlayerTransfer::where('parent_id',$this->agent->player_id)->whereIn('type',$types);

        if(isset($input['player_id']) && trim($input['player_id']) != '' ) {
            if(strlen($input['player_id'])==8){
                $query->where('player_id',$input['player_id']);
            }else{
                $playerId = PlayerCache::getPlayerIdByExtentId($this->agent->prefix,$input['player_id']);
                $query->where('player_id',$playerId);
            }
        }

        if(isset($input['user_name']) && trim($input['user_name']) != '' ) {
            $query->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['startDate']) &&  strtotime($input['startDate'])) {
            $query->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00');
        }

        if(isset($input['endDate']) && strtotime($input['endDate']) ) {
            $query->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59');
        }

        if(isset($input['type']) && in_array($input['type'],$types)){
            $query->where('type',$input['type']);
        } else{
            $query->whereIn('type',$types);
        }

        $developments     =  Development::all();
        $developmenttypes = [];

        foreach ($developments as $key => $value) {
            if(in_array($value->sign,$types)){
                $row                = [];
                $row['key']         = $value->sign;
                $row['value']       = $value->name;
                $developmenttypes[] = $row;
            }
        }

        $total         = $query->count();
        $item          = $query->skip($offset)->take($pageSize)->get();

        foreach ($item as $key => $value) {
            $value->user_name = rtrim($value->user_name,'_'.$this->prefix);
            $value->extend_id = PlayerCache::getExtendIdByplayerId($value->carrier_id,$value->player_id); 
        }
        return returnApiJson(config('language')[$this->language]['success1'], 1, ['types'=>$developmenttypes,'itme' => [], 'total' => 0,'currentPage' => 1, 'totalPage' => 1]);
    }

    public function rechargeList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerDepositPayLog::select('user_name','player_id','created_at','arrivedamount','amount','third_fee')->where('parent_id',$this->agent->player_id)->where('status',1)->orderby('id','desc');

        if(isset($input['user_name']) && trim($input['user_name']) != '' ) {
            $query->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '' ) {
            if(strlen($input['player_id'])==8){
                $query->where('player_id',$input['player_id']);
            }else{
                $playerId = PlayerCache::getPlayerIdByExtentId($this->agent->prefix,$input['player_id']);
                $query->where('player_id',$playerId);
            }
        }

        if(isset($input['startDate']) &&  strtotime($input['startDate'])) {
            $query->where('created_at','>=',$input['startDate']);
        }

        if(isset($input['endDate']) && strtotime($input['endDate']) ) {
            $query->where('created_at','<=',$input['endDate'].' 23:59:59');
        }

        $total         = $query->count();
        $item          = $query->skip($offset)->take($pageSize)->get();

        foreach ($item as $key => $value) {
            $value->user_name = rtrim($value->user_name,'_'.$this->prefix);
            $value->extend_id = PlayerCache::getExtendIdByplayerId($this->agent->carrier_id,$value->player_id);
        }

        return returnApiJson(config('language')[$this->language]['success1'], 1, ['itme' => $item, 'total' => $total,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function withdrawList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerWithdraw::where('parent_id',$this->agent->player_id)->whereIn('status',[1,2]);

        if(isset($input['player_id']) && trim($input['player_id']) != '' ) {
            if(strlen($input['player_id'])==8){
                $query->where('player_id',$input['player_id']);
            }else{
                $playerId = PlayerCache::getPlayerIdByExtentId($this->agent->prefix,$input['player_id']);
                $query->where('player_id',$playerId);
            }
        }

        if(isset($input['user_name']) && trim($input['user_name']) != '' ) {
            $query->where('user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['startDate']) &&  strtotime($input['startDate'])) {
            $query->where('created_at','>=',$input['startDate']);
        }

        if(isset($input['endDate']) && strtotime($input['endDate']) ) {
            $query->where('created_at','<=',$input['endDate'].' 23:59:59');
        }

        $total         = $query->count();
        $item          = $query->skip($offset)->take($pageSize)->get();

        foreach ($item as $key => $value) {
            $value->user_name = rtrim($value->user_name,'_'.$this->prefix);
            $value->extend_id = PlayerCache::getExtendIdByplayerId($this->agent->carrier_id,$value->player_id);
        }

        return returnApiJson(config('language')[$this->language]['success1'], 1, ['itme' => $item, 'total' => $total,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function selfWithdrawList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerWithdraw::where('player_id',$this->agent->player_id);

        if(isset($input['startDate']) &&  strtotime($input['startDate'])) {
            $query->where('created_at','>=',$input['startDate']);
        }

        if(isset($input['endDate']) && strtotime($input['endDate']) ) {
            $query->where('created_at','<=',$input['endDate'].' 23:59:59');
        }

        $total         = $query->count();
        $item          = $query->skip($offset)->take($pageSize)->get();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['item' => $item, 'total' => $total,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function commissionPersonalReport()
    {
        $input                        = request()->all();
        $playerSetting                = PlayerCache::getPlayerSetting($this->agent->player_id);
        $data                         = [];

        $data['data']                 = ReportPlayerEarnings::select('from_day','end_day','directlyunder_recharge_amount','directlyunder_withdraw_amount','team_recharge_amount','team_withdraw_amount','earnings','accumulation','lastaccumulation','real_amount','directlyunder_stock','team_stock','directlyunder_stock_change','team_stock_change')->where('player_id',$this->agent->player_id)->where('status','!=',0)->orderBy('id','desc')->limit(30)->get();

        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function commissionTeamReport()
    {
        $input                        = request()->all();

        $subPlayers                   = Player::where('inviteplayerid',$this->agent->player_id)->where('win_lose_agent',1)->pluck('player_id')->toArray();

         if(isset($input['sendDay']) && strtotime($input['sendDay'])){
                $data = ReportPlayerEarnings::whereIn('player_id',$subPlayers)->where('status',1)->where('send_day',$input['sendDay'])->orderby('id','desc')->get();
            } else{
                $data = ReportPlayerEarnings::whereIn('player_id',$subPlayers)->where('status',1)->orderBy('id','desc')->get();
            }

        //活跃人数
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }


    public function financePersonalHistoryReport()
    {
        $input          = request()->all();
        $operatingExpenses    = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses    = bcdiv(100-$operatingExpenses,100,2);
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $data           = [];

        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error380'], 0);
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error381'], 0);
        }

        if(time()<strtotime($input['endDate'].' 23:59:59')){
            $players = Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',0)->get();
            foreach ($players as $key => $value) {
                $result = DevidendMode2::singleStockCalculateMemberByday($value,0);
                ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd'))->update($result);
            }
        }

        $selfPlayerSetting                =  PlayerCache::getPlayerSetting($this->agent->player_id);
        $underPlayerIds                   =  Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',0)->pluck('player_id')->toArray();
    
        //净输赢
        $selfPlayerStatDayStat           = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as page_recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(change_self_stock) as change_self_stock'))
            ->whereIn('player_id',$underPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->first()->toArray();

        $selfPlayerStatDayStat['recharge_amount'] = $selfPlayerStatDayStat['page_recharge_amount']*$operatingExpenses;
        $selfPlayerStatDayStat['revenue']         = $selfPlayerStatDayStat['recharge_amount'] - $selfPlayerStatDayStat['withdraw_amount'];
        $selfPlayerStatDayStat['commission']      = bcdiv(($selfPlayerStatDayStat['revenue'] - $selfPlayerStatDayStat['change_self_stock'])*$selfPlayerSetting->earnings, 100,2);

        $query                            = ReportPlayerStatDay::select('player_id','user_name',\DB::raw('sum(recharge_amount) as page_recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$underPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('player_id');

        $total                            = count($query->get());
        $items                            = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->recharge_amount    = $value->page_recharge_amount*$operatingExpenses;
            $value->revenue            = $value->recharge_amount-$value->withdraw_amount;
            $value->contribute_amount  = bcdiv(($value->revenue - $value->change_self_stock)*$selfPlayerSetting->earnings,100,0);
            $value->extend_id          = PlayerCache::getExtendIdByplayerId($this->agent->carrier_id,$this->agent->player_id);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['selfStat'=>$selfPlayerStatDayStat,'item' => $items,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function memberTimeIntervalStat()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $operatingExpenses    = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses    = bcdiv(100-$operatingExpenses,100,2);
        $data           = [];

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson(config('language')[$this->language]['error357'], 0);
        }

        $player                           = Player::where('player_id',$input['player_id'])->first();

        if(!$player){
            return $this->returnApiJson(config('language')[$this->language]['error110'], 0);
        }

        if($player->win_lose_agent){
            return $this->returnApiJson(config('language')[$this->language]['error444'], 0);
        }

        if($player->parent_id != $this->agent->player_id){
            return $this->returnApiJson(config('language')[$this->language]['error55'], 0);
        }

        $selfPlayerSetting                = PlayerCache::getPlayerSetting($this->agent->player_id);

        $query                            = ReportPlayerStatDay::select('player_id','user_name','day','recharge_amount','withdraw_amount','change_self_stock')->where('player_id',$player->player_id)->where('day','>=',date('Ymd',strtotime($player->created_at)));

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total                            = $query->count();
        $items                            = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->day                = date('Y-m-d',strtotime($value->day));
            $value->recharge_amount    = $value->recharge_amount*$operatingExpenses;
            $value->revenue            = $value->recharge_amount-$value->withdraw_amount;
            $value->contribute_amount  = bcdiv(($value->revenue - $value->change_self_stock)*$selfPlayerSetting->earnings,100,0);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['item' => $items,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function financePersonalReport()
    {
        $input                            = request()->all();
        $data                             = [];
        $operatingExpenses    = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses    = bcdiv(100-$operatingExpenses,100,2);
        $underPlayerIds                   = Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',0)->pluck('player_id')->toArray();
    
        //净输赢
        $query  = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as page_recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'))
            ->whereIn('player_id',$underPlayerIds);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $reportPlayerStatDayStat = $query->first();

        //存款额度
        $teamRechargeAmount     = $reportPlayerStatDayStat->page_recharge_amount;

        //取款额度
        $teamWithdrawAmount     = $reportPlayerStatDayStat->withdraw_amount;
        //游戏输赢
        $totalAmount            = $reportPlayerStatDayStat->win_amount + $reportPlayerStatDayStat->lottery_winorloss;

        $data['team_withdraw_amount'] = is_null($teamWithdrawAmount)?'0.00':$teamWithdrawAmount;
        $data['team_recharge_amount'] = is_null($teamRechargeAmount)?'0.00':$teamRechargeAmount*$operatingExpenses;
        $data['totalAmount']          = $totalAmount;


        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }


    public function financeTeamHistoryReport()
    {
        $operatingExpenses    = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses    = bcdiv(100-$operatingExpenses,100,2);
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $data           = [];

        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error380'], 0);
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error381'], 0);
        }

        if(time()<strtotime($input['endDate'].' 23:59:59')){
            $players = Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',1)->get();
            foreach ($players as $key => $value) {
                $result = DevidendMode2::singleStockCalculateByday($value,0);
                ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd'))->update($result);
            }
        }

        $selfPlayerSetting                = PlayerCache::getPlayerSetting($this->agent->player_id);
        $underAgentPlayers                = Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',1)->get();
        $underAgentPlayerIds              = [];

        foreach ($underAgentPlayers as $key => $value) {
            $underAgentPlayerIds[] = $value->player_id;
        }

        $underAgentPlayerSettings          = PlayerSetting::whereIn('player_id',$underAgentPlayerIds)->get();
        $underAgentPlayerSettingArr        = [];

        foreach ($underAgentPlayerSettings as $key => $value) {
            $underAgentPlayerSettingArr[$value->player_id] = $value->earnings;
        }

        //查询团队数据
        $query                             = ReportPlayerStatDay::where('parent_id',$this->agent->player_id)->where('win_lose_agent',1)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('player_id')->orderBy('player_id','desc');
        $total                             = count($query->get());
        $items                             = $query->skip($offset)->take($pageSize)->get();

        $resultPlayerIds = [];
        foreach ($items as $key => $value) {
            $resultPlayerIds[] = $value->player_id;
        }

        $selfPlayerStatDayStat                         = [];
        $selfPlayerStatDayStat['team_recharge_amount'] = 0;
        $selfPlayerStatDayStat['team_withdraw_amount'] = 0;
        $selfPlayerStatDayStat['team_revenue']         = 0;
        $selfPlayerStatDayStat['team_change_stock']    = 0;
        $selfPlayerStatDayStat['team_commission']      = 0;

        $teamData = [];

        foreach ($underAgentPlayers as $key => $value) {
            $playerStatDayStat  = ReportPlayerStatDay::select(\DB::raw('sum(team_recharge_amount) as page_team_recharge_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(change_team_stock) as change_team_stock'),\DB::raw('sum(change_stock) as change_stock'))
                ->where('player_id',$value->player_id)
                ->where('day','>=',date('Ymd',strtotime($input['startDate'])))
                ->where('day','<=',date('Ymd',strtotime($input['endDate'])))
                ->first();

            if($playerStatDayStat){
                $selfPlayerStatDayStat['team_recharge_amount'] += $playerStatDayStat->page_team_recharge_amount*$operatingExpenses;
                $selfPlayerStatDayStat['team_withdraw_amount'] += $playerStatDayStat->team_withdraw_amount;
                $selfPlayerStatDayStat['team_revenue']         += $playerStatDayStat->page_team_recharge_amount*$operatingExpenses - $playerStatDayStat->team_withdraw_amount;
                $selfPlayerStatDayStat['team_change_stock']    += $playerStatDayStat->change_team_stock + $playerStatDayStat->change_stock;
                $selfPlayerStatDayStat['team_commission']      += bcdiv(($playerStatDayStat->page_team_recharge_amount*$operatingExpenses - $playerStatDayStat->team_withdraw_amount- $playerStatDayStat->change_team_stock - $playerStatDayStat->change_stock)*($selfPlayerSetting->earnings-$underAgentPlayerSettingArr[$value->player_id]), 100,2);

                if(in_array($value->player_id,$resultPlayerIds)){
                    $row                            = [];
                    $row['player_id']               = $value->player_id;
                    $row['extend_id']               = PlayerCache::getExtendIdByplayerId($value->carrier_id,$value->player_id);
                    $row['user_name']               = $value->user_name;
                    $row['team_recharge_amount']    = $playerStatDayStat->page_team_recharge_amount*$operatingExpenses;
                    $row['team_withdraw_amount']    = $playerStatDayStat->team_withdraw_amount;
                    $row['team_revenue']            = $playerStatDayStat->page_team_recharge_amount*$operatingExpenses - $playerStatDayStat->team_withdraw_amount;
                    $row['team_change_stock']       = $playerStatDayStat->change_team_stock + $playerStatDayStat->change_stock;
                    $row['earnings']                = $underAgentPlayerSettingArr[$value->player_id];
                    $row['team_commission']         = bcdiv(($playerStatDayStat->page_team_recharge_amount*$operatingExpenses - $playerStatDayStat->team_withdraw_amount- $playerStatDayStat->change_team_stock - $playerStatDayStat->change_stock)*($selfPlayerSetting->earnings-$underAgentPlayerSettingArr[$value->player_id]), 100,2);

                    $teamData[] = $row;
                }
            } 
        }
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['selfStat'=>$selfPlayerStatDayStat,'item' => $teamData,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function teamTimeIntervalStat()
    {
        $operatingExpenses    = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses    = bcdiv(100-$operatingExpenses,100,2);
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $data           = [];

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson(config('language')[$this->language]['error357'], 0);
        }

        $player   = Player::where('player_id',$input['player_id'])->first();

        if(!$player){
            return $this->returnApiJson(config('language')[$this->language]['error110'], 0);
        }

        if(!$player->win_lose_agent){
            return $this->returnApiJson(config('language')[$this->language]['error445'], 0);
        }

        if($player->parent_id != $this->agent->player_id){
            return $this->returnApiJson(config('language')[$this->language]['error55'], 0);
        }

        $selfPlayerSetting                = PlayerCache::getPlayerSetting($this->agent->player_id);

        $query                            = ReportPlayerStatDay::select('day','player_id','user_name',\DB::raw('team_recharge_amount as page_team_recharge_amount'),'team_withdraw_amount','change_team_stock','change_stock')->where('player_id',$input['player_id']);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total                            = $query->count();
        $items                            = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->day                     = date('Y-m-d',strtotime($value->day));
            $value->team_recharge_amount    = $value->recharge_amount*$operatingExpenses;
            $value->team_revenue            = $value->recharge_amount-$value->team_withdraw_amount;
            $value->team_change_stock       = $value->change_team_stock+$value->change_stock;
            $value->team_contribute_amount  = bcdiv(($value->revenue - $value->change_self_stock)*$selfPlayerSetting->earnings,100,0);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['item' => $items,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function financeTeamreport()
    {
        $operatingExpenses                = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses                = bcdiv(100-$operatingExpenses,100,2);
        $input                            = request()->all();
        $data                             = [];
        $underPlayerIds                   = Player::where('parent_id','!=',$this->agent->player_id)->where('rid','like',$this->agent->rid.'|%')->pluck('player_id')->toArray();
    
        //净输赢
        $query  = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as page_recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(gift) as gift'),\DB::raw('sum(dividend) as dividend'))
            ->whereIn('player_id',$underPlayerIds);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $reportPlayerStatDayStat = $query->first();

        //存款额度
        $teamRechargeAmount     = $reportPlayerStatDayStat->page_recharge_amount*$operatingExpenses;

        //取款额度
        $teamWithdrawAmount     = $reportPlayerStatDayStat->withdraw_amount;
        //游戏输赢
        $totalAmount            = $reportPlayerStatDayStat->win_amount + $reportPlayerStatDayStat->lottery_winorloss;

        $data['team_withdraw_amount'] = is_null($teamWithdrawAmount)?'0.00':$teamWithdrawAmount;
        $data['team_recharge_amount'] = is_null($teamRechargeAmount)?'0.00':$teamRechargeAmount;
        $data['totalAmount']          = $totalAmount;


        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function settlementDateList()
    {
        $data = ReportPlayerEarnings::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->where('send_day','<>',0)->orderBy('id','desc')->groupby('send_day')->limit(5)->pluck('send_day')->toArray();
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function channeLlist()
    {
        $input = request()->all();
        if(!isset($input['is_mobile']) || !in_array($input['is_mobile'], [1,0])){
            return $this->returnApiJson(config('language')[$this->language]['error260'], 0);
        }

        $payChannelIds          = PayChannel::where('type',1)->pluck('id')->toArray();
        $carrierThirdPartPayIds = CarrierThirdPartPay::where('carrier_id',$this->agent->carrier_id)->whereIn('def_pay_channel_id',$payChannelIds)->pluck('id')->toArray();
        $query                  = CarrierPayChannel::select('def_pay_channel_list.min','def_pay_channel_list.max','def_pay_channel_list.enum','def_pay_factory_list.currency','inf_carrier_pay_channel.id','inf_carrier_pay_channel.img','inf_carrier_pay_channel.show_name')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('inf_carrier_pay_channel.carrier_id',$this->carrier->id)
            ->where('inf_carrier_pay_channel.status',1)
            ->whereIn('inf_carrier_pay_channel.binded_third_part_pay_id',$carrierThirdPartPayIds)
            ->orderby('inf_carrier_pay_channel.sort','desc');
        if($input['is_mobile']==1){
            $data               = $query->whereIn('inf_carrier_pay_channel.show',[2,3])->get();
        } else{
            $data               = $query->whereIn('inf_carrier_pay_channel.show',[1,3])->get();
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function deposit()
    {
        $params                                                = request()->all();
        $notifyUrl                                             = config('main.notifyUrl');
        $returnUrl                                             = config('main.returnUrl');
       

        if($this->agent->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        if(!isset($params['amount']) || empty($params['amount'])) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        $continuousUnpaid                                     = CarrierCache::getCarrierConfigure($this->carrier->id,'continuous_unpaid');
        $banHour                                              = CarrierCache::getCarrierConfigure($this->carrier->id,'ban_hour');
        $banTime                                              = $banHour*60;
        $tag                                                  = 'orderLock';                                          
        $time                                                 = time(); 
        $key                                                  = 'orderLock_'.$this->agent->player_id;
        $flag                                                 = false;
        if($continuousUnpaid){
            $checkPlayerDepositPayLogs                            = PlayerDepositPayLog::where('player_id',$this->agent->player_id)->where('created_at','>=',date('Y-m-d H:i:s',$time-$banTime))->orderBy('id','desc')->take($continuousUnpaid)->get();

            if($checkPlayerDepositPayLogs && count($checkPlayerDepositPayLogs) >= $continuousUnpaid){

                foreach ($checkPlayerDepositPayLogs as $k => $v) {
                    if($v->status==1){
                        $flag  = true;
                    }
                }

                if(!$flag){
                    cache()->tags($tag)->put($key, 1, now()->addMinutes($banHour));
                }
            }
        }

        if(cache()->tags($tag)->has($key)){
            return $this->returnApiJson(config('language')[$this->language]['error219'], 0);
        }


        $amount       = $params['amount'];

        $isFirstDepositPay                  = PlayerDepositPayLog::where('player_id',$this->agent->player_id)->where('status',1)->first();
        $minRecharge                        = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'finance_min_recharge',$this->agent->prefix);
        $maxRecharge                        = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'finance_max_recharge',$this->agent->prefix);
        $digitalFinanceMinRecharge          = CarrierCache::getCarrierConfigure($this->carrier->id, 'digital_finance_min_recharge');
        $digitalFinanceMaxRecharge          = CarrierCache::getCarrierConfigure($this->carrier->id, 'digital_finance_max_recharge');

        if (!is_numeric($amount) || intval($amount)!= $amount) {
            return $this->returnApiJson(config('language')[$this->language]['error34'], 0);
        }

        $param=[
                'amount'        => $amount,
                'orderid'       => 'CZ'.date('YmdHis').mt_rand(1000,9999),
                'user_name'     => $this->agent->user_name,
                'player_id'     => $this->agent->player_id,
                'real_name'     => $this->agent->real_name,
                'transfer_name' => empty($this->agent->real_name) && isset($params['transfer_name'])? $params['transfer_name'] :$this->agent->real_name
        ];

        $playerDepositPayLog                                    = new PlayerDepositPayLog();
        $playerDepositPayLog->player_id                         = $this->agent->player_id;
        $playerDepositPayLog->user_name                         = $this->agent->user_name;
        $playerDepositPayLog->rid                               = $this->agent->rid;
        $playerDepositPayLog->top_id                            = $this->agent->top_id;
        $playerDepositPayLog->parent_id                         = $this->agent->parent_id;
        $playerDepositPayLog->carrier_id                        = $this->agent->carrier_id;
        $playerDepositPayLog->prefix                            = $params['prefix'];
        $playerDepositPayLog->pay_order_number                  = $param['orderid'];
        $playerDepositPayLog->depositimg                        = '';
        $playerDepositPayLog->is_agent                          = 1;

        if(!isset($params['carrier_pay_channel_id']) || $params['carrier_pay_channel_id']==0){
            return $this->returnApiJson(config('language')[$this->language]['error44'], 0);
        }

        $payChannelObj = CarrierPayChannel::select('conf_carrier_third_part_pay.is_anti_complaint','inf_carrier_pay_channel.show_name','def_pay_factory_list.currency','def_pay_channel_list.name','inf_carrier_pay_channel.gift_ratio','inf_carrier_pay_channel.gift_ratio','def_pay_channel_list.has_realname','def_pay_channel_list.min','def_pay_channel_list.max','def_pay_channel_list.enum','def_pay_factory_list.third_wallet_id')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('inf_carrier_pay_channel.id',$params['carrier_pay_channel_id'])
            ->first();

        if(!$payChannelObj){
            return $this->returnApiJson(config('language')[$this->language]['error45'], 0);
        }

        //金额限制
        if(empty($payChannelObj->enum)){
            if($params['amount']>$payChannelObj->max || $params['amount'] <$payChannelObj->min){
                return $this->returnApiJson(config('language')[$this->language]['error240'], 0);
            }
        } else{
            $enum = explode(',',$payChannelObj->enum);
            if(!in_array($params['amount'],$enum)){
                return $this->returnApiJson(config('language')[$this->language]['error240'], 0);
            }
        }

        $param['notifyUrl']                       = $notifyUrl.'/'.$params['carrier_pay_channel_id'];
        $param['returnUrl']                       = 'http://www.baidu.com';   //'http://'.$playerLogin->login_domain;

        $playerDepositPayLog->collection          = $payChannelObj->name.'|'.$payChannelObj->show_name; 
        $playerDepositPayLog->pay                 = '';
        $playerDepositPayLog->carrier_pay_channel = $params['carrier_pay_channel_id'];
        $playerDepositPayLog->currency            = $payChannelObj->currency;

        //查询币种确定是否需要转换
        if($payChannelObj->currency == 'USD'){
            if($params['amount']>$digitalFinanceMaxRecharge){
                return $this->returnApiJson(config('language')[$this->language]['error204'], 0);
            }

            if($params['amount']<$digitalFinanceMinRecharge){
                return $this->returnApiJson(config('language')[$this->language]['error34'], 0);
            }

            $digitalRate                              = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'digital_rate',$this->prefix);
            $playerDepositPayLog->amount              = $params['amount']*10000;
            if($payChannelObj->gift_ratio < 0){
                $playerDepositPayLog->third_fee       = bcdiv(abs($payChannelObj->gift_ratio)*$playerDepositPayLog->amount,$digitalRate*100,0);
            } else{
                $playerDepositPayLog->third_fee           = 0;
            }
            
            $playerDepositPayLog->is_wallet_recharge  = 1;
            $params['amount']                         = bcdiv($params['amount'],$digitalRate,2);
            $param['amount']                          = $params['amount'];
            $playerDepositPayLog->pay                 = $params['amount'].'USDT';

        } else {

            if($params['amount']>$maxRecharge){
                return $this->returnApiJson(config('language')[$this->language]['error204'], 0);
            }

            if($params['amount']<$minRecharge){
                return $this->returnApiJson(config('language')[$this->language]['error34'], 0);
            }

            $playerDepositPayLog->amount              = $params['amount']*10000;
            if($payChannelObj->gift_ratio < 0){
                $playerDepositPayLog->third_fee       = bcdiv(abs($payChannelObj->gift_ratio)*$playerDepositPayLog->amount,200,0);
            } else{
                $playerDepositPayLog->third_fee           = 0;
            }
            $playerDepositPayLog->pay                 = $params['amount'].$payChannelObj->currency;
        }

        $playerDepositPayLog->is_wallet_recharge      = $payChannelObj->third_wallet_id;


        if($payChannelObj->gift_ratio < 0){
            $playerDepositPayLog->arrivedamount    = $playerDepositPayLog->amount - $playerDepositPayLog->third_fee;
        } else{
            $playerDepositPayLog->arrivedamount       = $playerDepositPayLog->amount;
        }


        $playerDepositPayLog->status                  = 0;

        $count                                     = 0;
        $tempOrderId                               = $param['orderid'];
        $param['has_realname']                     = $payChannelObj->has_realname;

        if(!$payChannelObj->is_anti_complaint){
            $param['orderid']                         = $tempOrderId;
            $playerDepositPayLog->pay_order_number    = $param['orderid'];
                    
            $pay                                      = new Pay($params['carrier_pay_channel_id']);

            if(isset($params['bankcode']) && !empty($params['bankcode'])){
                $param['bankCode'] = $params['bankcode'];
            }

            $payData                                  = $pay->sendData($param);

            if(is_array($payData) && isset($payData['ticket'])){
                $playerDepositPayLog->pay_order_channel_trade_number = $payData['ticket'];
            }
                    
            $playerDepositPayLog->save();

        } else {
            do{
                $param['orderid']                         = $tempOrderId;
                $playerDepositPayLog->pay_order_number    = $param['orderid'];    

                $pay                                      = new Pay($params['carrier_pay_channel_id']);

                if(isset($params['bankcode']) && !empty($params['bankcode'])){
                    $param['bankCode'] = $params['bankcode'];
                }

                $payData                                  = $pay->sendData($param);

                if(is_array($payData) && isset($payData['ticket'])){
                    $playerDepositPayLog->pay_order_channel_trade_number = $payData['ticket'];
                }
                    
                $playerDepositPayLog->save();

                $count ++;
                $tempOrderId                              = 'CZ'.date('YmdHis').mt_rand(10000,99999);

            } while(!is_array($payData) && $count<10);
        }

        if(is_array($payData)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $payData);
        } else {
            return $this->returnApiJson($payData, 0);
        }
    }

    public function withdrawApply()
    {
        $input                        = request()->all();
        $amount                       = $input['amount'] ?? 0;
        $playerDigitalAddressId       = $input['player_digital_address_id'] ?? '';
        $minWithdraw                  = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id, 'finance_min_withdraw',$this->agent->prefix);
        $minWithdrawalUsdt            = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id, 'min_withdrawal_usdt',$this->agent->prefix);
        $withdrawDigitalRate          = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id, 'in_r_out_u',$this->agent->prefix);
        $playerDigitalAddress         = PlayerDigitalAddress::where('id',$playerDigitalAddressId)->where('player_id',$this->agent->player_id)->where('status',1)->first();
        

        if (!$playerDigitalAddress) {
            return $this->returnApiJson(config('language')[$this->language]['error191'],0);
        }

        if($this->agent->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'],0);
        }

        if(!isset($input['password']) || empty($input['password'])) {
            return $this->returnApiJson(config('language')[$this->language]['error21'],0);
        }
        
        if(!\Hash::check($input['password'], $this->agent->paypassword)) {
            return $this->returnApiJson(config('language')[$this->language]['error76'],0);
        }

        if (!is_numeric($amount) || intval($amount) != $amount || $amount <1 ) {
            return $this->returnApiJson(config('language')[$this->language]['error77'],0);
        }

        // 如果没有绑定  不用验证谷歌验证
        if ($this->agent->bind_google_status == 1) {
            $oneCode = trim(request("google_code"));
            if (empty($oneCode)) {
                return returnApiJson(config('language')[$this->language]['error430'], 0);
            }

            $ga = new \PHPGangsta_GoogleAuthenticator();
            $checkResult = $ga->verifyCode($this->agent->remember_token, $oneCode, 1); // 2 = 2 * 30秒时钟容差
            if (!$checkResult) {
                return returnApiJson(config('language')[$this->language]['error431'], 0);
            }
        }

        $currency           = CarrierCache::getCurrencyByPrefix($this->agent->prefix);
        $currdigitalpay     = config('main')['digitalpay'][$currency];
        $currdigitalpaykeys = array_keys($currdigitalpay);
        $playerAccount      = PlayerAccount::where('player_id',$this->agent->player_id)->first();

         if(in_array($playerDigitalAddress->type,$currdigitalpaykeys)){
             if(bcdiv($playerAccount->agentbalance,10000,0) < $amount) {
                return $this->returnApiJson(config('language')[$this->language]['error58'],0);
            }
            
            if ($amount < $minWithdraw || intval($amount) != $amount) {
                return $this->returnApiJson(config('language')[$this->language]['error77'],0);
            }
         } else {

            if(bcdiv($playerAccount->agentbalance,10000,0) < $amount) {
                return $this->returnApiJson(config('language')[$this->language]['error58'],0);
            }

            if ($amount < $minWithdrawalUsdt) {
                return $this->returnApiJson(config('language')[$this->language]['error77'],0);
            }
         }
        
        $cacheKey           = "player_" .$this->agent->player_id;
        $redisLock = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            return $this->returnApiJson(config('language')[$this->language]['error20'],0);
        } else {
            try {
                \DB::beginTransaction();

                // 添加记录
                $playerWithdrawM                                 = new PlayerWithdraw();
                $playerWithdrawM->player_id                      = $this->agent->player_id;
                $playerWithdrawM->user_name                      = $this->agent->user_name;
                $playerWithdrawM->carrier_id                     = $this->agent->carrier_id;
                $playerWithdrawM->rid                            = $this->agent->rid;
                $playerWithdrawM->level                          = $this->agent->level;
                $playerWithdrawM->prefix                         = $this->prefix;
                $playerWithdrawM->pay_order_number               = 'TX'.date('YmdHis').mt_rand(1000,9999);  // 平台单号
                $playerWithdrawM->pay_order_channel_trade_number = ''; // 第三方平台单号
                $playerWithdrawM->carrier_pay_channel            = '';
                $playerWithdrawM->amount                         = bcmul($amount,10000,0);

                $withdrawBankcardRatefee                         = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'withdraw_ratefee',$this->agent->prefix);
                if($withdrawBankcardRatefee>0){
                    $playerWithdrawM->withdraw_fee               = bcdiv($playerWithdrawM->amount*$withdrawBankcardRatefee,100);
                } else{
                    $playerWithdrawM->withdraw_fee               = 0;
                }

                $playerWithdrawM->real_amount                    = $playerWithdrawM->amount - $playerWithdrawM->withdraw_fee;
 
                if(in_array($playerDigitalAddress->type,$currdigitalpaykeys)){
                    $playerWithdrawM->collection                     = $currdigitalpay[$playerDigitalAddress->type].'|'.$playerDigitalAddress->address.'|'.$amount;
                    $playerWithdrawM->type                           = $playerDigitalAddress->type;
                    $playerWithdrawM->currency                       = $currency;
                }else {
                    if($playerDigitalAddress->type==1){
                        $playerWithdrawM->collection                     = 'TRC20|'.$playerDigitalAddress->address.'|'.bcdiv($amount,$withdrawDigitalRate,2);
                        $playerWithdrawM->type                           =  1 ;
                        $playerWithdrawM->currency                       = 'USD';

                    } else if($playerDigitalAddress->type==2){
                        $playerWithdrawM->collection                     = 'ERC20|'.$playerDigitalAddress->address.'|'.bcdiv($amount,$withdrawDigitalRate,2);
                        $playerWithdrawM->type                           =  2 ;
                        $playerWithdrawM->currency                       = 'USD';
                    }
                }

                $playerWithdrawM->player_digital_address         = $playerDigitalAddress->address;
                $playerWithdrawM->review_one_user_id             = 0;
                $playerWithdrawM->review_one_time                = 0;
                $playerWithdrawM->review_two_user_id             = 0;
                $playerWithdrawM->review_two_time                = 0;
                $playerWithdrawM->status                         = 0;
                $playerWithdrawM->player_bank_id                 = '';
                $playerWithdrawM->remark                         = '';
                $playerWithdrawM->is_agent                       = 1;
                $playerWithdrawM->save();

                //帐变记录
                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->agent->prefix;
                $playerTransfer->carrier_id                      = $this->agent->carrier_id;
                $playerTransfer->rid                             = $this->agent->rid;
                $playerTransfer->top_id                          = $this->agent->top_id;
                $playerTransfer->parent_id                       = $this->agent->parent_id;
                $playerTransfer->player_id                       = $this->agent->player_id;
                $playerTransfer->is_tester                       = $this->agent->is_tester;
                $playerTransfer->user_name                       = $this->agent->user_name;
                $playerTransfer->level                           = $this->agent->level;
                $playerTransfer->project_id                      = $playerWithdrawM->pay_order_number;
                $playerTransfer->mode                            = 3;
                $playerTransfer->day_m                           = date('Ym');
                $playerTransfer->day                             = date('Ymd');
                $playerTransfer->amount                          = 0;

                $playerTransfer->type                            = 'withdraw_apply';
                $playerTransfer->type_name                       = config('language')['zh']['text141'];
                $playerTransfer->en_type_name                    = config('language')['en']['text141'];
                $playerTransfer->before_balance                  = $playerAccount->balance;
                $playerTransfer->balance                         = $playerAccount->balance;
                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen + bcmul($amount,10000,0);
                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                $playerTransfer->agent_balance                   = $playerAccount->agentbalance - bcmul($amount,10000,0);
                $playerTransfer->save();

                //帐变
                $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                $playerAccount->agentfrozen                      = $playerTransfer->agent_frozen_balance;
                $playerAccount->save();
                
                //申请提现日志
                $playerOperate                                    = new PlayerOperate();
                $playerOperate->carrier_id                        = $this->agent->carrier_id;
                $playerOperate->player_id                         = $this->agent->player_id;
                $playerOperate->user_name                         = $this->agent->user_name;
                $playerOperate->type                              = 1;
                $playerOperate->desc                              = config('language')[$this->language]['text169'].$amount;
                $playerOperate->ip                                = ip2long(real_ip());
                $playerOperate->save();

                \DB::commit();
                Lock::release($redisLock);
                return $this->returnApiJson(config('language')[$this->language]['success1'],1);
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('代理申请提现异常:'.$e->getMessage());   
                return $this->returnApiJson($e->getMessage(),0);
            }
        }
    }

    public function captcha()
    {
        $phrase = new PhraseBuilder;
        // 设置验证码位数
        $code = $phrase->build(4,'0123456789');
        // 生成验证码图片的Builder对象，配置相应属性
        $builder = new CaptchaBuilder($code, $phrase);
        // 设置背景颜色25,25,112
        $builder->setBackgroundColor(204, 224, 222);
        // 设置倾斜角度
        $builder->setMaxAngle(5);
        // 设置验证码后面最大行数
        $builder->setMaxBehindLines(10);
        // 设置验证码前面最大行数
        $builder->setMaxFrontLines(10);
        // 设置验证码颜色
        $builder->setTextColor(149, 117, 142);
        // 可以设置图片宽高及字体
        $builder->build($width = 150, $height = 40, $font = null);
        // 获取验证码的内容
        $phrase = $builder->getPhrase();

        $ip              = real_ip();

        cache()->put(md5($ip),$phrase,now()->addSeconds(60));
        $data['captcha'] = $builder->inline();

       

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function checkPaypassword()
    {
        if(is_null($this->agent->paypassword)){
            $data['status'] = 0;
        } else{
            $data['status'] = 1;
        }

       
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function digitalAddressList()
    {
        $data = PlayerDigitalAddress::select('inf_player_digital_address.id','inf_player_digital_address.address','inf_player_digital_address.type','def_third_wallet.name')->leftJoin('def_third_wallet','def_third_wallet.id','=','inf_player_digital_address.type')->where('inf_player_digital_address.player_id',$this->agent->player_id)->where('inf_player_digital_address.status',1)->where('inf_player_digital_address.win_lose_agent',1)->get();
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function financeExchangeRate()
    {
        $data['digitalRate']               = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'digital_rate',$this->prefix);
        $data['withdrawDigitalRate']       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'in_r_out_u',$this->prefix);
        $data['digitalFinanceMinRecharge'] = CarrierCache::getCarrierConfigure($this->carrier->id,'digital_finance_min_recharge');
        $data['financeMinRecharge']        = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'finance_min_recharge',$this->prefix);
        $data['minWithdrawalUsdt']         = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'min_withdrawal_usdt',$this->prefix);
        $data['financeMinWithdraw']        = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'finance_min_withdraw',$this->prefix);
       

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function digitaltype()
    {
        $carrierPreFixDomain          = CarrierPreFixDomain::where('prefix',$this->agent->prefix)->first();   
        $thirdWallets                 = ThirdWallet::where('currency',$carrierPreFixDomain->currency)->get();
        $disableWithdrawChannelIds    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'disable_withdraw_channel',$this->prefix);
        $disableWithdrawChannelIds    = json_decode($disableWithdrawChannelIds,true);
        $data                = [];

        foreach ($thirdWallets as $key => $value) {
            if(!in_array($value->id,$disableWithdrawChannelIds)){
                $row          = [];
                $row['label'] = $value->name;
                $row['value'] = $value->id;
                $data[]       = $row;
            }
        }

        return returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function init()
    {
        $data['playerDividendsDay']                 = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_dividends_day',$this->prefix);
        $data['prefix']                             = $this->prefix;
        $data['enableStreamingAgency']              = 0;

        //禁止提现通道
        $carrierPreFixDomain          = CarrierPreFixDomain::where('prefix',$this->agent->prefix)->first();   
        $thirdWallets                 = ThirdWallet::where('currency',$carrierPreFixDomain->currency)->pluck('id')->toArray();
        $disableWithdrawChannelIds    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'disable_withdraw_channel',$this->prefix);
        $disableWithdrawChannelIds    = json_decode($disableWithdrawChannelIds,true);
        $agentWithdrawChannelArr      = array_diff($thirdWallets, $disableWithdrawChannelIds);

        $data['agentWithdrawChannel']               = $agentWithdrawChannelArr;
        $data['carrier_id']                         = $this->carrier->id;
        $data['gameImgResourseUrl']                 = config('main')['alicloudstore'];
        $data['h5url']                              = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'h5url',$this->prefix);

        return  returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function venuefee()
    {
        $data                                  = [];
        $row['key']                            = config('language')[$this->language]['text170'];
        $row['value']                          = CarrierCache::getCarrierConfigure($this->carrier->id,'casino_venue_rate');
        $data[]                                = $row;

        $row['key']                            = config('language')[$this->language]['text171'];
        $row['value']                         = CarrierCache::getCarrierConfigure($this->carrier->id,'electronic_venue_rate');
        $data[]                                = $row;
        
        $row['key']                            = config('language')[$this->language]['text172'];
        $row['value']                         = CarrierCache::getCarrierConfigure($this->carrier->id,'esport_venue_rate');
        $data[]                                = $row;

        $row['key']                            = config('language')[$this->language]['text173'];
        $row['value']                         = CarrierCache::getCarrierConfigure($this->carrier->id,'fish_venue_rate');
        $data[]                                = $row;

        $row['key']                            = config('language')[$this->language]['text174'];
        $row['value']                         = CarrierCache::getCarrierConfigure($this->carrier->id,'card_venue_rate');
        $data[]                                = $row;

        $row['key']                            = config('language')[$this->language]['text175'];
        $row['value']                         = CarrierCache::getCarrierConfigure($this->carrier->id,'lottery_venue_rate');
        $data[]                                = $row;

        $row['key']                            = config('language')[$this->language]['text176'];
        $row['value']                         = CarrierCache::getCarrierConfigure($this->carrier->id,'sport_venue_rate');
        $data[]                                = $row;

        return  returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function childbetStat($playerId)
    {
        $input  = request()->all();
        $player = Player::where('player_id',$playerId)->first();

        if(!$player){
            return  returnApiJson(config('language')[$this->language]['error110'], 0);
        }

        if($player->parent_id!=$this->agent->player_id){
            return  returnApiJson(config('language')[$this->language]['error55'], 0);
        }

        $query = PlayerBetFlowMiddle::select('game_category',\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->where('player_id',$playerId);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $data = $query->groupby('game_category')->get();
        
        return  returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function memberGameplat()
    {
        $input = request()->all();
        if(!isset($input['player_id']) || empty($input['player_id'])){
            return  returnApiJson(config('language')[$this->language]['error175'], 0);
        }

        $player = Player::where('player_id',$input['player_id'])->first();

        if(!$player){
            return  returnApiJson(config('language')[$this->language]['error110'], 0);
        }

        if($player->parent_id!=$this->agent->player_id){
            return  returnApiJson(config('language')[$this->language]['error55'], 0);
        }

        $mainGamePlat    = MainGamePlat::all();
        $mainGamePlatArr   = [];
        $mainGamePlatEnArr = [];
        foreach ($mainGamePlat as $key => $value) {
            $mainGamePlatArr[$value->main_game_plat_code]   = $value->alias;
            $mainGamePlatEnArr[$value->main_game_plat_code] = $value->en_alias;
        }

        $data = PlayerGameAccount::select('main_game_plat_code','balance','account_id')->where('player_id',$input['player_id'])->get();

        foreach ($data as $k => $v) {
           if($v->main_game_plat_code=='jp6'){
                $v->alias    = config('language')['zh']['text181'];
                $v->en_alias = config('language')['zh']['text181'];
            } else if($v->main_game_plat_code=='jp5'){
                $v->alias    = config('language')['zh']['text182'];
                $v->en_alias = config('language')['zh']['text182'];
            } else if($v->main_game_plat_code=='jp7'){
                $v->alias    = config('language')['zh']['text183'];
                $v->en_alias = config('language')['zh']['text183'];
            } else if($v->main_game_plat_code=='jp8'){
                $v->alias    = config('language')['zh']['text185'];
                $v->en_alias = config('language')['zh']['text185'];
            } else if($v->main_game_plat_code=='jp9'){
                $v->alias    = config('language')['zh']['text186'];
                $v->en_alias = config('language')['zh']['text186'];
            } else{
                $v->alias     = $mainGamePlatArr[$v->main_game_plat_code];
                $v->en_alias  = $mainGamePlatEnArr[$v->main_game_plat_code];
            }
        }

        return  returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function gameBalance()
    {
        $input = request()->all();

        if(!isset($input['account_id']) ||empty($input['account_id'])){
            return  returnApiJson(config('language')[$this->language]['error447'], 0);
        }

        $existPlayerGameAccount = PlayerGameAccount::where('account_id',$input['account_id'])->first();
        if(!$existPlayerGameAccount){
            return  returnApiJson(config('language')[$this->language]['error339'], 0);
        }

        $player = Player::where('player_id',$existPlayerGameAccount->player_id)->first();
        if($player->parent_id!=$this->agent->player_id){
            return  returnApiJson(config('language')[$this->language]['error448'], 0);
        }

        request()->offsetSet('mainGamePlatCode',$existPlayerGameAccount->main_game_plat_code);
        request()->offsetSet('accountUserName',$existPlayerGameAccount->account_user_name);
        request()->offsetSet('password',$existPlayerGameAccount->password);

        $game    = new Game($this->carrier,$existPlayerGameAccount->main_game_plat_code);
        $balance = $game->getBalance();

        if(is_array($balance)){
            if($balance['success']){
                if(gettype($balance['data']['balance'])=='string'){
                    $balance['data']['balance'] = floatval($balance['data']['balance']);
                }
                return returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>number_format($balance['data']['balance'],2)]);
            } 

            return returnApiJson(config('language')[$this->language]['error449'], 0);
        } else{
            return returnApiJson(config('language')[$this->language]['error449'], 0);
        }
    }

    public function setGuaranteed()
    {
        $input                     = request()->all();
        $guaranteedLevelDifference = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'guaranteed_level_difference',$this->agent->prefix);
        $limitHighestGuaranteed    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'limit_highest_guaranteed',$this->agent->prefix);

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson(config('language')[$this->language]['error357'], 0);
        }

        if(!isset($input['guaranteed']) || !is_numeric($input['guaranteed']) || $input['guaranteed']<0 ){
            return $this->returnApiJson(config('language')[$this->language]['error358'], 0);
        }

        $selfPlayerSetting = PlayerCache::getPlayerSetting($this->agent->player_id);

        if($input['guaranteed'] >= $limitHighestGuaranteed){
            return $this->returnApiJson(config('language')[$this->language]['error359'], 0);
        }

        if($selfPlayerSetting->guaranteed < $input['guaranteed']){
            return $this->returnApiJson(config('language')[$this->language]['error360'], 0);
        }

        if($selfPlayerSetting->guaranteed < $input['guaranteed'] + $guaranteedLevelDifference){
            if($selfPlayerSetting->guaranteed == $input['guaranteed']){
                return $this->returnApiJson(config('language')[$this->language]['error361'], 0);
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error362'].$guaranteedLevelDifference, 0);
            }
        }

        $playerSetting     = PlayerCache::getPlayerSetting($input['player_id']);
        if($playerSetting && $playerSetting->parent_id == $this->agent->player_id){
            if($input['guaranteed']<$playerSetting->guaranteed){
                return $this->returnApiJson(config('language')[$this->language]['error363'], 0);
            }

            $playerSetting->guaranteed = $input['guaranteed'];
            $playerSetting->save();

            PlayerCache::forgetPlayerSetting($input['player_id']);

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } else{
            return $this->returnApiJson(config('language')[$this->language]['error450'], 0);
        }
    }

    public function setEarning()
    {
        $input = request()->all();

        $dividendLevelDifference = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'dividend_level_difference',$this->agent->prefix);
        $limitHighestDividend    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'limit_highest_dividend',$this->agent->prefix);
        $dividendEnumerate       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'dividend_enumerate',$this->agent->prefix);

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson(config('language')[$this->language]['error357'], 0);
        }

        if(!isset($input['earnings']) || !is_numeric($input['earnings']) || $input['earnings'] <= 0 ){
            return $this->returnApiJson(config('language')[$this->language]['error364'], 0);
        }

        if(!empty($input['dividendEnumerate'])){
            $dividendEnumerateArr = explode(',',$dividendEnumerate);
            if(!in_array($input['dividendEnumerate'],$dividendEnumerateArr)){
                return $this->returnApiJson(config('language')[$this->language]['error451'], 0);
            }
        }

        $selfPlayerSetting = PlayerCache::getPlayerSetting($this->agent->player_id);

        if($input['earnings'] >= $limitHighestDividend){
            return $this->returnApiJson(config('language')[$this->language]['error452'], 0);
        }

        if($selfPlayerSetting->earnings < $input['earnings']){
            return $this->returnApiJson(config('language')[$this->language]['error367'], 0);
        }

        if($selfPlayerSetting->earnings < $input['earnings'] + $dividendLevelDifference){
            if($selfPlayerSetting->earnings == $input['earnings']){
                return $this->returnApiJson(config('language')[$this->language]['error368'], 0);
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error369'].$dividendLevelDifference, 0);
            }
        }

        $playerSetting = PlayerCache::getPlayerSetting($input['player_id']);
        if($playerSetting && $playerSetting->parent_id == $this->agent->player_id){
            if($playerSetting->earnings >0){
                return $this->returnApiJson(config('language')[$this->language]['error370'], 0);
            }

            $playerSetting->earnings = $input['earnings'];
            $playerSetting->save();

            PlayerDigitalAddress::where('player_id',$input['player_id'])->update(['win_lose_agent'=>1]);
            ReportPlayerStatDay::where('player_id',$input['player_id'])->update(['win_lose_agent'=>1]);
            Player::where('player_id',$input['player_id'])->update(['win_lose_agent'=>1]);

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        }else{
            return $this->returnApiJson(config('language')[$this->language]['error450'], 0);
        }
    }

    public function myCarte()
    {
        $playerSetting = PlayerCache::getPlayerSetting($this->agent->player_id);
        $data =[
            'wechat' => $this->agent->wechat,
            'qq_account' => $this->agent->qq_account,
            'othercontact' => $this->agent->othercontact,
            'earnings'     => $playerSetting->earnings
        ];
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function mycarteSave()
    {
        $input = request()->all();
        if(array_key_exists('wechat', $input) && !is_null($input['wechat'])){
            $this->agent->wechat = $input['wechat'];
        }

        if(array_key_exists('qq_account', $input) && !is_null($input['qq_account'])){
            $this->agent->qq_account = $input['qq_account'];
        }

        if(array_key_exists('othercontact', $input) && !is_null($input['othercontact'])){
            $this->agent->othercontact = $input['othercontact'];
        }

        $this->agent->save();
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function parentCarte()
    {
        $data = Player::select('wechat','qq_account','othercontact')->where('player_id',$this->agent->parent_id)->first()->toArray();
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function createAgent()
    {
        $input                   = request()->all();
        $dividendLevelDifference = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'dividend_level_difference',$this->agent->prefix);
        $limitHighestDividend    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'limit_highest_dividend',$this->agent->prefix);
        $dividendEnumerate       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'dividend_enumerate',$this->agent->prefix);

        if(!isset($input['user_name']) || empty($input['user_name'])){
            return $this->returnApiJson(config('language')[$this->language]['error453'], 0);
        }

        if ( !Validator::isUsr($input['user_name'], [ 'min' => 5, 'max' => 36, 'checkUpper' => false ]) ) {
                return $this->returnApiJson(config('language')[$this->language]['error439'],0);
        }

        $existPlayer = Player::where('user_name',$input['user_name'].'_'.$this->prefix)->first();
        if($existPlayer){
            return $this->returnApiJson(config('language')[$this->language]['error454'], 0);
        }

        if(!isset($input['password']) || empty($input['password'])){
            return $this->returnApiJson(config('language')[$this->language]['error455'], 0);
        }

        if(!isset($input['earnings']) || !is_numeric($input['earnings']) || $input['earnings']<=0){
            return $this->returnApiJson(config('language')[$this->language]['error456'], 0);
        }

        if(!empty($dividendEnumerate)){
            $dividendEnumerateArr = explode(',', $dividendEnumerate);
            if(!in_array($input['earnings'], $dividendEnumerateArr)){
                return $this->returnApiJson(config('language')[$this->language]['error457'], 0);
            }
        }   

        if($input['earnings'] >= $limitHighestDividend){
            return $this->returnApiJson(config('language')[$this->language]['error452'], 0);
        }

        $playerSetting = PlayerCache::getPlayerSetting($this->agent->player_id);

        if($playerSetting->earnings < $input['earnings']){
            return $this->returnApiJson(config('language')[$this->language]['error458'], 0);
        } else{
            if($playerSetting->earnings == $input['earnings']){
                return $this->returnApiJson(config('language')[$this->language]['error368'], 0);
            }

            if($playerSetting->earnings < $input['earnings'] + $dividendLevelDifference){
                return $this->returnApiJson(config('language')[$this->language]['error369'].$dividendLevelDifference, 0);
            }
        }

        $carrierPlayerLevel          = CarrierPlayerGrade::where('carrier_id',$this->carrier->id)->where('prefix',$this->agent->prefix)->where('is_default',1)->first();

        try {
            \DB::beginTransaction();

            $domain                            = request()->header('Origin');
            $domain                            = str_replace("https://", "", trim($domain));
            $domain                            = str_replace("http://", "", trim($domain));
            $player                            = new Player();
            $player->top_id                    = $this->agent->top_id;
            $player->parent_id                 = $this->agent->player_id;
            $player->register_domain           = $domain;
            $player->prefix                    = $this->prefix;
            $player->is_tester                 = $this->agent->is_tester;
            $player->mobile                    = '';
            $player->user_name                 = $input['user_name'].'_'.$this->prefix;
            $player->real_name                 = '';
            $player->password                  = bcrypt($input['password']);
            $player->paypassword               = null;
            $player->carrier_id                = $this->agent->carrier_id;
            $player->player_level_id           = $carrierPlayerLevel->id;
            $player->register_ip               = real_ip();
            $player->level                     = $this->agent->level+1;
            $player->type                      = 2;
            $player->win_lose_agent            = 1;
            $player->nick_name                 = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'default_nick_name',$this->agent->prefix);
            $player->avatar                    = CarrierCache::getCarrierConfigure($this->agent->carrier_id,'default_avatar');
            $player->save();

            if(is_null($player->rid)){
                $player->rid     = $this->agent->rid.'|'.$player->player_id;
                $player->save();
            }

            $playerSetting                              = new PlayerSetting();
            $playerSetting->player_id                   = $player->player_id;
            $playerSetting->carrier_id                  = $player->carrier_id;
            $playerSetting->top_id                      = $player->top_id;
            $playerSetting->parent_id                   = $player->parent_id;
            $playerSetting->rid                         = $player->rid;
            $playerSetting->is_tester                   = $player->is_tester;
            $playerSetting->user_name                   = $player->user_name;
            $playerSetting->earnings                    = $input['earnings'];
            $playerSetting->guaranteed                  = 0;
            $playerSetting->level                       = $player->level;
            $playerSetting->prefix                      = $player->prefix;
            $playerSetting->lottoadds                   = CarrierCache::getCarrierConfigure($this->agent->carrier_id, 'default_lottery_odds');
            $playerSetting->save();

            $selfInviteCode                              = new PlayerInviteCode();
            $selfInviteCode->carrier_id                  = $player->carrier_id;
            $selfInviteCode->player_id                   = $player->player_id;
            $selfInviteCode->rid                         = $player->rid;
            $selfInviteCode->username                    = $player->user_name;
            $selfInviteCode->type                        = 2;
            $selfInviteCode->lottoadds                   = $playerSetting->lottoadds;
            $selfInviteCode->is_tester                   = $player->is_tester;
            $selfInviteCode->code                        = $player->extend_id;
            $selfInviteCode->prefix                      = $player->prefix;
            $selfInviteCode->save();

            \DB::commit();
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } catch (\Exception $e) {
            \DB::rollback();
            Clog::recordabnormal('代理后台开户异常:'.$e->getMessage());   
            return returnApiJson(config('language')[$this->language]['error441'].$e->getMessage(), 0);
        }
    }

    public function indexstat()
    {
        $data                      = [];
        $operatingExpenses                = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses                = bcdiv(100-$operatingExpenses,100,2);
        $showtime                  = strtotime(date('Y-m-d').'00:30:00');
        $data['agentNum']          = Player::where('rid','like',$this->agent->rid.'|%')->where('win_lose_agent',1)->count();
        $data['memberNum']         = Player::where('rid','like',$this->agent->rid.'|%')->where('win_lose_agent',0)->count();
        $data['registerMemberNum'] = Player::where('rid','like',$this->agent->rid.'|%')->where('win_lose_agent',0)->where('created_at','>=',date('Y-m-d').' 00:00:00')->count();
        $data['memberOnlineNum']   = Player::where('rid','like',$this->agent->rid.'|%')->where('win_lose_agent',0)->where('is_online',1)->count();
        
        if(time() < $showtime){
            $data['directly_under_recharge_amount'] = 0;
            $data['directly_under_withdraw_amount'] = 0;
            $data['directly_under_stock']           = 0;
            $data['directly_under_change_stock']    = 0;
            $data['directly_under_commission']      = 0;
            $data['team_recharge_amount']           = 0;
            $data['team_withdraw_amount']           = 0;
            $data['team_stock']                     = 0;
            $data['team_change_stock']              = 0;
            $data['team_commission']                = 0;
        } else{
            $directlyUnderPlayerIds                = Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',0)->pluck('player_id')->toArray();
            $selfSetting                           = PlayerSetting::where('player_id',$this->agent->player_id)->first();
    
            //净输赢
            $directlyUnderReportPlayerStatDayStat  = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as page_recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'))
            ->whereIn('player_id',$directlyUnderPlayerIds)
            ->where('day',date('Ymd'))
            ->first();

            $directlyUnderCalculate                 = DevidendMode2::singleStockCalculateByday($this->agent,0);
            $data['directly_under_recharge_amount'] = bcdiv($directlyUnderReportPlayerStatDayStat->page_recharge_amount*$operatingExpenses,10000,2);
            $data['directly_under_withdraw_amount'] = bcdiv($directlyUnderReportPlayerStatDayStat->withdraw_amount,10000,2);
            $data['directly_under_stock']           = bcdiv($directlyUnderCalculate['stock'],10000,2);
            $data['directly_under_change_stock']    = bcdiv($directlyUnderCalculate['change_stock'],10000,2);
            $data['directly_under_commission']      = bcdiv(($directlyUnderReportPlayerStatDayStat->page_recharge_amount*$operatingExpenses - $directlyUnderReportPlayerStatDayStat->withdraw_amount - $directlyUnderCalculate['change_stock'])*$selfSetting->earnings,1000000,2);

            //开始计算团队数据
            $teamAgentPlayers                       = Player::where('parent_id',$this->agent->player_id)->where('win_lose_agent',1)->get();
            $teamAgentPlayerIds                     = [];

            foreach ($teamAgentPlayers as $key => $value) {
                $teamAgentPlayerIds[] = $value->player_id;
            }

            $playerSettings                    = PlayerSetting::whereIn('player_id',$teamAgentPlayerIds)->get();
            $playerSettingsArr                 = [];

            foreach ($playerSettings as $key => $value) {
                $playerSettingsArr[$value->player_id] = $value->earnings;
            }

            $data['team_recharge_amount']           = 0;
            $data['team_withdraw_amount']           = 0;
            $data['team_stock']                     = 0;
            $data['team_change_stock']              = 0;
            $data['team_commission']                = 0;
        
            foreach ($teamAgentPlayers as $key => $value) {
                $teamAgentMemberPlayerIds                = Player::where('rid','like',$value->rid.'|%')->where('win_lose_agent',0)->pluck('player_id')->toArray();
                //今日数据
               //$reportPlayerStatDayStat                 = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as page_recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(gift) as gift'))->whereIn('player_id',$teamAgentMemberPlayerIds)->where('day',date('Ymd'))->first();


                $reportPlayerStatDayStat                 = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as page_recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(self_stock) as stock'),\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$teamAgentMemberPlayerIds)->where('day',date('Ymd'))->first();

                //昨日库存
                //$yesterdayReportPlayerStatDayStat      = ReportPlayerStatDay::select(\DB::raw('sum(stock) as stock'))->whereIn('player_id',$teamAgentMemberPlayerIds)->where('day',date('Ymd',strtotime('-1 day')))->first();
                $yesterdayReportPlayerStatDayStat        = ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as stock'),\DB::raw('sum(change_self_stock) as change_self_stock'))->whereIn('player_id',$teamAgentMemberPlayerIds)->where('day',date('Ymd',strtotime('-1 day')))->first();

                $yesterdayStock                          = 0;
                if(!is_null($yesterdayReportPlayerStatDayStat->stock)){
                    $yesterdayStock = $yesterdayReportPlayerStatDayStat->stock;
                }

                //计算分红
                $teamRechargeAmount            = $reportPlayerStatDayStat->page_recharge_amount*$operatingExpenses;
                $teamWithdrawAmount            = $reportPlayerStatDayStat->withdraw_amount;
               // $teamWinOrLossAmount           = $reportPlayerStatDayStat->win_amount + $reportPlayerStatDayStat->lottery_winorloss;
                //$teamGift                      = $reportPlayerStatDayStat->gift;
                
                $data['team_recharge_amount'] += $teamRechargeAmount;
                $data['team_withdraw_amount'] += $teamWithdrawAmount;

               //计算库存
               //$stock                         = $teamRechargeAmount - $teamWithdrawAmount + $teamGift + $teamWinOrLossAmount;
               //$changeStock                   = $stock - $yesterdayStock;
                $stock                          = $reportPlayerStatDayStat->stock;
                $changeStock                    = $reportPlayerStatDayStat->change_self_stock;

               $data['team_stock']            += $stock;
               $data['team_change_stock']     += $changeStock;
               $data['team_commission']       += bcdiv(($teamRechargeAmount - $teamWithdrawAmount - $changeStock)*($selfSetting->earnings-$playerSettingsArr[$value->player_id]),100,0);
            }

            $data['team_recharge_amount']           = bcdiv($data['team_recharge_amount'],10000,2);
            $data['team_withdraw_amount']           = bcdiv($data['team_withdraw_amount'],10000,2);
            $data['team_stock']                     = bcdiv($data['team_stock'],10000,2);
            $data['team_change_stock']              = bcdiv($data['team_change_stock'],10000,2);
            $data['team_commission']                = bcdiv($data['team_commission'],10000,2);
        }
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function memberList()
    {
        $params               = request()->all();
        $currentPage          = isset($params['page_index']) ? intval($params['page_index']) : 1;
        $pageSize             = isset($params['page_size'])  ? intval($params['page_size'])  : config('main')['page_size'];
        $offset               = ($currentPage - 1) * $pageSize;
        $operatingExpenses    = CarrierCache::getCarrierMultipleConfigure($this->agent->carrier_id,'operating_expenses',$this->agent->prefix);
        $operatingExpenses    = bcdiv(100-$operatingExpenses,100,2);

        $query = Player::select('is_online','player_id','extend_id','user_name','created_at')->where('carrier_id',$this->carrier->id)->where('parent_id',$this->agent->player_id)->where('win_lose_agent',0);

        if(isset($params['user_name']) && !empty($params['user_name'])){
            $query->where('user_name','like','%'.$params['user_name'].'%');
        }

        if(isset($params['player_id']) && !empty($params['player_id'])){
            if(strlen($params['player_id'])==8){
                $query->where('player_id',$params['player_id']);
            }else{
                $query->where('extend_id',$params['player_id']);  
            }
        }

        $total          = $query->get()->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        $playerIds = [];
        foreach ($items as $key => $value) {
            $playerIds[] = $value->player_id;
        }

        $query1 = PlayerDepositPayLog::select('player_id',\DB::raw('sum(arrivedamount) as amount'))->whereIn('player_id',$playerIds)->where('status',1);
        $query2 = PlayerWithdraw::select('player_id',\DB::raw('sum(amount) as amount'))->whereIn('player_id',$playerIds)->whereIn('status',[1,2]);
        
        if(isset($params['startDate']) && strtotime($params['startDate'])){   
            $query1->where('day','>=',date('Ymd',strtotime($params['startDate'])));
            $query2->where('created_at','>=',$params['startDate'].' 00:00:00');
        }

        if(isset($params['endDate']) && strtotime($params['endDate'])) {
            $query1->where('day','<=',date('Ymd',strtotime($params['endDate'])));
            $query2->where('created_at','<=',$params['endDate'].' 23:59:59');
        }

        $playerDepositPayLogs = $query1->groupBy('player_id')->get();
        $playerWithdraws      = $query2->groupBy('player_id')->get();
        $playerAccounts       = PlayerAccount::select('player_id','balance','agentbalance','agentfrozen')->whereIn('player_id',$playerIds)->groupBy('player_id')->get();
        

        $playerDepositPayLogArr    = [];
        $playerWithdrawArr         = [];
        $playerBalanceArr          = [];
        $playerAgentBalanceArr     = [];
       
        foreach ($playerDepositPayLogs as $key => $value) {
            $playerDepositPayLogArr[$value->player_id]    = $value->amount;
        }

        foreach ($playerWithdraws as $key => $value) {
            $playerWithdrawArr[$value->player_id] = $value->amount;
        }

        foreach ($playerAccounts as $key => $value) {
            $playerBalanceArr[$value->player_id] = $value->balance;
            $playerAgentBalanceArr[$value->player_id] = $value->agentbalance + $value->agentfrozen;
        }

        foreach ($items as $k => &$v) {
            $v->balance            = isset($playerBalanceArr[$v->player_id]) ? $playerBalanceArr[$v->player_id] :0;
            $v->agentbalance       = isset($playerAgentBalanceArr[$v->player_id]) ? $playerAgentBalanceArr[$v->player_id] :0;
            $v->rechargeAmount     = isset($playerDepositPayLogArr[$v->player_id]) ? $playerDepositPayLogArr[$v->player_id]*$operatingExpenses :0;
            $v->withdrawAmout      = isset($playerWithdrawArr[$v->player_id]) ? $playerWithdrawArr[$v->player_id] :0;
            $v->revenue            = $v->rechargeAmount - $v->withdrawAmout;
            $v->user_name          =  rtrim($v->user_name,'_'.$this->prefix);
        }

        

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function settlementStart()
    {
        $playerDividendsDay    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_dividends_day',$this->prefix);
        $data                  = [];
        if($playerDividendsDay==3 ){
            $playerDividendsStartDay = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_dividends_start_day',$this->prefix);

            $existReportPlayerEarnings = ReportPlayerEarnings::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->orderBy('id','desc')->first();
            if($existReportPlayerEarnings){
                $data['startDate'] = date('Y-m-d',strtotime($existReportPlayerEarnings->created_at));
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function agentVoucherList()
    {
        $res = PlayerHoldGiftCode::agentVoucherList($this->agent);
        if(is_array($res)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function sendAgentvoucher()
    {
        $input = request()->all();
        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson(config('language')[$this->language]['error459'], 0);
        }

        $player = Player::where('player_id',$input['player_id'])->first();

        if(!$player){
            return $this->returnApiJson(config('language')[$this->language]['error110'], 0);
        }

        $ridIds = explode('|', $player->rid);
        if(!in_array($this->agent->player_id,$ridIds) || $this->agent->player_id == $player->player_id || strlen($player->rid) < strlen($this->agent->rid)){
            return $this->returnApiJson(config('language')[$this->language]['error460'], 0);
        }

        if(!isset($input['number']) || $input['number']<0 || intval($input['number']) != $input['number'] ){
            return $this->returnApiJson(config('language')[$this->language]['error461'], 0);
        }


        if(!isset($input['money']) || !is_numeric($input['money']) || $input['money']<0 || $input['money']!=intval($input['money'])){
            return $this->returnApiJson(config('language')[$this->language]['error462'], 0);
        }

        $playerHoldGiftCode = PlayerHoldGiftCode::where('player_id',$this->agent->player_id)->where('status',0)->where('money',$input['money'])->limit($input['number'])->get();
        if(count($playerHoldGiftCode) != $input['number']){
            return $this->returnApiJson(config('language')[$this->language]['error463'], 0);
        } else{
            $holdGiftCodeIds = [];
            $holdGiftCodes   = [];
            foreach ($playerHoldGiftCode as $key => $value) {
                $holdGiftCodeIds[] = $value->id;
                $holdGiftCodes[]   = $value->gift_code;
            }

            PlayerHoldGiftCode::whereIn('id',$holdGiftCodeIds)->update(['player_id'=>$input['player_id']]);
            CarrierActivityGiftCode::whereIn('gift_code',$holdGiftCodes)->update(['player_id'=>$input['player_id']]);
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        }
    }

    public function websiteInfo()
    {
        $data              = [];
        $marketingContact  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'marketing_contact',$this->agent->prefix);
        $data['notice']    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'carrier_agent_marquee_notice',$this->agent->prefix);
        $data['contact']   = json_decode($marketingContact,true);
        $data['siteTitle'] = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'site_title',$this->agent->prefix);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }
}