<?php

namespace App\Http\Controllers\Web;

use App\Models\Report\ReportPlayerStatDay;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerBetFlow;
use App\Models\PlayerActivityAudit;
use App\Models\CarrierActivity;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\CarrierImage;
use App\Jobs\SignInJob;
use App\Models\Log\PlayerSignIn;
use App\Lib\Cache\CarrierCache;
use App\Models\Log\PlayerSignInReceive;
use App\Models\Log\PlayerTransferCasino;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\CarrierPop;
use App\Lib\Cache\Lock;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Lib\Clog;

class ActivityController extends BaseController
{
    public function activityCategory()
    {
        $activityCategory = [];
        $gameCategoryIds         = CarrierActivity::where('carrier_id',$this->carrier->id)->where('status',1)->where('game_category','>',0)->groupBy('game_category')->pluck('game_category')->toArray();

        foreach ($gameCategoryIds as $key => $value) {

           switch ($value) {
             case '1':
               $arr['name']        = config('language')[$this->language]['text1'];
               $arr['type']        = 1;
               $activityCategory[] = $arr;
               break;
             case '2':
               $arr['name']        = config('language')[$this->language]['text2'];
               $arr['type']        = 2;
               $activityCategory[] = $arr;
               break;
              case '3':
               $arr['name']        = config('language')[$this->language]['text5'];
               $arr['type']        = 3;
               $activityCategory[] = $arr;
               break;
              case '4':
               $arr['name']        = config('language')[$this->language]['text4'];
               $arr['type']        = 4;
               $activityCategory[] = $arr;
               break;
              case '5':
               $arr['name']        = config('language')[$this->language]['text7'];
               $arr['type']        = 5;
               $activityCategory[] = $arr;
               break;
              case '6':
               $arr['name']        = config('language')[$this->language]['text6'];
               $arr['type']        = 6;
               $activityCategory[] = $arr;
               break;
              case '7':
               $arr['name']        = config('language')[$this->language]['text3'];
               $arr['type']        = 7;
               $activityCategory[] = $arr;
               break;
             
             default:
               
               break;
           }
        }
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$activityCategory);
    }

    private function judgeAvailable($value,$user){
        switch ($value->apply_times) {
            case 1:
                $joinTimes = PlayerDepositPayLog::where('player_id',$user->player_id)->where('status',1)->where('activityids',$value->id)->where('created_at','>=',date('Y-m-d'.' 00:00:00'))->where('created_at','<=',date('Y-m-d'.' 23:59:59'))->count();
                if($joinTimes){
                   return false;
                  }
                  break;
            case 2:
                 $weekTime       = getWeekStartEnd(date('Y-m-d',time()));
                 $startDate      = $weekTime[0];
                 $endDate        = $weekTime[1];
                 $joinTimes = PlayerDepositPayLog::where('player_id',$user->player_id)->where('status',1)->where('activityids',$value->id)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
                  if($joinTimes){
                      return false;
                  }
                  break;
              case 3:
                  $monthTime       = getMonthStartEnd(date('Y-m-d',time()));
                  $startDate      = $monthTime[0];
                  $endDate        = $monthTime[1];
                  $joinTimes = PlayerDepositPayLog::where('player_id',$user->player_id)->where('status',1)->where('activityids',$value->id)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
                  if($joinTimes){
                      return false;
                  }
                  break;
              case 4:
                  $joinTimes = PlayerDepositPayLog::where('player_id',$user->player_id)->where('status',1)->where('activityids',$value->id)->count();
                  if($joinTimes){
                      return false;
                   }
                  break;
                    
              default:
                      // code...
                  break;
          }

          return true;
    }

    public function activitiesDesc($id)
    {
       $carrierActivity  = CarrierActivity::where('carrier_id',$this->carrier->id)->where('id',$id)->where('status',1)->first();
       $language         = CarrierCache::getLanguageByPrefix($this->prefix);
       $carrierImages    = CarrierImage::all();
       foreach ($carrierImages as $key => $value) {
         $carrierImages[$value->id]   = $value->image_path;
       }

        if($carrierActivity && !is_null($carrierActivity->image_id) && isset($carrierImages[$carrierActivity->image_id])){
            $carrierActivity->image_path        = $carrierImages[$carrierActivity->image_id];
        } else{
            $carrierActivity->image_path        = '';
        }

        if($carrierActivity && !is_null($carrierActivity->mobile_image_id) && isset($carrierImages[$carrierActivity->mobile_image_id])){
           $carrierActivity->moblie_image_path = $carrierImages[$carrierActivity->mobile_image_id];
        } else{
           $carrierActivity->moblie_image_path = '';
        }
      

      return $this->returnApiJson(config('language')[$language]['success1'], 1,$carrierActivity);
    }

    public function activitiesList()
    {
        $input  = request()->all();
        $query  = CarrierActivity::select('id','name','mobile_image_id','sort')->where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->where('status',1);

        if(isset($input['type']) && in_array($input['type'], [1,2,3,4,5,6,7])){
            $query->where('game_category',$input['type']);
        }

        if(isset($input['is_agent_activity']) &&  in_array($input['is_agent_activity'], [0,1])){
            $query->where('is_agent_activity',$input['is_agent_activity']);
        }
        
        $carrierActivities = $query->orderBy('inf_carrier_activity.sort','desc')->get();
        $carrierImages     = CarrierImage::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->get();
        $carrierImagesArrs = [];

        foreach ($carrierImages as $key => $value) {
           $carrierImagesArrs[$value->id] = $value->image_path;
        }

        foreach ($carrierActivities as $key => &$value) {
          if(!is_null($value->mobile_image_id)){
            $value->moblie_image_path = $carrierImagesArrs[$value->mobile_image_id];
          } else {
            $value->moblie_image_path = '';
          }

          if ($this->user) {
            if($value->apply_way==2 || $value->apply_way==3){
              $value->enableApply = 0;
            } else {
      
              $applyRuleStringArr = json_decode($value->apply_rule_string,true);
                switch ($applyRuleStringArr[0]) {
                  case 'userfirstdepositamount':  //首存

                  $judgeAvailable     = $this->judgeAvailable($value,$this->user);

                  if($judgeAvailable){
                    $playerDepositPayLog  = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','asc')->first();
                    if($playerDepositPayLog){
                     
                      if($applyRuleStringArr[1]=='>='){
                        if($playerDepositPayLog->amount >= $applyRuleStringArr[2]*10000){
                        
                          if($value->act_type_id==1 || $value->act_type_id==2){
                            
                            if(empty($playerDepositPayLog->activityids)){
                             
                              $value->enableApply = 1;
                            } else {
                              $value->enableApply = 0;
                            }
                          } else {
                            $value->enableApply = 1;
                          }
                          
                        } else {
                          $value->enableApply = 0;
                        }
                      } else {
                        if($playerDepositPayLog->amount <= $applyRuleStringArr[2]*10000){

                          //首充与存送活动
                          if($value->act_type_id==1 || $value->act_type_id==2){
                            if(empty($playerDepositPayLog->activityids)){
                              $value->enableApply = 1;
                            } else {
                              $value->enableApply = 0;
                            }
                          } else {
                            $value->enableApply = 1;
                          }
                        } else {
                          $value->enableApply = 0;
                        }
                      }

                    } else{
                      $value->enableApply = 0;
                    }
                  } else{
                    $value->enableApply = 0;
                  }

                    break;
                  case 'todayfirstdepositamount':  //今日首存

                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    
                    if($judgeAvailable){
                      $playerDepositPayLog  = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->where('created_at','>=',date('Y-m-d'.' 00:00:00'))->where('created_at','<=',date('Y-m-d'.' 23:59:59'))->orderBy('id','asc')->first();

                      if($playerDepositPayLog){
                        if($applyRuleStringArr[1]=='>='){
                          if($playerDepositPayLog->amount >= $applyRuleStringArr[2]*10000){
                             //首充与存送活动
                            if($value->act_type_id==1 || $value->act_type_id==2){
                              if(empty($playerDepositPayLog->activityids)){
                                $value->enableApply = 1;
                              } else {
                                $value->enableApply = 0;
                              }
                            } else {
                              $value->enableApply = 1;
                            }
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($playerDepositPayLog->amount <= $applyRuleStringArr[2]*10000){
                             //首充与存送活动
                            if($value->act_type_id==1 || $value->act_type_id==2){
                              if(empty($playerDepositPayLog->activityids)){
                                $value->enableApply = 1;
                              } else {
                                $value->enableApply = 0;
                              }
                            } else {
                              $value->enableApply = 1;
                            }
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                       } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                    
                    break;
                  case 'singledepositamount': //单笔存款
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $playerDepositPayLog  = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->first();

                      if($playerDepositPayLog){
                        if($applyRuleStringArr[1]=='>='){
                          if($playerDepositPayLog->amount >= $applyRuleStringArr[2]*10000){
                             //首充与存送活动
                            if($value->act_type_id==1 || $value->act_type_id==2){
                              if(empty($playerDepositPayLog->activityids)){
                                $value->enableApply = 1;
                              } else {
                                $value->enableApply = 0;
                              }
                            } else {
                              $value->enableApply = 1;
                            }
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($playerDepositPayLog->amount <= $applyRuleStringArr[2]*10000){
                             //首充与存送活动
                            if($value->act_type_id==1 || $value->act_type_id==2){
                              if(empty($playerDepositPayLog->activityids)){
                                $value->enableApply = 1;
                              } else {
                                $value->enableApply = 0;
                              }
                            } else {
                              $value->enableApply = 1;
                            }
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                       } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }


                    break;

                  case 'todaydepositamount':    //今日存款
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $todayDepositAmount  = ReportPlayerStatDay::select('recharge_amount')->where('player_id',$this->user->player_id)->where('day',date('Ymd'))->first();
                      if($todayDepositAmount){
                        $rechargeAmount = $todayDepositAmount->recharge_amount;
                         if($applyRuleStringArr[1]=='>='){
                          if($rechargeAmount >= $applyRuleStringArr[2]*10000){

                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($rechargeAmount <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                   
                    break;
                  case 'todaybetflow':        //今日累积流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $todayBetflow  = ReportPlayerStatDay::select('available_bets','lottery_available_bets')->where('player_id',$this->user->player_id)->where('day',date('Ymd'))->first();
                      if($todayBetflow){
                        $availableBets = $todayBetflow->available_bets + $todayBetflow->lottery_available_bets;
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }
                    
                    break;
                  case 'todaylottbetflow':        //今日彩票流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('lottery_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }
                    
                    
                    break;
                  case 'todaycasinobetflow':        //今日视讯流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('casino_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                    
                    break;
                  case 'todayelectronicbetflow':        //今日电子流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('electronic_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }
                    break;
                  case 'todayesportbetflow':        //今日电竞流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('esport_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }
                    
                    break;
                  case 'todayfishbetflow':        //今日捕鱼流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('fish_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                    break;
                  case 'todaycardbetflow':        //今日棋牌流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('card_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                       $value->enableApply = 0;
                    }

                    
                    break;
                  case 'todaysportbetflow':        //今日体育流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('sport_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }
                    
                    
                    break;
                  case 'weekbetflow':         //本周累积流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime     = getWeekStartEnd();
                      $weekStart    = $weekTime[2];
                      $weekEnd      = $weekTime[3];
                      $weekbetflow  = ReportPlayerStatDay::select(\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'))->where('player_id',$this->user->player_id)->where('day','>=',$weekStart)->where('day','<=',$weekEnd)->first();
                      if($weekbetflow){
                        $availableBets = $weekbetflow->available_bets + $weekbetflow->lottery_available_bets;
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                        $value->enableApply = 0;
                      }
                    } else{
                       $value->enableApply = 0;
                    }

                    break;
                  case 'weeklottbetflow':         //本周彩票流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime       = getWeekStartEnd();
                      $weekStart      = $weekTime[2];
                      $weekEnd        = $weekTime[3];
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$weekStart)->where('day','<=',$weekEnd)->sum('lottery_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }
                    
                    break;
                  case 'weekcasinobetflow':         //本周视讯流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime       = getWeekStartEnd();
                      $weekStart      = $weekTime[2];
                      $weekEnd        = $weekTime[3];
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$weekStart)->where('day','<=',$weekEnd)->sum('casino_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                    break;
                  case 'weekelectronicbetflow':         //本周电子流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime       = getWeekStartEnd();
                      $weekStart      = $weekTime[2];
                      $weekEnd        = $weekTime[3];
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$weekStart)->where('day','<=',$weekEnd)->sum('electronic_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                    
                    break;
                  case 'weekesportbetflow':         //本周电竞流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime       = getWeekStartEnd();
                      $weekStart      = $weekTime[2];
                      $weekEnd        = $weekTime[3];
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$weekStart)->where('day','<=',$weekEnd)->sum('esport_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                    
                    break;
                  case 'weekfishbetflow':         //本周捕鱼流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime       = getWeekStartEnd();
                      $weekStart      = $weekTime[2];
                      $weekEnd        = $weekTime[3];
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$weekStart)->where('day','<=',$weekEnd)->sum('fish_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                    
                    break;
                  case 'weekcardbetflow':         //本周捕鱼流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime       = getWeekStartEnd();
                      $auditStarTime  = $weekTime[2];
                      $auditEndTime   = $weekTime[3];
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('card_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }
                    
                    break;
                  case 'weeksportbetflow':         //本周捕鱼流水
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime       = getWeekStartEnd();
                      $auditStarTime  = $weekTime[2];
                      $auditEndTime   = $weekTime[3];
                      $availableBets  = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('sport_available_bets');
                      if($availableBets){
                        if($applyRuleStringArr[1]=='>='){
                          if($availableBets >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($availableBets <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }

                    
                    break;
                  case 'weekdepositamount':   //本周累积存款
                    $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                    if($judgeAvailable){
                      $weekTime           = getWeekStartEnd();
                      $auditStarTime      = $weekTime[2];
                      $auditEndTime       = $weekTime[3];

                      $rechargeAmount = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('recharge_amount');

                      if($rechargeAmount){
                        if($applyRuleStringArr[1]=='>='){
                          if($rechargeAmount >= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($rechargeAmount <= $applyRuleStringArr[2]*10000){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else {
                        $value->enableApply = 0;
                      }
                    } else{
                      $value->enableApply = 0;
                    }


                    break;

                  case 'balance':             //帐户余额
                      $judgeAvailable     = $this->judgeAvailable($value,$this->user);
                      if($judgeAvailable){
                        $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->first();
                        if($applyRuleStringArr[1]=='>='){
                          if($playerAccount->balance >= $applyRuleStringArr[2]*10000 ){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        } else {
                          if($playerAccount->balance <= $applyRuleStringArr[2]*10000 ){
                            $value->enableApply = 1;
                          } else {
                            $value->enableApply = 0;
                          }
                        }
                      } else{
                         $value->enableApply = 0;
                      }
                    break;
                  
                  default:
                    $value->enableApply = 0;
                    break;
                }
            }

          } else {
            $value->enableApply = 0;
          }
        }

        foreach ($carrierActivities as $key => &$value) {
           $carrierNameArr = explode(':',$value->name); 
           $value->name    = $carrierNameArr[0];
        }

        $language         = CarrierCache::getLanguageByPrefix($this->prefix);
        return $this->returnApiJson(config('language')[$language]['success1'], 1,$carrierActivities);
    }

    public function activitySignIn()
    {
       $input          = request()->all();

       $signInNeedRechargeAmount = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sign_in_need_recharge_amount',$this->user->prefix);
       $signInNeedBetFlow        = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sign_in_need_bet_flow',$this->user->prefix);
       $rechargeAmount           = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('amount');
       $language                 = CarrierCache::getLanguageByPrefix($this->prefix);

        if(is_null($rechargeAmount)){
          $rechargeAmount = bcdiv($rechargeAmount,10000,2);
        }


        if($rechargeAmount < $signInNeedRechargeAmount){
           return $this->returnApiJson(config('language')[$language]['error254'],0);
        }

        $availableBetAmount       = PlayerBetFlowMiddle::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('process_available_bet_amount');
        if($availableBetAmount < $signInNeedBetFlow){
          return $this->returnApiJson(config('language')[$language]['error255'],0);
        }

       $signInCategory = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sign_in_category',$this->user->prefix);
       if($signInCategory==3){
          if(!isset($input['receiveday']) || empty($input['receiveday']) || !is_numeric($input['receiveday']) || $input['receiveday']!=intval($input['receiveday'])){
             return $this->returnApiJson(config('language')[$language]['error256'],0);
          }

          $playerSignIn        = PlayerSignIn::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->first();
          if($playerSignIn){
              return $this->returnApiJson(config('language')[$language]['error257'],0);
          } else{
            dispatch(new SignInJob($this->user,$this->carrier,$input));
            return $this->returnApiJson(config('language')[$language]['success1'],1);
          }
       } elseif($signInCategory==2){

       } elseif($signInCategory==1){

       }
    }

    public function activityGetSignInInfo()
    {
      $openSignIn           = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'open_sign_in',$this->user->prefix);
      $signInCategory       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'sign_in_category',$this->user->prefix);
      $signInDayGift        = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'sign_in_day_gift',$this->user->prefix);
      $language             = CarrierCache::getLanguageByPrefix($this->prefix);
      $result               = [];

      if($openSignIn){
        $todaySignin              = PlayerSignIn::where('player_id',$this->user->player_id)->where('day','>=',date('Ymd'))->count();
        $signInDayGiftArr         = json_decode($signInDayGift,true);
        $result['signInCategory'] = $signInCategory;
        $amount                   = PlayerSignInReceive::where('player_id',$this->user->player_id)->where('day','>=',date('Ym01'))->sum('amount');
        $result['gift']           = $amount/10000;
        $result['rules']          = $signInDayGiftArr;
        $result['currmonth']      = date('Y-m');
        $result['todaysignin']    = $todaySignin;
        switch ($signInCategory) {
            case 1:
              $day                      = PlayerSignIn::where('player_id',$this->user->player_id)->where('day','>=',date('Ym01'))->count();
              $result['day']            = 1;
             break;
            case 2:
              $day                      = PlayerSignIn::where('player_id',$this->user->player_id)->where('day','>=',date('Ym01'))->count();
              $result['day']            = $day;
            case 3:
              $maxday                   = 0;
              $playerSignInArr          = PlayerSignIn::where('player_id',$this->user->player_id)->where('day','>=',date('Ym01'))->orderBy('id','asc')->pluck('day')->toArray();
              $startDay                 = date('Ym01');
              $nextStartDay             = date("Ymd", strtotime('+1 month'));
              $currday                  = 0;

              for(;$startDay<$nextStartDay;){
                if(in_array($startDay, $playerSignInArr)){
                  $currday++;
                  if($currday>$maxday){
                    $maxday = $currday;
                  }
                  
                }else{
                  $currday =0;
                }
                $startDay = date('Ymd',strtotime($startDay)+86400);
              }

              $result['day']            = $maxday;
             break;
           default:
             break;
       }

        if($result['signInCategory']==1){
          $result['str1'] = '您本月已签到[day:'.$result['day'].']天';
          $result['str3'][] = '每签到1天,获得奖励[money:'.$signInDayGiftArr[0][1].']';
        } else if($result['signInCategory']==2){
          $result['str1'] = '您本月已累积签到[day:'.$result['day'].']天';

          foreach($signInDayGiftArr as $key=>$value){
            $result['str3'][] ='本月累积签到[day:'.$value[0].']，获得奖励[money:'.$value[1].']';
          }

        } else if($result['signInCategory']==3){
          $result['str1'] = '您本月已连续签到[day:'.$result['day'].']天';
          foreach($signInDayGiftArr as $key=>$value){
            $result['str3'][] ='本月连续签到[day:'.$value[0].']天，获得奖励[money:'.$value[1].']';
          }
        }
        $result['str2'] = '您本月已获得奖励[money:'.$result['gift'].']';

        return $this->returnApiJson(config('language')[$language]['success1'],1,$result);
      } else {
        return $this->returnApiJson(config('language')[$language]['error213'],0);
      }
    }

    public function activityGetSignInList1()
    {
      $input     = request()->all();
      $language  = CarrierCache::getLanguageByPrefix($this->prefix);
      if(isset($input['yearmonth'])){
        $input['yearmonth'] = str_replace('-','',$input['yearmonth']);
      }

      if(isset($input['yearmonth']) && intval($input['yearmonth']) == $input['yearmonth'] && strlen($input['yearmonth'])==6){
         $beginDate = $input['yearmonth'].'01';
         $endDate   = date('Ymd',strtotime($beginDate.' +1 month -1 day'));
         $result    = PlayerSignIn::where('player_id',$this->user->player_id)->where('day','>=',$beginDate)->where('day','<=',$endDate)->pluck('day')->toArray();
         $data      = [];
         foreach ($result as  &$value) {
           $value =substr($value,0,4).'-'.substr($value,4,2).'-'.substr($value,6,2);
         }
         return $this->returnApiJson(config('language')[$language]['success1'],1,$result);
      } else {
        return $this->returnApiJson(config('language')[$language]['error21'],0);
      }
      
    }

    //连续签倒
    public function activityGetSignInList()
    {
        $data          = [];
        //连续签到天数
        $continuousDay = PlayerSignIn::where('player_id',$this->user->player_id)->where('is_continuous',1)->count();


        //最近一次签倒
        $latelySign = PlayerSignIn::where('player_id',$this->user->player_id)->where('is_continuous',1)->orderBy('id','desc')->first();

        //领取金额
        $receiveAmount = PlayerSignInReceive::where('player_id',$this->user->player_id)->sum('amount');

        //领取次数
        $receiveCount = PlayerSignInReceive::where('player_id',$this->user->player_id)->count();

        //所需充值金额
        $signInNeedRechargeAmount = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sign_in_need_recharge_amount',$this->user->prefix);
        $rechargeAmount           = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('amount');

        if(!is_null($rechargeAmount)){
          $rechargeAmount = bcdiv($rechargeAmount,10000,2);
        }

        //所需有效投注
        $signInNeedBetFlow        = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sign_in_need_bet_flow',$this->user->prefix);
        $availableBetAmount       = PlayerBetFlowMiddle::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('process_available_bet_amount');

        $data['continuous_day']               = $continuousDay;
        $data['receive_amount']               = bcdiv($receiveAmount,10000,2);
        $data['signin_need_rechargeamount']   = $signInNeedRechargeAmount;
        $data['rechargeamount']               = $rechargeAmount;
        $data['signin_need_betflow']          = $signInNeedBetFlow;
        $data['available_betamount']          = bcdiv($availableBetAmount,1,2);


        $receivedays                          = PlayerSignInReceive::where('player_id',$this->user->player_id)->pluck('receiveday')->toArray();
        $signInDayGift                        = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sign_in_day_gift',$this->user->prefix);
        $signInDayGift                        = json_decode($signInDayGift,true);

        $items                                = [];
        foreach ($signInDayGift as $key => $value) {
          $row           = [];
          $row['day']    = $value['day'];
          $row['amount'] = $value['money'];

          if(in_array($value['day'], $receivedays)){
              $row['status'] = 2;
          } else{
            if($continuousDay+1 ==$value['day'] && $availableBetAmount >= $signInNeedBetFlow && $rechargeAmount >=$signInNeedRechargeAmount && (!$latelySign || ($latelySign->day== date('Ymd',strtotime('-1 day'))))){
              $row['status'] = 1;
            } else{
              $row['status'] = 0;
            }
          }
          
          $items[]       = $row;
        }

        $data['items']   = $items;

        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function receiveGift($id)
    {
        $existplayerReceiveGiftCenter = PlayerReceiveGiftCenter::where('carrier_id',$this->carrier->id)->where('player_id',$this->user->player_id)->where('id',$id)->first();
        if(!$existplayerReceiveGiftCenter){
          return $this->returnApiJson(config('language')[$this->language]['error252'],0);
        }

        if($existplayerReceiveGiftCenter->status !=0 || time()>$existplayerReceiveGiftCenter->invalidtime){
          return $this->returnApiJson(config('language')[$this->language]['error253'],0);
        }

        $cacheKey              = "player_" .$this->user->player_id;
        $redisLock             = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            return $this->returnApiJson(config('language')[$this->language]['error20'],0);
        } else {
          try {
            \DB::beginTransaction();
            $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();

            $playerTransfer                                  = new PlayerTransfer();
            $playerTransfer->carrier_id                      = $this->user->carrier_id;
            $playerTransfer->prefix                          = $this->user->prefix;
            $playerTransfer->rid                             = $this->user->rid;
            $playerTransfer->top_id                          = $this->user->top_id;
            $playerTransfer->parent_id                       = $this->user->parent_id;
            $playerTransfer->player_id                       = $this->user->player_id;
            $playerTransfer->is_tester                       = $this->user->is_tester;
            $playerTransfer->user_name                       = $this->user->user_name;
            $playerTransfer->level                           = $this->user->level;
            $playerTransfer->mode                            = 1;
            $playerTransfer->day_m                           = date('Ym');
            $playerTransfer->day                             = date('Ymd');
            $playerTransfer->amount                          = $existplayerReceiveGiftCenter->amount;
            $playerTransfer->before_balance                  = $playerAccount->balance;
            $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
            $playerTransfer->frozen_balance                  = $playerAccount->frozen;

            $playerTransfer->before_agent_balance           = $playerAccount->agentbalance;
            $playerTransfer->agent_balance                  = $playerAccount->agentbalance;
            $playerTransfer->before_agent_frozen_balance    = $playerAccount->agentfrozen;
            $playerTransfer->agent_frozen_balance           = $playerAccount->agentfrozen;

            switch ($existplayerReceiveGiftCenter->type) {
              case 20:
                $playerTransfer->type                            = 'register_gift';
                $playerTransfer->type_name                       = '注册礼金';
                break;
              case 21:
                $playerTransfer->type                            = 'luck_draw_prize';
                $playerTransfer->type_name                       = '幸运轮盘礼金';
                break;
              case 22:
                $playerTransfer->type                            = 'signin_gift';
                $playerTransfer->type_name                       = '签到礼金';
                break;
              case 26:
                $playerTransfer->type                            = 'code_gift';
                $playerTransfer->type_name                       = '体验券';
                break;
              case 29:
                $playerTransfer->type                            = 'break_through_gift';
                $playerTransfer->type_name                       = '闯关礼金';
                break;
              case 30:
                $playerTransfer->type                            = 'video_break_through_gift';
                $playerTransfer->type_name                       = '视讯闯关礼金';
                break;
              case 31:
                $playerTransfer->type                            = 'electronic_break_through_gift';
                $playerTransfer->type_name                       = '电子闯关礼金';
                break;
              case 32:
                $playerTransfer->type                            = 'esport_break_through_gift';
                $playerTransfer->type_name                       = '电竞闯关礼金';
                break;
              case 33:
                $playerTransfer->type                            = 'card_break_through_gift';
                $playerTransfer->type_name                       = '棋牌闯关礼金';
                break;
              case 34:
                $playerTransfer->type                            = 'sport_break_through_gift';
                $playerTransfer->type_name                       = '体育闯关礼金';
                break;
              case 35:
                $playerTransfer->type                            = 'lottery_break_through_gift';
                $playerTransfer->type_name                       = '彩票闯关礼金';
                break;
              case 36:
                $playerTransfer->type                            = 'fish_break_through_gift';
                $playerTransfer->type_name                       = '捕鱼闯关礼金';
                break;
              default:
                // code...
                break;
            }
            
            $playerTransfer->save();

            //流水限制
            $playerWithdrawFlowLimit                                   = new PlayerWithdrawFlowLimit();
            $playerWithdrawFlowLimit->carrier_id                       = $this->user->carrier_id;
            $playerWithdrawFlowLimit->top_id                           = $this->user->top_id;
            $playerWithdrawFlowLimit->parent_id                        = $this->user->parent_id;
            $playerWithdrawFlowLimit->rid                              = $this->user->rid;
            $playerWithdrawFlowLimit->player_id                        = $this->user->player_id;
            $playerWithdrawFlowLimit->user_name                        = $this->user->user_name;
            $playerWithdrawFlowLimit->limit_amount                     = $existplayerReceiveGiftCenter->limitbetflow;
            $playerWithdrawFlowLimit->betflow_limit_category           = $existplayerReceiveGiftCenter->betflow_limit_category;
            $playerWithdrawFlowLimit->betflow_limit_main_game_plat_id  = $existplayerReceiveGiftCenter->betflow_limit_main_game_plat_id;
            $playerWithdrawFlowLimit->limit_type                       = config('main')['limittype'][$playerTransfer->type];
            $playerWithdrawFlowLimit->operator_id                      = 0;
            $playerWithdrawFlowLimit->save();

            $playerAccount->balance                                     = $playerTransfer->balance;
            $playerAccount->save();

            $existplayerReceiveGiftCenter->status                       = 1;
            $existplayerReceiveGiftCenter->receivetime                  = time();
            $existplayerReceiveGiftCenter->save();

            \DB::commit();
            Lock::release($redisLock);
            return $this->returnApiJson(config('language')[$this->language]['success1'],1);

          } catch (\Exception $e) {
             \DB::rollback();
             Clog::recordabnormal('福彩中心领取礼金异常:'.$e->getMessage());   
             Lock::release($redisLock);
            return $this->returnApiJson(config('language')[$this->language]['error258'],0);
          }
        }
    }

    public function receivegiftList()
    {
       $res = PlayerReceiveGiftCenter::receivegiftList($this->user);
       if(is_array($res)){
          return $this->returnApiJson(config('language')[$this->language]['success1'],1,$res);
       } else{
          return $this->returnApiJson($res,0);
       }
    }

    public function receivegiftStat()
    {
       $input = request()->all();
       $receivedAmount            = PlayerReceiveGiftCenter::where('player_id',$this->user->player_id)->where('status',1)->sum('amount');
       $data['receivedAmount']    = bcdiv($receivedAmount,10000,2);
       $notReceivedAmount         = PlayerReceiveGiftCenter::where('player_id',$this->user->player_id)->where('status',0)->sum('amount');
       $data['notReceivedAmount'] = bcdiv($notReceivedAmount,10000,2);

       return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function activityApply()
    {
        $input          = request()->all();
        $time           = time();
        $todayStartTime = strtotime(date('Y-m-d').' 00:00:00');
        $todayEndTime   = strtotime(date('Y-m-d').' 23:59:59');
        $activid        = $input['id'] ?? 0;

        if ($activid == 0) {
            return $this->returnApiJson(config('language')[$this->language]['error7'], 0);
        }

        $activInfo = CarrierActivity::find($activid);

        if (is_null($activInfo)) {
            return $this->returnApiJson(config('language')[$this->language]['error7'], 0);
        }

        if($time<$activInfo->startTime || $time>$activInfo->endTime) {
            return $this->returnApiJson(config('language')[$this->language]['error8'], 0);
        }

        //流水限制类型
        $game_category                           = $activInfo->game_category;
        //  '状态 1 启用  0=禁用',
        $status                                  = $activInfo->status;
        // 1=首充，2=充送，3=静态
        $act_type_id                             = $activInfo->act_type_id;
        // 1=百分比  2=固定金额
        $bonuses_type                            = $activInfo->bonuses_type;
        // 申请方式, 1=手动，2=自动, 3=无需申请
        $apply_way                               = $activInfo->apply_way;
        // 0=不限,1=每日-次，2=每周一次，3=每月一次，4=永久一次
        $apply_times                             = $activInfo->apply_times;
        // 1=手动，2=自动，3=无需审核
        $censor_way                              = $activInfo->censor_way;
        // 规则  todayDespositPay = 今日存款，todayBetFlow=今日流水，accountBalance=帐户余额
        $apply_rule_string                       = $activInfo->apply_rule_string;
    
        // 红利类型阶梯比例
        $rebate_financial_bonuses_step_rate_json = $activInfo->rebate_financial_bonuses_step_rate_json;

        if (!$status) {
            return $this->returnApiJson(config('language')[$this->language]['error9'], 0);
        }

        if($apply_way==3) {
            return $this->returnApiJson(config('language')[$this->language]['error10'], 0);
        }

        switch ($apply_times) {
            case 1:
                $auditStarTime = date('Y-m-d 00:00:00');
                $auditEndTime  = date('Y-m-d 23:59:59');
                break;
            case 2:
                $weekTime      = getWeekStartEnd();
                $auditStarTime = $weekTime[0];
                $auditEndTime  = $weekTime[1];
                break;
            case 3:
                $auditStarTime = date('Y-m-01 00:00:00');
                $auditEndTime  = date('Y-m-t 23:59:59');
                break;
            case 4:
                break;
            default:
                break;
        }

        if ($apply_times == 1 || $apply_times == 2 || $apply_times == 3 ) {
            $playerActivityAuditSum = PlayerActivityAudit::where(['carrier_id' => $this->carrier->id,'act_id' => $activid])->where('player_id',$this->user->player_id)->whereBetween('created_at', [$auditStarTime, $auditEndTime])->count();
        } else if($apply_times == 4) {
            $playerActivityAuditSum = PlayerActivityAudit::where(['carrier_id' => $this->carrier->id,'act_id' => $activid])->where('player_id',$this->user->player_id)->count();
        } else {
            $playerActivityAuditSum = 0;
        }

        if($playerActivityAuditSum>0) {
            return $this->returnApiJson(config('language')[$this->language]['error11'], 0);
        }

        $playerActivityAudit = PlayerActivityAudit::where('player_id',$this->user->player_id)->where('act_id',$activid)->where('status',0)->first();
        if($playerActivityAudit) {
            return $this->returnApiJson(config('language')[$this->language]['error13'], 0);
        }

        //判断申请规则
        // userfirstdepositamount    = 首存，todayfirstdepositamount =今日首存，singledepositamount =单笔存款，todaydepositamount =今日存款，todaybetflow =今日累积流水，weekbetflow=本周累积流水，weekdepositamount=本周累积存款，balance=帐户余额

        $apply_rule        = json_decode($apply_rule_string, 1);
        $depositAmount     = 0;
        $betflowAmount     = 0;
        $joinGameLimit     = 0;
        $depositTime       = 0;

        switch ($apply_rule[0]) {

            //首存
            case 'userfirstdepositamount':
                $playerDepositPayLog  = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','asc')->first();
                if($playerDepositPayLog && empty($playerDepositPayLog->activityids)){
                  if($apply_rule[1]=='>=' && $playerDepositPayLog->amount < bcmul($apply_rule[2], 10000,0)){
                    return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]=='<=' && $playerDepositPayLog->amount > bcmul($apply_rule[2], 10000,0)){
                    return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                $depositAmount = $playerDepositPayLog->amount;
                $joinGameLimit = 1; 
                $depositTime   = $playerDepositPayLog->review_time;
                break;

            //今日首存
            case 'todayfirstdepositamount':
                $playerDepositPayLog = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->whereBetween('review_time',[$todayStartTime,$todayEndTime])->orderBy('id','asc')->first();
                if($playerDepositPayLog && empty($playerDepositPayLog->activityids)){
                  if($apply_rule[1]=='>=' && $playerDepositPayLog->amount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]=='<=' && $playerDepositPayLog->amount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                $depositAmount = $playerDepositPayLog->amount;
                $joinGameLimit = 1; 
                $depositTime   = $playerDepositPayLog->review_time;
                break;

            //单笔首存
            case 'singledepositamount':
                $playerDepositPayLog = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->first();
                if($playerDepositPayLog && empty($playerDepositPayLog->activityids)){
                  if($apply_rule[1]=='>=' && $playerDepositPayLog->amount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]=='<=' && $playerDepositPayLog->amount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  //单笔首存，参加了的活动不能重复参加
                  $activityids = explode(',',$playerDepositPayLog->activityids);
                  if(in_array($activid,$activityids)){
                    return $this->returnApiJson(config('language')[$this->language]['error259'], 0);
                  }

                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                $depositAmount = $playerDepositPayLog->amount;
                $joinGameLimit = 1;
                $depositTime   = $playerDepositPayLog->review_time; 
                break;

            //今日存款
            case 'todaydepositamount':
                $amount = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->whereBetween('review_time',[$todayStartTime,$todayEndTime])->sum('amount');
                if(!is_null($amount)){
                    $amount = 0;
                }
                if($apply_rule[1]=='>=' && $amount < bcmul($apply_rule[2], 10000,0)) {
                    return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }

                if($apply_rule[1]=='<=' && $amount > bcmul($apply_rule[2], 10000,0)) {
                    return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                $depositAmount = $amount;
                break;

            //今日流水
            case 'todaybetflow':

                $todaybetflow       = ReportPlayerStatDay::select('available_bets','lottery_available_bets')->where('player_id',$this->user->player_id)->where('day',date('Ymd'))->first();
                if($todaybetflow){
                  $availableBetAmount = $todaybetflow->available_bets + $todaybetflow->lottery_available_bets;
                   if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                    $betflowAmount = $availableBetAmount;
                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
               
                break;
            //今日彩票有效投注额
            case 'todaylottbetflow':

                $availableBetAmount       = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('lottery_available_bets');
                if($availableBetAmount){
                   if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                    $betflowAmount = $availableBetAmount;
                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
               
                break;
            //今日视讯有效投注额
            case 'todaycasinobetflow':

                $availableBetAmount       = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('casino_available_bets');
                if($availableBetAmount){
                   if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                    $betflowAmount = $availableBetAmount;
                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
               
                break;
            //今日电子有效投注额
            case 'todayelectronicbetflow':

                $availableBetAmount       = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('electronic_available_bets');
                if($availableBetAmount){
                   if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                    $betflowAmount = $availableBetAmount;
                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
               
                break;
            //今日电竞有效投注额
            case 'todayesportbetflow':

                $availableBetAmount       = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('esport_available_bets');
                if($availableBetAmount){
                   if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                    $betflowAmount = $availableBetAmount;
                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
               
                break;
            //今日捕鱼有效投注额
            case 'todayfishbetflow':

                $availableBetAmount       = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('fish_available_bets');
                if($availableBetAmount){
                   if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                    $betflowAmount = $availableBetAmount;
                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
               
                break;
            //今日棋牌有效投注额
            case 'todaycardbetflow':

                $availableBetAmount       = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('card_available_bets');
                if($availableBetAmount){
                   if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                    $betflowAmount = $availableBetAmount;
                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
               
                break;
            //今日体育有效投注额
            case 'todaysportbetflow':

                $availableBetAmount       = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('sport_available_bets');
                if($availableBetAmount){
                   if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                    $betflowAmount = $availableBetAmount;
                } else {
                   return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
               
                break;

            //本周累积流水
            case 'weekbetflow':
                $weekTime           = getWeekStartEnd();
                $auditStarTime      = $weekTime[2];
                $auditEndTime       = $weekTime[3];

                $weekbetflow        = ReportPlayerStatDay::select(\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'))->where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->first();

                if($weekbetflow){
                  $availableBetAmount = $weekbetflow->available_bets + $weekbetflow->lottery_available_bets;
                  if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $betflowAmount = $availableBetAmount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;
            //本周彩票有效投注额
            case 'weeklottbetflow':
                $weekTime           = getWeekStartEnd();
                $auditStarTime      = $weekTime[2];
                $auditEndTime       = $weekTime[3];

                $availableBetAmount        = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('lottery_available_bets');

                if($availableBetAmount){
                  if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $betflowAmount = $availableBetAmount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;
            //本周视讯有效投注额
            case 'weekcasinobetflow':
                $weekTime           = getWeekStartEnd();
                $auditStarTime      = $weekTime[2];
                $auditEndTime       = $weekTime[3];

                $availableBetAmount        = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('casino_available_bets');

                if($availableBetAmount){
                  if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $betflowAmount = $availableBetAmount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;

            //本周电子有效投注额
            case 'weekelectronicbetflow':
                $weekTime           = getWeekStartEnd();
                $auditStarTime      = $weekTime[2];
                $auditEndTime       = $weekTime[3];

                $availableBetAmount        = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('electronic_available_bets');

                if($availableBetAmount){
                  if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $betflowAmount = $availableBetAmount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;

            //本周电竞有效投注额
            case 'weekesportbetflow':
                $weekTime           = getWeekStartEnd();
                $auditStarTime      = $weekTime[2];
                $auditEndTime       = $weekTime[3];

                $availableBetAmount        = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('esport_available_bets');

                if($availableBetAmount){
                  if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $betflowAmount = $availableBetAmount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;

            //本周捕鱼有效投注额
            case 'weekfishbetflow':
                $weekTime           = getWeekStartEnd();
                $auditStarTime      = $weekTime[2];
                $auditEndTime       = $weekTime[3];

                $availableBetAmount        = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('fish_available_bets');

                if($availableBetAmount){
                  if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $betflowAmount = $availableBetAmount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;

            //本周棋牌有效投注额
            case 'weekcardbetflow':
                $weekTime           = getWeekStartEnd();
                $auditStarTime      = $weekTime[2];
                $auditEndTime       = $weekTime[3];

                $availableBetAmount        = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('card_available_bets');

                if($availableBetAmount){
                  if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $betflowAmount = $availableBetAmount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;
                
            //本周体育有效投注额
            case 'weeksportbetflow':
                $weekTime           = getWeekStartEnd();
                $auditStarTime      = $weekTime[2];
                $auditEndTime       = $weekTime[3];

                $availableBetAmount        = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('sport_available_bets');

                if($availableBetAmount){
                  if($apply_rule[1]== '>=' && $availableBetAmount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]== '<=' && $availableBetAmount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $betflowAmount = $availableBetAmount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;

            //本周累积存款
            case 'weekdepositamount':
                $weekTime      = getWeekStartEnd();
                $auditStarTime = $weekTime[2];
                $auditEndTime  = $weekTime[3];

                $weekdepositamount = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day','>=',$auditStarTime)->where('day','<=',$auditEndTime)->sum('recharge_amount');
                if($weekdepositamount){
                  $amount = $weekdepositamount->recharge_amount;
                  if($apply_rule[1]=='>=' && $amount < bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }

                  if($apply_rule[1]=='<=' && $amount > bcmul($apply_rule[2], 10000,0)) {
                      return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                  }
                  $depositAmount = $amount;
                } else {
                  return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }

                break;
            //帐户余额
            case 'balance':
                $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->first();
                if($apply_rule[1]== '>=' && $playerAccount->balance < bcmul($apply_rule[2], 10000,0)) {
                    return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }

                if($apply_rule[1]== '<=' && $playerAccount->balance > bcmul($apply_rule[2], 10000,0)) {
                    return $this->returnApiJson(config('language')[$this->language]['error14'], 0);
                }
                break;
            default:
                break;
        }

        $bonus             = 0;
        $withdrawFlowLimit = 0;
        $limitAmount       = 0;
        $subBonus          = 0;

        //首存与存送活动必须投注前才能申请
        //进入游戏前才能申请
        if($joinGameLimit){

            $existTransferOut   = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','casino_transfer_out')->where('created_at','>',date('Y-m-d H:i:s',$depositTime))->first();
            $existPlayerBetFlow = PlayerBetFlow::where('player_id',$this->user->player_id)->where('bet_time','>=',$depositTime)->first();
            
            if($existTransferOut && $existPlayerBetFlow){
               return $this->returnApiJson(config('language')[$this->language]['error238'], 0);
            }
        }

        $specialDeposit =  false;
        $playerDepositPayLog  = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->first();
        if($playerDepositPayLog && $playerDepositPayLog->is_wallet_recharge && in_array($act_type_id,[1,2])){
            $specialDeposit =  true;    
        }

        if($bonuses_type==1 || $bonuses_type==2) {
            
            //百分比或固定比例
            $rebate_financial_bonuses_step_rate_json_arr = json_decode($rebate_financial_bonuses_step_rate_json, 1);
            
            if (!is_array($rebate_financial_bonuses_step_rate_json_arr) || empty($rebate_financial_bonuses_step_rate_json_arr)) {
                return $this->returnApiJson(config('language')[$this->language]['error18'], 0);
            }

            $flag = array();
            foreach($rebate_financial_bonuses_step_rate_json_arr as $v) {
                $flag[] = $v['money'];
            }

            array_multisort($flag, SORT_ASC, $rebate_financial_bonuses_step_rate_json_arr);

            foreach ($rebate_financial_bonuses_step_rate_json_arr as $key => $value) {

                if ($depositAmount >= $value['money'] * 10000) {
                         
                    switch ($bonuses_type) {
                        case 1: // 百分比
                            if($specialDeposit){
                              $bonus = $depositAmount * bcdiv($value['percent_special'],100,2);
                            } else{
                              $bonus = $depositAmount * bcdiv($value['percent'],100,2);
                            }
                            
                            if($bonus > $value['maxgive'] * 10000){
                                $bonus = $value['maxgive'] * 10000;
                            }
                            break;
                        default: // 固定金额
                            if($specialDeposit){
                              $bonus = $value['give_special']*10000;
                            } else{
                              $bonus = $value['give']*10000;
                            }

                            break;
                    }

                    $withdrawFlowLimit = ($bonus +$depositAmount)*$value['water'];
                    //本金加礼金
                    if($activInfo->gift_limit_method ==1){
                        $withdrawFlowLimit = ($bonus +$depositAmount)*$value['water'];
                        $limitAmount       = $withdrawFlowLimit-$depositAmount;
                    } else {
                        $withdrawFlowLimit = $depositAmount + $bonus*$value['water'];
                        $limitAmount       = $withdrawFlowLimit - $depositAmount;
                    }
                }
            }

            if(!$bonus) {
                return $this->returnApiJson(config('language')[$this->language]['error19'], 0);
            }
        }

        $playerActivityAudit                                  = new PlayerActivityAudit();
        $playerActivityAudit->carrier_id                      = $this->carrier->id;
        $playerActivityAudit->act_id                          = $activid;
        $playerActivityAudit->player_id                       = $this->user->player_id;
        $playerActivityAudit->user_name                       = $this->user->user_name;
        $playerActivityAudit->ip                              = getRealIP();
        $playerActivityAudit->admin_id                        = 0;
        $playerActivityAudit->top_id                          = $this->user->top_id;
        $playerActivityAudit->parent_id                       = $this->user->parent_id;
        $playerActivityAudit->rid                             = $this->user->rid;
        $playerActivityAudit->deposit_amount                  = $depositAmount;
        $playerActivityAudit->betflow_limit_category          = $activInfo->betflow_limit_category;
        $playerActivityAudit->betflow_limit_main_game_plat_id = $activInfo->betflow_limit_main_game_plat_id;

        if($bonuses_type==1 || $bonuses_type==2) {
            $playerActivityAudit->gift_amount         = $bonus;
            $playerActivityAudit->withdraw_flow_limit = $withdrawFlowLimit;
        } else {
            $playerActivityAudit->gift_amount         = 0;
            $playerActivityAudit->withdraw_flow_limit = 0;
        }
            
        // 处理审核
        switch ($censor_way) {
            case 1: // 手动 ， 添加审核记录
                if(isset($playerDepositPayLog)) {
                    $playerActivityAudit->depositpay_id = $playerDepositPayLog->id;
                }
                $playerActivityAudit->status    = 0;
                $playerActivityAudit->save();

                return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
                break;
            case 2: // 自动 , 添加审核记录，帐变记录，更改数值
               $cacheKey = "player_" .$this->user->player_id;
               $redisLock = Lock::addLock($cacheKey,10);

                if (!$redisLock) {
                    return $this->returnApiJson(config('language')[$this->language]['error20'], 0);
                } else {
                    try{
                        \DB::beginTransaction();

                        $playerActivityAudit->status    = 1;
                        $playerActivityAudit->save();

                        $playerReceiveGiftCenter                     = new PlayerReceiveGiftCenter();
                        $playerReceiveGiftCenter->orderid            = 'LJ'.$this->user->player_id.time().rand('1','99');
                        $playerReceiveGiftCenter->carrier_id         = $this->user->carrier_id;
                        $playerReceiveGiftCenter->player_id          = $this->user->player_id;
                        $playerReceiveGiftCenter->user_name          = $this->user->user_name;
                        $playerReceiveGiftCenter->top_id             = $this->user->top_id;
                        $playerReceiveGiftCenter->parent_id          = $this->user->parent_id;
                        $playerReceiveGiftCenter->rid                = $this->user->rid;

                        if($act_type_id==1){
                          $playerReceiveGiftCenter->type               = 41;
                        } elseif($act_type_id==2){
                          $playerReceiveGiftCenter->type               = 42;
                        } 
                        
                        $playerReceiveGiftCenter->amount                          = $subBonus == 0 ? $bonus : $subBonus;
                        $playerReceiveGiftCenter->invalidtime                     = time()+31536000;
                        $playerReceiveGiftCenter->limitbetflow                    = $limitAmount;
                        $playerReceiveGiftCenter->betflow_limit_category          = $activInfo->betflow_limit_category;
                        $playerReceiveGiftCenter->betflow_limit_main_game_plat_id = $activInfo->betflow_limit_main_game_plat_id;
                        $playerReceiveGiftCenter->save();

                        //写入存款表
                        if(isset($playerDepositPayLog)) {
                            $playerDepositPayLog->activityids = $activid;
                            $playerDepositPayLog->save();
                        }

                        \DB::commit();
                        Lock::release($redisLock);
                        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
                    }catch (\Exception $e) {
                        \DB::rollBack();
                        Lock::release($redisLock);
                        Clog::recordabnormal('申请活动异常:'.$e->getMessage());  
                        return $this->returnApiJson($e->getMessage(), 0);
                    }
                }
                break;
            }
    }

    public function enableApplyActivityList()
    {
        $carrierActivitys                =   CarrierActivity::select('name','act_type_id','id')->where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->whereIn('act_type_id',[1,2,6,7])->where('status',1)->orderBy('sort','desc')->get();

        //夜间活动
        $existNightActivity              = false;
        $startDate                       = date('Y-m-d').' 20:00:00';
        $endDate                         = date('Y-m-d').' 08:00:00';

        if(time() >= strtotime($startDate) || time() <= strtotime($endDate)){
          $existNightActivity     = true;
        }

        $data                     = [];

        foreach ($carrierActivitys as $key => $value) {
          switch ($value->act_type_id) {
            case 1:
              $existfirstDeposit               = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','recharge')->first();
              if(!$existfirstDeposit){
                if(strpos($value->name, ':') != false){
                   $valueNameArr = explode(':',$value->name);
                   $preStr       = $valueNameArr[0];
                   $options      = explode(',',$valueNameArr[1]);
                   foreach ($options as $k => $v) {
                      $temp       = clone $value;
                      $temp->name =$preStr.':'.$v;
                      $data[] = $temp;
                   }
                }
              }
              break;
            case 2:
              if($value->apply_times==1) {
                  $existPlayerDepositPayLog = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->where('day',date('Ymd'))->where('activityids',$value->id)->first();
                  if(!$existPlayerDepositPayLog){
                    if(strpos($value->name, ':') != false){
                       $valueNameArr = explode(':',$value->name);
                       $preStr       = $valueNameArr[0];
                       $options      = explode(',',$valueNameArr[1]);
                       foreach ($options as $k => $v) {
                         $temp       = clone $value;
                         $temp->name =$preStr.':'.$v;
                        $data[] = $temp;
                       }
                    }
                  }
              } else{
                  if(strpos($value->name, ':') != false){
                    $valueNameArr = explode(':',$value->name);
                    $preStr       = $valueNameArr[0];
                    $options      = explode(',',$valueNameArr[1]);
                    foreach ($options as $k => $v) {
                      $temp       = clone $value;
                      $temp->name =$preStr.':'.$v;
                      $data[] = $temp;
                    }
                  }
              }
              break;
            case 6:
              $existtodayFirstDeposit          = PlayerTransfer::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->where('type','recharge')->first();
              if(!$existtodayFirstDeposit){
                  if(strpos($value->name, ':') != false){
                    $valueNameArr = explode(':',$value->name);
                    $preStr       = $valueNameArr[0];
                    $options      = explode(',',$valueNameArr[1]);
                    foreach ($options as $k => $v) {
                     $temp       = clone $value;  
                     $temp->name =$preStr.':'.$v;
                     $data[] = $temp;
                    }
                  }
              }
              break;
            case 7:
              if($existNightActivity){
                if(strpos($value->name, ':') != false){
                  $valueNameArr = explode(':',$value->name);
                  $preStr       = $valueNameArr[0];
                  $options      = explode(',',$valueNameArr[1]);
                  foreach ($options as $k => $v) {
                     $temp       = clone $value;
                     $temp->name =$preStr.':'.$v;
                     $data[] = $temp;
                  }
                }
              }
            
            default:
              break;
          }
        }

        return $this->returnApiJson('操作成功', 1,$data);
    }

    public function popList()
    {
       $input       = request()->all();
       if(!isset($input['type']) || !in_array($input['type'],[1,2])){
          return $this->returnApiJson(config('language')[$this->language]['error260'], 0);
       }
       $carrierPops = CarrierPop::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->where('type',$input['type'])->where('status',1)->orderBy('sort','desc')->get();
       return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$carrierPops);
    }
}