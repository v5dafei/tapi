<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerMessage;
use App\Models\CarrierBankCardType;
use App\Models\PlayerBankCard;
use App\Models\PlayerTransfer;
use App\Models\PlayerAccount;
use App\Models\CarrierUser;
use App\Models\Carrier;
use App\Models\Player;
use App\Models\CarrierPayFactory;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Def\PayChannel;
use App\Models\Conf\CarrierPayChannel;
use App\Models\CarrierPreFixDomain;
use App\Lib\Cache\Lock;
use App\Models\CarrierActivity;
use App\Models\Def\DigitalAddressLib;
use App\Models\ArbitrageBank;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\PlayerAlipay;
use App\Lib\Clog;

class PlayerWithdraw extends Model
{
    public $table = 'log_player_withdraw';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'carrier_id',
        'player_id',
        'pay_order_number',
        'pay_order_channel_trade_number',
        'third_part_pay_id',
        'carrier_pay_channel',
        'amount',
        'real_amount',
        'pay',
        'collection',
        'review_one_user_id',
        'review_one_time',
        'review_two_user_id',
        'review_two_time',
        'arrival_time',
        'player_bank_id',
        'type',
        'currency',
        'is_oneandone_withdrawal',
        'is_fraud_recharge',
        'is_auto_pay'
    ];

    protected $casts = [
    ];

    public static $rules = [];

    static function withdrawList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;


        if(isset($input['factory_id']) && !empty($input['factory_id'])){
            $carrierPayFactoryIds[]  = $input['factory_id'];
        } else{
            $carrierPayFactoryIds  = CarrierPayFactory::where('carrier_id',$carrier->id)->pluck('factory_id')->toArray();
        }
        
        $defPayChannelIds      = CarrierThirdPartPay::where('carrier_id',$carrier->id)->pluck('def_pay_channel_id')->toArray();
        $payChannelIds         = PayChannel::whereIn('factory_id',$carrierPayFactoryIds)->whereIn('id',$defPayChannelIds)->where('type',2)->pluck('id')->toArray();

        $carrierPayChannelIds  = [];

        //厂商
        if(isset($input['factory_id']) && in_array($input['factory_id'],$carrierPayFactoryIds)){
            //通道
            if(isset($input['pay_channel_id']) && in_array($input['pay_channel_id'],$payChannelIds)){
                $carrierThirdPartPayIds = CarrierThirdPartPay::where('carrier_id',$carrier->id)->where('def_pay_channel_id',$input['pay_channel_id'])->pluck('id')->toArray();
                $carrierPayChannelIds   = CarrierPayChannel::where('carrier_id',$carrier->id)->whereIn('binded_third_part_pay_id',$carrierThirdPartPayIds)->pluck('id')->toArray();
            } else{
                $carrierThirdPartPayIds = CarrierThirdPartPay::where('carrier_id',$carrier->id)->whereIn('def_pay_channel_id',$payChannelIds)->pluck('id')->toArray();
                $carrierPayChannelIds   = CarrierPayChannel::where('carrier_id',$carrier->id)->whereIn('binded_third_part_pay_id',$carrierThirdPartPayIds)->pluck('id')->toArray();
            }
        }

        $query          =self::select('log_player_withdraw.arrival_time','log_player_withdraw.is_fraud_recharge','inf_player.extend_id','log_player_withdraw.withdraw_fee','log_player_withdraw.is_hedging_account','log_player_withdraw.prefix','log_player_withdraw.third_fee','log_player_withdraw.id','log_player_withdraw.created_at','log_player_withdraw.pay_order_number','log_player_withdraw.pay_order_channel_trade_number','log_player_withdraw.pay','log_player_withdraw.remark','log_player_withdraw.collection','inf_player.player_id','inf_player.top_id','inf_player.parent_id','inf_player.user_name','inf_player.real_name','inf_player.win_lose_agent','log_player_withdraw.amount','log_player_withdraw.real_amount','inf_player_bank_cards.card_owner_name','inf_player_bank_cards.bank_Id','inf_player_bank_cards.card_account','log_player_withdraw.status','log_player_withdraw.review_one_user_id','log_player_withdraw.review_one_time','log_player_withdraw.review_two_user_id','log_player_withdraw.review_two_time','log_player_withdraw.frontremark','log_player_withdraw.arrival_time')
            ->leftJoin('inf_player_bank_cards','inf_player_bank_cards.id','=','log_player_withdraw.player_bank_id')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_withdraw.player_id')
            ->where('log_player_withdraw.carrier_id',$carrier->id)
            ->whereIn('log_player_withdraw.status',[1,-1,2,3,6,7])
            ->where('log_player_withdraw.review_status',-1)
            ->orderBy('log_player_withdraw.id','desc');

        $query1          =self::select(\DB::raw('sum(log_player_withdraw.amount) as amount'))
            ->leftJoin('inf_player_bank_cards','inf_player_bank_cards.id','=','log_player_withdraw.player_bank_id')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_withdraw.player_id')
            ->where('log_player_withdraw.carrier_id',$carrier->id)
            ->whereIn('log_player_withdraw.status',[1,-1,2,3,6,7])
            ->where('log_player_withdraw.review_status',-1)
            ->orderBy('log_player_withdraw.id','desc');

        if(count($carrierPayChannelIds)){
            $query->whereIn('log_player_withdraw.carrier_pay_channel',$carrierPayChannelIds);
            $query1->whereIn('log_player_withdraw.carrier_pay_channel',$carrierPayChannelIds);
        }

        if(isset($input['startTime']) && !empty(trim($input['startTime']))) {
            if(!strtotime($input['startTime'])){
                return '对不起，创建开始时间格式不正确';
            }
            $query->where('log_player_withdraw.created_at','>=',$input['startTime']);
            $query1->where('log_player_withdraw.created_at','>=',$input['startTime']);
        }

        if(isset($input['endTime']) && !empty(trim($input['endTime']))) {
            if(!strtotime($input['endTime'])){
                return '对不起，创建结束时间格式不正确';
            }
            $query->where('log_player_withdraw.created_at','<=',$input['endTime']);
            $query1->where('log_player_withdraw.created_at','<=',$input['endTime']);
        } 

        if(isset($input['arrivalStartTime']) && !empty(trim($input['arrivalStartTime']))) {
            if(!strtotime($input['arrivalStartTime'])){
                return '对不起，到帐开始时间格式不正确';
            }
            $query->where('log_player_withdraw.arrival_time','>=',strtotime($input['arrivalStartTime']));
            $query1->where('log_player_withdraw.arrival_time','>=',strtotime($input['arrivalStartTime']));
        }

        if(isset($input['arrivalEndTime']) && !empty(trim($input['arrivalEndTime']))) {
            if(!strtotime($input['arrivalEndTime'])){
                return '对不起，到帐结束时间格式不正确';
            }
            $query->where('log_player_withdraw.arrival_time','<=',strtotime($input['arrivalEndTime']));
            $query1->where('log_player_withdraw.arrival_time','<=',strtotime($input['arrivalEndTime']));
        }

        if(isset($input['extend_id']) && !empty(trim($input['extend_id']))) {
            $query->where('inf_player.extend_id',$input['extend_id']);
            $query1->where('inf_player.extend_id',$input['extend_id']);
        }

        if(isset($input['pay_order_number']) && !empty(trim($input['pay_order_number']))) {
            $query->where('log_player_withdraw.pay_order_number',$input['pay_order_number']);
            $query1->where('log_player_withdraw.pay_order_number',$input['pay_order_number']);
        }

        if(isset($input['is_hedging_account']) && in_array($input['is_hedging_account'],[0,1])) {
            $query->where('log_player_withdraw.is_hedging_account',$input['is_hedging_account']);
            $query1->where('log_player_withdraw.is_hedging_account',$input['is_hedging_account']);
        }

        if(isset($input['review_status']) && in_array($input['review_status'],[-1,0,1,2])) {
            $query->where('log_player_withdraw.review_status',$input['review_status']);
            $query1->where('log_player_withdraw.review_status',$input['review_status']);
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $playerIds = Player::where('user_name','like','%'.$input['user_name'].'%')->where('carrier_id',$carrier->id)->pluck('player_id')->toArray();
            if(count($playerIds)){
                $query->whereIn('log_player_withdraw.player_id',$playerIds);
                $query1->whereIn('log_player_withdraw.player_id',$playerIds);
            } else {
                $query->where('log_player_withdraw.player_id','');
                $query1->where('log_player_withdraw.player_id','');
            }
        }

        if(isset($input['win_lose_agent']) && in_array($input['win_lose_agent'],[0,1])) {
            $query->where('inf_player.win_lose_agent',$input['win_lose_agent']);
            $query1->where('inf_player.win_lose_agent',$input['win_lose_agent']);
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('log_player_withdraw.player_id',$input['player_id']);
            $query1->where('log_player_withdraw.player_id',$input['player_id']);
        }

        if(isset($input['prefix']) && trim($input['prefix']) != '') {
            $query->where('log_player_withdraw.prefix',$input['prefix']);
            $query1->where('log_player_withdraw.prefix',$input['prefix']);
        }

        if(isset($input['status']) && trim($input['status']) != '') {
            $query->where('log_player_withdraw.status',$input['status']);
            $query1->where('log_player_withdraw.status',$input['status']);
        }

        $totalAmount   = $query1->first();
        $total         = $query->count();
        $items         = $query->skip($offset)->take($pageSize)->get();


        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        $carrierBankCardTypes   = CarrierBankCardType::where('carrier_id',$carrier->id)->get();
        $bank    = [];
        foreach ($carrierBankCardTypes as $key => $value) {
            $bank[$value->id]= $value->bank_name;
        }

        foreach ($items as $k => &$v) {
            $playerAccount    = PlayerAccount::where('player_id',$v->player_id)->first();
            $v->balance       = $playerAccount->balance;
            $v->agentbalance  = $playerAccount->agentbalance;
            $v->agentfrozen   = $playerAccount->agentfrozen;
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
            if($v->arrival_time == 0){
                $v->arrival_time ='';
            } else{
                $v->arrival_time =date('Y-m-d H:i:s',$v->arrival_time);
            }
        }

        $carrierUsers      = CarrierUser::where('username','<>','super_admin')->get();

        $k = [];
        $v = [];
        foreach ($carrierUsers as $key => $value) {
            $k[] = $value->id;
            $v[] = $value->username;
        }

        return ['totalamount'=>$totalAmount->amount,'banks'=>$bank,'item' => $items, 'total' => $total,'userIds'=>$k,'userNames'=>$v,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    static function agentWithdrawList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;


        if(isset($input['factory_id']) && !empty($input['factory_id'])){
            $carrierPayFactoryIds[]  = $input['factory_id'];
        } else{
            $carrierPayFactoryIds  = CarrierPayFactory::where('carrier_id',$carrier->id)->pluck('factory_id')->toArray();
        }
        
        $defPayChannelIds      = CarrierThirdPartPay::where('carrier_id',$carrier->id)->pluck('def_pay_channel_id')->toArray();
        $payChannelIds         = PayChannel::whereIn('factory_id',$carrierPayFactoryIds)->whereIn('id',$defPayChannelIds)->where('type',2)->pluck('id')->toArray();

        $carrierPayChannelIds  = [];

        //厂商
        if(isset($input['factory_id']) && in_array($input['factory_id'],$carrierPayFactoryIds)){
            //通道
            if(isset($input['pay_channel_id']) && in_array($input['pay_channel_id'],$payChannelIds)){
                $carrierThirdPartPayIds = CarrierThirdPartPay::where('carrier_id',$carrier->id)->where('def_pay_channel_id',$input['pay_channel_id'])->pluck('id')->toArray();
                $carrierPayChannelIds   = CarrierPayChannel::where('carrier_id',$carrier->id)->whereIn('binded_third_part_pay_id',$carrierThirdPartPayIds)->pluck('id')->toArray();
            } else{
                $carrierThirdPartPayIds = CarrierThirdPartPay::where('carrier_id',$carrier->id)->whereIn('def_pay_channel_id',$payChannelIds)->pluck('id')->toArray();
                $carrierPayChannelIds   = CarrierPayChannel::where('carrier_id',$carrier->id)->whereIn('binded_third_part_pay_id',$carrierThirdPartPayIds)->pluck('id')->toArray();
            }
        }

        $query          =self::select('log_player_withdraw.id','log_player_withdraw.created_at','log_player_withdraw.pay_order_number','log_player_withdraw.pay_order_channel_trade_number','log_player_withdraw.pay','log_player_withdraw.remark','log_player_withdraw.collection','inf_player.player_id','inf_player.top_id','inf_player.parent_id','inf_player.user_name','inf_player.real_name','inf_player.win_lose_agent','log_player_withdraw.amount','log_player_withdraw.real_amount','inf_player_bank_cards.card_owner_name','inf_player_bank_cards.bank_Id','inf_player_bank_cards.card_account','log_player_withdraw.status','log_player_withdraw.review_one_user_id','log_player_withdraw.review_one_time','log_player_withdraw.review_two_user_id','log_player_withdraw.review_two_time','log_player_withdraw.frontremark')
            ->leftJoin('inf_player_bank_cards','inf_player_bank_cards.id','=','log_player_withdraw.player_bank_id')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_withdraw.player_id')
            ->where('log_player_withdraw.carrier_id',$carrier->id)
            ->whereIn('log_player_withdraw.status',[1,-1,2,3,6,7])
            ->where('log_player_withdraw.review_status','<>',-1)
            ->orderBy('log_player_withdraw.id','desc');

        $query1          =self::select(\DB::raw('sum(log_player_withdraw.amount) as amount'))
            ->leftJoin('inf_player_bank_cards','inf_player_bank_cards.id','=','log_player_withdraw.player_bank_id')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_withdraw.player_id')
            ->where('log_player_withdraw.carrier_id',$carrier->id)
            ->whereIn('log_player_withdraw.status',[1,-1,2,3,6,7])
            ->where('log_player_withdraw.review_status','<>',-1)
            ->orderBy('log_player_withdraw.id','desc');

        if(count($carrierPayChannelIds)){
            $query->whereIn('log_player_withdraw.carrier_pay_channel',$carrierPayChannelIds);
            $query1->whereIn('log_player_withdraw.carrier_pay_channel',$carrierPayChannelIds);
        }

        if(isset($input['startTime']) && !empty(trim($input['startTime']))) {
            if(!strtotime($input['startTime'])){
                return '对不起，开始时间格式不正确';
            }
            $query->where('log_player_withdraw.created_at','>=',$input['startTime']);
            $query1->where('log_player_withdraw.created_at','>=',$input['startTime']);
        } else {
            $query->where('log_player_withdraw.created_at','>=',date('Y-m-d').' 00:00:00');
            $query1->where('log_player_withdraw.created_at','>=',date('Y-m-d').' 00:00:00');
        }

        if(isset($input['endTime']) && !empty(trim($input['endTime']))) {
            if(!strtotime($input['endTime'])){
                return '对不起，开始时间格式不正确';
            }
            $query->where('log_player_withdraw.created_at','<=',$input['endTime']);
            $query1->where('log_player_withdraw.created_at','<=',$input['endTime']);
        } else {
            $query->where('log_player_withdraw.created_at','<=',date('Y-m-d').' 23:59:59');
            $query1->where('log_player_withdraw.created_at','<=',date('Y-m-d').' 23:59:59');
        }

        if(isset($input['pay_order_number']) && !empty(trim($input['pay_order_number']))) {
            $query->where('log_player_withdraw.pay_order_number',$input['pay_order_number']);
            $query1->where('log_player_withdraw.pay_order_number',$input['pay_order_number']);
        }

        if(isset($input['review_status']) && in_array($input['review_status'],[-1,0,1,2])) {
            $query->where('log_player_withdraw.review_status',$input['review_status']);
            $query1->where('log_player_withdraw.review_status',$input['review_status']);
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $player = Player::where('user_name',$input['user_name'])->where('carrier_id',$carrier->id)->first();
            if($player){
                $query->where('log_player_withdraw.player_id',$player->player_id);
                $query1->where('log_player_withdraw.player_id',$player->player_id);
            } else {
                $query->where('log_player_withdraw.player_id','');
                $query1->where('log_player_withdraw.player_id','');
            }
        }

        if(isset($input['win_lose_agent']) && in_array($input['win_lose_agent'],[0,1])) {
            $query->where('inf_player.win_lose_agent',$input['win_lose_agent']);
            $query1->where('inf_player.win_lose_agent',$input['win_lose_agent']);
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('log_player_withdraw.player_id',$input['player_id']);
            $query1->where('log_player_withdraw.player_id',$input['player_id']);
        }

        if(isset($input['status']) && trim($input['status']) != '') {
            $query->where('log_player_withdraw.status',$input['status']);
            $query1->where('log_player_withdraw.status',$input['status']);
        }

        $totalAmount   = $query1->first();
        $total         = $query->count();
        $items         = $query->skip($offset)->take($pageSize)->get();

        $carrierBankCardTypes   = CarrierBankCardType::where('carrier_id',$carrier->id)->get();
        $bank    = [];
        foreach ($carrierBankCardTypes as $key => $value) {
            $bank[$value->id]= $value->bank_name;
        }

        $carrierUsers      = CarrierUser::where('username','<>','super_admin')->get();

        $k = [];
        $v = [];
        foreach ($carrierUsers as $key => $value) {
            $k[] = $value->id;
            $v[] = $value->username;
        }

        return ['totalamount'=>$totalAmount->amount,'banks'=>$bank,'item' => $items, 'total' => $total,'userIds'=>$k,'userNames'=>$v,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    static function withdrawAuditList($carrier,$user)
    {
        $input          = request()->all();
        $query          = self::select('log_player_withdraw.carrier_id','is_fraud_recharge','log_player_withdraw.is_suspend','log_player_withdraw.prefix','log_player_withdraw.is_hedging_account','inf_player.is_supplementary_data','inf_player.extend_id','log_player_withdraw.player_digital_address','log_player_withdraw.id','log_player_withdraw.prefix','log_player_withdraw.remark','log_player_withdraw.type','log_player_withdraw.currency','log_player_withdraw.pay','log_player_withdraw.pay_order_channel_trade_number','log_player_withdraw.collection','log_player_withdraw.created_at','inf_player.player_id','inf_player.top_id','inf_player.parent_id','inf_player.is_withdraw_mobile','inf_player.user_name','inf_player.real_name','log_player_withdraw.pay_order_number','inf_player.user_name','inf_player.real_name','log_player_withdraw.amount','log_player_withdraw.real_amount','inf_player_bank_cards.card_owner_name','inf_player_bank_cards.bank_Id','inf_player_bank_cards.card_account','log_player_withdraw.status','log_player_withdraw.review_one_user_id','log_player_withdraw.review_one_time','log_player_withdraw.review_two_user_id','log_player_withdraw.review_two_time','log_player_withdraw.status','inf_player.win_lose_agent','inf_player.has_software_login')
            ->leftJoin('inf_player_bank_cards','inf_player_bank_cards.id','=','log_player_withdraw.player_bank_id')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_withdraw.player_id')
            ->where('log_player_withdraw.carrier_id',$carrier->id)
            ->whereIn('log_player_withdraw.status',[0,-1,4,5])
            ->orderBy('log_player_withdraw.id','desc');

        if(isset($input['pay_order_number']) && !empty(trim($input['pay_order_number']))) {
            $query->where('log_player_withdraw.pay_order_number',$input['pay_order_number']);
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $playerIds = Player::where('user_name','like',$input['user_name'])->where('carrier_id',$carrier->id)->pluck('player_id')->toArray();
            if(count($playerIds)){
                $query->whereIn('log_player_withdraw.player_id',$playerIds);
            } else {
                $query->where('log_player_withdraw.player_id','');
            }
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('log_player_withdraw.player_id',$input['player_id']);
        }

        if(isset($input['is_hedging_account']) && in_array($input['is_hedging_account'],[0,1])) {
            $query->where('log_player_withdraw.is_hedging_account',$input['is_hedging_account']);
        }

        if(isset($input['prefix']) && trim($input['prefix']) != '') {
            $query->where('log_player_withdraw.prefix',$input['prefix']);
        }

        if(isset($input['win_lose_agent']) && in_array($input['win_lose_agent'],[0,1])) {
            $query->where('inf_player.win_lose_agent',$input['win_lose_agent']);
        }

        if(isset($input['extend_id']) && !empty(trim($input['extend_id']))) {
            $query->where('inf_player.extend_id',$input['extend_id']);
        }

        $carrierBankCardTypes   = CarrierBankCardType::where('carrier_id',$carrier->id)->get();
        $bank    = [];
        foreach ($carrierBankCardTypes as $key => $value) {
            $bank[$value->id]= $value->bank_name;
        }
        $items   = $query->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($items as $key => &$value) {
            //查询上次存款记录
            $playerDepositPayLog =  PlayerDepositPayLog::where('player_id',$value->player_id)->where('status',1)->orderBy('id','desc')->first();
            if($playerDepositPayLog){
                $value->depositPayInfo = $playerDepositPayLog->collection.'|'.$playerDepositPayLog->pay;
            } else{
                $value->depositPayInfo = '';
            }

            $playerAccount         = PlayerAccount::where('player_id',$value->player_id)->first();

            $value->balance        = $playerAccount->balance;
            $value->agentbalance   = $playerAccount->agentbalance;
            $value->agentfrozen    = $playerAccount->agentfrozen;
            $value->multiple_name  = $carrierPreFixDomainArr[$value->prefix];

            //查询是否拉黑银行卡或拉黑数字币出款
            $isArbitrage = 0;
            if(in_array($value->type,[3,4,6,7,8,9,10,11])){
                $digitalAddressLib = DigitalAddressLib::where('type',$value->type)->where('address',$value->player_digital_address)->where('is_arbitrage',1)->first();
                if($digitalAddressLib){
                    $isArbitrage = 1;
                }
            } else{
                $collectionArr = explode('|',$value->collection);
                $arbitrageBank = ArbitrageBank::where('card_account',$collectionArr[1])->first();
                if($arbitrageBank){
                    $isArbitrage = 1;
                }
            } 
            
            $value->is_arbitrage  = $isArbitrage;

            //查询银行卡姓名
            $cardOwnerNames    = PlayerBankCard::where('player_id',$value->player_id)->pluck('card_owner_name')->toArray();
            $ownerBankNames    = '';
            $cardOwnerNamesArr = [];

            if(!empty($value->real_name)){
                $cardOwnerNamesArr[] = $value->real_name;
            }
            
            if(count($cardOwnerNames) > 0){
                $cardOwnerNamesArr   = array_merge($cardOwnerNamesArr,$cardOwnerNames);
            }

            //查询支付宝姓名
            $realNames    = PlayerAlipay::where('player_id',$value->player_id)->pluck('real_name')->toArray();
            if(count($realNames) > 0){
                $cardOwnerNamesArr              = array_merge($realNames,$cardOwnerNamesArr);
            }

            $cardOwnerNamesArr                  = array_unique($cardOwnerNamesArr);

            if(count($cardOwnerNamesArr)){
                $ownerBankNames                 = implode(',', $cardOwnerNamesArr);
            }

            //查询是否多个姓名
            $withdrawName          = [];
            $historyDigitals       = self::where('player_id',$value->player_id)->whereIn('type',[3,4,6,8,9,10,11])->pluck('player_digital_address')->toArray();
            $digitalNames          = DigitalAddressLib::whereIn('address',$historyDigitals)->pluck('name')->toArray();
            if(count($digitalNames)){
                $withdrawName = array_unique($digitalNames);
            }

            $historyCollections     = self::where('player_id',$value->player_id)->where('type',0)->whereIn('status',[1,2])->pluck('collection')->toArray();
            if(count($historyCollections)){
                foreach ($historyCollections as $key1 => $value1) {
                    $collectionArr = explode('|',$value1);
                    $withdrawName[]= $collectionArr[2];
                }
            }
            $withdrawName                   = array_unique($withdrawName);
            if(empty($ownerBankNames)){
                $value->multiple_withdraw_name  = implode(',', $withdrawName);
            } else{
                if(empty($withdrawName)){
                    $value->multiple_withdraw_name  = $ownerBankNames;
                } else{
                    $value->multiple_withdraw_name  = $ownerBankNames.','.implode(',', $withdrawName);
                }
            }

            $multipleWithdrawNamesArr = explode(',',$value->multiple_withdraw_name);
            $multipleWithdrawNamesArr = array_unique($multipleWithdrawNamesArr);
            $value->multiple_withdraw_name  = implode(',', $multipleWithdrawNamesArr);

            if($value->is_hedging_account){
                $value->need_set_name = 0;
            }else{
                //需设用户名
                if(in_array($value->type,[3,4,6,8,9,10,11])){
                    $existDigitalAddressLib = DigitalAddressLib::where('address',$value->player_digital_address)->first();
                    if(!$existDigitalAddressLib){
                        $value->need_set_name = 1;
                    } else{
                        $value->need_set_name = 0;
                    }
                } else{
                    $value->need_set_name = 0;
                }
            }

            $value->pointKill = 0;
        }

        $role    = [];
        if($user->is_super_admin==1){
            $role['examine'] = 1;
            $role['pay']     = 1;
        } else {
            $withdrawFirstAudit     = CarrierCache::getCarrierConfigure($carrier->id,'withdraw_first_audit');

            $withdrawSecondAudit     = CarrierCache::getCarrierConfigure($carrier->id,'withdraw_second_audit');

            if($user->team_id == $withdrawFirstAudit){
                $role['examine'] = 1;
            } else {
                $role['examine'] = 0;
            }

            if($user->team_id == $withdrawSecondAudit){
                $role['pay']     = 1;
            } else {
                $role['pay']     = 0;
            }
        }

        $carrierUsers      = CarrierUser::where('username','<>','super_admin')->get();
        $users             = [];
        foreach ($carrierUsers as $key => $value) {
            $users[$value->id] = $value->username;
        }
        return ['item' => $items,'banks'=>$bank,'role'=>$role,'users'=>$users];
    }

    public static function withdrawAudit($carrierUser,$carrier)
    {
        $input          = request()->all();

        if(!isset($input['id']) || empty($input['id']) ) {
            return "对不起，订单号不能为空";
        }

        if(!isset($input['status']) || empty($input['status']) || !in_array($input['status'], [1,-1])) {
            return "对不起，审核状态取值不正确";
        }

        $cacheKey = "playerWithdraw_".$input['id'];
        $redisLock = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            return '对不起，系统错误请稍后再试';
        } else {
            $withdrawFirstAudit     = CarrierCache::getCarrierConfigure($carrier->id,'withdraw_first_audit');
            try {
                \DB::beginTransaction();

                $logPlayerWithdraw = self::where('id',$input['id'])->where('carrier_id',$carrier->id)->lockForUpdate()->first();

                if(!$logPlayerWithdraw) {
                    Lock::release($redisLock);
                     return "对不起，此订单不存在";
                }

                if($logPlayerWithdraw->is_suspend == 1) {
                    Lock::release($redisLock);
                     return "对不起，此订单已挂起，请选取消挂起";
                }

                if($logPlayerWithdraw->status != 0) {
                    Lock::release($redisLock);
                     return "对不起，此订单已审核，无需重复审核";
                }

                if($carrierUser->is_super_admin)
                {
                    $logPlayerWithdraw->review_one_user_id = $carrierUser->id;
                    $logPlayerWithdraw->review_one_time    = time();
                    $logPlayerWithdraw->remark             = isset($input['remark'])?$logPlayerWithdraw->remark.$input['remark']:$logPlayerWithdraw->remark;
                    $logPlayerWithdraw->frontremark        = isset($input['frontremark'])?$logPlayerWithdraw->frontremark.$input['frontremark']:$logPlayerWithdraw->frontremark;

                    if($input['status']==-1) {
                        $logPlayerWithdraw->status   = 3;
                        //帐变
                        self::cancelWithdraw($logPlayerWithdraw);
                    } else{
                        //提款需钱包实名
                        if(in_array($logPlayerWithdraw->type,[3,4,6,8,9,10,11]) && !$logPlayerWithdraw->is_hedging_account){
                            $digitalAddressLib = DigitalAddressLib::where('type',$logPlayerWithdraw->type)->where('address',$logPlayerWithdraw->player_digital_address)->first();
                            if(!$digitalAddressLib){
                                Lock::release($redisLock);
                                return "对不起，钱包请先实名";
                            }
                        }
                        $logPlayerWithdraw->status             = 4;
                    }
                    $logPlayerWithdraw->save();
                } else {
                    $logPlayerWithdraw->remark             = isset($input['remark'])?$logPlayerWithdraw->remark.$input['remark']:$logPlayerWithdraw->remark;
                    $logPlayerWithdraw->frontremark        = isset($input['frontremark'])?$logPlayerWithdraw->frontremark.$input['frontremark']:$logPlayerWithdraw->frontremark;

                    if(empty($withdrawFirstAudit)) {
                        Lock::release($redisLock);

                        return "对不起，您没有审核权限";
                    }
                       
                    if($carrierUser->team_id != $withdrawFirstAudit){
                        Lock::release($redisLock);
                        return "对不起，您没有审核权限";
                    }

                    $logPlayerWithdraw->review_one_user_id = $carrierUser->id;
                    $logPlayerWithdraw->review_one_time    = time();

                    if($input['status']==-1) {
                        $logPlayerWithdraw->status          = 3;
                        //帐变
                        self::cancelWithdraw($logPlayerWithdraw);
                    } else{
                        //提款需钱包实名
                        if(in_array($logPlayerWithdraw->type,[3,4,6,8,9,10,11]) && !$logPlayerWithdraw->is_hedging_account){
                            $digitalAddressLib = DigitalAddressLib::where('type',$logPlayerWithdraw->type)->where('address',$logPlayerWithdraw->player_digital_address)->first();
                            if(!$digitalAddressLib){
                                Lock::release($redisLock);
                                return "对不起，钱包请先实名";
                            }
                        }
                        
                        $logPlayerWithdraw->status      = 4;
                    }

                    $logPlayerWithdraw->save();
                }

                \DB::commit();
                Lock::release($redisLock);

                return true;
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('玩家申请提现异常'.$e->getMessage()); 
                return '操作异常2：'.$e->getMessage();
            }
        }
    }

     
    public static function withdrawsuccess($carrierUser,$carrier)
    {
        $input          = request()->all();

        if(!isset($input['id']) || empty($input['id']) ){
            return "对不起，订单号不能为空";
        }

        $withdrawSecondAudit    = CarrierCache::getCarrierConfigure($carrier->id,'withdraw_second_audit');
        $withdrawSecondAuditArr = explode(',', $withdrawSecondAudit);
        $allCarrierUser         = CarrierUser::whereIn('team_id',$withdrawSecondAuditArr)->pluck('id')->toArray();

        if(!in_array($carrierUser->id, $allCarrierUser) && $carrierUser->is_super_admin != 1) {
            return '对不起您没有操作权限';
        }

        $cacheKey = "playerWithdraw_".$input['id'];
        $redisLock = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            return '对不起，系统错误请稍后再试';
        } else {
            if(!isset($input['remark'])){
                $input['remark']='';
            }
            try {
                \DB::beginTransaction();

                $logPlayerWithdraw = self::where('id',$input['id'])->where('carrier_id',$carrier->id)->lockForUpdate()->first();

                if(!$logPlayerWithdraw) {
                    Lock::release($redisLock);
                    return "对不起，此订单不存在";
                }

                if($logPlayerWithdraw->status!=-1 && $logPlayerWithdraw->status!=4) {
                    Lock::release($redisLock);
                    return "对不起，状态错误";
                }

                if($logPlayerWithdraw->is_suspend == 1) {
                    Lock::release($redisLock);
                    return "对不起，此订单已挂起，请先取消挂起";
                }

                if(isset($input['is_oneandone_withdrawal']) && $input['is_oneandone_withdrawal']==1){
                    $logPlayerWithdraw->is_oneandone_withdrawal  = 1;
                }

                if(isset($input['is_empty_withdrawal']) && $input['is_empty_withdrawal']==1){
                    $logPlayerWithdraw->is_empty_withdrawal  = 1;
                }

                $logPlayerWithdraw->status             = 2;
                $logPlayerWithdraw->pay                = '';
                $logPlayerWithdraw->payment_channel    = '';
                $logPlayerWithdraw->review_two_user_id = $carrierUser->id;
                $logPlayerWithdraw->remark             = empty($logPlayerWithdraw->remark)? $input['remark']:$logPlayerWithdraw->remark.'|'.$input['remark'];
                $logPlayerWithdraw->review_two_time    = time();
                $logPlayerWithdraw->arrival_time       = time();
                $logPlayerWithdraw->save();
                self::successWithdraw($logPlayerWithdraw);

                //活动提现统计
                $recentPlayerDepositPay  = PlayerDepositPayLog::where('player_id',$logPlayerWithdraw->player_id)->where('status',1)->where('created_at','<',$logPlayerWithdraw->created_at)->orderBy('id','desc')->first();
                if($recentPlayerDepositPay && !empty($recentPlayerDepositPay->activityids)){
                    $currCarrierActivity                   = CarrierActivity::where('id',$recentPlayerDepositPay->activityids)->first();
                    $currCarrierActivity->withdraw_amount  = $currCarrierActivity->withdraw_amount + $logPlayerWithdraw->amount;
                    $currCarrierActivity->withdraw_account = $currCarrierActivity->withdraw_account + 1;
                    $currCarrierActivity->save();
                }

                //活动提现统计结束 

                //注册送提现统计
                if($logPlayerWithdraw->amount==1030000){
                    $existplayerTransfer =  playerTransfer::where('player_id',$logPlayerWithdraw->player_id)->where('type','register_gift')->first();
                    if($existplayerTransfer){
                        $registerReceiveActivityid = CarrierCache::getCarrierMultipleConfigure($logPlayerWithdraw->carrier_id,'register_receive_activityid',$logPlayerWithdraw->prefix);
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
                Lock::release($redisLock);

                return true;
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('手动出款异常'.$e->getMessage()); 

                return '操作异常2：'.$e->getMessage();
            }
        }
    }

    private static function cancelWithdraw($withDrawLog)
    {
        $enableSafeBox                                   = CarrierCache::getCarrierMultipleConfigure($withDrawLog->carrier_id,'enable_safe_box',$withDrawLog->prefix);
        $agentSingleBackground                           = CarrierCache::getCarrierMultipleConfigure($withDrawLog->carrier_id,'agent_single_background',$withDrawLog->prefix);
        $playerAccount                                   = PlayerAccount::where('player_id',$withDrawLog->player_id)->lockForUpdate()->first();
        $player                                          = Player::where('player_id',$withDrawLog->player_id)->first();
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
        $playerTransfer->project_id                      = $withDrawLog->pay_order_number;
        $playerTransfer->day_m                           = date('Ym',time());
        $playerTransfer->day                             = date('Ymd',time());
        $playerTransfer->amount                          = $withDrawLog->amount;

        $playerTransfer->type                            = 'withdraw_cancel';
        $playerTransfer->type_name                       = config('language')[$language]['text53'];

        if($enableSafeBox || ($agentSingleBackground==1 &&  $withDrawLog->is_agent==1)){
            $playerTransfer->before_balance                  = $playerAccount->balance;
            $playerTransfer->balance                         = $playerAccount->balance;
            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
            $playerTransfer->frozen_balance                  = $playerAccount->frozen;

            $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
            $playerTransfer->agent_balance                = $playerAccount->agentbalance + $withDrawLog->amount;
            $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
            $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen  - $withDrawLog->amount;
            $playerAccount->agentbalance                  = $playerTransfer->agent_balance;
            $playerAccount->agentfrozen                   = $playerTransfer->agent_frozen_balance;
        } else{
            $playerTransfer->before_balance                  = $playerAccount->balance;
            $playerTransfer->balance                         = $playerAccount->balance + $withDrawLog->amount;
            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
            $playerTransfer->frozen_balance                  = $playerAccount->frozen - $withDrawLog->amount;

            $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
            $playerTransfer->agent_balance                = $playerAccount->agentbalance;
            $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
            $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

            $playerAccount->balance                  = $playerTransfer->balance;
            $playerAccount->frozen                   = $playerTransfer->frozen_balance;
        }

        $playerTransfer->save();
        $playerAccount->save();
    }

    public static function successWithdraw($withDrawLog)
    {
        $enableSafeBox                                   = CarrierCache::getCarrierMultipleConfigure($withDrawLog->carrier_id,'enable_safe_box',$withDrawLog->prefix);
        $agentSingleBackground                           = CarrierCache::getCarrierMultipleConfigure($withDrawLog->carrier_id,'agent_single_background',$withDrawLog->prefix);
        $playerAccount                                   = PlayerAccount::where('player_id',$withDrawLog->player_id)->lockForUpdate()->first();
        $player                                          = Player::where('player_id',$withDrawLog->player_id)->first();
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
        $playerTransfer->mode                            = 2;
        $playerTransfer->project_id                      = $withDrawLog->pay_order_number;
        $playerTransfer->day_m                           = date('Ym',time());
        $playerTransfer->day                             = date('Ymd',time());
        $playerTransfer->amount                          = $withDrawLog->amount;
        $playerTransfer->prefix                          = $withDrawLog->prefix;

        $playerTransfer->type                            = 'withdraw_finish';
        $playerTransfer->type_name                       = config('language')[$language]['text36'];

        if(!empty($withDrawLog->player_digital_address)){
            $playerTransfer->remark                   = 1;
        }

        if($enableSafeBox || ($agentSingleBackground==1 &&  $withDrawLog->is_agent==1)){
            $playerTransfer->before_balance               = $playerAccount->balance;
            $playerTransfer->balance                      = $playerAccount->balance;
            $playerTransfer->before_frozen_balance        = $playerAccount->frozen;
            $playerTransfer->frozen_balance               = $playerAccount->frozen;
            $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
            $playerTransfer->agent_balance                = $playerAccount->agentbalance;
            $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
            $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen  - $withDrawLog->amount;
            $playerAccount->agentbalance                  = $playerTransfer->agent_balance;
            $playerAccount->agentfrozen                   = $playerTransfer->agent_frozen_balance;
        } else{
            $playerTransfer->before_balance               = $playerAccount->balance;
            $playerTransfer->balance                      = $playerAccount->balance;
            $playerTransfer->before_frozen_balance        = $playerAccount->frozen;
            $playerTransfer->frozen_balance               = $playerAccount->frozen - $withDrawLog->amount;
            $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
            $playerTransfer->agent_balance                = $playerAccount->agentbalance;
            $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
            $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

            $playerAccount->balance                  = $playerTransfer->balance;
            $playerAccount->frozen                   = $playerTransfer->frozen_balance;
        }

        $playerTransfer->save();
        $playerAccount->save();

        $playerMessage                                   = new PlayerMessage();
        $playerMessage->carrier_id                       = $playerAccount->carrier_id;
        $playerMessage->player_id                        = $playerAccount->player_id;
        $playerMessage->type                             = 1;
        $playerMessage->title                            = config('main')['noticetemplate'][$language]['withdrawsuccess']['title'];
        $playerMessage->content                          = str_replace('amount',bcdiv($withDrawLog->amount, 10000,0),str_replace('startTime',$withDrawLog->created_at,config('main')['noticetemplate'][$language]['withdrawsuccess']['content']));
        $playerMessage->is_read                          = 0;
        $playerMessage->admin_id                         = 0;
        $playerMessage->save();


        //1+1活动提现统计
        if($withDrawLog->is_oneandone_withdrawal){
            $oneAndOneWithdrawalAmount = CarrierCache::getCarrierMultipleConfigure($withDrawLog->carrier_id,'one_and_one_withdrawal_amount',$withDrawLog->prefix);
            $oneAndOneWithdrawalAmount += bcdiv($withDrawLog->amount,10000,0);
            CarrierMultipleFront::where('carrier_id',$withDrawLog->carrier_id)->where('prefix',$withDrawLog->prefix)->where('sign','one_and_one_withdrawal_amount')->update(['value'=>$oneAndOneWithdrawalAmount]);
            CarrierCache::flushCarrierMultipleConfigure($withDrawLog->carrier_id,$withDrawLog->prefix);
        }
    }
}
