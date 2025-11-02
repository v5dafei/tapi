<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Conf\CarrierWebSite;
use App\Models\Conf\PlayerSetting;
use App\Models\Log\PlayerBetFlow;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerLogin;
use App\Models\Log\PlayerOperate;
use App\Models\Log\PlayerTransferCasino;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerSignIn;
use App\Models\Log\PlayerSignInReceive;
use App\Models\Log\RemainQuota;
use App\Models\Log\CarrierSms;
use App\Models\Log\PlayerFingerprint;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Log\PlayerCapitationFee;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\Player;
use App\Models\PlayerActivityAudit;
use App\Models\PlayerBankCard;
use App\Models\PlayerDigitalAddress;
use App\Models\PlayerGameAccount;
use App\Models\PlayerInviteCode;
use App\Models\PlayerMessage;
use App\Models\PlayerRecent;
use App\Models\PlayerBreakThrough;
use App\Models\Carrier;
use App\Models\PlayerGameCollect;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\Log\ThirdPartPayCallBack;
use App\Models\Log\PlayerGiftCode;
use App\Models\PlayerCommission;
use App\Models\PlayerBetflowCalculate;
use App\Models\PlayerHoldGiftCode;
use App\Models\Report\ReportRealPlayerEarnings;
use App\Models\PlayerRealCommission;
use App\Models\Log\PlayerRealDividendTongbao;
use App\Models\Log\PlayerRealCommissionTongbao;


class TestSeederCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'testSeeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'testSeeder';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $playerId  = '';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $defaultUserName = CarrierCache::getCarrierConfigure(10000023,'default_user_name');
        $levels          = Player::where('prefix','O')->orderBy('level','desc')->groupBy('level')->pluck('level')->toArray();
        foreach ($levels as $key => $value) {
            $playerIds = Player::where('prefix','O')->where('user_name','!=',$defaultUserName)->where('level',$value)->pluck('player_id')->toArray();
            foreach ($playerIds as $key1 => $value1) {
                $existPlayerDepositPayLog = PlayerDepositPayLog::where('player_id',$value1)->where('status',1)->first();
                $existPlayer              = Player::where('parent_id',$value1)->first();
                if(!$existPlayerDepositPayLog && !$existPlayer){
                    $playerIds[] = $value1;
                    PlayerCommission::whereIn('player_id',$playerIds)->delete();
                    PlayerCapitationFee::whereIn('player_id',$playerIds)->delete();
                    PlayerSetting::whereIn('player_id',$playerIds)->delete();
                    PlayerBetflowCalculate::whereIn('player_id',$playerIds)->delete();
                    PlayerAccount::whereIn('player_id',$playerIds)->delete();
                    PlayerActivityAudit::whereIn('player_id',$playerIds)->delete();
                    PlayerBankCard::whereIn('player_id',$playerIds)->delete();
                    PlayerDigitalAddress::whereIn('player_id',$playerIds)->delete();
                    PlayerGameAccount::whereIn('player_id',$playerIds)->delete();
                    PlayerInviteCode::whereIn('player_id',$playerIds)->delete();
                    PlayerMessage::whereIn('player_id',$playerIds)->delete();
                    PlayerRecent::whereIn('player_id',$playerIds)->delete();
                    PlayerTransfer::whereIn('player_id',$playerIds)->delete();
                    PlayerBetFlow::whereIn('player_id',$playerIds)->delete();
                    PlayerBetFlowMiddle::whereIn('player_id',$playerIds)->delete();
                    PlayerDepositPayLog::whereIn('player_id',$playerIds)->delete();
                    PlayerLogin::whereIn('player_id',$playerIds)->delete();
                    PlayerOperate::whereIn('player_id',$playerIds)->delete();
                    PlayerTransferCasino::whereIn('player_id',$playerIds)->delete();
                    PlayerWithdraw::whereIn('player_id',$playerIds)->delete();
                    PlayerWithdrawFlowLimit::whereIn('player_id',$playerIds)->delete();
                    ReportPlayerEarnings::whereIn('player_id',$playerIds)->delete();
                    ReportPlayerStatDay::whereIn('player_id',$playerIds)->delete();
                    PlayerSignIn::whereIn('player_id',$playerIds)->delete();
                    PlayerSignInReceive::whereIn('player_id',$playerIds)->delete();
                    PlayerBreakThrough::whereIn('player_id',$playerIds)->delete();
                    PlayerFingerprint::whereIn('player_id',$playerIds)->delete();
                    PlayerGameCollect::whereIn('player_id',$playerIds)->delete();
                    PlayerReceiveGiftCenter::whereIn('player_id',$playerIds)->delete();
                    PlayerGiftCode::whereIn('player_id',$playerIds)->delete();
                    PlayerHoldGiftCode::whereIn('player_id',$playerIds)->delete();
                    ReportRealPlayerEarnings::whereIn('player_id',$playerIds)->delete();
                    PlayerRealCommission::whereIn('player_id',$playerIds)->delete();
                    PlayerRealDividendTongbao::whereIn('player_id',$playerIds)->delete();
                    PlayerRealCommissionTongbao::whereIn('player_id',$playerIds)->delete();
                    Player::whereIn('player_id',$playerIds)->delete();
                }
            }
        }
    }

    public static function calculateDividend($user,$startDate=null,$endDate=null)
    {
        $playerRealTimeDividendsStartDay               = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'player_realtime_dividends_start_day',$user->prefix);

        if(!is_null($startDate)){
            $startTime                                 = date('Y-m-d',strtotime($startDate)).' 00:00:00';
            $endTime                                   = date('Y-m-d',strtotime($endDate)).'23:59:59';
        } else{
            $startDate                                 = date('Ymd',strtotime($playerRealTimeDividendsStartDay));
            $endDate                                   = date('Ymd');
            $startTime                                 = date('Y-m-d',strtotime($playerRealTimeDividendsStartDay)).' 00:00:00';
            $endTime                                   = date('Y-m-d').'23:59:59';
        }

        $stock                                     = 0;
        $teamstock                                 = 0;
        $teamStockDiff                             = 0;
        $directlyunderRechargeAmount               = 0;
        $directlyunderWithdrawAmount               = 0;
        $directlyundervenueFee                     = 0;
        $directlyunderGift                         = 0;
        $directlyunderCommission                   = 0;
        $directlyunderCompanyWinAmount             = 0;
        $directlyunderDividend                     = 0;
        $directlyunderDiscountFee                  = 0;
        $directlyunderVenuesFee                    = 0;
        $teamRechargeAmount                        = 0;
        $teamWithdrawAmount                        = 0;
        $teamvenueFee                              = 0;
        $teamGift                                  = 0;
        $teamCommission                            = 0;
        $teamCompanyWinAmount                      = 0;
        $teamDiscountFee                           = 0;
        $teamVenuesFee                             = 0;
        $selfRechargeAmount                        = 0;
        $selfWithdrawAmount                        = 0;
        $selfvenueFee                              = 0;
        $selfGift                                  = 0;
        $selfCommission                            = 0;
        $selfCompanyWinAmount                      = 0;
        $selfDividend                              = 0;
        $data                                      = [];
        $data['teamStockChange']                   = 0;
        $data['selfStockChange']                   = 0;
        $data['directlyunderStockChange']          = 0;
        $data['teamDividend']                      = 0;

        $bonusRate                = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'bonus_rate',$user->prefix);


        //游戏平台点位
        $carrierGamePlats         = CarrierPreFixGamePlat::where('carrier_id',$user->carrier_id)->where('prefix',$user->prefix)->get();
        $gamePlatPoints           = [];

        foreach ($carrierGamePlats as $key => $value) {
            $gamePlatPoints[$value->game_plat_id] = $value->point;
        }

        $selfPlayerSetting               = PlayerSetting::where('player_id',$user->player_id)->first();
        $reportPlayerEarnings            = ReportPlayerEarnings::where('player_id',$user->player_id)->orderBy('id','desc')->first();
                        
        if($reportPlayerEarnings){
            $lastaccumulation    = $reportPlayerEarnings->accumulation;
        } else {
            $lastaccumulation    = 0;
        }

        //自已设置了分红自已数据算自已的
        if($selfPlayerSetting->earnings >0){
            //自已的充提
            $selfRecharge =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

            if($selfRecharge && !is_null($selfRecharge->amount)){
                $selfRechargeAmount        = $selfRecharge->amount;
            }

            $selfWithdraw =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->where('type','withdraw_finish')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

            if($selfWithdraw && !is_null($selfWithdraw->amount)){
                $selfWithdrawAmount        = $selfWithdraw->amount;
            }

            //活动新增
            $selfPlayerTransferGift      = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->whereIn('type',config('main')['giftadd'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first(); 

            //活动扣减
            $selfReducePlayerTransferGift = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('player_id',$user->player_id)->whereIn('type',['gift_transfer_reduce','inside_transfer_to'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first(); 

            if($selfPlayerTransferGift && !is_null($selfPlayerTransferGift->amount)){
                $selfGift += $selfPlayerTransferGift->amount;
            }

            if($selfReducePlayerTransferGift && !is_null($selfReducePlayerTransferGift->amount)){
                $selfGift -= $selfReducePlayerTransferGift->amount;
            }

            //游戏输赢
            $selfCompanyWinAmountGift     = PlayerBetFlowMiddle::select(\DB::raw('sum(company_win_amount) as company_win_amount'))->where('player_id',$user->player_id)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

            //游戏输赢
            if($selfCompanyWinAmountGift && !is_null($selfCompanyWinAmountGift->company_win_amount)){
                $selfCompanyWinAmount  = -$selfCompanyWinAmountGift->company_win_amount*10000;
            }

            //自营库存变化
            $data['selfStockChange']   = $selfRechargeAmount + $selfGift + $selfCompanyWinAmount - $selfWithdrawAmount;

            //彩金费
            $discountFee               = 0;
            $discountPlayerAmount      = PlayerTransfer::where('player_id',$user->player_id)->whereIn('type',config('main')['giftdeduction'])->where('day','>=',$startDate)->where('day','<=',$endDate)->sum('amount');
            if($discountPlayerAmount && $discountPlayerAmount > 0){
                $discountFee = bcdiv($bonusRate*$discountPlayerAmount,100,0);
            }

            //场馆费
            $venuesFee                = 0;
            $playerBetFlowMiddles     = PlayerBetFlowMiddle::select('main_game_plat_id',\DB::raw('sum(company_win_amount) as company_win_amount'))->where('player_id',$user->player_id)->where('day','>=',$startDate)->where('day','<=',$endDate)->groupBy('main_game_plat_id')->get(); 

            foreach ($playerBetFlowMiddles as $key => $value) {
                if($value->company_win_amount > 0){
                    $venuesFee += $value->company_win_amount*$gamePlatPoints[$value->main_game_plat_id]*100;
                }
            }

            $directlyunderDiscountFee                  = $discountFee;
            $directlyunderVenuesFee                    = $venuesFee;

            //自营分红  
            $selfDividend              = bcdiv(($selfRechargeAmount- $selfWithdrawAmount - $data['selfStockChange']- $discountFee - $venuesFee)*$selfPlayerSetting->earnings,100,2) ;
        }

        //直属即为未开代理的直属下级
        $subordinateUnderdirectRids = PlayerSetting::where('parent_id',$user->player_id)->where('earnings',0)->pluck('rid')->toArray();
        $tempPlayerIds              = PlayerSetting::where('earnings',0)->where('rid','like',$user->rid.'%')->pluck('rid')->toArray();
        $directlyunderPlayerIds     = [];

        foreach ($subordinateUnderdirectRids as $key => $value) {
            foreach ($tempPlayerIds as $key1 => $value1) {
                if(strstr($value1,$value)!==false){
                    $tempAll = explode('|',$value1);
                    $directlyunderPlayerIds[] = end($tempAll);
                }
            }
        }

        $directlyunderRecharge                          =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$directlyunderPlayerIds)->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first();;
        $directlyunderRechargePeopleNumber              =  PlayerTransfer::where('type','recharge')->where('day','>=',$startDate)->where('day','<=',$endDate)->whereIn('player_id',$directlyunderPlayerIds)->pluck('player_id')->toArray();
        $data['directlyunder_recharge_people_number']   = count(array_unique($directlyunderRechargePeopleNumber));
        $registerPeopleNumber                           = Player::where('day','>=',$startDate)->where('day','<=',$endDate)->whereIn('player_id',$directlyunderPlayerIds)->pluck('player_id')->toArray();
        $data['register_people_number']                 = count(array_unique($registerPeopleNumber));
        $data['directlyunder_people_number']            = $user->soncount;

        if($directlyunderRecharge && !is_null($directlyunderRecharge->amount)){
            $directlyunderRechargeAmount        += $directlyunderRecharge->amount;
        }

        //直属提现
        $directlyunderWithdraw =  PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$directlyunderPlayerIds)->where('type','withdraw_finish')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        //直属提现金额
        if($directlyunderWithdraw && !is_null($directlyunderWithdraw->amount)){
            $directlyunderWithdrawAmount        += $directlyunderWithdraw->amount;
        } 

        //活动礼金
        $playerTransferGift      = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$directlyunderPlayerIds)->whereIn('type',config('main')['giftadd'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first(); 

        //活动扣减
        $reducePlayerTransferGift = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->whereIn('player_id',$directlyunderPlayerIds)->whereIn('type',['gift_transfer_reduce','inside_transfer_to'])->where('day','>=',$startDate)->where('day','<=',$endDate)->first(); 

        //游戏输赢
        $companyWinAmountGift     = PlayerBetFlowMiddle::select(\DB::raw('sum(company_win_amount) as company_win_amount'))->whereIn('player_id',$directlyunderPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if($playerTransferGift && !is_null($playerTransferGift->amount)){
            $directlyunderGift += $playerTransferGift->amount;
        }

        if($reducePlayerTransferGift && !is_null($reducePlayerTransferGift->amount)){
            $directlyunderGift -= $reducePlayerTransferGift->amount;
        }

        //游戏输赢
        if($companyWinAmountGift && !is_null($companyWinAmountGift->company_win_amount)){
            $directlyunderCompanyWinAmount  = $directlyunderCompanyWinAmount -$companyWinAmountGift->company_win_amount*10000;
        }

        //直属库存变化
        $data['directlyunderStockChange']   =  $directlyunderRechargeAmount + $directlyunderGift + $directlyunderCompanyWinAmount - $directlyunderWithdrawAmount;

        //彩金费
        $discountFee               = 0;
        $discountPlayerAmount      = PlayerTransfer::whereIn('player_id',$directlyunderPlayerIds)->whereIn('type',config('main')['giftdeduction'])->where('day','>=',$startDate)->where('day','<=',$endDate)->sum('amount');
        if($discountPlayerAmount && $discountPlayerAmount > 0){
            $discountFee = bcdiv($bonusRate*$discountPlayerAmount,100,0);
        }

        //场馆费
        $venuesFee                = 0;
        $playerBetFlowMiddles     = PlayerBetFlowMiddle::select('main_game_plat_id',\DB::raw('sum(company_win_amount) as company_win_amount'))->whereIn('player_id',$directlyunderPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->groupBy('main_game_plat_id')->get(); 

        foreach ($playerBetFlowMiddles as $key => $value) {
            if($value->company_win_amount > 0){
                $venuesFee += $value->company_win_amount*$gamePlatPoints[$value->main_game_plat_id]*100;
            }
        }

        $directlyunderDiscountFee           += $discountFee;
        $directlyunderVenuesFee             += $venuesFee;
        $directlyunderDividend              = bcdiv(($directlyunderRechargeAmount - $directlyunderWithdrawAmount- $data['directlyunderStockChange'] - $discountFee - $venuesFee)*$selfPlayerSetting->earnings,100,2) ;

        ///////////////////////////////////

        $allRids                       = PlayerSetting::where('rid','like',$user->rid.'|%')->pluck('rid')->toArray();
        //查询所有的直属
        $subordinateTeamPlayerIds      = PlayerSetting::where('parent_id',$user->player_id)->where('earnings','>',0)->pluck('player_id')->toArray();
        $playerSons                    = [];
        $grandsonPlarIds               = [];
        foreach ($allRids as $key => $value) {
            foreach ($subordinateTeamPlayerIds as $key1 => $value1) {
                if(strpos($value,strval($value1)) !== false ){
                    $arr               = explode('|',$value);
                    $tmp               = end($arr);
                    $grandsonPlarIds[] = $tmp;
                    $playerSons[$tmp]  = $value1;
                }
            }
        }

        $allPlayers                         = PlayerSetting::where('rid','like',$user->rid.'|%')->pluck('player_id')->toArray();
        $teamPlayerIds                     = array_diff($allPlayers, $directlyunderPlayerIds);

        //直属充值
        $summaryTeamRecharge             =  PlayerTransfer::select('amount','player_id')->whereIn('player_id',$teamPlayerIds)->whereIn('type',['recharge','dividend_from_parent','agent_reimbursement'])->where('day','>=',$startDate)->where('day','<=',$endDate)->get();

        //直属提现
        $summaryTeamWithdraw             =  PlayerTransfer::select('amount','player_id')->whereIn('player_id',$teamPlayerIds)->where('type','withdraw_finish')->where('day','>=',$startDate)->where('day','<=',$endDate)->get();

        //活动礼金
        $summaryPlayerTransferGift       = PlayerTransfer::select('amount','player_id')->whereIn('player_id',$teamPlayerIds)->whereIn('type',config('main')['giftadd'])->where('day','>=',$startDate)->where('day','<=',$endDate)->get(); 

        //活动扣减
        $summaryReducePlayerTransferGift = PlayerTransfer::select('amount','player_id')->whereIn('player_id',$teamPlayerIds)->whereIn('type',['gift_transfer_reduce','inside_transfer_to'])->where('day','>=',$startDate)->where('day','<=',$endDate)->get(); 

        //佣金
        $summaryPlayerCommissionGift     = PlayerCommission::select('amount','player_id')->whereIn('player_id',$teamPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->get();

        //游戏输赢
        $summaryCompanyWinAmountGift     = PlayerBetFlowMiddle::select('company_win_amount','player_id')->whereIn('player_id',$teamPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->get();

        //场馆费
        $summaryPlayerBetFlowMiddles     = PlayerBetFlowMiddle::select('main_game_plat_id',\DB::raw('sum(company_win_amount) as company_win_amount'),'player_id')->whereIn('player_id',$teamPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->groupBy('player_id','main_game_plat_id')->get(); 

        //彩金费
        $summaryDiscountPlayerAmount      = PlayerTransfer::select(\DB::raw('sum(amount) as amount'),'player_id')->whereIn('player_id',$teamPlayerIds)->whereIn('type',config('main')['giftdeduction'])->where('day','>=',$startDate)->where('day','<=',$endDate)->get();
        
        //团队数据开始
        $directlyUnderPlayerSettings     = PlayerSetting::whereIn('player_id',$subordinateTeamPlayerIds)->get();
        foreach ($directlyUnderPlayerSettings as $k3 => $v3) {
            $tempRechargeAmount   = 0;
            $tempWithdrawAmount   = 0;
            $tempGift             = 0;
            $tempCommission       = 0;
            $tempCompanyWinAmount = 0;

            //直属充值
            foreach ($summaryTeamRecharge as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $teamRechargeAmount        += $value->amount;
                    $tempRechargeAmount        += $value->amount;
                }
            }

            //直属提现
            foreach ($summaryTeamWithdraw as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $teamWithdrawAmount        += $value->amount;
                    $tempWithdrawAmount        += $value->amount;
                }
            }

            //活动礼金
            foreach ($summaryPlayerTransferGift as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $teamGift += $value->amount;
                    $tempGift += $value->amount;
                }
            }

            //活动扣减
            foreach ($summaryReducePlayerTransferGift as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $teamGift -= $value->amount;
                    $tempGift -= $value->amount;
                }
            }

            //佣金
            foreach ($summaryPlayerCommissionGift as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    $teamCommission  += $value->amount;
                    $tempCommission  += $value->amount;
                }
            }

            //游戏输赢
            foreach ($summaryCompanyWinAmountGift as $key => $value) {
                if(!is_null($value->company_win_amount) && $playerSons[$value->player_id]== $v3->player_id){
                    $teamCompanyWinAmount        = $teamCompanyWinAmount - $value->company_win_amount*10000;
                    $tempCompanyWinAmount        = $tempCompanyWinAmount - $value->company_win_amount*10000;  
                }
            }

            //场馆费
            $venuesFee                = 0;
            foreach ($summaryPlayerBetFlowMiddles as $key => $value) {
                if($playerSons[$value->player_id]== $v3->player_id){
                    if($value->company_win_amount > 0){
                        $venuesFee += $value->company_win_amount*$gamePlatPoints[$value->main_game_plat_id]*100;
                    }
                }
            }

            $teamVenuesFee                             += $venuesFee;

            //彩金费
            $discountFee              = 0;
            foreach ($summaryDiscountPlayerAmount as $key => $value) {
                if(!is_null($value->amount) && $playerSons[$value->player_id]== $v3->player_id){
                    $discountFee += bcdiv($bonusRate*$value->amount,100,0);
                }
            }

            $teamDiscountFee     += $discountFee;

            //团队库存变化   = （团队存款 + 团队活动礼金 + 团队保底  -团队提现 + 团队游戏输赢）*自已的分红比例

            $tempTeamStockChange       = $tempRechargeAmount + $tempGift - $tempWithdrawAmount + $tempCompanyWinAmount;
            $data['teamStockChange']   += $tempTeamStockChange;
            $data['teamDividend']      += bcdiv(($tempRechargeAmount - $tempWithdrawAmount- $tempTeamStockChange - $discountFee - $venuesFee)*($selfPlayerSetting->earnings - $v3->earnings),100,2); 
        }

        if(date('Ymd') >= $endDate){
            $reportPlayerStatDay              = ReportPlayerStatDay::where('player_id',$user->player_id)->where('day',$endDate)->first();
        } else{
            $reportPlayerStatDay              = ReportPlayerStatDay::where('player_id',$user->player_id)->where('day',date('Ymd'))->first();
        }

        if($reportPlayerStatDay){
            $data['directlyunderRecharge']    = $directlyunderRechargeAmount + $selfRechargeAmount;
            $data['directlyunderWithdraw']    = $directlyunderWithdrawAmount + $selfWithdrawAmount;
            $data['directlyunderDividend']    = $directlyunderDividend + $selfDividend;
            $data['directlyunderStock']       = $reportPlayerStatDay->stock + $reportPlayerStatDay->self_stock;
            $data['directlyunderStockChange'] += $data['selfStockChange'];

            $data['selfRecharge']             = 0;
            $data['selfWithdraw']             = 0;
            $data['selfDividend']             = 0;
            $data['selfStock']                = 0;

            $data['teamRecharge']             = $teamRechargeAmount;
            $data['teamWithdraw']             = $teamWithdrawAmount;
            $data['teamStock']                = $reportPlayerStatDay->team_stock;

            $data['totalCommission']          = $data['teamDividend'] + $data['directlyunderDividend'] ;
            $data['allCommission']            = $data['totalCommission'];
            $data['earnings']                 = $selfPlayerSetting->earnings;
            $data['lastaccumulation']         = $lastaccumulation;
            $data['venue_fee']                = $directlyundervenueFee;

            $data['directlyunderDiscountFee'] = $directlyunderDiscountFee;
            $data['directlyunderVenuesFee']   = $directlyunderVenuesFee;

            $data['teamDiscountFee']          = $teamDiscountFee;
            $data['teamVenuesFee']            = $teamVenuesFee;

            \Log::info('生成的值是',$data);
            
            return $data;
        }
    }

    //模式3单个用户的分红
    public static function singlestockCalculateDividend($player)
    {
        $freshPlayerIds                        = Player::where('parent_id',$player->player_id)->where('win_lose_agent',1)->pluck('player_id')->toArray();
        $playerRealTimeDividendsStartDay       = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'player_realtime_dividends_start_day',$player->prefix);
        $playerDividendsDay                    = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'player_dividends_day',$player->prefix);
        $time                                  = 0;
        if($playerDividendsDay==1){
            $time = 345600;
        } elseif($playerDividendsDay==2){
            $time = 518400;
        } elseif($playerDividendsDay==3){
            $time = 172800;
        }

        $startDay                              = date('Ymd',strtotime($playerRealTimeDividendsStartDay));
        $endDay                                = date('Ymd',strtotime($playerRealTimeDividendsStartDay)+$time);
        $startTime                             = date('Y-m-d',strtotime($playerRealTimeDividendsStartDay)).' 00:00:00';
        $endTime                               = date('Y-m-d',strtotime($playerRealTimeDividendsStartDay)+$time).'23:59:59';
        
        $data                                  = [];
        $rows                                  = [];
        $selfSetting                           = PlayerSetting::where('player_id',$player->player_id)->first();
        $reportPlayerEarnings                  = ReportPlayerEarnings::where('player_id',$player->player_id)->orderBy('id','desc')->first();                
        if($reportPlayerEarnings){
            $rows['lastaccumulation']    = $reportPlayerEarnings->accumulation;
        } else {
            $rows['lastaccumulation']    = 0;
        }

        $directlyunderPlayerIds = Player::where('parent_id',$player->player_id)->where('win_lose_agent',0)->pluck('player_id')->toArray();
        $directlyunderPlayerRids = Player::where('parent_id',$player->player_id)->where('win_lose_agent',0)->pluck('rid')->toArray();
        $directlyunderteamQuery  = Player::whereIn('player_id',$directlyunderPlayerIds);

        foreach ($directlyunderPlayerRids as $key => $value) {
            $directlyunderteamQuery->orWhere('rid','like',$value.'|%');
        }

        //直属团队用户ID
        $directlyunderPlayerIds = $directlyunderteamQuery->pluck('player_id')->toArray();

        //先计算直属
        $reportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(page_recharge_amount) as page_recharge_amount'),\DB::raw('sum(dividend) as dividend'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(change_self_stock) as change_self_stock'))
            ->whereIn('player_id',$directlyunderPlayerIds)
            ->where('day','>=',$startDay)
            ->where('day','<=',$endDay)
            ->first();

        $endReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))
            ->whereIn('player_id',$directlyunderPlayerIds)
            ->where('day',$endDay)
            ->first();

        $directlyUnderStock              = 0;
        $directlyunderStockChange        = 0;
        $directlyUnderRechargeAmount     = 0; 
        $directlyUnderWithdrawAmount     = 0;

        if($endReportPlayerStatDay && !is_null($endReportPlayerStatDay->self_stock)){
            $directlyUnderStock = $endReportPlayerStatDay->self_stock;
        }

        if($reportPlayerStatDay && !is_null($reportPlayerStatDay->page_recharge_amount)){
            $directlyUnderRechargeAmount = ($reportPlayerStatDay->page_recharge_amount + $reportPlayerStatDay->dividend)*0.9;
            $directlyUnderWithdrawAmount = $reportPlayerStatDay->withdraw_amount;
            $directlyunderStockChange    = $reportPlayerStatDay->change_self_stock;
        }
                
        $rows['carrier_id']                    = $player->carrier_id;
        $rows['rid']                           = $player->rid;
        $rows['top_id']                        = $player->top_id;
        $rows['parent_id']                     = $player->parent_id;
        $rows['player_id']                     = $player->player_id;
        $rows['is_tester']                     = $player->is_tester;
        $rows['prefix']                        = $player->prefix;
        $rows['user_name']                     = $player->user_name;
        $rows['level']                         = $player->level;
        $rows['inviteplayerid']                = $player->inviteplayerid;
        $rows['descendantscount']              = $player->descendantscount;
        $rows['from_day']                      = $startDay;
        $rows['end_day']                       = $endDay;
        $rows['init_time']                     = time();
        $rows['created_at']                    = date('Y-m-d H:i:s');
        $rows['updated_at']                    = date('Y-m-d H:i:s');
        $rows['venue_fee']                     = 0;
        $rows['earnings']                      = $selfSetting->earnings;
        $rows['directlyunder_stock']           = $directlyUnderStock;
        $rows['directlyunder_stock_change']    = $directlyunderStockChange;
        $rows['directlyunder_recharge_amount'] = $directlyUnderRechargeAmount;
        $rows['directlyunder_withdraw_amount'] = $directlyUnderWithdrawAmount;

        $playerBetFlowCommission         = PlayerCommission::select(\DB::raw('sum(amount) as amount'))->where('player_id',$player->player_id)->where('day','>=',$startDay)->where('day','<=',$endDay)->first();

        if($playerBetFlowCommission && !is_null($playerBetFlowCommission->amount)){
            $rows['venue_fee']                  = $playerBetFlowCommission->amount;
        }

        $directlyUnderDividend                 = bcdiv(($directlyUnderRechargeAmount - $directlyUnderWithdrawAmount- $directlyunderStockChange - $rows['venue_fee'])*$selfSetting->earnings,100,0);
        $sonAgents                             = Player::where('parent_id',$player->player_id)->where('win_lose_agent',1)->get();

        $teamStock                             = 0;
        $teamStockChange                       = 0;
        $teamRechargeAmount                    = 0; 
        $teamWithdrawAmount                    = 0;
        $teamDividend                          = 0;

        $sonAgentPlayerIds = [];
        foreach ($sonAgents as $key => $value) {
            $sonAgentPlayerIds[] = $value->player_id;
        }

        $sonPlayerSettings    = PlayerSetting::whereIn('player_id',$sonAgentPlayerIds)->get();
        $sonPlayerSettingsArr = [];
        foreach ($sonPlayerSettings as $key => $value) {
            $sonPlayerSettingsArr[$value->player_id] = $value->earnings;
        }

        foreach ($sonAgents as $key => $value) {
            //团队分红
            $reportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(page_recharge_amount) as page_recharge_amount'),\DB::raw('sum(dividend) as dividend'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(change_self_stock) as change_self_stock'))
                ->where('rid','like',$value->rid.'%')
                ->where('day','>=',$startDay)
                ->where('day','<=',$endDay)
                ->first();

            $rechangeAmount  = 0;
            $withdrawAmount  = 0;
            $changeStock     = 0;
            $stock           = 0;
            $selfstock       = 0;
            $venueFee        = 0;

            if($reportPlayerStatDay && !is_null($reportPlayerStatDay->page_recharge_amount)){
                $rechangeAmount     = ($reportPlayerStatDay->page_recharge_amount + $reportPlayerStatDay->dividend)*0.9;
                $withdrawAmount     = $reportPlayerStatDay->withdraw_amount;
                $changeStock        = $reportPlayerStatDay->change_self_stock;
            }

            $endReportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(self_stock) as self_stock'))
                ->where('rid','like',$value->rid.'%')
                ->where('day',$endDay)
                ->first();

            if($endReportPlayerStatDay && !is_null($endReportPlayerStatDay->self_stock)){
                $stock = $endReportPlayerStatDay->self_stock;
            }

            $playerBetFlowCommission         = PlayerCommission::select(\DB::raw('sum(amount) as amount'))->where('player_id',$value->player_id)->where('day','>=',$startDay)->where('day','<=',$endDay)->first();

            if($playerBetFlowCommission && !is_null($playerBetFlowCommission->amount)){
                $venueFee                    = $playerBetFlowCommission->amount;
            }

            $teamStock          += $stock;
            $teamStockChange    += $changeStock;
            $teamRechargeAmount += $rechangeAmount;
            $teamWithdrawAmount += $withdrawAmount;
            $teamDividend       += bcdiv(($rechangeAmount - $withdrawAmount - $changeStock - $venueFee)*($selfSetting->earnings - $sonPlayerSettingsArr[$value->player_id]),100,0);
        }

        //注册人数
        $rows['registerpersoncount']         = Player::where('rid','like',$player->rid.'%')->where('created_at','>=',$startTime)->where('created_at','<=',$endTime)->count();
        $rows['activepersonacount']          = 0;
        $rows['availableadd']                = 0;
        $rows['team_recharge_amount']        = $teamRechargeAmount;
        $rows['team_withdraw_amount']        = $teamWithdrawAmount;
        $rows['amount']                      = $teamDividend + $directlyUnderDividend + $rows['lastaccumulation'];
        $rows['team_stock']                  = $teamStock;
        $rows['team_stock_change']           = $teamStockChange;

        return $rows;
    }

    public function chData()
    {
        if ( CacheService::cronLimit('get168Data', 5) ) {
            consoleLog('--- 计划任务：' . __FUNCTION__ . '跳过，原因：正在处理中....');
        }

        $pullNum = !empty($this->config['rows_168']) ? $this->config['rows_168'] : 5;

        $c168 = [
            'azxy5'     => [ 'name' => '澳洲幸运5', 'lottCode' => '168aozxy5', 'fromCode' => 'azxy5', 'api' => 'https://api.api68.com/CQShiCai/getBaseCQShiCaiList.do?lotCode=10010' ],//澳洲幸运5
            'azxy8'     => [ 'name' => '澳洲幸运8', 'lottCode' => '168aozxy8', 'fromCode' => 'azxy8', 'api' => 'https://api.api68.com/klsf/getHistoryLotteryInfo.do?date=&lotCode=10011' ],//澳洲幸运8
            'azxy10'    => [ 'name' => '澳洲幸运10', 'lottCode' => '168aozxy10', 'fromCode' => 'azxy10', 'api' => 'https://api.api68.com/pks/getPksHistoryList.do?lotCode=10012' ],//澳洲幸运10
            'azxy20'    => [ 'name' => '澳洲幸运20', 'lottCode' => '168aozxy20', 'fromCode' => 'azxy20', 'api' => 'https://api.api68.com/LuckTwenty/getBaseLuckTwentyList.do?date=&lotCode=10013' ],//澳洲幸运20
            'sgft'      => [ 'name' => 'SG飞艇', 'lottCode' => '168sgft', 'fromCode' => 'xyft', 'api' => 'https://api.api68.com/pks/getPksHistoryList.do?lotCode=10058' ],
            '168sgkl8'  => [ 'name' => 'SG快乐8', 'lottCode' => '168sgkl8', 'fromCode' => '168sgkl8', 'api' => 'https://api.api68.com/LuckTwenty/getBaseLuckTwentyList.do?date=&lotCode=10082' ],
            '168sgkl10' => [ 'name' => 'SG快乐十分', 'lottCode' => '168sgkl10', 'fromCode' => 'gdkl10', 'api' => 'https://api.api68.com/klsf/getHistoryLotteryInfo.do?date=&lotCode=10083' ],
            '168sgssc'  => [ 'name' => 'SG时时彩', 'lottCode' => '168sgssc', 'fromCode' => 'cqssc', 'api' => 'https://api.api68.com/CQShiCai/getBaseCQShiCaiList.do?lotCode=10075' ],
            '168sgk3'   => [ 'name' => 'SG快3', 'lottCode' => '168sgk3', 'fromCode' => 'jsk3', 'api' => 'https://api.api68.com/lotteryJSFastThree/getJSFastThreeList.do?date=&lotCode=10076' ],
            '168sg11x5' => [ 'name' => 'SG11选5', 'lottCode' => '168sg11x5', 'fromCode' => '168sg11x5', 'api' => 'https://api.api68.com/ElevenFive/getElevenFiveList.do?date=&lotCode=10084' ],
            '168kl8'    => [ 'name' => '168快乐8', 'lottCode' => '168kl8', 'fromCode' => '168kl8', 'api' => 'https://api.api68.com/LuckTwenty/getBaseLuckTwentyList.do?date=&lotCode=10073' ],
            '168fc3d'   => [ 'name' => '168福彩3D', 'lottCode' => '168fc3d', 'fromCode' => 'fc3d', 'api' => 'https://api.api68.com/QuanGuoCai/getLotteryInfoList.do?lotCode=10041' ],
            '168tc7xc'  => [ 'name' => '168七星彩', 'lottCode' => '168tc7xc', 'fromCode' => '168tcqxc', 'api' => 'https://api.api68.com/QuanGuoCai/getHistoryLotteryInfo.do?lotCode=10045' ],
            '168xyft'   => [ 'name' => '168幸运飞艇', 'lottCode' => '168xyft', 'fromCode' => 'xyft', 'api' => 'https://api.api68.com/pks/getPksHistoryList.do?lotCode=10057' ],
            '168xyssc'  => [ 'name' => '168幸运时时彩', 'lottCode' => '168xyssc', 'fromCode' => 'cqssc', 'api' => 'https://api.api68.com/CQShiCai/getBaseCQShiCaiList.do?lotCode=10059' ],
        ];

        foreach ( $c168 as $key => $val ) {
            $lottCode = $val['lottCode'];
            $fromCode = $val['fromCode'];

            if ( empty($this->platformList[$lottCode]) ) continue;

            $apiUrl = $val['api'];

            $response = Helper::curlMethod($apiUrl, [], 'GET', 30);
            if ( !empty($response['curl_error']) ) {
                throw new ErrMsg($response['error_msg']);
            }
            $data = json_decode($response, true);

            $limit = $pullNum;
            $list  = !empty($data['result']['data']) && count($data['result']['data']) > 5 ? array_slice($data['result']['data'], 0, $limit) : $data['result']['data'];
            if ( !empty($list) ) {
//                var_dump($list);die;
                $list = ArrHelper::arraySort($list, 'preDrawIssue', 'ASC');
            } else {
                continue;
            }

//            var_dump($list);die;

            foreach ( $list AS $each ) {

                if ( in_array($key, [ 'azxy20', '168sgkl8' ]) ) {
                    $each['preDrawCodeArr'] = explode(',', $each['preDrawCode']);
                    array_pop($each['preDrawCodeArr']);
                    foreach ( $each['preDrawCodeArr'] AS &$each2 ) {
                        $each2 = sprintf('%02d', $each2);
                    }
                    $openData = implode(',', $each['preDrawCodeArr']);
                } else {
                    if ( !isset($each['preDrawCode']) ) {
                        var_dump($val, $each);
                        die;
                    }
                    $openData = $each['preDrawCode'];
                }


                $openTime  = $each['preDrawTime'];
                $openIssue = $each['preDrawIssue'];

                //验证期数和号码
                $check = self::verifyNumberData($fromCode, $openIssue, $openData);

                if ( (string)$check['error'] == '0' ) {

                    # 获取开奖数据
                    $lottData = CenterOpenData::findOne([ 'lott_code' => $lottCode, 'open_issue' => $openIssue ]);

                    if ( empty($lottData['id']) ) {

                        # 六合彩、七星彩、福彩3D、大乐透，需要手动审核，默认不启用
                        $closeAry = [ 'lhc', 'qxc', 'fc3d', 'dlt', 'pl3', 'pl5' ];

                        # 添加拉取数据
                        $openData = [
                            'enable'              => in_array($lottCode, $closeAry) ? 0 : 1,
                            'lott_code'           => $lottCode,
                            'open_time'           => strtotime($openTime),
                            'open_issue'          => $openIssue,
                            'open_data'           => $openData,
                            'last_update'         => time(),
                            'platform_lottery_id' => $this->platformList[$lottCode]['id'],
                            'created_at'          => time(),
                            'updated_at'          => time(),
                        ];

                        CenterOpenData::insert2($openData);

                        if ( $lottCode === 'pk10' ) {//额外插入pk10牛牛
                            $openData['lott_code'] = 'pk10nn';
                            CenterOpenData::insert2($openData);
                        }
                    }
                    echo $lottCode . ' , 第' . $openIssue . '期 , 开奖号码****** , 开奖时间' . $openTime . "\r\n";
                } else {
                    echo $lottCode . " , 第" . $openIssue . '期 , 数据错误：' . $check['msg'] . "\r\n";
                }
            }

        }

    }

    public function deletePlayer()
    {
       $playerIds           = PlayerTransfer::where('carrier_id','10000017')->pluck('player_id')->toArray();
       $registerIps        = Player::groupBy('login_ip')->having(\DB::raw('count(login_ip)'),'>',1)->pluck('login_ip')->toArray();

       //Player::whereIn('register_ip',$registerIps)->where('login_at','<=',date('Y-m-d H:i:s',time()-7*86400))->

       //\Log::info('同IP的IP数'.count($registerIps)); exit;

       //$playerIds           = PlayerTransfer::where('carrier_id','10000017')->pluck('player_id')->toArray();
       //$playerIds           = array_unique($playerIds);

       //$noRealNamePlayerIds = Player::where('real_name','')->pluck('player_id')->toArray();
       $maxLevel            = Player::where('carrier_id','10000017')->max('level');

       if(!$maxLevel){
            return;
        }

              do{
                $ids = Player::where('is_tester',0)->where('level',$maxLevel)->get();
                //\Log::info('获取到的值是',['aaa'=>$ids]);
                //$ids = Player::whereIn('player_id',$noRealNamePlayerIds)->whereNotIn('player_id',$playerIds)->where('level',$maxLevel)->get();
               
                foreach ($ids as $key => $value) {
                    $player              = Player::where('rid','like',$value->rid.'|%')->first();
                    $defaultUserName     = CarrierCache::getCarrierConfigure(10000017,'default_user_name');

                    if(!$player && $value->user_name !=$defaultUserName ){
                        try {
                            \DB::beginTransaction();
                           
                            Player::where('player_id',$value->player_id)->update(['type'=>2]);
                    
                            PlayerInviteCode::where('player_id',$value->player_id)->update(['type'=>2]);
                            ReportPlayerStatDay::where('player_id',$value->player_id)->update(['type'=>2]);
                          
                            \DB::commit();
                        } catch (\Exception $e) {
                            \DB::rollback();
                            \Log::info('日删除用户数据操作异常：'.$e->getMessage());
                            return false;
                        }
                    }
                }
                $maxLevel--;
            }while ($maxLevel);

    }

    public function addConf()
    {
         $inserts =[
              'is_sms_verify'=>[
                  'sign'       => 'is_sms_verify',               
                  'value'      => 0,
                  'remark'     => '手机启用短信验证'
              ],
              'is_registergift'=>[
                  'sign'       => 'is_registergift',               
                  'value'      => 0,
                  'remark'     => '是否开启注册即送活动'
              ],
              'giftamount'=>[
                  'sign'       => 'giftamount',               
                  'value'      => 0,
                  'remark'     => '注册即送金额'
              ],
              'is_bindtelephone'=>[
                  'sign'       => 'is_bindtelephone',               
                  'value'      => 0,
                  'remark'     => '是否需要绑定手机',
              ]
            ];
        $carriers = Carrier::all();
        $insertArr = [];
        foreach ($carriers as $key => $value) {
           foreach ($inserts as $k => $v) {
             $existSign = CarrierWebSite::where('carrier_id',$value->id)->where('sign',$v['sign'])->first();
             if(!$existSign){
               $row               = [];
               $row['sign']       = $v['sign'];
               $row['value']      = $v['value'];
               $row['remark']     = $v['remark'];
               $row['carrier_id'] = $value->id;
               $row['created_at'] = date('Y-m-d H:i:s');
               $insertArr[]       = $row;
             }
           }
        }

        if(count($insertArr)){
          \DB::table('conf_carrier_web_site')->insert($insertArr);
        }
    }

    public function  test()
    {
        dispatch(new TestJob());
    }

  /*  public function createJnd28Issue()
    {
        //设置开盘后的第一期
        $data['open_time'=>'22:00:00','currIssue'=3068320];

        $startTime = strtotime(date('Y-m-d').' '.$data['open_time']);
        $data      = [];

        for($i=0;;$i++){
            $row                        = [];
            $row['carrier_id']          = 0;
            $row['lott_id']             = 66;
            $row['open_issue']          = $data['currIssue']+$i;
            $row['day_time']            = '0000-00-00 00:00:00';
            $row['platform_lottery_id'] = 50;
            $row['last_update']         = 0;
            $row['created_at']          = date('Y-m-d H:i:s');
            $row['updated_at']          = date('Y-m-d H:i:s');

            //当前是星期天
            if(date('w')==0){
                if(){

                }
                $row['open_time']           = data('H:i:s');
                $row['stop_time']           = data('H:i:s');
            } else{
                $row['open_time']           = data('H:i:s');
                $row['stop_time']           = data('H:i:s');
            }
        }
        SscOpenDataTime::where('platform_lottery_id',50)->where('lott_id',66)->where()
    }
    */
}