<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\BaseController;
use Illuminate\Auth\Authenticatable;
use App\Pay\Pay;
use App\Models\WhiteIp;
use App\Models\Log\DigitalCallback;
use App\Models\CarrierDigitalAddress;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerWithdraw;
use App\Models\PlayerMessage;
use App\Models\Player;
use App\Lib\Cache\Lock;
use App\Lib\Clog;

class PayController extends BaseController
{
    use Authenticatable;

    public function callback($carrierPayChannelId)
    {	
        $input = request()->all();

        if(!count($input)){
            $content =request()->getContent();
            $input   = json_decode($content,true);
        }

        $pay   = new Pay($carrierPayChannelId);
        $pay->callback($input);
    }

    public function behalfCallback($carrierPayChannelId)
    {
    	$input = request()->all();

        if(!count($input)){
            $content =request()->getContent();
            $input   = json_decode($content,true);
        }

        $pay   = new Pay($carrierPayChannelId);
        $pay->behalfCallback($input);
    }

    public function reverseCheck()
    {
        $input = request()->all();
        $ip    = real_ip();

        if(in_array($ip,config('main')['reversecheck']['gopay'])){
            if(isset($input['orderid']) && !empty($input['orderid'])){
                $playerWithdraw =  PlayerWithdraw::select('conf_carrier_third_part_pay.rsa_private_key','conf_carrier_third_part_pay.rsa_public_key')->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','log_player_withdraw.third_part_pay_id')->where('log_player_withdraw.pay_order_number',$input['orderid'])->first();
                if($playerWithdraw){
                    $sign = $input['sign'];
                    unset($input['sign']);
                    unset($input['note']);

                    $str = $input['sendid'].$input['orderid'].$input['amount'].$input['address'];

                    $newSign = md5($str.$playerWithdraw->rsa_private_key);

                    if($newSign == $sign){
                        $data =[
                            'code'=>1,
                            'retsign' => md5($sign.$playerWithdraw->rsa_public_key),
                            'msg' => 'success'
                        ];

                        echo json_encode($data);
                    }
                }
            }
        } elseif (in_array($ip,config('main')['reversecheck']['ebpay'])) {
            $playerWithdraw =  PlayerWithdraw::select('conf_carrier_third_part_pay.private_key')->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','log_player_withdraw.third_part_pay_id')->where('log_player_withdraw.pay_order_number',$input['merchantOrderId'])->first();
            if($playerWithdraw){
                $newSign = 'merchantNo='.$input['merchantNo'].'&merchantOrderId='.$input['merchantOrderId'].'&bankNum='.$input['bankNum'].'&payAmount='.$input['payAmount'].'&key='.$playerWithdraw->private_key;
                $newSign = md5( $newSign);
                if($newSign==$input['sign']){
                    $data =[
                        'code'=>200,
                        'msg' => '反查成功'
                    ];

                    echo json_encode($data);
                }
            }
        }
    }

    public function digitalCallback()
    {
        $input = request()->all();
        if(!count($input)){
            $content = request()->getContent();
            $input   = json_decode($content,true);
        }

        $header              = request()->header();
        $xTokenviewSignature = '';
        if(isset($header['x-tokenview-signature'])){
            $xTokenviewSignature = $header['x-tokenview-signature'];
            $xTokenviewSignature = $xTokenviewSignature[0];
            $sign                = hash_hmac('sha256', request()->getContent(),'a976cfbbda8ba9c338f33564eca14636137f6755490810bf625099f5d27ad305');
            
            if($sign == $xTokenviewSignature){
                if(count($input)){
                    $carrierDigitalAddress           =  CarrierDigitalAddress::where('address',$input['address'])->first();
                    if($carrierDigitalAddress){
                        $digitalCallback                 = new DigitalCallback();
                        $digitalCallback->carrier_id     = $carrierDigitalAddress->carrier_id;
                        $digitalCallback->tokenAddress   = $input['tokenAddress'];
                        $digitalCallback->address        = $input['address'];
                        $digitalCallback->tokenSymbol    = $input['tokenSymbol'];
                        $digitalCallback->txid           = $input['txid'];
                        $digitalCallback->confirmations  = $input['confirmations'];
                        $digitalCallback->tokenValue     = $input['tokenValue'];
                        $digitalCallback->value          = $input['value'];
                        $digitalCallback->coin           = $input['coin'];
                        $digitalCallback->height         = $input['height'];
                        $digitalCallback->save();

                        $digitalCallback = DigitalCallback::where('txid',$input['txid'])->where('address',$input['address'])->first();
                        if(!$digitalCallback){
                            $playerDepositPayLog   = PlayerDepositPayLog::where('carrier_id',$carrierDigitalAddress->carrier_id)->where('txid',$input['txid'])->where('status',0)->first();
                            if($playerDepositPayLog){
                                //处理成功信息
                                $cacheKey   = "player_" .$playerDepositPayLog->player_id;

                                $redisLock = Lock::addLock($cacheKey,60);

                                if (!$redisLock) {
                                    \Log::info('回调加锁异常');
                                    return returnApiJson("对不起,系统繁忙!", 0);
                                } else {
                                      $clearBetflowLimitAmount = CarrierCache::getCarrierConfigure($playerDepositPayLog->carrier_id,'clearbetflowlimitamount');
                                    try {
                                        \DB::beginTransaction();

                                        $playerAccount                                   = PlayerAccount::where('player_id',$playerDepositPayLog->player_id)->lockForUpdate()->first();
                                        $player                                          = Player::where('player_id',$playerDepositPayLog->player_id)->first();
                                        $language                                        = CarrierCache::getLanguageByPrefix($player->prefix);

                                        //清流水
                                        if($playerAccount->balance+$playerAccount->frozen < $clearBetflowLimitAmount*10000){
                                            dispatch(new ClearBetFlowLimitJob($playerDepositPayLog));
                                        }

                                        //查询是否首充
                                        $existPlayerDepositPayLog                        =  PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->where('status',1)->first();
                                        if(!$existPlayerDepositPayLog){
                                            $playerDepositPayLog->is_first_recharge      = 1;
                                        }

                                        $playerDepositPayLog->status                     = 1;
                                        $playerDepositPayLog->review_time                = time();
                                        $playerDepositPayLog->day                        = date('Ymd'); 
                                        $playerDepositPayLog->save();

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
                                        $playerTransfer->type                            = 'recharge';
                                        $playerTransfer->type_name                       = config('language')[$language]['text37'];
                                        $playerTransfer->project_id                      = $playerDepositPayLog->pay_order_number;
                                        $playerTransfer->day_m                           = date('Ym',time());
                                        $playerTransfer->day                             = date('Ymd',time());
                                        $playerTransfer->amount                          = $playerDepositPayLog->arrivedamount;
                                        $playerTransfer->before_balance                  = $playerAccount->balance;
                                        $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                                        $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                                        $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                                        $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                                        $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                                        $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                                        $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                                        $playerTransfer->remark1                         = $playerDepositPayLog->amount;

                                        $playerTransfer->save();

                                        $playerAccount->balance                          = $playerTransfer->balance;
                                        $playerAccount->save();

                                        $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                                        $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                                        $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                                        $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                                        $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                                        $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                                        $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;
                                        $playerWithdrawFlowLimit->limit_amount           = $playerDepositPayLog->arrivedamount;
                                        $playerWithdrawFlowLimit->limit_type             = 1;
                                        $playerWithdrawFlowLimit->save();

                                        $weekTime     = getWeekStartEnd();
                                        $weekStart    = $weekTime[0];
                                        $weekEnd      = $weekTime[1];


                                        //充值成功通知
                                        $playerMessage                                   = new PlayerMessage();
                                        $playerMessage->carrier_id                       = $playerAccount->carrier_id;
                                        $playerMessage->player_id                        = $playerAccount->player_id;
                                        $playerMessage->type                             = 1;
                                        $playerMessage->title                            = config('main')['noticetemplate'][$language]['depositsuccess']['title'];
                                        $playerMessage->content                          = str_replace('amount',bcdiv($playerDepositPayLog->arrivedamount, 10000,0),str_replace('startTime',$playerDepositPayLog->created_at,config('main')['noticetemplate'][$language]['depositsuccess']['content']));
                                        $playerMessage->is_read                          = 0;
                                        $playerMessage->admin_id                         = 0;
                                        $playerMessage->save();

                                        \DB::commit();
                                        Lock::release($redisLock);
                        
                                    } catch (\Exception $e) {
                                        \DB::rollback();
                                        Lock::release($redisLock);
                                        Clog::recordabnormal('数字币自动回调异常：'.$e->getMessage());   
                                    }
                                }
                                //处理成功结束
                            }
                        }
                    }

                }

                echo '回调成功';
            }
        }
    }
}
