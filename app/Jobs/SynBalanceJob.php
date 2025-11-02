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
use App\Game\Game;
use App\Models\Carrier;


class SynBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $accountUserNames      = null;

    public function __construct($accountUserNames) {
        $this->accountUserNames   = $accountUserNames;
    }

    public function handle()
    {
        $this->synBalance();
    }

    public function synBalance()
    {   
        $playerGameAccounts = PlayerGameAccount::whereIn('account_user_name',$this->accountUserNames)->get();
        $carrier            = null;

        foreach ($playerGameAccounts as $key => $value) {
            request()->offsetSet('accountUserName',$value->account_user_name);
            request()->offsetSet('password',$value->password);
            request()->offsetSet('mainGamePlatCode',$value->main_game_plat_code);

            if(is_null($carrier)){
                $carrier = Carrier::where('id',$value->carrier_id)->first();
            }
                
            $game = new Game($carrier,$value->main_game_plat_code);
                
            $game->getBalance();
        }
    }
}
