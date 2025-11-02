<?php

namespace App\Http\Controllers\Web;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\BaseController;
use App\Models\TaskSetting;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\PlayerBreakThrough;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Lib\Cache\Lock;
use App\Lib\Clog;


class TaskController extends BaseController
{
    use Authenticatable;

    public function breakThroughList()
    {
        $gameCategorys = TaskSetting::select('game_category')->where('carrier_id',$this->carrier->id)->where('status',1)->where('prefix',$this->prefix)->groupBy('game_category')->get();
        $data          = [];

        foreach ($gameCategorys as $key => $value) {
            $list                                     = TaskSetting::where('carrier_id',$this->carrier->id)->where('status',1)->where('prefix',$this->prefix)->where('game_category',$value->game_category)->orderBy('sort','asc')->get();
            if($this->user){
                $betAmount                                = PlayerBetFlowMiddle::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->where('game_category',$value->game_category)->sum('process_available_bet_amount');
            } else{
                $betAmount                                = 0;
            }

            foreach ($list as $k => &$v) {
                $v->betAmount = $betAmount;
                if($v->available_bet_amount > $betAmount){
                    $v->status = 0;
                } else{
                    $playerBreakThrough =  PlayerBreakThrough::where('player_id',$this->user->player_id)->where('game_category',$v->game_category)->where('sort',$v->sort)->first();
                    if($playerBreakThrough){
                        $v->status  = 2;
                    } else{
                        $v->status  = 1;
                    }
                }

                switch ($v->game_category) {
                    case '1':
                        $v->title =config('language')[$this->language]['text161'];
                        break;
                    case '2':
                        $v->title =config('language')[$this->language]['text162'];
                        break;
                    case '3':
                        $v->title =config('language')[$this->language]['text163'];
                        break;
                    case '4':
                        $v->title =config('language')[$this->language]['text164'];
                        break;
                    case '5':
                        $v->title =config('language')[$this->language]['text165'];
                        break;
                    case '6':
                        $v->title =config('language')[$this->language]['text166'];
                        break;
                    case '7':
                        $v->title =config('language')[$this->language]['text167'];
                        break;
                    
                    default:
                        // code...
                        break;
                }
                $v->title        = $v->title.' ('.$v->giftmultiple.config('language')[$this->language]['text167'].')';
                $v->completeness = bcdiv($betAmount,$v->available_bet_amount,4) >1 ? 100: bcdiv($betAmount,$v->available_bet_amount,4)*100; 
            }

            $row = [];
            $row['type'] = $value->game_category;
            $row['item'] = $list;

            $data [] = $row;
        }

        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function receiveBreakThroughGift()
    {
        $input = request()->all();
        if(!isset($input['game_category']) || empty($input['game_category'])){
            return $this->returnApiJson(config('language')[$this->language]['error421'], 0);
        }

        if(!isset($input['sort']) || empty($input['sort'])){
            return $this->returnApiJson(config('language')[$this->language]['error422'], 0);
        }

        $taskSetting = TaskSetting::where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->where('game_category',$input['game_category'])->where('sort',$input['sort'])->first();
        if(!$taskSetting){
            return $this->returnApiJson(config('language')[$this->language]['error423'], 0);
        }

        $playerBreakThrough = PlayerBreakThrough::where('player_id',$this->user->player_id)->where('game_category',$input['game_category'])->where('sort',$input['sort'])->where('day',date('Ymd'))->first();
        if($playerBreakThrough){
            return $this->returnApiJson(config('language')[$this->language]['error424'], 0);
        }

        $betAmount          = PlayerBetFlowMiddle::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->where('game_category',$input['game_category'])->sum('process_available_bet_amount');

        if($betAmount >= $taskSetting->available_bet_amount){
            $cacheKey = 'player_'.$this->user->player_id;
            $redisLock = Lock::addLock($cacheKey,60);

            if(!$redisLock) {
                return returnApiJson(config('language')[$this->language]['error20'], 0);
            } else {
                try{
                    \DB::beginTransaction();

                    $playerAccount                                   = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();
                                    
                    $playerTransfer                                  = new PlayerTransfer();
                    $playerTransfer->prefix                          = $this->user->prefix;
                    $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                    $playerTransfer->rid                             = $playerAccount->rid;
                    $playerTransfer->top_id                          = $playerAccount->top_id;
                    $playerTransfer->parent_id                       = $playerAccount->parent_id;
                    $playerTransfer->player_id                       = $playerAccount->player_id;
                    $playerTransfer->is_tester                       = $playerAccount->is_tester;
                    $playerTransfer->level                           = $playerAccount->level;
                    $playerTransfer->user_name                       = $playerAccount->user_name;
                    $playerTransfer->mode                            = 1;

                    switch ($input['game_category']) {
                        case '1':
                            $playerTransfer->type                            = 'video_break_through_gift';
                            $playerTransfer->type_name                       = config('language')['zh']['text119'];
                            $playerTransfer->en_type_name                    = config('language')['en']['text119'];
                            break;
                        case '2':
                            $playerTransfer->type                            = 'electronic_break_through_gift';
                            $playerTransfer->type_name                       = config('language')['zh']['text120'];
                            $playerTransfer->en_type_name                    = config('language')['en']['text120'];
                            break;
                        case '3':
                            $playerTransfer->type                            = 'esport_break_through_gift';
                            $playerTransfer->type_name                       = config('language')['zh']['text121'];
                            $playerTransfer->en_type_name                    = config('language')['en']['text121'];
                            break;
                        case '4':
                            $playerTransfer->type                            = 'card_break_through_gift';
                            $playerTransfer->type_name                       = config('language')['zh']['text122'];
                            $playerTransfer->en_type_name                    = config('language')['en']['text122'];
                            break;
                        case '5':
                            $playerTransfer->type                            = 'sport_break_through_gift';
                            $playerTransfer->type_name                       = config('language')['zh']['text123'];
                            $playerTransfer->en_type_name                    = config('language')['en']['text123'];
                            break;
                        case '6':
                            $playerTransfer->type                            = 'lottery_break_through_gift';
                            $playerTransfer->type_name                       = config('language')['zh']['text124'];
                            $playerTransfer->en_type_name                    = config('language')['en']['text124'];
                            break;
                        case '7':
                            $playerTransfer->type                            = 'fish_break_through_gift';
                            $playerTransfer->type_name                       = config('language')['zh']['text125'];
                            $playerTransfer->en_type_name                    = config('language')['en']['text125'];
                            break;
                        default:
                            // code...
                            break;
                    }

                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $taskSetting->amount*10000;
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;;
                    $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;;
                    $playerTransfer->save();

                    $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                    $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                    $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                    $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                    $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                    $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;

                    switch ($input['game_category']) {
                        case '1':
                            $playerWithdrawFlowLimit->limit_type             = 30;
                            break;
                        case '2':
                            $playerWithdrawFlowLimit->limit_type             = 31;
                            break;
                        case '3':
                            $playerWithdrawFlowLimit->limit_type             = 32;
                            break;
                        case '4':
                            $playerWithdrawFlowLimit->limit_type             = 33;
                            break;
                        case '5':
                            $playerWithdrawFlowLimit->limit_type             = 34;
                            break;
                        case '6':
                            $playerWithdrawFlowLimit->limit_type             = 35;
                            break;
                        case '7':
                            $playerWithdrawFlowLimit->limit_type             = 36;
                            break;
                        default:
                            // code...
                            break;
                    }

                    $playerWithdrawFlowLimit->limit_amount           = $playerTransfer->amount*$taskSetting->giftmultiple;
                    $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                    $playerWithdrawFlowLimit->is_finished            = 0;
                    $playerWithdrawFlowLimit->operator_id            = 0;
                    $playerWithdrawFlowLimit->save();

                    $playerBreakThrough                              = new PlayerBreakThrough();
                    $playerBreakThrough->carrier_id                  = $playerAccount->carrier_id;
                    $playerBreakThrough->prefix                      = $this->user->prefix;
                    $playerBreakThrough->top_id                      = $this->user->top_id;
                    $playerBreakThrough->parent_id                   = $this->user->parent_id;
                    $playerBreakThrough->rid                         = $this->user->rid;
                    $playerBreakThrough->player_id                   = $this->user->player_id;
                    $playerBreakThrough->user_name                   = $this->user->user_name;
                    $playerBreakThrough->game_category               = $input['game_category'];
                    $playerBreakThrough->day                         = date('Ymd');
                    $playerBreakThrough->limit_amount                = $taskSetting->amount*10000*$taskSetting->giftmultiple;
                    $playerBreakThrough->amount                      = $taskSetting->amount*10000;
                    $playerBreakThrough->sort                        = $taskSetting->sort;
                    $playerBreakThrough->save();

                    $playerAccount->balance                          = $playerTransfer->balance;
                    $playerAccount->save();

                    \DB::commit();
                    Lock::release($redisLock);
                    return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($playerAccount->balance,10000,2)]);
                } catch(\Exception $e){
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('领取闯关礼金异常:'.$e->getMessage());   
                    return $this->returnApiJson($e->getMessage(), 0);
                }
            }
        } else{
            return $this->returnApiJson(config('language')[$this->language]['error425'], 0);
        }
    }
}