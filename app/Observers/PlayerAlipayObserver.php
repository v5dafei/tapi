<?php
namespace App\Observers;

use App\Models\PlayerAlipay;
use App\Models\Player;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerAccount;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\PlayerTransfer;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\CarrierActivityGiftCode;
use App\Models\Log\PlayerGiftCode;
use App\Models\PlayerHoldGiftCode;
use App\Lib\Cache\Lock;
use App\Models\CarrierActivity;
use App\Lib\Clog;
use App\Models\Log\AlipayStat;

class PlayerAlipayObserver
{
    public function created(PlayerAlipay $playerAlipay)
    {
        $player                           = Player::where('player_id',$playerAlipay->player_id)->first();
        //注册赚送彩金
        $isRegistergift                   = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'is_registergift',$player->prefix);
        $isBindBankCard                   = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'is_bindbankcardorthirdwallet',$player->prefix);
        $registerProbability              = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'register_probability',$player->prefix);
        $giftmultiple                     = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'giftmultiple',$player->prefix);
        $registergiftLimitDayNumber       = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'registergift_limit_day_number',$player->prefix);
        $registergiftLimitCycle           = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'registergift_limit_cycle',$player->prefix);
        
        $existRegisterGift                = PlayerTransfer::where('player_id',$player->player_id)->where('type','register_gift')->first();
        $existRecharge                    = PlayerTransfer::where('player_id',$player->player_id)->where('type','recharge')->first();

        if($player->is_tester ==0 && $player->type==2 && $isRegistergift && $isBindBankCard==1 && !$existRegisterGift && !$existRecharge){
                $seedProbability                 = rand(1,100);
                $registerProbabilityArr          = json_decode($registerProbability,true);
                $map                             = [];
                $preProbabilityArr               = 0;

                foreach ($registerProbabilityArr as $key => &$value) {
                    $preProbabilityArr                  = $value['register_gift_probability']+$preProbabilityArr;
                    $value['register_gift_probability'] = $preProbabilityArr;
                }

                foreach ($registerProbabilityArr as $key1 => $value1) {
                    if($seedProbability<=$value1['register_gift_probability']){
                        $giftAmount = rand($value1['giftamount'],$value1['giftmaxamount']);
                        break;
                    }
                }

                if($registergiftLimitCycle==1){
                    $sendRegisterGiftNumber =  PlayerTransfer::where('type','register_gift')->where('prefix',$player->prefix)->where('day',date('Ymd'))->count();
                } else{
                    $sendRegisterGiftNumber =  PlayerTransfer::where('type','register_gift')->where('prefix',$player->prefix)->count();
                }
                
                $existRecord            =  PlayerTransfer::where('player_id',$playerAlipay->player_id)->count();


                //同名的不送
                $sameRealNameCount = Player::where('prefix',$player->prefix)->where('real_name',$player->real_name)->count();

                //判断是否有在领了体验券不充值的银行卡列表中
                $bankStatFlag = AlipayStat::where('banknumber',$playerAlipay->account)->first();

                if($registergiftLimitDayNumber > $sendRegisterGiftNumber && !$existRecord && isset($giftAmount) && !$bankStatFlag &&  $sameRealNameCount==1){
                    //帐变记录
                    try {
                        \DB::beginTransaction();
                        $playerAccount                                   = PlayerAccount::where('player_id',$player->player_id)->lockForUpdate()->first();

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
                        $playerTransfer->type                            = 'register_gift';
                        $playerTransfer->type_name                       = '注册礼金';
                        $playerTransfer->day_m                           = date('Ym',time());
                        $playerTransfer->day                             = date('Ymd',time());
                        $playerTransfer->amount                          = $giftAmount*10000;

                        $playerTransfer->before_balance                  = $playerAccount->balance;
                        $playerTransfer->balance                         = $playerAccount->balance + $giftAmount*10000;
                        $playerTransfer->before_frozen_balance           = 0;
                        $playerTransfer->frozen_balance                  = 0;

                        $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                        $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                        $playerTransfer->save();

                        $playerWithdrawFlowLimit                                  = new PlayerWithdrawFlowLimit();
                        $playerWithdrawFlowLimit->carrier_id                      = $playerAccount->carrier_id;
                        $playerWithdrawFlowLimit->top_id                          = $playerAccount->top_id;
                        $playerWithdrawFlowLimit->parent_id                       = $playerAccount->parent_id;
                        $playerWithdrawFlowLimit->rid                             = $playerAccount->rid;
                        $playerWithdrawFlowLimit->player_id                       = $playerAccount->player_id;
                        $playerWithdrawFlowLimit->user_name                       = $playerAccount->user_name;
                        $playerWithdrawFlowLimit->limit_amount                    = $giftAmount*$giftmultiple*10000;
                        $playerWithdrawFlowLimit->limit_type                      = 16;
                        $playerWithdrawFlowLimit->save();

                        $playerReceiveGiftCenter                     = new PlayerReceiveGiftCenter();
                        $playerReceiveGiftCenter->orderid            = 'LJ'.$playerAccount->player_id.time().rand('1','99');
                        $playerReceiveGiftCenter->carrier_id         = $playerAccount->carrier_id;
                        $playerReceiveGiftCenter->player_id          = $playerAccount->player_id;
                        $playerReceiveGiftCenter->user_name          = $playerAccount->user_name;
                        $playerReceiveGiftCenter->top_id             = $playerAccount->top_id;
                        $playerReceiveGiftCenter->parent_id          = $playerAccount->parent_id;
                        $playerReceiveGiftCenter->rid                = $playerAccount->rid;
                        $playerReceiveGiftCenter->type               = 20;
                        $playerReceiveGiftCenter->amount             = $giftAmount*10000;
                        $playerReceiveGiftCenter->invalidtime        = time()+31536000;
                        $playerReceiveGiftCenter->limitbetflow       = $giftAmount*$giftmultiple*10000;
                        $playerReceiveGiftCenter->status             = 1;
                        $playerReceiveGiftCenter->receivetime        = time();
                        $playerReceiveGiftCenter->save();

                        $playerAccount->balance                      = $playerAccount->balance+$giftAmount*10000;
                        $playerAccount->save();

                        //加入注册送统计
                        $statCarrierActivity =  CarrierActivity::where('carrier_id',$playerAccount->carrier_id)->where('prefix',$player->prefix)->where('name','like','%注册%')->first();
                        if($statCarrierActivity){
                            $statCarrierActivity->person_account = $statCarrierActivity->person_account +1;
                            $statCarrierActivity->account        = $statCarrierActivity->account +1;
                            $statCarrierActivity->gift_amount    = $statCarrierActivity->gift_amount +$playerTransfer->amount;
                            $statCarrierActivity->save();
                        }

                        \DB::commit();
                    } catch (\Exception $e) {
                        \DB::rollback();
                        Clog::recordabnormal('用户'.$playerAccount->player_id.'注册赠送彩金发放失败'.$e->getMessage());  
                    }
                }     
        }
    }
}

