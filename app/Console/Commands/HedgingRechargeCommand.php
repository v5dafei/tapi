<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\Player;
use App\Lib\Cache\Lock;
use App\Models\CarrierActivity;
use App\Lib\Clog;

class HedgingRechargeCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hedgingrecharge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'hedgingrecharge';

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
        $playerDepositPayLogs = PlayerDepositPayLog::where('is_hedging_account',1)->where('status',0)->get();
        foreach ($playerDepositPayLogs as $key => $value) {

            $cacheKey   = "player_" .$value->player_id;
            $redisLock = Lock::addLock($cacheKey,60);

            if (!$redisLock) {
                return $this->returnApiJson("对不起,系统繁忙!", 0);
            } else {
                try {
                    \DB::beginTransaction();
                    $existPlayerDepositPayLog                             =  PlayerDepositPayLog::where('player_id',$value->player_id)->where('status',1)->first();
                    if(!$existPlayerDepositPayLog){
                        $value->is_first_recharge           = 1;
                    }

                    $value->status =1;
                    $value->review_user_id             = 0;
                    $value->review_time                = time();
                    $value->day                        = date('Ymd'); 
                    $value->save();

                    $playerAccount                                   = PlayerAccount::where('player_id',$value->player_id)->lockForUpdate()->first();
                    $player                                          = Player::where('player_id',$value->player_id)->first();

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
                    $playerTransfer->project_id                      = $value->pay_order_number;
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $value->arrivedamount;
                    $playerTransfer->type                            = 'recharge';
                    $playerTransfer->type_name                       = '充值';
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                    $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                    $playerTransfer->remark1                         = $value->amount;

                    $playerTransfer->save();
                        
                    $playerAccount->balance                          = $playerAccount->balance + $value->arrivedamount;
                    $playerAccount->save();

                    if(!empty($value->activityids)){
                        $survivalActivity               = CarrierActivity::where('id',$value->activityids)->where('prefix',$value->prefix)->first();
                        $time                           = time();
                        $rebateFinancialBonusesStepRate = json_decode($survivalActivity->rebate_financial_bonuses_step_rate_json,true);
                        $applyRuleString                = json_decode($survivalActivity->apply_rule_string,true);
                        if($survivalActivity && $survivalActivity->status && $survivalActivity->endTime >= $time && $survivalActivity->startTime <= $time && $survivalActivity->apply_way==1){
                            $handselAmount      = 0;
                            $handselLimitAmount = 0;
                            if($survivalActivity->act_type_id==1){
                                //首存
                                $existRecharge = PlayerTransfer::where('player_id',$value->player_id)->where('type','recharge')->get();
                                if(count($existRecharge)==1){
                                    if($applyRuleString[0] == 'userfirstdepositamount'  && $value->amount >= $applyRuleString[2]*10000){
                                        //满足申请条件
                                        $flag = array();
                                        foreach($rebateFinancialBonusesStepRate as $v) {
                                            $flag[] = $v['money'];
                                        }

                                        array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       
                                        
                                        foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                                            if($value->amount >= $v['money']*10000){
                                                if($survivalActivity->bonuses_type==2){
                                                    $handselAmount       = $v['give']*10000;
                                                    $principal           = $v['money']*10000;
                                                    //固定金额
                                                    if($survivalActivity->gift_limit_method==1){
                                                        //本金加礼金
                                                        $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
                                                    } else{
                                                        //礼金
                                                        $handselLimitAmount = $handselAmount*$v['water'];
                                                    }
                                                } elseif($survivalActivity->bonuses_type==1){
                                                    $handselAmount       = bcdiv($value->amount*$v['percent'],100,0);
                                                    if($handselAmount>$v['maxgive']*10000){
                                                        $handselAmount = $v['maxgive']*10000;
                                                    }
                                                    $principal           = $value->amount;
                                                    //百分比
                                                    if($survivalActivity->gift_limit_method==1){
                                                        //本金加礼金
                                                        $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
                                                    } else{
                                                        //礼金
                                                        $handselLimitAmount = $handselAmount*$v['water'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } elseif($survivalActivity->act_type_id==6){
                                //今日首存
                                $existRecharge = PlayerTransfer::where('player_id',$value->player_id)->where('type','recharge')->where('day',date('Ymd'))->get();
                                if(count($existRecharge)==1){
                                    if($applyRuleString[0] == 'todayfirstdepositamount'  && $value->amount >= $applyRuleString[2]*10000){
                                        //满足申请条件
                                        $flag = array();
                                        foreach($rebateFinancialBonusesStepRate as $v) {
                                            $flag[] = $v['money'];
                                        }

                                        array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       
                                        
                                        foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                                            if($value->amount >= $v['money']*10000){
                                                if($survivalActivity->bonuses_type==2){
                                                    $handselAmount       = $v['give']*10000;
                                                    $principal           = $v['money']*10000;
                                                    //固定金额
                                                    if($survivalActivity->gift_limit_method==1){
                                                        //本金加礼金
                                                        $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
                                                    } else{
                                                        //礼金
                                                        $handselLimitAmount = $handselAmount*$v['water'];
                                                    }
                                                } elseif($survivalActivity->bonuses_type==1){
                                                    $handselAmount       = bcdiv($value->amount*$v['percent'],100,0);
                                                    if($handselAmount>$v['maxgive']*10000){
                                                        $handselAmount = $v['maxgive']*10000;
                                                    }
                                                    $principal           = $value->amount;
                                                    //百分比
                                                    if($survivalActivity->gift_limit_method==1){
                                                        //本金加礼金
                                                        $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
                                                    } else{
                                                        //礼金
                                                        $handselLimitAmount = $handselAmount*$v['water'];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } elseif($survivalActivity->act_type_id==2){
                                $tempflag = false;
                                if($survivalActivity->apply_times ==1){
                                    $existActivityCount = PlayerDepositPayLog::where('player_id',$value->player_id)->where('status',1)->where('day',date('Ymd'))->where('activityids',$survivalActivity->id)->get();
                                    if(count($existActivityCount) < 2){
                                        $tempflag = true;
                                    }
                                } else if($survivalActivity->apply_times ==0){
                                    $tempflag = true;
                                }

                                //充送
                                if($applyRuleString[0] == 'singledepositamount'  && $value->amount >= $applyRuleString[2]*10000 && $tempflag){
                                        //满足申请条件
                                    $flag = array();
                                    $handselflag = true;
                                    foreach($rebateFinancialBonusesStepRate as $v) {
                                        $flag[] = $v['money'];
                                    }

                                    array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       
                                        
                                    foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                                        if($value->amount >= $v['money']*10000){
                                            if($survivalActivity->bonuses_type==2){
                                                $handselAmount       = $v['give']*10000;
                                                $principal           = $v['money']*10000;
                                                //固定金额
                                                if($survivalActivity->gift_limit_method==1){
                                                    //本金加礼金
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
                                                } else{
                                                    //礼金
                                                    $handselLimitAmount = $handselAmount*$v['water'];
                                                }
                                            } elseif($survivalActivity->bonuses_type==1){
                                                $handselAmount       = bcdiv($value->amount*$v['percent'],100,0);
                                                if($handselAmount>$v['maxgive']*10000){
                                                    $handselAmount = $v['maxgive']*10000;
                                                }
                                                $principal           = $value->amount;
                                                //百分比
                                                if($survivalActivity->gift_limit_method==1){
                                                    //本金加礼金
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
                                                } else{
                                                    //礼金
                                                    $handselLimitAmount = $handselAmount*$v['water'];
                                                }
                                            }
                                        }
                                    }
                                }
                            } elseif($survivalActivity->act_type_id==7){
                                $allActivityids = PlayerDepositPayLog::where('player_id',$value->player_id)->where('status',1)->where('day',date('Ymd'))->pluck('activityids')->toArray();
                                $i              = 0;
                                foreach ($allActivityids as $k => $v) {
                                    if(!empty($v)){
                                        $allActivityidArr = explode(',',$v);
                                        if(in_array($survivalActivity->id,$allActivityidArr)){
                                            $i++;
                                        }
                                    }
                                }

                                //夜间首存每日一次
                                if($applyRuleString[0] == 'singledepositamount'  && $value->amount >= $applyRuleString[2]*10000 && $i==1){
                                    //满足申请条件
                                    $flag = array();
                                    $handselflag = true;
                                    foreach($rebateFinancialBonusesStepRate as $v) {
                                        $flag[] = $v['money'];
                                    }

                                    array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       
                                        
                                    foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                                        if($value->amount >= $v['money']*10000){
                                            if($survivalActivity->bonuses_type==2){
                                                $handselAmount       = $v['give']*10000;
                                                $principal           = $v['money']*10000;
                                                //固定金额
                                                if($survivalActivity->gift_limit_method==1){
                                                    //本金加礼金
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
                                                } else{
                                                    //礼金
                                                    $handselLimitAmount = $handselAmount*$v['water'];
                                                }
                                            } elseif($survivalActivity->bonuses_type==1){
                                                $handselAmount       = bcdiv($value->amount*$v['percent'],100,0);
                                                if($handselAmount>$v['maxgive']*10000){
                                                    $handselAmount = $v['maxgive']*10000;
                                                }
                                                $principal           = $value->amount;
                                                //百分比
                                                if($survivalActivity->gift_limit_method==1){
                                                    //本金加礼金
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
                                                } else{
                                                    //礼金
                                                    $handselLimitAmount = $handselAmount*$v['water'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $playerTransferActivity                                  = new PlayerTransfer();
                            $playerTransferActivity->prefix                          = $player->prefix;
                            $playerTransferActivity->carrier_id                      = $playerAccount->carrier_id;
                            $playerTransferActivity->rid                             = $playerAccount->rid;
                            $playerTransferActivity->top_id                          = $playerAccount->top_id;
                            $playerTransferActivity->parent_id                       = $playerAccount->parent_id;
                            $playerTransferActivity->player_id                       = $playerAccount->player_id;
                            $playerTransferActivity->is_tester                       = $playerAccount->is_tester;
                            $playerTransferActivity->level                           = $playerAccount->level;
                            $playerTransferActivity->user_name                       = $playerAccount->user_name;
                            $playerTransferActivity->mode                            = 1;
                            $playerTransferActivity->day_m                           = date('Ym',time());
                            $playerTransferActivity->day                             = date('Ymd',time());
                            $playerTransferActivity->amount                          = $handselAmount;
                            $playerTransferActivity->type                            = 'gift';
                            $playerTransferActivity->type_name                       = '活动礼金';
                            $playerTransferActivity->before_balance                  = $playerAccount->balance;
                            $playerTransferActivity->balance                         = $playerAccount->balance + $handselAmount;
                            $playerTransferActivity->before_frozen_balance           = $playerAccount->frozen;
                            $playerTransferActivity->frozen_balance                  = $playerAccount->frozen;

                            $playerTransferActivity->before_agent_balance             = $playerAccount->agentbalance;
                            $playerTransferActivity->agent_balance                    = $playerAccount->agentbalance;
                            $playerTransferActivity->before_agent_frozen_balance      = $playerAccount->agentfrozen;
                            $playerTransferActivity->agent_frozen_balance             = $playerAccount->agentfrozen;

                            $playerTransferActivity->save();

                            $playerAccount->balance                                   = $playerTransferActivity->balance;
                            $playerAccount->save();
                        }
                    }

                    \DB::commit();
                    Lock::release($redisLock);
                    return true;
                } catch (\Exception $e) {
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('对冲号存款自动审核异常：'.$e->getMessage());
                    return false;
                }
            }
        }
    }
}