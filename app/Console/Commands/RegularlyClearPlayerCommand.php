<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CarrierPreFixDomain;
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
use App\Models\CarrierActivityGiftCode;
use App\Models\CarrierCapitationFeeSetting;
use App\Models\CarrierGuaranteed;
use App\Models\CarrierHorizontalMenu;
use App\Models\CarrierImage;
use App\Models\TaskSetting;
use App\Models\Log\RankingList;
use App\Models\Report\ReportCarrierMonthStat;

class RegularlyClearPlayerCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'regularlyclearplayer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regularly Clear Player';


    //重置用户数据库
    public   $deleteall = false;

    //删除的站点
    public $prefixs     = ['D'];
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
       $carrierPreFixDomains = CarrierPreFixDomain::all();
       $currTime             = date('Y-m-d').' 00:00:00';
       foreach ($carrierPreFixDomains as $key => $value) {
           $playerIds  = Player::where('login_at','<=',date('Y-m-d H:i:s',strtotime($currTime)-5184000))->where('prefix',$value->prefix)->where('player_id','!=',10001023)->pluck('player_id')->toArray();
           $playerIds2 = PlayerAccount::where('balance','<=',100000)->pluck('player_id')->toArray();
           $playerIds  = array_intersect($playerIds, $playerIds2);

           foreach ($playerIds as $k => $v) {
                $currPlayer = Player::where('player_id',$v)->first();
                $existPlayer = Player::where('rid','like',$currPlayer->rid.'|%')->first();
                if(!$existPlayer){
                    PlayerCommission::where('player_id',$currPlayer->player_id)->delete();
                    PlayerSetting::where('player_id',$currPlayer->player_id)->delete();
                    PlayerBetflowCalculate::where('player_id',$currPlayer->player_id)->delete();
                    PlayerAccount::where('player_id',$currPlayer->player_id)->delete();
                    PlayerActivityAudit::where('player_id',$currPlayer->player_id)->delete();
                    PlayerBankCard::where('player_id',$currPlayer->player_id)->delete();
                    PlayerDigitalAddress::where('player_id',$currPlayer->player_id)->delete();
                    PlayerGameAccount::where('player_id',$currPlayer->player_id)->delete();
                    PlayerInviteCode::where('player_id',$currPlayer->player_id)->delete();
                    PlayerMessage::where('player_id',$currPlayer->player_id)->delete();
                    PlayerRecent::where('player_id',$currPlayer->player_id)->delete();
                    PlayerTransfer::where('player_id',$currPlayer->player_id)->delete();
                    PlayerBetFlow::where('player_id',$currPlayer->player_id)->delete();
                    PlayerBetFlowMiddle::where('player_id',$currPlayer->player_id)->delete();
                    PlayerDepositPayLog::where('player_id',$currPlayer->player_id)->delete();
                    PlayerLogin::where('player_id',$currPlayer->player_id)->delete();
                    PlayerOperate::where('player_id',$currPlayer->player_id)->delete();
                    PlayerTransferCasino::where('player_id',$currPlayer->player_id)->delete();
                    PlayerWithdraw::where('player_id',$currPlayer->player_id)->delete();
                    PlayerWithdrawFlowLimit::where('player_id',$currPlayer->player_id)->delete();
                    ReportPlayerEarnings::where('player_id',$currPlayer->player_id)->delete();
                    ReportPlayerStatDay::where('player_id',$currPlayer->player_id)->delete();
                    PlayerSignIn::where('player_id',$currPlayer->player_id)->delete();
                    PlayerSignInReceive::where('player_id',$currPlayer->player_id)->delete();
                    PlayerBreakThrough::where('player_id',$currPlayer->player_id)->delete();
                    PlayerFingerprint::where('player_id',$currPlayer->player_id)->delete();
                    PlayerGameCollect::where('player_id',$currPlayer->player_id)->delete();
                    PlayerReceiveGiftCenter::where('player_id',$currPlayer->player_id)->delete();
                    PlayerGiftCode::where('player_id',$currPlayer->player_id)->delete();
                    PlayerHoldGiftCode::where('player_id',$currPlayer->player_id)->delete();
                    Player::where('player_id',$currPlayer->player_id)->delete();
                }
           }

           if($this->deleteall){
                CarrierMultipleFront::where('prefix',$value->prefix)->delete();
                CarrierPreFixDomain::where('prefix',$value->prefix)->delete();
                CarrierActivity::where('prefix',$value->prefix)->delete();
                CarrierActivityGiftCode::where('prefix',$value->prefix)->delete();
                CarrierCapitationFeeSetting::where('prefix',$value->prefix)->delete();
                CarrierGuaranteed::where('prefix',$value->prefix)->delete();
                CarrierHorizontalMenu::where('prefix',$value->prefix)->delete();
                CarrierImage::where('prefix',$value->prefix)->delete();
                TaskSetting::where('prefix',$value->prefix)->delete();
                RankingList::where('prefix',$value->prefix)->delete();
                ReportCarrierMonthStat::where('prefix',$value->prefix)->delete();
           }
       }
    }
}