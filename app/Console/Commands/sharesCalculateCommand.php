<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Player;
use App\Models\PlayerTransfer;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerWithdraw;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Log\PlayerBetFlow;

class sharesCalculateCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sharesCalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sharesCalculate';

    const STARTDATE                          = 20251006;
    const ENDDATE                            = 20251012;
    const CREATEDATE                         = 20251013;
    const PREFIX                             = 'I';
    const CALCULATEPLAYERID                  =  10090558;
    const FLAG                               =  true;   //true加入上期分红  false不加入上期分红
    const CYCLE                              =  4; //2=一周，3=3天，4=1天,5=半月,1=5天

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
        $player            = Player::where('player_id',self::CALCULATEPLAYERID)->first();
        $rechargeAmount    = PlayerDepositPayLog::where('rid','like',$player->rid.'|%')->where('day','>=',self::STARTDATE)->where('day','<=',self::ENDDATE)->where('is_hedging_account',0)->sum('amount');

        if(self::FLAG){
            $withdrawAmount    = PlayerWithdraw::whereIn('status',[1,2])->where('is_hedging_account',0)->where('is_empty_withdrawal',0)->where('arrival_time','>=',strtotime(self::STARTDATE))->where('arrival_time','<',strtotime(self::ENDDATE)+86400)->where('rid','like',$player->rid.'|%')->sum('amount');
        } else{
            $withdrawAmount    = PlayerWithdraw::whereIn('status',[1,2])->where('is_hedging_account',0)->where('is_empty_withdrawal',0)->where('is_agent',0)->where('arrival_time','>=',strtotime(self::STARTDATE))->where('arrival_time','<',strtotime(self::ENDDATE)+86400)->where('rid','like',$player->rid.'|%')->sum('amount');
        }
        
        $earningsAmount    = ReportPlayerEarnings::where('rid','like',$player->rid.'|%')->where('send_day',self::CREATEDATE)->where('status',1)->where('prefix',self::PREFIX)->sum('real_amount');
        $scoreAmount       = PlayerBetFlowMiddle::where('rid','like',$player->rid.'|%')->where('day','>=',self::STARTDATE)->where('day','<=',self::ENDDATE)->sum('company_win_amount');

        //去掉试玩的分钱
        $tryPlayerIds        = PlayerBetFlowMiddle::where('rid','like',$player->rid.'|%')->where('day','>=',self::STARTDATE)->where('day','<=',self::ENDDATE)->pluck('player_id')->toArray();
        $tryPlayerIds        = array_unique($tryPlayerIds);
        $tryCompanyWinAmount = PlayerBetFlow::where('day','>=',self::STARTDATE)->where('day','<=',self::ENDDATE)->where('prefix',self::PREFIX)->where('game_status',1)->where('is_trygame',1)->whereIn('player_id',$tryPlayerIds)->sum('company_win_amount');

        $preearningsAmount = ReportPlayerEarnings::where('rid','like',$player->rid.'|%')->where('send_day',self::STARTDATE)->where('status',1)->where('prefix',self::PREFIX)->sum('real_amount');

        if(self::FLAG){
            if($scoreAmount >0){
                $resultAmount   = bcdiv(($rechargeAmount*0.935 + $preearningsAmount - $withdrawAmount - $earningsAmount - ($scoreAmount-$tryCompanyWinAmount)*1000)*0.27,10000,0);
                \Log::info('上期分红金额是'.bcdiv($preearningsAmount,10000,0).'充值金额是'.bcdiv($rechargeAmount,10000,0).'提现金额是'.bcdiv($withdrawAmount,10000,0).'分红金额是'.bcdiv($earningsAmount,10000,0).'用分是'.$scoreAmount.'试玩分对冲'.$tryCompanyWinAmount.'计算出来的金额是'.$resultAmount);
            } else{
                $resultAmount   = bcdiv(($rechargeAmount*0.935 + $preearningsAmount - $withdrawAmount - $earningsAmount )*0.27,10000,0);
                \Log::info('上期分红金额是'.bcdiv($preearningsAmount,10000,0).'充值金额是'.bcdiv($rechargeAmount,10000,0).'提现金额是'.bcdiv($withdrawAmount,10000,0).'分红金额是'.bcdiv($earningsAmount,10000,0).'计算出来的金额是'.$resultAmount);
            }
            
        } else{
            if($scoreAmount >0){
            $resultAmount   = bcdiv(($rechargeAmount*0.935 - $withdrawAmount - $earningsAmount -($scoreAmount-$tryCompanyWinAmount)*1000)*0.27,10000,0);
            \Log::info('充值金额是'.bcdiv($rechargeAmount,10000,0).'提现金额是'.bcdiv($withdrawAmount,10000,0).'分红金额是'.bcdiv($earningsAmount,10000,0).'用分是'.$scoreAmount.'试玩分对冲'.$tryCompanyWinAmount.'计算出来的金额是'.$resultAmount);
            } else{
                 $resultAmount   = bcdiv(($rechargeAmount*0.935 - $withdrawAmount - $earningsAmount)*0.27,10000,0);
            \Log::info('充值金额是'.bcdiv($rechargeAmount,10000,0).'提现金额是'.bcdiv($withdrawAmount,10000,0).'分红金额是'.bcdiv($earningsAmount,10000,0).'计算出来的金额是'.$resultAmount);
            }
        }
    }
}