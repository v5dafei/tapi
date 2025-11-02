<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Lib\Cache\CarrierCache;
use App\Models\Log\PlayerSignIn;
use App\Models\Log\PlayerSignInReceive;
use App\Models\PlayerTransfer;
use App\Models\PlayerAccount;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Lib\Clog;

class SignInJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user      = null;
    public $carrier   = null;
    public $input     = null;

    public function __construct($user,$carrier,$input=null) {
        $this->user      = $user;
        $this->carrier   = $carrier;
        $this->input     = $input;
    }

    public function handle()
    {
        $this->SignIn();
    }

    public function SignIn()
    {   
       $signInCategory           = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'sign_in_category',$this->user->prefix);
       $signInDayGift            = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'sign_in_day_gift',$this->user->prefix);
       $signInFlowLimitMultiple  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'sign_in_flow_limit_multiple',$this->user->prefix);

       $signInDayGiftArr      = json_decode($signInDayGift,true);
       $signInDayGiftKey      = [];
       $signInDayGiftKeyValue = [];

       if(count($signInDayGiftArr)){
            foreach ($signInDayGiftArr as $key => $value) {
                $signInDayGiftKeyValue[$value['day']] = $value['money'];
                $signInDayGiftKey[]                   = $value['day'];
            }
       }

       $day            = date('Ymd');
       $gift           = 0;
       $signday        = 0;

       //写入签倒表
        if($signInCategory==1){
            //每日签倒
            $existPlayerSignInReceive = PlayerSignInReceive::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->first();
            if($existPlayerSignInReceive){
                return;
            }

            $gift    = $signInDayGiftKeyValue[1];
            $signday = 1;

        } elseif ($signInCategory==2) {

            // 月累积签倒
            $count                    = PlayerSignIn::where('player_id',$this->user->player_id)->where('day','>=',date('Ym01'))->count();
            if(!in_array($count, $signInDayGiftKey)){
                return;
            }
            $signday = $count;
            $gift    = $signInDayGiftKeyValue[$count];

            //已有领取记录
            $existPlayerSignInReceive = PlayerSignInReceive::where('player_id',$this->user->player_id)->where('day','>=',date('Ym01'))->where('day','<=',date('Ymd'))->where('number',$count)->first();
            if($existPlayerSignInReceive){
                return;
            }

        } elseif($signInCategory==3){
            $signday = $this->input['receiveday'];
            $gift    = $signInDayGiftKeyValue[$signday];

            try {
                \DB::beginTransaction();

                $playerSignIn              = new PlayerSignIn();
                $playerSignIn->carrier_id  = $this->carrier->id;
                $playerSignIn->player_id   = $this->user->player_id;
                $playerSignIn->user_name   = $this->user->user_name;
                $playerSignIn->prefix      = $this->user->prefix;
                $playerSignIn->day         = $day;

                $existPlayerSignIn         =  PlayerSignIn::where('player_id',$this->user->player_id)->orderby('day','desc')->first();
                if(!$existPlayerSignIn){
                    $playerSignIn->is_continuous = 1;
                } else{
                    $preDay = date('Ymd',strtotime('-1 day'));
                    if($preDay==$existPlayerSignIn->day){
                        $playerSignIn->is_continuous = 1;
                    } else{
                        PlayerSignIn::where('player_id',$this->user->player_id)->update(['is_continuous'=>0]);
                        $playerSignIn->is_continuous = 1;
                    }
                }
                
                $playerSignIn->save();

                //开始帐变
                $playerSignInReceive = PlayerSignInReceive::where('player_id',$this->user->player_id)->where('receiveday',$this->input['receiveday'])->first();
                $continuousCount     = PlayerSignIn::where('player_id',$this->user->player_id)->where('is_continuous',1)->count();
                if($playerSignInReceive || $continuousCount < $this->input['receiveday']){
                    \DB::commit(); 
                    return false;
                } else{

                    $playerAccount                              = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();

                    $playerSignInReceive                        = new PlayerSignInReceive();
                    $playerSignInReceive->carrier_id            = $this->user->carrier_id;
                    $playerSignInReceive->player_id             = $this->user->player_id;
                    $playerSignInReceive->amount                = $gift*10000;
                    $playerSignInReceive->number                = 1;
                    $playerSignInReceive->receiveday            = $this->input['receiveday'];
                    $playerSignInReceive->day                   = date('Ymd');
                    $playerSignInReceive->save();

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
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $playerSignInReceive->amount;
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                    $playerTransfer->type                            = 'signin_gift';
                    $playerTransfer->type_name                       = '签到礼金';
                    $playerTransfer->save();

                    $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                    $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                    $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                    $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                    $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                    $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;
                    $playerWithdrawFlowLimit->limit_amount           = $playerSignInReceive->amount*$signInFlowLimitMultiple;
                    $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                    $playerWithdrawFlowLimit->is_finished            = 0;
                    $playerWithdrawFlowLimit->operator_id            = 0;
                    $playerWithdrawFlowLimit->limit_type             = 22;
                    $playerWithdrawFlowLimit->save();

                    $playerAccount->balance = $playerAccount->balance + $playerTransfer->amount;
                    $playerAccount->save();

                    \DB::commit(); 
                }
            } catch (\Exception $e) {
                \DB::rollback(); 
                Clog::recordabnormal('用户签倒异常：:'.$e->getMessage());     
                return false;
            }    
        }
    }
}
