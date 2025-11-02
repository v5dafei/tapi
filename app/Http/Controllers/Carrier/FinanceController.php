<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Carrier\BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Authenticatable;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerWithdraw;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Def\PayChannel;
use App\Models\PlayerTransfer;
use App\Models\PlayerAccount;
use App\Models\Def\Development;
use App\Lib\Cache\CarrierCache;
use App\Models\Def\ThirdWallet;
use App\Models\Log\PlayerBetFlow;
use App\Models\ArbitrageBank;
use App\Models\Def\PayFactory;
use App\Models\PlayerMessage;
use App\Models\Player;
use App\Pay\Pay;
use App\Lib\Cache\PlayerCache;
use App\Models\CarrierActivityGiftCode;
use App\Models\CarrierActivity;
use App\Models\Log\PlayerGiftCode;
use App\Lib\Cache\Lock;
use App\Models\Def\DigitalAddressLib;
use App\Models\Def\Game;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\ArbitrageAlipay;
use App\Models\PlayerAlipay;
use App\Lib\Clog;

class FinanceController extends BaseController
{
    use Authenticatable;

    public function depositList()
    {
    	$res = PlayerDepositPayLog::depositList($this->carrier);
    	if(is_array($res)) {
    		return $this->returnApiJson('操作成功', 1, $res);
    	} else {
    		return $this->returnApiJson($res, 0);
    	}
    }

    public function depositCollect()
    {
        $res = PlayerDepositPayLog::depositCollect($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function giftList()
    {
        $res = Player::giftList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function giftTypelist()
    {
        $res = Player::giftTypelist();
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function redbagList()
    {
        $res = Player::redbagList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function depositStat()
    {
        $res = PlayerDepositPayLog::depositStat($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function arbitrageBankAdd($withdrawId)
    {
        $playerWithdraw = PlayerWithdraw::where('carrier_id',$this->carrier->id)->where('id',$withdrawId)->first();
        if(!$playerWithdraw){
            return $this->returnApiJson('对不起，此条数据不存在', 0);
        } elseif(in_array($playerWithdraw->type,[3,4,6])){
            $digitalAddressLib = DigitalAddressLib::where('type',$playerWithdraw->type)->where('address',$playerWithdraw->player_digital_address)->first();
            if(!$digitalAddressLib){
                return returnApiJson('对不起，此钱包不存在', 0);
            } else{
                $digitalAddressLib->is_arbitrage   = 1;
                $digitalAddressLib->save();

                return $this->returnApiJson('操作成功', 1);
            }
        } elseif(!empty($playerWithdraw->player_alipay_id)){
            $playerAlipay = PlayerAlipay::where('address',$playerWithdraw->player_alipay_id)->first();
            if(!$playerAlipay){
                return returnApiJson('对不起，此支付宝不存在', 0);
            } else{
                $arbitrageAlipay              = new ArbitrageAlipay();
                $arbitrageAlipay->real_name   = $playerAlipay->real_name;
                $arbitrageAlipay->account     = $playerAlipay->account;
                $arbitrageAlipay->save();

                return $this->returnApiJson('操作成功', 1);
            }

        } else{
            $collectionArr = explode('|',$playerWithdraw->collection);

            $existArbitrageBank = ArbitrageBank::where('bank_name',$collectionArr[0])->where('card_account',$collectionArr[1])->first();
            if($existArbitrageBank){
                return $this->returnApiJson('对不起，此条数据已存在', 0);
            }
            $arbitrageBank      = new  ArbitrageBank();

            if(!empty($playerWithdraw->player_digital_address)){
                $arbitrageBank->bank_name     =  $collectionArr[0];
                $arbitrageBank->card_account  =  $collectionArr[1];
                $arbitrageBank->save();
            } else {
                $arbitrageBank->bank_name        =  $collectionArr[0];
                $arbitrageBank->card_account     =  $collectionArr[1];
                $arbitrageBank->card_owner_name  =  $collectionArr[2];
                $arbitrageBank->save();
            }
             return $this->returnApiJson('操作成功', 1);
        }
    }

    public function arbitrageBankaList()
    {
        $res = ArbitrageBank::arbitrageBankaList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function allThirdWallet()
    {
        $input         = request()->all();
        if(isset($input['currency'])){
            $thiredWallets = ThirdWallet::where('currency',$input['currency'])->orWhere('currency','USD')->get();
        } else{
            $thiredWallets = ThirdWallet::where('currency','CNY')->orWhere('currency','USD')->get();
        }
        return $this->returnApiJson('操作成功', 1, $thiredWallets);
    }

    public function depositAuditList()
    {
        $res = PlayerDepositPayLog::depositAuditList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function depositAudit($depositPayId)
    {
        $input               = request()->all();
        $playerDepositPayLog = PlayerDepositPayLog::where('id',$depositPayId)->where('carrier_id',$this->carrier->id)->first();

        if(!$playerDepositPayLog) {
            return $this->returnApiJson('对不起，订单号不存在', 0);
        }

        if(!isset($input['amount']) || !is_numeric($input['amount']) || $input['amount']<0) {
            return $this->returnApiJson('对不起，金额取值不正确', 0);
        }

        if($playerDepositPayLog->status!=0 && $playerDepositPayLog->status!=2){
            return $this->returnApiJson('对不起，对不起此订单已处理，不能重复处理', 0);
        }

        if(!isset($input['status']) || !in_array($input['status'], [1,-1,-2])) {
            return $this->returnApiJson('对不起，审核状态取值不正确', 0);
        }

        if(!isset($input['desc'])) {
            $input['desc']='';
        }

        $language                 = CarrierCache::getLanguageByPrefix($playerDepositPayLog->prefix);

         $cacheKey   = "player_" .$playerDepositPayLog->player_id;
         $redisLock = Lock::addLock($cacheKey,60);


        if (!$redisLock) {
            return $this->returnApiJson("对不起,系统繁忙!", 0);
        } else {
            try {
                \DB::beginTransaction();

                if($input['status']==1){
                    if($input['amount']*10000 != $playerDepositPayLog->amount){
                        
                        $playerDepositPayLog->arrivedamount =  bcdiv($playerDepositPayLog->arrivedamount*$input['amount']*10000,$playerDepositPayLog->amount,0);
                        $playerDepositPayLog->third_fee     =  bcdiv($playerDepositPayLog->third_fee*$input['amount']*10000,$playerDepositPayLog->amount,0);
                        
                        $playerDepositPayLog->amount        = $input['amount']*10000;
                        if(strpos($playerDepositPayLog->pay, 'USDT') !== false){
                            $playerDepositPayLog->pay       = bcdiv(floatval(str_replace('USDT','',$playerDepositPayLog->pay))*$input['amount']*10000,$playerDepositPayLog->amount,2).'USDT';
                        } elseif(strpos($playerDepositPayLog->pay, 'CNY') !== false){
                            $playerDepositPayLog->pay       = bcdiv(floatval(str_replace('CNY','',$playerDepositPayLog->pay))*$input['amount']*10000,$playerDepositPayLog->amount,2).'CNY';
                        }
                    }

                    //查询是否首充
                    $existPlayerDepositPayLog                             =  PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->where('status',1)->first();
                    if(!$existPlayerDepositPayLog){
                        $playerDepositPayLog->is_first_recharge           = 1;
                    } else{
                        $playerDepositPayLog->is_first_recharge           = 0;
                    }

                    $playerDepositPayLog->status =1;
                    $playerDepositPayLog->review_user_id             = $this->carrierUser->id;
                    $playerDepositPayLog->review_time                = time();
                    $playerDepositPayLog->day                        = date('Ymd'); 
                    $playerDepositPayLog->save();

                    //更新活动1+1活动金额
                    $firstDepositActivityPlus = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'first_deposit_activity_plus',$playerDepositPayLog->prefix);
                    if($firstDepositActivityPlus==$playerDepositPayLog->activityids){
                        $oneAndOneRechargeAmount = CarrierCache::getCarrierMultipleConfigure($playerDepositPayLog->carrier_id,'one_and_one_recharge_amount',$playerDepositPayLog->prefix);
                        $oneAndOneRechargeAmount += bcdiv($playerDepositPayLog->amount,10000,0);
                        CarrierMultipleFront::where('carrier_id',$playerDepositPayLog->carrier_id)->where('prefix',$playerDepositPayLog->prefix)->where('sign','one_and_one_recharge_amount')->update(['value'=>$oneAndOneRechargeAmount]);
                        CarrierCache::flushCarrierMultipleConfigure($playerDepositPayLog->carrier_id,$playerDepositPayLog->prefix);
                    }

                    $playerAccount                                   = PlayerAccount::where('player_id',$playerDepositPayLog->player_id)->lockForUpdate()->first();
                    $player                                          = Player::where('player_id',$playerDepositPayLog->player_id)->first();

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
                    $playerTransfer->project_id                      = $playerDepositPayLog->pay_order_number;
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $playerDepositPayLog->arrivedamount;
                    $playerTransfer->type                            = 'recharge';
                    $playerTransfer->type_name                       = config('language')[$language]['text37'];
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
                            if($survivalActivity->act_type_id==1){
                                //首存
                                $existRecharge = PlayerTransfer::where('player_id',$playerDepositPayLog->player_id)->where('type','recharge')->get();
                                if(count($existRecharge)==1){
                                    if($applyRuleString[0] == 'userfirstdepositamount'  && $playerDepositPayLog->amount >= $applyRuleString[2]*10000){
                                        //满足申请条件
                                        $flag = array();
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
                                                        $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
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
                                                    $principal           = $playerDepositPayLog->amount;
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
                                $existRecharge = PlayerTransfer::where('player_id',$playerDepositPayLog->player_id)->where('type','recharge')->where('day',date('Ymd'))->get();
                                if(count($existRecharge)==1){
                                    if($applyRuleString[0] == 'todayfirstdepositamount'  && $playerDepositPayLog->amount >= $applyRuleString[2]*10000){
                                        //满足申请条件
                                        $flag = array();
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
                                                        $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
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
                                                    $principal           = $playerDepositPayLog->amount;
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
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
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
                                                $principal           = $playerDepositPayLog->amount;
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
                                                    $handselLimitAmount = $handselAmount*$v['water']+$principal*($v['water']-1);
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
                                                $principal           = $playerDepositPayLog->amount;
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
                            $activityArr                                              =  explode(':',$survivalActivity->name);
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
                            $currentCarrierActivitys                     = CarrierActivity::where('id',$playerDepositPayLog->activityids)->first();

                            $carrierActivityPerson                       =  PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->where('status',1)->where('activityids',$playerDepositPayLog->activityids)->count();
                            if($carrierActivityPerson==1){
                                $currentCarrierActivitys->person_account  = $currentCarrierActivitys->person_account +1;//申请人数
                            }
                            
                            $currentCarrierActivitys->account            = $currentCarrierActivitys->account + 1;  //申请次数
                            $currentCarrierActivitys->gift_amount        = $currentCarrierActivitys->gift_amount + $handselAmount;  //礼金总金额
                            $currentCarrierActivitys->save();

                            //记录申请人数与金额结束
                        }
                    }
                    //活动处理结束
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
                } else if($input['status']==-2) {
                    $playerDepositPayLog->status =-2;
                    $playerDepositPayLog->review_user_id     = $this->carrierUser->id;
                    $playerDepositPayLog->review_time        = time();
                    $playerDepositPayLog->desc               = $input['desc'];
                    $playerDepositPayLog->day                = date('Ymd');  
                    $playerDepositPayLog->save();
                } else if($input['status']==-1){
                    $playerDepositPayLog->status             =-1;
                    $playerDepositPayLog->desc               = $input['desc'];
                    $playerDepositPayLog->review_user_id     = $this->carrierUser->id;
                    $playerDepositPayLog->review_time        = time();
                    $playerDepositPayLog->day                = date('Ymd');  
                    $playerDepositPayLog->save();
                }

                \DB::commit();
                Lock::release($redisLock);
                return $this->returnApiJson('操作成功', 1);
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('存款审核异常：'.$e->getMessage());   
                return $this->returnApiJson('对不起，系统异常'.$e->getMessage(), 0);
            }
        }
    }

    public function withdrawCancel($playerWithdrawId)
    {

        $input          = request()->all();
        $playerWithdraw = PlayerWithdraw::where('carrier_id',$this->carrier->id)->where('id',$playerWithdrawId)->first();

        if($playerWithdraw->status==7){
            return $this->returnApiJson('对不起,此订单不能重复取消', 0);
        }

        if(!isset($input['type']) || !in_array($input['type'],[0,1])){
            return $this->returnApiJson('对不起,是否扣除盈余取值不正确', 0);
        }

        if(!isset($input['flowtype']) || !in_array($input['flowtype'],[0,1,2,3])){
            return $this->returnApiJson('对不起,流水处理取值不正确', 0);
        }

        if($playerWithdraw && in_array($playerWithdraw->status, [1,2,5])){
            return $this->returnApiJson('对不起,此订单无法取消', 0);
        } else {
            //拉入点杀
            $cacheKey   = "player_" .$playerWithdraw->player_id;
            $redisLock = Lock::addLock($cacheKey,60);

            if (!$redisLock) {
            return $this->returnApiJson("对不起,系统繁忙!", 0);
            } else {
                try {
                    \DB::beginTransaction();

                    $playerAccount                                   = PlayerAccount::where('player_id',$playerWithdraw->player_id)->lockForUpdate()->first();
                    $player                                          = Player::where('player_id',$playerWithdraw->player_id)->first();
                    $enableSafeBox                                   = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'enable_safe_box',$player->prefix);
                    $agentSingleBackground                           = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'agent_single_background',$player->prefix);
                    $language                                        = CarrierCache::getLanguageByPrefix($player->prefix);

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
                    $playerTransfer->mode                            = 3;
                    $playerTransfer->project_id                      = $playerWithdraw->pay_order_number;
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $playerWithdraw->amount;

                    $playerTransfer->type                            = 'withdraw_cancel';
                    $playerTransfer->type_name                       = '取消提现';

                    if($enableSafeBox || ($agentSingleBackground==1 &&  $playerWithdraw->is_agent==1)){
                        $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                   = $playerAccount->agentbalance + $playerTransfer->amount;
                        $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen - $playerTransfer->amount;
                        $playerTransfer->before_balance                  = $playerAccount->balance;
                        $playerTransfer->balance                         = $playerAccount->balance;
                        $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                        $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                        $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                        $playerAccount->agentfrozen                      = $playerTransfer->agent_frozen_balance;
                    } else{
                        $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                        $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                        $playerTransfer->before_balance                  = $playerAccount->balance;
                        $playerTransfer->balance                         = $playerAccount->balance  + $playerTransfer->amount;
                        $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                        $playerTransfer->frozen_balance                  = $playerAccount->frozen - $playerTransfer->amount;
                        $playerAccount->balance                          = $playerTransfer->balance;
                        $playerAccount->frozen                           = $playerTransfer->frozen_balance;
                    }

                    $playerTransfer->save();


                    $playerAccount->save();
                   
                    $playerWithdraw->status  = 7;
                    if(isset($input['remark']) && !empty($input['remark'])){
                        $playerWithdraw->remark  = $input['remark'] .'|'.$this->carrierUser->username.'于'.date('Y-m-d H:i:s').'取消了订单';
                    } else {
                        $playerWithdraw->remark  = $this->carrierUser->username.'于'.date('Y-m-d H:i:s').'取消了订单';
                    }

                    if(isset($input['frontremark']) && !empty($input['frontremark'])){
                        $playerWithdraw->frontremark  = $input['frontremark'];
                    }
                    
                    $playerWithdraw->save();

                    $playerMessage                                   = new PlayerMessage();
                    $playerMessage->carrier_id                       = $playerAccount->carrier_id;
                    $playerMessage->player_id                        = $playerAccount->player_id;
                    $playerMessage->type                             = 1;
                    $playerMessage->title                            = config('main')['noticetemplate'][$language]['withdrawfail']['title'];
                    $tempstr                                         = str_replace('startTime',$playerWithdraw->created_at,config('main')['noticetemplate'][$language]['withdrawfail']['content']).$playerWithdraw->frontremark.','.config('language')['zh']['text69'];

                    $playerMessage->content                          = str_replace('amount',bcdiv($playerWithdraw->amount, 10000,0),$tempstr);
                    $playerMessage->is_read                          = 0;
                    $playerMessage->admin_id                         = 0;
                    $playerMessage->save();
                    

                        $recharge                = PlayerTransfer::where('player_id',$playerWithdraw->player_id)->where('type','recharge')->orderBy('id','desc')->first();
                        $player                  = Player::where('player_id',$playerWithdraw->player_id)->first();
                        $playerWithdrawFlowLimit = new PlayerWithdrawFlowLimit();

                        switch ($input['type']) {
                            case '0':
                                switch ($input['flowtype']) {
                                    case '0':
                                    // code...
                                    break;
                                    case '1':
                                        if($recharge){
                                             //本金10倍水
                                            $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                                            $playerWithdrawFlowLimit->top_id                 = $player->top_id;
                                            $playerWithdrawFlowLimit->parent_id              = $player->parent_id;
                                            $playerWithdrawFlowLimit->rid                    = $player->rid;
                                            $playerWithdrawFlowLimit->player_id              = $player->player_id;
                                            $playerWithdrawFlowLimit->user_name              = $player->user_name;
                                            $playerWithdrawFlowLimit->betflow_limit_category = '';
                                            $playerWithdrawFlowLimit->limit_amount           = $recharge->amount*10;
                                            $playerWithdrawFlowLimit->limit_type             = 12;
                                            $playerWithdrawFlowLimit->remark                 = '提现风控增加流水';
                                            $playerWithdrawFlowLimit->operator_id            = $this->carrierUser->id;
                                            $playerWithdrawFlowLimit->save();
                                        }
                                       
                                        break;
                                    case '2':
                                        if($recharge){
                                           $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                                            $playerWithdrawFlowLimit->top_id                 = $player->top_id;
                                            $playerWithdrawFlowLimit->parent_id              = $player->parent_id;
                                            $playerWithdrawFlowLimit->rid                    = $player->rid;
                                            $playerWithdrawFlowLimit->player_id              = $player->player_id;
                                            $playerWithdrawFlowLimit->user_name              = $player->user_name;
                                            $playerWithdrawFlowLimit->betflow_limit_category = '';
                                            $playerWithdrawFlowLimit->limit_amount           = $recharge->amount;
                                            $playerWithdrawFlowLimit->limit_type             = 12;
                                            $playerWithdrawFlowLimit->remark                 = '提现风控增加流水';
                                            $playerWithdrawFlowLimit->operator_id            = $this->carrierUser->id;
                                            $playerWithdrawFlowLimit->save(); 
                                        }
                                        
                                        break;
                                    case '3':
                                        if($recharge){
                                            $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                                            $playerWithdrawFlowLimit->top_id                 = $player->top_id;
                                            $playerWithdrawFlowLimit->parent_id              = $player->parent_id;
                                            $playerWithdrawFlowLimit->rid                    = $player->rid;
                                            $playerWithdrawFlowLimit->player_id              = $player->player_id;
                                            $playerWithdrawFlowLimit->user_name              = $player->user_name;
                                            $playerWithdrawFlowLimit->betflow_limit_category = '';
                                            $playerWithdrawFlowLimit->limit_amount           = ($playerAccount->balance+$playerAccount->agentbalance)*10;
                                            $playerWithdrawFlowLimit->limit_type             = 12;
                                            $playerWithdrawFlowLimit->remark                 = '提现风控增加流水';
                                            $playerWithdrawFlowLimit->operator_id            = $this->carrierUser->id;
                                            $playerWithdrawFlowLimit->save();
                                        }
                                       
                                        break;
                                
                                    default:
                                    // code...
                                        break;
                                }
                                break;
                            default:
                            // code...
                                break;
                        }
                
                    \DB::commit();
                    Lock::release($redisLock);
                    return $this->returnApiJson('操作成功', 1);
                } catch (\Exception $e) {
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('订单取消异常：'.$e->getMessage());   
                    return $this->returnApiJson('对不起，取款取消异常'.$e->getMessage(), 0);
                }
            }
        }
    }

    public function financialStat()
    {
        $input =request()->all();

        if(!isset($input['status']) || !in_array($input['status'], [0,1,2])){
            return $this->returnApiJson('对不起，状态取值不正确', 0);
        }

        //type=1 三方存款   2= 公司卡收  4,线上活动 ，5线下活动
        if(isset($input['type']) && in_array($input['type'], [1,2,3,4,5])){
           switch ($input['type']) {
                case 1:
                   $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
                    $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
                    $offset      = ($currentPage - 1) * $pageSize;

                    \Log::info('进入至方面');
                   $query = PlayerDepositPayLog::select('log_player_deposit_pay.id','log_player_deposit_pay.player_id','log_player_deposit_pay.user_name','log_player_deposit_pay.pay_order_number','log_player_deposit_pay.pay_order_channel_trade_number','log_player_deposit_pay.status','log_player_deposit_pay.created_at','log_player_deposit_pay.updated_at','log_player_deposit_pay.collection','log_player_deposit_pay.amount')
                    ->where('log_player_deposit_pay.carrier_id',$this->carrier->id);

                   if($input['status']==1){
                        $query->where('log_player_deposit_pay.status',$input['status']);
                   } else if($input['status']==2){
                        $query->whereIn('log_player_deposit_pay.status',[-1,-2]);
                   }

                    if(isset($input['startTime']) && strtotime($input['startTime'])){
                        $query->where('log_player_deposit_pay.updated_at','>=',$input['startTime']);
                    } else {
                        $query->where('log_player_deposit_pay.updated_at','>=',date('Y-m-d 00:00:00'));
                    }

                    if(isset($input['endTime']) && strtotime($input['endTime'])){
                         $query->where('log_player_deposit_pay.updated_at','<=',$input['endTime']);
                    } 

                    $total       = $query->count();
                    $items       = $query->skip($offset)->take($pageSize)->get();

                    return $this->returnApiJson("操作成功", 1,[ 'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ]);
                   break;
                   
                 case 2:
                    $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
                    $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
                    $offset      = ($currentPage - 1) * $pageSize;

                   $query = PlayerDepositPayLog::select('log_player_deposit_pay.id','log_player_deposit_pay.player_id','log_player_deposit_pay.user_name','log_player_deposit_pay.pay_order_number','log_player_deposit_pay.collection','log_player_deposit_pay.pay','log_player_deposit_pay.created_at','log_player_deposit_pay.updated_at')
                    ->where('log_player_deposit_pay.carrier_id',$this->carrier->id);

                   if($input['status']==1){
                        $query->where('log_player_deposit_pay.status',$input['status']);
                   } else if($input['status']==2){
                        $query->whereIn('log_player_deposit_pay.status',[-1,-2]);
                   }

                    if(isset($input['startTime']) && strtotime($input['startTime'])){
                        $query->where('log_player_deposit_pay.updated_at','>=',$input['startTime']);
                    } else {
                        $query->where('log_player_deposit_pay.updated_at','>=',date('Y-m-d 00:00:00'));
                    }

                    if(isset($input['endTime']) && strtotime($input['endTime'])){
                         $query->where('log_player_deposit_pay.updated_at','<=',$input['endTime']);
                    } 

                    $total       = $query->count();
                    $items       = $query->skip($offset)->take($pageSize)->get();

                    return $this->returnApiJson("操作成功!", 1,[ 'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ]);
                    break;
                
                case 4:
                    $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
                    $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
                    $offset      = ($currentPage - 1) * $pageSize;

                    $query       = PlayerTransfer::select('id','user_name','amount','type','created_at')->where('carrier_id',$this->carrier->id)->where('type','gift');

                    if(isset($input['startTime']) && strtotime($input['startTime'])){
                        $query->where('created_at','>=',$input['startTime']);
                    } else {
                        $query->where('created_at','>=',date('Y-m-d 00:00:00'));
                    }

                    if($input['status']==0 || $input['status']==1){
                        $total       = $query->count();
                        $items       = $query->skip($offset)->take($pageSize)->get();
                        return $this->returnApiJson("操作成功!", 1,[ 'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ]);

                    } else {
                        return $this->returnApiJson("操作成功!", 1,[ 'data' => [], 'total' => 0, 'currentPage' => 1, 'totalPage' => 0 ]);
                    }
                   
                   break;
                case 5:
                    $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
                    $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
                    $offset      = ($currentPage - 1) * $pageSize;

                    $query       = PlayerTransfer::select('id','user_name','amount','type','created_at')->where('carrier_id',$this->carrier->id)->where('type','gift_transfer_add');

                    if(isset($input['startTime']) && strtotime($input['startTime'])){
                        $query->where('created_at','>=',$input['startTime']);
                    } else {
                        $query->where('created_at','>=',date('Y-m-d 00:00:00'));
                    }

                    if($input['status']==0 || $input['status']==1){
                        $total       = $query->count();
                        $items       = $query->skip($offset)->take($pageSize)->get();
                        return $this->returnApiJson("操作成功!", 1,[ 'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ]);

                    } else {
                        return $this->returnApiJson("操作成功!", 1,[ 'data' => [], 'total' => 0, 'currentPage' => 1, 'totalPage' => 0 ]);
                    }
                   break;
            
               default:
                   # code...
                   break;
               }
        }
    }

    public function pageFinancialStat()
    {
         $input =request()->all();

        if(!isset($input['status']) || !in_array($input['status'], [0,1,2])){
            return $this->returnApiJson('对不起，状态取值不正确', 0);
        }
        //type=1 三方存款   2= 公司卡收   3=线下存款  4,线上活动 ，5线下活动
        if(isset($input['type']) && in_array($input['type'], [1,2,3,4,5])){
           switch ($input['type']) {
                case 1:
                    $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
                    $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
                    $offset      = ($currentPage - 1) * $pageSize;

                   $query = PlayerDepositPayLog::select(\DB::raw('sum(amount) as amount'),\DB::raw('count(user_name) as user_name'))
            
                    ->where('carrier_id',$this->carrier->id)
                    ->where('status',$input['status']);

                   if($input['status']==1){
                        $query->where('status',$input['status']);
                   } else {
                        $query->where('status',$input['status']);
                   } 

                    $total       = $query->count();
                    $items       = $query->skip($offset)->take($pageSize)->get();

                   break;
               
               
               default:
                   # code...
                   break;
           }
        }
    }

    public function withdrawList()
    {
        $res = PlayerWithdraw::withdrawList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function agentWithdrawList()
    {
        $res = PlayerWithdraw::agentWithdrawList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function withdrawAuditList()
    {
        $res = PlayerWithdraw::withdrawAuditList($this->carrier,$this->carrierUser);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function withdrawAudit()
    {
        $res = PlayerWithdraw::withdrawAudit($this->carrierUser,$this->carrier);
        if($res===true) {
            return $this->returnApiJson('操作成功', 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function withdrawsuccess()
    {
        $res = PlayerWithdraw::withdrawsuccess($this->carrierUser,$this->carrier);
        if($res===true) {
            return $this->returnApiJson('操作成功', 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function withdrawsLimitList()
    {
        $res = PlayerWithdrawFlowLimit::withdrawsLimitList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function withdrawsLimitComplete()
    {
        $res = PlayerWithdrawFlowLimit::withdrawsLimitComplete($this->carrierUser,$this->carrier);
        if($res===true) {
            return $this->returnApiJson('操作成功', 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function transferList()
    {
        $res = PlayerTransfer::transferList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson('操作成功', 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function transferTypeList()
    {
        $res = Development::all();
    
        return $this->returnApiJson('操作成功', 1, $res);
    }

    public function paymentOnBehalfList()
    {
        $input  = request()->all();

        $currency = CarrierCache::getCurrencyByPrefix($input['prefix']);

        if(isset($input['currency']) && !in_array($input['currency'], ['CNY','VND','PHP','THB','INR','IDR','USD'])){
            return $this->returnApiJson('对不起，币种取值不正确',0);
        }

        if(!isset($input['prefix']) && empty($input['prefix'])){
            return $this->returnApiJson('对不起，站点取值不正确',0);
        }

        if(isset($input['currency'])){
            $query = CarrierPayChannel::select('inf_carrier_pay_channel.id','def_pay_factory_list.factory_name')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('inf_carrier_pay_channel.carrier_id',$this->carrier->id)
            ->where('inf_carrier_pay_channel.prefix',$input['prefix'])
            ->where('def_pay_channel_list.type',2)
            ->where('def_pay_factory_list.currency',$input['currency']);

            if(!isset($input['type'])){
                $input['type'] = 0;
            }

            switch ($input['type']) {
                case '0':
                    $query->whereNotIn('def_pay_factory_list.code',['outokpay','outgopay','outtopay','outebpay','outwanbpay','outjdpay','outkdpay','outnopay','bobipay']);
                    break;
                case '3':
                    $query->where('def_pay_factory_list.code','outokpay');
                    break;
                case '4':
                    $query->where('def_pay_factory_list.code','outgopay');
                    break;
                case '6':
                    $query->where('def_pay_factory_list.code','outtopay');
                    break;
                case '7':
                    $query->where('def_pay_factory_list.code','outebpay');
                    break;
                case '8':
                    $query->where('def_pay_factory_list.code','outwanbpay');
                    break;
                case '9':
                    $query->where('def_pay_factory_list.code','outjdpay');
                    break;
                case '10':
                    $query->where('def_pay_factory_list.code','outkdpay');
                    break;
                case '11':
                    $query->where('def_pay_factory_list.code','outnopay');
                    break;
                case '12':
                    $query->where('def_pay_factory_list.code','outbobipay');
                    break;
                default:
                    break;
                }

            $paychannelLists = $query->get();
        } else {
             $paychannelLists = CarrierPayChannel::select('inf_carrier_pay_channel.id','def_pay_factory_list.factory_name')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('inf_carrier_pay_channel.carrier_id',$this->carrier->id)
            ->where('inf_carrier_pay_channel.prefix',$input['prefix'])
            ->where('def_pay_channel_list.type',2)
            ->whereIn('def_pay_factory_list.currency',[$currency,'USD'])
            ->get();
        }
        
        return $this->returnApiJson('操作成功', 1,$paychannelLists);
    }

    public function paymentOnBehalf()
    {
        $input = request()->all();

        if(!isset($input['id']) || empty($input['id'])){
            return $this->returnApiJson('对不起，ID取值不正确', 0);
        }

        $playerWithdraw = PlayerWithdraw::where('id',$input['id'])->where('carrier_id',$this->carrier->id)->first();
        if(!$playerWithdraw){
            return $this->returnApiJson('对不起，此订单不存在', 0);
        }

        if(!in_array($playerWithdraw->status,[-1,4])) {
            return $this->returnApiJson('对不起，此订单状态不正确', 0);
        }

        if(!isset($input['carrier_pay_channel_id']) || empty($input['carrier_pay_channel_id'])){
            return $this->returnApiJson('对不起，支付渠道取值不正确', 0);
        }

        if($playerWithdraw->is_suspend){
            return $this->returnApiJson('对不起，订单挂机状态不能出款', 0);
        }

        if($playerWithdraw->is_hedging_account==1){
            return $this->returnApiJson('对不起，对冲号不能使用三方代付', 0);
        }

        $carrierPayChannel                        = CarrierPayChannel::select('inf_carrier_pay_channel.show_name','inf_carrier_pay_channel.id','inf_carrier_pay_channel.binded_third_part_pay_id','def_pay_channel_list.trade_rate','def_pay_channel_list.single_fee')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->where('inf_carrier_pay_channel.carrier_id',$this->carrier->id)
            ->where('inf_carrier_pay_channel.id',$input['carrier_pay_channel_id'])
            ->first();

        $pay                                      = new Pay($input['carrier_pay_channel_id']);

        if(isset($input['is_oneandone_withdrawal']) && $input['is_oneandone_withdrawal']==1){
            $playerWithdraw->is_oneandone_withdrawal = 1; 
        }

        $playerWithdraw->pay                      = $carrierPayChannel->show_name;
        $playerWithdraw->review_two_time          = time(); 
        $playerWithdraw->payment_channel          = $carrierPayChannel->id;
        $playerWithdraw->carrier_pay_channel      = $carrierPayChannel->id;   
        $playerWithdraw->review_two_user_id       = $this->carrierUser->id;
        $playerWithdraw->third_part_pay_id        = $carrierPayChannel->binded_third_part_pay_id;

        $withdrawBankcardRatefee = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'withdraw_ratefee',$playerWithdraw->prefix);
        if($withdrawBankcardRatefee>0){
            $playerWithdraw->third_fee                = bcdiv($playerWithdraw->amount * $withdrawBankcardRatefee,100,0);
        } else{
            $playerWithdraw->third_fee                = 0;
        }

        $playerWithdraw->status                   = 5;
        $playerWithdraw->save();

        $flag                = $pay->paymentOnBehalf($playerWithdraw);
        if($flag===true){
            return $this->returnApiJson('操作成功', 1);
        } else if($flag===false){
            return $this->returnApiJson('对不起，代付提交失败', 0);
        } else {
            return $this->returnApiJson($flag, 0);
        }
    }

    public function checkPaymentOnBehalf($id)
    {
        $playWithdraw = PlayerWithdraw::select('inf_carrier_pay_channel.id','log_player_withdraw.pay_order_number')
            ->leftJoin('inf_carrier_pay_channel','inf_carrier_pay_channel.id','=','log_player_withdraw.carrier_pay_channel')
            ->where('log_player_withdraw.id',$id)
            ->where('carrier_id',$this->carrier->id)
            ->first();

        if(!$playWithdraw){
            return $this->returnApiJson('对不起，此订单不存在', 0);
        }

        $pay               = new Pay($playWithdraw->id);
        $flag              = $pay->checked($playWithdraw); 

        if(!$flag){
            return $this->returnApiJson('操作成功', 1,['status'=>-1]);
        } else if($flag =='success') {
            return $this->returnApiJson('操作成功', 1,['status'=>1]);
        } else {
            return $this->returnApiJson('操作成功', 1,['status'=>0]); 
        }
    }

    public function addWithdrawsLimit($playerId=0)
    {
        $input   = request()->all();
        $player  = Player::where('player_id',$playerId)->where('carrier_id',$this->carrier->id)->first();

        if(!$player){
            return returnApiJson('对不起，此用户不存在', 0);
        }

        if(!isset($input['game_category']) || !in_array($input['game_category'], [0,1,2,3,4,5,6,7])){
            return returnApiJson('对不起，限制游戏分类取值不正确', 0);
        }

        if(!isset($input['limit_amount']) || !is_numeric($input['limit_amount']) || $input['limit_amount']<=0){
            return returnApiJson('对不起，金额取值不正确', 0);
        }

        $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
        $playerWithdrawFlowLimit->carrier_id             = $this->carrier->id;
        $playerWithdrawFlowLimit->top_id                 = $player->top_id;
        $playerWithdrawFlowLimit->parent_id              = $player->parent_id;
        $playerWithdrawFlowLimit->rid                    = $player->rid;
        $playerWithdrawFlowLimit->player_id              = $player->player_id;
        $playerWithdrawFlowLimit->user_name              = $player->user_name;
        $playerWithdrawFlowLimit->betflow_limit_category = $input['game_category'];
        $playerWithdrawFlowLimit->limit_amount           = $input['limit_amount']*10000;
        $playerWithdrawFlowLimit->limit_type             = 12;
        $playerWithdrawFlowLimit->operator_id            = $this->carrierUser->id;
        $playerWithdrawFlowLimit->save();

        return returnApiJson('操作成功', 1);
    }

    public function resetWithdrawsLimit($id=0)
    {
        $playerWithdrawFlowLimit = PlayerWithdrawFlowLimit::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$playerWithdrawFlowLimit){
            return returnApiJson('对不起，此条数据不存在', 0);
        } else {
            $playerWithdrawFlowLimit->complete_limit_amount = 0;
            $playerWithdrawFlowLimit->is_finished           = 0;
            $playerWithdrawFlowLimit->operator_id           = $this->carrierUser->id;
            $playerWithdrawFlowLimit->save();

            return returnApiJson('操作成功', 1);
        }

    }

    public function playerGameStat()
    {
        $input = request()->all();

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return returnApiJson('对不起,用户ID不能为空', 0);
        }

        $startTime = 0;

        $existPlayerTransfer = PlayerTransfer::where('player_id',$input['player_id'])->where('type','recharge')->orderBy('id','desc')->first();

        if($existPlayerTransfer){
            $existWithdraw       = PlayerWithdraw::where('player_id',$input['player_id'])->where('status',7)->where('created_at','>',$existPlayerTransfer->created_at)->orderBy('id','desc')->first();

            if($existWithdraw){
                $startTime = strtotime($existWithdraw->created_at);
            } else{
                $startTime = strtotime($existPlayerTransfer->created_at);
            } 

        } else {
            $player = Player::where('player_id',$input['player_id'])->first();
            $startTime = strtotime($player->created_at);
        }

        $playerBetFlowStat = PlayerBetFlow::select('main_game_plat_code','game_name','game_id',\DB::raw('sum(bet_amount) as bet_amount'),\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'),'game_flow_code')->where('player_id',$input['player_id'])->where('bet_time','>=',$startTime)->groupBy('game_id')->get();

        foreach ($playerBetFlowStat as $key => &$value) {


            if(strpos($value->game_flow_code, '181225-') !== false || strpos($value->game_flow_code, '181227-') !== false || strpos($value->game_flow_code, '181228-') !== false || strpos($value->game_flow_code, '181216-') !== false || strpos($value->game_flow_code, '181218-') !== false || strpos($value->game_flow_code, '181221-') !== false || strpos($value->game_flow_code, '181229-') !== false || strpos($value->game_flow_code, '181230-') !== false || strpos($value->game_flow_code, '181232-') !== false || strpos($value->game_flow_code, '181233-') !== false || strpos($value->game_flow_code, '181234-') !== false || strpos($value->game_flow_code, '181235-') !== false || strpos($value->game_flow_code, '181236-') !== false || strpos($value->game_flow_code, '181237-') !== false || strpos($value->game_flow_code, '181238-') !== false || strpos($value->game_flow_code, '181239-') !== false || strpos($value->game_flow_code, '181240-') !== false || strpos($value->game_flow_code, '181241-') !== false || strpos($value->game_flow_code, '181242-') !== false || strpos($value->game_flow_code, '181243-') !== false || strpos($value->game_flow_code, '181244-') !== false || strpos($value->game_flow_code, '181245-') !== false || strpos($value->game_flow_code, '181246-') !== false || strpos($value->game_flow_code, '181247-') !== false || strpos($value->game_flow_code, '181248-') !== false || strpos($value->game_flow_code, '181249-') !== false || strpos($value->game_flow_code, '181250-') !== false || strpos($value->game_flow_code, '181251-') !== false || strpos($value->game_flow_code, '181252-') !== false || strpos($value->game_flow_code, '181253-') !== false || strpos($value->game_flow_code, '181254-') !== false || strpos($value->game_flow_code, '181255-') !== false || strpos($value->game_flow_code, '181256-') !== false || strpos($value->game_flow_code, '181257-') !== false || strpos($value->game_flow_code, '181258-') !== false || strpos($value->game_flow_code, '181259-') !== false || strpos($value->game_flow_code, '181260-') !== false){
                $value->isReal = 0;
            } else{
                $value->isReal = 1;
            }
        }

        return returnApiJson('操作成功', 1,$playerBetFlowStat);
    }

    public function specialGamestat()
    {
        $input = request()->all();

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return returnApiJson('对不起,用户ID不能为空', 0);
        }

        $startTime = 0;

        $existPlayerTransfer = PlayerTransfer::where('player_id',$input['player_id'])->where('type','recharge')->orderBy('id','desc')->first();
        if($existPlayerTransfer){
            $existWithdraw       = PlayerWithdraw::where('player_id',$input['player_id'])->where('status',7)->where('created_at','>',$existPlayerTransfer->created_at)->orderBy('id','desc')->first();
            if($existWithdraw){
                $startTime = strtotime($existWithdraw->created_at);
            } else{
                $startTime = strtotime($existPlayerTransfer->created_at);
            } 

        } else {
            $player = Player::where('player_id',$input['player_id'])->first();
            $startTime = strtotime($player->created_at);
        }

        $playerBetFlowIds   = PlayerBetFlow::select('id')->where('bet_time','>=',$startTime)->where('player_id',$input['player_id'])->pluck('id')->toArray();

        $playerBetFlowStats = PlayerBetFlow::select('isFeatureBuy','multi_spin_game','main_game_plat_code','game_name',\DB::raw('sum(bet_amount) as bet_amount'),\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'))->whereIn('id',$playerBetFlowIds)->whereIn('id',function($query){
            $query->select('id')->from('log_player_bet_flow')->where('isFeatureBuy',1)->orWhere('multi_spin_game',1);
        })->groupBy('game_id')->get();

        return returnApiJson('操作成功', 1,$playerBetFlowStats);
    }

    public function agentDepositPaychannel()
    {
        $payFactoryIds          = PayFactory::where('third_wallet_id',1)->pluck('id')->toArray();
        $payChannelIds          = PayChannel::where('type',1)->whereIn('factory_id',$payFactoryIds)->pluck('id')->toArray();
        $carrierThirdPartPayIds = CarrierThirdPartPay::where('carrier_id',$this->carrier->id)->whereIn('def_pay_channel_id',$payChannelIds)->pluck('id')->toArray();
        $data                  = CarrierPayChannel::select('id','show_name')->whereIn('binded_third_part_pay_id',$carrierThirdPartPayIds)->orderby('sort','desc')->get();

        return returnApiJson('操作成功', 1,$data);
    }

    public function collectionFactoryList()
    {
        $input = request()->all();

        if(!isset($input['type']) || !in_array($input['type'],[1,2])){
            return returnApiJson('对不起,类型取值不正确', 0);
        }

        $defPayChannelIds      = CarrierThirdPartPay::where('carrier_id',$this->carrier->id)->pluck('def_pay_channel_id')->toArray();
        $factoryIds            = PayChannel::whereIn('id',$defPayChannelIds)->where('type',$input['type'])->pluck('factory_id')->toArray();
        $payFactorys           = PayFactory::select('factory_name','id as factory_id')->whereIn('id',$factoryIds)->orderBy('id','asc')->get();

        return returnApiJson('操作成功', 1,$payFactorys);
    }

    public function collectionPaychannelList()
    {
        $input = request()->all();

        if(!isset($input['factory_id'])){
            return returnApiJson('对不起,厂商取值不正确', 0);
        }

        if(!isset($input['type']) || !in_array($input['type'],[1,2])){
            return returnApiJson('对不起,类型取值不正确', 0);
        }

        $defPayChannelIds      = CarrierThirdPartPay::where('carrier_id',$this->carrier->id)->pluck('def_pay_channel_id')->toArray();
        $payFactorys           = PayChannel::select('name','id as pay_channel_id')->whereIn('id',$defPayChannelIds)->where('type',$input['type'])->where('factory_id',$input['factory_id'])->get();

        return returnApiJson('操作成功', 1,$payFactorys);
    }

    public function stat()
    {
        $input = request()->all();

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson("对不起，玩家ID取值不正确", 0);
        }

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$input['player_id'])->first();

        if(!$player){
            return $this->returnApiJson("对不起，玩家不存在", 0);
        }

        $rechargePlayerTransfer = PlayerTransfer::select('amount',\DB::raw('count(id) as count'))->where('player_id',$input['player_id'])->where('type','recharge')->groupBy('amount')->get();
        $data                   = [];

        foreach ($rechargePlayerTransfer as $key => $value) {
            if(!is_null($value->amount)){
                $row           = [];
                $row['type']   = 1;
                $row['amount'] = bcdiv($value->amount,10000,2);
                $row['count']  = $value->count;
                $data[]        = $row;       
            }
        }

        $withdrawPlayerTransfer = PlayerTransfer::select('amount',\DB::raw('count(id) as count'))->where('player_id',$input['player_id'])->where('type','withdraw_finish')->groupBy('amount')->get();

        foreach ($withdrawPlayerTransfer as $key => $value) {
            if(!is_null($value->amount)){
                $row           = [];
                $row['type']   = 0;
                $row['amount'] = bcdiv($value->amount,10000,2);
                $row['count']  = $value->count;
                $data[]        = $row;       
            }
        }

        return $this->returnApiJson("操作成功", 1,$data);
    }


    public function financeBriefing()
    {
        $input        = request()->all();
        $data         = [];
        
        if(isset($input['player_id']) && !empty($input['player_id'])){
            $winLoseAgent = PlayerCache::getisWinLoseAgent($input['player_id']);
            if($winLoseAgent){
                $query = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('type','agent_recharge');
                $query2 = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('type','agent_withdraw_finish');

                if(isset($input['player_id']) && !empty($input['player_id'])){
                    $query->where('player_id',$input['player_id']);
                    $query2->where('player_id',$input['player_id']);
                }

                if(isset($input['startDate']) && !empty($input['startDate']) && strtotime($input['startDate'])){
                    $query->where('created_at','>=',$input['startDate'].' 00:00:00');
                    $query2->where('created_at','>=',$input['startDate'].' 00:00:00');
                }

                if(isset($input['endDate']) && !empty($input['endDate']) && strtotime($input['endDate'])){
                    $query->where('created_at','<=',$input['endDate'].' 23:59:59');
                    $query2->where('created_at','<=',$input['endDate'].' 23:59:59');
                }

                $data['offlinerecharge'] = 0;
                $data['offlinewithdraw'] = 0;
                
            } else{
                $query = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('type','recharge');
                $query2 = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('type','withdraw_finish');

                if(isset($input['player_id']) && !empty($input['player_id'])){
                    $query->where('player_id',$input['player_id']);
                    $query2->where('player_id',$input['player_id']);
                }

                if(isset($input['startDate']) && !empty($input['startDate']) && strtotime($input['startDate'])){
                    $query->where('created_at','>=',$input['startDate'].' 00:00:00');
                    $query2->where('created_at','>=',$input['startDate'].' 00:00:00');
                }

                if(isset($input['endDate']) && !empty($input['endDate']) && strtotime($input['endDate'])){
                    $query->where('created_at','<=',$input['endDate'].' 23:59:59');
                    $query2->where('created_at','<=',$input['endDate'].' 23:59:59');
                }

                $data['offlinerecharge'] = 0;
                $data['offlinewithdraw'] = 0;
            }
            
            $temp = $query->first();
            if(is_null($temp->amount)){
                $data['onlinerecharge'] = 0;
            } else {
                $data['onlinerecharge'] = $temp->amount;
            }

            $temp = $query2->first();
            if(is_null($temp->amount)){
                $data['onlinewithdraw'] = 0;
            } else {
                $data['onlinewithdraw'] = $temp->amount;
            }     

            return returnApiJson('操作成功', 1,$data);
        } else{
            //直播号
            $isHedgingAccount       = Player::where('carrier_id',$this->carrier->id)->where('is_hedging_account',1)->pluck('player_id')->toArray();
            $query                  = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('type','recharge')->whereNotIn('player_id',$isHedgingAccount);
            $query2                 = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('type','withdraw_finish')->whereNotIn('player_id',$isHedgingAccount);
            $query3                 = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('type','recharge')->whereIn('player_id',$isHedgingAccount);
            $query4                 = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('carrier_id',$this->carrier->id)->where('type','withdraw_finish')->whereIn('player_id',$isHedgingAccount);

            if(isset($input['startDate']) && !empty($input['startDate']) && strtotime($input['startDate'])){
                $query->where('created_at','>=',$input['startDate'].' 00:00:00');
                $query2->where('created_at','>=',$input['startDate'].' 00:00:00');
                $query3->where('created_at','>=',$input['startDate'].' 00:00:00');
                $query4->where('created_at','>=',$input['startDate'].' 00:00:00');
            }

            if(isset($input['endDate']) && !empty($input['endDate']) && strtotime($input['endDate'])){
                $query->where('created_at','<=',$input['endDate'].' 23:59:59');
                $query2->where('created_at','<=',$input['endDate'].' 23:59:59');
                $query3->where('created_at','<=',$input['endDate'].' 23:59:59');
                $query4->where('created_at','<=',$input['endDate'].' 23:59:59');
            }

            if(isset($input['prefix']) && !empty($input['prefix'])){
                $playerIds = Player::where('prefix',$input['prefix'])->pluck('player_id')->toArray();
                $query->whereIn('player_id',$playerIds);
                $query2->whereIn('player_id',$playerIds);
                $query3->whereIn('player_id',$playerIds);
                $query4->whereIn('player_id',$playerIds);
            }

            $temp = $query->first();
            if(is_null($temp->amount)){
                $data['onlinerecharge'] = 0;
            } else {
                $data['onlinerecharge'] = $temp->amount;
            }

            $temp = $query2->first();
            if(is_null($temp->amount)){
                $data['onlinewithdraw'] = 0;
            } else {
                $data['onlinewithdraw'] = $temp->amount;
            }

            $temp = $query3->first();
            if(is_null($temp->amount)){
                $data['hedgingrecharge'] = 0;
            } else {
                $data['hedgingrecharge'] = $temp->amount;
            } 

            $temp = $query4->first();
            if(is_null($temp->amount)){
                $data['hedgingwithdraw'] = 0;
            } else {
                $data['hedgingwithdraw'] = $temp->amount;
            }       

            return returnApiJson('操作成功', 1,$data);
        }
    }
}
