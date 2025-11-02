<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Carrier;

use App\Lib\Cache\CarrierCache;
use App\Models\Log\PlayerTransferCasino;
use App\Models\Map\CarrierGamePlat;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\PlayerGameAccount;
use App\Models\PlayerAccount;
use App\Models\Log\PlayerBetFlow;
use App\Game\Game;

class ClearBetFlowLimitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $playerDepositPayLog   = null;

    public function __construct($playerDepositPayLog) {
        $this->playerDepositPayLog      = $playerDepositPayLog;
    }

    public function handle()
    {
        $this->clearBetFlowLimit();
    }

    public function clearBetFlowLimit()
    {   $carrier                = Carrier::where('id',$this->playerDepositPayLog->carrier_id)->first();

        $clearBetFlowLimitAmout = CarrierCache::getCarrierConfigure($carrier->id,'clearbetflowlimitamount');
        $playerAccount          = PlayerAccount::where('player_id',$this->playerDepositPayLog->player_id)->first();
       
        //有转帐记录的平台
        $playerGameAccounts   = PlayerGameAccount::where('player_id',$this->playerDepositPayLog->player_id)->where('exist_transfer',1)->get();
        $playerTransferCasino = PlayerTransferCasino::where('player_id',$this->playerDepositPayLog->player_id)->where('type',1)->groupBy('main_game_plat_id')->orderby('id','desc')->limit(5)->pluck('main_game_plat_id')->toArray();
        $maintainPlatIds      = CarrierGamePlat::where('carrier_id',$carrier->id)->where('status',2)->pluck('game_plat_id')->toArray();

        $jumpPlats            = [];

        //如果系统维护，玩家1天内没有投注记录跳过此平台
        if(count($maintainPlatIds)){
            foreach ($maintainPlatIds as $key => $value) {
                $existPlayerBetFlow = PlayerBetFlow::where('player_id',$this->playerDepositPayLog->player_id)->where('main_game_plat_id',$value)->where('created_at','>=',date('Y-m-d H:i:s',strtotime('-1 days')))->first();
                if(!$existPlayerBetFlow){
                    $jumpPlats[] = $value;
                } else {
                    return;
                }
            }
        }

        foreach ($playerGameAccounts as $key => $value) {
            if(in_array($value->main_game_plat_id,$jumpPlats)){
                continue;
            }

            request()->offsetSet('accountUserName',$value->account_user_name);
            request()->offsetSet('password',$value->password);
            request()->offsetSet('mainGamePlatCode',$value->main_game_plat_code);
                
            $game    = new Game($carrier,$value->main_game_plat_code);
            $result  = $game->getBalance();

            if(!is_array($result) || !$result['success']){
                return;
            }

            if($result['data']['balance'] > $clearBetFlowLimitAmout){
                return;
            }
        }

        //清空流水
        PlayerWithdrawFlowLimit::where('player_id',$this->playerDepositPayLog->player_id)->where('is_finished',0)->where('created_at','<',$this->playerDepositPayLog->updated_at)->update(['is_finished'=>1,'complete_limit_amount'=>\DB::raw('limit_amount')]);
    }
}
