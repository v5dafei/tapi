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

class DeleteNewPlayerCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deleteNewPlayer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete New Player';


    //重置用户数据库
    public   $deleteall = false;
    /**
     * Create a new command instance.
     *
     * @return void
     */

    const PREFIX                             = 'O';

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
        //$defaultUserName = CarrierCache::getCarrierConfigure(10000023,'default_user_name');
        //$playerIds=Player::where('prefix',self::PREFIX)->where('user_name','!=',$defaultUserName)->pluck('player_id')->toArray();
        //\Log::info('查询出来的值是',$playerIds);
        $playerIds=[10293998,10294000,10294001,10294002,10294003,10294004];

        //$playerIds1 = Player::where('prefix','Y')->where('descendantscount',0)->pluck('player_id')->toArray();

        //$playerIds=PlayerAccount::where('prefix','Y')->whereIn('player_id',$playerIds1)->where('balance',180000)->pluck('player_id')->toArray();
        
        
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