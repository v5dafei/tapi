<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Lib\JWT;
use Illuminate\Support\Facades\Redis;
use Illuminate\Cache\RedisLock;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Conf\CarrierWebSite;
use App\Models\Conf\PlayerSetting;
use App\Models\Log\PlayerBetFlow;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerSignInReceive;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerTransfer;
use App\Models\Player;
use App\Models\PlayerActivityAudit;
use App\Models\PlayerInviteCode;
use App\Models\PlayerMessage;
use App\Models\PlayerRecent;
use App\Models\Bet\Lottery;
use App\Models\Aaa;
use App\Lib\Behavioralcaptcha;
use App\Models\Lottery\SscOpenDataTime;
use App\Models\Log\PlayerGiftCode;
use App\Models\Conf\CarrierMultipleFront;
use App\Lib\Cache\Lock;
use App\Lib\Cache\PlayerCache;
use App\Models\CarrierPreFixDomain;
use App\Models\PlayerCommission;
use App\Models\Def\Game;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerWithdraw;
use App\Models\Report\ReportRealPlayerEarnings;


class IntelligentControlCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intelligentcontrol';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'intelligentcontrol';

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
            $carrierPreFixDomains             = CarrierPreFixDomain::all();
            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $playerRealtimeDividendsStartDay  = CarrierCache::getCarrierMultipleConfigure($value1->carrier_id,'player_realtime_dividends_start_day',$value1->prefix);

                $liveStreamingAccountPlayerId    = Player::where('prefix',$value1->prefix)->where('is_live_streaming_account',1)->pluck('player_id')->toArray();
                $totalAgentId                    = Player::where('prefix',$value1->prefix)->where('level',2)->pluck('player_id')->toArray();
                $liveStreamingAccountPlayerId    = array_merge($totalAgentId,$liveStreamingAccountPlayerId);

                $gift                    = ReportPlayerStatDay::where('prefix',$value1->prefix)->whereNotIn('player_id',$liveStreamingAccountPlayerId)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->sum('gift');
                $companyWinAmount        = PlayerBetFlowMiddle::where('prefix',$value1->prefix)->whereNotIn('player_id',$liveStreamingAccountPlayerId)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->sum('company_win_amount');
                $rechargeAmount          = PlayerDepositPayLog::where('prefix',$value1->prefix)->whereNotIn('player_id',$liveStreamingAccountPlayerId)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->where('status',1)->sum('arrivedamount');
                $withdrawAmount          = PlayerTransfer::where('prefix',$value1->prefix)->whereNotIn('player_id',$liveStreamingAccountPlayerId)->where('type','withdraw_finish')->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->sum('amount');
                $earningsAmount          = ReportPlayerEarnings::where('prefix',$value1->prefix)->where('status',1)->where('send_day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->whereNotIn('player_id',$liveStreamingAccountPlayerId)->sum('amount');
                $commissionAmount        = PlayerCommission::where('prefix',$value1->prefix)->whereNotIn('player_id',$liveStreamingAccountPlayerId)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->sum('amount');
                $maxGuaranteed           = PlayerSetting::where('user_name','like','%_'.$value1->prefix)->max('guaranteed');
                $maxEarnings             = PlayerSetting::where('user_name','like','%_'.$value1->prefix)->max('earnings');

                //未发放的佣金
                $todayPerformance        = PlayerBetFlowMiddle::where('prefix',$value1->prefix)->where('day',date('Ymd'))->whereNotIn('player_id',$liveStreamingAccountPlayerId)->sum('agent_process_available_bet_amount');
                $noCommission            = bcdiv($todayPerformance*$maxGuaranteed,10000,2);

                $noearningsAmount        = ReportRealPlayerEarnings::where('prefix',$value1->prefix)->where('from_day',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->whereNotIn('player_id',$liveStreamingAccountPlayerId)->where('amount','>',0)->sum('amount');

                $stock                   = $rechargeAmount + $gift + $commissionAmount + $earningsAmount - $withdrawAmount - $companyWinAmount*10000;

                if($rechargeAmount + $earningsAmount >0){
                    $profitRate              = bcdiv($rechargeAmount+$earningsAmount - $withdrawAmount  -$noearningsAmount -$noCommission- $stock-abs($companyWinAmount)*1000,$rechargeAmount+$earningsAmount,4);
                } else{
                    $profitRate              = 0;
                }

                CarrierMultipleFront::where('prefix',$value1->prefix)->where('sign','current_intelligent_rate')->update(['value'=>$profitRate*100]);
            }
    }
}