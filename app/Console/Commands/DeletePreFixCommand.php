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
use App\Models\Conf\CarrierMultipleFront;
use App\Models\CarrierActivity;
use App\Models\CarrierCapitationFeeSetting;
use App\Models\CarrierGuaranteed;
use App\Models\CarrierHorizontalMenu;
use App\Models\CarrierImage;
use App\Models\Conf\CarrierPayChannel;
use App\Models\CarrierPop;
use App\Models\CarrierPreFixDomain;
use App\Models\TaskSetting;
use App\Models\Log\GameHot;
use App\Models\Log\PlayerCapitationFee;
use App\Models\Log\PlayerCommissionTongbao;
use App\Models\Log\PlayerDividendTongbao;
use App\Models\Log\PlayerToken;
use App\Models\Log\RankingList;
use App\Models\Report\ReportCarrierMonthStat;
use App\Models\CarrierActivityGiftCode;
use App\Models\Log\PlayerRealCommissionTongbao;
use App\Models\CarrierPlayerGrade;
use App\Models\PayChannelGroup;
use App\Models\Map\CarrierPreFixGamePlat;
use App\Lib\Cache\PlayerCache;
use App\Models\PlayerAlipay;
use App\Models\CarrierNotice;
use App\Models\Map\CarrierPlayerLevelBankCardMap;

class DeletePreFixCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteprefix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete prefix';


    //重置用户数据库
    public   $prefixlist = ['Y'];
    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
            $playerIds = Player::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->pluck('player_id')->toArray();

            foreach ($this->prefixlist as $key1 => $value1) {
                PlayerCache::flushFingerprint($value1);
                PlayerCache::flushIps($value1);
            }

            foreach ($playerIds as $key2 => $value2) {
                ReportPlayerStatDay::where('player_id',$value2)->delete();
            }

            PlayerBetflowCalculate::whereIn('player_id',$playerIds)->delete();
            PlayerGameAccount::whereIn('player_id',$playerIds)->delete();
            PlayerMessage::whereIn('player_id',$playerIds)->delete();
            PlayerRecent::whereIn('player_id',$playerIds)->delete();
            PlayerSignIn::whereIn('player_id',$playerIds)->delete();
            PlayerSignInReceive::whereIn('player_id',$playerIds)->delete();
            PlayerFingerprint::whereIn('player_id',$playerIds)->delete();
            PlayerGameCollect::whereIn('player_id',$playerIds)->delete();
            PlayerSetting::whereIn('player_id',$playerIds)->delete();
            PlayerAccount::whereIn('player_id',$playerIds)->delete();
            PlayerLogin::whereIn('player_id',$playerIds)->delete();
            PlayerOperate::whereIn('player_id',$playerIds)->delete();
            PlayerToken::whereIn('player_id',$playerIds)->delete();
            PlayerTransferCasino::whereIn('player_id',$playerIds)->delete();
            PlayerWithdrawFlowLimit::whereIn('player_id',$playerIds)->delete();
            PlayerReceiveGiftCenter::whereIn('player_id',$playerIds)->delete();

            CarrierMultipleFront::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierActivity::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierActivityGiftCode::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierCapitationFeeSetting::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierGuaranteed::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierHorizontalMenu::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierImage::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();

            $carrierPayChannelIds = CarrierPayChannel::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->pluck('id')->toArray();
            CarrierPlayerLevelBankCardMap::whereIn('carrier_channle_id',$carrierPayChannelIds)->delete();

            CarrierPayChannel::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierPop::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierPreFixDomain::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierPreFixGamePlat::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerBankCard::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerDigitalAddress::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerHoldGiftCode::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerInviteCode::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerTransfer::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            TaskSetting::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            GameHot::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerBetFlow::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerBetFlowMiddle::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerBreakThrough::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerCapitationFee::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerCommissionTongbao::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerDepositPayLog::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerDividendTongbao::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerGiftCode::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerRealCommissionTongbao::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerWithdraw::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            RankingList::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            ReportCarrierMonthStat::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerCommission::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            ReportPlayerEarnings::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierPlayerGrade::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PayChannelGroup::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            PlayerAlipay::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            Player::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();
            CarrierNotice::where('carrier_id',$value->id)->whereIn('prefix',$this->prefixlist)->delete();

        }
    }
}