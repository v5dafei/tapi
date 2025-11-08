<?php
namespace App\Models;

use App\Utils\Arr\ArrHelper;
use App\Utils\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Auth;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\PlayerBetFlow;
use App\Models\Log\PlayerBackWater;
use App\Models\Log\PlayerLogin;
use App\Models\Log\PlayerOperate;
use App\Models\Log\PlayerFingerprint;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\PlayerDigitalAddress;
use App\Models\Conf\PlayerSetting;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Def\MainGamePlat;
use App\Models\Def\Development;
use App\Vendor\Game\gameway;
use App\Models\Map\CarrierGamePlat;
use App\Models\Map\CarrierGame;
use App\Models\PlayerInviteCode;
use App\Models\PlayerActivityAudit;
use App\Models\PlayerTransfer;
use App\Models\PlayerAccount;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Lib\Cache\GameCache;
use App\Models\Report\ReportCardPlayerEarnings;
use App\Models\Carrier;
use App\Game\Game;
use App\Models\PlayerBreakThrough;
use App\Models\Log\PlayerGiftCode;
use App\Models\PlayerGameCollect;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\CarrierPreFixDomain;
use App\Models\PlayerAlipay;
use App\Lib\Behavioralcaptcha;
use App\Lib\Clog;
use App\Lib\Cache\Lock;
use App\Models\Def\ThirdWallet;
use App\Models\Def\DigitalAddressLib;
use App\Lib\Cache\PrefixCache;

class Player extends Auth implements JWTSubject
{
    const ONLINE_ON          = 1;
    const ONLINE_OFF         = 0;
    const USER_STATUS_LOCKED = 0;
    const USER_STATUS_OK     = 1;
    const USER_STATUS_CLOSED = 2;
    const SEX_MAN            = 0;
    const SEX_WOMAN          = 1;

    public $table = 'inf_player';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'player_id';
    public $errMsg        = '';

    public static $rules = [
        'user_name' => 'required|min:4|max:11',
        'password'  => 'required|min:6|max:16|confirm'
    ];

    protected $hidden = [
        'password', 'paypassword',
    ];

    public $fillable = [
        'top_id',
        'parent_id',
        'rid',
        'type',
        'is_tester',
        'frozen_status',
        'user_name',
        'wechat',
        'level',
        'consignee',
        'sex',
        'mobile',
        'real_name',
        'password',
        'pay_password',
        'email',
        'main_account_amount',
        'login_ip',
        'carrier_id',
        'player_level_id',
        'status',
        'login_domain',
        'referral_code',
        'recommend_url',
        'qq_account',
        'birthday',
        'app_model',
        'register_ip',
        'login_at',
        'remark',
        'carrier_bankcard_id',
        'is_video_effective',
        'day',
        'style',
        'avatar',
        'chatManager',
        'bankcardname',
        'delayorder',
        'is_auto_register',
        'limitgameplat',
        'is_import',
        'gift_code',
        'register_domain',
        'prefix',
        'is_live_streaming_account',
        'othercontact',
        'extend_id',
        'parent_extend_id',
        'top_extend_id',
        'is_auto_dividend',
        'is_forum_user',
        'forum_username'
    ];

    protected $casts = [
        'player_id' => 'integer',
        'user_name' => 'string',
        'mobile' => 'string',
        'real_name' => 'string',
        'password' => 'string',
        'pay_password' => 'string',
        'email' => 'string',
        'sex' => 'integer',
        'login_ip' => 'string',
        'carrier_id' => 'integer',
        'player_level_id' => 'integer',
        'login_domain' => 'string',
        'qq_account' => 'string',
        'wechat' => 'string',
        'avatar' => 'string',
        'register_ip' => 'string',
        'remark' => 'string',
        'carrier_bankcard_id'=>'integer',
        'is_video_effective'=>'integer',
        'is_tester'=>'integer',
        'style'=>'integer',
        'chatManager'=>'integer',
        'bankcardname'=>'string',
        'delayorder'=>'integer',
        'limitgameplat'=>'string',
        'register_domain'=>'string',
        'extend_id'=>'integer',
        'is_auto_dividend'=>'integer',
    ];

    public function scopeOnline(Builder $query)
    {
        return $query->where('is_online', true);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('user_status', self::USER_STATUS_OK);
    }

    public static function lastPlayerId()
    {
        return self::max('player_id');
    }

    public function scopeIdBetween(Builder $query, $idSmaller, $idLarger)
    {
        return $query->where('player_id', '>=', $idSmaller)->where('player_id', '<', $idLarger);
    }

    public function digitalAddressList($carrierId)
    {
        $items =  PlayerDigitalAddress::select('id','address','status','type','is_default','type')->where('player_id',$this->player_id)->orderBy('id','asc')->get();

        foreach ($items as $key => &$value) {
            $digitalAddressLib = DigitalAddressLib::where('address',$value->address)->first();
            if($digitalAddressLib){
                $value->realName   = $digitalAddressLib->name;
            } else{
                $value->realName   = '';
            }
        }

        return ['data' => $items];
    }

    /**
     * 生成邀请码
     *
     * @return string
     */
    public static function generateReferralCode()
    {
        $rand   = rand(100000,999999);
        $result = self::where('referral_code', $rand)->first();

        if ($result) {
            return self::generateReferralCode();
        }
        return $rand;
    }

    static function getInfoWithAccount($uid) {
        return self::select('inf_player.*','inf_player_account.balance','inf_player_account.frozen')
            ->leftJoin('inf_player_account','inf_player_account.player_id','=','inf_player.player_id')
            ->where('inf_player.player_id',$uid)
            ->first();
    }

    static function getList($carrier,$carrierUser, $isDownLoad = false)
    {
        $input                = request()->all();
        $defaultLotteryOdds   = CarrierCache::getCarrierConfigure($carrier->id,'default_lottery_odds');

        $query = self::select('inf_player.*','inf_player_account.balance','inf_player_account.frozen','inf_player_account.agentbalance','inf_player_account.agentfrozen','conf_player_setting.guaranteed','conf_player_setting.earnings')
                ->leftJoin('inf_player_account','inf_player_account.player_id','=','inf_player.player_id')
                ->leftJoin('conf_player_setting','conf_player_setting.player_id','=','inf_player.player_id')
                ->whereIn('inf_player.is_tester',[0,2])
                ->where('inf_player.carrier_id',$carrier->id)
                ->orderBy('inf_player.player_id','desc');

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;


        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('inf_player.prefix',$input['prefix']);
        }

        if(isset($input['has_software_login']) && in_array($input['has_software_login'],[0,1])){
            $query->where('inf_player.has_software_login',$input['has_software_login']);
        }

        if(isset($input['player_id']) && !empty(trim($input['player_id']))) {
            $query->where('inf_player.player_id',$input['player_id']);
        }

        if(isset($input['extend_id']) && !empty(trim($input['extend_id']))) {
            $query->where('inf_player.extend_id',$input['extend_id']);
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $query->where('inf_player.user_name','like','%'.$input['user_name'].'%');
        }

        if(isset($input['win_lose_agent']) && in_array($input['win_lose_agent'],[0,1])) {
            $query->where('inf_player.win_lose_agent',$input['win_lose_agent']);
        }

        if(isset($input['parent_id']) && trim($input['parent_id']) != '' && is_numeric($input['parent_id'])) {
            $query->where('inf_player.parent_id',$input['parent_id']);
        }

        if(isset($input['parent_extend_id']) && trim($input['parent_extend_id']) != '' && is_numeric($input['parent_extend_id'])) {
            $query->where('inf_player.parent_extend_id',$input['parent_extend_id']);
        }

        if(isset($input['is_tester']) && trim($input['is_tester']) != '') {
            $query->where('inf_player.is_tester',$input['is_tester']);
        }

        if(isset($input['status']) && in_array($input['status'],[0,1])) {
            $query->where('inf_player.status',$input['status']);
        }

        if(isset($input['type']) && trim($input['type']) != '') {
            $query->where('inf_player.type',$input['type']);
        }

        if(isset($input['is_online']) && in_array($input['is_online'], [0,1])) {
            $query->where('inf_player.is_online',$input['is_online']);
        }

        if(isset($input['startTime']) && !empty(trim($input['startTime'])) && strtotime($input['startTime'])) {
            $query->where('inf_player.created_at','>=',$input['startTime'].' 00:00:00');
        }

        if(isset($input['endTime']) && !empty(trim($input['endTime'])) && strtotime($input['startTime'])) {
            $query->where('inf_player.created_at','<=',$input['endTime'].' 23:59:59');
        }

        if(isset($input['frozen_status']) && trim($input['frozen_status']) !='') {
            $query->where('inf_player.frozen_status',$input['frozen_status']);
        }

        if(isset($input['player_group_id']) && trim($input['player_group_id']) !='') {
            $query->where('inf_player.player_group_id',$input['player_group_id']);
        }

        if(isset($input['mobile']) && !empty($input['mobile'])){
            $query->where('inf_player.mobile',$input['mobile']);
        }

        if(isset($input['min_balance']) && trim($input['min_balance']) !='') {
            $minBalance = $input['min_balance']*10000;
            $query->where('inf_player_account.balance','>=',$minBalance);
        }

        if(isset($input['max_balance']) && trim($input['max_balance']) !='') {
            $minBalance = $input['max_balance']*10000;
            $query->where('inf_player_account.balance','<',$minBalance);
        }

        $total            = $query->count();
        if ( $isDownLoad ) {
            $data['player'] = $query->get();
        } else {
            $data['player'] = $query->skip($offset)->take($pageSize)->get();
        }

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        # 用户等级
        $levelGroup = PlayerLevel::getKvList($carrier->id);
        $levelGroup = ArrHelper::getKeyValuePair($levelGroup, 'id', 'groupname');
        $data['levelGroupList'] = $levelGroup;

        foreach ($data['player'] as $key=> &$v) {
            if(!empty($levelGroup[$v->player_group_id])){
                $v->group_name = $levelGroup[$v->player_group_id];
            } else{
                $v->group_name = PrefixCache::getDefaultPlayerLevelName($v->prefix);
            }
            
            if(!$carrierUser->is_super_admin){
                $v->mobile     = empty($v->mobile) ? '': substr($v->mobile,0,3).'****'.substr($v->mobile,-4);
            }
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
            $materialIds      = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'materialIds',$v->prefix);
            $materialIdsArr   = explode(',',$materialIds);
            if(in_array($v->player_id,$materialIdsArr)){
                $v->is_material  = 1;
            } else{
                $v->is_material  = 0;
            }
        }
        $data['player'] = $data['player']->toArray();
        //会员等级
        $playerLevels   = CarrierPlayerGrade::where('carrier_id',$carrier->id)->get();
        $playerLevelArr = array();
        foreach ( $playerLevels as  $playerLevel) {
            $playerLevelArr[$playerLevel->id] =  $playerLevel->level_name;
        }

        //所有银行卡
        $carrierBankCards   = CarrierBankCard::select('inf_carrier_bank_type.bank_name','inf_carrier_bankcard.*')
            ->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_carrier_bankcard.bank_id')
            ->where('inf_carrier_bankcard.carrier_id',$carrier->id)
            ->where('inf_carrier_bankcard.status',1)
            ->where('inf_carrier_bank_type.carrier_id',$carrier->id)
            ->get();
        $carrierBankCardArr = [];

        foreach ($carrierBankCards as $key => $value) {
            $rows                 = [];
            $rows['id']           = $value->id;
            $rows['bankinfo']     = $value->bank_username.'|'.$value->bank_name;
            $carrierBankCardArr[] =  $rows;
        }

        $data['bankCards'] = $carrierBankCardArr;
        $data['levels']    = $playerLevelArr;

        $data['defaultAgentUser'] = CarrierCache::getCarrierConfigure($carrier->id,'default_user_name');
        # 等级处理
        return ['lotteryOdds'=>$defaultLotteryOdds,'data' => $data, 'total' => $total,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function changeStatus()
    {
        $this->status = $this->status ? 0: 1;
        $this->save();
    }

    public function changeFrozenStatus()
    {
        $input        = request()->all();
        $frozenStatus = request()->get('frozenStatus','');

        if(!in_array($frozenStatus,[0,1,2,3,4])) {
            return '对不起, 参数不正确！';
        }
        if(isset($input['remark']) && !empty($input['remark'])){
            $this->remark =  $input['remark'];
        } else {
            $this->remark =  '';
        }

        $this->frozen_status =  $frozenStatus;
        $this->save();

        return true;
    }

    public function changePassword()
    {
        $input = request()->all();

        if(!isset($input['type'])||empty(trim($input['type'])) ||($input['type'] != 1 && $input['type'] != 2) ) {
            return '对不起, 参数不正确！';
        }

        if(!isset($input['password']) || empty(trim($input['password']))) {
            return '对不起, 参数不正确！';
        }

        if($input['type'] == 1) {
            $this->password = bcrypt($input['password']);
        } else {
            $this->paypassword = bcrypt($input['password']);
        }

        $this->save();

        return true;
    }

    private function match_chinese($chars,$encoding='utf8')
    {
        $pattern = ($encoding=='utf8')?'/[\x{4e00}-\x{9fa5}a-zA-Z0-9_]/u':'/[\x80-\xFF]/';
        preg_match_all($pattern,$chars,$result);
        $temp = join('',$result[0]);
        return $temp;
    }

    public function updatePlayerInfo($carrierUser)
    {
        $input     = request()->all();
        $changeStr ='';
        if(isset($input['real_name'])) {
            $this->real_name = $this->match_chinese($input['real_name']);
        }

        if(isset($input['player_level_id']) && $input['player_level_id']!=$this->player_level_id) {
            $this->player_level_id = $input['player_level_id'];
        }


        if(isset($input['enable_wind_control']) && $input['enable_wind_control']!=$this->enable_wind_control) {
            $this->enable_wind_control = $input['enable_wind_control'];
        }

        if(isset($input['remark']) && !empty($input['remark'])) {
            $this->remark = $input['remark'];
        }

        if(isset($input['bankcardname'])) {
            $this->bankcardname = $input['bankcardname'];
        }

        if(isset($input['win_lose_agent']) && in_array($input['win_lose_agent'],[0,1])) 
        {
            PlayerDigitalAddress::where('player_id',$this->player_id)->update(['win_lose_agent'=>$input['win_lose_agent']]);
            ReportPlayerStatDay::where('player_id',$this->player_id)->update(['win_lose_agent'=>$input['win_lose_agent']]);
            $this->win_lose_agent = $input['win_lose_agent'];
        }
        
        if(isset($input['inviteplayerid']) && $this->win_lose_agent==1){
            if($input['inviteplayerid']==0){
                if($this->inviteplayerid!=0){
                        $changeStr ='转介绍人由'.$this->inviteplayerid.'变更成0|';
                }
                $this->inviteplayerid = 0;
            } else{
                $existInviteplayer = Player::where('carrier_id',$this->carrier_id)->where('win_lose_agent',1)->where('player_id',$input['inviteplayerid'])->first();
                if($existInviteplayer){
                    if($this->inviteplayerid!=$input['inviteplayerid']){
                        $changeStr ='转介绍人由'.$this->inviteplayerid.'变更成'.$input['inviteplayerid'].'|';

                    }
                    $this->inviteplayerid = $input['inviteplayerid'];
                } else{
                    return '对不起, 介绍人不存在！';
                }
            }
        }

        if(isset($input['parent_id']) && $this->parent_id != $input['parent_id']) {

            if(!$carrierUser->is_super_admin){
                return '对不起, 只有超管才能变更上级！';
            }

            //开始处理
            if($this->is_tester>0) {
                return '对不起, 试玩用户与带玩用户不能变更上级！';
            }

            if($input['parent_id']!=0){
                $existPlayer = Player::where('player_id',$input['parent_id'])->where('carrier_id',$this->carrier_id)->first();
                if(!$existPlayer){
                    return '对不起, 此上级ID不存在！';
                }

                if(strstr($existPlayer->rid, $this->rid)){
                    return '对不起, 不能与下级互换！';
                }

              /*  if($this->win_lose_agent){
                    return '对不起, 负盈利代理不能更换上级';
                }
                */
            }

            $selfPlayerSetting            = PlayerCache::getPlayerSetting($this->player_id);

            if($input['parent_id']!=0){
                $newParentPlayerSetting  = PlayerSetting::where('player_id',$input['parent_id'])->first();
                if($newParentPlayerSetting->lottoadds <$selfPlayerSetting->lottoadds){
                    return '对不起, 奖金组必须小于上级等于上级奖金组！';
                }

                if($newParentPlayerSetting->earnings <$selfPlayerSetting->earnings){
                    return '对不起, 佣金比例必须小于上级等于上级佣金比例！';
                }
            }

            try {
                \DB::beginTransaction();

                $oldRid                      = $this->rid;
                $oldLevel                    = $this->level;
                //操作原来上级
                $allLowerLevelIds            = Player::where('rid','like',$this->rid.'|%')->pluck('player_id')->toArray();
                $number                      = count($allLowerLevelIds);

                if($this->parent_id != 0){
                    Player::where('player_id',$this->parent_id)->update(['soncount' =>\DB::raw('soncount - 1')]);
                    $oldParent     = Player::where('player_id',$this->parent_id)->first();
                    $oldParentIds  = explode('|',$oldParent->rid);
                    Player::whereIn('player_id',$oldParentIds)->update(['descendantscount' =>\DB::raw('descendantscount - 1 -'.$number)]);
                }

                if($input['parent_id'] != 0){
                    //更新现在的上级
                    $parent                      = Player::where('player_id',$input['parent_id'])->first();                   
                    
                    Player::where('player_id',$input['parent_id'])->update(['soncount' =>\DB::raw('soncount + 1')]);

                    $parent                      = Player::where('player_id',$input['parent_id'])->first();
                    $parentIds                   = explode('|',$parent->rid);
                    Player::whereIn('player_id',$parentIds)->update(['descendantscount' =>\DB::raw('descendantscount + 1 +'.$number)]);

                    //更新自已
                    $this->top_id    = $parent->top_id;
                    if($this->parent_id!=$parent->player_id){
                        $changeStr = $changeStr.'上级由'.$this->parent_id.'变更成'.$parent->player_id;
                    }
                    $this->parent_id        = $parent->player_id;
                    $this->parent_extend_id = $parent->extend_id;
                    $this->rid              = $parent->rid.'|'.$this->player_id;
                    $this->level            = $parent->level + 1;
                    $this->type             = 2;        
                    $this->save();
                } else {
                    //更新自已
                    $this->top_id    = $this->player_id;
                    if($this->parent_id!=0){
                        $changeStr =$changeStr.'上级由'.$this->parent_id.'变更成0';
                    }
                    $this->parent_id = 0;
                    $this->parent_extend_id = 0;
                    $this->rid       = $this->player_id;
                    $this->level     = 1;
                    $this->type      = 1;
                    $this->save();
                }
        
                $difflevel = $this->level-$oldLevel;

                //更新下级用户表
                Player::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

                //更新设置表
                PlayerSetting::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid,'level'=>$this->level]);
                PlayerSetting::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

                //更新资金表
                PlayerAccount::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid,'level'=>$this->level]);
                PlayerAccount::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

                //更新活动申请表
                PlayerActivityAudit::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid]);
                PlayerActivityAudit::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id]);

                //更新邀请码表
                PlayerInviteCode::where('player_id',$this->player_id)->update(['rid'=>$this->rid]);
                PlayerInviteCode::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')")]);

                //更新帐变信息表
                PlayerTransfer::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid,'level'=>$this->level]);
                PlayerTransfer::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

                //更新闯关记录表
                PlayerBreakThrough::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid]);
                PlayerBreakThrough::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id]);

                //更新充值记录
                PlayerDepositPayLog::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid]);
                PlayerDepositPayLog::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id]);

                //更新推广码注册记录表
                PlayerGiftCode::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid]);
                PlayerGiftCode::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id]);

                //更新提现记录
                PlayerWithdraw::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid,'level'=>$this->level]);
                PlayerWithdraw::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);
                    
                //更新邀情码表
                PlayerWithdrawFlowLimit::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid]);
                PlayerWithdrawFlowLimit::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id]);

                //更新分红表
                ReportPlayerEarnings::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid,'level'=>$this->level]);
                ReportPlayerEarnings::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

                //更新日统计表
                ReportPlayerStatDay::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid,'level'=>$this->level]);
                ReportPlayerStatDay::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

                //更新投注中间表
                PlayerBetFlowMiddle::where('player_id',$this->player_id)->update(['parent_id'=>$this->parent_id,'rid'=>$this->rid]);
                PlayerBetFlowMiddle::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')")]);

                //更新返水中间表
                PlayerBetFlowMiddle::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')")]);

                //更新玩家游戏收藏记录
                PlayerGameCollect::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid]);
                PlayerGameCollect::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id]);

                //更新福利中心记录
                PlayerReceiveGiftCenter::where('player_id',$this->player_id)->update(['top_id'=>$this->top_id,'parent_id'=>$this->parent_id,'rid'=>$this->rid]);
                PlayerReceiveGiftCenter::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace(`rid`,'".$oldRid."','".$this->rid."')"),'top_id'=>$this->top_id]);

                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollback();
                Clog::recordabnormal('移上级异常:'.$e->getMessage());  
                return '对不起，移上级异常'.$e->getMessage();
            }
        }

        if(isset($input['birthday'])) {
            $this->birthday = $input['birthday'];
        }

        if(isset($input['mobile'])) {
            //查询是否有相同手机号的
            $player = self::where('mobile',$input['mobile'])->where('carrier_id',$this->carrier_id)->first();

            if(isset($player) && $player->player_id != $this->player_id) {
                return '对不起, 此手机号已存在';
            }
            $this->mobile = $input['mobile'];
        }

        if(isset($input['email'])) {
            $player = self::where('email',$input['email'])->where('carrier_id',$this->carrier_id)->first();

            if(isset($player) && $player->email != $this->email) {
                return '对不起, 此邮箱号已存在';
            }
            $this->email = $input['email'];
        }

        if(isset($input['qq_account'])) {
            $this->qq_account = $input['qq_account'];
        }

        if(isset($input['wechat'])) {
            $this->wechat = $input['wechat'];
        }

        if ( isset($input['player_group_id']) ) {
            $this->player_group_id = (int)$input['player_group_id'];
        }

        $this->save();

        if(!empty($changeStr)){
            $changeStr = $carrierUser->username.'修改用户'.$this->user_name.':'.$changeStr;
            Clog::updateUserLog($changeStr);
        }

        return true;
    }

    public function playerTransfer()
    {
        $input          = request()->all();
        $query          = playerTransfer::orderBy('id','desc');
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(isset($input['starttime']) && !empty(trim($input['starttime']))) {
            $query->where('created_at','>=',$input['starttime']);
        }

        if(isset($input['endtime']) && !empty(trim($input['endtime']))) {
            $query->where('created_at','<',$input['endtime']);
        }

        $total               = $query->count();
        $data['transfers']   = $query->skip($offset)->take($pageSize)->get();
        $developments        = Development::all();
        $developmentArr      = array();

        foreach ($developments as $development) {
            $developmentArr[$development->sign] = $development->name;
        }

        $data['developments'] = $developmentArr;

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function setPlayerSalary($carrier)
    {
        $input         = request()->all();

        if(!isset($input['earnings']) || !is_numeric($input['earnings']) || $input['earnings'] < 0 ){
            return '对不起，佣金比例取值不正确';
        }

        if(!isset($input['guaranteed']) || !is_numeric($input['guaranteed']) || $input['guaranteed'] < 0 ){
            return '对不起，保底工资取值不正确';
        }

        $dividendEnumerate        = CarrierCache::getCarrierMultipleConfigure($carrier->id,'dividend_enumerate',$this->prefix);

        $parent               = PlayerSetting::where('player_id',$this->parent_id)->first();
        $sonMaxguaranteed     = PlayerSetting::where('parent_id',$this->player_id)->orderBy('guaranteed','desc')->first();
        $sonMaxearnings       = PlayerSetting::where('parent_id',$this->player_id)->orderBy('earnings','desc')->first();

        $defalutAgentUserName = CarrierCache::getCarrierConfigure($this->carrier_id,'default_user_name');
        $defalutAgentPlayerId = PlayerCache::getPlayerId($this->carrier_id,$defalutAgentUserName,$this->prefix);

        if($parent){
            if($parent->player_id == $defalutAgentPlayerId){
                if($parent->earnings < $input['earnings']){
                    $parent->earnings = $input['earnings'];
                    $parent->save();
                }

                PlayerCache::forgetPlayerSetting($this->parent_id);
            }

             if($parent->player_id != $defalutAgentPlayerId){
                if($parent->earnings < $input['earnings']){
                    return '对不起，佣金比例不能大于上级佣金比例';
                }

                if($parent->guaranteed < $input['guaranteed']){
                    return '保底工资不能大于上级保底工资';
                }
            }

            if($sonMaxguaranteed && $sonMaxguaranteed->guaranteed > $input['guaranteed']){
                return '保底工资不能小于下级保底工资';
            }

            if($sonMaxearnings && $sonMaxearnings->earnings > $input['earnings']){
                return '分红不能小于下级分红';
            }

        } else{
            if($sonMaxguaranteed && $sonMaxguaranteed->guaranteed > $input['guaranteed']){
                return '保底工资不能小于下级保底工资';
            }

            if($sonMaxearnings && $sonMaxearnings->earnings > $input['earnings']){
                return '分红不能小于下级分红';
            }
        }


        $casino_betflow_calculate_rate        = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'casino_betflow_calculate_rate',$this->prefix);
        $electronic_betflow_calculate_rate    = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'electronic_betflow_calculate_rate',$this->prefix);
        $esport_betflow_calculate_rate        = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'esport_betflow_calculate_rate',$this->prefix);
        $card_betflow_calculate_rate          = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'card_betflow_calculate_rate',$this->prefix);
        $sport_betflow_calculate_rate         = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'sport_betflow_calculate_rate',$this->prefix);
        $lottery_betflow_calculate_rate       = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'lottery_betflow_calculate_rate',$this->prefix);
        $fish_betflow_calculate_rate          = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'fish_betflow_calculate_rate',$this->prefix);

        //新增
        $playerBetflowCalculates = PlayerBetflowCalculate::where('player_id',$this->player_id)->delete();
        $insert                  = [];

        if($input['casino_betflow_calculate_rate'] != $casino_betflow_calculate_rate){
            $row                              = [];
            $row['player_id']                 = $this->player_id;
            $row['game_category']             = 1;
            $row['betflow_calculate_rate']    = $input['casino_betflow_calculate_rate'];
            $row['created_at']                = date('Y-m-d H:i:s');
            $row['updated_at']                = date('Y-m-d H:i:s');
            $insert[]                         = $row;
            GameCache::forgetBetflowCalculate($this->carrier_id,$this->player_id,1,$this->prefix);
        }

        if($input['electronic_betflow_calculate_rate'] != $electronic_betflow_calculate_rate){
            $row                              = [];
            $row['player_id']                 = $this->player_id;
            $row['game_category']             = 2;
            $row['betflow_calculate_rate']    = $input['electronic_betflow_calculate_rate'];
            $row['created_at']                = date('Y-m-d H:i:s');
            $row['updated_at']                = date('Y-m-d H:i:s');
            $insert[]                         = $row;
            GameCache::forgetBetflowCalculate($this->carrier_id,$this->player_id,2,$this->prefix);
        }

        if($input['esport_betflow_calculate_rate'] != $esport_betflow_calculate_rate){
            $row                              = [];
            $row['player_id']                 = $this->player_id;
            $row['game_category']             = 3;
            $row['betflow_calculate_rate']    = $input['esport_betflow_calculate_rate'];
            $row['created_at']                = date('Y-m-d H:i:s');
            $row['updated_at']                = date('Y-m-d H:i:s');
            $insert[]                         = $row;
            GameCache::forgetBetflowCalculate($this->carrier_id,$this->player_id,3,$this->prefix);
        }

        if($input['card_betflow_calculate_rate'] != $card_betflow_calculate_rate){
            $row                              = [];
            $row['player_id']                 = $this->player_id;
            $row['game_category']             = 4;
            $row['betflow_calculate_rate']    = $input['card_betflow_calculate_rate'];
            $row['created_at']                = date('Y-m-d H:i:s');
            $row['updated_at']                = date('Y-m-d H:i:s');
            $insert[]                         = $row;
            GameCache::forgetBetflowCalculate($this->carrier_id,$this->player_id,4,$this->prefix);
        }

        if($input['sport_betflow_calculate_rate'] != $sport_betflow_calculate_rate){
            $row                              = [];
            $row['player_id']                 = $this->player_id;
            $row['game_category']             = 5;
            $row['betflow_calculate_rate']    = $input['sport_betflow_calculate_rate'];
            $row['created_at']                = date('Y-m-d H:i:s');
            $row['updated_at']                = date('Y-m-d H:i:s');
            $insert[]                         = $row;
            GameCache::forgetBetflowCalculate($this->carrier_id,$this->player_id,5,$this->prefix);
        }

        if($input['lottery_betflow_calculate_rate'] != $lottery_betflow_calculate_rate){
            $row                              = [];
            $row['player_id']                 = $this->player_id;
            $row['game_category']             = 6;
            $row['betflow_calculate_rate']    = $input['lottery_betflow_calculate_rate'];
            $row['created_at']                = date('Y-m-d H:i:s');
            $row['updated_at']                = date('Y-m-d H:i:s');
            $insert[]                         = $row;
            GameCache::forgetBetflowCalculate($this->carrier_id,$this->player_id,6,$this->prefix);
        }

        if($input['fish_betflow_calculate_rate'] != $fish_betflow_calculate_rate){
            $row                              = [];
            $row['player_id']                 = $this->player_id;
            $row['game_category']             = 7;
            $row['betflow_calculate_rate']    = $input['fish_betflow_calculate_rate'];
            $row['created_at']                = date('Y-m-d H:i:s');
            $row['updated_at']                = date('Y-m-d H:i:s');
            $insert[]                         = $row;

            GameCache::forgetBetflowCalculate($this->carrier_id,$this->player_id,7,$this->prefix);
        }

        if(count($insert)){
            \DB::table('inf_player_betflow_calculate')->insert($insert);
        }

        $playerSetting                     = PlayerSetting::where('player_id',$this->player_id)->first();
        $playerSetting->earnings           = $input['earnings'];
        $playerSetting->guaranteed         = $input['guaranteed'];
        $playerSetting->save();

        if($playerSetting->earnings > 0 ){
            PlayerDigitalAddress::where('player_id',$this->player_id)->update(['win_lose_agent'=>1]);
            ReportPlayerStatDay::where('player_id',$this->player_id)->update(['win_lose_agent'=>1]);
            PlayerBetFlowMiddle::where('player_id',$this->player_id)->update(['win_lose_agent'=>1]);
            PlayerDepositPayLog::where('player_id',$this->player_id)->update(['is_agent'=>1]);
            $this->win_lose_agent = 1;
            $this->save();
        } else{
            PlayerDigitalAddress::where('player_id',$this->player_id)->update(['win_lose_agent'=>0]);
            ReportPlayerStatDay::where('player_id',$this->player_id)->update(['win_lose_agent'=>0]);
            PlayerBetFlowMiddle::where('player_id',$this->player_id)->update(['win_lose_agent'=>0]);
            PlayerDepositPayLog::where('player_id',$this->player_id)->update(['is_agent'=>0]);
            $this->win_lose_agent = 0;
            $this->save();
        }

        PlayerCache::forgetisWinLoseAgent($this->player_id);
        PlayerCache::forgetPlayerSetting($this->player_id);
        PlayerCache::forgetPlayerBetflowCalculate($this->carrier_id,$this->player_id,$this->prefix);

        return true;
    }

    public function addreduce($admin_Id, $params = [])
    {
        if(!empty($params)) {
            $input['type']      = $params['type'];
            $input['amount']    = $params['amount'];
            $input['flowlimit'] = $params['flowlimit'];
            $input['player_id'] = $params['player_id'];
            $input['admin_Id']  = $admin_Id;
        } else {
            $input['type']      = request()->get('type');
            $input['amount']    = request()->get('amount');
            $input['flowlimit'] = request()->get('flowlimit');
            $input['remark']    = request()->get('remark','');
            $input['player_id'] = $this->player_id;
            $input['admin_Id']  = $admin_Id;
        }

        $lists              = config('main')['addReduceList'];
        
        $typeArr            = array();

        foreach ($lists as $value) {
            foreach ($value as $key => $v) {
                $typeArr[] = $key;
            }
        }

        if(!is_numeric($input['amount'])|| $input['amount']<0) {
            return '对不起, 金额不正确';
        }

        if(is_null($input['remark']) || empty($input['remark'])) {
            return '对不起, 备注必须填写';
        }

        $player          = $this;
        $playerAccount   = PlayerAccount::where('player_id',$this->player_id)->first();
        $input['amount'] = $input['amount']*10000;

        if(!is_null($input['flowlimit']) && is_numeric($input['flowlimit']) && $input['flowlimit']>0 && intval($input['flowlimit']) == $input['flowlimit']){
            $input['flowlimit'] = $input['flowlimit']*10000;
            return self::changeBalance($input,$player,$playerAccount,$input['flowlimit']);
        } else {
            return self::changeBalance($input,$player,$playerAccount);
        }
    }

    static  function changeBalance($input , $player, $playerAccount,$flowlimit=0)
    {
        $typenames = [];

        foreach (config('main')['addReduceList']['add'] as $key => $value) {
            $typenames[$key] = $value;
        }

        foreach (config('main')['addReduceList']['reduce'] as $key => $value) {
            $typenames[$key] = $value;
        }
        
        try {
            \DB::beginTransaction();
            $playerAccount = PlayerAccount::where('player_id',$playerAccount->player_id)->lockForUpdate()->first();
            switch ($input['type']) {
                //真人礼金
                case 'casino_gift':
                //电子礼金
                case 'electronic_gift':
                //电竞礼金
                case 'esport_gift':
                //棋牌礼金
                case 'card_gift':
                //体育礼金
                case 'sport_gift':
                //彩票礼金
                case 'lottery_gift':
                //捕鱼礼金
                case 'fish_gift':
                //手动礼金
                case 'gift_transfer_add':
                //报销礼金
                case 'reimbursement_gift':
                //代理扶持
                case 'agent_support':
                    //生日彩金
                    $playerTransfer                         = new PlayerTransfer();
                    $playerTransfer->prefix                 = $player->prefix;
                    $playerTransfer->carrier_id             = $player->carrier_id;
                    $playerTransfer->rid                    = $player->rid;
                    $playerTransfer->top_id                 = $player->top_id;
                    $playerTransfer->parent_id              = $player->parent_id;
                    $playerTransfer->player_id              = $player->player_id;
                    $playerTransfer->is_tester              = $player->is_tester;
                    $playerTransfer->user_name              = $player->user_name;
                    $playerTransfer->level                  = $player->level;
                    $playerTransfer->mode                   = 1;
                    $playerTransfer->type                   = $input['type'];
                    $playerTransfer->type_name              = $typenames[$input['type']];
                    $playerTransfer->day_m                  = date('Ym');
                    $playerTransfer->day                    = date('Ymd');
                    $playerTransfer->amount                 = $input['amount'];
                    $playerTransfer->admin_id               = $input['admin_Id'];

                    $playerTransfer->before_balance         = $playerAccount->balance;
                    $playerTransfer->balance                = $playerAccount->balance + $input['amount'];
                    $playerTransfer->before_frozen_balance  = $playerAccount->frozen;
                    $playerTransfer->frozen_balance         = $playerAccount->frozen;

                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                    if(isset($input['remark']) && !empty($input['remark'])){
                        $playerTransfer->remark         = $input['remark'];
                    }
                        
                    $playerTransfer->save();

                    $playerAccount->balance = $playerTransfer->balance;
                    $playerAccount->save();
                    break;
                //保险箱新增
                case 'agent_reimbursement':
                    $enableSafeBox = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'enable_safe_box',$player->prefix);
                    if($enableSafeBox==1){
                        //生日彩金
                        $playerTransfer                         = new PlayerTransfer();
                        $playerTransfer->prefix                 = $player->prefix;
                        $playerTransfer->carrier_id             = $player->carrier_id;
                        $playerTransfer->rid                    = $player->rid;
                        $playerTransfer->top_id                 = $player->top_id;
                        $playerTransfer->parent_id              = $player->parent_id;
                        $playerTransfer->player_id              = $player->player_id;
                        $playerTransfer->is_tester              = $player->is_tester;
                        $playerTransfer->user_name              = $player->user_name;
                        $playerTransfer->level                  = $player->level;
                        $playerTransfer->mode                   = 1;
                        $playerTransfer->type                   = $input['type'];
                        $playerTransfer->type_name              = $typenames[$input['type']];
                        $playerTransfer->day_m                  = date('Ym');
                        $playerTransfer->day                    = date('Ymd');
                        $playerTransfer->amount                 = $input['amount'];
                        $playerTransfer->admin_id               = $input['admin_Id'];

                        $playerTransfer->before_balance         = $playerAccount->balance;
                        $playerTransfer->balance                = $playerAccount->balance;
                        $playerTransfer->before_frozen_balance  = $playerAccount->frozen;
                        $playerTransfer->frozen_balance         = $playerAccount->frozen;

                        $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                = $playerAccount->agentbalance + $input['amount'];
                        $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                        if(isset($input['remark']) && !empty($input['remark'])){
                            $playerTransfer->remark         = $input['remark'];
                        }
                            
                        $playerTransfer->save();

                        $playerAccount->agentbalance = $playerTransfer->agent_balance;
                        $playerAccount->save();
                    } else{
                        //生日彩金
                        $playerTransfer                         = new PlayerTransfer();
                        $playerTransfer->prefix                 = $player->prefix;
                        $playerTransfer->carrier_id             = $player->carrier_id;
                        $playerTransfer->rid                    = $player->rid;
                        $playerTransfer->top_id                 = $player->top_id;
                        $playerTransfer->parent_id              = $player->parent_id;
                        $playerTransfer->player_id              = $player->player_id;
                        $playerTransfer->is_tester              = $player->is_tester;
                        $playerTransfer->user_name              = $player->user_name;
                        $playerTransfer->level                  = $player->level;
                        $playerTransfer->mode                   = 1;
                        $playerTransfer->type                   = $input['type'];
                        $playerTransfer->type_name              = $typenames[$input['type']];
                        $playerTransfer->day_m                  = date('Ym');
                        $playerTransfer->day                    = date('Ymd');
                        $playerTransfer->amount                 = $input['amount'];
                        $playerTransfer->admin_id               = $input['admin_Id'];

                        $playerTransfer->before_balance         = $playerAccount->balance;
                        $playerTransfer->balance                = $playerAccount->balance+ $input['amount'];
                        $playerTransfer->before_frozen_balance  = $playerAccount->frozen;
                        $playerTransfer->frozen_balance         = $playerAccount->frozen;

                        $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                        $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                        $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                        $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                        if(isset($input['remark']) && !empty($input['remark'])){
                            $playerTransfer->remark         = $input['remark'];
                        }
                            
                        $playerTransfer->save();

                        $playerAccount->balance = $playerTransfer->balance;
                        $playerAccount->save();
                    }
                    break;
                case 'safe_transfer_add':
                    //生日彩金
                    $playerTransfer                         = new PlayerTransfer();
                    $playerTransfer->prefix                 = $player->prefix;
                    $playerTransfer->carrier_id             = $player->carrier_id;
                    $playerTransfer->rid                    = $player->rid;
                    $playerTransfer->top_id                 = $player->top_id;
                    $playerTransfer->parent_id              = $player->parent_id;
                    $playerTransfer->player_id              = $player->player_id;
                    $playerTransfer->is_tester              = $player->is_tester;
                    $playerTransfer->user_name              = $player->user_name;
                    $playerTransfer->level                  = $player->level;
                    $playerTransfer->mode                   = 1;
                    $playerTransfer->type                   = $input['type'];
                    $playerTransfer->type_name              = $typenames[$input['type']];
                    $playerTransfer->day_m                  = date('Ym');
                    $playerTransfer->day                    = date('Ymd');
                    $playerTransfer->amount                 = $input['amount'];
                    $playerTransfer->admin_id               = $input['admin_Id'];

                    $playerTransfer->before_balance         = $playerAccount->balance;
                    $playerTransfer->balance                = $playerAccount->balance;
                    $playerTransfer->before_frozen_balance  = $playerAccount->frozen;
                    $playerTransfer->frozen_balance         = $playerAccount->frozen;

                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance + $input['amount'];
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                    if(isset($input['remark']) && !empty($input['remark'])){
                        $playerTransfer->remark         = $input['remark'];
                    }
                        
                    $playerTransfer->save();

                    $playerAccount->agentbalance = $playerTransfer->agent_balance;
                    $playerAccount->save();
                    break;
                case 'gift_transfer_reduce':
                    //活动扣减
                    if($input['amount']>$playerAccount->balance) {
                        //把钱从保险签转入钱包
                        return '对不起，帐户资金不足！';
                    }
                    $playerTransfer                         = new PlayerTransfer();
                    $playerTransfer->prefix                 = $player->prefix;
                    $playerTransfer->carrier_id             = $player->carrier_id;
                    $playerTransfer->rid                    = $player->rid;
                    $playerTransfer->top_id                 = $player->top_id;
                    $playerTransfer->parent_id              = $player->parent_id;
                    $playerTransfer->player_id              = $player->player_id;
                    $playerTransfer->is_tester              = $player->is_tester;
                    $playerTransfer->user_name              = $player->user_name;
                    $playerTransfer->level                  = $player->level;
                    $playerTransfer->mode                   = 2;
                    $playerTransfer->type                   = $input['type'];
                    $playerTransfer->type_name              = $typenames[$input['type']];
                    $playerTransfer->day_m                  = date('Ym');
                    $playerTransfer->day                    = date('Ymd');
                    $playerTransfer->amount                 = $input['amount'];
                    $playerTransfer->admin_id               = $input['admin_Id'];
                    $playerTransfer->before_balance         = $playerAccount->balance;
                    $playerTransfer->balance                = $playerAccount->balance - $input['amount'];
                    $playerTransfer->before_frozen_balance  = $playerAccount->frozen;
                    $playerTransfer->frozen_balance         = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                    if(isset($input['remark']) && !empty($input['remark'])){
                        $playerTransfer->remark         = $input['remark'];
                    }
                    $playerTransfer->save();

                    $playerAccount->balance = $playerTransfer->balance;
                    $playerAccount->save();
                    break;
                case 'safe_transfer_reduce':
                    if($input['amount']>$playerAccount->agentbalance) {
                        return '对不起，保险箱资金不足！';
                    }

                    $playerTransfer                         = new PlayerTransfer();
                    $playerTransfer->prefix                 = $player->prefix;
                    $playerTransfer->carrier_id             = $player->carrier_id;
                    $playerTransfer->rid                    = $player->rid;
                    $playerTransfer->top_id                 = $player->top_id;
                    $playerTransfer->parent_id              = $player->parent_id;
                    $playerTransfer->player_id              = $player->player_id;
                    $playerTransfer->is_tester              = $player->is_tester;
                    $playerTransfer->user_name              = $player->user_name;
                    $playerTransfer->level                  = $player->level;
                    $playerTransfer->mode                   = 2;
                    $playerTransfer->type                   = 'transfer_in_wallet';
                    $playerTransfer->type_name              = '保险箱转入钱包';
                    $playerTransfer->day_m                  = date('Ym');
                    $playerTransfer->day                    = date('Ymd');
                    $playerTransfer->amount                 = $input['amount'];
                    $playerTransfer->admin_id               = $input['admin_Id'];
                    $playerTransfer->before_balance         = $playerAccount->balance;
                    $playerTransfer->balance                = $playerAccount->balance + $input['amount'];
                    $playerTransfer->before_frozen_balance  = $playerAccount->frozen;
                    $playerTransfer->frozen_balance         = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance - $input['amount'];
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                    if(isset($input['remark']) && !empty($input['remark'])){
                        $playerTransfer->remark         = $input['remark'];
                    }
                    $playerTransfer->save();

                    $playerTransfer1                         = new PlayerTransfer();
                    $playerTransfer1->prefix                 = $player->prefix;
                    $playerTransfer1->carrier_id             = $player->carrier_id;
                    $playerTransfer1->rid                    = $player->rid;
                    $playerTransfer1->top_id                 = $player->top_id;
                    $playerTransfer1->parent_id              = $player->parent_id;
                    $playerTransfer1->player_id              = $player->player_id;
                    $playerTransfer1->is_tester              = $player->is_tester;
                    $playerTransfer1->user_name              = $player->user_name;
                    $playerTransfer1->level                  = $player->level;
                    $playerTransfer1->mode                   = 2;
                    $playerTransfer1->type                   = 'gift_transfer_reduce';
                    $playerTransfer1->type_name              = '活动扣减';
                    $playerTransfer1->day_m                  = date('Ym');
                    $playerTransfer1->day                    = date('Ymd');
                    $playerTransfer1->amount                 = $input['amount'];
                    $playerTransfer1->admin_id               = $input['admin_Id'];
                    $playerTransfer1->before_balance         = $playerTransfer->balance;
                    $playerTransfer1->balance                = $playerTransfer->balance - $input['amount'];
                    $playerTransfer1->before_frozen_balance  = $playerAccount->frozen;
                    $playerTransfer1->frozen_balance         = $playerAccount->frozen;
                    $playerTransfer1->before_agent_balance         = $playerTransfer->agent_balance;
                    $playerTransfer1->agent_balance                = $playerTransfer->agent_balance;
                    $playerTransfer1->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer1->agent_frozen_balance         = $playerAccount->agentfrozen;

                    if(isset($input['remark']) && !empty($input['remark'])){
                        $playerTransfer1->remark         = $input['remark'];
                    }
                    $playerTransfer1->save();


                    $playerAccount->agentbalance = $playerTransfer->agent_balance;
                    $playerAccount->save();
                    break;
                default:
                    break;
            }

            $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'player_dividends_method',$player->prefix);
            if($player->win_lose_agent && $playerDividendsMethod ==2){
                $flowlimit = 0;
            }

            if($flowlimit>0) {
                switch ($input['type']) {
                    case 'gift_transfer_add':
                    case 'casino_gift':
                    case 'electronic_gift':
                    case 'esport_gift':
                    case 'card_gift':
                    case 'sport_gift':
                    case 'lottery_gift':
                    case 'fish_gift':
                        $limit_type = 5;  //活动理赔
                        break;
                    case 'agent_support':
                        $limit_type = 52;  //代理扶持
                        break;
                    default:
                            # code...
                        break;
                }

                $playerWithdrawFlowLimit               = new PlayerWithdrawFlowLimit();
                $playerWithdrawFlowLimit->carrier_id   = $player->carrier_id;
                $playerWithdrawFlowLimit->top_id       = $player->top_id;
                $playerWithdrawFlowLimit->parent_id    = $player->parent_id;
                $playerWithdrawFlowLimit->rid          = $player->rid;
                $playerWithdrawFlowLimit->player_id    = $player->player_id;
                $playerWithdrawFlowLimit->user_name    = $player->user_name;
                $playerWithdrawFlowLimit->limit_amount = $flowlimit;
                $playerWithdrawFlowLimit->limit_type   = $limit_type;
                $playerWithdrawFlowLimit->operator_id  = $input['admin_Id'];
                $playerWithdrawFlowLimit->save();
            }

            \DB::commit();
                
            return true;
        } catch (\Exception $e) {
            \DB::rollback();   
            Clog::recordabnormal('后台帐变异常'.$e->getMessage()); 
            return '操作异常changeBalance：'.$e->getMessage();
        }     
    }

    public function playerFinanceinfo()
    {
        $intput =request()->all();

        if(isset($intput['startTime']) && strtotime($intput['startTime'])){
            $intput['startTime'] = $intput['startTime'].' 00:00:00';
        } else {
            $intput['startTime'] = date('Y-m-01 00:00:00', strtotime(date("Y-m-d")));
        }

        if(isset($intput['endTime']) && strtotime($intput['endTime'])){
            $intput['endTime']  = $intput['endTime'].' 23:59:59';
        } else {
            $intput['endTime']  = date('Y-m-d H:i:s', time());
        }


        //查询存款  包括 recharge
        $rechargeTransferAdd = PlayerTransfer::select(\DB::raw('sum(amount) as amount'),\DB::raw('count(id) as counts'))
            ->where('player_id',$this->player_id)
            ->where('type','recharge')
            ->whereBetween('created_at',[$intput['startTime'],$intput['endTime']])
            ->first();

        $data['rechargeCount']       = is_null($rechargeTransferAdd->amount) ? 0 : $rechargeTransferAdd->counts  ;
        $data['rechargeAmount']      = !is_null($rechargeTransferAdd->amount) ? bcdiv($rechargeTransferAdd->amount,10000,2) : 0 ;


        //查询取款 包括 withdraw_finish
        $withdrawAmounReduce         = PlayerTransfer::select(\DB::raw('sum(amount) as amount'),\DB::raw('count(id) as counts'))
            ->where('player_id',$this->player_id)
            ->where('type','withdraw_finish')
            ->whereBetween('created_at',[$intput['startTime'],$intput['endTime']])
            ->first();

        $data['withdrawCount']       = is_null($withdrawAmounReduce->amount) ? 0 : $withdrawAmounReduce->counts ;
        $data['withdrawAmount']      = is_null($withdrawAmounReduce->amount) ? 0 : bcdiv($withdrawAmounReduce->amount,10000,2);
        
        $giftAdd    = 0;
        $giftReduce = 0;


        //活动礼金 包括 gift gift_transfer_add luck_draw_prize 减去 gift_transfer_reduce
        $giftAdd = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))
            ->where('player_id',$this->player_id)
            ->whereIn('type',config('main')['giftadd'])
            ->whereBetween('created_at',[$intput['startTime'],$intput['endTime']])
            ->first();

        $giftReduce = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))
            ->where('player_id',$this->player_id)
            ->whereIn('type',['gift_transfer_reduce','inside_transfer_to'])
            ->where('day','>=',date('Ymd',strtotime($intput['startTime'])))
            ->where('day','<=',date('Ymd',strtotime($intput['endTime'])))
            ->first();

        $giftAdd      = is_null($giftAdd->amount) ? 0: $giftAdd->amount;
        $giftReduce   = is_null($giftReduce->amount) ? 0: $giftReduce->amount;
        $data['gift'] = bcdiv($giftAdd - $giftReduce,10000,2);

        //返佣
        $commission = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))
            ->where('player_id',$this->player_id)
            ->where('type','commission_from_child')
            ->whereBetween('created_at',[$intput['startTime'],$intput['endTime']])
            ->first();

        $data['commission'] = is_null($commission->amount) ? '0.00' :  bcdiv($commission->amount,10000,2);

        //返水
        $returnwater = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))
            ->where('player_id',$this->player_id)
            ->where('type','commission_from_self')
            ->whereBetween('created_at',[$intput['startTime'],$intput['endTime']])
            ->first();

        $data['returnwater'] = is_null($returnwater->amount) ? '0.00' :  bcdiv($returnwater->amount,10000,2);

        $dividend    = '0.00';
        $dividendadd = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))
            ->where('player_id',$this->player_id)
            ->whereIn('type',['dividend_from_parent','agent_reimbursement'])
            ->whereBetween('created_at',[$intput['startTime'],$intput['endTime']])
            ->first();

        if(!is_null($dividendadd->amount)){
            $dividend    = bcdiv($dividendadd->amount,10000,2);
        }

        $data['dividend'] = $dividend;

        //投注相关
        $playerBetFlow = PlayerBetFlowMiddle::select(\DB::raw('sum(company_win_amount) as company_win_amount'))
            ->where('player_id',$this->player_id)
            ->whereBetween('bet_time',[strtotime($intput['startTime']),strtotime($intput['endTime'])])
            ->first();

        $data['companyWinAmount']      = is_null($playerBetFlow->company_win_amount) ? '0.00' :  $playerBetFlow->company_win_amount;

        //代充下分
        $data['putSubstitute']            = '0.00';
        $data['putAgentGift']             = '0.00';
        $data['commonTransferReduce']     = '0.00';

        return $data;
    }

    //链接开户
    static function register($carrier,$prefix)
    {
        $input                                = request()->all();
        $carrierRegisterTelehone              = CarrierCache::getCarrierMultipleConfigure($carrier->id, 'carrier_register_telehone',$prefix);
        $enableRegisterBehavioralVerification = CarrierCache::getCarrierMultipleConfigure($carrier->id, 'enable_register_behavior_verification',$prefix);   //启用行为验证码
        $registerVerification                 = CarrierCache::getCarrierMultipleConfigure($carrier->id, 'carrier_register_telehone',$prefix);               //启用手机验证码
        $registerImgVerification              = CarrierCache::getCarrierMultipleConfigure($carrier->id, 'enable_register_img_verification',$prefix);        //启用图形验证码

        //启用了图形验证码
        if($registerImgVerification==1){
            if(!isset($input['captcha']) && empty($input['captcha'])){
                return '对不起, 验证码不能为空！';
            }

            $ip              = real_ip();
            $captchaKey      = cache()->get(md5($ip));
               
            if(strtolower($input["captcha"]) != strtolower($captchaKey)){
                return '对不起, 验证码不正确！';
            }
        }

        $language                             = CarrierCache::getLanguageByPrefix($prefix);

        if($enableRegisterBehavioralVerification){

            if(!isset($input['dataInfo'])){
                return '对不起,行为验证码参数不正确';
            }

            $behavioral = Behavioralcaptcha::captcha($input);
            $bizCode    = null;

            if(isset($behavioral->Code)){
                $bizCode = $behavioral->Code;
            }

            if(isset($behavioral->BizCode)){
                $bizCode = $behavioral->BizCode;
            }

            if(!isset($bizCode) || $bizCode=='800' || $bizCode=='900'){
                return '对不起,验证不通过';
            } else if($bizCode=='400'){
                return 400;
            }
        }

        if(isset($input['is_auto_register']) && $input['is_auto_register'] == 1){
            $input['mobile'] = 0;
        } else if($carrierRegisterTelehone ){
            if(!isset($input['mobile']) || empty(trim($input['mobile']))) {
                return '对不起,手机号不能为空！';
            }

            //手机号解密
            if(!is_numeric($input['mobile'])){
                $code                     = md5('mobile');
                $iv                       = substr($code,0,16);
                $key                      = substr($code,16);
                $input['mobile']          =  openssl_decrypt(base64_decode($input['mobile']), 'AES-128-CBC', $key,1,$iv);
            }

            $existMobile   = self::where('carrier_id',$carrier->id)->where(function($query) use($input){
                $query->where('mobile',$input['mobile'])->orWhere('user_name',$input['mobile']);
            })->first();

            if($existMobile){
                return '对不起,此手机号已存在！';
            }
        }

        if(!isset($input['user_name'])){
            //生成帐号
            do{
                $userName          = randDomainCode().randDomainCode();
                 //$existuserName         = Player::where('user_name',$userName)->where('carrier_id',$carrier->id)->first();

                $existuserName         = Player::where('carrier_id',$carrier->id)->where(function($query) use($userName){
                    $query->where('mobile',$userName)->orWhere('user_name',$userName);
                })->first();

            } while ($existuserName);
            $input['user_name'] = $userName;
        } else{
            if ( !Validator::isUsr($input['user_name'], [ 'min' => 5, 'max' => 36, 'checkUpper' => false ]) ) {
                return '对不起,帐号只能包括字母,数字,或下划线，且不以下划线开头长度为4到36个小写字符！';
            }

            //$existuserName = self::where('carrier_id',$carrier->id)->where('user_name',$input['user_name'])->first();
            $addLock = Lock::addLock('register_'.$input['user_name'].'_'.$prefix,3);
            if(!$addLock){
                return '对不起,请勿频繁注册！';
            }

            $existuserName         = Player::where('carrier_id',$carrier->id)->where(function($query) use($input,$prefix){
                    $query->where('mobile',$input['user_name'])->orWhere('user_name',$input['user_name'].'_'.$prefix);
                })->first();

            if($existuserName){
                return '对不起,此用户已存在！';
            }
        }

        if(!isset($input['password']) || empty(trim($input['password']))) {
            return '对不起,密码不能为空！';
        }

        $carrierRegisterRealName = CarrierCache::getCarrierMultipleConfigure($carrier->id,'register_real_name',$prefix);
        if(isset($input['is_auto_register']) && $input['is_auto_register'] == 1){
            $input['real_name'] = '';
        } else if($carrierRegisterRealName){
            if(!isset($input['real_name']) || empty(trim($input['real_name']))) {
                return '对不起,真实姓名不能为空！';
            }

            $currency = CarrierCache::getCurrencyByPrefix($prefix);
            if($currency =='CNY'){
                $exp = '/^[\x{4e00}-\x{9fa5}]+[·?]?[\x{4e00}-\x{9fa5}]+$/u';
                if(!preg_match( $exp, $input['real_name'] ) ){
                    return '对不起,真实姓名取值不正确';
                }
            }
        }

        $recommendPlayerId = false;
        $ip                = real_ip();
        $intIp             = ip2long($ip);

        $defaultAgent             = Player::where('user_name',CarrierCache::getCarrierConfigure($carrier->id,'default_user_name'))->where('prefix',$prefix)->where('carrier_id',$carrier->id)->first();
        $defaultPlayerInviteCode  = PlayerInviteCode::where('username',CarrierCache::getCarrierConfigure($carrier->id,'default_user_name'))->where('prefix',$prefix)->where('carrier_id',$carrier->id)->first();
        $isAllowGeneralAgent      = CarrierCache::getCarrierMultipleConfigure($carrier->id,'is_allow_general_agent',$prefix);
        $playerDividendsMethod    = CarrierCache::getCarrierMultipleConfigure($carrier->id,'player_dividends_method',$prefix);

        if($defaultAgent->player_id == $input['mobile_code'] || $defaultAgent->extend_id == $input['mobile_code']){
            $input['mobile_code'] = 'www';
        }
       
        if(array_key_exists('mobile_code', $input) && !empty($input['mobile_code'])){
            if(!$isAllowGeneralAgent && $input['mobile_code']=='www'){
                return '对不起,系统不允许注册总代！';
            }
            if($input['mobile_code']=='www'){
                $playerInviteCode = PlayerInviteCode::where('code',$input['mobile_code'])->where('prefix',$prefix)->where('carrier_id',$carrier->id)->where('status',1)->first();
            } else{
                if(strlen($input['mobile_code'])==8){
                    $playerInviteCode = PlayerInviteCode::where('player_id',$input['mobile_code'])->where('carrier_id',$carrier->id)->where('prefix',$prefix)->where('status',1)->first();
                }else{
                    $playerInviteCode = PlayerInviteCode::where('code',$input['mobile_code'])->where('prefix',$prefix)->where('carrier_id',$carrier->id)->where('status',1)->first();
                }
            }
            
            if(!$playerInviteCode) {
                return '对不起,邀请码不正确！';
            } else{
                $invitePlayer          = Player::where('carrier_id',$carrier->id)->where('player_id',$playerInviteCode->player_id)->first();
                if($playerDividendsMethod == 2 && $input['mobile_code']!='www'){
                    //结算方式2
                    if($invitePlayer->win_lose_agent==0){
                        $invitePlayer  = Player::where('carrier_id',$carrier->id)->where('player_id',$invitePlayer->parent_id)->first();
                    }
                }
                $parent                = $invitePlayer;
            }
        } else if(array_key_exists('agentDomain', $input) && !is_null($input['agentDomain'])) {
            $url          = str_replace("https://", "", trim($input['agentDomain']));
            $url          = str_replace("http://", "", trim($input['agentDomain']));

            $urlArr       = explode('.',$url);

            if(count($urlArr)!=3) {
                return '对不起,代理域名不正确！';
            }

            if($urlArr[0] == 'www') {
                if(!$isAllowGeneralAgent){
                    return '对不起,系统不允许注册总代！';
                }
                $playerInviteCode = $defaultPlayerInviteCode;
            } else {
                if(strlen($urlArr[0])==8){
                    $playerInviteCode = PlayerInviteCode::where('player_id',$urlArr[0])->where('carrier_id',$carrier->id)->where('prefix',$prefix)->first();
                }else{
                    $playerInviteCode = PlayerInviteCode::where('code',$urlArr[0])->where('carrier_id',$carrier->id)->where('prefix',$prefix)->first();
                }

                if(!$playerInviteCode || $playerInviteCode->status==0) {

                    return '对不起,您的注册域名不正确！';
                    //不存在或失效的邀请链接挂到系统下面
                    //$playerInviteCode = $defaultPlayerInviteCode;
                }
            }
            $input['mobile_code'] = $playerInviteCode->code;
            $invitePlayer         = Player::where('carrier_id',$carrier->id)->where('player_id',$playerInviteCode->player_id)->first();

            if($playerDividendsMethod == 2 && $input['mobile_code']!='www'){
                //结算方式2
                if($invitePlayer->win_lose_agent==0){
                    $invitePlayer  = Player::where('carrier_id',$carrier->id)->where('player_id',$invitePlayer->parent_id)->first();
                }
            }
            $parent               =  $invitePlayer;
        } else{
            return '对不起,推荐人ID不能为空！';
        }


        //限制当前IP注册数量
        $registerNum = Player::where('carrier_id',$carrier->id)->where('prefix',$prefix)->where('is_tester',0)->where('register_ip',real_ip())->count();

        if($registerNum >= CarrierCache::getCarrierMultipleConfigure($carrier->id,'player_max_register_one_ip_minute',$prefix)) {
            return config('language')[$language]['error108'];
        }

        $carrierPlayerLevel          = CarrierPlayerGrade::where('carrier_id',$carrier->id)->where('is_default',1)->where('prefix',$prefix)->first();

        $enableFixedEarnings    = CarrierCache::getCarrierMultipleConfigure($parent->carrier_id,'enable_fixed_earnings',$prefix);
        $enableFixedGuaranteed  = CarrierCache::getCarrierMultipleConfigure($parent->carrier_id,'enable_fixed_guaranteed',$prefix);
        $defaultEarnings        = CarrierCache::getCarrierMultipleConfigure($parent->carrier_id,'default_earnings',$prefix);
        $defaultGuaranteed      = CarrierCache::getCarrierMultipleConfigure($parent->carrier_id,'default_guaranteed',$prefix);

         try {
            \DB::beginTransaction();

            $domain                = request()->header('Origin');
            $domain                = str_replace("https://", "", trim($domain));
            $domain                = str_replace("http://", "", trim($domain));

            $player                            = new Player();
            $player->top_id                    = $parent->top_id?$parent->top_id:$parent->id;
            $player->parent_id                 = $parent->id;
            $player->top_extend_id            = $parent->top_extend_id?$parent->top_extend_id:$parent->extend_id;
            $player->parent_extend_id         = $parent->extend_id;
            $player->register_domain           = $domain;
            $player->prefix                    = $prefix;
            $player->day                       = date('Ymd');


            if(isset($input['is_auto_register']) && $input['is_auto_register']==1){
                $player->is_auto_register  = 1;
            }

            $player->is_tester       = $parent->is_tester;
            if($carrierRegisterTelehone && (!isset($input['is_auto_register']) || !$input['is_auto_register'])){
                $player->mobile = $input['mobile'];
            } else {
                $player->mobile = '';
            }
            $player->user_name       = $input['user_name'].'_'.$prefix;
            $player->real_name       = isset($input['real_name'])?$input['real_name']:'';
            $player->password        = bcrypt($input['password']);
            $player->paypassword     = null;
            $player->carrier_id      = $parent->carrier_id;
            $player->player_level_id = $carrierPlayerLevel->id;
            $player->register_ip     = real_ip();
            $player->level           = $parent->level+1;

            if(isset($input['gift_code']) && !empty($input['gift_code'])){
                $codeGiftListPlayerIds = PlayerTransfer::where('type','code_gift')->pluck('player_id')->toArray();
                $loginips              = PlayerLogin::where('player_id',$codeGiftListPlayerIds)->pluck('login_ip')->toArray();
                $loginips              = array_unique($loginips);
                $ip                    = real_ip();

                if(!in_array($ip,$loginips)){
                    $player->gift_code           = $input['gift_code'];
                }
            }

            $player->type            = 2;
            $player->nick_name       = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'default_nick_name',$prefix);
            if($enableFixedEarnings==1 && $defaultEarnings > 0){
                $player->win_lose_agent = 1;
            }
            $player->save();

            if(is_null($player->rid)){
                if($player->parent_id){
                    $parent          = Player::where('player_id',$player->parent_id)->first();
                    $player->rid     = $parent->rid.'|'.$player->player_id;
                } else {
                    $player->rid     = $player->player_id;
                }
                $player->save();
            }

            $playerSetting                              = new PlayerSetting();
            $playerSetting->player_id                   = $player->player_id;
            $playerSetting->carrier_id                  = $player->carrier_id;
            $playerSetting->top_id                      = $player->top_id;
            $playerSetting->parent_id                   = $player->parent_id;
            $playerSetting->rid                         = $player->rid;
            $playerSetting->prefix                      = $prefix;
            $playerSetting->is_tester                   = $player->is_tester;
            $playerSetting->user_name                   = $player->user_name;
            $playerSetting->level                       = $player->level;
            $playerSetting->lottoadds                   = CarrierCache::getCarrierConfigure($carrier->id, 'default_lottery_odds');
            if($enableFixedEarnings==1){
                $playerSetting->earnings                  = $defaultEarnings;
            }

            if($enableFixedGuaranteed==1){
                $playerSetting->guaranteed                = $defaultGuaranteed;
            }
            
            $playerSetting->save();

            $selfInviteCode                              = new PlayerInviteCode();
            $selfInviteCode->carrier_id                  = $player->carrier_id;
            $selfInviteCode->player_id                   = $player->player_id;
            $selfInviteCode->rid                         = $player->rid;
            $selfInviteCode->username                    = $player->user_name;
            $selfInviteCode->type                        = 2;
            $selfInviteCode->lottoadds                   = $playerSetting->lottoadds;
            $selfInviteCode->is_tester                   = $player->is_tester;
            $selfInviteCode->code                        = $player->extend_id;
            $selfInviteCode->prefix                      = $prefix;

            if($enableFixedEarnings==1){
                $playerSetting->earnings                  = $defaultEarnings;
            }

            $selfInviteCode->save();

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollback();
            Clog::recordabnormal('用户注册异常'.$e->getMessage());
            return '操作异常1：'.$e->getMessage();
        }
    }

    //直接开户
    static function createChild($carrier,$user)
    {
        $enableFixedEarnings    = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'enable_fixed_earnings',$user->prefix);
        $defaultEarnings        = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'default_earnings',$user->prefix);
        $input                  = request()->all();
        $input['lottoadds']     = CarrierCache::getCarrierConfigure($carrier->id, 'default_lottery_odds');
        $input['type']          = 2;
        $language               = CarrierCache::getLanguageByPrefix($user->prefix);

        if($enableFixedEarnings ==1){
            $input['earnings'] = $defaultEarnings;
        }

        if(!isset($input['user_name']) || empty(trim($input['user_name']))) {
            return config('language')[$language]['error84'];
        }

        if(!isset($input['password']) || empty(trim($input['password']))) {
            return config('language')[$language]['error85'];
        }

        if(!isset($input['mobile']) || empty(trim($input['mobile']))) {
            return config('language')[$language]['error86'];
        }

        $existPlayer = Player::where('carrier_id',$carrier->id)->where('mobile',$input['mobile'])->first();
        if($existPlayer){
            return config('language')[$language]['error107'];
        }

        if(isset($input['earnings']) && !is_numeric($input['earnings'])){
            return config('language')[$language]['error103'];
        }

        if(!isset($input['earnings'])) {
            $input['earnings'] = 0;
        }

        $playerSetting               = PlayerCache::getPlayerSetting($user->player_id);

        if($playerSetting->earnings < intval($input['earnings']) || intval($input['earnings']) < 0) {
            return config('language')[$language]['error104'];
        }  

        $existUsername = self::where('carrier_id',$carrier->id)->where('user_name',$input['user_name'])->first();

        if($existUsername) {
            return config('language')[$language]['error106'];
        }

        //限制当前IP注册数量
        $registerNum = Player::where('carrier_id',$carrier->id)->where('prefix',$user->prefix)->where('register_ip',real_ip())->count();

        if($registerNum == CarrierCache::getCarrierMultipleConfigure($carrier->id,'player_max_register_one_ip_minute',$user->prefix)) {
            return config('language')[$language]['error108'];
        }
        
        $carrierPlayerLevel      = CarrierPlayerGrade::where('carrier_id',$carrier->id)->where('prefix',$user->prefix)->where('is_default',1)->first();
        $player                  = new Player();
        $player->top_id          = $user->top_id;
        $player->is_tester       = $user->is_tester;
        $player->parent_id       = $user->id;
        $player->user_name       = $input['user_name'];
        $player->password        = bcrypt($input['password']);
        $player->paypassword     = null;
        $player->carrier_id      = $user->carrier_id;
        $player->player_level_id = $carrierPlayerLevel->id;
        $player->level           = $user->level+1;
        $player->type            = 2;
        $player->mobile          = $input['mobile'];

        if(isset($input['real_name']) && !empty($input['real_name'])){
            $player->real_name            = $input['real_name'];
        }

        $player->nick_name       = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'default_nick_name',$player->prefix);
        if($enableFixedEarnings ==1 && $defaultEarnings > 0){
            $player->win_lose_agent =1;
        } 
        $player->save();

        if(is_null($player->rid)){
            $parent          = Player::where('player_id',$player->parent_id)->first();
            $player->rid     = $parent->rid.'|'.$player->player_id;
            $player->save();
        }

        //附加
        $playerSetting                              = new PlayerSetting();
        $playerSetting->player_id                   = $player->player_id;
        $playerSetting->carrier_id                  = $player->carrier_id;
        $playerSetting->top_id                      = $player->top_id;
        $playerSetting->parent_id                   = $player->parent_id;
        $playerSetting->rid                         = $player->rid;
        $playerSetting->prefix                      = $player->prefix;
        $playerSetting->user_name                   = $player->user_name;
        $playerSetting->level                       = $player->level;
        $playerSetting->is_tester                   = $player->is_tester;
        $playerSetting->lottoadds                   = $input['lottoadds'];
        $playerSetting->earnings                    = $input['earnings'];
        $playerSetting->save();

        //生成邀请链接
        $playerInviteCode                              = new PlayerInviteCode();
        $playerInviteCode->carrier_id                  = $player->carrier_id;
        $playerInviteCode->player_id                   = $player->player_id;
        $playerInviteCode->rid                         = $player->rid;
        $playerInviteCode->username                    = $player->user_name;
        $playerInviteCode->is_tester                   = $player->is_tester;
        $playerInviteCode->lottoadds                   = $playerSetting->lottoadds;        
        $playerInviteCode->code                        = $player->extend_id;
        $playerInviteCode->earnings                    = $playerSetting->earnings;
        $playerInviteCode->type                        = 2;
        $playerInviteCode->prefix                      = $player->prefix;
        $playerInviteCode->save();

        return true;
    }

    public function playerLoginInfo()
    {
        $input       = request()->all();
        $currentPage = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize    = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset      = ($currentPage - 1) * $pageSize;

        $query = PlayerLogin::where('player_id',$this->player_id)->orderBy('id','desc');

        if(isset($input['startTime']) && strtotime($input['startTime'])) {
            $query->where('login_time','>=',strtotime($input['startTime'].' 00:00:00'));
        } else {
            $query->where('login_time','>=',strtotime(date('Y-m-01 00:00:00')));
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])){
            $query->where('login_time','<=',strtotime($input['endTime'].' 23:59:59'));
        } else {
            $query->where('login_time','<=',time());
        }

        $total       = $query->count();
        $items       = $query->skip($offset)->take($pageSize)->get();

        return ['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];

    }

    public function playerBankList($carrier_id)
    {
        $input = request()->all();
        $items = PlayerBankCard::select('inf_carrier_bank_type.bank_name','inf_player_bank_cards.card_owner_name','inf_player_bank_cards.card_account','inf_player_bank_cards.status','inf_player_bank_cards.is_default','inf_player_bank_cards.bank_Id','inf_player_bank_cards.id')
            ->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')
            ->where('inf_player_bank_cards.player_id',$this->player_id)
            ->where('inf_carrier_bank_type.carrier_id',$carrier_id)
            ->orderBy('inf_player_bank_cards.id','desc')
            ->get();

        return ['data' => $items];
    }

    public function playerAlipayList($carrier_id)
    {
        $input = request()->all();
        $items = PlayerAlipay::where('carrier_id',$carrier_id)->where('player_id',$this->player_id)->get();
        return ['data' => $items];
    }

    public function playerExchangeList()
    {
        $ips          = [];
        $playerLogins = PlayerLogin::where('player_id',$this->player_id)->pluck('login_ip')->toArray();
        $playerLogins = array_unique($playerLogins);

        if($playerLogins) {
            foreach ($playerLogins as  $value) {
                $ips[] = $value;
            }
        }

        if(!in_array($this->register_ip, $ips)) {
            $ips[] = $this->register_ip;
        }

        $sameLoginIps         = PlayerLogin::select('player_id','login_ip','login_time')->where('carrier_id',$this->carrier_id)->where('player_id','<>',$this->player_id)->whereIn('login_ip',$ips)->get();
        $player        = $this;

        $likePlayer = Player::where(function ($query) use ($player) {
            $query->where('real_name', $player->real_name)->where('carrier_id',$player->carrier_id)->where('prefix',$player->prefix)->where('is_tester',0)->whereNotNull('real_name')->where('real_name', '<>', '')->where('player_id', '<>', $player->player_id);
        })->orwhere(function ($query) use ($player) {
            $query->where('mobile', $player->mobile)->where('carrier_id',$player->carrier_id)->where('prefix',$player->prefix)->where('is_tester',0)->whereNotNull('mobile')->where('mobile', '<>', '')->where('player_id', '<>', $player->player_id);
        })->orwhere(function ($query) use ($player,$ips) {
            $query->whereIn('register_ip', $ips)->where('carrier_id',$player->carrier_id)->where('prefix',$player->prefix)->where('is_tester',0)->whereNotNull('register_ip')->where('register_ip', '<>', '')->where('player_id', '<>', $player->player_id);
        })->get();

        if(!empty(trim($this->real_name)) && !is_null($this->real_name)) {
            $ips[] = $this->real_name;
        }

        if(!empty(trim($this->mobile)) && !is_null($this->mobile)) {
            $ips[] = $this->mobile;
        }

        $all = [];

        $loginIps = PlayerLogin::where('player_id',$this->player_id)->pluck('login_ip')->toArray();
        $loginIps = array_unique($loginIps);
        //添加自已信息
        $arr                = [];
        $arr['user_name']   = $this->user_name;
        $arr['real_name']   = $this->real_name;
        $arr['mobile']      = $this->mobile;
        $arr['register_ip'] = $this->register_ip;
        $arr['created_at']  = $this->created_at;
        $arr['login_ip']    = count($loginIps)?implode(',',$loginIps):'';
        $arr['login_at']    = date('Y-m-d H:i:s',$this->login_time);
        $arr['fingerprint'] = '********';
        $arr['bankcard']    = '********';
        $arr['digitaladdress'] = '********';
        $all[]              = $arr;

        if($sameLoginIps) {
            foreach ($sameLoginIps as $key => $value) {
                $arr                = [];
                $samePlayer         = Player::where('player_id',$value->player_id)->first();
                if($samePlayer->prefix==$player->prefix){
                    $arr['user_name']   = $samePlayer->user_name;
                    $arr['real_name']   = $samePlayer->real_name;
                    $arr['mobile']      = $samePlayer->mobile;
                    $arr['register_ip'] = $samePlayer->register_ip;
                    $arr['created_at']  = date('Y-m-d H:i:s',strtotime($samePlayer->created_at));
                    $arr['login_ip']    = $value->login_ip;
                    $arr['login_at']    = date('Y-m-d H:i:s',$value->login_time);
                    $arr['fingerprint'] = '--------';
                    $arr['bankcard']    = '--------';
                    $arr['digitaladdress'] = '--------';
                    $all[]              = $arr;
                }

            }
        }

        if($likePlayer) {
            foreach ($likePlayer as $key => $value) {
                $arr                = [];
                $arr['user_name']   = $value->user_name;
                $arr['real_name']   = $value->real_name;
                $arr['mobile']      = $value->mobile;
                $arr['register_ip'] = $value->register_ip;
                $arr['created_at']  = date('Y-m-d H:i:s',strtotime($value->created_at));
                $arr['login_ip']    = $value->login_ip;
                $arr['login_at']    = is_null($value->login_at)?'':date('Y-m-d H:i:s',strtotime($value->login_at));
                $arr['fingerprint'] = '--------';
                $arr['bankcard']    = '--------';
                $arr['digitaladdress'] = '--------';
                $all[]              = $arr;
            }
        }

        //相同收款信息
        $playerBankCards      = PlayerBankCard::where('player_id',$this->player_id)->pluck('card_account')->toArray();
        $playerDigitalAddress = PlayerDigitalAddress::where('player_id',$this->player_id)->pluck('address')->toArray();

        if(count($playerBankCards)){
            $sameBankCardPlayers = PlayerBankCard::where('carrier_id',$this->carrier_id)->where('prefix',$player->prefix)->whereIn('card_account',$playerBankCards)->where('player_id', '<>', $this->player_id)->pluck('player_id')->toArray();
            if(count($sameBankCardPlayers)){
                $bankCardPlayers = Player::whereIn('player_id',$sameBankCardPlayers)->where('prefix',$player->prefix)->get(); 
                foreach($bankCardPlayers as $key => $value){
                    $arr                = [];
                    $arr['user_name']   = $value->user_name;
                    $arr['real_name']   = $value->real_name;
                    $arr['mobile']      = $value->mobile;
                    $arr['fingerprint'] = $value->mobile;
                    $arr['register_ip'] = $value->register_ip;
                    $arr['created_at']  = date('Y-m-d H:i:s',strtotime($value->created_at));
                    $arr['login_ip']    = $value->login_ip;
                    $arr['login_at']    = is_null($value->login_at)?'':date('Y-m-d H:i:s',strtotime($value->login_at));
                    $arr['fingerprint'] = '--------';
                    $arr['bankcard']    = '********';
                    $arr['digitaladdress'] = '--------';
                    $all[]              = $arr;
                }
            }
        }

        //相同数字币收款地址
        if(count($playerDigitalAddress)){
            $sameDigitalAddressPlayers = PlayerDigitalAddress::where('carrier_id',$this->carrier_id)->where('prefix',$player->prefix)->whereIn('address',$playerDigitalAddress)->where('player_id', '<>', $this->player_id)->pluck('player_id')->toArray();
            if(count($sameDigitalAddressPlayers)){
                $digitalAddressPlayers = Player::whereIn('player_id',$sameDigitalAddressPlayers)->where('prefix',$player->prefix)->get(); 
                foreach($digitalAddressPlayers as $key => $value){
                    $arr                = [];
                    $arr['user_name']   = $value->user_name;
                    $arr['real_name']   = $value->real_name;
                    $arr['mobile']      = $value->mobile;
                    $arr['fingerprint'] = $value->mobile;
                    $arr['register_ip'] = $value->register_ip;
                    $arr['created_at']  = date('Y-m-d H:i:s',strtotime($value->created_at));
                    $arr['login_ip']    = $value->login_ip;
                    $arr['login_at']    = is_null($value->login_at)?'':date('Y-m-d H:i:s',strtotime($value->login_at));
                    $arr['fingerprint'] = '--------';
                    $arr['bankcard']    = '--------';
                    $arr['digitaladdress'] = '********';
                    $all[]              = $arr;
                }
            }
        }

        //相似指纹
        $fingerprintS = PlayerFingerprint::where('player_id',$this->player_id)->pluck('fingerprint')->toArray();
        if(count($fingerprintS)){
            $sameFingerprintPlayers = PlayerFingerprint::where('carrier_id',$this->carrier_id)->whereIn('fingerprint',$fingerprintS)->where('player_id', '<>', $this->player_id)->pluck('player_id')->toArray();
            if(count($sameFingerprintPlayers)){
                $fingerprintPlayers = Player::whereIn('player_id',$sameFingerprintPlayers)->where('prefix',$player->prefix)->get(); 
                foreach($fingerprintPlayers as $key => $value){
                    $arr                = [];
                    $arr['user_name']   = $value->user_name;
                    $arr['real_name']   = $value->real_name;
                    $arr['mobile']      = $value->mobile;
                    $arr['fingerprint'] = $value->mobile;
                    $arr['register_ip'] = $value->register_ip;
                    $arr['created_at']  = date('Y-m-d H:i:s',strtotime($value->created_at));
                    $arr['login_ip']    = $value->login_ip;
                    $arr['login_at']    = is_null($value->login_at)?'':date('Y-m-d H:i:s',strtotime($value->login_at));
                    $arr['fingerprint'] = '********';
                    $arr['bankcard']    = '--------';
                    $arr['digitaladdress'] = '--------';
                    $all[]              = $arr;
                }
            }
        }

        $ips[]   = '********';

        $only    = [];
        $onlyKey = [];

        foreach ($all as $key => $value) {
            if(!in_array($value['user_name'], $onlyKey) && substr($value['user_name'], 0,6) != 'guest_') {
                $onlyKey[] = $value['user_name'];
                $only[]    = $value;
            }
        }
        return ['data' => $only, 'options' => $ips];
    }

    public function playerChangePassword($carrier)
    {
        $input    = request()->all();
        $language = CarrierCache::getLanguageByPrefix($this->prefix);

        if(!isset($input['password']) || !isset($input['newpassword'])) {
            return config('language')[$language]['error21'];
        }

        //登录密码不能与支付密码相同
        if(\Hash::check($input['newpassword'], $this->paypassword)) {
            return config('language')[$language]['error129'];
        }

        //修改登录密码
        if(!\Hash::check($input['password'], $this->password)) {
            return config('language')[$language]['error68'];
        }

        $this->password = bcrypt($input['newpassword']);
        $this->save();

        return true;
    }

    public function playerChangePayPassword($carrier)
    {
        $input    = request()->all();
        $language = CarrierCache::getLanguageByPrefix($this->prefix);

        if($this->paypassword){
            if(!isset($input['password'])  || !isset($input['newpassword'])) {
                return config('language')[$language]['error21'];
            }

            //登录密码不能与支付密码相同
            if(\Hash::check($input['newpassword'], $this->password)) {
                return config('language')[$language]['error129'];
            }

            //修改资金密码
            if(!\Hash::check($input['password'], $this->paypassword)) {
                return config('language')[$language]['error69'];
            }

        } else {
            if(!isset($input['loginpassword'])  || !isset($input['newpassword'])) {
                return config('language')[$language]['error21'];
            }

            //登录密码不能与支付密码相同
            if(\Hash::check($input['newpassword'], $this->password)) {
                return config('language')[$language]['error129'];
            }

            //修改资金密码
            if(!\Hash::check($input['loginpassword'], $this->password)) {
                return config('language')[$language]['error68'];
            }
        }

        $this->paypassword = bcrypt($input['newpassword']);
        $this->save();

        return true;
    }

    public function bankcardList()
    {
        $data = PlayerBankCard::select('inf_player_bank_cards.*','inf_carrier_bank_type.bank_name','inf_carrier_bank_type.bank_name','inf_carrier_bank_type.bank_background_url')
            ->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')
            ->where('inf_player_bank_cards.player_id',$this->player_id)
            ->where('inf_carrier_bank_type.carrier_id',$this->carrier_id)
            ->where('inf_player_bank_cards.status',1)
            ->orderBy('inf_player_bank_cards.id','desc')
            ->get();

        return ['data'=>$data, 'count'=>count($data)];
    }

    public function alipayList()
    {
        $data = PlayerAlipay::where('player_id',$this->player_id)->where('carrier_id',$this->carrier_id)->where('status',1)->orderBy('id','desc')->get();

        return ['data'=>$data, 'count'=>count($data)];
    }

    public function bankcardDel($id,$carrier)
    {
        $playerBankCard = PlayerBankCard::where('id',$id)->first();
        $language       = CarrierCache::getLanguageByPrefix($this->prefix);

        if(!$playerBankCard) {
            return config('language')[$language]['error73'];
        }

        if($playerBankCard->player_id != $this->player_id) {
            return config('language')[$language]['error74'];
        }

        $playerBankCard->status = $playerBankCard->status ? 0: 1;
        $playerBankCard->save();

        return true;
    }

    public function alipayDel($id,$carrier)
    {   
        $playerAlipay   = PlayerAlipay::where('id',$id)->first();
        $language       = CarrierCache::getLanguageByPrefix($this->prefix);

        if(!$playerAlipay) {
            return config('language')[$language]['error516'];
        }

        if($playerAlipay->player_id != $this->player_id) {
            return config('language')[$language]['error517'];
        }

        $playerAlipay->status = $playerAlipay->status ? 0: 1;
        $playerAlipay->save();

        return true;
    }

    public function messageList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerMessage::where('player_id',$this->player_id)->orderBy('player_id','desc');

        if(isset($input['is_read']) && $input['is_read']!=='') {
            $query->where('is_read',$input['is_read']);
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        return ['item' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function messageChangeStatus($id,$carrier)
    {
        $playerMessage = PlayerMessage::where('id',$id)->first();
        $language       = CarrierCache::getLanguageByPrefix($this->prefix);

        if(!$playerMessage) {
            return config('language')[$language]['error75'];
        }

        if($playerMessage->player_id != $this->player_id) {
            return config('language')[$language]['error55'];
        }

        $playerMessage->is_read = 1;
        $playerMessage->save();

        return true;
    }

    public function messageDelete()
    {
        $input =request()->all();

        //type=1 删除所有，2删除已读，3删除单条
        if(!isset($input['type']) || !in_array($input['type'], [1,2,3])){
            return '对不起，类型取值不正确';
        }

        if($input['type']==1){
            PlayerMessage::where('player_id',$this->player_id)->delete();
            return true;
        } elseif($input['type']==2){
            PlayerMessage::where('player_id',$this->player_id)->where('is_read',1)->delete();
            return true;
        } elseif($input['type']==3){
            if(!isset($input['id'])){
                return '对不起，参数不正确';
            } else{
                $existPlayerMessage = PlayerMessage::where('player_id',$this->player_id)->where('id',$input['id'])->first();
                if(!$existPlayerMessage){
                    return '对不起，此条数据不存在';
                }
                $existPlayerMessage->delete();
                return true;
            }
        }
    }
    public function platList()
    {
        $carrierGamePlats       = CarrierGamePlat::select('map_carrier_game_plats.game_plat_id','def_main_game_plats.main_game_plat_code')
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','map_carrier_game_plats.game_plat_id')
            ->where('map_carrier_game_plats.carrier_id',$this->carrier_id)
            ->get();

        $playerGameAccounts     = PlayerGameAccount::where('player_id',$this->player_id)->get();
        $playerGameAccountArray = [];

        foreach ($playerGameAccounts as $k => $v) {
            $playerGameAccountArray[$v->main_game_plat_code] = $v->balance;
        }

        $data                 = [];

        foreach ($carrierGamePlats as $key => $value) {
            $temp['main_game_plat_code'] = $value->main_game_plat_code;
            $temp['game_plat_id']        = $value->game_plat_id;
            $temp['desc']                = config('main')['plats'][$value->main_game_plat_code];
            $temp['balance']             = isset($playerGameAccountArray[$value->main_game_plat_code]) ? $playerGameAccountArray[$value->main_game_plat_code]:'0.00';
            $data[]                      = $temp;
        }

        $temp                                   = [];
        $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->first();
        $temp['main']['main_game_plat_code']    = 'main';
        $temp['main']['balance']                = bcdiv($playerAccount->balance + $playerAccount->frozen,10000,2);
        $data[]                                 = $temp;

        return ['data' => $data];
    }

    public function refreshplat($platcode)
    {
        $data = [];

        if(empty(trim($platcode))) {
            $carrierGamePlats       = CarrierGamePlat::select('map_carrier_game_plats.game_plat_id','def_main_game_plats.main_game_plat_code')
                ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','map_carrier_game_plats.game_plat_id')
                ->where('map_carrier_game_plats.carrier_id',$this->carrier_id)
                ->get();

            $playerGameAccounts     = PlayerGameAccount::where('player_id',$this->player_id)->get()->toArray();
            $playerGameAccountIds   = PlayerGameAccount::where('player_id',$this->player_id)->pluck('main_game_plat_id')->toArray();
            $playerAccount          = PlayerAccount::where('player_id',$this->player_id)->first();
            $data['main']           = bcdiv($playerAccount->balance + $playerAccount->frozen,10000,2);
            $playGameAccountArr     = [];

            foreach ($playerGameAccounts as $key => $value) {
                $playGameAccountArr[$value['main_game_plat_id']] = $value;
            }

            foreach ($carrierGamePlats as $k => $v) {
                if(in_array($v->game_plat_id,  $playerGameAccountIds)) {
                    if($playGameAccountArr[$v->game_plat_id]['exist_transfer']) {
                        request()->offsetSet('accountUserName' , $playGameAccountArr[$v->game_plat_id]['account_user_name']);
                        request()->offsetSet('password'         , $playGameAccountArr[$v->game_plat_id]['password']);

                        $game = new Game($playGameAccountArr[$v->game_plat_id]['main_game_plat_code']);
                        $item = $game->getBalance();

                        if(is_array($item) &&$item['success']) {
                            $data[$playGameAccountArr[$v->game_plat_id]['main_game_plat_code']] = $item['data']['balance'];
                        } else {
                            $data[$playGameAccountArr[$v->game_plat_id]['main_game_plat_code']] = 'error';
                        }
                    } else {
                        $data[$v->main_game_plat_code] = '0.00';
                    }
                } else {
                    $data[$v->main_game_plat_code] = '0.00';
                }
            }
            return $data;
        } else {
            $playerGameAccount = PlayerGameAccount::where('player_id',$this->player_id)->where('main_game_plat_code',$platcode)->first();

            if(!$playerGameAccount) {
                $data[$platcode]  = '0.00';

                return $data;
            } else {
                request()->offsetSet('accountUserName' , $playerGameAccount->account_user_name);
                request()->offsetSet('password'        , $playerGameAccount->password);

                $game = new Game($platcode);
                $item = $game->getBalance();

                if(is_array($item) && $item['success']) {
                    $data[$platcode] = $item['data']['balance'];
                } else {
                    $data[$platcode] = 'error';
                }
                return $data;
            }
        }
    }

    public function betflowListStat()
    {
        $input               = request()->all();
        $currentPage         = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize            = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset              = ($currentPage - 1) * $pageSize;

        $query               = ReportPlayerStatDay::select('day','win_amount','lottery_winorloss','available_bets','lottery_available_bets')->where('player_id',$this->player_id)->orderBy('id','desc');
        $query1              = ReportPlayerStatDay::select(\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'))->where('player_id',$this->player_id);

        if(isset($input['startDay'])){
            $input['startDay'] =str_replace('-','',$input['startDay']);
        }

        if(isset($input['endDay'])){
            $input['endDay'] =str_replace('-','',$input['endDay']);
        }

        if(isset($input['startDay']) && strtotime($input['startDay'])){
            $query->where('day','>=',$input['startDay']);
            $query1->where('day','>=',$input['startDay']);
        }

        if(isset($input['endDay']) && strtotime($input['endDay'])){
            $query->where('day','<=',$input['endDay']);
            $query1->where('day','<=',$input['endDay']);
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();
        $stattotal      = $query1->first();


        foreach($items as $key => &$value){
            $value->availableBets = bcdiv($value->available_bets,10000,2) + bcdiv($value->lottery_available_bets,10000,2);
            $value->winAmount     = bcdiv($value->win_amount,10000,2) + bcdiv($value->lottery_winorloss,10000,2);
            $week                 = date('w',strtotime($value->day));
            switch ($week) {
                case '0':
                    $value->week = '星期日';
                    break;
                case '1':
                    $value->week = '星期一';
                    break;
                case '2':
                    $value->week = '星期二';
                    break;
                case '3':
                    $value->week = '星期三';
                    break;
                case '4':
                    $value->week = '星期四';
                    break;
                case '5':
                    $value->week = '星期五';
                    break;
                case '6':
                    $value->week = '星期六';
                    break;
                default:
                    // code...
                    break;
            }
            $year  = substr($value->day,0,4);
            $month = ltrim(substr($value->day,4,2),'0');
            $day   = ltrim(substr($value->day,6,2),'0');
            $value->day1 = $year.'-'.substr($value->day,4,2).'-'.substr($value->day,6,2);
            $value->day  = $month.'月'.$day.'日';
        }

        if(!$stattotal->available_bets){
            $stattotal->available_bets = 0 ;
        }

        if(!$stattotal->lottery_available_bets){
            $stattotal->lottery_available_bets = 0;
        }

        if(!$stattotal->win_amount){
            $stattotal->win_amount = 0;
        }

        if(!$stattotal->lottery_winorloss){
            $stattotal->lottery_winorloss = 0;
        }
        
        $stattotal->availableBets = bcdiv($stattotal->available_bets,10000,2) + bcdiv($stattotal->lottery_available_bets,10000,2);
        $stattotal->winAmount     = bcdiv($stattotal->win_amount,10000,2) + bcdiv($stattotal->lottery_winorloss,10000,2);


        return ['item' => $items, 'total' => $total ,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)),'stattotal'=>$stattotal];
    }

    public function betflowList()
    {
        $input           = request()->all();
        $gameCategoryArr = [1,2,3,4,5,6,7];
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;
        $query           = PlayerBetFlow::where('player_id',$this->player_id)->orderBy('bet_time','desc');
        $query1          = PlayerBetFlow::where('player_id',$this->player_id)->orderBy('bet_time','desc');

        if(isset($input['game_category']) && in_array($input['game_category'], $gameCategoryArr)) {
            $query->where('game_category',$input['game_category']);
            $query1->where('game_category',$input['game_category']);
        }


        if(isset($input['startTime']) && !empty(trim($input['startTime'])) && strtotime($input['startTime']) ) {
            $query->where('bet_time','>=',strtotime($input['startTime']));
            $query1->where('bet_time','>=',strtotime($input['startTime']));
        }

        if(isset($input['endTime']) && !empty(trim($input['endTime'])) && strtotime($input['endTime'])) {
            if(mb_strlen($input['endTime'])==10){
                $query->where('bet_time','<=',strtotime($input['endTime'].' 23:59:59'));
                $query1->where('bet_time','<=',strtotime($input['endTime'].' 23:59:59'));
            } else {
                $query->where('bet_time','<=',strtotime($input['endTime']));
                $query1->where('bet_time','<=',strtotime($input['endTime']));
            }
        }

        if(isset($input['game_plat_id']) && trim($input['game_plat_id'])!='') {
            if($input['game_plat_id']==17){
                $query->whereIn('main_game_plat_id',[2,17,43,66]);
                $query1->whereIn('main_game_plat_id',[2,17,43,66]);
            } elseif($input['game_plat_id']==16){
                $query->whereIn('main_game_plat_id',[8,16,23,50]);
                $query1->whereIn('main_game_plat_id',[8,16,23,50]);
            } elseif($input['game_plat_id']==22){
                $query->whereIn('main_game_plat_id',[11,19,22]);
                $query1->whereIn('main_game_plat_id',[11,19,22]);
            } elseif($input['game_plat_id']==4){
                $query->whereIn('main_game_plat_id',[4,12,84]);
                $query1->whereIn('main_game_plat_id',[4,12,84]);
            } elseif($input['game_plat_id']==58){
                $query->whereIn('main_game_plat_id',[15,58,82]);
                $query1->whereIn('main_game_plat_id',[15,58,82]);
            } elseif($input['game_plat_id']==45){
                $query->whereIn('main_game_plat_id',[45,48,67]);
                $query1->whereIn('main_game_plat_id',[45,48,67]);
            } elseif($input['game_plat_id']==41){
                $query->whereIn('main_game_plat_id',[41,55,80,88]);
                $query1->whereIn('main_game_plat_id',[41,55,80,88]);
            } elseif($input['game_plat_id']==5){
                $query->whereIn('main_game_plat_id',[5,79]);
                $query1->whereIn('main_game_plat_id',[5,79]);
            } else{
                $query->where('main_game_plat_id',$input['game_plat_id']);
                $query1->where('main_game_plat_id',$input['game_plat_id']);
            }
        }

        if(isset($input['game_flow_code']) && !empty(trim($input['game_flow_code']))) {
            $query->where('game_flow_code',$input['game_flow_code']);
            $query1->where('game_flow_code',$input['game_flow_code']);
        }

        if(isset($input['game_status']) && in_array($input['game_status'],[0,1,2])) {
            $query->where('game_status',$input['game_status']);
            $query1->where('game_status',$input['game_status']);
        }

        $total            = $query->count();
        $items            = $query->skip($offset)->take($pageSize)->get();
        $companyWinAmount = $query1->sum('company_win_amount');
        $selfTotal        = $query->select(\DB::raw('SUM(bet_amount) as bet_amount'),\DB::raw('SUM(available_bet_amount) as available_bet_amount'),\DB::raw('SUM(company_win_amount) as company_win_amount'))->first();

        if(is_null($selfTotal)){
            $selfTotal = new PlayerBetFlow();
            $selfTotal->bet_amount           = 0.00;
            $selfTotal->available_bet_amount = 0.00;
            $selfTotal->company_win_amount   = 0.00;
        } else if(is_null($selfTotal->bet_amount)){
            $selfTotal->bet_amount           = 0.00;
            $selfTotal->available_bet_amount = 0.00;
            $selfTotal->company_win_amount   = 0.00;
        }

        $mainGamePlats  = MainGamePlat::whereNotIn('main_game_plat_code',config('main')['fakegameplatnames'])->get();
        $data           = [];

        foreach ($mainGamePlats as $key => $value) {
            $data[$value->main_game_plat_code] = $value->alias;
        }

        foreach ($items as $key => &$value) {
            if($value->main_game_plat_code=='pg'){
                $t1   = str_replace('181217-','',$value->game_flow_code);
                $t1   = str_replace('181216-','',$t1);
                $t1   = str_replace('181220-','',$t1);
                $t1   = str_replace('181244-','',$t1);
                $t1   = str_replace('181249-','',$t1);

                if(strpos($t1,':') !== false){
                    $arr1                  = explode(':',$t1);
                    $value->game_flow_code = $arr1[0];
                } else{
                    $value->game_flow_code = $t1;
                }
            } elseif($value->main_game_plat_code=='pp'){
                $t1   = str_replace('181218-','',$value->game_flow_code);
                $t1   = str_replace('181219-','',$t1);
                $t1   = str_replace('181223-','',$t1);
                $t1   = str_replace('181243-','',$t1);
                $t1   = str_replace('181248-','',$t1);

                if(strpos($t1,':') !== false){
                    $arr1                  = explode(':',$t1);
                    $value->game_flow_code = $arr1[0];
                } else{
                    $value->game_flow_code = $t1;
                }
            } elseif($value->main_game_plat_code=='jili'){
                $t1   = str_replace('181221-','',$value->game_flow_code);
                $t1   = str_replace('181222-','',$t1);
                $t1   = str_replace('181240-','',$t1);
                $t1   = str_replace('181257-','',$t1);

                if(strpos($t1,':') !== false){
                    $arr1                  = explode(':',$t1);
                    $value->game_flow_code = $arr1[0];
                } else{
                    $value->game_flow_code = $t1;
                }
            } elseif($value->main_game_plat_code=='habanero'){
                $t1   = str_replace('181236-','',$value->game_flow_code);
                $t1   = str_replace('181250-','',$t1);
                $value->game_flow_code = $t1;
            } elseif($value->main_game_plat_code=='fc'){
                $t1   = str_replace('181234-','',$value->game_flow_code);
                $t1   = str_replace('181255-','',$t1);
                $value->game_flow_code = $t1;
            } elseif($value->main_game_plat_code=='jdb'){
                $t1   = str_replace('181233-','',$value->game_flow_code);
                $t1   = str_replace('181256-','',$t1);
                $value->game_flow_code = $t1;
            } elseif($value->main_game_plat_code=='cq9'){
                $t1   = str_replace('181232-','',$value->game_flow_code);
                $t1   = str_replace('181247-','',$t1);
                $value->game_flow_code = $t1;
            }
        }

        return ['companyWinAmount' =>$companyWinAmount,'item' => $items, 'total' => $total ,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)),'plats'=>$data,'selfTotal'=>$selfTotal,'userName' => $this->user_name];
    }

    public function digitalWithdrawApply($carrier)
    {
        $input                        = request()->all();
        $amount                       = $input['amount'] ?? 0;
        $playerDigitalAddressId       = $input['player_digital_address_id'] ?? '';
        $minWithdraw                  = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'finance_min_withdraw',$this->prefix);
        $minWithdrawalUsdt            = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'min_withdrawal_usdt',$this->prefix);
        $withdrawDigitalRate          = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'withdraw_digital_rate',$this->prefix);
        $inROutU                      = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'in_r_out_u',$this->prefix);
        $inTOutU                      = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'in_t_out_u',$this->prefix);
        $enableLimitOneWithdrawal     = CarrierCache::getCarrierConfigure($this->carrier_id, 'enable_limit_one_withdrawal');
        $withdrawalNeedSms            = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'withdrawal_need_sms',$this->prefix);
        $enableSafeBox                = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'enable_safe_box',$this->prefix);
        $materialIds                  = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'materialIds',$this->prefix);
        $enableVoucherRecharge        = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'enable_voucher_recharge',$this->prefix);
        $voucherNeedRechargeAmount    = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'voucher_need_recharge_amount',$this->prefix);
        $language                     = CarrierCache::getLanguageByPrefix($this->prefix);
        $materialIdsArr               = explode(',',$materialIds);

        if(in_array($this->player_id,$materialIdsArr)){
            return config('language')[$language]['error534'];
        }

        //查询最近一次充值
        $latePlayerDepositPayLog      = PlayerDepositPayLog::where('player_id',$this->player_id)->where('status',1)->orderBy('id','desc')->first();
        $cnyThirdWalletArr            = config('main')['digitalpay']['CNY'];
        
        if($latePlayerDepositPayLog){
            if(strpos($latePlayerDepositPayLog->pay,'USDT') === false){
                $withdrawDigitalRate = $inROutU;
            } else{
                foreach ($cnyThirdWalletArr as $key => $value) {
                    if(stristr($latePlayerDepositPayLog->collection, $value)!==false){
                        $withdrawDigitalRate = $inTOutU;
                        break;
                    }
                }
            }
        }

        $disableWithdrawChannelIds    = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'disable_withdraw_channel',$this->prefix);
        $disableWithdrawChannelIds    = json_decode($disableWithdrawChannelIds,true);

        $playerDigitalAddress         = PlayerDigitalAddress::where('id',$playerDigitalAddressId)->where('player_id',$this->player_id)->where('status',1)->first();
        if (!$playerDigitalAddress) {
            return config('language')[$language]['error191'];
        }

        if(count($disableWithdrawChannelIds) && in_array($playerDigitalAddress->type,$disableWithdrawChannelIds)){
            return config('language')[$language]['error246'];
        }

        if($this->is_tester == 1) {
            return config('language')[$language]['error138'];
        }

        if(!isset($input['password']) || empty($input['password'])) {
            return config('language')[$language]['error21'];
        }

        if(!\Hash::check($input['password'], $this->paypassword)) {
            return config('language')[$language]['error76'];
        }

        if($this->frozen_status==3){
            return config('language')[$language]['error196'];
        }

        if (!is_numeric($amount) || intval($amount) != $amount || $amount <1 ) {
            return config('language')[$language]['error77'];
        }

        //判断是否启用体验券充值才能提现
        if($enableVoucherRecharge==1){
            $playerTransferExtends = PlayerTransfer::where('player_id',$this->player_id)->whereNotIn('type',['transfer_in_wallet','transfer_in_safe','casino_transfer_in','casino_transfer_out_error','casino_transfer_out','code_gift'])->first();
            //如果没有充值直接扣除多出来的彩金
            if(!$playerTransferExtends){
                //扣除多余出来的金额
                $cacheKey              = "player_" .$this->player_id;
                $redisLock             = Lock::addLock($cacheKey,60);

                if (!$redisLock) {
                    return config('language')[$language]['error20'];
                } else {
                    try {
                        if($enableSafeBox){
                            if($amount > $voucherNeedRechargeAmount*10000){

                                \DB::beginTransaction();
                                $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();
                                $diff                                   = $playerAccount->agentbalance - $voucherNeedRechargeAmount*10000;

                                if(bcdiv($playerAccount->agentbalance,10000,0) < $amount) {
                                    Lock::release($redisLock);
                                    return config('language')[$language]['error58'];
                                }

                                //扣除多出来的余额
                                $playerTransfer                         = new PlayerTransfer();
                                $playerTransfer->prefix                 = $this->prefix;
                                $playerTransfer->carrier_id             = $this->carrier_id;
                                $playerTransfer->rid                    = $this->rid;
                                $playerTransfer->top_id                 = $this->top_id;
                                $playerTransfer->parent_id              = $this->parent_id;
                                $playerTransfer->player_id              = $this->player_id;
                                $playerTransfer->is_tester              = $this->is_tester;
                                $playerTransfer->user_name              = $this->user_name;
                                $playerTransfer->level                  = $this->level;
                                $playerTransfer->mode                   = 2;
                                $playerTransfer->type                   = 'gift_transfer_reduce';
                                $playerTransfer->type_name              = config('language')[$language]['text59'];
                                $playerTransfer->day_m                  = date('Ym');
                                $playerTransfer->day                    = date('Ymd');
                                $playerTransfer->amount                 = $diff;
                                $playerTransfer->admin_id               = 0;
                                $playerTransfer->remark                 = config('language')[$language]['text72'];
                                $playerTransfer->before_balance                  = $playerAccount->balance;
                                $playerTransfer->balance                         = $playerAccount->balance;
                                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                                $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                                $playerTransfer->agent_balance                   = $playerAccount->agentbalance - $playerTransfer->amount;
                                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                                $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                                $playerAccount->agentfrozen                      = $playerTransfer->agent_frozen_balance;
                                $playerTransfer->save();
                                $playerAccount->save();

                                \DB::commit();
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$voucherNeedRechargeAmount.config('language')[$language]['text71'];
                            }
                        } else{
                            if($amount > $voucherNeedRechargeAmount*10000){
                                \DB::beginTransaction();
                                $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();
                                $diff                                   = $playerAccount->balance - $voucherNeedRechargeAmount*10000;

                                if(bcdiv($playerAccount->balance,10000,0) < $amount) {
                                    Lock::release($redisLock);
                                    return config('language')[$language]['error58'];
                                }

                                $playerTransfer                         = new PlayerTransfer();
                                $playerTransfer->prefix                 = $this->prefix;
                                $playerTransfer->carrier_id             = $this->carrier_id;
                                $playerTransfer->rid                    = $this->rid;
                                $playerTransfer->top_id                 = $this->top_id;
                                $playerTransfer->parent_id              = $this->parent_id;
                                $playerTransfer->player_id              = $this->player_id;
                                $playerTransfer->is_tester              = $this->is_tester;
                                $playerTransfer->user_name              = $this->user_name;
                                $playerTransfer->level                  = $this->level;
                                $playerTransfer->mode                   = 2;
                                $playerTransfer->type                   = 'gift_transfer_reduce';
                                $playerTransfer->type_name              = config('language')[$language]['text59'];
                                $playerTransfer->day_m                  = date('Ym');
                                $playerTransfer->day                    = date('Ymd');
                                $playerTransfer->amount                 = $diff*10000;
                                $playerTransfer->admin_id               = 0;
                                $playerTransfer->remark                 = config('language')[$language]['text72'];

                                $playerTransfer->before_balance                  = $playerAccount->balance;
                                $playerTransfer->balance                         = $playerAccount->balance- $playerTransfer->amount;
                                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                                $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                                $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                                $playerAccount->balance                          = $voucherNeedRechargeAmount*10000;

                                $playerTransfer->save();
                                $playerAccount->save();

                                \DB::commit();
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            }
                        }
                    } catch (\Exception $e) {
                        \DB::rollback();
                        Lock::release($redisLock);
                        Clog::recordabnormal('用户体验彩金提现异常'.$e->getMessage());
                        return $e->getMessage();
                    }
                }
            } 
        }

        //判断之前是否是佣金或分红
        $playerTransferExtends = PlayerTransfer::where('player_id',$this->player_id)->whereNotIn('type',['transfer_in_wallet','transfer_in_safe','reimbursement_gift'])->orderBy('id','desc')->limit(5)->get();

        if(count($playerTransferExtends)>0){
            foreach ($playerTransferExtends as $key => $value) {
                if(in_array($value->type,['commission_from_child','dividend_from_parent'])){
                    PlayerWithdrawFlowLimit::where('player_id',$this->player_id)->where('limit_amount',$value->amount)->update(['is_finished'=>1,'complete_limit_amount'=>$value->amount]);
                } else{
                    break;
                }
            } 
        }

        //判断流水是否完成
        $playerWithdrawFlowLimit = PlayerWithdrawFlowLimit::where('player_id',$this->player_id)->where('is_finished',0)->first();

        if($playerWithdrawFlowLimit) {
            return config('language')[$language]['error81'];
        }

        $carrierPlayerLevel = CarrierPlayerGrade::find($this->player_level_id);

        // 获取每日提款次数 以及额度
        $startTime = date('Y-m-d 00:00:00');
        $endTime   = date('Y-m-d 23:59:59');

        $playerWithdraw = PlayerWithdraw::select(\DB::raw('count(id) as ids'),\DB::raw('sum(amount) as amount'))->where(['carrier_id' => $this->carrier_id,'player_id' => $this->player_id,])->whereIn('status',[1,2,4,5,6])->whereBetween('created_at', [$startTime, $endTime])->whereIn('status',[1,2])->first();

        if ($playerWithdraw->ids >= $carrierPlayerLevel->withdrawcount ) {
            return config('language')[$language]['error82'];
        }

        if($withdrawalNeedSms){
            if(!isset($input['smscode']) || empty($input['smscode'])){
                return config('language')[$language]['error529'];
            }

            if(empty($this->mobile)){
                return config('language')[$language]['error530'];
            }

            $shortmobile = cache()->get('short_mobile_'.$this->mobile);
            if($shortmobile!=$input['smscode']){
                return config('language')[$language]['error531'];
            }
        }

        $currency           = CarrierCache::getCurrencyByPrefix($this->prefix);
        $currdigitalpay     = config('main')['digitalpay'][$currency];
        $currdigitalpaykeys = array_keys($currdigitalpay);
        $playerAccount      = PlayerAccount::where('player_id',$this->player_id)->first();


        //启用了保险箱
        $enableSafeBox      = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'enable_safe_box',$this->prefix);
        if($enableSafeBox){
            if(bcdiv($playerAccount->agentbalance,10000,0) < $amount) {
                return config('language')[$language]['error58'];
            }
        } else{
            if(bcdiv($playerAccount->balance,10000,0) < $amount) {
                return config('language')[$language]['error58'];
            }
        }

        if(in_array($playerDigitalAddress->type,$currdigitalpaykeys)){
            if ($amount < $minWithdraw || intval($amount) != $amount) {
                return config('language')[$language]['error77'];
            }
        } else {
            if ($amount < $minWithdrawalUsdt) {
                return config('language')[$language]['error77'];
            }
        }

        if($enableLimitOneWithdrawal){
            $existPlayerWithdraw = PlayerWithdraw::where('carrier_id',$this->carrier_id)->where('player_id',$this->player_id)->whereIn('status',[0,-1,4,5,6])->first();
            if($existPlayerWithdraw){
                return config('language')[$language]['error532'];
            }
        }

        $cacheKey              = "player_" .$this->player_id;
        $redisLock             = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            return config('language')[$language]['error20'];
        } else {
            try {
                \DB::beginTransaction();
                $playerAccount      = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();

                // 添加记录
                $playerWithdrawM                                 = new PlayerWithdraw();
                $playerWithdrawM->player_id                      = $this->player_id;
                $playerWithdrawM->user_name                      = $this->user_name;
                $playerWithdrawM->carrier_id                     = $this->carrier_id;
                $playerWithdrawM->rid                            = $this->rid;
                $playerWithdrawM->level                          = $this->level;
                $playerWithdrawM->is_hedging_account             = $this->is_hedging_account;
                $playerWithdrawM->prefix                         = $input['prefix'];
                $playerWithdrawM->pay_order_number               = 'TX'.date('YmdHis').mt_rand(1000,9999);  // 平台单号
                $playerWithdrawM->pay_order_channel_trade_number = ''; // 第三方平台单号
                $playerWithdrawM->carrier_pay_channel            = '';
                $playerWithdrawM->amount                         = bcmul($amount,10000,0);

                $withdrawBankcardRatefee                         = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'withdraw_ratefee',$this->prefix);

                if($withdrawBankcardRatefee>0){
                    $playerWithdrawM->withdraw_fee               = bcdiv($playerWithdrawM->amount*$withdrawBankcardRatefee,100);
                } else{
                    $playerWithdrawM->withdraw_fee               = 0;
                }

                $playerWithdrawM->real_amount                    = $playerWithdrawM->amount - $playerWithdrawM->withdraw_fee;

                $realAmount  =  bcdiv($playerWithdrawM->real_amount,10000,2);

                if(in_array($playerDigitalAddress->type,$currdigitalpaykeys)){
                    $playerWithdrawM->collection                     = $currdigitalpay[$playerDigitalAddress->type].'|'.$playerDigitalAddress->address.'|'.$realAmount;
                    $playerWithdrawM->type                           = $playerDigitalAddress->type;
                    $playerWithdrawM->currency                       = $currency;
                }else {
                    if($playerDigitalAddress->type==1){
                        $playerWithdrawM->collection                     = 'TRC20|'.$playerDigitalAddress->address.'|'.bcdiv($realAmount,$withdrawDigitalRate,2);
                        $playerWithdrawM->type                           =  1 ;
                        $playerWithdrawM->currency                       = 'USD';

                    } else if($playerDigitalAddress->type==2){
                        $playerWithdrawM->collection                     = 'ERC20|'.$playerDigitalAddress->address.'|'.bcdiv($realAmount,$withdrawDigitalRate,2);
                        $playerWithdrawM->type                           =  2 ;
                        $playerWithdrawM->currency                       = 'USD';
                    }
                }

                $playerWithdrawM->player_digital_address         = $playerDigitalAddress->address;
                $playerWithdrawM->review_one_user_id             = 0;
                $playerWithdrawM->review_one_time                = 0;
                $playerWithdrawM->review_two_user_id             = 0;
                $playerWithdrawM->review_two_time                = 0;
                $playerWithdrawM->status                         = 0;
                $playerWithdrawM->player_bank_id                 = '';
                $playerWithdrawM->remark                         = '';
                $playerWithdrawM->save();

                //帐变记录
                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->prefix;
                $playerTransfer->carrier_id                      = $this->carrier_id;
                $playerTransfer->rid                             = $this->rid;
                $playerTransfer->top_id                          = $this->top_id;
                $playerTransfer->parent_id                       = $this->parent_id;
                $playerTransfer->player_id                       = $this->player_id;
                $playerTransfer->is_tester                       = $this->is_tester;
                $playerTransfer->user_name                       = $this->user_name;
                $playerTransfer->level                           = $this->level;
                $playerTransfer->project_id                      = $playerWithdrawM->pay_order_number;
                $playerTransfer->mode                            = 3;
                $playerTransfer->day_m                           = date('Ym');
                $playerTransfer->day                             = date('Ymd');
                $playerTransfer->amount                          = 0;

                $playerTransfer->type                            = 'withdraw_apply';
                $playerTransfer->type_name                       = '申请提现';

                if($enableSafeBox){
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance - $playerWithdrawM->amount;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen  + $playerWithdrawM->amount;

                    $playerTransfer->save();

                    //帐变
                    $playerAccount->agentbalance                          = $playerTransfer->agent_balance;
                    $playerAccount->agentfrozen                           = $playerTransfer->agent_frozen_balance;
                    $playerAccount->save();
                } else{
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance - $playerWithdrawM->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen + $playerWithdrawM->amount;

                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                    $playerTransfer->save();

                    //帐变
                    $playerAccount->balance                          = $playerTransfer->balance;
                    $playerAccount->frozen                           = $playerTransfer->frozen_balance;
                    $playerAccount->save();
                }
                    
                //申请提现日志
                $playerOperate                                    = new PlayerOperate();
                $playerOperate->carrier_id                        = $this->carrier_id;
                $playerOperate->player_id                         = $this->player_id;
                $playerOperate->user_name                         = $this->user_name;
                $playerOperate->type                              = 1;
                $playerOperate->desc                              = '提现金额'.$amount;
                $playerOperate->ip                                = ip2long(real_ip());
                $playerOperate->save();

                \DB::commit();
                Lock::release($redisLock);

                return ['balance'=>bcdiv($playerAccount->balance,10000,2),'frozen'=>bcdiv($playerAccount->frozen,10000,2),'agentbalance'=>bcdiv($playerAccount->agentbalance,10000,2),'agentfrozen'=>bcdiv($playerAccount->agentfrozen,10000,2)];
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('用户申请提数字币异常'.$e->getMessage());
                return $e->getMessage();
            }
        }
    }

    public function alipayWithdrawApply($carrier)
    {
        $input                         = request()->all();
        $amount                        = $input['amount'] ?? 0;
        $player_alipay_id              = $input['player_alipay_id'] ?? '';
        $enableLimitOneWithdrawal      = CarrierCache::getCarrierConfigure($this->carrier_id, 'enable_limit_one_withdrawal');
        $minWithdraw                   = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'finance_min_withdraw',$this->prefix);
        $withdrawalNeedSms             = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'withdrawal_need_sms',$this->prefix);
        $enableSafeBox                 = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'enable_safe_box',$this->prefix);
        $materialIds                   = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'materialIds',$this->prefix);
        $agentSupportWithdrawAmount    = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'agent_support_withdraw_amount',$this->prefix);
        $enableVoucherRecharge         = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'enable_voucher_recharge',$this->prefix);
        $voucherNeedRechargeAmount     = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'voucher_need_recharge_amount',$this->prefix);
        $language                      = CarrierCache::getLanguageByPrefix($this->prefix);
        $currency                      = CarrierCache::getCurrencyByPrefix($this->prefix);

        $materialIdsArr               = explode(',',$materialIds);

        if(in_array($this->player_id,$materialIdsArr)){
            return config('language')[$language]['error534'];
        }

        if($this->is_tester == 1) {
            return config('language')[$language]['error138'];
        }

        if(!isset($input['password']) || empty($input['password'])) {
            return config('language')[$language]['error21'];
        }

        if(!\Hash::check($input['password'], $this->paypassword)) {
            return config('language')[$language]['error76'];
        }

        if($this->frozen_status==3){
            return config('language')[$language]['error196'];
        }

        if($withdrawalNeedSms){
            if(!isset($input['smscode']) || empty($input['smscode'])){
                \Log::info('对不起,手机验证码不能为空！');
                return config('language')[$language]['error529'];
            }

            if(empty($this->mobile)){
                \Log::info('对不起,手机号未绑定！');
                return config('language')[$language]['error530'];
            }

            $shortmobile = cache()->get('short_mobile_'.$this->mobile);
            if($shortmobile!=$input['smscode']){
                \Log::info('对不起,手机验证码不正确！');
                return config('language')[$language]['error531'];
            }
        }

        if (!is_numeric($amount) || $amount < $minWithdraw || intval($amount) != $amount) {
            return config('language')[$language]['error77'];
        }

        $playerAlipay     = PlayerAlipay::where('id',$player_alipay_id)->where('player_id',$this->player_id)->first();

        if (!$playerAlipay) {
            return config('language')[$language]['error518'];
        }

        //判断是否启用体验券充值才能提现
        if($enableVoucherRecharge==1){
            $playerTransferExtends = PlayerTransfer::where('player_id',$this->player_id)->whereNotIn('type',['transfer_in_wallet','transfer_in_safe','casino_transfer_in','casino_transfer_out_error','casino_transfer_out','code_gift'])->first();
            //如果没有充值直接扣除多出来的彩金
            if(!$playerTransferExtends){
                //扣除多余出来的金额
                $cacheKey              = "player_" .$this->player_id;
                $redisLock             = Lock::addLock($cacheKey,60);

                if (!$redisLock) {
                    return config('language')[$language]['error20'];
                } else {
                    try {
                        if($enableSafeBox){
                            if($amount > $voucherNeedRechargeAmount*10000){

                                \DB::beginTransaction();
                                $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();
                                $diff                                   = $playerAccount->agentbalance - $voucherNeedRechargeAmount*10000;

                                if(bcdiv($playerAccount->agentbalance,10000,0) < $amount) {
                                    Lock::release($redisLock);
                                    return config('language')[$language]['error58'];
                                }

                                //扣除多出来的余额
                                $playerTransfer                         = new PlayerTransfer();
                                $playerTransfer->prefix                 = $this->prefix;
                                $playerTransfer->carrier_id             = $this->carrier_id;
                                $playerTransfer->rid                    = $this->rid;
                                $playerTransfer->top_id                 = $this->top_id;
                                $playerTransfer->parent_id              = $this->parent_id;
                                $playerTransfer->player_id              = $this->player_id;
                                $playerTransfer->is_tester              = $this->is_tester;
                                $playerTransfer->user_name              = $this->user_name;
                                $playerTransfer->level                  = $this->level;
                                $playerTransfer->mode                   = 2;
                                $playerTransfer->type                   = 'gift_transfer_reduce';
                                $playerTransfer->type_name              = config('language')[$language]['text59'];
                                $playerTransfer->day_m                  = date('Ym');
                                $playerTransfer->day                    = date('Ymd');
                                $playerTransfer->amount                 = $diff;
                                $playerTransfer->admin_id               = 0;
                                $playerTransfer->remark                 = config('language')[$language]['text72'];
                                $playerTransfer->before_balance                  = $playerAccount->balance;
                                $playerTransfer->balance                         = $playerAccount->balance;
                                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                                $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                                $playerTransfer->agent_balance                   = $playerAccount->agentbalance - $playerTransfer->amount;
                                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                                $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                                $playerAccount->agentfrozen                      = $playerTransfer->agent_frozen_balance;
                                $playerTransfer->save();
                                $playerAccount->save();

                                \DB::commit();
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$voucherNeedRechargeAmount.config('language')[$language]['text71'];
                            }
                        } else{
                            if($amount > $voucherNeedRechargeAmount*10000){
                                \DB::beginTransaction();
                                $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();
                                $diff                                   = $playerAccount->balance - $voucherNeedRechargeAmount*10000;

                                if(bcdiv($playerAccount->balance,10000,0) < $amount) {
                                    Lock::release($redisLock);
                                    return config('language')[$language]['error58'];
                                }

                                $playerTransfer                         = new PlayerTransfer();
                                $playerTransfer->prefix                 = $this->prefix;
                                $playerTransfer->carrier_id             = $this->carrier_id;
                                $playerTransfer->rid                    = $this->rid;
                                $playerTransfer->top_id                 = $this->top_id;
                                $playerTransfer->parent_id              = $this->parent_id;
                                $playerTransfer->player_id              = $this->player_id;
                                $playerTransfer->is_tester              = $this->is_tester;
                                $playerTransfer->user_name              = $this->user_name;
                                $playerTransfer->level                  = $this->level;
                                $playerTransfer->mode                   = 2;
                                $playerTransfer->type                   = 'gift_transfer_reduce';
                                $playerTransfer->type_name              = config('language')[$language]['text59'];
                                $playerTransfer->day_m                  = date('Ym');
                                $playerTransfer->day                    = date('Ymd');
                                $playerTransfer->amount                 = $diff*10000;
                                $playerTransfer->admin_id               = 0;
                                $playerTransfer->remark                 = config('language')[$language]['text72'];

                                $playerTransfer->before_balance                  = $playerAccount->balance;
                                $playerTransfer->balance                         = $playerAccount->balance- $playerTransfer->amount;
                                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                                $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                                $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                                $playerAccount->balance                          = $voucherNeedRechargeAmount*10000;

                                $playerTransfer->save();
                                $playerAccount->save();

                                \DB::commit();
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            }
                        }
                    } catch (\Exception $e) {
                        \DB::rollback();
                        Lock::release($redisLock);
                        Clog::recordabnormal('用户体验彩金提现异常'.$e->getMessage());
                        return $e->getMessage();
                    }
                }
            } 
        }

        //判断之前是否是佣金或分红
        $playerTransferExtends = PlayerTransfer::where('player_id',$this->player_id)->whereNotIn('type',['transfer_in_wallet','transfer_in_safe','reimbursement_gift'])->orderBy('id','desc')->limit(5)->get();

        if(count($playerTransferExtends)>0){
            foreach ($playerTransferExtends as $key => $value) {
                if(in_array($value->type,['commission_from_child','dividend_from_parent'])){
                    PlayerWithdrawFlowLimit::where('player_id',$this->player_id)->where('limit_amount',$value->amount)->update(['is_finished'=>1,'complete_limit_amount'=>$value->amount]);
                } else{
                    break;
                }
            } 
        }

        //判断流水是否完成
        $playerWithdrawFlowLimit = PlayerWithdrawFlowLimit::where('player_id',$this->player_id)->where('is_finished',0)->first();

        if($playerWithdrawFlowLimit) {
            return config('language')[$language]['error81'];
        }

        $carrierPlayerLevel = CarrierPlayerGrade::find($this->player_level_id);

        // 获取每日提款次数 以及额度
        $startTime = date('Y-m-d 00:00:00');
        $endTime   = date('Y-m-d 23:59:59');

        $playerWithdraw = PlayerWithdraw::select(\DB::raw('count(id) as ids'),\DB::raw('sum(amount) as amount'))->where(['carrier_id' => $this->carrier_id,'player_id' => $this->player_id,])->whereBetween('created_at', [$startTime, $endTime])->whereIn('status',[1,2])->first();

        if ($playerWithdraw->ids >= $carrierPlayerLevel->withdrawcount ) {
            return config('language')[$language]['error82'];
        }

        if($enableLimitOneWithdrawal){
            $existPlayerWithdraw = PlayerWithdraw::where('carrier_id',$this->carrier_id)->where('player_id',$this->player_id)->whereIn('status',[0,-1,4,5,6])->first();
            if($existPlayerWithdraw){
                return config('language')[$language]['error532'];
            }
        }

        //是否代理扶持且未充值
        $existAgentSupport = PlayerTransfer::where('player_id',$this->player_id)->where('type','agent_support')->orderBy('id','desc')->first();
        if($existAgentSupport){
            $existAmountIncrease = PlayerTransfer::where('player_id',$this->player_id)->whereIn('type',['recharge','dividend_from_parent','commission_from_child'])->orderBy('id','desc')->first();
            //提现金额少于代理扶持最低出款金额
            if($agentSupportWithdrawAmount > $amount){
                return config('language')[$language]['error535'].$agentSupportWithdrawAmount;
            }

            //扣除多余出来的金额
            if(!$existAmountIncrease && $amount > $agentSupportWithdrawAmount){
                $cacheKey              = "player_" .$this->player_id;
                $redisLock             = Lock::addLock($cacheKey,60);
                $diff                  = $amount - $agentSupportWithdrawAmount;

                if (!$redisLock) {
                    return config('language')[$language]['error20'];
                } else {
                    try {
                        \DB::beginTransaction();

                        $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();
                        if($enableSafeBox){
                            if($playerAccount->agentbalance < $diff*10000){
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            } elseif($playerAccount->agentbalance - $diff*10000 < $agentSupportWithdrawAmount*10000){
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            }
                        } else{
                            if($playerAccount->balance < $diff*10000){
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            }  elseif($playerAccount->balance - $diff*10000 < $agentSupportWithdrawAmount*10000){
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            }
                        }

                        $playerTransfer                         = new PlayerTransfer();
                        $playerTransfer->prefix                 = $this->prefix;
                        $playerTransfer->carrier_id             = $this->carrier_id;
                        $playerTransfer->rid                    = $this->rid;
                        $playerTransfer->top_id                 = $this->top_id;
                        $playerTransfer->parent_id              = $this->parent_id;
                        $playerTransfer->player_id              = $this->player_id;
                        $playerTransfer->is_tester              = $this->is_tester;
                        $playerTransfer->user_name              = $this->user_name;
                        $playerTransfer->level                  = $this->level;
                        $playerTransfer->mode                   = 2;
                        $playerTransfer->type                   = 'gift_transfer_reduce';
                        $playerTransfer->type_name              = config('language')[$language]['text59'];
                        $playerTransfer->day_m                  = date('Ym');
                        $playerTransfer->day                    = date('Ymd');
                        $playerTransfer->amount                 = $diff*10000;
                        $playerTransfer->admin_id               = 0;
                        $playerTransfer->remark                 = config('language')[$language]['text70'];

                        if($enableSafeBox){
                            $playerTransfer->before_balance                  = $playerAccount->balance;
                            $playerTransfer->balance                         = $playerAccount->balance;
                            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                            $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                            $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                            $playerTransfer->agent_balance                   = $playerAccount->agentbalance - $playerTransfer->amount;
                            $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                            $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                            $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                            $playerAccount->agentfrozen                      = $playerTransfer->agent_frozen_balance;

                        } else{
                            $playerTransfer->before_balance                  = $playerAccount->balance;
                            $playerTransfer->balance                         = $playerAccount->balance- $playerTransfer->amount;
                            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                            $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                            $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                            $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                            $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                            $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                            $playerAccount->balance                          = $playerTransfer->balance;
                            $playerAccount->frozen                           = $playerTransfer->frozen_balance;
                        }

                        $playerTransfer->save();
                        $playerAccount->save();

                        \DB::commit();
                        Lock::release($redisLock);

                        return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                    } catch (\Exception $e) {
                        \DB::rollback();
                        Lock::release($redisLock);
                        Clog::recordabnormal('用户代理扶持提现异常'.$e->getMessage());
                        return $e->getMessage();
                    }
                }
            }

            if(!$existAmountIncrease){
                return config('language')[$language]['error533'].$agentSupportWithdrawAmount.config('language')[$language]['error533'];
            }
        }

        $cacheKey              = "player_" .$this->player_id;
        $redisLock             = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            return config('language')[$language]['error20'];
        } else {
            try {
                \DB::beginTransaction();

                $playerAccount  = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();

                if($enableSafeBox){
                    if(bcdiv($playerAccount->agentbalance,10000,0) < $amount) {
                        Lock::release($redisLock);
                        return config('language')[$language]['error58'];
                    }

                    if($playerAccount->agentfrozen<0){
                        Lock::release($redisLock);
                        return config('language')[$language]['error232'];
                    }
                } else{
                    if(bcdiv($playerAccount->balance,10000,0) < $amount) {
                        Lock::release($redisLock);
                        return config('language')[$language]['error58'];
                    }

                    if($playerAccount->frozen<0){
                        Lock::release($redisLock);
                        return config('language')[$language]['error232'];
                    }
                }

                // 添加记录
                $playerWithdrawM                                 = new PlayerWithdraw();
                $playerWithdrawM->player_id                      = $this->player_id;
                $playerWithdrawM->user_name                      = $this->user_name;
                $playerWithdrawM->carrier_id                     = $this->carrier_id;
                $playerWithdrawM->rid                            = $this->rid;
                $playerWithdrawM->level                          = $this->level;
                $playerWithdrawM->is_hedging_account             = $this->is_hedging_account;
                $playerWithdrawM->prefix                         = $this->prefix;
                $playerWithdrawM->pay_order_number               = 'TX'.date('YmdHis').mt_rand(1000,9999);  // 平台单号
                $playerWithdrawM->pay_order_channel_trade_number = ''; // 第三方平台单号
                $playerWithdrawM->carrier_pay_channel            = '';
                $playerWithdrawM->amount                         = bcmul($amount,10000,0);

                $withdrawAlipayRatefee                         = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'alipay_withdraw_ratefee',$this->prefix);
                if($withdrawAlipayRatefee>0){
                    $playerWithdrawM->withdraw_fee               = bcdiv($playerWithdrawM->amount*$withdrawAlipayRatefee,100);
                }

                $playerWithdrawM->real_amount                    = $playerWithdrawM->amount - $playerWithdrawM->withdraw_fee;

                $realAmount  =  bcdiv($playerWithdrawM->real_amount,10000,2);

                $playerWithdrawM->collection                     = '支付宝|'.$playerAlipay->account.'|'.$playerAlipay->real_name.'|'.$realAmount;
                $playerWithdrawM->review_one_user_id             = 0;
                $playerWithdrawM->review_one_time                = 0;
                $playerWithdrawM->review_two_user_id             = 0;
                $playerWithdrawM->review_two_time                = 0;
                $playerWithdrawM->status                         = 0;
                $playerWithdrawM->currency                       = $currency;
                $playerWithdrawM->player_bank_id                 = '';
                $playerWithdrawM->player_alipay_id               = $player_alipay_id;
                $playerWithdrawM->remark                         = '';
                $playerWithdrawM->save();

                //帐变记录
                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->prefix;
                $playerTransfer->carrier_id                      = $this->carrier_id;
                $playerTransfer->rid                             = $this->rid;
                $playerTransfer->top_id                          = $this->top_id;
                $playerTransfer->parent_id                       = $this->parent_id;
                $playerTransfer->player_id                       = $this->player_id;
                $playerTransfer->is_tester                       = $this->is_tester;
                $playerTransfer->user_name                       = $this->user_name;
                $playerTransfer->level                           = $this->level;
                $playerTransfer->project_id                      = $playerWithdrawM->pay_order_number;
                $playerTransfer->mode                            = 3;
                
                $playerTransfer->day_m                           = date('Ym');
                $playerTransfer->day                             = date('Ymd');
                $playerTransfer->amount                          = 0;

                $playerTransfer->type                            = 'withdraw_apply';
                $playerTransfer->type_name                       = config('language')[$language]['text21'];

                if($enableSafeBox){
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance - $playerWithdrawM->amount;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen  + $playerWithdrawM->amount;

                    $playerAccount->agentbalance                  = $playerTransfer->agent_balance;
                    $playerAccount->agentfrozen                   = $playerTransfer->agent_frozen_balance;

                } else{
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance- $playerWithdrawM->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen+ $playerWithdrawM->amount;

                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;
                    $playerAccount->balance                       = $playerTransfer->balance;
                    $playerAccount->frozen                        = $playerTransfer->frozen_balance;
                }

                $playerTransfer->save();

                //帐变
                $playerAccount->save();

                //申请提现日志
                $playerOperate                                    = new PlayerOperate();
                $playerOperate->carrier_id                        = $this->carrier_id;
                $playerOperate->player_id                         = $this->player_id;
                $playerOperate->user_name                         = $this->user_name;
                $playerOperate->type                              = 1;
                $playerOperate->desc                              = '提现金额'.$amount;
                $playerOperate->ip                                = ip2long(real_ip());
                $playerOperate->save();

                \DB::commit();
                Lock::release($redisLock);
                return ['balance'=>bcdiv($playerAccount->balance,10000,2),'frozen'=>bcdiv($playerAccount->frozen,10000,2),'agentbalance'=>bcdiv($playerAccount->agentbalance,10000,2),'agentfrozen'=>bcdiv($playerAccount->agentfrozen,10000,2)];
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('用户申请提现异常'.$e->getMessage());
                return $e->getMessage();
            }
        }
    }

    public function withdrawApply($carrier)
    {
        $input                         = request()->all();
        $amount                        = $input['amount'] ?? 0;
        $player_bank_id                = $input['player_bank_id'] ?? '';
        $enableLimitOneWithdrawal      = CarrierCache::getCarrierConfigure($this->carrier_id, 'enable_limit_one_withdrawal');
        $minWithdraw                   = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'finance_min_withdraw',$this->prefix);
        $withdrawalNeedSms             = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'withdrawal_need_sms',$this->prefix);
        $enableSafeBox                 = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'enable_safe_box',$this->prefix);
        $materialIds                   = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'materialIds',$this->prefix);
        $agentSupportWithdrawAmount    = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'agent_support_withdraw_amount',$this->prefix);
        $enableVoucherRecharge         = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'enable_voucher_recharge',$this->prefix);
        $voucherNeedRechargeAmount     = CarrierCache::getCarrierMultipleConfigure($this->carrier_id, 'voucher_need_recharge_amount',$this->prefix);
        $language                      = CarrierCache::getLanguageByPrefix($this->prefix);
        $currency                      = CarrierCache::getCurrencyByPrefix($this->prefix);

        $materialIdsArr               = explode(',',$materialIds);

        if(in_array($this->player_id,$materialIdsArr)){
            return config('language')[$language]['error534'];
        }

        if($this->is_tester == 1) {
            return config('language')[$language]['error138'];
        }

        if(!isset($input['password']) || empty($input['password'])) {
            return config('language')[$language]['error21'];
        }

        if($this->frozen_status==3){
            return config('language')[$language]['error196'];
        }

        if(!\Hash::check($input['password'], $this->paypassword)) {
            return config('language')[$language]['error76'];
        }


        if($withdrawalNeedSms){
            if(!isset($input['smscode']) || empty($input['smscode'])){
                \Log::info('对不起,手机验证码不能为空！');
                return config('language')[$language]['error529'];
            }

            if(empty($this->mobile)){
                \Log::info('对不起,手机号未绑定！');
                return config('language')[$language]['error530'];
            }

            $shortmobile = cache()->get('short_mobile_'.$this->mobile);
            if($shortmobile!=$input['smscode']){
                \Log::info('对不起,手机验证码不正确！');
                return config('language')[$language]['error531'];
            }
        }

        if (!is_numeric($amount) || $amount < $minWithdraw || intval($amount) != $amount) {
            return config('language')[$language]['error77'];
        }

        $playerBankCard     = PlayerBankCard::where('id',$player_bank_id)->where('player_id',$this->player_id)->first();

        if (!$playerBankCard) {
            return config('language')[$language]['error78'];
        }

        //判断是否启用体验券充值才能提现
        if($enableVoucherRecharge==1){
            $playerTransferExtends = PlayerTransfer::where('player_id',$this->player_id)->whereNotIn('type',['transfer_in_wallet','transfer_in_safe','casino_transfer_in','casino_transfer_out_error','casino_transfer_out','code_gift'])->first();
            //如果没有充值直接扣除多出来的彩金
            if(!$playerTransferExtends){
                //扣除多余出来的金额
                $cacheKey              = "player_" .$this->player_id;
                $redisLock             = Lock::addLock($cacheKey,60);

                if (!$redisLock) {
                    return config('language')[$language]['error20'];
                } else {
                    try {
                        if($enableSafeBox){
                            if($amount > $voucherNeedRechargeAmount*10000){

                                \DB::beginTransaction();
                                $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();
                                $diff                                   = $playerAccount->agentbalance - $voucherNeedRechargeAmount*10000;

                                if(bcdiv($playerAccount->agentbalance,10000,0) < $amount) {
                                    Lock::release($redisLock);
                                    return config('language')[$language]['error58'];
                                }

                                //扣除多出来的余额
                                $playerTransfer                         = new PlayerTransfer();
                                $playerTransfer->prefix                 = $this->prefix;
                                $playerTransfer->carrier_id             = $this->carrier_id;
                                $playerTransfer->rid                    = $this->rid;
                                $playerTransfer->top_id                 = $this->top_id;
                                $playerTransfer->parent_id              = $this->parent_id;
                                $playerTransfer->player_id              = $this->player_id;
                                $playerTransfer->is_tester              = $this->is_tester;
                                $playerTransfer->user_name              = $this->user_name;
                                $playerTransfer->level                  = $this->level;
                                $playerTransfer->mode                   = 2;
                                $playerTransfer->type                   = 'gift_transfer_reduce';
                                $playerTransfer->type_name              = config('language')[$language]['text59'];
                                $playerTransfer->day_m                  = date('Ym');
                                $playerTransfer->day                    = date('Ymd');
                                $playerTransfer->amount                 = $diff;
                                $playerTransfer->admin_id               = 0;
                                $playerTransfer->remark                 = config('language')[$language]['text72'];
                                $playerTransfer->before_balance                  = $playerAccount->balance;
                                $playerTransfer->balance                         = $playerAccount->balance;
                                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                                $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                                $playerTransfer->agent_balance                   = $playerAccount->agentbalance - $playerTransfer->amount;
                                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                                $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                                $playerAccount->agentfrozen                      = $playerTransfer->agent_frozen_balance;
                                $playerTransfer->save();
                                $playerAccount->save();

                                \DB::commit();
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$voucherNeedRechargeAmount.config('language')[$language]['text71'];
                            }
                        } else{
                            if($amount > $voucherNeedRechargeAmount*10000){
                                \DB::beginTransaction();
                                $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();
                                $diff                                   = $playerAccount->balance - $voucherNeedRechargeAmount*10000;

                                if(bcdiv($playerAccount->balance,10000,0) < $amount) {
                                    Lock::release($redisLock);
                                    return config('language')[$language]['error58'];
                                }

                                $playerTransfer                         = new PlayerTransfer();
                                $playerTransfer->prefix                 = $this->prefix;
                                $playerTransfer->carrier_id             = $this->carrier_id;
                                $playerTransfer->rid                    = $this->rid;
                                $playerTransfer->top_id                 = $this->top_id;
                                $playerTransfer->parent_id              = $this->parent_id;
                                $playerTransfer->player_id              = $this->player_id;
                                $playerTransfer->is_tester              = $this->is_tester;
                                $playerTransfer->user_name              = $this->user_name;
                                $playerTransfer->level                  = $this->level;
                                $playerTransfer->mode                   = 2;
                                $playerTransfer->type                   = 'gift_transfer_reduce';
                                $playerTransfer->type_name              = config('language')[$language]['text59'];
                                $playerTransfer->day_m                  = date('Ym');
                                $playerTransfer->day                    = date('Ymd');
                                $playerTransfer->amount                 = $diff*10000;
                                $playerTransfer->admin_id               = 0;
                                $playerTransfer->remark                 = config('language')[$language]['text72'];

                                $playerTransfer->before_balance                  = $playerAccount->balance;
                                $playerTransfer->balance                         = $playerAccount->balance- $playerTransfer->amount;
                                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                                $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                                $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                                $playerAccount->balance                          = $voucherNeedRechargeAmount*10000;

                                $playerTransfer->save();
                                $playerAccount->save();

                                \DB::commit();
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            }
                        }
                    } catch (\Exception $e) {
                        \DB::rollback();
                        Lock::release($redisLock);
                        Clog::recordabnormal('用户体验彩金提现异常'.$e->getMessage());
                        return $e->getMessage();
                    }
                }
            } 
        }

        //判断之前是否是佣金或分红
        $playerTransferExtends = PlayerTransfer::where('player_id',$this->player_id)->whereNotIn('type',['transfer_in_wallet','transfer_in_safe','reimbursement_gift'])->orderBy('id','desc')->limit(5)->get();

        if(count($playerTransferExtends)>0){
            foreach ($playerTransferExtends as $key => $value) {
                if(in_array($value->type,['commission_from_child','dividend_from_parent'])){
                    PlayerWithdrawFlowLimit::where('player_id',$this->player_id)->where('limit_amount',$value->amount)->update(['is_finished'=>1,'complete_limit_amount'=>$value->amount]);
                } else{
                    break;
                }
            } 
        }

        //判断流水是否完成
        $playerWithdrawFlowLimit = PlayerWithdrawFlowLimit::where('player_id',$this->player_id)->where('is_finished',0)->first();

        if($playerWithdrawFlowLimit) {
            return config('language')[$language]['error81'];
        }

        $carrierPlayerLevel = CarrierPlayerGrade::find($this->player_level_id);

        // 获取每日提款次数 以及额度
        $startTime = date('Y-m-d 00:00:00');
        $endTime   = date('Y-m-d 23:59:59');

        $playerWithdraw = PlayerWithdraw::select(\DB::raw('count(id) as ids'),\DB::raw('sum(amount) as amount'))->where(['carrier_id' => $this->carrier_id,'player_id' => $this->player_id,])->whereBetween('created_at', [$startTime, $endTime])->whereIn('status',[1,2])->first();

        if ($playerWithdraw->ids >= $carrierPlayerLevel->withdrawcount ) {
            return config('language')[$language]['error82'];
        }

        if($enableLimitOneWithdrawal){
            $existPlayerWithdraw = PlayerWithdraw::where('carrier_id',$this->carrier_id)->where('player_id',$this->player_id)->whereIn('status',[0,-1,4,5,6])->first();
            if($existPlayerWithdraw){
                return config('language')[$language]['error532'];
            }
        }

        //是否代理扶持且未充值
        $existAgentSupport = PlayerTransfer::where('player_id',$this->player_id)->where('type','agent_support')->orderBy('id','desc')->first();
        if($existAgentSupport){
            $existAmountIncrease = PlayerTransfer::where('player_id',$this->player_id)->whereIn('type',['recharge','dividend_from_parent','commission_from_child'])->orderBy('id','desc')->first();
            //提现金额少于代理扶持最低出款金额
            if($agentSupportWithdrawAmount > $amount){
                return config('language')[$language]['error535'].$agentSupportWithdrawAmount;
            }

            //扣除多余出来的金额
            if(!$existAmountIncrease && $amount > $agentSupportWithdrawAmount){
                $cacheKey              = "player_" .$this->player_id;
                $redisLock             = Lock::addLock($cacheKey,60);
                $diff                  = $amount - $agentSupportWithdrawAmount;

                if (!$redisLock) {
                    return config('language')[$language]['error20'];
                } else {
                    try {
                        \DB::beginTransaction();

                        $playerAccount                          = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();
                        if($enableSafeBox){
                            if($playerAccount->agentbalance < $diff*10000){
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            } elseif($playerAccount->agentbalance - $diff*10000 < $agentSupportWithdrawAmount*10000){
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            }
                        } else{
                            if($playerAccount->balance < $diff*10000){
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            } elseif($playerAccount->balance - $diff*10000 < $agentSupportWithdrawAmount*10000){
                                Lock::release($redisLock);
                                return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                            }
                        }

                        $playerTransfer                         = new PlayerTransfer();
                        $playerTransfer->prefix                 = $this->prefix;
                        $playerTransfer->carrier_id             = $this->carrier_id;
                        $playerTransfer->rid                    = $this->rid;
                        $playerTransfer->top_id                 = $this->top_id;
                        $playerTransfer->parent_id              = $this->parent_id;
                        $playerTransfer->player_id              = $this->player_id;
                        $playerTransfer->is_tester              = $this->is_tester;
                        $playerTransfer->user_name              = $this->user_name;
                        $playerTransfer->level                  = $this->level;
                        $playerTransfer->mode                   = 2;
                        $playerTransfer->type                   = 'gift_transfer_reduce';
                        $playerTransfer->type_name              = config('language')[$language]['text59'];
                        $playerTransfer->day_m                  = date('Ym');
                        $playerTransfer->day                    = date('Ymd');
                        $playerTransfer->amount                 = $diff*10000;
                        $playerTransfer->admin_id               = 0;
                        $playerTransfer->remark                 = config('language')[$language]['text70'];

                        if($enableSafeBox){
                            $playerTransfer->before_balance                  = $playerAccount->balance;
                            $playerTransfer->balance                         = $playerAccount->balance;
                            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                            $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                            $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                            $playerTransfer->agent_balance                   = $playerAccount->agentbalance - $playerTransfer->amount;
                            $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                            $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                            $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                            $playerAccount->agentfrozen                      = $playerTransfer->agent_frozen_balance;

                        } else{
                            $playerTransfer->before_balance                  = $playerAccount->balance;
                            $playerTransfer->balance                         = $playerAccount->balance- $playerTransfer->amount;
                            $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                            $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                            $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                            $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                            $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                            $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                            $playerAccount->balance                          = $playerTransfer->balance;
                            $playerAccount->frozen                           = $playerTransfer->frozen_balance;
                        }

                        $playerTransfer->save();
                        $playerAccount->save();

                        \DB::commit();
                        Lock::release($redisLock);

                        return config('language')[$language]['error537'].$agentSupportWithdrawAmount.config('language')[$language]['text71'];
                    } catch (\Exception $e) {
                        \DB::rollback();
                        Lock::release($redisLock);
                        Clog::recordabnormal('用户代理扶持提现异常'.$e->getMessage());
                        return $e->getMessage();
                    }
                }
            }

            if(!$existAmountIncrease){
                return config('language')[$language]['error533'].$agentSupportWithdrawAmount.config('language')[$language]['error533'];
            }
        }

        $cacheKey              = "player_" .$this->player_id;
        $redisLock             = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            return config('language')[$language]['error20'];
        } else {
            try {
                \DB::beginTransaction();

                $playerAccount  = PlayerAccount::where('player_id',$this->player_id)->lockForUpdate()->first();

                if($enableSafeBox){
                    if(bcdiv($playerAccount->agentbalance,10000,0) < $amount) {
                        Lock::release($redisLock);
                        return config('language')[$language]['error58'];
                    }

                    if($playerAccount->agentfrozen<0){
                        Lock::release($redisLock);
                        return config('language')[$language]['error232'];
                    }
                } else{
                    if(bcdiv($playerAccount->balance,10000,0) < $amount) {
                        Lock::release($redisLock);
                        return config('language')[$language]['error58'];
                    }

                    if($playerAccount->frozen<0){
                        Lock::release($redisLock);
                        return config('language')[$language]['error232'];
                    }
                }
                
                $playerBankCard                                  = PlayerBankCard::select('inf_player_bank_cards.*','inf_carrier_bank_type.bank_name')->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')->where('inf_player_bank_cards.id',$player_bank_id)->first();
                // 添加记录
                $playerWithdrawM                                 = new PlayerWithdraw();
                $playerWithdrawM->player_id                      = $this->player_id;
                $playerWithdrawM->user_name                      = $this->user_name;
                $playerWithdrawM->carrier_id                     = $this->carrier_id;
                $playerWithdrawM->rid                            = $this->rid;
                $playerWithdrawM->level                          = $this->level;
                $playerWithdrawM->is_hedging_account             = $this->is_hedging_account;
                $playerWithdrawM->prefix                         = $input['prefix'];
                $playerWithdrawM->pay_order_number               = 'TX'.date('YmdHis').mt_rand(1000,9999);  // 平台单号
                $playerWithdrawM->pay_order_channel_trade_number = ''; // 第三方平台单号
                $playerWithdrawM->carrier_pay_channel            = '';
                $playerWithdrawM->amount                         = bcmul($amount,10000,0);

                $withdrawBankcardRatefee                         = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'withdraw_ratefee',$this->prefix);
                if($withdrawBankcardRatefee>0){
                    $playerWithdrawM->withdraw_fee               = bcdiv($playerWithdrawM->amount*$withdrawBankcardRatefee,100);
                }

                $playerWithdrawM->real_amount                    = $playerWithdrawM->amount - $playerWithdrawM->withdraw_fee;

                $realAmount  =  bcdiv($playerWithdrawM->real_amount,10000,2);

                $playerWithdrawM->collection                     = $playerBankCard->bank_name.'|'.$playerBankCard->card_account.'|'.$playerBankCard->card_owner_name.'|'.$realAmount;
                $playerWithdrawM->review_one_user_id             = 0;
                $playerWithdrawM->review_one_time                = 0;
                $playerWithdrawM->review_two_user_id             = 0;
                $playerWithdrawM->review_two_time                = 0;
                $playerWithdrawM->status                         = 0;
                $playerWithdrawM->currency                       = $currency;
                $playerWithdrawM->player_bank_id                 = $player_bank_id;
                $playerWithdrawM->remark                         = '';
                $playerWithdrawM->save();

                //帐变记录
                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->prefix;
                $playerTransfer->carrier_id                      = $this->carrier_id;
                $playerTransfer->rid                             = $this->rid;
                $playerTransfer->top_id                          = $this->top_id;
                $playerTransfer->parent_id                       = $this->parent_id;
                $playerTransfer->player_id                       = $this->player_id;
                $playerTransfer->is_tester                       = $this->is_tester;
                $playerTransfer->user_name                       = $this->user_name;
                $playerTransfer->level                           = $this->level;
                $playerTransfer->project_id                      = $playerWithdrawM->pay_order_number;
                $playerTransfer->mode                            = 3;
                
                $playerTransfer->day_m                           = date('Ym');
                $playerTransfer->day                             = date('Ymd');
                $playerTransfer->amount                          = 0;

                $playerTransfer->type                            = 'withdraw_apply';
                $playerTransfer->type_name                       = '申请提现';

                if($enableSafeBox){
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance - $playerWithdrawM->amount;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen  + $playerWithdrawM->amount;

                    $playerAccount->agentbalance                  = $playerTransfer->agent_balance;
                    $playerAccount->agentfrozen                   = $playerTransfer->agent_frozen_balance;

                } else{
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance- $playerWithdrawM->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen+ $playerWithdrawM->amount;

                    $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;
                    $playerAccount->balance                       = $playerTransfer->balance;
                    $playerAccount->frozen                        = $playerTransfer->frozen_balance;
                }

                $playerTransfer->save();

                //帐变
                $playerAccount->save();

                //申请提现日志
                $playerOperate                                    = new PlayerOperate();
                $playerOperate->carrier_id                        = $this->carrier_id;
                $playerOperate->player_id                         = $this->player_id;
                $playerOperate->user_name                         = $this->user_name;
                $playerOperate->type                              = 1;
                $playerOperate->desc                              = '提现金额'.$amount;
                $playerOperate->ip                                = ip2long(real_ip());
                $playerOperate->save();

                \DB::commit();
                Lock::release($redisLock);
                return ['balance'=>bcdiv($playerAccount->balance,10000,2),'frozen'=>bcdiv($playerAccount->frozen,10000,2),'agentbalance'=>bcdiv($playerAccount->agentbalance,10000,2),'agentfrozen'=>bcdiv($playerAccount->agentfrozen,10000,2)];
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('用户申请提现异常'.$e->getMessage());
                return $e->getMessage();
            }
        }
    }

    public function depositPayList()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;
        $query           = PlayerDepositPayLog::orderBy('id','desc')->where('player_id',$this->player_id);

        if(isset($input['startTime']) && !empty(trim($input['startTime'])) && strtotime($input['startTime'])) {
            $query->where('created_at','>=',$input['startTime']);
        } else {
            $query->where('created_at','>=',date('Y-m-01 00:00:00', strtotime(date("Y-m-d"))));
        }

        if(isset($input['endTime']) && !empty(trim($input['endTime'])) && strtotime($input['endTime'])) {
            $query->where('created_at','<=',date('Y-m-d 23:59:59',strtotime($input['endTime'])));
        }

        if(isset($input['front_status']) && in_array($input['front_status'], [0,1,-1])) {
            if($input['front_status']==-1) {
                $query->whereIn('status',[-1,-2]);
            } else if($input['front_status']==1) {
                $query->where('status',1);
            } else if($input['front_status']==0){
                $query->whereIn('status',[0,2]);
            }
        }

        if(isset($input['status']) && trim($input['status']) != '') {
            if($input['status']==1){
                $query->whereIn('status',[1,2]);
            } else {
                $query->where('status',$input['status']);
            }
        }

        $totalAmount    = $query->sum('amount');
        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        return ['item' => $items, 'total' => $total ,'totalAmout'=>$totalAmount,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function withdrawList()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $fakeWithdrawLimit     = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'fake_withdraw_limit',$this->prefix);
        $fakeWithdrawPlayerIds = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'fake_withdraw_player_ids',$this->prefix);
        $fakeWithdrawPlayerIds = explode(',',$fakeWithdrawPlayerIds);
        $fakeWithdrawLimit     = $fakeWithdrawLimit*10000;

        if(in_array($this->player_id,$fakeWithdrawPlayerIds)){
            $playerIds       = PlayerWithdraw::whereIn('status',[1,2])->where('amount','>=',$fakeWithdrawLimit)->pluck('player_id')->toArray();
            $randPlayerId    = array_rand($playerIds,1);
            $query           = PlayerWithdraw::select('log_player_withdraw.*','inf_player_bank_cards.card_owner_name','inf_player_bank_cards.card_account','inf_carrier_bank_type.bank_name')
            ->leftJoin('inf_player_bank_cards','inf_player_bank_cards.id','=','log_player_withdraw.player_bank_id')
            ->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')
            ->where('log_player_withdraw.amount','>=',$fakeWithdrawLimit)
            ->where('log_player_withdraw.player_id',$playerIds[$randPlayerId])
            ->orderBy('log_player_withdraw.id','desc');
        } else{
            $query           = PlayerWithdraw::select('log_player_withdraw.*','inf_player_bank_cards.card_owner_name','inf_player_bank_cards.card_account','inf_carrier_bank_type.bank_name')
            ->leftJoin('inf_player_bank_cards','inf_player_bank_cards.id','=','log_player_withdraw.player_bank_id')
            ->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')
            ->where('log_player_withdraw.player_id',$this->player_id)
            ->orderBy('log_player_withdraw.id','desc');
            
        }

        if(!in_array($this->player_id,$fakeWithdrawPlayerIds)){
            if(isset($input['startTime']) && !empty(trim($input['startTime'])) && strtotime($input['startTime'])) {
                if(strlen($input['endTime']) >10){
                    $query->where('log_player_withdraw.created_at','>=',$input['startTime']);
                } else{
                    $query->where('log_player_withdraw.created_at','>=',$input['startTime'].' 00:00:00');
                }
                
            } 

            if(isset($input['endTime']) && !empty(trim($input['endTime'])) && strtotime($input['endTime'])) {
                if(strlen($input['endTime']) >10){
                    $query->where('log_player_withdraw.created_at','<=',$input['endTime']);
                } else{
                    $query->where('log_player_withdraw.created_at','<=',$input['endTime'].' 23:59:59');
                }
            }
        }

        if(isset($input['front_status']) && in_array($input['front_status'], [0,1,-1])) {
            if($input['front_status']==0) {
                $query->whereIn('log_player_withdraw.status',[0,-1,4,5]);
            } else if($input['front_status']==1){
                $query->whereIn('log_player_withdraw.status',[1,2]);
            } else if($input['front_status']==-1){
                $query->where('log_player_withdraw.status',3);
            }
        }

        $total          = $query->count();
        $totalAmount    = $query->sum('amount');

        if(in_array($this->player_id,$fakeWithdrawPlayerIds)){
            $items          = $query->limit(1)->get();
            $total          = 1;
        } else{
            $items          = $query->skip($offset)->take($pageSize)->get();
        }

        foreach ($items as $key => &$value) {

            $existBank  = stripos($value->collection,'银行');
            $existBank1 = stripos($value->collection,'信用社');
            
            if($existBank!==false || $existBank1!==false){
                $bankArr  = explode('|',$value->collection);
                $length   = strlen($bankArr[1]);

                $startStr = substr($bankArr[1],0,3);
                $endStr   = substr($bankArr[1],-4);
                
                $value->collection = $bankArr[0].'|'.$startStr.'****'.$endStr;
            } else{
                $bankArr  = explode('|',$value->collection);
                $startStr = substr($bankArr[1],0,3);
                $endStr   = substr($bankArr[1],-4);
                $value->collection = $bankArr[0].'|'.$startStr.'****'.$endStr;
            }

            if($value->status==3){
                $value->extstatus = -1;
            } else if($value->status==1 || $value->status==2){
                $value->extstatus = 1;
            } else {
                $value->extstatus = 0;
            }

            if(!empty($value->remark)){
                $remarkArr = explode('|', $value->remark);
                $value->remark  = $remarkArr[0];
            }

            if(in_array($this->player_id,$fakeWithdrawPlayerIds)){
                $value->pay_order_number = 'TX'.date('YmdHis').rand(1111,9999);
                $value->created_at       = date('Y-m-d H:i:s');
                $value->updated_at       = date('Y-m-d H:i:s');
            }
        }

        return ['item' => $items, 'total' => $total ,'totalAmout'=>$totalAmount,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function playerAdd($carrier, $params = [])
    {
        $input         = !empty($params) ? $params : request()->all();
        $sameusername  = self::where('user_name',$input['user_name'])->where('carrier_id',$carrier->id)->first();

        if($sameusername) {
            return '对不起，此用户名已被使用';
        }

        if ( !Validator::isUsr($input['user_name'], [ 'min' => 5, 'max' => 36, 'checkUpper' => true ]) ) {
            return '对不起,帐号只能包括字母,数字,或下划线，且不以下划线开头长度为4到36个字符！';
        }

        if(!empty($input['mobile'])) {
            $samemobile    = self::where('mobile',$input['mobile'])->where('carrier_id',$carrier->id)->first();
            if($samemobile) {
                return '对不起，此手机号码已被使用';
            }
        }

        if(!isset($input['win_lose_agent']) || !in_array($input['win_lose_agent'], [0,1])){
            return '对不起，是否负盈利代理取值不正确';
        }

        if(!isset($input['prefix'])){
            return '对不起，站点取值不正确';
        }

        if(!isset($input['earnings'])) $input['earnings'] = 0;

        $maxId  = self::max('player_id');

        $this->player_id                 = $maxId+1;
        $this->user_name                 = $input['user_name'];
        $this->carrier_id                = $carrier->id;
        $this->password                  = bcrypt($input['password']);
        $this->paypassword               = null;
        $this->type                      = $input['type'];
        $this->parent_id                 = 0;
        $this->top_id                    = $this->player_id;
        $this->rid                       = $this->player_id;
        $this->win_lose_agent            = $input['win_lose_agent'];

        $defaultCarrierPlayerLevel       = CarrierPlayerGrade::where('carrier_id',$carrier->id)->where('is_default',1)->where('prefix',$input['prefix'])->first();
        $this->player_level_id           = $defaultCarrierPlayerLevel->id;
        $this->level                     = 1;
        $this->mobile                    = $input['mobile'];
        $this->is_tester                 = $input['is_tester'];
        $this->nick_name                 = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'default_nick_name',$input['prefix']);
        $this->prefix                    = $input['prefix'];
        $this->save();

        $playerSetting                            = new PlayerSetting();

        $playerSetting->lottoadds                 = CarrierCache::getCarrierConfigure($this->carrier_id,'default_lottery_odds');
        $playerSetting->earnings                  = $input['earnings'];
        $playerSetting->player_id                 = $this->player_id;
        $playerSetting->carrier_id                = $this->carrier_id;
        $playerSetting->top_id                    = $this->top_id;
        $playerSetting->parent_id                 = $this->parent_id;
        $playerSetting->rid                       = $this->rid;
        $playerSetting->user_name                 = $this->user_name;
        $playerSetting->level                     = $this->level;
        $playerSetting->is_tester                 = $this->is_tester;
        $playerSetting->prefix                    = $this->prefix;
        $playerSetting->save();

        $playerInviteCode                              = new PlayerInviteCode();
        $playerInviteCode->carrier_id                  = $playerSetting->carrier_id;
        $playerInviteCode->rid                         = $playerSetting->rid;
        $playerInviteCode->player_id                   = $playerSetting->player_id;
        $playerInviteCode->username                    = $playerSetting->user_name;
        $playerInviteCode->is_tester                   = $playerSetting->is_tester;
        $playerInviteCode->prefix                      = $playerSetting->prefix;
        $playerInviteCode->type                        = 2;
        
        $playerInviteCode->lottoadds                   = CarrierCache::getCarrierConfigure($this->carrier_id,'default_lottery_odds');
        $playerInviteCode->earnings                    = 0;

        $playerInviteCode->code                        = $this->extend_id;
        $playerInviteCode->save();

        return true;
    }

    public function playerTransferList($specialRemark=false)
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $rechargeTotalAmount = 0;
        $withdrawTotalAmount = 0;
        $giftTotalAmount     = 0;

        $query  = PlayerTransfer::select('inf_player_transfer.*','inf_carrier_user.username')
            ->leftJoin('inf_carrier_user','inf_carrier_user.id','=','inf_player_transfer.admin_id')
            ->where('inf_player_transfer.player_id',$this->player_id)
            ->orderBy('id','desc')
            ->orderBy('created_at','desc');

        $query1  = PlayerTransfer::select('inf_player_transfer.*','inf_carrier_user.username')
            ->leftJoin('inf_carrier_user','inf_carrier_user.id','=','inf_player_transfer.admin_id')
            ->where('inf_player_transfer.player_id',$this->player_id)
            ->orderBy('id','desc')
            ->orderBy('created_at','desc');

        if(isset($input['startTime']) && !empty(trim($input['startTime'])) && strtotime($input['startTime'])) {
            $query->where('inf_player_transfer.created_at','>=',$input['startTime'].' 00:00:00');
            $query1->where('inf_player_transfer.created_at','>=',$input['startTime'].' 00:00:00');
        } else {
            $query->where('inf_player_transfer.created_at','>=',date('Y-m-01 00:00:00', strtotime(date("Y-m-d"))));
            $query1->where('inf_player_transfer.created_at','>=',date('Y-m-01 00:00:00', strtotime(date("Y-m-d"))));
        }

        if(isset($input['endTime']) && !empty(trim($input['endTime'])) && strtotime($input['endTime'])){
            $query->where('inf_player_transfer.created_at','<',$input['endTime'].' 23:59:59');
            $query1->where('inf_player_transfer.created_at','<',$input['endTime'].' 23:59:59');
        } else {
            $query->where('inf_player_transfer.created_at','<',date('Y-m-d H:i:s',time()));
            $query1->where('inf_player_transfer.created_at','<',date('Y-m-d H:i:s',time()));
        }

        $rechargeTotalAmount = $query1->where('type','recharge')->sum('amount');
        $withdrawTotalAmount = $query1->where('type','withdraw_finish')->sum('amount');
        $giftTotalAmount     = $query1->whereIn('type',config('main')['giftadd'])->sum('amount');

        if(isset($input['type']) && !empty(trim($input['type']))) {
            if($input['type']=='rechargecollect'){
                $query->whereIn('inf_player_transfer.type',config('main')['rechargecollect']);
            } elseif($input['type']=='withdrawcollect'){
                $query->whereIn('inf_player_transfer.type',config('main')['withdrawcollect']);
            } elseif($input['type']=='rebatecollect'){
                $query->whereIn('inf_player_transfer.type',config('main')['rebatecollect']);
            } elseif($input['type']=='bonuscollect'){
                $query->whereIn('inf_player_transfer.type',config('main')['bonuscollect']);
            } elseif($input['type']=='othercollect'){
                $query->whereIn('inf_player_transfer.type',config('main')['othercollect']);
            } else{
                $query->where('inf_player_transfer.type',$input['type']);
            }
        }

        if(isset($input['mode']) && !empty(trim($input['mode']))) {
            $query->where('inf_player_transfer.mode',$input['mode']);
        }

        if(isset($input['flag']) && $input['flag']==1){
            $query->where('inf_player_transfer.type','<>','commission_from_child');
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();
        $mainGamePlats  = MainGamePlat::all();
        $plats          = [];

        foreach ($mainGamePlats as $key => $value) {
            $plats[$value->main_game_plat_id] = $value['alias'];
        }

        $developments = Development::all();

        $types        = [];

        foreach ($developments as $key => $value) {
            $types[$value->sign] = $value->name;
        }

        $datas = [];

        $carrier = Carrier::where('id',$this->carrier_id)->first();
        foreach ($items as $key => $value) {
            $enableSafeBox         = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'enable_safe_box',$this->prefix);
            $agentSingleBackground = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'agent_single_background',$this->prefix);
            $language              = CarrierCache::getLanguageByPrefix($this->prefix);
            $row                = [];
            $row['id']          = $value->id;
            $row['type']        = $types[$value->type];

            if($value->type=='casino_transfer_in') {

                //假PG单独处理
                if(in_array($value->platform_id, [50,69,72,75])){
                    $row['type']         = '从PG电子'.$row['type'];
                } elseif(in_array($value->platform_id, [71,76])){
                    $row['type']         = '从PP电子'.$row['type'];
                }else{
                    $row['type']         = '从'.$plats[$value->platform_id].$row['type'];
                }
            } else if($value->type=='casino_transfer_out'){
                if(in_array($value->platform_id, [50,69,72,75])){
                    $row['type']         = $row['type'].config('language')[$language]['text26'].'PG电子';
                } elseif(in_array($value->platform_id, [71,76])){
                    $row['type']         = $row['type'].config('language')[$language]['text26'].'PP电子';
                }else{
                    $row['type']         = $row['type'].config('language')[$language]['text26'].$plats[$value->platform_id];
                }
            }

            if($value->type=='withdraw_apply'){

                if($enableSafeBox==1 || ($agentSingleBackground==1 && $this->win_lose_agent==1)){
                    $row['amount']           = bcdiv($value->agent_frozen_balance - $value->before_agent_frozen_balance,10000,2);
                } else{
                    $row['amount']           = bcdiv($value->frozen_balance - $value->before_frozen_balance,10000,2);
                }
                
            } else{
                $row['amount']           = bcdiv($value->amount,10000,2);
            }
            $row['balance']          = bcdiv($value->balance,10000,2);

            $row['agentbalance']     = bcdiv($value->agent_balance,10000,2);
            $row['frozen_balance']   = bcdiv($value->agent_frozen_balance,10000,2);

            $row['mode']             = $value->mode;


            
            if($enableSafeBox || ($agentSingleBackground==1 && $this->win_lose_agent==1)){
                $row['remark']           = '原余额:'.bcdiv($value->before_balance,10000,2) .',现余额:'.bcdiv($value->balance,10000,2).',原宝险箱余额：'.bcdiv($value->before_agent_balance,10000,2).',现宝险箱余额'.bcdiv($value->agent_balance,10000,2).'; 原锁定余额:'.bcdiv($value->before_agent_frozen_balance,10000,2). ',现锁定余额:'.bcdiv($value->agent_frozen_balance,10000,2);
            } else{
                $row['remark']           = '原余额:'.bcdiv($value->before_balance,10000,2) .',现余额:'.bcdiv($value->balance,10000,2).',原锁定余额:'.bcdiv($value->before_frozen_balance,10000,2). ',现锁定余额:'.bcdiv($value->frozen_balance,10000,2);
            }

            if($specialRemark && (!empty($value->remark) || !empty($value->remark1) || !empty($value->remark2))){
                if(!empty($value->remark)){
                    $row['remark']       = $row['remark'].' | '.$value->remark;
                }
                if(!empty($value->remark1) && !is_numeric($value->remark1)){
                    $row['remark']       = $row['remark'].'|'.$value->remark1;
                }
                if(!empty($value->remark2)){
                    $row['remark']       = $row['remark'].'|'.'三方余额是'.$value->remark2;
                }
                
            }
            $row['username']         = is_null($value->username)? config('language')[$language]['text27']:$value->username;
            $row['created_at']       = date('Y-m-d H:i:s',strtotime($value->created_at));
            $datas[]                 = $row;
        }

        return ['rechargeTotalAmount'=>$rechargeTotalAmount,'withdrawTotalAmount'=>$withdrawTotalAmount,'giftTotalAmount'=>$giftTotalAmount,'item' => $datas, 'total' => $total ,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];

    }

    public function odds()
    {

        $playerSetting                       = PlayerCache::getPlayerSetting($this->player_id);
        $casinoBetflowCalculateRate          = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'casino_betflow_calculate_rate',$this->prefix);
        $electronicBetflowCalculateRate      = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'electronic_betflow_calculate_rate',$this->prefix);
        $esportBetflowCalculateRate          = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'esport_betflow_calculate_rate',$this->prefix);
        $fishBetflowCalculateRate            = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'fish_betflow_calculate_rate',$this->prefix);
        $cardBetflowCalculateRate            = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'card_betflow_calculate_rate',$this->prefix);
        $lotteryBetflowCalculateRate         = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'lottery_betflow_calculate_rate',$this->prefix);
        $sportBetflowCalculateRate           = CarrierCache::getCarrierMultipleConfigure($this->carrier_id,'sport_betflow_calculate_rate',$this->prefix);

        $playerSetting->casino_betflow_calculate_rate       = $casinoBetflowCalculateRate;
        $playerSetting->electronic_betflow_calculate_rate   = $electronicBetflowCalculateRate;
        $playerSetting->esport_betflow_calculate_rate       = $esportBetflowCalculateRate;
        $playerSetting->fish_betflow_calculate_rate         = $fishBetflowCalculateRate;
        $playerSetting->card_betflow_calculate_rate         = $cardBetflowCalculateRate;
        $playerSetting->lottery_betflow_calculate_rate      = $lotteryBetflowCalculateRate;
        $playerSetting->sport_betflow_calculate_rate        = $sportBetflowCalculateRate;

        $playerBetflowCalculates                             = PlayerBetflowCalculate::where('player_id',$this->player_id)->get();

        foreach ($playerBetflowCalculates as $key => $value) {
            switch ($value->game_category) {
                case 1:
                    $playerSetting->casino_betflow_calculate_rate       = $value->betflow_calculate_rate;
                    break;
                case 2:
                    $playerSetting->electronic_betflow_calculate_rate   = $value->betflow_calculate_rate;
                    break;
                case 3:
                    $playerSetting->esport_betflow_calculate_rate       = $value->betflow_calculate_rate;
                    break;
                case 4:
                    $playerSetting->card_betflow_calculate_rate         = $value->betflow_calculate_rate;
                    break;
                case 5:
                    $playerSetting->sport_betflow_calculate_rate        = $value->betflow_calculate_rate;
                    break;
                case 6:
                    $playerSetting->lottery_betflow_calculate_rate      = $value->betflow_calculate_rate;
                    break;
                case 7:
                    $playerSetting->fish_betflow_calculate_rate         = $value->betflow_calculate_rate;
                    break;
                
                default:
                    // code...
                    break;
            }
        }
        $noEdit                   = ['casino_returnwater','electron_returnwater','electron_sport_returnwater','fish_returnwater','card_returnwater','sport_returnwater','lott_returnwater'];
        
        return ['item'=>$playerSetting,'noEdit'=>$noEdit];
    }

    public function oddsupdate()
    {
        $input                                      = request()->all();
        $playerSetting                              = PlayerCache::getPlayerSetting($this->player_id);
        $playerSetting->earnings                    = $input['earnings'];
        $playerSetting->save();

        PlayerCache::forgetPlayerSetting($this->player_id);

        return true;
    }

    public function playerInvitecodeList($carrier,$playerId=0)
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $query           = PlayerInviteCode::where('carrier_id',$carrier->id)->where('username','<>',CarrierCache::getCarrierConfigure($carrier->id,'default_user_name'))->where('status',1)->where('is_tester',0)->orderBy('updated_at','desc');

        if(isset($input['username']) && !empty(trim($input['username']))) {
            $query->where('username','like',$input['username'].'%');
        }

        if($playerId){
            $query->where('player_id',$playerId);
        }

        if(isset($input['type']) && in_array($input['type'],[0,1])){
            if($input['type']==1){
                $query->where('domain','<>','');
            } else{
                $query->where('domain','');
            }
        }

        if(isset($input['prefix']) && !empty(trim($input['prefix']))) {
            $query->where('prefix',$input['prefix']);
        }

        if(isset($input['code']) && !empty($input['code'])){
            $query->where('code',$input['code']);
        }

        if(isset($input['win_lose_agent']) && in_array($input['win_lose_agent'],[0,1])){
            $playerIds = Player::where('carrier_id',$carrier->id)->where('win_lose_agent',$input['win_lose_agent'])->pluck('player_id')->toArray();
            if($input['win_lose_agent']==1){
                $query->whereIn('player_id',$playerIds);
            } else{
                $query->whereNotIn('player_id',$playerIds);
            }
        }

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        $show['lotto'] = 0;


        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $k => $v) {
            $carrierPreFixDomainArr[$v->prefix] = $v->name;
        }

        foreach ($items as $key => &$value) {
            $playerRealTimeDividendsStartDay         = CarrierCache::getCarrierMultipleConfigure($carrier->id,'player_realtime_dividends_start_day',$value->prefix);
            $value->directlyUnderNumber      = Player::where('parent_id',$value->player_id)->count();
            $value->cycledirectlyUnderNumber = Player::where('parent_id',$value->player_id)->where('created_at','>=',$playerRealTimeDividendsStartDay.' 00:00:00')->count();
            $value->multiple_name            = $carrierPreFixDomainArr[$value->prefix];
            $value->site_domain              = CarrierCache::getCarrierMultipleConfigure($carrier->id,'h5url',$value->prefix);
        }

        return ['show'=>$show,'item' => $items, 'total' => $total ,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function statPersonMoney($self,$startDate,$endDate,$selfEarnings)
    {  
        $data            = [];
        $players         = Player::where('parent_id',$self->player_id)->where('is_tester',0)->get();
        $positive        = 0;
        $earningsdiff    = [];

        foreach ($players as $key => $value) {

            //团队充值金额
            $query1    =  ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(team_recharge_amount) as team_recharge_amount'))->where('player_id',$value->player_id);
            //团队提现金额
            $query2    =  ReportPlayerStatDay::select(\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'))->where('player_id',$value->player_id);
            //团队分红金额
            $query5    =  ReportPlayerStatDay::select(\DB::raw('sum(dividend) as dividend'),\DB::raw('sum(team_dividend) as team_dividend'))->where('player_id',$value->player_id);

            if(!is_null($startDate)){
                $query1->where('day','>=',$startDate);
                $query2->where('day','>=',$startDate);
                $query5->where('day','>=',$startDate);
            } 

            if(!is_null($endDate)){
                $query1->where('day','<=',$endDate);
                $query2->where('day','<=',$endDate);
                $query5->where('day','<=',$endDate);
            }

            $playerDepositPayAmount  = $query1->first();
            $playerDepositPayAmount  = $playerDepositPayAmount->team_recharge_amount - $playerDepositPayAmount->recharge_amount;

            //团队提现金额
            $playerWithdrawAmount    = $query2->first();
            $playerWithdrawAmount    = $playerWithdrawAmount->team_withdraw_amount - $playerWithdrawAmount->withdraw_amount; 
            //团队分红金额
            $playerDividendsAmount   = $query5->first();
            $playerDividendsAmount   = $playerDividendsAmount->team_dividend - $playerDividendsAmount->dividend; 

            $playerSetting           = PlayerCache::getPlayerSetting($value->player_id);
            $diffEarnings            =  $selfEarnings-$playerSetting->earnings;

            if(isset($earningsdiff[$playerSetting->earnings])){

                $earningsdiff[$playerSetting->earnings]['playerDepositPayAmount'] =  $earningsdiff[$playerSetting->earnings]['playerDepositPayAmount'] + $playerDepositPayAmount;
                $earningsdiff[$playerSetting->earnings]['playerWithdrawAmount']   =  $earningsdiff[$playerSetting->earnings]['playerWithdrawAmount']   + $playerWithdrawAmount;
                $earningsdiff[$playerSetting->earnings]['playerDividendsAmount']   =  $earningsdiff[$playerSetting->earnings]['playerDividendsAmount'] + $playerDividendsAmount;

            } else {
                $earningsdiff[$playerSetting->earnings]['playerDepositPayAmount'] = $playerDepositPayAmount;
                $earningsdiff[$playerSetting->earnings]['playerWithdrawAmount']   = $playerWithdrawAmount;
                $earningsdiff[$playerSetting->earnings]['playerDividendsAmount']  = $playerDividendsAmount;
            }
            $allAdd                  = $playerDepositPayAmount + $playerDividendsAmount;
            $commission              =  bcdiv(bcmul(bcsub($playerDepositPayAmount+$playerDividendsAmount,$playerWithdrawAmount,0),$diffEarnings,0),100,0);
            $positive                += $commission;
        }

        $playerIds  = Player::where('parent_id',$self->player_id)->where('is_tester',0)->pluck('player_id')->toArray();
        $query3    =  ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as recharge_amount'))->whereIn('player_id',$playerIds);
        $query4    =  ReportPlayerStatDay::select(\DB::raw('sum(withdraw_amount) as withdraw_amount'))->whereIn('player_id',$playerIds);
        $query6    =  ReportPlayerStatDay::select(\DB::raw('sum(dividend) as dividend'))->whereIn('player_id',$playerIds);

        if(!is_null($startDate)){
            $query3->where('day','>=',$startDate);
            $query4->where('day','>=',$startDate);
            $query6->where('day','>=',$startDate);
        } 

        if(!is_null($endDate)){
            $query3->where('day','<=',$endDate);
            $query4->where('day','<=',$endDate);
            $query6->where('day','<=',$endDate);
        }

        $playerDepositPayAmount  = $query3->first();
        $playerDepositPayAmount  = $playerDepositPayAmount->recharge_amount;
        //团队提现金额
        $playerWithdrawAmount    = $query4->first();
        $playerWithdrawAmount    = $playerWithdrawAmount->withdraw_amount;
        //团队分红金额
        $playerDividendsAmount    = $query6->first();
        $playerDividendsAmount    = $playerDividendsAmount->dividend;

        $allAdd                  = $playerDepositPayAmount + $playerDividendsAmount;
        //直属记录总帐
        $commission              =  bcdiv(bcmul(bcsub($allAdd,$playerWithdrawAmount,0),$selfEarnings,0),100,0);
        $positive                += $commission;

        if(isset($earningsdiff['0.00'])){
            $earningsdiff['0.00']['playerDepositPayAmount'] =  $earningsdiff['0.00']['playerDepositPayAmount'] + $playerDepositPayAmount;
            $earningsdiff['0.00']['playerWithdrawAmount']   =  $earningsdiff['0.00']['playerWithdrawAmount']   + $playerWithdrawAmount;
            $earningsdiff['0.00']['playerDividendsAmount']   =  $earningsdiff['0.00']['playerDividendsAmount']   + $playerDividendsAmount;

        } else {
            $earningsdiff['0.00']['playerDepositPayAmount'] =  $playerDepositPayAmount;
            $earningsdiff['0.00']['playerWithdrawAmount']   =  $playerWithdrawAmount;
            $earningsdiff['0.00']['playerDividendsAmount']  =  $playerDividendsAmount;
        }

        $myCommission          = bcdiv($positive,10000,2);

        $data = [];

        foreach ($earningsdiff as $key => $value) {
            $row                           = [];
            $row['earning']                = $key;
            $row['depositamount']          = bcdiv($value['playerDepositPayAmount'],10000,2);
            $row['withdrawAmount']         = bcdiv($value['playerWithdrawAmount'],10000,2);
            $row['playerDividendsAmount']  = bcdiv($value['playerDividendsAmount'],10000,2);
            $data[]                        = $row;
        }

        return ['items'=>$data,'myCommission'=> $myCommission];
    }

    public function subordinateList()
    {
        $params          = request()->all();
        $currentPage     = isset($params['page_index']) ? intval($params['page_index']) : 1;
        $pageSize        = isset($params['page_size'])  ? intval($params['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $query           = ReportPlayerStatDay::select('user_name','player_id',\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'))->where('rid','like',$this->rid.'|%')->groupby('player_id');
        $query1          = ReportPlayerStatDay::where('rid','like',$this->rid.'|%')->groupby('player_id');

        if(isset($params['user_name']) && !empty($params['user_name'])){
            $query->where('user_name',$params['user_name']);
            $query1->where('user_name',$params['user_name']);
        }

       if(isset($params['startDate']) && strtotime($params['startDate'])){   
            $query->where('day','>=',date('Ymd',strtotime($params['startDate'])));
            $query1->where('day','>=',date('Ymd',strtotime($params['startDate'])));
        }

        if(isset($params['endDate']) && strtotime($params['endDate'])) {
            $query->where('day','<=',date('Ymd',strtotime($params['endDate'])));
            $query1->where('day','<=',date('Ymd',strtotime($params['endDate'])));
        }
        $playerIds        = $query->skip($offset)->take($pageSize)->pluck('player_id')->toArray();
        $total            = $query->count();
        $reportdatas      = $query->skip($offset)->take($pageSize)->get();

        $players          = Player::whereIn('player_id',$playerIds)->get()->toArray();

        $arr = [];
        foreach ($players as $key => $value) {
            $arr[$value['player_id']] = $value;
        }

        $playerGrades    = CarrierPlayerGrade::where('carrier_id',$this->carrier_id)->get();
        $playerGradesArr = [];

        foreach ($playerGrades as $key => $value) {
             $playerGradesArr[$value->id] = $value->level_name;
        }

        foreach($reportdatas as $k => &$v){
            $v->real_name  = $arr[$v->player_id]['real_name'];
            $v->login_at   = $arr[$v->player_id]['login_at'];
            $v->created_at = $arr[$v->player_id]['created_at'];
            $v->level_name = $playerGradesArr[$arr[$v->player_id]['player_level_id']];
        }

        return ['data' => $reportdatas, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];

    }

    public function teamInfo($carrier)
    {
        $params          = request()->all();
        $currentPage     = isset($params['page_index']) ? intval($params['page_index']) : 1;
        $pageSize        = isset($params['page_size'])  ? intval($params['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;
        $players         = Player::where('rid','like',$this->rid.'%')->pluck('player_id')->toArray();
        $language        = CarrierCache::getLanguageByPrefix($this->prefix);
        
        if(isset($params['player_id']) && !empty($params['player_id'])) {
            if(!in_array($params['player_id'], $players)) {
                return config('language')[$language]['error55'];
            }

            $parent                = self::select('rid','parent_id','player_id','login_at','created_at','is_online','user_name','real_name','descendantscount')->where('player_id',$params['player_id'])->first();

            if($this->player_id==$parent->player_id){
                $parent->type= 1;
            } else if($this->player_id == $parent->parent_id){
                $parent->type= 2;
            } else{
                $parent->type= 3;
            }

            
            $parentPlayerAccount      = PlayerAccount::select(\DB::raw('sum(balance) as balance'),\DB::raw('sum(frozen) as frozen'))->where('rid','like',$parent->rid.'%')->first();
            $selfTeamPlayerIds        = Player::where('rid','like',$parent->rid.'%')->pluck('player_id')->toArray();
            $parent->descendantscount = $parent->descendantscount+1;
            
            $parent->team_balance         = bcdiv($parentPlayerAccount->balance + $parentPlayerAccount->frozen,10000,2);
            $selfquery1          = ReportPlayerStatDay::select(\DB::raw('sum(team_recharge_amount) as team_recharge_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'))->where('player_id',$parent->player_id);
            $selfquery2          = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'))->whereIn('player_id',$selfTeamPlayerIds);
           

            if(isset($params['user_name']) && !empty($params['user_name'])) {
               $query = self::select('rid','parent_id','player_id','login_at','created_at','is_online','user_name','real_name','descendantscount')->where('user_name',$params['user_name'])->where('rid','like',$parent->rid.'|%')->orderBy('descendantscount','desc');
            } else {
               $query = self::select('rid','parent_id','player_id','login_at','created_at','is_online','user_name','real_name','descendantscount')->where(['parent_id' => $params['player_id']])->orderBy('descendantscount','desc');
            }

            if(isset($params['startDate']) && strtotime($params['startDate'])){
                $query->where('created_at','>=',$params['startDate']);
                $selfquery1->where('day','>=',date('Ymd',strtotime($params['startDate'])));
                $selfquery2->where('day','>=',date('Ymd',strtotime($params['startDate'])));
            }

            if(isset($params['endDate']) && strtotime($params['endDate'])) {
                $query->where('created_at','<=',date('Y-m-d H:i:s',strtotime($params['endDate'].' 23:59:59')));
                $selfquery1->where('day','<=',date('Ymd',strtotime($params['endDate'])));
                $selfquery2->where('day','<=',date('Ymd',strtotime($params['endDate'])));
            }

            $selfReportPlayerStatDay         = $selfquery1->first();
            $parent->team_recharge_amount    = bcdiv($selfReportPlayerStatDay->team_recharge_amount,10000,2);
            $parent->team_withdraw_amount    = bcdiv($selfReportPlayerStatDay->team_withdraw_amount,10000,0); 
            

            $parent->recharge_amount    = bcdiv($selfReportPlayerStatDay->recharge_amount,10000,2);
            $parent->withdraw_amount    = bcdiv($selfReportPlayerStatDay->withdraw_amount,10000,0); 
    
            $selfPlayerBetFlowMiddle         = $selfquery2->first();
            $selfProcessAvailableBetAmount   = $selfPlayerBetFlowMiddle->process_available_bet_amount;
            $parent->team_betflow_amount     = is_null($selfProcessAvailableBetAmount) ? 0 :$selfProcessAvailableBetAmount;

            $total          = $query->count();
            $items          = $query->skip($offset)->take($pageSize)->get();

            foreach ($items as $key => $value) {
  
                $playerAccount                     = PlayerAccount::select(\DB::raw('sum(balance) as balance'),\DB::raw('sum(frozen) as frozen'))->where('rid','like',$value->rid.'%')->first();
                $teamPlayerIds                     = Player::where('rid','like',$value->rid.'%')->pluck('player_id')->toArray();
                $value->descendantscount           = $value->descendantscount+1;
                $value->team_balance               = bcdiv($playerAccount->balance + $playerAccount->frozen,10000,2);
                $playerSetting                     = PlayerCache::getPlayerSetting($value->player_id);
                $value->lottoadds                  = $playerSetting->lottoadds;
                $value->earnings                   = $playerSetting->earnings;

                
                $query1        = ReportPlayerStatDay::select(\DB::raw('sum(team_recharge_amount) as team_recharge_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'))->where('player_id',$value->player_id);
                $query2        = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'))->whereIn('player_id',$teamPlayerIds);

                if(isset($params['startDate']) && strtotime($params['startDate'])){
                    $query1->where('day','>=',date('Ymd',strtotime($params['startDate'])));
                    $query2->where('day','>=',date('Ymd',strtotime($params['startDate'])));
                }

                if(isset($params['endDate']) && strtotime($params['endDate'])) {
                    $query1->where('day','<=',date('Ymd',strtotime($params['endDate'])));
                    $query2->where('day','<=',date('Ymd',strtotime($params['endDate'])));
                }

                $reportPlayerStatDay         = $query1->first();
                $value->team_recharge_amount = bcdiv($reportPlayerStatDay->team_recharge_amount,10000,2);
                $value->team_withdraw_amount = bcdiv($reportPlayerStatDay->team_withdraw_amount,10000,0);
                

                $value->recharge_amount = bcdiv($reportPlayerStatDay->recharge_amount,10000,2);
                $value->withdraw_amount = bcdiv($reportPlayerStatDay->withdraw_amount,10000,0);
                $value->type            = 3;

                $playerBetFlowMiddle         = $query2->first();
                $processAvailableBetAmount   = $playerBetFlowMiddle->process_available_bet_amount;
                $value->team_betflow_amount  = is_null($processAvailableBetAmount) ? 0 :$processAvailableBetAmount;
            }
            return ['self'=>$parent,'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
        } else {
           
           
            $selfPlayerAccount       = PlayerAccount::select(\DB::raw('sum(balance) as balance'),\DB::raw('sum(frozen) as frozen'))->where('rid','like',$this->rid.'%')->first();
            $selfTeamPlayerIds       = Player::where('rid','like',$this->rid.'%')->pluck('player_id')->toArray();
            $this->descendantscount  = $this->descendantscount+1;
            $this->team_balance  = bcdiv($selfPlayerAccount->balance + $selfPlayerAccount->frozen,10000,2);
            $this->type          = 1;

            $selfquery1          = ReportPlayerStatDay::select(\DB::raw('sum(team_recharge_amount) as team_recharge_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'))->where('player_id',$this->player_id);
            $selfquery2          = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'))->whereIn('player_id',$selfTeamPlayerIds);


            if(isset($params['user_name']) && !empty($params['user_name'])) {
               $query = self::select('rid','parent_id','player_id','login_at','created_at','is_online','real_name','user_name','descendantscount')->where('user_name',$params['user_name'])->where('rid','like',$this->rid.'|%')->orderBy('descendantscount','desc');
            } else {
               $query = self::select('rid','parent_id','player_id','login_at','created_at','is_online','real_name','user_name','descendantscount')->where(['parent_id' => $this->player_id])->orderBy('descendantscount','desc');
            }

            if(isset($params['startDate']) && strtotime($params['startDate'])){
                $query->where('created_at','>=',$params['startDate']);
                $selfquery1->where('day','>=',date('Ymd',strtotime($params['startDate'])));
                $selfquery2->where('day','>=',date('Ymd',strtotime($params['startDate'])));
            }

            if(isset($params['endDate']) && strtotime($params['endDate'])) {
                $query->where('created_at','<=',date('Y-m-d H:i:s',strtotime($params['endDate'].' 23:59:59')));
                $selfquery1->where('day','<=',date('Ymd',strtotime($params['endDate'])));
                $selfquery2->where('day','<=',date('Ymd',strtotime($params['endDate'])));
            }

            $selfReportPlayerStatDay         = $selfquery1->first();


            $this->team_recharge_amount      = bcdiv($selfReportPlayerStatDay->team_recharge_amount,10000,0);
            $this->team_withdraw_amount      = bcdiv($selfReportPlayerStatDay->team_withdraw_amount,10000,0);
            $this->recharge_amount           = bcdiv($selfReportPlayerStatDay->recharge_amount,10000,0);
            $this->withdraw_amount           = bcdiv($selfReportPlayerStatDay->withdraw_amount,10000,0); 

            $selfPlayerBetFlowMiddle         = $selfquery2->first();
            $selfProcessAvailableBetAmount   = $selfPlayerBetFlowMiddle->process_available_bet_amount;
            $this->team_betflow_amount       = is_null($selfProcessAvailableBetAmount) ? 0 :$selfProcessAvailableBetAmount;

            $total               = $query->count();
            $items               = $query->skip($offset)->take($pageSize)->get();

            foreach ($items as $key => $value) {
                
                $playerAccount                     = PlayerAccount::select(\DB::raw('sum(balance) as balance'),\DB::raw('sum(frozen) as frozen'))->where('rid','like',$value->rid.'%')->first();
                $teamPlayerIds                     = Player::where('rid','like',$value->rid.'%')->pluck('player_id')->toArray();
                $value->descendantscount           = $value->descendantscount+1;
                
                $value->team_balance               = bcdiv($playerAccount->balance + $playerAccount->frozen,10000,2);
                $playerSetting                     = PlayerCache::getPlayerSetting($value->player_id);
                $value->lottoadds                  = $playerSetting->lottoadds;
                $value->earnings                   = $playerSetting->earnings;
                
                $query1        = ReportPlayerStatDay::select(\DB::raw('sum(team_recharge_amount) as team_recharge_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'))->where('player_id',$value->player_id);
                $query2        = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'))->whereIn('player_id',$teamPlayerIds);

                if(isset($params['startDate']) && strtotime($params['startDate'])){
                    $query1->where('day','>=',date('Ymd',strtotime($params['startDate'])));
                    $query2->where('day','>=',date('Ymd',strtotime($params['startDate'])));
                }

                if(isset($params['endDate']) && strtotime($params['endDate'])) {
                    $query1->where('day','<=',date('Ymd',strtotime($params['endDate'])));
                    $query2->where('day','<=',date('Ymd',strtotime($params['endDate'])));
                }

                $reportPlayerStatDay         = $query1->first();
                $value->team_recharge_amount = bcdiv($reportPlayerStatDay->team_recharge_amount,10000,0);
                $value->team_withdraw_amount = bcdiv($reportPlayerStatDay->team_withdraw_amount,10000,0);
                $value->recharge_amount      = bcdiv($reportPlayerStatDay->recharge_amount,10000,0);
                $value->withdraw_amount      = bcdiv($reportPlayerStatDay->withdraw_amount,10000,0);

                $value->type =2;

                $playerBetFlowMiddle         = $query2->first();
                $processAvailableBetAmount   = $playerBetFlowMiddle->process_available_bet_amount;
                $value->team_betflow_amount  = is_null($processAvailableBetAmount) ? 0 :$processAvailableBetAmount;
            }

            return ['self'=>$this,'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
        }
    }

    public function directlyunderInfo($carrier)
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;
        $language        = CarrierCache::getLanguageByPrefix($this->prefix);
        $startDate       = null;
        $endDate         = null;


        $query           = Player::where('parent_id',$this->player_id);
        
        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('player_id',$input['player_id']);
        }
        
        //1=今天，2=昨天，3=本周，4=上周，5=本月
        if(!isset($input['type']) || !in_array($input['type'], [1,2,3,4,5])){
            return config('language')[$language]['error247'];
        }

        switch ($input['type']) {
            case '1':
                $startDate   = date('Ymd');
                $endDate     = date('Ymd');
                break;
            case '2':
                $startDate   = date("Ymd",strtotime("-1 day"));
                $endDate     = date("Ymd",strtotime("-1 day"));
                break;
            case '3':
                $weekTime    = getWeekStartEnd();
                $startDate   = $weekTime[2];
                $endDate     = $weekTime[3];
                break;
            case '4':
                if(date('w')==1){
                    $startDate = date('Ymd', strtotime('last monday'));
                } else{
                    $startDate = date('Ymd', strtotime('-1 week last monday'));
                }
                $endDate        = date('Ymd', strtotime($startDate)+518400);
                break;
            case '5':
                $monthTime   = getMonthStartEnd();
                $startDate   = $monthTime[0];
                $endDate     = $monthTime[1];
                break;
            default:
                break;
        }

        $total               = $query->count();
        $items               = $query->skip($offset)->take($pageSize)->get();

        $data = [];
        foreach ($items as $key => $value) {
            $playerAccount        = PlayerAccount::where('player_id',$value->player_id)->first();
            $row                  = [];
            $row['player_id']     = $value->player_id;
            $row['created_at']    = $value->created_at;
            $row['balance']       = bcdiv($playerAccount->balance + $playerAccount->frozen,10000,2);

            $reportPlayerStatDay  = ReportPlayerStatDay::select(\DB::raw('sum(recharge_amount) as recharge_amount'))->where('player_id',$value->player_id)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();
            $row['rechargeAmount']= bcdiv($reportPlayerStatDay->recharge_amount,10000,2);

            $playerBetFlowMiddle  = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'))->where('player_id',$value->player_id)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();
            $row['betflow']       = bcdiv($playerBetFlowMiddle->process_available_bet_amount,10000,2);

            $data[]               = $row; 
        }

        return ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function setBonus($carrier)
    {
        $params                         = request()->all();
        $player_id                      = $this->player_id;
        $carrier_id                     = $this->carrier_id;
        $earnings                       = $params['earnings'] ?? 0;  //负盈利 百分之1
        $language                       = CarrierCache::getLanguageByPrefix($this->prefix);

        if(!isset($params['next_player_id']) || empty($params['next_player_id'])) {
            return config('language')[$language]['error114'];
        }

        $selfPlayerSetting  = PlayerCache::getPlayerSetting($this->player_id);

        $nextPlayerSetting = PlayerSetting::where(['player_id' => $params['next_player_id'],'parent_id'=>$this->player_id])->first();

        if(!$nextPlayerSetting) {
            return config('language')[$language]['error115'];
        }

        //游戏盈亏计算分红
        $playerIds          = Player::where('rid','like',$nextPlayerSetting->rid.'%')->pluck('player_id')->toArray();
        $existPlayerBetFlow = PlayerBetFlow::whereIn('player_id',$playerIds)->first();
        if($existPlayerBetFlow){
            return config('language')[$language]['error244'];
        }

        $maxEarnings                  = PlayerSetting::where('rid','like',$nextPlayerSetting->rid.'|%')->max('earnings');
        $maxEarnings                  =  $maxEarnings ? $maxEarnings : 0;

        if(!is_numeric($earnings) || $earnings < 0) {
            return config('language')[$language]['error103'];
        }

        if ($selfPlayerSetting->earnings < $earnings || $earnings < $maxEarnings) {
            return config('language')[$language]['error125'];
        }

        $nextPlayerSetting->earnings                     = $earnings ;
        $nextPlayerSetting->save();

        $playerOperate                                    = new PlayerOperate();
        $playerOperate->carrier_id                        = $this->carrier_id;
        $playerOperate->player_id                         = $this->player_id;
        $playerOperate->user_name                         = $this->user_name;
        $playerOperate->type                              = 5;
        $playerOperate->desc                              = '调整下级用户ID'.$params['next_player_id'].'的佣金比例:'.$earnings;
        $playerOperate->ip                                = ip2long(real_ip());
        $playerOperate->save();

        $playerInviteCode                                = PlayerInviteCode::where('player_id',$params['next_player_id'])->get();

        foreach ($playerInviteCode as $key => $value) {
            if($value->earnings>$nextPlayerSetting->earnings) {
                $value->earnings = $nextPlayerSetting->earnings;
            }

            $value->save();
        }

        PlayerCache::forgetPlayerSetting($params['next_player_id']);

        return true;
    }

    public function winAndLoseList()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $selfQuery = ReportPlayerStatDay::select('user_name','type','player_id','level',
                \DB::raw('sum(recharge_amount) as recharge_amount'),
                \DB::raw('sum(withdraw_amount) as withdraw_amount'),
                \DB::raw('sum(available_bets) as available_bets'),
                \DB::raw('sum(lottery_available_bets) as lottery_available_bets'),
                \DB::raw('sum(win_amount) as win_amount'),
                \DB::raw('sum(lottery_winorloss) as lottery_winorloss'),
                \DB::raw('sum(dividend) as dividend'),
                \DB::raw('sum(gift) as gift'),
                \DB::raw('sum(team_recharge_amount) as team_recharge_amount'),
                \DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),
                \DB::raw('sum(team_available_bets) as team_available_bets'),
                \DB::raw('sum(team_lottery_available_bets) as team_lottery_available_bets'),
                \DB::raw('sum(team_win_amount) as team_win_amount'),
                \DB::raw('sum(team_lottery_winorloss) as team_lottery_winorloss'),
                \DB::raw('sum(team_dividend) as team_dividend'),
                \DB::raw('sum(team_gift) as team_gift'))
            ->orderBy('id','desc');

        $query = ReportPlayerStatDay::select('user_name','type','player_id','level',
                \DB::raw('sum(recharge_amount) as recharge_amount'),
                \DB::raw('sum(withdraw_amount) as withdraw_amount'),
                \DB::raw('sum(available_bets) as available_bets'),
                \DB::raw('sum(lottery_available_bets) as lottery_available_bets'),
                \DB::raw('sum(win_amount) as win_amount'),
                \DB::raw('sum(lottery_winorloss) as lottery_winorloss'),
                \DB::raw('sum(dividend) as dividend'),
                \DB::raw('sum(gift) as gift'),
                \DB::raw('sum(team_recharge_amount) as team_recharge_amount'),
                \DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),
                \DB::raw('sum(team_available_bets) as team_available_bets'),
                \DB::raw('sum(team_lottery_available_bets) as team_lottery_available_bets'),
                \DB::raw('sum(team_win_amount) as team_win_amount'),
                \DB::raw('sum(team_lottery_winorloss) as team_lottery_winorloss'),
                \DB::raw('sum(team_dividend) as team_dividend'),
                \DB::raw('sum(team_gift) as team_gift'))
            ->groupBy('user_name')->orderBy('type','asc')->orderBy('id','desc');

        if(isset($input['player_id']) && !empty(trim($input['player_id']))){
            //$selfQuery->where('player_id',$input['player_id'])->where('rid','like',$this->rid.'%');

            $selfQuery->where('player_id',$input['player_id']);
            $parent = Player::where('parent_id',$input['player_id'])->first();

            if(isset($input['user_name']) && !empty($input['user_name'])){
                //$query->where('user_name',$input['user_name'])->where('rid','like',$parent->rid.'%');
                $query->where('user_name',$input['user_name'])->where('rid','like',$this->rid.'%');
            } else {
                $query->where('parent_id',$input['player_id']);
            }

        } else {
            $selfQuery->where('player_id',$this->player_id);
            if(isset($input['user_name']) && !empty($input['user_name'])){
                $query->where('user_name',$input['user_name'])->where('rid','like',$this->rid.'%');
            } else {
                $query->where('parent_id',$this->player_id);
            }
        }

        if(isset($input['startDate']) && !empty($input['startDate']) && strtotime($input['startDate'])) {
            $selfQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        } else {
            $selfQuery->where('day','>=',date('Ymd',time()));
            $query->where('day','>=',date('Ymd',time()));
        }

        if(isset($input['endDate']) && !empty($input['endDate']) && strtotime($input['endDate'])) {
            $selfQuery->where('day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'].' 23:59:59')));
        } else {
            $selfQuery->where('day','<=',date('Ymd',time()));
            $query->where('day','<=',date('Ymd',time()));
        }

        $selfItem       = $selfQuery->first();

        if(is_null($selfItem->user_name)){
            $selfItem->link                          = '';
            $selfItem->player_id                     = $this->player_id;
            $selfItem->user_name                     = $this->user_name;
            $selfItem->type                          = $this->type;
            $selfItem->recharge_amount               = '0.00';
            $selfItem->withdraw_amount               = '0.00';
            $selfItem->available_bets                = '0.00';
            $selfItem->lottery_available_bets        = '0.00';
            $selfItem->win_amount                    = '0.00';
            $selfItem->lottery_winorloss             = '0.00';
            $selfItem->dividend                      = '0.00';
            $selfItem->gift                          = '0.00';
            $selfItem->team_recharge_amount          = '0.00';
            $selfItem->team_withdraw_amount          = '0.00';
            $selfItem->team_available_bets           = '0.00';
            $selfItem->team_lottery_available_bets   = '0.00';
            $selfItem->team_win_amount               = '0.00';
            $selfItem->team_lottery_winorloss        = '0.00';
            $selfItem->team_dividend                 = '0.00';
            $selfItem->team_gift                     = '0.00';
            $selfItem->team_profit                   = '0.00';
        } else {
            $linkArr                                 = self::where('level','<',$selfItem->level)->where('level','>=',$this->level)->orderBy('level','asc')->pluck('user_name')->toArray();
            $selfItem->link                          = json_encode($linkArr);
            $realWin                                 = -$selfItem->team_win_amount-$selfItem->team_lottery_winorloss - $selfItem->team_gift-$selfItem->team_dividend ;

            $selfItem->recharge_amount               = bcdiv($selfItem->recharge_amount,10000,2);
            $selfItem->withdraw_amount               = bcdiv($selfItem->withdraw_amount,10000,2);
            $selfItem->available_bets                = bcdiv($selfItem->available_bets,10000,2);
            $selfItem->lottery_available_bets        = bcdiv($selfItem->lottery_available_bets,10000,2);
            $selfItem->win_amount                    = bcdiv($selfItem->win_amount,10000,2);
            $selfItem->lottery_winorloss             = bcdiv($selfItem->lottery_winorloss,10000,2);
            $selfItem->dividend                      = bcdiv($selfItem->dividend,10000,2);
            $selfItem->gift                          = bcdiv($selfItem->gift,10000,2);
            $selfItem->team_recharge_amount          = bcdiv($selfItem->team_recharge_amount,10000,2);
            $selfItem->team_withdraw_amount          = bcdiv($selfItem->team_withdraw_amount,10000,2);
            $selfItem->team_available_bets           = bcdiv($selfItem->team_available_bets,10000,2);
            $selfItem->team_lottery_available_bets   = bcdiv($selfItem->team_lottery_available_bets,10000,2);
            $selfItem->team_win_amount               = bcdiv($selfItem->team_win_amount,10000,2);
            $selfItem->team_lottery_winorloss        = bcdiv($selfItem->team_lottery_winorloss,10000,2);
            $selfItem->team_dividend                 = bcdiv($selfItem->team_dividend,10000,2);
            $selfItem->team_gift                     = bcdiv($selfItem->team_gift,10000,2);
            $selfItem->team_profit                   = bcdiv(-$realWin,10000,2);
        }
        $total          = $query->get()->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => $value) {

            $realWin                              = -$value->team_win_amount-$value->team_lottery_winorloss - $value->team_gift -$value->team_dividend ;
            $value->recharge_amount               = bcdiv($value->recharge_amount,10000,2);
            $value->withdraw_amount               = bcdiv($value->withdraw_amount,10000,2);
            $value->available_bets                = bcdiv($value->available_bets,10000,2);
            $value->lottery_available_bets        = bcdiv($value->lottery_available_bets,10000,2);
            $value->win_amount                    = bcdiv($value->win_amount,10000,2);
            $value->lottery_winorloss             = bcdiv($value->lottery_winorloss,10000,2);
            $value->dividend                      = bcdiv($value->dividend,10000,2);
            $value->gift                          = bcdiv($value->gift,10000,2);
            $value->team_recharge_amount          = bcdiv($value->team_recharge_amount,10000,2);
            $value->team_withdraw_amount          = bcdiv($value->team_withdraw_amount,10000,2);
            $value->team_available_bets           = bcdiv($value->team_available_bets,10000,2);
            $value->team_lottery_available_bets   = bcdiv($value->team_lottery_available_bets,10000,2);
            $value->team_win_amount               = bcdiv($value->team_win_amount,10000,2);
            $value->team_lottery_winorloss        = bcdiv($value->team_lottery_winorloss,10000,2);
            $value->team_dividend                 = bcdiv($value->team_dividend,10000,2);
            $value->team_gift                     = bcdiv($value->team_gift,10000,2);
            $value->team_profit                   = bcdiv(-$realWin,10000,2);
            $existchild                           = Player::where('parent_id',$value->player_id)->first();
            if($existchild){
                $value->child                     = 1 ;
            }else{
                $value->child                     = 0;
            }
        }

        return ['selfitem'=>$selfItem,'item' => $items, 'total' => $total ,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    public function changeAgentline()
    {
        $input           = request()->all();

        if($this->is_tester>0) {
            return '对不起，非正式帐号不能变更代理线';
        }

        if(!isset($input['parent_id']) || empty($input['parent_id'])) {
            return '对不起，上级不能为空';
        }

        $parent  = self::where('carrier_id',$this->carrier_id)->where('player_id',$input['parent_id'])->first();

        if(strpos($parent->rid,$this->rid) !==false) {
            return '对不起，上级配置错误';
        }

        try {
            \DB::beginTransaction();

            $oldRid   = $this->rid;
            $oldLevel = $this->level;

            //变更前的上级
            $oldParent           = Player::where('player_id',$this->parent_id)->first();

            //搜索原来的下级
            $allLowerLevelIds    = Player::where('rid','like',$this->rid.'|%')->pluck('player_id')->toArray();
            $number              = count($allLowerLevelIds);

            //更新老上级的用户数量
            $oldParent->soncount = $oldParent->soncount-1;
            $oldParent->save();

            $oldParentIds        = explode('|',$oldParent->rid);
            Player::whereIn('player_id',$oldParentIds)->update(['descendantscount' =>\DB::raw('descendantscount - 1 -'.$number)]);

            $parent->soncount    = $parent->soncount +1;
            $parent->save();

            $parentIds           = explode('|',$parent->rid);
            Player::whereIn('player_id',$parentIds)->update(['descendantscount' =>\DB::raw('descendantscount + 1 +'.$number)]);

            //开始更新
            $this->top_id        = $parent->top_id;
            $this->parent_id     = $parent->player_id;
            $this->rid           = $parent->rid.'|'.$this->player_id;
            $this->level         = $parent->level + 1;
            $this->save();

            $difflevel           = $this->level-$oldLevel;

            Player::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

            PlayerAccount::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid,'level'=>$this->level]);
            PlayerAccount::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

            PlayerSetting::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid,'level'=>$this->level]);
            PlayerSetting::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

            PlayerProcessTransfer::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid]);
            PlayerProcessTransfer::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id]);

            PlayerActivityAudit::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid]);
            PlayerActivityAudit::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id]);

            PlayerTransfer::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid,'level'=>$this->level]);
            PlayerTransfer::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

            PlayerDepositPayLog::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid]);
            PlayerDepositPayLog::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id]);

            PlayerWithdraw::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid]);
            PlayerWithdraw::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id]);

            PlayerWithdrawFlowLimit::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid]);
            PlayerWithdrawFlowLimit::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id]);

            ReportPlayerEarnings::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid,'level'=>$this->level]);
            ReportPlayerEarnings::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

            ReportPlayerStatDay::where('player_id',$this->player_id)->update(['top_id'=>$parent->top_id,'parent_id'=>$parent->player_id,'rid'=>$this->rid,'level'=>$this->level]);
            ReportPlayerStatDay::whereIn('player_id',$allLowerLevelIds)->update(['rid'=>\DB::raw("replace('rid',".$oldRid.",".$this->rid.")"),'top_id'=>$this->top_id,'level'=>\DB::raw('level +'.$difflevel)]);

            \DB::commit();

            return true;
        } catch (\Exception $e) {
            \DB::rollback();

            Clog::recordabnormal('移动代理线异常'.$e->getMessage());

            return '对不起，移上级异常'.$e->getMessage();
        }
    }

    public function playerLevel()
    {
        return $this->belongsTo(CarrierPlayerGrade::class, 'player_level_id', 'id');
    }

    public function getIdAttribute()
    {
        return $this->player_id;
    }

    public function getZhuId()
    {
        return $this->player_id;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function playerOperateLog($carrierId)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerOperate::select('log_player_operate.*','inf_player.player_id','inf_player.user_name')->leftJoin('inf_player','inf_player.player_id','=','log_player_operate.player_id')->where('log_player_operate.carrier_id',$carrierId)->orderBy('log_player_operate.id','desc');

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('log_player_operate.player_id',$input['player_id']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $player = Player::where('carrier_id',$carrierId)->where('user_name',$input['user_name'])->first();
            if($player){
                $query->where('log_player_operate.player_id',$player->player_id);
            }
        }

        if(isset($input['type']) && in_array($input['type'], [0,1,2,3,4,5])){
            $query->where('log_player_operate.type',$input['type']);
        }

        if(isset($input['startTime']) && strtotime($input['startTime'])){
            $query->where('log_player_operate.created_at','>=',$input['startTime']);
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])){
            $query->where('log_player_operate.created_at','<=',$input['endTime']);
        }

        $total          = $query->get()->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->ip =long2ip($value->ip);
        }

        return [ 'data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize)) ];
    }

    static function giftTypelist()
    {
        $giftTypeList =  Development::select('sign','name')->whereIn('sign',config('main')['giftadd'])->get()->toArray();

        return  $giftTypeList;
    }

    static function giftList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $allgift        = array_merge(config('main')['giftadd'],['gift_transfer_reduce','safe_transfer_reduce','inside_transfer_to']);
        $query          = PlayerTransfer::whereIn('type',$allgift)->where('inf_player_transfer.carrier_id',$carrier->id)->orderBy('inf_player_transfer.id','desc');

        $queryTotalAdd     = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('inf_player_transfer.carrier_id',$carrier->id)->whereIn('type',config('main')['giftadd']);

        $queryTotalReduce  = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('inf_player_transfer.carrier_id',$carrier->id)->whereIn('type',['gift_transfer_reduce','safe_transfer_reduce','inside_transfer_to']);


        if(isset($input['type']) && !empty($input['type'])){
            $query->where('inf_player_transfer.type',$input['type']);
            $queryTotalAdd->where('inf_player_transfer.type',$input['type']);
            $queryTotalReduce->where('inf_player_transfer.type',$input['type']);
        }

        if(isset($input['prefix']) && !empty($input['prefix'])){
            $query->where('inf_player_transfer.prefix',$input['prefix']);
            $queryTotalAdd->where('inf_player_transfer.prefix',$input['prefix']);
            $queryTotalReduce->where('inf_player_transfer.prefix',$input['prefix']);
        }
        
        if(isset($input['startTime']) &&  strtotime($input['startTime'])) {
            $query->where('inf_player_transfer.created_at','>=',$input['startTime']);
            $queryTotalAdd->where('inf_player_transfer.created_at','>=',$input['startTime']);
            $queryTotalReduce->where('inf_player_transfer.created_at','>=',$input['startTime']);
           
        } else {
            $query->where('inf_player_transfer.created_at','>=',date('Y-m-d').' 00:00:00');
            $queryTotalAdd->where('inf_player_transfer.created_at','>=',date('Y-m-d').' 00:00:00');
            $queryTotalReduce->where('inf_player_transfer.created_at','>=',date('Y-m-d').' 00:00:00');
           
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])) {
            $query->where('inf_player_transfer.created_at','<=',$input['endTime']);
            $queryTotalAdd->where('inf_player_transfer.created_at','<=',$input['endTime']);
            $queryTotalReduce->where('inf_player_transfer.created_at','<=',$input['endTime']);
           
        } else {
            $query->where('inf_player_transfer.created_at','<=',date('Y-m-d').' 23:59:59');
            $queryTotalAdd->where('inf_player_transfer.created_at','<=',date('Y-m-d').' 23:59:59');
            $queryTotalReduce->where('inf_player_transfer.created_at','<=',date('Y-m-d').' 23:59:59');
            
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $query->where('inf_player_transfer.user_name','like','%'.$input['user_name'].'%');
            $queryTotalAdd->where('inf_player_transfer.user_name','like','%'.$input['user_name'].'%'); 
            $queryTotalReduce->where('inf_player_transfer.user_name','like','%'.$input['user_name'].'%');
            
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('inf_player_transfer.player_id',$input['player_id']);
            $queryTotalAdd->where('inf_player_transfer.player_id',$input['player_id']);
            $queryTotalReduce->where('inf_player_transfer.player_id',$input['player_id']);
        }

        $total             = $query->count();
        $items             = $query->skip($offset)->take($pageSize)->get();
        $addcount          = $queryTotalAdd->first();
        $reducecount       = $queryTotalReduce->first();

        $data['amount']         = 0;
        $data['agentamount']    = 0;

        if($addcount && !is_null($addcount->amount)){
            if($reducecount && !is_null($reducecount->amount)){
                $data['amount'] =  $addcount->amount - $reducecount->amount;
            } else {
                $data['amount'] =  $addcount->amount;
            }

        } else {
            $data['amount'] = - $reducecount->amount;
        }

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
        }

        return ['totalCollect'=>$data,'item' => $items,'carrierUsers'=>$carrierUserArr, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

    static function redbagList($carrier)
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $query          = PlayerTransfer::whereIn('type',['grab_red_bag','grab_mine_red_bag','more_mine_reward','lucky_mine_reward','hit_mine_get_indemnify','grab_pass_red_bag','revoke_red_bag','revoke_mine_red_bag','revoke_pass_red_bag','send_redbag','send_mine_redbag','hit_mine_indemnify','send_pass_redbag'])->where('inf_player_transfer.carrier_id',$carrier->id)->orderBy('inf_player_transfer.id','desc');
        $queryTotalAdd     = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('inf_player_transfer.carrier_id',$carrier->id)->whereIn('type',['grab_red_bag','grab_mine_red_bag','more_mine_reward','lucky_mine_reward','hit_mine_get_indemnify','grab_pass_red_bag','revoke_red_bag','revoke_mine_red_bag','revoke_pass_red_bag']);
        $queryTotalReduce  = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('inf_player_transfer.carrier_id',$carrier->id)->whereIn('type',['send_redbag','send_mine_redbag','hit_mine_indemnify','send_pass_redbag']);

        if(isset($input['startTime']) &&  strtotime($input['startTime'])) {
            $query->where('inf_player_transfer.created_at','>=',$input['startTime']);
            $queryTotalAdd->where('inf_player_transfer.created_at','>=',$input['startTime']);
            $queryTotalReduce->where('inf_player_transfer.created_at','>=',$input['startTime']);
        } else {
            $query->where('inf_player_transfer.created_at','>=',date('Y-m-d').' 00:00:00');
            $queryTotalAdd->where('inf_player_transfer.created_at','>=',date('Y-m-d').' 00:00:00');
            $queryTotalReduce->where('inf_player_transfer.created_at','>=',date('Y-m-d').' 00:00:00');
        }

        if(isset($input['endTime']) && strtotime($input['endTime'])) {
            $query->where('inf_player_transfer.created_at','<=',$input['endTime']);
            $queryTotalAdd->where('inf_player_transfer.created_at','<=',$input['endTime']);
            $queryTotalReduce->where('inf_player_transfer.created_at','<=',$input['endTime']);
        } else {
            $query->where('inf_player_transfer.created_at','<=',date('Y-m-d').' 23:59:59');
            $queryTotalAdd->where('inf_player_transfer.created_at','<=',date('Y-m-d').' 23:59:59');
            $queryTotalReduce->where('inf_player_transfer.created_at','<=',date('Y-m-d').' 23:59:59');
        }

        if(isset($input['user_name']) && !empty(trim($input['user_name']))) {
            $query->where('inf_player_transfer.user_name',$input['user_name']);
            $queryTotalAdd->where('inf_player_transfer.user_name',$input['user_name']); 
            $queryTotalReduce->where('inf_player_transfer.user_name',$input['user_name']);
        }

        if(isset($input['player_id']) && trim($input['player_id']) != '') {
            $query->where('inf_player_transfer.player_id',$input['player_id']);
            $queryTotalAdd->where('inf_player_transfer.player_id',$input['player_id']);
            $queryTotalReduce->where('inf_player_transfer.player_id',$input['player_id']);
        }

        $total             = $query->count();
        $items             = $query->skip($offset)->take($pageSize)->get();
        $addcount          = $queryTotalAdd->first();
        $reducecount       = $queryTotalReduce->first();

        $data['amount']    = 0;

        if($addcount && !is_null($addcount->amount)){
            if($reducecount && !is_null($reducecount->amount)){
                $data['addcount']    = $addcount->amount;
                $data['reducecount'] = $reducecount->amount;
            } else {
                $data['addcount'] =  $addcount->amount;
                $data['reducecount'] = 0;
            }
            
        } else {
            $data['addcount']    = 0;
            $data['reducecount'] = is_null($reducecount->amount) ? 0 : $reducecount->amount;
        }

        $carrierUsers      = CarrierUser::where('username','<>','super_admin')->get();
        $carrierUserArr    = [];
        $carrierUserArr[0] = '系统';
        foreach ($carrierUsers as $key => $value) {
            $carrierUserArr[$value->id] = $value->username;
        }
        return ['totalCollect'=>$data,'item' => $items,'carrierUsers'=>$carrierUserArr, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))];
    }

}
