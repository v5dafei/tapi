<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\CarrierActivity;
use App\Models\Player;
use App\Models\PlayerBreakThrough;
use App\Lib\Cache\Lock;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\CarrierPreFixDomain;
use App\Lib\Cache\CarrierCache;
use App\Lib\Clog;

class BreakThroughCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'breakThrough';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'breakThrough';

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
        $weekTime       = getWeekStartEnd(date('Y-m-d'),time()-86400);
        $weekStarDate   = $weekTime[2];
        $weekEndTDate   = $weekTime[3];
        $carrierPreFixDomains             = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $k => $v) {
                $carrierActivities = CarrierActivity::where('prefix',$v->prefix)->where('act_type_id',4)->where('status',1)->where('startTime','<=',time())->where('endTime','>=',time()-86400)->get();
                if(count($carrierActivities)){
                    foreach ($carrierActivities as $k => $v) {
                        $rebateFinancialBonusesStepRate = json_decode($v->rebate_financial_bonuses_step_rate_json,true);
                        $applyRuleString                = json_decode($v->apply_rule_string,true);
                        $enableReportPlayerStatDays     = null;

                        switch ($applyRuleString[0]) {
                            case 'todaybetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select('lottery_available_bets','available_bets','carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day',date('Ymd',time()-86400))->where(\DB::raw('lottery_available_bets + available_bets'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,0);
                                break;
                            case 'todaylottbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select('lottery_available_bets as available_bets','carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day',date('Ymd',time()-86400))->where('lottery_available_bets','>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,6);
                                break;
                            case 'todaycasinobetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select('casino_available_bets as available_bets','carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day',date('Ymd',time()-86400))->where('casino_available_bets','>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,1);
                                break;
                            case 'todayelectronicbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select('electronic_available_bets as available_bets','carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day',date('Ymd',time()-86400))->where('electronic_available_bets','>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,2);
                                break;
                            case 'todayesportbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select('esport_available_bets as available_bets','carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day',date('Ymd',time()-86400))->where('esport_available_bets','>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,3);
                                break;
                            case 'todayfishbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select('fish_available_bets as available_bets','carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day',date('Ymd',time()-86400))->where('fish_available_bets','>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,7);
                                break;
                            case 'todaycardbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select('card_available_bets as available_bets','carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day',date('Ymd',time()-86400))->where('card_available_bets','>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,4);
                                break;
                            case 'todaysportbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select('sport_available_bets as available_bets','carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day',date('Ymd',time()-86400))->where('sport_available_bets','>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,5);
                                break;
                            case 'weekbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select(\DB::raw('sum(lottery_available_bets) as lottery_available_bets'),\DB::raw('sum(available_bets) as available_bets'),'carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('carrier_id',$value->id)->where('prefix',$v->prefix)->where('is_tester',0)->where('day','>=',$weekStarDate)->where('day','<=',$weekEndTDate)->groupBy('player_id')->having(\DB::raw('sum(lottery_available_bets) + sum(available_bets)'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,0);
                                break;
                            case 'weeklottbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select(\DB::raw('sum(lottery_available_bets) as available_bets'),'carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day','>=',$weekStarDate)->where('day','<=',$weekEndTDate)->groupBy('player_id')->having(\DB::raw('sum(lottery_available_bets)'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,6);
                                break;
                            case 'weekcasinobetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select(\DB::raw('sum(casino_available_bets) as available_bets'),'carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day','>=',$weekStarDate)->where('day','<=',$weekEndTDate)->groupBy('player_id')->having(\DB::raw('sum(casino_available_bets)'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,1);
                                break;
                            case 'weekelectronicbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select(\DB::raw('sum(electronic_available_bets) as available_bets'),'carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day','>=',$weekStarDate)->where('day','<=',$weekEndTDate)->groupBy('player_id')->having(\DB::raw('sum(electronic_available_bets)'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,2);
                                break;
                            case 'weekesportbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select(\DB::raw('sum(esport_available_bets) as available_bets'),'carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day','>=',$weekStarDate)->where('day','<=',$weekEndTDate)->groupBy('player_id')->having(\DB::raw('sum(esport_available_bets)'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,3);
                                break;
                            case 'weekfishbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select(\DB::raw('sum(fish_available_bets) as available_bets'),'carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day','>=',$weekStarDate)->where('day','<=',$weekEndTDate)->groupBy('player_id')->having(\DB::raw('sum(fish_available_bets)'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,7);
                                break;
                            case 'weekcardbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select(\DB::raw('sum(card_available_bets) as available_bets'),'carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day','>=',$weekStarDate)->where('day','<=',$weekEndTDate)->groupBy('player_id')->having(\DB::raw('sum(card_available_bets)'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,4);
                                break;
                            case 'weeksportbetflow':
                                $enableReportPlayerStatDays = ReportPlayerStatDay::select(\DB::raw('sum(sport_available_bets) as available_bets'),'carrier_id','top_id','parent_id','rid','player_id','user_name','prefix')->where('prefix',$v->prefix)->where('is_tester',0)->where('day','>=',$weekStarDate)->where('day','<=',$weekEndTDate)->groupBy('player_id')->having(\DB::raw('sum(sport_available_bets)'),'>=',$applyRuleString[2]*10000)->get();
                                $this->sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$v,5);
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
    }

    private function sendBreakThrough($enableReportPlayerStatDays,$rebateFinancialBonusesStepRate,$carrierActivity,$type)
    {       
        $data = [];
        foreach ($enableReportPlayerStatDays as $key => $value) {
            $player                 = Player::where('player_id',$value->player_id)->first();
            $language               = CarrierCache::getLanguageByPrefix($value->prefix);
            

            $cacheKey = "player_" .$player->player_id;
            $redisLock = Lock::addLock($cacheKey,10);

            if (!$redisLock) {
                return $this->returnApiJson(config('language')[$language]['error20'], 0);
            } else {
                try{
                    \DB::beginTransaction();
                    if(isset($value->lottery_available_bets)){
                        $value->available_bets = $value->available_bets + $value->lottery_available_bets;
                    }

                    $flag = array();
                    foreach($rebateFinancialBonusesStepRate as $v) {
                        $flag[] = $v['todaybetflow'];
                    }

                    $playerBreakThrough     = new PlayerBreakThrough();

                    array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       

                    foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                        if($value->available_bets >= $v['todaybetflow']*10000){
                            $playerBreakThrough->amount       = $v['give']*10000;
                            $playerBreakThrough->limit_amount = $playerBreakThrough->amount*$v['water'];
                        }
                    }

                    $playerAccount                            = PlayerAccount::where('player_id',$value->player_id)->lockForUpdate()->first();

                    $playerBreakThrough->carrier_id           = $value->carrier_id;
                    $playerBreakThrough->prefix               = $value->prefix;
                    $playerBreakThrough->act_id               = $carrierActivity->id;
                    $playerBreakThrough->top_id               = $value->top_id;
                    $playerBreakThrough->parent_id            = $value->parent_id;
                    $playerBreakThrough->rid                  = $value->rid;
                    $playerBreakThrough->player_id            = $value->player_id;
                    $playerBreakThrough->user_name            = $value->user_name;
                    $playerBreakThrough->game_category        = $type;
                    $playerBreakThrough->day                  = date('Ymd',time()-86400);
                    $playerBreakThrough->save();

                    $playerTransfer                                  = new PlayerTransfer();
                    $playerTransfer->prefix                          = $value->prefix;
                    $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                    $playerTransfer->rid                             = $playerAccount->rid;
                    $playerTransfer->top_id                          = $playerAccount->top_id;
                    $playerTransfer->parent_id                       = $playerAccount->parent_id;
                    $playerTransfer->player_id                       = $playerAccount->player_id;
                    $playerTransfer->is_tester                       = $playerAccount->is_tester;
                    $playerTransfer->level                           = $playerAccount->level;
                    $playerTransfer->user_name                       = $playerAccount->user_name;
                    $playerTransfer->mode                            = 1;
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $playerBreakThrough->amount;
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                    $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                    $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                    $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                    $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                    $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                    $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;
                    $playerWithdrawFlowLimit->limit_amount           = $playerBreakThrough->limit_amount;
                    $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                    $playerWithdrawFlowLimit->is_finished            = 0;
                    $playerWithdrawFlowLimit->operator_id            = 0;

                    switch ($type) {
                        case 0:
                            $playerTransfer->type                            = 'break_through_gift';
                            $playerTransfer->type_name                       = '闯关礼金';
                            $playerWithdrawFlowLimit->limit_type             = 29;
                            break;
                        case 1:
                            $playerTransfer->type                            = 'video_break_through_gift';
                            $playerTransfer->type_name                       = '视讯闯关礼金';
                            $playerWithdrawFlowLimit->limit_type             = 30;
                            break;
                        case 2:
                            $playerTransfer->type                            = 'electronic_break_through_gift';
                            $playerTransfer->type_name                       = '电子闯关礼金';
                            $playerWithdrawFlowLimit->limit_type             = 31;
                            break;
                        case 3:
                            $playerTransfer->type                            = 'esport_break_through_gift';
                            $playerTransfer->type_name                       = '电竞闯关礼金';
                            $playerWithdrawFlowLimit->limit_type             = 32;
                            break;
                        case 4:
                            $playerTransfer->type                            = 'card_break_through_gift';
                            $playerTransfer->type_name                       = '棋牌闯关礼金';
                            $playerWithdrawFlowLimit->limit_type             = 33;
                            break;
                        case 5:
                            $playerTransfer->type                            = 'sport_break_through_gift';
                            $playerTransfer->type_name                       = '体育闯关礼金';
                            $playerWithdrawFlowLimit->limit_type             = 34;
                            break;
                        case 6:
                            $playerTransfer->type                            = 'lottery_break_through_gift';
                            $playerTransfer->type_name                       = '彩票闯关礼金';
                            $playerWithdrawFlowLimit->limit_type             = 35;
                            break;
                        case 7:
                            $playerTransfer->type                            = 'fish_break_through_gift';
                            $playerTransfer->type_name                       = '捕鱼闯关礼金';
                            $playerWithdrawFlowLimit->limit_type             = 36;
                            break;
                        
                        default:
                            // code...
                            break;
                    }

                    $playerTransfer->save();
                    $playerWithdrawFlowLimit->save();

                    $playerAccount->balance                          = $playerTransfer->balance;
                    $playerAccount->save();

                    \DB::commit();
                    Lock::release($redisLock);
                    return true;
                }catch (\Exception $e) {
                    \DB::rollBack();
                    Lock::release($redisLock);
                    Clog::recordabnormal('用户发放闯关礼异常'.$e->getMessage());
                    return false;
                }
            }
        }
    } 
}