<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Carrier;
use App\Models\PlayerGameAccount;
use App\Game\Game;

class SynGamePlatBalanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $mainGamePlatCode = null;

    public function __construct($mainGamePlatCode) {
        $this->mainGamePlatCode   = $mainGamePlatCode;
    }

    public function handle()
    {
        $this->synBalance();
    }

    public function synBalance()
    {   
        $carriers           = Carrier::all();
        $playerGameAccounts = PlayerGameAccount::where('main_game_plat_code',$this->mainGamePlatCode)->where('exist_transfer',1)->where('balance','>',1)->get();

        foreach ($carriers as $key => $value) {
            foreach ($playerGameAccounts as $k => $v) {
                $data =[
                    'mainGamePlatCode' => $this->mainGamePlatCode,
                    'accountUserName'  => $v->account_user_name,
                    'password'         => $v->password
                ];

                $game = new Game($value,$this->mainGamePlatCode);
                $game->getBalance($data);
            }
        }
    }
}
