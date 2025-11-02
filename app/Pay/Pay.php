<?php

namespace App\Pay;

use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\ThirdPartPayCallBack;
use App\Models\CarrierBankCardType;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\PlayerBankCard;
use App\Models\Player;
use App\Models\Carrier;
use App\Models\Map\PayFactoryBankCode;
use App\Models\PlayerMessage;
use App\Models\PlayerReceiveGiftCenter;
use App\Jobs\ClearBetFlowLimitJob;
use App\Models\CarrierActivityGiftCode;
use App\Models\CarrierActivity;
use App\Models\Log\PlayerGiftCode;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\Log\PlayerLogin;
use App\Lib\Clog;

class Pay
{
    public $pay;
    public $paychannel;
    public $carrierPayChannelId;
    public $channelCode;
    public $carrier;
    public $ip;
    public $factoryId;
    public $thirdPartPay = [] ;

    public function __construct($paychannelidorcode)
    {
        $carrierPayChannel = CarrierPayChannel::select('def_pay_factory_list.code','def_pay_factory_list.id as factoryid','def_pay_factory_list.ip','conf_carrier_third_part_pay.id','def_pay_channel_list.channel_code','inf_carrier_pay_channel.carrier_id')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('inf_carrier_pay_channel.id',$paychannelidorcode)
            ->first();
            
        $carrier                   = Carrier::where('id',$carrierPayChannel->carrier_id)->first();
        $this->carrier             = $carrier;
        $this->carrierPayChannelId = $paychannelidorcode;
        $this->factoryId           = $carrierPayChannel->factoryid;
        $this->ip                  = $carrierPayChannel->ip;
        $this->channelCode         = $carrierPayChannel->channel_code;
        $platNamespace             = '\\App\\Pay\\'.$carrierPayChannel->code;

        if(is_null($this->carrierPayChannelId)){
            \Log::info('对不起，未绑定支付通道');
            exit;
        }

        if (class_exists($platNamespace)) {
            if ($this->paychannel === null) {
                $platClass        = new \ReflectionClass($platNamespace);
                $this->paychannel = $platClass->newInstanceArgs();
            }
            $this->getMerchant($carrierPayChannel->id);
        } else {
            \Log::info('对不起，此支付通道不存在'.$platNamespace);
            exit;
        }
    }

    // 获取商户信息
    public function getMerchant($payChannelId)
    {
        $carrierThirdPartPay = CarrierThirdPartPay::where(['id' => $payChannelId])->first();
        if (!empty($carrierThirdPartPay)) {
            $this->thirdPartPay['thirdPartPayId']     = $carrierThirdPartPay->id;
            $this->thirdPartPay['merchantNumber']     = $carrierThirdPartPay->merchant_number;
            $this->thirdPartPay['merchantBindDomain'] = $carrierThirdPartPay->merchant_bind_domain;
            $this->thirdPartPay['privateKey']         = $carrierThirdPartPay->private_key;
            $this->thirdPartPay['rsaPrivateKey']      = $carrierThirdPartPay->rsa_private_key;
            $this->thirdPartPay['rsaPublicKey']       = $carrierThirdPartPay->rsa_public_key;
            $this->thirdPartPay['merchantQueryDomain']= $carrierThirdPartPay->merchant_query_domain;
        }
    }

    // 支付发起申请1 - 获取加密参数 - url
    public function sendData($param)
    {
        if(!isset($param['bankCode'])){
            $param['bankCode'] = $this->channelCode;
        }

        $carrierThirdPartPay              = CarrierThirdPartPay::where(['id' => $this->thirdPartPay['thirdPartPayId']])->first();
        $carrierThirdPartPay->total_order = $carrierThirdPartPay->total_order + 1;
        $carrierThirdPartPay->save();
        
        return $this->paychannel->sendData($param, $this->thirdPartPay,$this->carrier);
    }

    //回调
    public function callback($input)
    {
        $allIps = [];

        if(!is_null($this->ip) && !empty($this->ip)){
            $subips = explode(',',$this->ip);
        }
        
        foreach ($subips as $k => $v) {
            $allIps[] = $v;
        }

        if(!in_array(real_ip(),$allIps)){
            return;
        }

        //特殊处理
        if(array_key_exists('diyserialize',$input)){
            $realArr            = explode('_',$input['diyserialize'],2);
            $input[$realArr[0]] = unserialize($realArr[1]);
            unset($input['diyserialize']);
        }

        $payOrderArr                                        = $this->paychannel->callback($input,$this->thirdPartPay);

        if(!$payOrderArr){
            return false;
        }

        $playerDepositPayLog                          = PlayerDepositPayLog::where('pay_order_number',$payOrderArr['orderNo'])->first();

        $str = '';

        foreach($input as $k => $v){

            if(is_array($v)){
                $str =$str.'diyserialize='.$k.'_'.serialize($v).'&';
            } else {
                $str =$str.$k.'='.$v.'&';
            }
        }

        $str = rtrim($str,'&');

        //入库
        $thirdPartPayCallBack                         = new ThirdPartPayCallBack();
        $thirdPartPayCallBack->carrier_id             = $playerDepositPayLog->carrier_id;
        $thirdPartPayCallBack->third_part_pay_id      = $this->thirdPartPay['thirdPartPayId'];
        $thirdPartPayCallBack->ip                     = real_ip();
        $thirdPartPayCallBack->type                   = 1;
        $thirdPartPayCallBack->url                    = config('main')['notifyUrl'].'/'.$this->carrierPayChannelId.'?'.$str;
        $thirdPartPayCallBack->orderid                = $payOrderArr['orderNo'];
        $thirdPartPayCallBack->save();

        if(!$payOrderArr['status']){
            return false;
        }

        if(!$playerDepositPayLog || $playerDepositPayLog->status !=0){
            return false;
        }
        
        $clearBetflowLimitAmount = CarrierCache::getCarrierConfigure($playerDepositPayLog->carrier_id,'clearbetflowlimitamount');
        $language                = CarrierCache::getLanguageByPrefix($playerDepositPayLog->prefix);

        if($payOrderArr) {
            try {
                \DB::beginTransaction();
                $playerAccount                                         = PlayerAccount::where('player_id',$playerDepositPayLog->player_id)->lockForUpdate()->first();
                $player                                                = Player::where('player_id',$playerDepositPayLog->player_id)->first();

                //清流水
                if($playerAccount->balance+$playerAccount->frozen < $clearBetflowLimitAmount*10000){
                    dispatch(new ClearBetFlowLimitJob($playerDepositPayLog));
                }

                $carrierThirdPartPay                                   = CarrierThirdPartPay::where('id',$this->thirdPartPay['thirdPartPayId'])->first();
                $carrierThirdPartPay->success_order                    = $carrierThirdPartPay->success_order + 1;
                $carrierThirdPartPay->save();

                //查询是否首充
                $existPlayerDepositPayLog                              =  PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->where('status',1)->first();
                if(!$existPlayerDepositPayLog){
                    $playerDepositPayLog->is_first_recharge            = 1;
                } else{
                    $playerDepositPayLog->is_first_recharge            = 0;
                }

                $playerDepositPayLog->review_time                      = time();
                $playerDepositPayLog->status                           = 1;
                $playerDepositPayLog->day                              = date('Ymd');
                $playerDepositPayLog->pay_order_channel_trade_number   = $payOrderArr['thirdOrderNo'];
                $playerDepositPayLog->save();

                //更新活动1+1活动金额
                $firstDepositActivityPlus = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'first_deposit_activity_plus',$playerDepositPayLog->prefix);
                if($firstDepositActivityPlus==$playerDepositPayLog->activityids){
                    $oneAndOneRechargeAmount = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'one_and_one_recharge_amount',$playerDepositPayLog->prefix);
                    $oneAndOneRechargeAmount += bcdiv($playerDepositPayLog->amount,10000,0);
                    CarrierMultipleFront::where('carrier_id',$playerDepositPayLog->carrier_id)->where('prefix',$playerDepositPayLog->prefix)->where('sign','one_and_one_recharge_amount')->update(['value'=>$oneAndOneRechargeAmount]);
                    CarrierCache::flushCarrierMultipleConfigure($playerDepositPayLog->carrier_id,$playerDepositPayLog->prefix);
                }

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
                $playerTransfer->project_id                      = $payOrderArr['orderNo'];
                $playerTransfer->mode                            = 1;
                $playerTransfer->day_m                           = date('Ym',time());
                $playerTransfer->day                             = date('Ymd',time());
                $playerTransfer->amount                          = $playerDepositPayLog->arrivedamount;
                $playerTransfer->type                            = 'recharge';
                $playerTransfer->type_name                       = config('language')[$language]['text37'];
                $playerTransfer->before_balance                  = $playerAccount->balance;
                $playerTransfer->balance                         = $playerAccount->balance + $playerDepositPayLog->arrivedamount;
                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;
                $playerTransfer->remark1                      = $playerDepositPayLog->amount;

                $playerTransfer->save();

                $playerAccount->balance                          = $playerAccount->balance + $playerDepositPayLog->arrivedamount;
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
                $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                $playerWithdrawFlowLimit->is_finished            = 0;
                $playerWithdrawFlowLimit->operator_id            = 0;
                $playerWithdrawFlowLimit->save();

                //发放体验券
                $enableSendVoucher        = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'enable_send_voucher',$playerDepositPayLog->prefix);
                $registerGiftCodeAmount   = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'register_gift_code_amount',$playerDepositPayLog->prefix);

                $playerDepositPayLogOne =  PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->where('id','!=',$playerDepositPayLog->id)->where('status',1)->sum('amount');
                $playerDepositPayLogTwo =  PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->where('status',1)->sum('amount');

                if(is_null($playerDepositPayLogOne)){
                    $playerDepositPayLogOne = 0;
                }

                if($enableSendVoucher && $playerDepositPayLogOne < $registerGiftCodeAmount*10000 && $playerDepositPayLogTwo >= $registerGiftCodeAmount*10000){
                    $sendVoucherNumber       = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'voucher_recharge_amount',$playerDepositPayLog->prefix);
                    $currPlayer              = Player::where('player_id',$playerDepositPayLog->parent_id)->first();
                    
                    //负盈利代理才发放
                    if($currPlayer->win_lose_agent){
                        $voucherMoney            = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'voucher_money',$playerDepositPayLog->prefix);
                        $voucherBetflowMultiple  = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'voucher_betflow_multiple',$playerDepositPayLog->prefix);
                        $voucherValidDay         = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'voucher_valid_day',$playerDepositPayLog->prefix);

                        $existGiftCodes = CarrierActivityGiftCode::where('carrier_id',$playerDepositPayLog->carrier_id)->pluck('gift_code')->toArray();
                        $giftCodes      = [];

                        for($i=1;$i<=$sendVoucherNumber;){
                            $giftCode = randGiftCode();
                            if(!in_array($giftCodes,$existGiftCodes) && !in_array($giftCode,$giftCodes)){
                                $giftCodes[] = $giftCode;
                                $i++;
                            }
                        }

                        $insertData                 = [];
                        $giftrowInsertData          = [];

                        foreach($giftCodes as $k => $v ){
                            $giftrow                       = [];
                            $giftrow['carrier_id']         = $playerDepositPayLog->carrier_id;
                            $giftrow['name']               = '充值发放体验券';
                            $giftrow['startTime']          = time();
                            $giftrow['endTime']            = strtotime(date('Y-m-d',strtotime('+'.$voucherValidDay.' days')).' 23:59:59');
                            $giftrow['gift_code']          = $v;
                            $giftrow['money']              = $voucherMoney;
                            $giftrow['betflowmultiple']    = $voucherBetflowMultiple;
                            $giftrow['distributestatus']   = 1;
                            $giftrow['created_at']         = date('Y-m-d H:i:s');
                            $giftrow['updated_at']         = date('Y-m-d H:i:s');
                            $giftrow['type']               = 2;
                            $giftrow['prefix']             = $playerDepositPayLog->prefix;
                            $giftrow['player_id']          = $playerDepositPayLog->parent_id;
                            $giftrowInsertData[]           = $giftrow;

                            $row                           = [];
                            $row['carrier_id']             = $playerDepositPayLog->carrier_id;
                            $row['player_id']              = $playerDepositPayLog->parent_id;
                            $row['gift_code']              = $v;
                            $row['money']                  = $voucherMoney;
                            $row['betflowmultiple']        = $voucherBetflowMultiple;
                            $row['endTime']                = strtotime(date('Y-m-d',strtotime('+'.$voucherValidDay.' days')).' 23:59:59');
                            $row['status']                 = 0;
                            $row['prefix']                 = $playerDepositPayLog->prefix;
                            $row['created_at']             = date('Y-m-d H:i:s');
                            $row['updated_at']             = date('Y-m-d H:i:s');
                            $insertData[]                  = $row; 
                        }

                        if(count($insertData)){
                            \DB::table('inf_player_hold_gift_code')->insert($insertData);
                        }

                        if(count($giftrowInsertData)){
                            \DB::table('inf_carrier_activity_gift_code')->insert($giftrowInsertData);
                        }
                    }
                }
                //发放体验券结束
                //更新充值
                PlayerGiftCode::where('player_id',$playerDepositPayLog->player_id)->update(['is_recharge'=>1]);

                //活动处理开始
                if(!empty($playerDepositPayLog->activityids)){
                    $survivalActivity               = CarrierActivity::where('id',$playerDepositPayLog->activityids)->where('prefix',$playerDepositPayLog->prefix)->first();
                    $time                           = time();
                    $rebateFinancialBonusesStepRate = json_decode($survivalActivity->rebate_financial_bonuses_step_rate_json,true);
                    $applyRuleString                = json_decode($survivalActivity->apply_rule_string,true);
                    
                    if($survivalActivity && $survivalActivity->status && $survivalActivity->endTime >= $time && $survivalActivity->startTime <= $time && $survivalActivity->apply_way==1){
                        $handselAmount      = 0;
                        $handselLimitAmount = 0;
                        $handselflag        = false;
                        if($survivalActivity->act_type_id==1){

                            //首存
                            if($playerDepositPayLog->is_first_recharge){
                                //判断是否多IP及相同指纹
                                $selfPlayer     = Player::where('player_id',$playerDepositPayLog->player_id)->first();
                                $loginIps       = PlayerLogin::where('player_id',$playerDepositPayLog->player_id)->pluck('login_ip')->toArray();
                                $fingerprints   = PlayerLogin::where('player_id',$playerDepositPayLog->player_id)->pluck('fingerprint')->toArray();
                                array_push($loginIps,$selfPlayer->register_ip);
                                $loginIps       = array_diff(array_unique($loginIps),['']);
                                $fingerprints   = array_diff(array_unique($fingerprints),['']);

                                $allLoginIps          = PlayerLogin::where('prefix',$selfPlayer->prefix)->where('player_id','!=',$playerDepositPayLog->player_id)->pluck('login_ip')->toArray();
                                $allLoginIps          = array_unique($allLoginIps);
                                $allFingerprints      = PlayerLogin::where('prefix',$selfPlayer->prefix)->where('player_id','!=',$playerDepositPayLog->player_id)->pluck('fingerprint')->toArray();
                                $allFingerprints      = array_unique($allFingerprints);
                                $moreIps  = false;

                                if(count(array_intersect($loginIps,$allLoginIps)) > 0 || count(array_intersect($fingerprints,$allFingerprints)) > 0){
                                    $moreIps  = true;
                                }
                                if($applyRuleString[0] == 'userfirstdepositamount'  && $playerDepositPayLog->amount >= $applyRuleString[2]*10000 && !$moreIps){
                                    //满足申请条件
                                    $handselflag = true;
                                    $flag        = array();
                                    foreach($rebateFinancialBonusesStepRate as $v) {
                                        $flag[] = $v['money'];
                                    }

                                    array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       
                                    
                                    foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                                        if($playerDepositPayLog->amount >= $v['money']*10000){
                                            if($survivalActivity->bonuses_type==2){
                                                if($playerDepositPayLog->is_wallet_recharge){
                                                    $handselAmount       = $v['give_special']*10000;
                                                } else{
                                                    $handselAmount       = $v['give']*10000;
                                                }
                                                
                                                $principal           = $v['money']*10000;
                                                //固定金额
                                                if($survivalActivity->gift_limit_method==1){
                                                    //本金加礼金
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1)+($playerDepositPayLog->amount-$principal)*$v['water']*2;
                                                } else{
                                                    //礼金
                                                    $handselLimitAmount = $handselAmount*$v['water'];
                                                }
                                            } elseif($survivalActivity->bonuses_type==1){
                                                if($playerDepositPayLog->is_wallet_recharge){
                                                    $handselAmount       = bcdiv($playerDepositPayLog->amount*$v['percent_special'],100,0);
                                                } else{
                                                    $handselAmount       = bcdiv($playerDepositPayLog->amount*$v['percent'],100,0);
                                                }
                                                
                                                if($handselAmount>$v['maxgive']*10000){
                                                    $handselAmount = $v['maxgive']*10000;
                                                }
                                                $principal           = $v['money']*10000;
                                                //百分比
                                                if($survivalActivity->gift_limit_method==1){
                                                    //本金加礼金
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1)+($playerDepositPayLog->amount-$principal)*$v['water']*2;
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
                            $existRecharge = PlayerTransfer::where('player_id',$playerDepositPayLog->player_id)->where('type','recharge')->where('day',date('Ymd'))->get();
                            if(count($existRecharge)==1){
                                if($applyRuleString[0] == 'todayfirstdepositamount'  && $playerDepositPayLog->amount >= $applyRuleString[2]*10000){
                                    //满足申请条件
                                    $handselflag = true;
                                    $flag        = array();
                                    foreach($rebateFinancialBonusesStepRate as $v) {
                                        $flag[] = $v['money'];
                                    }

                                    array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       
                                    
                                    foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                                        if($playerDepositPayLog->amount >= $v['money']*10000){
                                            if($survivalActivity->bonuses_type==2){
                                                if($playerDepositPayLog->is_wallet_recharge){
                                                    $handselAmount       = $v['give_special']*10000;
                                                } else{
                                                    $handselAmount       = $v['give']*10000;
                                                }
                                                $principal           = $v['money']*10000;
                                                //固定金额
                                                if($survivalActivity->gift_limit_method==1){
                                                    //本金加礼金
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1)+($playerDepositPayLog->amount-$principal)*$v['water']*2;
                                                } else{
                                                    //礼金
                                                    $handselLimitAmount = $handselAmount*$v['water'];
                                                }
                                            } elseif($survivalActivity->bonuses_type==1){
                                                if($playerDepositPayLog->is_wallet_recharge){
                                                    $handselAmount       = bcdiv($playerDepositPayLog->amount*$v['percent_special'],100,0);
                                                } else{
                                                    $handselAmount       = bcdiv($playerDepositPayLog->amount*$v['percent'],100,0);
                                                }
                                                
                                                if($handselAmount>$v['maxgive']*10000){
                                                    $handselAmount = $v['maxgive']*10000;
                                                }
                                                $principal           = $v['money']*10000;
                                                //百分比
                                                if($survivalActivity->gift_limit_method==1){
                                                    //本金加礼金
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1)+($playerDepositPayLog->amount-$principal)*$v['water']*2;
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
                                $existActivityCount = PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->where('status',1)->where('day',date('Ymd'))->where('activityids',$survivalActivity->id)->get();
                                if(count($existActivityCount) < 2){
                                    $tempflag = true;
                                }
                            } else if($survivalActivity->apply_times ==0){
                                $tempflag = true;
                            }

                            //充送
                            if($applyRuleString[0] == 'singledepositamount'  && $playerDepositPayLog->amount >= $applyRuleString[2]*10000 && $tempflag){
                                    //满足申请条件
                                $flag = array();
                                $handselflag = true;
                                foreach($rebateFinancialBonusesStepRate as $v) {
                                    $flag[] = $v['money'];
                                }

                                array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       
                                    
                                foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                                    if($playerDepositPayLog->amount >= $v['money']*10000){
                                        if($survivalActivity->bonuses_type==2){
                                            if($playerDepositPayLog->is_wallet_recharge){
                                                $handselAmount       = $v['give_special']*10000;
                                            } else{
                                                $handselAmount       = $v['give']*10000;
                                            }
                                            $principal           = $v['money']*10000;
                                            //固定金额
                                            if($survivalActivity->gift_limit_method==1){
                                                //本金加礼金
                                                $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1)+($playerDepositPayLog->amount-$principal)*$v['water']*2;
                                            } else{
                                                //礼金
                                                $handselLimitAmount = $handselAmount*$v['water'];
                                            }
                                        } elseif($survivalActivity->bonuses_type==1){
                                            if($playerDepositPayLog->is_wallet_recharge){
                                                $handselAmount       = bcdiv($playerDepositPayLog->amount*$v['percent_special'],100,0);
                                            } else{
                                                $handselAmount       = bcdiv($playerDepositPayLog->amount*$v['percent'],100,0);
                                            }
                                            if($handselAmount>$v['maxgive']*10000){
                                                $handselAmount = $v['maxgive']*10000;
                                            }
                                            $principal           = $v['money']*10000;
                                            //百分比
                                            if($survivalActivity->gift_limit_method==1){
                                                //本金加礼金
                                                $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1)+($playerDepositPayLog->amount-$principal)*$v['water']*2;
                                            } else{
                                                //礼金
                                                $handselLimitAmount = $handselAmount*$v['water'];
                                            }
                                        }
                                    }
                                }
                            }
                        } elseif($survivalActivity->act_type_id==7){
                            $allActivityids = PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->where('status',1)->where('day',date('Ymd'))->pluck('activityids')->toArray();
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
                            if($applyRuleString[0] == 'singledepositamount'  && $playerDepositPayLog->amount >= $applyRuleString[2]*10000 && $i==1){
                                //满足申请条件
                                $flag = array();
                                $handselflag = true;
                                foreach($rebateFinancialBonusesStepRate as $v) {
                                    $flag[] = $v['money'];
                                }

                                array_multisort($flag, SORT_ASC, $rebateFinancialBonusesStepRate);       
                                    
                                foreach ($rebateFinancialBonusesStepRate as $k => $v) {
                                    if($playerDepositPayLog->amount >= $v['money']*10000){
                                        if($survivalActivity->bonuses_type==2){
                                            if($playerDepositPayLog->is_wallet_recharge){
                                                $handselAmount       = $v['give_special']*10000;
                                            } else{
                                                $handselAmount       = $v['give']*10000;
                                            }
                                            $principal           = $v['money']*10000;
                                            //固定金额
                                            if($survivalActivity->gift_limit_method==1){
                                                //本金加礼金
                                                $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1)+($playerDepositPayLog->amount-$principal)*$v['water']*2;
                                            } else{
                                                //礼金
                                                $handselLimitAmount = $handselAmount*$v['water'];
                                            }
                                        } elseif($survivalActivity->bonuses_type==1){
                                            if($playerDepositPayLog->is_wallet_recharge){
                                                $handselAmount       = bcdiv($playerDepositPayLog->amount*$v['percent_special'],100,0);
                                            } else{
                                                $handselAmount       = bcdiv($playerDepositPayLog->amount*$v['percent'],100,0);
                                            }
                                            if($handselAmount>$v['maxgive']*10000){
                                                $handselAmount = $v['maxgive']*10000;
                                            }
                                            $principal           = $v['money']*10000;
                                            //百分比
                                            if($survivalActivity->gift_limit_method==1){
                                                //本金加礼金
                                                $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1)+($playerDepositPayLog->amount-$principal)*$v['water']*2;
                                            } else{
                                                //礼金
                                                $handselLimitAmount = $handselAmount*$v['water'];
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if($handselflag){
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
                            $activityArr                                              = explode(':',$survivalActivity->name);
                            $playerTransferActivity->remark1                          = $activityArr[0];

                            $playerTransferActivity->save();

                            $playerAccount->balance                                   = $playerTransferActivity->balance;
                            $playerAccount->save();

                            $playerWithdrawFlowLimit                                  = new PlayerWithdrawFlowLimit();
                            $playerWithdrawFlowLimit->carrier_id                      = $playerAccount->carrier_id;
                            $playerWithdrawFlowLimit->top_id                          = $playerAccount->top_id;
                            $playerWithdrawFlowLimit->parent_id                       = $playerAccount->parent_id;
                            $playerWithdrawFlowLimit->rid                             = $playerAccount->rid;
                            $playerWithdrawFlowLimit->player_id                       = $playerAccount->player_id;
                            $playerWithdrawFlowLimit->user_name                       = $playerAccount->user_name;
                            $playerWithdrawFlowLimit->limit_amount                    = $handselLimitAmount;
                            $playerWithdrawFlowLimit->betflow_limit_category          = $survivalActivity->betflow_limit_category;
                            $playerWithdrawFlowLimit->betflow_limit_main_game_plat_id = $survivalActivity->betflow_limit_main_game_plat_id;
                            $playerWithdrawFlowLimit->limit_type                      = 2;
                            $playerWithdrawFlowLimit->complete_limit_amount           = 0;
                            $playerWithdrawFlowLimit->is_finished                     = 0;
                            $playerWithdrawFlowLimit->operator_id                     = 0;
                            $playerWithdrawFlowLimit->save();

                            //记录申请人数与金额
                            $carrierActivityPerson                       = PlayerDepositPayLog::where('player_id',$playerAccount->player_id)->where('status',1)->where('activityids',$playerDepositPayLog->activityids)->count();
                            $currentCarrierActivitys                     = CarrierActivity::where('id',$playerDepositPayLog->activityids)->first();
                            if($carrierActivityPerson==1){
                                $currentCarrierActivitys->person_account  = $currentCarrierActivitys->person_account +1;//申请人数
                            }
                            
                            $currentCarrierActivitys->account            = $currentCarrierActivitys->account + 1;  //申请次数
                            $currentCarrierActivitys->gift_amount        = $currentCarrierActivitys->gift_amount + $handselAmount;  //礼金总金额
                            $currentCarrierActivitys->save();
                            //记录申请人数与金额结束
                        }
                    }
                }
                //活动处理结束
                $weekTime     = getWeekStartEnd();
               
                //充值成功通知
                $playerMessage                                   = new PlayerMessage();
                $playerMessage->carrier_id                       = $playerAccount->carrier_id;
                $playerMessage->player_id                        = $playerAccount->player_id;
                $playerMessage->type                             = 1;
                $playerMessage->title                            = config('main')['noticetemplate'][$language]['depositsuccess']['title'];
                $playerMessage->content                          = str_replace('amount',bcdiv($playerDepositPayLog->amount, 10000,0),str_replace('startTime',$playerDepositPayLog->created_at,config('main')['noticetemplate'][$language]['depositsuccess']['content']));
                $playerMessage->is_read                          = 0;
                $playerMessage->admin_id                         = 0;
                $playerMessage->save();
                
                \DB::commit();
                $this->paychannel->successNotice();
                   
            } catch (\Exception $e) {
                \DB::rollback();
                Clog::recordabnormal('充值回调异常:'.$e->getMessage());        
                return false;
            }
            
        } 
    }

    public function behalfCallback($input)
    {
        $allIps = [];

        if(!is_null($this->ip) && !empty($this->ip)){
            $subips = explode(',',$this->ip);
        }
        
        foreach ($subips as $k => $v) {
            $allIps[] = $v;
        }

        if(!in_array(real_ip(),$allIps)){
            return;
        }

        $flag   = $this->paychannel->behalfCallback($input,$this->thirdPartPay);

        $str = '';

        foreach($input as $k => $v){

            if(is_array($v)){
                $str =$str.'diyserialize='.$k.'_'.serialize($v).'&';
            } else {
                $str =$str.$k.'='.$v.'&';
            }
        }

        $str = rtrim($str,'&');

        //入库
        $thirdPartPayCallBack                         = new ThirdPartPayCallBack();
        $thirdPartPayCallBack->carrier_id             = $this->carrier->id;
        $thirdPartPayCallBack->third_part_pay_id      = $this->thirdPartPay['thirdPartPayId'];
        $thirdPartPayCallBack->ip                     = real_ip();
        $thirdPartPayCallBack->type                   = 2;
        $thirdPartPayCallBack->url                    = config('main')['behalfUrl'].'/'.$this->carrierPayChannelId.'?'.$str;
        $thirdPartPayCallBack->orderid                = $flag['orderNo'];
        $thirdPartPayCallBack->save();

        if($flag){
            $playerWithdraw  = PlayerWithdraw::where('pay_order_number',$flag['orderNo'])->first();

            if($flag['status'] == 'success' && in_array($playerWithdraw->status,[1,2])){
                $this->paychannel->successNotice();
            } else if($flag['status'] == 'success' && $playerWithdraw->status==5) {
                try {
                    \DB::beginTransaction();
                    $enableSafeBox                                   = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'enable_safe_box',$playerWithdraw->prefix);
                    $agentSingleBackground                           = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'agent_single_background',$playerWithdraw->prefix);
                    $playerAccount                                   = PlayerAccount::where('player_id',$playerWithdraw->player_id)->lockForUpdate()->first();
                    $player                                          = Player::where('player_id',$playerWithdraw->player_id)->first();
                    $language                                        = CarrierCache::getLanguageByPrefix($player->prefix);

                    $playerWithdraw->pay_order_channel_trade_number = $flag['thirdOrderNo'];
                    $playerWithdraw->arrival_time                   = time();
                    $playerWithdraw->status                         = 1;
                    $playerWithdraw->save();

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
                    $playerTransfer->mode                            = 2;
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->project_id                      = $flag['orderNo'];
                    $playerTransfer->amount                          = $playerWithdraw->amount;

                    $playerTransfer->type                            = 'withdraw_finish';
                    $playerTransfer->type_name                       = config('language')[$language]['text36'];
                    if(!empty($playerWithdraw->player_digital_address)){
                        $playerTransfer->remark                   = 1;
                    }

                    if($enableSafeBox || ($agentSingleBackground==1 &&  $playerWithdraw->is_agent==1)){
                        $playerTransfer->before_balance                  = $playerAccount->balance;
                        $playerTransfer->balance                         = $playerAccount->balance;
                        $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                        $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                        $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                        $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen-$playerWithdraw->amount;
                        $playerAccount->agentfrozen                   = $playerTransfer->agent_frozen_balance;

                        
                    } else{
                        $playerTransfer->before_balance                  = $playerAccount->balance;
                        $playerTransfer->balance                         = $playerAccount->balance;
                        $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                        $playerTransfer->frozen_balance                  = $playerAccount->frozen-$playerWithdraw->amount;

                        $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                        $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;
                        $playerAccount->frozen                        = $playerTransfer->frozen_balance;
                    }

                    $playerTransfer->save();
                    $playerAccount->save();

                    $playerMessage                                   = new PlayerMessage();
                    $playerMessage->carrier_id                       = $playerAccount->carrier_id;
                    $playerMessage->player_id                        = $playerAccount->player_id;
                    $playerMessage->type                             = 1;
                    $playerMessage->title                            = config('main')['noticetemplate'][$language]['withdrawsuccess']['title'];
                    $playerMessage->content                          = str_replace('amount',bcdiv($playerWithdraw->amount, 10000,0),str_replace('startTime',$playerWithdraw->created_at,config('main')['noticetemplate'][$language]['withdrawsuccess']['content']));
                    $playerMessage->is_read                          = 0;
                    $playerMessage->admin_id                         = 0;
                    $playerMessage->save();

                    //1+1活动提现统计
                    if($playerWithdraw->is_oneandone_withdrawal){
                        $oneAndOneWithdrawalAmount = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'one_and_one_withdrawal_amount',$playerWithdraw->prefix);
                        $oneAndOneWithdrawalAmount += bcdiv($playerWithdraw->amount,10000,0);
                        CarrierMultipleFront::where('carrier_id',$playerWithdraw->carrier_id)->where('prefix',$playerWithdraw->prefix)->where('sign','one_and_one_withdrawal_amount')->update(['value'=>$oneAndOneWithdrawalAmount]);
                        CarrierCache::flushCarrierMultipleConfigure($playerWithdraw->carrier_id,$playerWithdraw->prefix);
                    }

                    //活动提现统计
                    $recentPlayerDepositPay  = PlayerDepositPayLog::where('player_id',$playerWithdraw->player_id)->where('status',1)->where('created_at','<',$playerWithdraw->created_at)->orderBy('id','desc')->first();
                    if($recentPlayerDepositPay && !empty($recentPlayerDepositPay->activityids)){
                        $currCarrierActivity                   = CarrierActivity::where('id',$recentPlayerDepositPay->activityids)->first();
                        $currCarrierActivity->withdraw_amount  = $currCarrierActivity->withdraw_amount + $playerWithdraw->amount;
                        $currCarrierActivity->withdraw_account = $currCarrierActivity->withdraw_account + 1;
                        $currCarrierActivity->save();
                    } 
                    //活动提现统计结束 

                    //注册送提现统计
                    if($playerWithdraw->amount==1030000){
                        $existplayerTransfer =  playerTransfer::where('player_id',$playerWithdraw->player_id)->where('type','register_gift')->first();
                        if($existplayerTransfer){
                            $registerReceiveActivityid = CarrierCache::getCarrierMultipleConfigure($playerWithdraw->carrier_id,'register_receive_activityid',$playerWithdraw->prefix);
                            if($registerReceiveActivityid > 0){
                                $currCarrierActivity                   = CarrierActivity::where('id',$registerReceiveActivityid)->first();
                                $currCarrierActivity->withdraw_amount  = $currCarrierActivity->withdraw_amount + 1030000;
                                $currCarrierActivity->withdraw_account = $currCarrierActivity->withdraw_account + 1;
                                $currCarrierActivity->save();
                            }
                        }
                    }
                    //注册送提现统计结束                       
                    \DB::commit();
                    $this->paychannel->successNotice();
                } catch (\Exception $e) {
                    \DB::rollback();
                    Clog::recordabnormal('提现成功异常:'.$e->getMessage());    
                    return  returnApiJson($e->getMessage(), 0);;
                }
            } else if($flag['status'] == 'fail'){

                $playerWithdraw->pay_order_channel_trade_number = $flag['thirdOrderNo'];
                $playerWithdraw->status                         = -1;
                $playerWithdraw->save();

                $this->paychannel->successNotice();
            }
        }
    }

    public function paymentOnBehalf($withdraw)
    {
        //代付开始
        if($withdraw->type==0){
            $playerBankCard = PlayerBankCard::select('inf_carrier_bank_type.bank_name','inf_player_bank_cards.card_account','inf_player_bank_cards.card_owner_name')
              ->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')
              ->where('inf_player_bank_cards.id',$withdraw->player_bank_id)
              ->where('inf_carrier_bank_type.carrier_id',$withdraw->carrier_id)
             ->first();
        } else {
            $playerBankCard = null;
        }

        if($this->paychannel->transfer==1){
            //三方银行配对
            if(!empty($withdraw->player_bank_id) && $this->paychannel->transfer){
                $playerBankCard     = PlayerBankCard::select('inf_carrier_bank_type.bank_code')->where('inf_player_bank_cards.id',$withdraw->player_bank_id)->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')->first();
                $payFactoryBankCode = PayFactoryBankCode::where('pay_factory_id',$this->factoryId)->where('bank_code',$playerBankCard->bank_code)->first();

                if(!$payFactoryBankCode){
                    $withdraw->status                  = -1;
                    $withdraw->save();
                    return '对不起, 此代付通道不支持此银行！';
                } else{
                     $thirdOrderNo           = $this->paychannel->paymentOnBehalf($withdraw, $this->thirdPartPay,$playerBankCard,$this->carrierPayChannelId,$payFactoryBankCode->third_bank_code);
                }
            } else {
                $thirdOrderNo           = $this->paychannel->paymentOnBehalf($withdraw, $this->thirdPartPay,$playerBankCard,$this->carrierPayChannelId);
            }
        } else {
            $thirdOrderNo           = $this->paychannel->paymentOnBehalf($withdraw, $this->thirdPartPay,$playerBankCard,$this->carrierPayChannelId);
        }

        if($thirdOrderNo['status']=='fail'){
            //代付失败
            $withdraw->status                  = -1;
            $withdraw->save();

            if(isset($thirdOrderNo['message'])){
                return $thirdOrderNo['message'];
            } else{
                return false;
            }
            
        } else if($thirdOrderNo['status']=='submitsuccess'){
            //代付提交成功
            $withdraw->pay_order_channel_trade_number       = isset($thirdOrderNo['order'])?$thirdOrderNo['order']:'';
            $withdraw->status                               = 5;
            $withdraw->save();
            return true;
        } else if($thirdOrderNo['status']=='success') {
            //代付成功
            try {
                \DB::beginTransaction();
                $enableSafeBox                                   = CarrierCache::getCarrierMultipleConfigure($withdraw->carrier_id,'enable_safe_box',$withdraw->prefix);
                $agentSingleBackground                           = CarrierCache::getCarrierMultipleConfigure($withdraw->carrier_id,'agent_single_background',$withdraw->prefix);
                $playerAccount                                   = PlayerAccount::where('player_id',$withdraw->player_id)->lockForUpdate()->first();
                $player                                          = PlayerAccount::where('player_id',$withdraw->player_id)->first();
                $language                                        = CarrierCache::getLanguageByPrefix($player->prefix);

                $withdraw->pay_order_channel_trade_number       = $thirdOrderNo['order'];
                $withdraw->status                               = 1;
                $withdraw->arrival_time                         = time();
                $withdraw->save();

                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $withdraw->prefix;
                $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                $playerTransfer->rid                             = $playerAccount->rid;
                $playerTransfer->top_id                          = $playerAccount->top_id;
                $playerTransfer->parent_id                       = $playerAccount->parent_id;
                $playerTransfer->player_id                       = $playerAccount->player_id;
                $playerTransfer->is_tester                       = $playerAccount->is_tester;
                $playerTransfer->level                           = $playerAccount->level;
                $playerTransfer->user_name                       = $playerAccount->user_name;
                $playerTransfer->day_m                           = date('Ym',time());
                $playerTransfer->day                             = date('Ymd',time());
                $playerTransfer->project_id                      = $thirdOrderNo['order'];
                $playerTransfer->amount                          = $withdraw->amount;
                $playerTransfer->mode                            = 2;

                $playerTransfer->type                            = 'withdraw_finish';
                $playerTransfer->type_name                       = config('language')[$language]['text145'];

                if($enableSafeBox || ($agentSingleBackground==1 &&  $withdraw->is_agent==1)){
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen-$withdraw->amount;

                    $playerAccount->agentfrozen                   = $playerTransfer->agent_frozen_balance;
                } else{
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen-$withdraw->amount;

                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                    $playerAccount->frozen                        = $playerTransfer->frozen_balance;
                }

                if(!empty($withdraw->player_digital_address)){
                    $playerTransfer->remark = 1;
                }

                $playerTransfer->save();
                $playerAccount->save();

                //1+1活动提现统计
                if($withdraw->is_oneandone_withdrawal){
                    $oneAndOneWithdrawalAmount = CarrierCache::getCarrierMultipleConfigure($withdraw->carrier_id,'one_and_one_withdrawal_amount',$withdraw->prefix);
                    $oneAndOneWithdrawalAmount += bcdiv($withdraw->amount,10000,0);
                    CarrierMultipleFront::where('carrier_id',$withdraw->carrier_id)->where('prefix',$withdraw->prefix)->where('sign','one_and_one_withdrawal_amount')->update(['value'=>$oneAndOneWithdrawalAmount]);
                    CarrierCache::flushCarrierMultipleConfigure($withdraw->carrier_id,$withdraw->prefix);
                }

                //活动提现统计
                $recentPlayerDepositPay  = PlayerDepositPayLog::where('player_id',$withdraw->player_id)->where('status',1)->where('created_at','<',$withdraw->created_at)->orderBy('id','desc')->first();
                if($recentPlayerDepositPay && !empty($recentPlayerDepositPay->activityids)){
                    $currCarrierActivity                   = CarrierActivity::where('id',$recentPlayerDepositPay->activityids)->first();
                    $currCarrierActivity->withdraw_amount  = $currCarrierActivity->withdraw_amount + $withdraw->amount;
                    $currCarrierActivity->withdraw_account = $currCarrierActivity->withdraw_account + 1;
                    $currCarrierActivity->save();
                }
                //活动提现统计结束

                //注册送提现统计
                if($withdraw->amount==1030000){
                    $existplayerTransfer =  playerTransfer::where('player_id',$withdraw->player_id)->where('type','register_gift')->first();
                    if($existplayerTransfer){
                        $registerReceiveActivityid = CarrierCache::getCarrierMultipleConfigure($withdraw->carrier_id,'register_receive_activityid',$withdraw->prefix);
                        if($registerReceiveActivityid > 0){
                            $currCarrierActivity                   = CarrierActivity::where('id',$registerReceiveActivityid)->first();
                            $currCarrierActivity->withdraw_amount  = $currCarrierActivity->withdraw_amount + 1030000;
                            $currCarrierActivity->withdraw_account = $currCarrierActivity->withdraw_account + 1;
                            $currCarrierActivity->save();
                        }
                    }
                }
                //注册送提现统计结束      

                \DB::commit();                      
            } catch (\Exception $e) {
                \DB::rollback();    
                Clog::recordabnormal('提现成功异常'.$e->getMessage()); 
                return  '数据入库异常';
            }
            
            return true;
        }
    }
}