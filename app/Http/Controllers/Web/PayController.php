<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Web\BaseController;
use Illuminate\Auth\Authenticatable;
use App\Models\Map\CarrierPlayerLevelBankCardMap;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerLogin;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Def\PayChannel;
use App\Models\CarrierBankCardType;
use App\Models\Carrier;
use App\Models\CarrierBankCard;
use App\Lib\Cache\CarrierCache;
use App\Models\CarrierDigitalAddress;
use App\Lib\Clog;


use App\Pay\Pay;

class PayController extends BaseController
{
    use Authenticatable;

    //获取充值金额
    public function getDepositAmount()
    {
        $input        = request()->all();
        $priceArr     = [];
        $depositPay   = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->first();
        $minRecharge  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'finance_min_recharge',$this->prefix);

        $carrierPlayerLevelBankCardMap = CarrierPlayerLevelBankCardMap::where('player_level_id',$this->user->player_level_id)->where('carrier_id',$this->user->carrier_id)->pluck('carrier_channle_id')->toArray();
        if(!count($carrierPlayerLevelBankCardMap)){
            return $this->returnApiJson(config('language')[$this->language]['error135'], 0);
        }

        //此运营商所有的支付渠道
        $middleVariable = CarrierPayChannel::select('inf_carrier_pay_channel.id','def_pay_factory_list.code','def_pay_channel_list.min','def_pay_channel_list.max','def_pay_channel_list.enum','conf_carrier_third_part_pay.startTime','conf_carrier_third_part_pay.endTime','def_pay_channel_list.is_smallamountpay')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('inf_carrier_pay_channel.status',1)
            ->get();

        $carrierPayChannel = [];

        $today = date('Y-m-d');
        $time  = time();
        foreach ($middleVariable as $key => $value) {
             $startTime = strtotime($today.' '.$value->startTime);
             $endTime   = strtotime($today.' '.$value->endTime);
             if($time >= $startTime && $time <= $endTime) {
                $carrierPayChannel[$key] = $value;
             }
        }

        //获取玩家所有的充值渠道参数
        $tempArr             = [];
        foreach ($carrierPayChannel as $key => $value) {
            if(in_array($value->id, $carrierPlayerLevelBankCardMap)){
                $tempArr[]             = $value;
            }
        }

        $payAmountArr        = [];
        $payAmountArr        = $tempArr;

        $enumArr = [];
        //开始处理金额
        foreach ($payAmountArr as $key => $value) {
           if(!empty($value->enum)){
                $explodeArr = explode(',',$value->enum);
                $enumArr    = array_merge($enumArr,$explodeArr);
           }
        }
        array_unique($enumArr);
        asort($enumArr);

        $min = 10000;
        $max = 0;
        foreach ($payAmountArr as $key => $value) {
            if($value->min>0 && $value->min<$min){
                $min = $value->min;
            }

            if($value->max>0&& $value->max>$max){
                $max = $value->max;
            }
        }

        $systemAmountenum = [100,200,300,500,1000,2000,5000,10000,20000,50000];

        //开始组装
        if($max<$min){
            //支设置了小额
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $enumArr);
        } else {

            if(count($enumArr)){
                foreach ($enumArr as $key => $value) {
                    if($value>=$min){
                        unset($enumArr[$key]);
                    }
                }

            } 
            foreach ($systemAmountenum as $key => $value) {
                if($value>=$min && $value<=$max){
                    $enumArr[]= $value;
                }
            }
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $enumArr);
        }
    }

    public function digitalDeposit()
    {
        $input                                                = request()->all();
        $continuousUnpaid                                     = CarrierCache::getCarrierConfigure($this->carrier->id,'continuous_unpaid');
        $banHour                                              = CarrierCache::getCarrierConfigure($this->carrier->id,'ban_hour');
        $banTime                                              = $banHour*60;
        $time                                                 = time(); 
        $tag                                                  = 'orderLock';
        $key                                                  = 'orderLock_'.$this->user->player_id;
        $flag                                                 = false;
        if($continuousUnpaid){
            $checkPlayerDepositPayLogs                            = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('created_at','>=',date('Y-m-d H:i:s',$time-$banTime))->orderBy('id','desc')->take($continuousUnpaid)->get();
            if($checkPlayerDepositPayLogs && count($checkPlayerDepositPayLogs) >= $continuousUnpaid){
                foreach ($checkPlayerDepositPayLogs as $k => $v) {
                    if($v->status==1){
                        $flag  = true;
                    }
                }

                if(!$flag){
                    cache()->tags($tag)->put($key, 1, now()->addMinutes($banHour));
                }
            }
        }

        if(cache()->tags($tag)->has($key)){
            return $this->returnApiJson(config('language')[$this->language]['error219'], 0);
        }

        if(!isset($input['address']) || empty($input['address'])){
            return $this->returnApiJson(config('language')[$this->language]['error183'], 0);
        }

        if(!isset($input['txid']) || empty($input['txid'])){
            return $this->returnApiJson(config('language')[$this->language]['error326'], 0);
        }

        $carrierDigitalAddress = CarrierDigitalAddress::where('carrier_id',$this->carrier->id)->where('address',$input['address'])->where('status',1)->first();
        if(!$carrierDigitalAddress){
            return $this->returnApiJson(config('language')[$this->language]['error189'], 0);
        }

        if(!isset($input['amount']) || !is_numeric($input['amount']) || $input['amount']<0){
            return $this->returnApiJson(config('language')[$this->language]['error190'], 0);
        }

        try {
            \DB::beginTransaction();
                           
            $rate                                                   = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'digital_rate',$this->user->prefix);
            $carrierUsdtGift                                        = CarrierCache::getCarrierConfigure($this->carrier->id,'carrier_usdt_gift');
            
            $playerDepositPayLog                                    = new PlayerDepositPayLog();
            $playerDepositPayLog->player_id                         = $this->user->player_id;
            $playerDepositPayLog->user_name                         = $this->user->user_name;
            $playerDepositPayLog->rid                               = $this->user->rid;
            $playerDepositPayLog->top_id                            = $this->user->top_id;
            $playerDepositPayLog->parent_id                         = $this->user->parent_id;
            $playerDepositPayLog->carrier_id                        = $this->user->carrier_id;
            $playerDepositPayLog->is_hedging_account                = $this->user->is_hedging_account;
            $playerDepositPayLog->is_agent                          = $this->user->win_lose_agent;
            $playerDepositPayLog->pay_order_number                  = 'CZ'.date('YmdHis').mt_rand(10000,99999); 
            $playerDepositPayLog->amount                            = $input['amount']*10000;
            $playerDepositPayLog->arrivedamount                     = $input['amount']*10000*bcdiv((100+$carrierUsdtGift),100,4);
            $playerDepositPayLog->pay                               = bcdiv($input['amount'],$rate,2).'USDT';
            $playerDepositPayLog->status                            = 2;
            $playerDepositPayLog->currency                          = 'USD';
            $playerDepositPayLog->is_wallet_recharge                = 1;
            $playerDepositPayLog->activityids                       = isset($input['activityids']) ? $input['activityids']:'';
            $playerDepositPayLog->txid                              = $input['txid'];
            $playerDepositPayLog->prefix                            = $input['prefix'];

            $playerDepositPayLog->carrier_digital_address           = $carrierDigitalAddress->address;
            $playerDepositPayLog->digital_type                      = $carrierDigitalAddress->type;
            

            if($carrierDigitalAddress->type==1){
                $playerDepositPayLog->collection                        = 'TRC20 |'.$carrierDigitalAddress->address;
            } else if($carrierDigitalAddress->type==2) {
                $playerDepositPayLog->collection                        = 'ERC20 |'.$carrierDigitalAddress->address;
            }

            $playerDepositPayLog->save();

                          
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollback();
            Clog::recordabnormal('商户数字币存款操作异常：'.$e->getMessage());   
            return false;
        }
        return $this->returnApiJson(config('language')[$this->language]['success2'], 1);
    }

    // 登录
    public function deposit()
    {
        $params                                                = request()->all();
        $notifyUrl                                             = config('main.notifyUrl');
        $returnUrl                                             = config('main.returnUrl');

        $currency                                               = CarrierCache::getCurrencyByPrefix($this->user->prefix);

        if($this->user->is_tester == 1) {
            return $this->returnApiJson(config('language')[$this->language]['error138'], 0);
        }

        if(!isset($params['amount']) || empty($params['amount'])) {
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        $continuousUnpaid                                     = CarrierCache::getCarrierConfigure($this->carrier->id,'continuous_unpaid');
        $banHour                                              = CarrierCache::getCarrierConfigure($this->carrier->id,'ban_hour');
        $banTime                                              = $banHour*60;
        $tag                                                  = 'orderLock';                                          
        $time                                                 = time(); 
        $key                                                  = 'orderLock_'.$this->user->player_id;
        $flag                                                 = false;
        if($continuousUnpaid){
            $checkPlayerDepositPayLogs                            = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('created_at','>=',date('Y-m-d H:i:s',$time-$banTime))->orderBy('id','desc')->take($continuousUnpaid)->get();

            if($checkPlayerDepositPayLogs && count($checkPlayerDepositPayLogs) >= $continuousUnpaid){

                foreach ($checkPlayerDepositPayLogs as $k => $v) {
                    if($v->status==1){
                        $flag  = true;
                    }
                }

                if(!$flag){
                    cache()->tags($tag)->put($key, 1, now()->addMinutes($banHour));
                }
            }
        }

        if(cache()->tags($tag)->has($key)){
            return $this->returnApiJson(config('language')[$this->language]['error219'], 0);
        }


        $amount       = $params['amount'];

        $isFirstDepositPay                  = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->first();
        $minRecharge                        = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'finance_min_recharge',$this->user->prefix);
        $maxRecharge                        = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'finance_max_recharge',$this->user->prefix);
        $digitalFinanceMinRecharge          = CarrierCache::getCarrierConfigure($this->carrier->id, 'digital_finance_min_recharge');
        $digitalFinanceMaxRecharge          = CarrierCache::getCarrierConfigure($this->carrier->id, 'digital_finance_max_recharge');
        $language                           = CarrierCache::getLanguageByPrefix($this->user->prefix);

        if (!is_numeric($amount) || intval($amount)!= $amount) {
            return $this->returnApiJson(config('language')[$this->language]['error34'], 0);
        }

        $param=[
                'amount'        => $amount,
                'orderid'       => 'CZ'.date('YmdHis').mt_rand(1000,9999),
                'user_name'     => $this->user->user_name,
                'player_id'     => $this->user->player_id,
                'real_name'     => $this->user->real_name,
                'transfer_name' => empty($this->user->real_name) && isset($params['transfer_name'])? $params['transfer_name'] :$this->user->real_name,
                'language'      => $language
        ];

        if(empty($this->user->real_name) && isset($params['transfer_name'])){
            $this->user->real_name = $params['transfer_name'];
            $this->user->save();
        }

        $playerDepositPayLog                                    = new PlayerDepositPayLog();
        $playerDepositPayLog->player_id                         = $this->user->player_id;
        $playerDepositPayLog->user_name                         = $this->user->user_name;
        $playerDepositPayLog->rid                               = $this->user->rid;
        $playerDepositPayLog->top_id                            = $this->user->top_id;
        $playerDepositPayLog->parent_id                         = $this->user->parent_id;
        $playerDepositPayLog->carrier_id                        = $this->user->carrier_id;
        $playerDepositPayLog->is_hedging_account                = $this->user->is_hedging_account;
        $playerDepositPayLog->is_agent                          = $this->user->win_lose_agent;
        $playerDepositPayLog->prefix                            = $params['prefix'];
        $playerDepositPayLog->activityids                       = isset($params['activityids']) ? $params['activityids']:'';
        $playerDepositPayLog->pay_order_number                  = $param['orderid'];
        $playerDepositPayLog->depositimg                        = isset($params['depositimg']) && !empty($params['depositimg']) ? $params['depositimg']:'';

        if(isset($params['carrier_bankcard_id']) && !empty($params['carrier_bankcard_id'])) {
            if ($amount < $minRecharge) {
                return $this->returnApiJson(config('language')[$this->language]['error34'], 0);
            }

           if($amount > $maxRecharge){
                return $this->returnApiJson(config('language')[$this->language]['error204'], 0);
           }

           $carrierBankCard   = CarrierBankCard::select('inf_carrier_bank_type.bank_name','inf_carrier_bankcard.*')->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_carrier_bankcard.bank_id')->where('inf_carrier_bankcard.id',$params['carrier_bankcard_id'])->first();

            if(!$carrierBankCard || $carrierBankCard->status==0) {
                return $this->returnApiJson(config('language')[$this->language]['error35'], 0);
            }

            if(!isset($params['deposit_account']) || empty($params['deposit_account'])) {
                return $this->returnApiJson(config('language')[$this->language]['error37'], 0);
            }

            if(!isset($params['deposit_username']) || empty($params['deposit_username'])) {
                return $this->returnApiJson(config('language')[$this->language]['error38'], 0);
            }

            if(!isset($params['bank_id']) || empty($params['bank_id'])) {
                return $this->returnApiJson(config('language')[$this->language]['error39'], 0);
            }

            $bank = CarrierBankCardType::where('carrier_id',$this->carrier->id)->where('id',$params['bank_id'])->first();

            if(!$bank) {
                return $this->returnApiJson(config('language')[$this->language]['error40'], 0);
            }

            $carrierBankGift                              = CarrierCache::getCarrierConfigure($this->carrier->id,'carrier_bank_gift');

            $playerDepositPayLog->collection              = $carrierBankCard->bank_name.'|'.$carrierBankCard->bank_account.'|'.$carrierBankCard->bank_username; 
            $playerDepositPayLog->pay                     = $bank->bank_name.'|'.$params['deposit_account'].'|'.$params['deposit_username'];
            $playerDepositPayLog->carrier_bankcard_id     = $carrierBankCard->id;      
            $playerDepositPayLog->deposit_account         = $params['deposit_account']; 
            $playerDepositPayLog->deposit_username        = $params['deposit_username']; 
            $playerDepositPayLog->bank_id                 = $params['bank_id'];
            $playerDepositPayLog->amount                  = $params['amount']*10000;
            $playerDepositPayLog->arrivedamount           = $params['amount']*10000*bcdiv((100+$carrierBankGift),100,4);
            $playerDepositPayLog->currency                = $currency;
            $playerDepositPayLog->status                  = 2;
            $playerDepositPayLog->save();

             return $this->returnApiJson(config('language')[$this->language]['success2'], 1);
        } else {
            $carrierChannleIds = CarrierPlayerLevelBankCardMap::where('carrier_id',$this->user->carrier_id)->where('player_level_id',$this->user->player_group_id)->pluck('carrier_channle_id')->toArray();

            if(!count($carrierChannleIds)) {
                return $this->returnApiJson(config('language')[$this->language]['error42'], 0);
            }

            if(!isset($params['carrier_pay_channel_id']) || $params['carrier_pay_channel_id']==0){
                return $this->returnApiJson(config('language')[$this->language]['error44'], 0);
            }


            $unpayFrequencyHidden   = CarrierCache::getCarrierConfigure($this->carrier->id, 'unpay_frequency_hidden');
            $carrierPayChannelIds   = CarrierPayChannel::leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
                ->where('inf_carrier_pay_channel.carrier_id',$this->carrier->id)
                ->where('conf_carrier_third_part_pay.is_anti_complaint',0)
                ->pluck('inf_carrier_pay_channel.id')
                ->toArray();

            if(in_array($params['carrier_pay_channel_id'],$carrierPayChannelIds)){
                $lastSuccessOrder       = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->first();
                if($lastSuccessOrder){
                    $unpayAntiComplaint = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('id','>',$lastSuccessOrder->id)->whereIn('carrier_pay_channel',$carrierPayChannelIds)->get();

                } else {
                    $unpayAntiComplaint = PlayerDepositPayLog::where('player_id',$this->user->player_id)->whereIn('carrier_pay_channel',$carrierPayChannelIds)->get();
                }

                if(count($unpayAntiComplaint) >= $unpayFrequencyHidden){
                    return $this->returnApiJson(config('language')[$this->language]['error234'], 0);
                }
            }

            $payChannelObj = CarrierPayChannel::select('conf_carrier_third_part_pay.is_anti_complaint','inf_carrier_pay_channel.show_name','def_pay_factory_list.currency','def_pay_channel_list.name','def_pay_channel_list.trade_rate','inf_carrier_pay_channel.gift_ratio','def_pay_channel_list.has_realname','def_pay_channel_list.min','def_pay_factory_list.third_wallet_id','def_pay_channel_list.max','def_pay_channel_list.enum')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->leftJoin('def_pay_factory_list','def_pay_factory_list.id','=','def_pay_channel_list.factory_id')
            ->where('inf_carrier_pay_channel.id',$params['carrier_pay_channel_id'])
            ->first();

            if(!$payChannelObj){
                 return $this->returnApiJson(config('language')[$this->language]['error45'], 0);
            }

            //金额限制
            if(empty($payChannelObj->enum)){
                if($params['amount']>$payChannelObj->max || $params['amount'] <$payChannelObj->min){
                    return $this->returnApiJson(config('language')[$this->language]['error240'], 0);
                }
            } else{
                $enum = explode(',',$payChannelObj->enum);
                if(!in_array($params['amount'],$enum) && $payChannelObj->max==0 && $payChannelObj->min==0){
                    return $this->returnApiJson(config('language')[$this->language]['error240'], 0);
                }
            }

            $param['notifyUrl']                       = $notifyUrl.'/'.$params['carrier_pay_channel_id'];
            //$param['returnUrl']                       = $returnUrl.'/'.$params['carrier_pay_channel_id'];

            //$playerLogin                              = PlayerLogin::where('player_id',$param['player_id'])->orderBy('id','desc')->first();

            $param['returnUrl']                       = 'http://www.baidu.com';   //'http://'.$playerLogin->login_domain;

            

            $playerDepositPayLog->collection          = $payChannelObj->name.'|'.$payChannelObj->show_name; 

            $tempName = strtolower($payChannelObj->name);
            if(strpos($tempName,'usdt') !== false){
                $playerDepositPayLog->digital_type = 1;
            } elseif(strpos($tempName,'okpay') !== false){
                $playerDepositPayLog->digital_type = 3;
            } elseif(strpos($tempName,'gopay') !== false){
                $playerDepositPayLog->digital_type = 4;
            } elseif(strpos($tempName,'topay') !== false){
                $playerDepositPayLog->digital_type = 6;
            } elseif(strpos($tempName,'ebpay') !== false){
                $playerDepositPayLog->digital_type = 7;
            } elseif(strpos($tempName,'wanb') !== false){
                $playerDepositPayLog->digital_type = 8;
            } elseif(strpos($tempName,'jdpay') !== false){
                $playerDepositPayLog->digital_type = 9;
            } elseif(strpos($tempName,'kdpay') !== false){
                $playerDepositPayLog->digital_type = 10;
            } elseif(strpos($tempName,'nopay') !== false){
                $playerDepositPayLog->digital_type = 11; 
            } elseif(strpos($tempName,'bobipay') !== false){
                $playerDepositPayLog->digital_type = 12; 
            }

            $playerDepositPayLog->pay                 = '';
            $playerDepositPayLog->carrier_pay_channel = $params['carrier_pay_channel_id'];

            //查询币种确定是否需要转换
            if($payChannelObj->currency == 'USD'){

                if($params['amount']>$digitalFinanceMaxRecharge){
                    return $this->returnApiJson(config('language')[$this->language]['error204'], 0);
                }

                if($params['amount']<$digitalFinanceMinRecharge){
                    return $this->returnApiJson(config('language')[$this->language]['error34'], 0);
                }

                $digitalRate                              = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'digital_rate',$this->user->prefix);

                $playerDepositPayLog->amount              = $params['amount']*10000;
                $playerDepositPayLog->is_wallet_recharge = 1;

                if($payChannelObj->gift_ratio<0){
                    if($this->user->self_deductions_method==2){
                        $playerDepositPayLog->third_fee           = 0;
                    } else{
                        $playerDepositPayLog->third_fee           = bcdiv(abs($payChannelObj->gift_ratio)*$playerDepositPayLog->amount,100,0);
                    }
                } else{
                    $playerDepositPayLog->third_fee           = 0;
                }

                $params['amount']                         = bcdiv($params['amount'],$digitalRate,2);
                $param['amount']                          = $params['amount'];
                $playerDepositPayLog->pay                 = $params['amount'].'USDT';

            } else {

                if($params['amount']>$maxRecharge){
                    return $this->returnApiJson(config('language')[$this->language]['error204'], 0);
                }

                if($params['amount']<$minRecharge){
                    return $this->returnApiJson(config('language')[$this->language]['error34'], 0);
                }

                $playerDepositPayLog->amount              = $params['amount']*10000;

                if($payChannelObj->gift_ratio<0){
                    if($this->user->self_deductions_method==2){
                        $playerDepositPayLog->third_fee           = 0;
                    } else{
                        $playerDepositPayLog->third_fee           = bcdiv(abs($payChannelObj->gift_ratio)*$playerDepositPayLog->amount,100,0);
                    }
                } else{
                    $playerDepositPayLog->third_fee           = 0;
                }

                $playerDepositPayLog->pay                 = $params['amount'].$payChannelObj->currency;
            }

            $playerDepositPayLog->is_wallet_recharge      = $payChannelObj->third_wallet_id;

            if($payChannelObj->gift_ratio<0){
                if($this->user->self_deductions_method==2){
                    $playerDepositPayLog->arrivedamount           = $playerDepositPayLog->amount;
                } else{
                    $playerDepositPayLog->arrivedamount           = $playerDepositPayLog->amount*bcdiv((100+$payChannelObj->gift_ratio),100,4);
                }
            } else{
                $playerDepositPayLog->arrivedamount           = $playerDepositPayLog->amount*bcdiv((100+$payChannelObj->gift_ratio),100,4);
            }

            $playerDepositPayLog->status               = 0;

            $count                                     = 0;
            $tempOrderId                               = $param['orderid'];
            $param['has_realname']                     = $payChannelObj->has_realname;

            if(!$payChannelObj->is_anti_complaint){
                $param['orderid']                         = $tempOrderId;
                $playerDepositPayLog->pay_order_number    = $param['orderid'];
                    

                $pay                                      = new Pay($params['carrier_pay_channel_id']);

                if(isset($params['bankcode']) && !empty($params['bankcode'])){
                    $param['bankCode'] = $params['bankcode'];
                }

                $payData                                  = $pay->sendData($param);

                if(is_array($payData) && isset($payData['ticket'])){
                    $playerDepositPayLog->pay_order_channel_trade_number = $payData['ticket'];
                }
                    
                $playerDepositPayLog->save();

            } else {
                do{
                    $param['orderid']                         = $tempOrderId;
                    $playerDepositPayLog->pay_order_number    = $param['orderid'];
                    

                    $pay                                      = new Pay($params['carrier_pay_channel_id']);

                    if(isset($params['bankcode']) && !empty($params['bankcode'])){
                        $param['bankCode'] = $params['bankcode'];
                    }

                    $payData                                  = $pay->sendData($param);

                    if(is_array($payData) && isset($payData['ticket'])){
                        $playerDepositPayLog->pay_order_channel_trade_number = $payData['ticket'];
                    }
                    
                    $playerDepositPayLog->save();

                    $count ++;
                    $tempOrderId                              = 'CZ'.date('YmdHis').mt_rand(10000,99999);

                } while(!is_array($payData) && $count<10);
            }

            if(is_array($payData)){
                return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $payData);
            } else {
                return $this->returnApiJson($payData, 0);
            }
        }
    }
}