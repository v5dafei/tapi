<?php

namespace App\Http\Controllers\Carrier;

use App\Models\PlayerLevel;
use App\Utils\Arr\ArrHelper;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Carrier\BaseController;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\GameCache;
use App\Models\Map\CarrierGamePlat;
use App\Models\Log\PlayerTransferCasino;
use App\Models\CarrierPlayerGrade;
use App\Models\PlayerGameAccount;
use App\Models\PlayerTransfer;
use App\Models\PlayerInviteCode;
use App\Models\CarrierBankCard;
use App\Models\PlayerAccount;
use App\Models\PlayerBankCard;
use App\Models\PlayerDigitalAddress;
use App\Models\Conf\PlayerSetting;
use App\Models\Def\MainGamePlat;
use App\Models\Player;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Conf\CarrierWebSite;
use App\Game\Game;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\PlayerMessage;
use App\Models\Log\PlayerDepositPayLog;
use App\Lib\Cache\Lock;
use App\Models\Log\PlayerBetFlow;
use App\Models\CarrierPreFixDomain;
use App\Models\PlayerAlipay;
use App\Lib\Cache\PlayerCache;
use App\Lib\Clog;

class PlayerController extends BaseController
{
    use Authenticatable;

    //业绩统计
    public function performanceStat($playerId)
    {
        $input = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $directlyUnderIds = Player::where('parent_id',$playerId)->pluck('player_id')->toArray();

        $query            = Player::select('inf_player.player_id','inf_player.user_name','inf_player_account.balance','inf_player_account.agentbalance','inf_player_account.agentfrozen')->leftJoin('inf_player_account','inf_player_account.player_id','=','inf_player.player_id')->whereIn('inf_player.player_id',$directlyUnderIds);

        if(isset($input['has_recharge']) && $input['has_recharge']==1){
            $hasRechargePlayerIds =  PlayerTransfer::where('type','recharge')->whereIn('player_id',$directlyUnderIds)->pluck('player_id')->toArray();
            $directlyUnderIds     = array_unique($hasRechargePlayerIds);
            $query->whereIn('inf_player.player_id',$directlyUnderIds);
        }

        if(isset($input['has_withdraw']) && $input['has_withdraw']==1){
            $hasWithdrawPlayerIds =  PlayerTransfer::where('type','withdraw_finish')->whereIn('player_id',$directlyUnderIds)->pluck('player_id')->toArray();
            $directlyUnderIds     = array_unique($hasWithdrawPlayerIds);
            $query->whereIn('inf_player.player_id',$directlyUnderIds);
        }

        if(isset($input['has_betting']) && $input['has_betting']==1){
            $hasBettingPlayerIds = PlayerBetFlowMiddle::where('whether_recharge',1)->whereIn('player_id',$directlyUnderIds)->pluck('player_id')->toArray();
            $directlyUnderIds     = array_unique($hasBettingPlayerIds);
            $query->whereIn('inf_player.player_id',$directlyUnderIds);
        }

        $total  = $query->count();
        $items  = $query->skip($offset)->take($pageSize)->get();

        $directlyUnderIds = [];
        foreach ($items as $key => $value) {
            $directlyUnderIds[] = $value->player_id;
        }

        //充值
        $rechargesQuery           = PlayerTransfer::select(\DB::raw('sum(amount) as amount'),'player_id')->whereIn('player_id',$directlyUnderIds)->where('type','recharge');

        //提现
        $withdrawQuery            = PlayerTransfer::select(\DB::raw('sum(amount) as amount'),'player_id')->whereIn('player_id',$directlyUnderIds)->where('type','withdraw_finish');

        //投注
        $availableBetamountQuery = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),'player_id')->where('whether_recharge',1)->whereIn('player_id',$directlyUnderIds);

        //保底待YU 
        $playerSettings      = PlayerSetting::whereIn('player_id',$directlyUnderIds)->get();

        //贡献佣金
        $selfPlayerSetting  = PlayerCache::getPlayerSetting($playerId);

        $earningsArr           = [];
        $guaranteedsArr        = [];

        foreach ($playerSettings as $key => $value) {
            $earningsArr[$value->player_id]    = $value->earnings;
            $guaranteedsArr[$value->player_id] = $value->guaranteed;
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $rechargesQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $withdrawQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $availableBetamountQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $rechargesQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $withdrawQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $availableBetamountQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        } 
        
        $recharges          = $rechargesQuery->groupBy('player_id')->get();
        $withdraws          = $withdrawQuery->groupBy('player_id')->get();

        $availableBetamount = $availableBetamountQuery->groupBy('player_id')->get();

        $rechargesArray    = [];
        foreach ($recharges as $key => $value) {
            $rechargesArray[$value->player_id] = $value->amount;
        }

        $withdrawsArray    = [];
        foreach ($withdraws as $key => $value) {
            $withdrawsArray[$value->player_id] = $value->amount;
        }

        $availableBetAmountsArr       = [];
        $processAvailableBetAmountArr = [];
        $contributeBetAmountArr       = [];

        foreach ($availableBetamount as $key => $value) {
            $availableBetAmountsArr[$value->player_id]       = $value->available_bet_amount;
            $processAvailableBetAmountArr[$value->player_id] = $value->process_available_bet_amount;
            $contributeBetAmountArr[$value->player_id]       = bcdiv($value->process_available_bet_amount*$selfPlayerSetting->guaranteed,10000,0);
        }

        foreach ($items as $k => &$v) {
            $v->rechargeAmount            = isset($rechargesArray[$v->player_id]) ? $rechargesArray[$v->player_id] : 0;
            $v->withdrawAmount            = isset($withdrawsArray[$v->player_id]) ? $withdrawsArray[$v->player_id] : 0;
            $v->availableBetAmount        = isset($availableBetAmountsArr[$v->player_id]) ? $availableBetAmountsArr[$v->player_id]*10000 : 0;
            $v->processAvailableBetAmount = isset($processAvailableBetAmountArr[$v->player_id]) ? $processAvailableBetAmountArr[$v->player_id]*10000 : 0;
            $v->contributeBetAmount       = isset($contributeBetAmountArr[$v->player_id]) ? $contributeBetAmountArr[$v->player_id]*10000:0;
            $v->balance                   = $v->balance + $v->agentbalance +$v->agentfrozen;
        }

        return $this->returnApiJson("获取成功", 1,['item' => $items,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }


    //团队业绩统计
    public function teamPerformanceStat($playerId)
    {
        $input = request()->all();

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        //充值
        $rechargesQuery           = PlayerTransfer::where('type','recharge')->where('rid','like',$player->rid.'|%');

        //提现
        $withdrawQuery            = PlayerTransfer::select(\DB::raw('sum(amount) as amount'),'player_id')->where('type','withdraw_finish')->where('rid','like',$player->rid.'|%');

        //投注
        $availableBetamountQuery = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'))->where('whether_recharge',1)->where('rid','like',$player->rid.'|%');


        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $rechargesQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $withdrawQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $availableBetamountQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        } else{
            $rechargesQuery->where('day','>=',date('Ymd'));
            $withdrawQuery->where('day','>=',date('Ymd'));
            $availableBetamountQuery->where('day','>=',date('Ymd'));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $rechargesQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $withdrawQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $availableBetamountQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        } 
        
        $rechargeAmount             = $rechargesQuery->sum('amount');
        $withdrawAmount             = $withdrawQuery->sum('amount');
        $availableBetAmount         = 0;
        $processAvailableBetAmount  = 0;
        $availableBetamount         = $availableBetamountQuery->first();

        if($availableBetamount && !is_null($availableBetamount->available_bet_amount)){
            $availableBetAmount = $availableBetamount->available_bet_amount;
        }

        if($availableBetamount && !is_null($availableBetamount->process_available_bet_amount)){
            $processAvailableBetAmount = $availableBetamount->process_available_bet_amount;
        }

        $data                                  = [];
        $data['recharge_amount']               = bcdiv($rechargeAmount,10000,2);
        $data['withdraw_amount']               = bcdiv($withdrawAmount,10000,2);
        $data['available_bet_amount']          = $availableBetAmount;
        $data['process_available_bet_amount']  = $processAvailableBetAmount;

        return $this->returnApiJson("获取成功", 1,$data);
    }

    // 登录
    public function playerList()
    {
        $pageData       = Player::getList($this->carrier,$this->carrierUser);

        return $this->returnApiJson('操作成功', 1, $pageData);
    }

    public function playerInfo($playerId = 0)
    {
        $allPlayers           = Player::select('user_name','player_id','type')->where('carrier_id',$this->carrier->id)->get();
        $allPlayersKy         = [];
        $allAgentsKy          = [];
        foreach ($allPlayers as $key => $value) {
            $allPlayersKy[$value->player_id] = $value;
            if(in_array($value->type, [1,2]) && $value->player_id != $playerId){
                $allAgentsKy[$value->player_id] = $value;
            }
        }

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        } else {
            $defaultUserName = CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');
            $defaultAgent    = Player::where('user_name',$defaultUserName)->where('carrier_id',$this->carrier->id)->first();
            $parents         = ['0'=>'直属代理',$defaultAgent->player_id => '默认代理'];
            if($player->parent_id){
                $parents[$player->parent_id] =$allPlayersKy[$player->parent_id]->user_name;
            }

            $inviteplayer   = Player::where('win_lose_agent',1)->where('carrier_id',$this->carrier->id)->where('player_id','<>',$playerId)->where('parent_id','<>',$playerId)->get();
            $inviteplayers   = ['0'=>'无介绍人'];

            foreach ($inviteplayer as $key => $value) {
                $inviteplayers[$value->player_id] = $value->user_name;
            }

            # 用户等级
            $levelGroup = PlayerLevel::getPrefixKvList($this->carrier->id,$player->prefix);
            $levelGroup = ArrHelper::getKeyValuePair($levelGroup, 'id', 'groupname');



            $player->levelGroupList = $levelGroup;

            if(isset($levelGroup[$player->player_group_id])){
                $player->group_name = $levelGroup[$player->player_group_id];
            } else {
                $player->group_name = current($levelGroup)===false ? '': current($levelGroup);
            }

            if($player->first_deposit_recommender){
                $firstDepositRecommender           = Player::select('user_name')->where('player_id',$player->first_deposit_recommender)->first();
                $player->first_deposit_recommender = $firstDepositRecommender->user_name;
            }else {
                $player->first_deposit_recommender = '';
            }

            $carrierPlayerLevel = CarrierPlayerGrade::where('carrier_id',$this->carrier->id)->where('prefix',$player->prefix)->get();
            $options            = [];

            foreach ($carrierPlayerLevel as  $value) {
                $options[$value->id] = $value->level_name;
            }

            $player->agents        = $parents;
            $player->option        = $options;
            $player->inviteplayers = $inviteplayers;

            if(!$this->carrierUser->is_super_admin){
                $player->mobile     = empty($player->mobile) ? '': substr($player->mobile,0,3).'****'.substr($player->mobile,-4);
            }
        }

        $ridArr      = explode('|', $player->rid);
        $playerLinks = Player::whereIn('player_id',$ridArr)->orderBy('level','asc')->get();
        $str = '';
        foreach ($playerLinks as $key => $value) {
            $str.=$value->player_id.'('.$value->user_name.') > ';
        }
        $str = rtrim($str,' > ');
        $player->playerlink = $str;
        return $this->returnApiJson("操作成功", 1,$player);
    }

    public function playerAdd()
    {
        $player = new Player();
        $res    = $player->playerAdd($this->carrier);

        if($res === true) {
            return $this->returnApiJson('操作成功', 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function changeAgentType($playerId)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        if($playerId->type!=3) {
            return $this->returnApiJson("对不起，此用户已经是代理!!!", 0);
        }

        $res = $player->changeAgentType();
        if($res === true) {
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function changePlayerStatus($playerId = 0)
    {
       $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $player->changeStatus();
        return $this->returnApiJson("操作成功", 1);
    }

    public function changeAgentlineStatus($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $player->changeAgentlineStatus();
        return $this->returnApiJson("操作成功", 1);
    }

    public function changePlayerFrozenStatus($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->changeFrozenStatus();
        if($res === true) {
             return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
       
    }

    public function changePlayerPassword($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player){
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->changePassword();
        if($res === true) {
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function updatePlayerInfo($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->updatePlayerInfo($this->carrierUser);
        if($res === true) {
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerTransfer($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->playerTransfer();
        if($res === true) {
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function setPlayerSalary($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->setPlayerSalary($this->carrier);
        if($res === true) {
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function addreduce($playerId = 0)
    {
        $input  = request()->all();
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $agentSingleBackground = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'agent_single_background',$player->prefix);
        if(!$agentSingleBackground && isset($input['type']) && $input['type'] == 'safe_transfer_add'){
            return $this->returnApiJson("对不起，非独立后台不能使用保险箱上分", 0);
        }

        if(isset($input['type']) && $input['type'] == 'agent_support'){
            $existPlayerBankCard = PlayerBankCard::where('player_id',$playerId)->first();
            $existPlayerAlipay   = PlayerAlipay::where('player_id',$playerId)->first();
            if(!$existPlayerBankCard && !$existPlayerAlipay){
                return $this->returnApiJson("对不起，代理扶持必须先绑定银行卡或支付宝", 0);
            }
        }

        $res = $player->addreduce($this->carrierUser->id);
        if($res === true) {
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerFinanceinfo($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->playerFinanceinfo($this->carrierUser->id);
        if(is_array($res)) {
            return $this->returnApiJson("操作成功", 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerTransferList($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->playerTransferList(true);
        if(is_array($res)) {
            return $this->returnApiJson("操作成功", 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerCasinoTransferList()
    {
        $res = PlayerTransferCasino::playerCasinoTransferList($this->carrier);
        if(is_array($res)) {
            return $this->returnApiJson("操作成功", 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerLoginInfo($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->playerLoginInfo();
        if(is_array($res)) {
            return $this->returnApiJson("操作成功", 1,$res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function changeAgentline($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->changeAgentline();
        if($res===true) {
            return $this->returnApiJson("操作成功", 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerBankList($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->playerBankList($this->carrier->id);
        if(is_array($res)) {
            return $this->returnApiJson("操作成功", 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerBankDelete($playerBankCardId=0)
    {
        $playerBankCardk = PlayerBankCard::where('carrier_id',$this->carrier->id)->where('id',$playerBankCardId)->first();
        if($playerBankCardk){
            $playerBankCardk->delete();
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson("对不起，此银行卡不存在", 0);
        }
    }

    public function playerBankEdit($playerBankCardId=0)
    {
        $input           = request()->all();
        $playerBankCard = PlayerBankCard::where('carrier_id',$this->carrier->id)->where('id',$playerBankCardId)->first();
        if(!$playerBankCard){
            return $this->returnApiJson("对不起，此银行卡不存在", 0);
        } else {
            if(!isset($input['bank_Id']) || empty($input['bank_Id'])){
                return $this->returnApiJson("对不起，银行名称不正确", 0);
            }

            if(!isset($input['card_account']) || empty($input['card_account'])){
                return $this->returnApiJson("对不起，银行帐号不正确", 0);
            }
            if(!isset($input['status']) || !in_array($input['status'], [0,1])){
                return $this->returnApiJson("对不起，银行卡状态不正确", 0);
            }

            $playerBankCard->bank_Id         = $input['bank_Id'];
            $playerBankCard->card_account    = $input['card_account'];
            $playerBankCard->status          = $input['status'];
            $playerBankCard->save();

            return $this->returnApiJson("操作成功", 1);
        }
    }


    public function playerAlipayList($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->playerAlipayList($this->carrier->id);
        if(is_array($res)) {
            return $this->returnApiJson("操作成功", 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerAlipayDelete($playerAlipayId=0)
    {
        $playerAlipay = PlayerAlipay::where('carrier_id',$this->carrier->id)->where('id',$playerAlipayId)->first();
        if($playerAlipay){
            $playerAlipay->delete();
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson("对不起，此银行卡不存在", 0);
        }
    }

    public function playerAlipayEdit($playerAlipayId=0)
    {
        $input           = request()->all();
        $playerAlipay    = PlayerAlipay::where('carrier_id',$this->carrier->id)->where('id',$playerAlipayId)->first();
        if(!$playerAlipay){
            return $this->returnApiJson("对不起，此支付宝不存在", 0);
        } else {
            if(!isset($input['account']) || empty($input['account'])){
                return $this->returnApiJson("对不起，支付宝帐号不正确", 0);
            }
            if(!isset($input['status']) || !in_array($input['status'], [0,1])){
                return $this->returnApiJson("对不起，支付宝状态不正确", 0);
            }

            $playerAlipay->account         = $input['account'];
            $playerAlipay->status          = $input['status'];
            $playerAlipay->save();

            return $this->returnApiJson("操作成功", 1);
        }
    }

    public function playerDigitalAddressList($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->digitalAddressList($this->carrier->id);
        if(is_array($res)) {
            return $this->returnApiJson("操作成功", 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerDigitalAddressDelete($playerDigitalAddressId=0)
    {
        $playerDigitalAddress = PlayerDigitalAddress::where('carrier_id',$this->carrier->id)->where('id',$playerDigitalAddressId)->first();
        if($playerDigitalAddress){
            $playerDigitalAddress->delete();
            return $this->returnApiJson("操作成功", 1);
        } else {
            return $this->returnApiJson("对不起，此数字币地址不存在", 0);
        }
    }

    public function playerDigitalAddressEdit($playerDigitalAddressId=0)
    {
        $input           = request()->all();
        $playerDigitalAddress = PlayerDigitalAddress::where('carrier_id',$this->carrier->id)->where('id',$playerDigitalAddressId)->first();
        if(!$playerDigitalAddress){
            return $this->returnApiJson("对不起，此数字币地址不存在", 0);
        } else {
            if(!isset($input['address']) || empty($input['address'])){
                return $this->returnApiJson("对不起，数字币地址取值不正确", 0);
            }

            if(!isset($input['type']) || !in_array($input['type'], [1,2,3,4])){
                return $this->returnApiJson("对不起，此数字币类型取值不正确", 0);
            }
            if(!isset($input['status']) || !in_array($input['status'], [0,1])){
                return $this->returnApiJson("对不起，此数字币地址状态取值不正确", 0);
            }

            $playerDigitalAddress->address = $input['address'];
            $playerDigitalAddress->type    = $input['type'];
            $playerDigitalAddress->status  = $input['status'];
            $playerDigitalAddress->save();

            return $this->returnApiJson("操作成功", 1);
        }
    }

    public function playerExchangeList($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->playerExchangeList();
        
        return $this->returnApiJson("操作成功", 1, $res);
    }

    public function playerGameBalance($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res = $player->playerGameBalance();
        if(is_array($res)) {
            return $this->returnApiJson("操作成功", 1, $res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function playerBalanceInfo($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }
        
        $playerAccount =  PlayerAccount::where('player_id',$playerId)->first();
        return $this->returnApiJson("获取成功", 1, ['balance'=> bcdiv($playerAccount->balance,10000,4),'frozen'=> bcdiv($playerAccount->frozen,10000,4),'agentbalance'=> bcdiv($playerAccount->agentbalance,10000,4),'agentfrozen'=> bcdiv($playerAccount->agentfrozen,10000,4)]);
    }

    public function playerGameplats($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $playGameAccounts      = PlayerGameAccount::select('main_game_plat_code','is_locked','is_need_repair','balance','account_user_name')->where('player_id',$playerId)->get();
        $playGameAccountsArray = [];

        foreach ($playGameAccounts as $key => $value) {
            $row['main_game_plat_code'] = $value->main_game_plat_code;
            $row['is_locked']           = $value->is_locked;
            $row['is_need_repair']      = $value->is_need_repair;
            $row['balance']             = $value->balance;
            $row['account_user_name']   = $value->account_user_name;

            if(in_array($row['main_game_plat_code'], config('game')['pub']['nologout'])) {
                $row['kick'] =0;
            } else {
                $row['kick'] =1;
            }

            $playGameAccountsArray[] = $row;
        }

        return $this->returnApiJson("获取成功", 1, ['plats'=>$playGameAccountsArray]);
    }

    public function directlyUnder($playerId = 0)
    {
        $input = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $query  = Player::select('inf_player.player_id','inf_player.user_name','inf_player.real_name','inf_player.mobile','inf_player.register_ip','inf_player.created_at','inf_player.login_at','inf_player.login_ip','inf_player_account.balance','inf_player_account.frozen')->leftJoin('inf_player_account','inf_player_account.player_id','=','inf_player.player_id')->where('inf_player.parent_id',$playerId);

        $total  = $query->count();
        $items  = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            if(!empty($value->mobile)){
                $value->mobile  = substr($value->mobile,0,3).'****'.substr($value->mobile,-4);
            }
        }            

        return $this->returnApiJson("获取成功", 1,['item' => $items,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function allUnder($playerId = 0)
    {
        $input = request()->all();

        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size']) ? intval($input['page_size']) : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $query = Player::select('inf_player.player_id','inf_player.user_name','inf_player.real_name','inf_player.mobile','inf_player.register_ip','inf_player.created_at','inf_player.login_at','inf_player.login_ip','inf_player_account.balance','inf_player_account.frozen')->leftJoin('inf_player_account','inf_player_account.player_id','=','inf_player.player_id')->where('inf_player.rid','like',$player->rid.'|%');

        $total  = $query->count();
        $items  = $query->skip($offset)->take($pageSize)->get();  

        foreach ($items as $key => &$value) {
            if(!empty($value->mobile)){
                $value->mobile  = substr($value->mobile,0,3).'****'.substr($value->mobile,-4);
            }
        }           

        return $this->returnApiJson("获取成功", 1,['item' => $items,'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function odds($playerId = 0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        $res          = $player->odds();
        return $this->returnApiJson("获取成功", 1,[$res]);
    }

    public function playerInvitecodeList()
    {
        $player = new Player();
        $res    = $player->playerInvitecodeList($this->carrier);
        
        return $this->returnApiJson('操作成功', 1,$res);
    }

    public function updatePlayerinvitecode($playerId = 0)
    {
        $input  = request()->all();
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();

        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }
       if(!isset($input['id']) || empty(trim($input['id']))) {
            return $this->returnApiJson("对不起，缺少参数!!!", 0);
       }

       $playerInviteCode = PlayerInviteCode::where('id',$input['id'])->where('player_id',$playerId)->first();

       if(!$playerInviteCode) {
           return $this->returnApiJson("对不起，此条不存在!!!", 0);
       }

       if(isset($input['domain'])){
          $existPlayerInviteCode = PlayerInviteCode::where('domain',$input['domain'])->first();
          if($existPlayerInviteCode && $existPlayerInviteCode->player_id != $playerId){
             return $this->returnApiJson("对不起，此域名已被使用!!!", 0);
          }
       }
       
       $playerInviteCode->domain = isset($input['domain']) ? $input['domain']:'';
       $playerInviteCode->code = $input['code'];
       $playerInviteCode->save();

       return $this->returnApiJson('操作成功', 1);
    }

    public function bindBankcard($playerId = 0)
    {
        $input = request()->all();

        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player) {
            return $this->returnApiJson("对不起，此用户不存在!!!", 0);
        }

        if(!isset($input['carrier_bankcard_id']) || trim($input['carrier_bankcard_id']) == '') {
            return $this->returnApiJson("对不起，参数不存在!!!", 0);
        }

        $carrierBankCard = CarrierBankCard::where('carrier_id',$this->carrier->id)->where('id',$input['carrier_bankcard_id'])->first();
        if(!$carrierBankCard && ($input['carrier_bankcard_id'] != 0)) {
             return $this->returnApiJson("对不起，此银行卡不存在!!!", 0);
        }
        
        $player->carrier_bankcard_id = $input['carrier_bankcard_id'] ;
        $player->save();

        return $this->returnApiJson('操作成功', 1);
    }

    public function playerCasinoTransferCheck($transferCasinoId = 0)
    {
        if(!$transferCasinoId){
            return $this->returnApiJson("对不起，此订单不存在!!!", 0);
        }

        $playerTransferCasino =  PlayerTransferCasino::where('id',$transferCasinoId)->first();

        if(!$transferCasinoId){
            return $this->returnApiJson("对不起，此订单不存在!!!", 0);
        }

        if($playerTransferCasino->status==1){
            return $this->returnApiJson("操作成功", 1,['status'=>1]);
        } 

        $currtime = time()-60;
        if(strtotime($playerTransferCasino->created_at)>$currtime){
            return $this->returnApiJson("对不起，系统繁忙请稍后再试!!!", 0);
        }

        $cacheKey = 'carrier_game_check_'.$transferCasinoId;
        $redisLock = Lock::addLock($cacheKey,60);
        
        if(!$redisLock){
            return $this->returnApiJson("对不起，系统繁忙请稍后再试1!!!", 0);
        }

        $playerGameAccount = PlayerGameAccount::where('player_id',$playerTransferCasino->player_id)->where('main_game_plat_code',$playerTransferCasino->main_game_plat_code)->first();
            
        request()->offsetSet('mainGamePlatCode',$playerTransferCasino->main_game_plat_code);
        request()->offsetSet('transferId',$playerTransferCasino->transferid);
        request()->offsetSet('direction',$playerTransferCasino->type);
        request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
        request()->offsetSet('password',$playerGameAccount->password);

        $game = new Game($this->carrier,$playerTransferCasino->main_game_plat_code);

        return $game->checkTransfer($playerTransferCasino);
    }

    public function changePlayerDelayOrder($playerId=0)
    {
        $player = Player::where('player_id',$playerId)->where('carrier_id',$this->carrier->id)->first();

        if(!$player){
            return $this->returnApiJson("对不起,此用户不存在", 0);
        } else {
            $player->delayorder = $player->delayorder ? 0 : 1;
            $player->save();
            return $this->returnApiJson("操作成功", 1);
        }
    }

    public function playerCasinoTransferSetting($transferCasinoId = 0)
    {
        $input = request()->all();

        if(!$transferCasinoId){
            return $this->returnApiJson("对不起，此订单不存在!!!", 0);
        }

        $playerTransferCasion =  PlayerTransferCasino::where('id',$transferCasinoId)->first();

        if(!$transferCasinoId){
            return $this->returnApiJson("对不起，此订单不存在!!!", 0);
        }

        if($playerTransferCasion->status != 0){
            return $this->returnApiJson("对不起，此订单无法设置!!!", 0);
        } 

        if(strtotime($playerTransferCasion->created_at)>time()-60){
            return $this->returnApiJson("对不起，系统繁忙请稍后再试!!!", 0);
        }

        if(!isset($input['status']) || !in_array($input['status'], [1,2])){
            return $this->returnApiJson("对不起，状态设置不正确!!!", 0);
        }

        if($input['status']==1){
            if($playerTransferCasion->type==1){
                //转入游戏平台帐变
                $playerTransferCasion->status =1;
                $playerTransferCasion->save();
            } else {
                $carrierGamePlat = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',$playerTransferCasion->main_game_plat_id)->first();

                $cacheKey   = "player_" .$playerTransferCasion->player_id;
                $redisLock = Lock::addLock($cacheKey,60);
                if (!$redisLock) {
                    return $this->returnApiJson('对不起, 系统繁忙请稍后再试!', 0);
                } else {
                    try {
                        \DB::beginTransaction();

                        $playAccount                                = PlayerAccount::where('player_id',$playerTransferCasion->player_id)->lockForUpdate()->first();
                        $player                                     = Player::where('player_id',$playerTransferCasion->player_id)->first();

                        $playerTransferCasion->status               = 1;
                        $playerTransferCasion->save();

                        $playerTransefer                            = new PlayerTransfer();
                        $playerTransefer->prefix                    = $player->prefix;
                        $playerTransefer->carrier_id                = $playAccount->carrier_id;
                        $playerTransefer->rid                       = $playAccount->rid;
                        $playerTransefer->top_id                    = $playAccount->top_id;
                        $playerTransefer->parent_id                 = $playAccount->parent_id;
                        $playerTransefer->player_id                 = $playAccount->player_id;
                        $playerTransefer->is_tester                 = $playAccount->is_tester;
                        $playerTransefer->user_name                 = $playAccount->user_name;
                        $playerTransefer->level                     = $playAccount->level;
                        $playerTransefer->platform_id               = GameCache::getGamePlatId($playerTransferCasion->main_game_plat_code);
                        $playerTransefer->mode                      = 1;
                        $playerTransefer->type                      = 'casino_transfer_in';
                        $playerTransefer->type_name                 = '转入中心钱包';
                        $playerTransefer->project_id                = $playerTransferCasion->transferid;
                        $playerTransefer->day_m                     = date('Ym');
                        $playerTransefer->day                       = date('Ymd');
                        $playerTransefer->amount                    = $playerTransferCasion->price*10000;
                        $playerTransefer->before_balance            = $playAccount->balance;
                        $playerTransefer->balance                   = $playAccount->balance + $playerTransferCasion->price*10000;
                        $playerTransefer->before_frozen_balance     = $playAccount->frozen;
                        $playerTransefer->frozen_balance            = $playAccount->frozen;

                        $playerTransefer->before_agent_balance         = $playAccount->agentbalance;
                        $playerTransefer->agent_balance                = $playAccount->agentbalance;
                        $playerTransefer->before_agent_frozen_balance  = $playAccount->agentfrozen;
                        $playerTransefer->agent_frozen_balance         = $playAccount->agentfrozen;

                        $playerTransefer->save();

                        $playAccount->balance                       = $playerTransefer->balance;
                        $playAccount->save();

                        $this->carrier->remain_quota                = bcadd($this->carrier->remain_quota,bcsub(bcmul($playerTransferCasion->price,$carrierGamePlat->point,6),100,4),4);
                        $this->carrier->save();

                        \DB::commit();
                        Lock::release($redisLock);

                    } catch (\Exception $e) {
                        \DB::rollback();
                        Lock::release($redisLock);
                        Clog::recordabnormal('转入中心钱包异常:'.$e->getMessage());   
                        return $this->returnApiJson($e->getMessage(), 0);
                    }
                }
            }
            return $this->returnApiJson("操作成功", 1,['status'=>1]);
        } else if($input['status']==2){
            if($playerTransferCasion->type==1){
                $carrierGamePlat = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',$playerTransferCasion->main_game_plat_id)->first();
                $cacheKey        = "player_" .$playerTransferCasion->player_id;
                $redisLock       = Lock::addLock($cacheKey,60);
                if (!$redisLock) {
                    return $this->returnApiJson('对不起, 系统繁忙请稍后再试!', 0);
                } else {
                    try {
                        \DB::beginTransaction();

                        $playAccount                                = PlayerAccount::where('player_id',$playerTransferCasion->player_id)->lockForUpdate()->first();
                        $player                                     = Player::where('player_id',$playerTransferCasion->player_id)->first();

                        $playerTransferCasion->status               = 2;
                        $playerTransferCasion->save();

                        $playerTransefer                            = new PlayerTransfer();
                        $playerTransefer->prefix                    = $player->prefix;
                        $playerTransefer->carrier_id                = $playAccount->carrier_id;
                        $playerTransefer->rid                       = $playAccount->rid;
                        $playerTransefer->top_id                    = $playAccount->top_id;
                        $playerTransefer->parent_id                 = $playAccount->parent_id;
                        $playerTransefer->player_id                 = $playAccount->player_id;
                        $playerTransefer->is_tester                 = $playAccount->is_tester;
                        $playerTransefer->user_name                 = $playAccount->user_name;
                        $playerTransefer->level                     = $playAccount->level;
                        $playerTransefer->platform_id               = GameCache::getGamePlatId($playerTransferCasion->main_game_plat_code);
                        $playerTransefer->mode                      = 1;
                        $playerTransefer->type                      = 'casino_transfer_out_error';
                        $playerTransefer->type_name                 = '转出中心钱包失败';
                        $playerTransefer->project_id                = $playerTransferCasion->transferid;
                        $playerTransefer->day_m                     = date('Ym');
                        $playerTransefer->day                       = date('Ymd');
                        $playerTransefer->amount                    = $playerTransferCasion->price*10000;
                        $playerTransefer->before_balance            = $playAccount->balance;
                        $playerTransefer->balance                   = $playAccount->balance + $playerTransferCasion->price*10000;
                        $playerTransefer->before_frozen_balance     = $playAccount->frozen;
                        $playerTransefer->frozen_balance            = $playAccount->frozen;

                        $playerTransefer->before_agent_balance         = $playAccount->agentbalance;
                        $playerTransefer->agent_balance                = $playAccount->agentbalance;
                        $playerTransefer->before_agent_frozen_balance  = $playAccount->agentfrozen;
                        $playerTransefer->agent_frozen_balance         = $playAccount->agentfrozen;

                        $playerTransefer->save();
                                    
                        $playAccount->balance                       = $playerTransefer->balance;
                        $playAccount->save();

                        $this->carrier->remain_quota                = bcadd($this->carrier->remain_quota,bcsub(bcmul($playerTransferCasion->price,$carrierGamePlat->point,6),100,4),4);
                        $this->carrier->save();
                        \DB::commit();
                        Lock::release($redisLock);

                    } catch (\Exception $e) {
                        \DB::rollback();
                        Lock::release($redisLock);
                        Clog::recordabnormal('转出中心钱包失败异常:'.$e->getMessage()); 

                        return $this->returnApiJson($e->getMessage(), 0);
                    }
                }
            } else {
                $playerTransferCasion->status =2;
                $playerTransferCasion->save();
            }
            return $this->returnApiJson("操作成功", 1,['status'=>0]);
        }
    }

    public function gameplatLimit($playerId)
    {
        $input  = request()->get('plat_ids');
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player){
            return $this->returnApiJson("对不起,此用户不存在", 0);
        }

        if(!empty($input)){
            $player->limitgameplat = $input;
        } else {
            $player->limitgameplat = '';
        }
        
        $player->save();

        return $this->returnApiJson("操作成功", 1);
    }

    public function playerGameAccountClear($playerId)
    {
        $input  = request()->get('gameplats');
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$player){
            return $this->returnApiJson("对不起,此用户不存在", 0);
        }

        PlayerGameAccount::where('player_id',$playerId)->delete();
        return $this->returnApiJson("操作成功", 1);
    }

    public function playerOperateLog()
    {
        $player         = new Player();
        $rs             = $player->playerOperateLog($this->carrier->id);

        return $this->returnApiJson("操作成功", 1,$rs);
    }

    public function agents()
    {
        $input             = request()->all();
        $defaultUserName   = CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');
        if(isset($input['player_id']) || isset($input['user_name']) || isset($input['parent_id'])){

            $query = Player::select('user_name','player_id','descendantscount')->where('carrier_id',$this->carrier->id)->where(function($query) use($defaultUserName){
                $query->where('win_lose_agent',1)->orWhere('user_name',$defaultUserName);
            });
           

            if(isset($input['user_name'])){
                $query->where('user_name',$input['user_name']);
            }

            if(isset($input['player_id'])){
                $query->where('player_id',$input['player_id']);
            }

            if(isset($input['parent_id'])){
                $query->where('parent_id',$input['parent_id']);
            }

            $players = $query->get();

        } else {

            $players = Player::select('user_name','player_id','descendantscount')->where('carrier_id',$this->carrier->id)->where(function($query) use($defaultUserName){
                $query->where('win_lose_agent',1)->orWhere('user_name',$defaultUserName);
            })->get();
        }

        foreach ($players as $key => &$value) {
            $value->descendantscount =0;
        }

        return $this->returnApiJson("操作成功", 1,$players);
    }

    public function playerGroupList()
    {
        $data = PlayerLevel::where('carrier_id',$this->carrier->id)->orderBy('id','asc')->get();

        return $this->returnApiJson("操作成功", 1,$data);
    }

    public function playerGameAccountList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query = PlayerGameAccount::select('inf_player_game_account.*','inf_player.user_name','inf_player.parent_id','inf_player.top_id','inf_player.rid')
            ->leftJoin('inf_player','inf_player.player_id','=','inf_player_game_account.player_id')
            ->where('inf_player_game_account.carrier_id',$this->carrier->id)
            ->orderBy('inf_player_game_account.account_id','desc');

        if(isset($input['main_game_plat_id']) && !empty($input['main_game_plat_id'])){
            $query->where('inf_player_game_account.main_game_plat_id',$input['main_game_plat_id']);
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $playerIds = Player::where('carrier_id',$this->carrier->id)->where('user_name','like','%'.$input['user_name'].'%')->pluck('player_id')->toArray();
            if(count($playerIds)){
                $query->whereIn('inf_player_game_account.player_id',$playerIds);
            } else{
                $query->where('inf_player_game_account.player_id','');
            }
        }

        if(isset($input['account_user_name']) && !empty($input['account_user_name'])){
            $query->where('inf_player_game_account.account_user_name','like','%'.$input['account_user_name'].'%');
        }

        if(isset($input['player_id']) && !empty($input['player_id'])){
            $query->where('inf_player_game_account.player_id',$input['player_id']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        $mainGamePlats    = MainGamePlat::all();
        $plats            = [];
        foreach ($mainGamePlats as $key => $value) {
            $row                          = [];
            $row['main_game_plat_code'] = $value->main_game_plat_code;
            $row['value']               = $value->alias;
            $plats[]                    = $row;
        }

        return $this->returnApiJson("操作成功", 1,['data' => $data,'plats'=>$plats, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function cancelMobileCode($playerId)
    {       
        $existPlayer = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        if(!$existPlayer){
            return $this->returnApiJson("对不起,此用户不存在", 0);
        }

        cache()->put('clear_mobile_sms_'.$playerId,1,now()->addMinutes(3));

        return $this->returnApiJson("操作成功", 1);
    }

    public function memberBankList()
    {
        $res = PlayerBankCard::memberBankList($this->carrier);
        if(is_array($res)){
            return $this->returnApiJson('操作成功', 1,$res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function memberAlipayList()
    {
        $res = PlayerAlipay::memberAlipayList($this->carrier);
        if(is_array($res)){
            return $this->returnApiJson('操作成功', 1,$res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function memberDigitalAddressList()
    {
        $res = PlayerDigitalAddress::memberDigitalAddressList($this->carrier);
        if(is_array($res)){
            return $this->returnApiJson('操作成功', 1,$res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function freezeArbitrage($id)
    {
        $existPlayerWithdraw =  PlayerWithdraw::where('carrier_id',$this->carrier->id)->where('id',$id)->first();
        if(!$existPlayerWithdraw){
            return $this->returnApiJson("对不起，此订单不存在", 0);
        }

        if(in_array($existPlayerWithdraw->status,[1,2,5,6,7])){
            return $this->returnApiJson("对不起，此订单状态不正确", 0);
        }

        $enableSafeBox          = CarrierCache::getCarrierMultipleConfigure($existPlayerWithdraw->carrier_id,'enable_safe_box',$existPlayerWithdraw->prefix);
        $agentSingleBackground  = CarrierCache::getCarrierMultipleConfigure($existPlayerWithdraw->carrier_id,'agent_single_background',$existPlayerWithdraw->prefix);

        try {
            \DB::beginTransaction();           
            $existPlayerWithdraw->status = 7;
            $existPlayerWithdraw->save();

            //取消提现逻辑
            $playerAccount                                   = PlayerAccount::where('player_id',$existPlayerWithdraw->player_id)->lockForUpdate()->first();
            $player                                          = Player::where('player_id',$existPlayerWithdraw->player_id)->first();
            $language                                        = CarrierCache::getLanguageByPrefix($player->prefix);

            $playerTransfer                                  = new PlayerTransfer();
            $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
            $playerTransfer->prefix                          = $player->prefix;
            $playerTransfer->rid                             = $playerAccount->rid;
            $playerTransfer->top_id                          = $playerAccount->top_id;
            $playerTransfer->parent_id                       = $playerAccount->parent_id;
            $playerTransfer->player_id                       = $playerAccount->player_id;
            $playerTransfer->is_tester                       = $playerAccount->is_tester;
            $playerTransfer->level                           = $playerAccount->level;
            $playerTransfer->user_name                       = $playerAccount->user_name;
            $playerTransfer->mode                            = 3;
            $playerTransfer->project_id                      = $existPlayerWithdraw->pay_order_number;
            $playerTransfer->day_m                           = date('Ym',time());
            $playerTransfer->day                             = date('Ymd',time());
            $playerTransfer->amount                          = $existPlayerWithdraw->amount;
            $playerTransfer->type                            = 'withdraw_cancel';
            $playerTransfer->type_name                       = config('language')[$language]['text53'];

            if($enableSafeBox || ($agentSingleBackground==1 &&  $existPlayerWithdraw->is_agent==1)){
                $playerTransfer->before_balance                  = $playerAccount->balance;
                $playerTransfer->balance                         = $playerAccount->balance;
                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                $playerTransfer->agent_balance                   = $playerAccount->agentbalance + $existPlayerWithdraw->amount;
                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen  - $existPlayerWithdraw->amount;
                $playerTransfer->save();

                $playerAccount->agentbalance                     = $playerTransfer->agent_balance;
                $playerAccount->agentfrozen                      = $playerTransfer->agent_frozen_balance;
                $playerAccount->save();

            } else{
                $playerTransfer->before_balance                  = $playerAccount->balance;
                $playerTransfer->balance                         = $playerAccount->balance + $existPlayerWithdraw->amount;
                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                $playerTransfer->frozen_balance                  = $playerAccount->frozen - $existPlayerWithdraw->amount;

                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                $playerTransfer->save();

                $playerAccount->balance                         = $playerTransfer->balance;
                $playerAccount->frozen                          = $playerTransfer->frozen_balance;
                $playerAccount->save();
            }

            //冻结帐户并拉入套利层级
            $playerLevel = PlayerLevel::where('carrier_id',$existPlayerWithdraw->carrier_id)->where('prefix',$existPlayerWithdraw->prefix)->where('groupname','套利')->first();
            Player::where('player_id',$existPlayerWithdraw->player_id)->update(['frozen_status'=>4,'player_group_id'=>$playerLevel->id]);    
            \DB::commit();
            return $this->returnApiJson("操作成功", 1);
        } catch (\Exception $e) {
            \DB::rollback();
            Clog::recordabnormal('拉入套利异常:'.$e->getMessage()); 
            return $this->returnApiJson("系统异常", 0);;
        }
    }

    public function liveStreamingChange($playerId=0)
    {
        $player      = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        $materialIds = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'materialIds',$player->prefix);

        if($player){
            $materialIdsArr   = explode(',',$materialIds);
            if($player->is_hedging_account){
                return $this->returnApiJson("对不起，对冲号不能设成直播号", 0);
            } elseif(in_array($player->player_id,$materialIdsArr)){
                return $this->returnApiJson("对不起，素材号不能设成直播号", 0);
            } else{
                $player->is_live_streaming_account = $player->is_live_streaming_account ? 0 : 1;
                $player->save();
                return $this->returnApiJson("操作成功", 1);
            }
        } else{
            return $this->returnApiJson("对不起，此用户不存在", 0);
        }
    }

    public function hedgingChange($playerId=0)
    {
        $player = Player::where('carrier_id',$this->carrier->id)->where('player_id',$playerId)->first();
        $materialIds = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'materialIds',$player->prefix);
        if($player){
            $materialIdsArr   = explode(',',$materialIds);
            if($player->is_hedging_account==0){
                if($player->is_live_streaming_account){
                    return $this->returnApiJson("对不起，直播号不能设成对冲号", 0);
                } elseif(in_array($player->player_id,$materialIdsArr)){
                    return $this->returnApiJson("对不起，素材号不能设成对冲号", 0);
                } else{
                    $player->is_hedging_account = 1;
                    $player->save();
                    PlayerWithdraw::where('player_id',$player->player_id)->update(['is_hedging_account'=>1]);
                    PlayerDepositPayLog::where('player_id',$player->player_id)->update(['is_hedging_account'=>1]);
                    
                    return $this->returnApiJson("操作成功", 1);
                }
            } else{
                $player->is_hedging_account = 0;
                $player->save();
            }
            
            
        } else{
            return $this->returnApiJson("对不起，此用户不存在", 0);
        }
    }

    public function performanceInquire()
    {
        $input = request()->all();

        if(!isset($input['day']) || !strtotime($input['day'])){
            return $this->returnApiJson('对不起，日期不能为空', 0);
        }

        $input['day']            = date('Ymd',strtotime($input['day']));

        $data                    = [];
        $playerIds               = [];
        $parentsArr              = [];

        $defaultUserName         = CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name');
        
        //查询出业绩最好的
        $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),'parent_id')->where('day',$input['day'])->groupby('parent_id')->get()->toArray();

        if(count($playerBetFlowMiddle)){
            $flag              = [];
            foreach ($playerBetFlowMiddle as $key => $value) {
                $flag[]      = $value['process_available_bet_amount']; 
                $playerIds[] = $value['parent_id'];
            }
            array_multisort($flag, SORT_DESC, $playerBetFlowMiddle);

            $players = Player::whereIn('player_id',$playerIds)->get();

            foreach ($players as $key => $value) {
               if($value->user_name != $defaultUserName){
                  $parentsArr[$value->player_id] = $value->user_name;
               }
            }
            $i=1;
            foreach ($playerBetFlowMiddle as $key => $value) {
                if($i==10){
                    break;
                }

                $row                                 = [];
                $row['user_name']                    = $parentsArr[$value['parent_id']];
                $row['process_available_bet_amount'] = bcdiv($value['process_available_bet_amount'],1,2);
                $data[]                              = $row;
            }
        }

        return $this->returnApiJson("操作成功", 1,$data);
    }

    public function sameAgentBetflow($id)
    {
        $playerWithdraw = PlayerWithdraw::where('id',$id)->first();
        if(!$playerWithdraw){
            return $this->returnApiJson("对不起，这条数据不存在", 0);
        }

        $alLPlayerIds   = PlayerWithdraw::where('parent_id',$playerWithdraw->parent_id)->where('created_at','>=',date('Y-m-d',strtotime($playerWithdraw->created_at)).' 00:00:00')->where('created_at','<=',date('Y-m-d',strtotime($playerWithdraw->created_at)).' 23:59:59')->whereIn('status',[1,2])->pluck('player_id')->toArray();

        $playerDepositPayAmount = [];
        $playerDepositTime      = [];
        $playerWithdrawAmount   = [];
        $data                   = [];
        foreach ($alLPlayerIds as $key => $value) {
            $playerDepositPayLog = PlayerDepositPayLog::where('player_id',$value)->where('status',1)->orderBy('id','desc')->first();

            if($playerDepositPayLog){
                $playerDepositPayAmount[$value] =  $playerDepositPayLog->amount;
                $playerDepositTime[$value]      = $playerDepositPayLog->review_time;
            } else{
                $playerDepositPayAmount[$value] = 0;
                $playerDepositTime[$value]      = 0;
            }
        }

        foreach ($alLPlayerIds as $key => $value) {
            $playerWithdraw               = PlayerWithdraw::where('player_id',$value)->whereIn('status',[1,2])->orderBy('id','desc')->first();
            $playerWithdrawAmount[$value] =  $playerWithdraw->amount;
        }

        foreach ($alLPlayerIds as $key => $value) {
            if($playerDepositTime[$value]!=0){
                $playerBetFlow = PlayerBetFlow::select(\DB::raw('count(game_id) as number'),\DB::raw('count(company_win_amount) as company_win_amount'),'game_name','player_id','main_game_plat_code')->where('bet_time','>=',$playerDepositTime[$value])->where('player_id',$value)->groupBy('game_id')->get();
            } else{
                $playerBetFlow = PlayerBetFlow::select(\DB::raw('count(game_id) as number'),\DB::raw('count(company_win_amount) as company_win_amount'),'game_name','player_id','main_game_plat_code')->where('player_id',$value)->groupBy('game_id')->get();
            }

            if(count($playerBetFlow)>0){
                $data[$value]['betflow']          = $playerBetFlow;
                $data[$value]['depositPayAmount'] = $playerDepositPayAmount[$value];
                $data[$value]['withdrawAmount']   = $playerWithdrawAmount[$value];
            }
        }

        return $this->returnApiJson('操作成功', 1,$data);
    }

    public function changeStatuSupplementary($playerId=0)
    {
        $player = Player::where('player_id',$playerId)->first();
        if(!$player->win_lose_agent){
            return $this->returnApiJson('对不起,仅负盈利代理才能使用此功能',0);
        }

        $player->is_supplementary_data =  $player->is_supplementary_data ? 0:1;
        $player->save();
        
        return $this->returnApiJson('操作成功', 1);
    }

    public function regressList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        if(!isset($input['prefix']) || empty($input['prefix'])){
            return $this->returnApiJson('对不起，站点不能为空', 0);
        }

        if(!isset($input['regressDays']) || empty($input['regressDays']) || intval($input['regressDays']) != $input['regressDays'] || $input['regressDays'] <0 ){
            return $this->returnApiJson('对不起，未登录天数取值不正确', 0);
        }

        $rechargePlayerIds     = PlayerTransfer::where('prefix',$input['prefix'])->where('type','recharge')->pluck('player_id')->toArray();
        $rechargePlayerIds     = array_unique($rechargePlayerIds);
        $loginDate             = date('Y-m-d',strtotime('-'.$input['regressDays'].' days')).' 00:00:00';
        $agentSingleBackground = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'agent_single_background',$input['prefix']);

        if($agentSingleBackground==1){
            $query = Player::select('player_id','top_id','parent_id','user_name','login_at','created_at','prefix')->whereIn('player_id',$rechargePlayerIds)->where('login_at','<=',$loginDate)->where('win_lose_agent',0);
        } else {
            $query = Player::select('player_id','top_id','parent_id','user_name','login_at','created_at','prefix')->whereIn('player_id',$rechargePlayerIds)->where('login_at','<=',$loginDate);
        }

        $total   = $query->count();
        $items   = $query->skip($offset)->take($pageSize)->get();

        $carrierPreFixDomain    = CarrierPreFixDomain::where('carrier_id',$this->carrier->id)->get();
        $carrierPreFixDomainArr = [];
        foreach ($carrierPreFixDomain as $key => $value) {
            $carrierPreFixDomainArr[$value->prefix] = $value->name;
        }

        foreach ($items as $k => &$v) {
            $v->multiple_name = $carrierPreFixDomainArr[$v->prefix];
        }

        return $this->returnApiJson('操作成功', 1,['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function sendRegress()
    {
        $input = request()->all();
        if(!isset($input['prefix']) || empty($input['prefix'])){
            return $this->returnApiJson('对不起，站点不能为空', 0);
        }

        if(!isset($input['regressDays']) || empty($input['regressDays']) || intval($input['regressDays']) != $input['regressDays'] || $input['regressDays'] <0 ){
            return $this->returnApiJson('对不起，未登录天数取值不正确', 0);
        }

        if(!isset($input['probability']) || !is_numeric($input['probability'])|| $input['probability']>100 || $input['probability']<0 ){
            return $this->returnApiJson('对不起，概率取值不正确', 0);
        }

        if(!isset($input['money']) || !is_numeric($input['money'])|| $input['money']<=0){
            return $this->returnApiJson('对不起，金额取值不正确', 0);
        }

        if(!isset($input['turnover_multiple']) || !is_numeric($input['turnover_multiple'])|| $input['turnover_multiple']<=0 || intval($input['turnover_multiple']) != $input['turnover_multiple']){
            return $this->returnApiJson('对不起，流水倍数取值不正确', 0);
        }   

        $rechargePlayerIds     = PlayerTransfer::where('prefix',$input['prefix'])->where('type','recharge')->pluck('player_id')->toArray();
        $rechargePlayerIds     = array_unique($rechargePlayerIds);
        $loginDate             = date('Y-m-d',strtotime('-'.$input['regressDays'].' days')).' 00:00:00';
        $agentSingleBackground = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'agent_single_background',$input['prefix']);

        if($agentSingleBackground==1){
            $playerIds = Player::whereIn('player_id',$rechargePlayerIds)->where('login_at','<=',$loginDate)->where('win_lose_agent',0)->pluck('player_id')->toArray();
        } else {
            $playerIds = Player::whereIn('player_id',$rechargePlayerIds)->where('login_at','<=',$loginDate)->pluck('player_id')->toArray();
        }

        foreach ($playerIds as $key => $value) {
            $rand                  = rand(0,100);
            if($rand > $input['probability']){
                continue;
            }
            $cacheKey    = "player_" .$value;
            $redisLock   = Lock::addLock($cacheKey,60);

            if (!$redisLock) {
                \Log::info('用户发放回归礼金已加锁，不能重复加锁');
                return $this->returnApiJson('对不起，用户发放回归礼金已加锁，不能重复加锁', 0);
               
            } else {
                try {
                    \DB::beginTransaction();
                    $playerAccount                                   = PlayerAccount::where('player_id',$value)->lockForUpdate()->first();
                                        
                    $playerTransfer                                  = new PlayerTransfer();
                    $playerTransfer->prefix                          = $input['prefix'];
                    $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                    $playerTransfer->rid                             = $playerAccount->rid;
                    $playerTransfer->top_id                          = $playerAccount->top_id;
                    $playerTransfer->parent_id                       = $playerAccount->parent_id;
                    $playerTransfer->player_id                       = $playerAccount->player_id;
                    $playerTransfer->is_tester                       = $playerAccount->is_tester;
                    $playerTransfer->level                           = $playerAccount->level;
                    $playerTransfer->user_name                       = $playerAccount->user_name;
                    $playerTransfer->mode                            = 1;
                    $playerTransfer->type                            = 'regress_gift';
                    $playerTransfer->type_name                       = '回归礼金';
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $input['money']*10000;
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;;
                    $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;;
                    $playerTransfer->save();

                    $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                    $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                    $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                    $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                    $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                    $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;
                    $playerWithdrawFlowLimit->limit_type             = 50;
                    $playerWithdrawFlowLimit->limit_amount           = $playerTransfer->amount*$input['turnover_multiple'];
                    $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                    $playerWithdrawFlowLimit->is_finished            = 0;
                    $playerWithdrawFlowLimit->operator_id            = 0;
                    $playerWithdrawFlowLimit->save();

                    $playerAccount->balance                          = $playerTransfer->balance;
                    $playerAccount->save();

                    \DB::commit();
                    Lock::release($redisLock);
                    return $this->returnApiJson('操作成功', 1);
                } catch (\Exception $e) {
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('发放回归礼金异常:'.$e->getMessage());   
                    return $this->returnApiJson('对不起，系统异常'.$e->getMessage(), 0);
                }
            }   
        }

        return $this->returnApiJson('操作成功', 1);
    }
}

