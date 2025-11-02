<?php
namespace App\Observers;

use App\Lib\Cache\SystemCache;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\FraudRecharge;
use App\Models\Log\PlayerLogin;
use App\Lib\Cache\CarrierCache;
use App\Models\Conf\CarrierPayChannel;
use App\Pay\Pay;
use App\Models\Player;

class PlayerWithdrawObserver
{
    public function created(PlayerWithdraw $playerWithdraw)
    {
        $ips                   = FraudRecharge::where('type',1)->pluck('ip')->toArray();
        $fingerprints          = FraudRecharge::where('type',2)->pluck('fingerprint')->toArray();
        $selfLoginIps          = PlayerLogin::where('player_id',$playerWithdraw->player_id)->pluck('login_ip')->toArray();
        $selfFingerprints      = PlayerLogin::where('player_id',$playerWithdraw->player_id)->pluck('fingerprint')->toArray();
        $intersectIps          = array_intersect($ips, $selfLoginIps);
        $intersectFingerprints = array_intersect($fingerprints, $selfFingerprints);
        if(count($intersectIps) > 0 || count($intersectFingerprints) > 0){
            $playerWithdraw->is_fraud_recharge = 1;
            $playerWithdraw->save();
        }
    }

    public function updated(PlayerWithdraw $playerWithdraw)
    {
        if($playerWithdraw->wasChanged('status') && $playerWithdraw->status == 4){
            $enableAutoPay      = CarrierCache::getCarrierConfigure($playerWithdraw->carrier_id,'enable_auto_pay');
            if($enableAutoPay==1){
                $autoPaySingleLimit = CarrierCache::getCarrierConfigure($playerWithdraw->carrier_id,'auto_pay_single_limit');
                $autoPayDayLimit    = CarrierCache::getCarrierConfigure($playerWithdraw->carrier_id,'auto_pay_day_limit'); 

                $amount = PlayerWithdraw::where('carrier_id',$playerWithdraw->carrier_id)->whereIn('status',[1,2])->where('is_auto_pay',1)->where('created_at','>=',date('Y-m-d').' 00:00:00')->where('created_at','<=',date('Y-m-d').' 23:59:59')->sum('amount');

                if($playerWithdraw->is_suspend==0 && $playerWithdraw->is_hedging_account==0 && bcdiv($playerWithdraw->amount,10000,0) <= $autoPaySingleLimit){
                    switch ($playerWithdraw->type) {
                        case '0':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','盛银代付')->first();
                            break;
                        case '3':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','okpay代付')->first();
                            break;
                        case '4':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','gopay代付')->first();
                            break;
                        case '6':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','topay代付')->first();
                            break;
                        case '7':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','ebet代付')->first();
                            break;
                        case '8':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','万币代付')->first();
                            break;
                        case '9':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','jdpay代付')->first();
                            break;
                        case '10':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','K豆代付')->first();
                            break;
                        case '12':
                            $carrierPayChannel = CarrierPayChannel::where('prefix',$playerWithdraw->prefix)->where('show_name','波币代付')->first();
                            break;
                                    
                        default:
                            // code...
                            break;
                    }

                    $player = Player::where('player_id',$playerWithdraw->player_id)->first();

                    if(isset($carrierPayChannel) && $carrierPayChannel && $player->is_forum_user==0){

                        $carrierPayChannel                        = CarrierPayChannel::select('inf_carrier_pay_channel.show_name','inf_carrier_pay_channel.id','inf_carrier_pay_channel.binded_third_part_pay_id','def_pay_channel_list.trade_rate','def_pay_channel_list.single_fee')
                            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
                            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
                            ->where('inf_carrier_pay_channel.carrier_id',$playerWithdraw->carrier_id)
                            ->where('inf_carrier_pay_channel.id',$carrierPayChannel->id)
                            ->first();

                        $pay                                          = new Pay($carrierPayChannel->id);
                        $playerWithdraw->pay                          = $carrierPayChannel->show_name;
                        $playerWithdraw->review_two_time              = time(); 
                        $playerWithdraw->payment_channel              = $carrierPayChannel->id;
                        $playerWithdraw->carrier_pay_channel          = $carrierPayChannel->id;   
                        $playerWithdraw->review_two_user_id           = 0;
                        $playerWithdraw->is_auto_pay                  = 1;
                        $playerWithdraw->third_part_pay_id            = $carrierPayChannel->binded_third_part_pay_id;

                        if(empty($playerWithdraw->player_alipay_id)){
                            $withdrawBankcardRatefee                      = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'withdraw_ratefee',$playerWithdraw->prefix);
                            
                        } else{
                            $withdrawBankcardRatefee                      = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'alipay_withdraw_ratefee',$playerWithdraw->prefix);
                        }

                        if($withdrawBankcardRatefee>0){
                            $playerWithdraw->third_fee                = bcdiv($playerWithdraw->amount * $withdrawBankcardRatefee,100,0);
                        } else{
                            $playerWithdraw->third_fee                = 0;
                        }

                        $playerWithdraw->status                   = 5;
                        $playerWithdraw->save();

                        $pay->paymentOnBehalf($playerWithdraw);

                    }
                }  elseif($playerWithdraw->is_suspend==0 && $playerWithdraw->is_hedging_account==1){
                    $playerWithdraw->status             = 2;
                    $playerWithdraw->review_two_time    = time();
                    $playerWithdraw->payment_channel    = '';
                    $playerWithdraw->carrier_pay_channel= 0;
                    $playerWithdraw->review_two_user_id = 0;
                    $playerWithdraw->is_auto_pay        = 1;
                    $playerWithdraw->third_part_pay_id  = 0;
                    $playerWithdraw->third_fee          = 0;
                    $playerWithdraw->pay                = '';
                    $playerWithdraw->remark             = '对冲号系统自动出款';
                    $playerWithdraw->arrival_time       = time();
                    $playerWithdraw->save();

                    PlayerWithdraw::successWithdraw($playerWithdraw);
                }
            }  
        }
    }

    public function deleted(PlayerWithdraw $playerWithdraw)
    {
        
    }
}