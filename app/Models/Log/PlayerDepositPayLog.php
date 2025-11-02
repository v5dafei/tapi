<?php
namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Lib\Cache\SystemCache;
use App\Lib\Cache\CarrierCache;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Def\PayChannel;
use App\Models\Def\PayFactory;
use App\Models\PlayerTransfer;
use App\Models\CarrierBankCard;
use App\Models\PlayerBankCard;
use App\Models\CarrierUser;
use App\Models\Carrier;
use App\Models\Player;
use App\Models\CarrierPayFactory;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\CarrierPreFixDomain;

class PlayerDepositPayLog extends Model
{

    public static $prefix = 'ply';

    public $table    = 'log_player_deposit_pay';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'player_id',
        'user_name',
        'carrier_id',
        'pay_order_number',
        'pay_order_channel_trade_number',
        'carrier_pay_channel',
        'bank_no',
        'carrier_bankcard_id',
        'deposit_account',
        'deposit_username',
        'bank_id',
        'amount',
        'status',
        'review_user_id',
        'review_time',
        'activityids',
        'day',
        'depositimg',
        'currency',
        'txid',
        'is_wallet_recharge'
    ];

    protected $casts = [
    ];

    public static $rules = [];

    static function depositList($carrier)
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
        $payChannelIds         = PayChannel::whereIn('factory_id',$carrierPayFactoryIds)->whereIn('id',$defPayChannelIds)->where('type',1)->pluck('id')->toArray();

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

        $query          = self::select('log_player_deposit_pay.*','inf_player.user_name','inf_player.real_name')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_deposit_pay.player_id')
            ->where('log_player_deposit_pay.status','<',2)
            ->where('log_player_deposit_pay.carrier_id',$carrier->id)
            ->orderBy('log_player_deposit_pay.id','desc');


        $query1          = self::select(\DB::raw('sum(log_player_deposit_pay.amount) as amount'),\DB::raw('sum(log_player_deposit_pay.arrivedamount) as arrivedamount'))
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_deposit_pay.player_id')
            ->where('log_player_deposit_pay.status','<',2)
            ->where('log_player_deposit_pay.carrier_id',$carrier->id)
            ->orderBy('log_player_deposit_pay.id','desc');

        if(isset($input['type'])){
            if($input['type']==1){
                $query->where('log_player_deposit_pay.carrier_bankcard_id','>',0);
            } else if($input['type']==2){
                $query->whereIn('log_player_deposit_pay.digital_type',[1,2]);
            } else if($input['type']==3){
                $query->where('log_player_deposit_pay.carrier_bankcard_id',0)->where('log_player_deposit_pay.digital_type',0);
            }
        }

        if(count($carrierPayChannelIds)){
            $query->whereIn('log_player_deposit_pay.carrier_pay_channel',$carrierPayChannelIds);
            $query1->whereIn('log_player_deposit_pay.carrier_pay_channel',$carrierPayChannelIds);
        }

        if(isset($input['is_agent']) && in_array($input['is_agent'],[0,1])){
            $query->where('log_player_deposit_pay.is_agent',$input['is_agent']);
            $query1->where('log_player_deposit_pay.is_agent',$input['is_agent']);
        }

        if(isset($input['is_hedging_account']) && in_array($input['is_hedging_account'],[0,1])){
            $query->where('log_player_deposit_pay.is_hedging_account',$input['is_hedging_account']);
            $query1->where('log_player_deposit_pay.is_hedging_account',$input['is_hedging_account']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('log_player_deposit_pay.prefix',$input['prefix']);
            $query1->where('log_player_deposit_pay.prefix',$input['prefix']);
        }

        if(isset($input['is_refill']) && in_array($input['is_refill'],[0,1])){
            $query->where('log_player_deposit_pay.is_refill',$input['is_refill']);
            $query1->where('log_player_deposit_pay.is_refill',$input['is_refill']);
        }

        if(isset($input['startTime']) &&  strtotime($input['startTime'])) {
            $query->where('log_player_deposit_pay.created_at','>=',$input['startTime']);
            $query1->where('log_player_deposit_pay.created_at','>=',$input['startTime']);
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])) {
            $query->where('log_player_deposit_pay.created_at','<=',$input['endTime']);
            $query1->where('log_player_deposit_pay.created_at','<=',$input['endTime']);
        }

        if(isset($input['arrivalStartTime']) && strtotime($input['arrivalStartTime'])){
            $query->where('log_player_deposit_pay.review_time','>=',strtotime($input['arrivalStartTime']));
            $query1->where('log_player_deposit_pay.review_time','>=',strtotime($input['arrivalStartTime']));
        }

        if(isset($input['arrivalEndTime']) && strtotime($input['arrivalEndTime'])){
            $query->where('log_player_deposit_pay.review_time','<=',strtotime($input['arrivalEndTime']));
            $query1->where('log_player_deposit_pay.review_time','<=',strtotime($input['arrivalEndTime']));
        }

        if(isset($input['pay_order_number']) && !empty(trim($input['pay_order_number']))) {
            $query->where('log_player_deposit_pay.pay_order_number',$input['pay_order_number']);
            $query1->where('log_player_deposit_pay.pay_order_number',$input['pay_order_number']);
        }

        if(isset($input['status']) && trim($input['status'])!='') {
            $query->where('log_player_deposit_pay.status',$input['status']);
            $query1->where('log_player_deposit_pay.status',$input['status']);
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $playerIds = Player::where('user_name','like','%'.$input['user_name'].'%')->where('carrier_id',$carrier->id)->pluck('player_id')->toArray();
            if(!count($playerIds)){
               $query->where('log_player_deposit_pay.player_id',0);
               $query1->where('log_player_deposit_pay.player_id',0);
            } else {
                $query->whereIn('log_player_deposit_pay.player_id',$playerIds);
                $query1->whereIn('log_player_deposit_pay.player_id',$playerIds);
            }
            
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('log_player_deposit_pay.player_id',$input['player_id']);
            $query1->where('log_player_deposit_pay.player_id',$input['player_id']);
        }

        if(isset($input['extend_id']) && trim($input['extend_id']) != ''){
            $playerIds = Player::where('extend_id',$input['extend_id'])->pluck('player_id')->toArray();
            $query->whereIn('log_player_deposit_pay.player_id',$playerIds);
            $query1->whereIn('log_player_deposit_pay.player_id',$playerIds);
        }

        $totalAmount   = $query1->first();
        $total         = $query->count();
        $items         = $query->skip($offset)->take($pageSize)->get();

        $carrierUsers      = CarrierUser::where('username','<>','super_admin')->get();
        $carrierUserArr    = [];
        $carrierUserArr[0] = '系统';
        foreach ($carrierUsers as $key => $value) {
            $carrierUserArr[$value->id] = $value->username;
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($items as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
            $v->day           = $v->day==0 ? '':date('Y-m-d',strtotime($v->day));
            if($v->review_time==0){
                $v->arrival_time ='';
            } else{
                $v->arrival_time = date('Y-m-d H:i:s',$v->review_time);
            }
        }

        return ['totalamount'=>$totalAmount->amount,'arrivedamount'=>$totalAmount->arrivedamount,'item' => $items,'carrierUsers'=>$carrierUserArr, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    static function depositCollect($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        //type =1 公司银行卡入款  2=公司USDT收款 3=三方入款 4=后台充值
        //status=0  充值中   1=充值成功  2=充值失败

        $query          = self::select(\DB::raw('sum(log_player_deposit_pay.amount) as amount'),\DB::raw('count(log_player_deposit_pay.id) as count'),'log_player_deposit_pay.status')
            ->groupBy('log_player_deposit_pay.status')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_deposit_pay.player_id')
            ->where('log_player_deposit_pay.carrier_id',$carrier->id)
            ->orderBy('log_player_deposit_pay.id','desc');

        if(isset($input['type'])){
            if($input['type']==1){
                $query->where('log_player_deposit_pay.carrier_bankcard_id','>',0);
            } else if($input['type']==2){
                $query->whereIn('log_player_deposit_pay.digital_type',[1,2]);
            } else if($input['type']==3){
                $query->where('log_player_deposit_pay.carrier_bankcard_id',0)->where('log_player_deposit_pay.digital_type',0);
            }
        }

        if(isset($input['startTime']) &&  strtotime($input['startTime'])) {
            $query->where('log_player_deposit_pay.created_at','>=',$input['startTime']);
        } else {
            $query->where('log_player_deposit_pay.created_at','>=',date('Y-m-d').' 00:00:00');
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])) {
            $query->where('log_player_deposit_pay.created_at','<=',$input['endTime']);
        } else {
            $query->where('log_player_deposit_pay.created_at','<=',date('Y-m-d').' 23:59:59');
        }

        if(isset($input['pay_order_number']) && !empty(trim($input['pay_order_number']))) {
            $query->where('log_player_deposit_pay.pay_order_number',$input['pay_order_number']);
        }

        if(isset($input['status']) && trim($input['status'])!='') {
            $query->where('log_player_deposit_pay.status',$input['status']);
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $player = Player::where('user_name',$input['user_name'])->where('carrier_id',$carrier->id)->first();
            if(!$player){
               $query->where('log_player_deposit_pay.player_id',0);
            } else {
                $query->where('log_player_deposit_pay.player_id',$player->player_id);
            }
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('log_player_deposit_pay.player_id',$input['player_id']);
        }

        $items   = $query->get();
        $data    = [
            'amount' => 0,
            'count'  => 0
        ];
        foreach ($items as $key => $value) {
            if($value->status==1){
                $data['amount'] = $value->amount;
                $data['count'] = $value->count;
            } 
        }
        return $data;
    }

    static function depositAuditList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = self::select('log_player_deposit_pay.*','inf_player.user_name','inf_player.real_name')
            ->leftJoin('inf_player','inf_player.player_id','=','log_player_deposit_pay.player_id')
            ->where('log_player_deposit_pay.carrier_id',$carrier->id)
            ->whereIn('log_player_deposit_pay.status',[0,2])
            ->orderBy('log_player_deposit_pay.id','desc');

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $playerIds = Player::where('user_name','like','%'.$input['user_name'].'%')->where('carrier_id',$carrier->id)->pluck('player_id')->toArray();
            if(!count($playerIds)){
               $query->where('log_player_deposit_pay.player_id',0);
            } else {
                $query->whereIn('log_player_deposit_pay.player_id',$playerIds);
            }
            
        }

        if(isset($input['is_agent']) && in_array($input['is_agent'],[0,1])){
            $query->where('log_player_deposit_pay.is_agent',$input['is_agent']);
        }

        if(isset($input['is_hedging_account']) && in_array($input['is_hedging_account'],[0,1])){
            $query->where('log_player_deposit_pay.is_hedging_account',$input['is_hedging_account']);
        }

        if(isset($input['is_refill']) && in_array($input['is_refill'],[0,1])){
            $query->where('log_player_deposit_pay.is_refill',$input['is_refill']);
        }

        if(isset($input['player_id']) && !empty($input['player_id'])) {
            $query->where('log_player_deposit_pay.player_id',$input['player_id']);
        }

        if(isset($input['extend_id']) && trim($input['extend_id']) != ''){
            $playerIds = Player::where('extend_id',$input['extend_id'])->pluck('player_id')->toArray();
            $query->whereIn('log_player_deposit_pay.player_id',$playerIds);
        }

        if(isset($input['pay_order_number']) && !empty($input['pay_order_number'])) {
            $query->where('log_player_deposit_pay.pay_order_number',$input['pay_order_number']);
        }

        if(isset($input['startTime']) && !empty(trim($input['startTime'])) && strtotime($input['startTime'])) {
            $query->where('log_player_deposit_pay.created_at','>=',$input['startTime']);
        } 

        if(isset($input['endTime']) && !empty(trim($input['endTime'])) && strtotime($input['endTime'])) {
            $query->where('log_player_deposit_pay.created_at','<=',$input['endTime']);
        } 

        $total             = $query->count();
        $items             = $query->skip($offset)->take($pageSize)->get();

        $carrierUsers      = CarrierUser::where('username','<>','super_admin')->get();

        $carrierUserArr    = [];
        $carrierUserArr[0] = '';
        foreach ($carrierUsers as $key => $value) {
            $carrierUserArr[$value->id] = $value->username;
        }
        return ['item' => $items,'carrierUsers'=>$carrierUserArr,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function canPay()
    {
        if ($this->player->isActive() == false || $this->player->isLocked() == true) {
            return false;
        }
        return $this->status == self::ORDER_STATUS_CREATED;
    }

    public function isPayedSuccessfully()
    {
        if ($this->player->isActive() == false || $this->player->isLocked() == true) {
            return false;
        }
        return $this->status == self::ORDER_STATUS_PAY_SUCCEED || $this->status == self::ORDER_STATUS_WAITING_REVIEW;
    }

    public function scopeByPlayerId(Builder $query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeWaitingReview(Builder $query)
    {
        return $query->where('status', 2);
    }

    public function canReview()
    {
        if ($this->player->isActive() == true && $this->player->isLocked() == true && ($this->status == self::ORDER_STATUS_WAITING_REVIEW||$this->status ==self::ORDER_STATUS_CREATED)) {
            return false;
        }
        return true;
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id', 'player_id');
    }

    public function carrierPayChannel()
    {
        return $this->belongsTo(CarrierPayChannel::class, 'carrier_pay_channel', 'id');
    }

    public function reviewUser()
    {
        return $this->hasOne(CarrierUser::class, 'id', 'review_user_id');
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'carrier_id', 'id');
    }

    public function playerBankCard()
    {
        return $this->belongsTo(PlayerBankCard::class, 'player_bank_card', 'card_id');
    }

    public function relatedCarrierActivity()
    {
        return $this->hasOne(CarrierActivity::class, 'id', 'carrier_activity_id');
    }
}
