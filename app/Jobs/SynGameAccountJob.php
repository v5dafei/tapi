<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Def\MainGamePlat;
use App\Models\PlayerGameAccount;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\CarrierCache;
use App\Game\Game;
use App\Models\Carrier;
use App\Models\PlayerAccount;
use App\Models\Report\ReportPlayerStatDay;


class SynGameAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $playerGameAccounts      = null;

    public function __construct($playerGameAccounts) {
        $this->playerGameAccounts   = $playerGameAccounts;
    }

    public function handle()
    {
        $this->synBalance();
    }

    public function synBalance()
    {
        $playerIds  = [];
        foreach ($this->playerGameAccounts as $key => $value) {
            request()->offsetSet('accountUserName',$value->account_user_name);
            request()->offsetSet('password',$value->password);
            request()->offsetSet('mainGamePlatCode',$value->main_game_plat_code);

            $carrier = CarrierCache::getCarrierById($value->carrier_id);    
            $game    = new Game($carrier,$value->main_game_plat_code);
            $game->getBalance();

            $playerIds[] = $value->player_id;
        }

        $allPlayers           = PlayerAccount::whereIn('player_id',$playerIds)->get();
        $data                 = [];
        foreach ($allPlayers as $key => $value) {
            $data[$value->player_id] = $value->balance + $value->frozen + $value->agentbalance + $value->agentfrozen;
        }

        //累积三方游戏的余额
        $playerGameAccounts = PlayerGameAccount::select(\DB::raw('sum(balance) as balance'),'player_id')->whereIn('player_id',$playerIds)->groupBy('player_id')->get();

        foreach ($playerGameAccounts as $key => $value) {
            $data[$value->player_id] = $data[$value->player_id] + $value->balance*10000;
            $preReportPlayerStatDay = ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd',strtotime('-1 day')))->first();
            if($preReportPlayerStatDay){
                $changeSelfStock = $data[$value->player_id] - $preReportPlayerStatDay->self_stock;
                ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd'))->update(['self_stock'=>$data[$value->player_id],'change_self_stock'=>$changeSelfStock]);
            } else{
                ReportPlayerStatDay::where('player_id',$value->player_id)->where('day',date('Ymd'))->update(['self_stock'=>$data[$value->player_id],'change_self_stock'=>$data[$value->player_id]]);
            }
        }
    }
}
