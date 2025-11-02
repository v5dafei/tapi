<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Conf\PlayerSetting;
use App\Models\CarrierActivityPlayerLuckDraw;
use App\Models\Player;
use App\Models\PlayerBankCard;
use App\Models\PlayerAccount;
use App\Models\PlayerActivityAudit;
use App\Models\PlayerGameCollect;
use App\Models\PlayerInviteCode;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerBetFlow;
use App\Models\PlayerBreakThrough;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerGiftCode;
use App\Models\Log\PlayerLogin;
use App\Models\Log\PlayerOperate;
use App\Models\Log\PlayerTransferCasino;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerSignIn;
use App\Models\PlayerCommission;
use App\Models\Report\ReportPlayerEarnings;

class AddPreFixCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addprefix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'addprefix';

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
        //$carrierId = 10000015;
        //$playerId  = 10003616;
        $playerIds  = [10001015,10001016,10003616,10003659,10003662,10003666];

        PlayerSetting::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        CarrierActivityPlayerLuckDraw::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        Player::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerBankCard::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerAccount::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerActivityAudit::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerGameCollect::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerInviteCode::whereNotIn('player_id',$playerIds)->update(['username'=>\DB::raw("concat(username,'_A')")]);
        PlayerReceiveGiftCenter::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerTransfer::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerBreakThrough::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerDepositPayLog::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerGiftCode::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerLogin::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerOperate::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerTransferCasino::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerWithdraw::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerWithdrawFlowLimit::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerSignIn::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerCommission::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        ReportPlayerEarnings::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        ReportPlayerStatDay::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
        PlayerBetFlow::whereNotIn('player_id',$playerIds)->update(['user_name'=>\DB::raw("concat(user_name,'_A')")]);
    }
}