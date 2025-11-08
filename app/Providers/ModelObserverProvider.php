<?php
namespace App\Providers;

use App\Models\Log\PlayerBetFlow;
use App\Observers\PlayerBetFlowObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\Log\PlayerDepositPayLog;
use App\Observers\CarrierObserver;
use App\Observers\CarrierUserObserver;
use App\Observers\PlayerObserver;
use App\Observers\CarrierGameObserver;
use App\Observers\PlayerSettingObserver;
use App\Observers\PayChannelObserver;
use App\Observers\BankObserver;
use App\Observers\CarrierPayChannelObserver;
use App\Observers\CarrierBankCardObserver;
use App\Observers\PlayerDepositPayLogObserver;
use App\Observers\PlayerGameAccountObserver;
use App\Observers\CarrierGamePlatObserver;
use App\Observers\PlayerAccountObserver;
use App\Observers\CarrierPlayerGradeObserver;
use App\Observers\PlayerBankCardObserver;
use App\Observers\PlayerMessageObserver;
use App\Observers\CarrierPreFixDomainObserver;
use App\Observers\PlayerTransferObserver;
use App\Observers\ReportPlayerEarningsObserver;
use App\Observers\PlayerCommissionObserver;
use App\Observers\PlayerWithdrawObserver;
use App\Observers\PlayerAlipayObserver;
use App\Models\Map\CarrierGame;
use App\Models\Def\PayChannel;
use App\Models\Def\Banks;
use App\Models\Conf\PlayerSetting;
use App\Models\Conf\CarrierPayChannel;
use App\Models\PlayerGameAccount;
use App\Models\CarrierBankCard;
use App\Models\PlayerAccount;
use App\Models\Carrier;
use App\Models\Player;
use App\Models\Map\CarrierGamePlat;
use App\Models\CarrierPlayerGrade;
use App\Models\PlayerTransfer;
use App\Models\PlayerBankCard;
use App\Models\PlayerMessage;
use App\Models\PlayerDigitalAddress;
use App\Models\CarrierPreFixDomain;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\PlayerCommission;
use App\Models\Log\PlayerWithdraw;
use App\Models\PlayerAlipay;

class ModelObserverProvider extends ServiceProvider
{
    public function boot()
    {
        Carrier::observe(CarrierObserver::class);
        Player::observe(PlayerObserver::class);
        CarrierGame::observe(CarrierGameObserver::class);
        PlayerSetting::observe(PlayerSettingObserver::class);
        PayChannel::observe(PayChannelObserver::class);
        CarrierPayChannel::observe(CarrierPayChannelObserver::class);
        Banks::observe(BankObserver::class);
        CarrierBankCard::observe(CarrierBankCardObserver::class);
        PlayerAlipay::observe(PlayerAlipayObserver::class);
        PlayerGameAccount::observe(PlayerGameAccountObserver::class);
        CarrierGamePlat::observe(CarrierGamePlatObserver::class);
        PlayerDepositPayLog::observe(PlayerDepositPayLogObserver::class);
        PlayerAccount::observe(PlayerAccountObserver::class);
        PlayerBetFlow::observe(PlayerBetFlowObserver::class);
        CarrierPlayerGrade::observe(CarrierPlayerGradeObserver::class);
        PlayerBankCard::observe(PlayerBankCardObserver::class);
        PlayerMessage::observe(PlayerMessageObserver::class);
        CarrierPreFixDomain::observe(CarrierPreFixDomainObserver::class);
        PlayerTransfer::observe(PlayerTransferObserver::class);
        ReportPlayerEarnings::observe(ReportPlayerEarningsObserver::class);
        PlayerCommission::observe(PlayerCommissionObserver::class);
        PlayerWithdraw::observe(PlayerWithdrawObserver::class);
    }

    public function register()
    {
    }
}
