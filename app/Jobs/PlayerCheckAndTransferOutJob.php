<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerGameAccount;
use App\Game\Game;

class PlayerCheckAndTransferOutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user      = null;
    public $carrier   = null;

    public function __construct($carrier,$user) {
        $this->user      = $user;
        $this->carrier   = $carrier;
    }

    public function handle()
    {
        $this->checkAndTransferOut();
    }

    public function checkAndTransferOut()
    {   
        $transferKey        = 'gametranfer_'.$this->user->player_id;
        $playerGameAccount  = PlayerGameAccount::where('player_id',$this->user->player_id)->where('main_game_plat_code',cache()->get($transferKey))->where('is_locked',0)->where('is_need_repair',0)->first();
        if($playerGameAccount){
            request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
            request()->offsetSet('password',$playerGameAccount->password);
            request()->offsetSet('mainGamePlatCode',cache()->get($transferKey));


                    
            $game    = new Game($this->carrier,cache()->get($transferKey));        
            $balance = $game->getBalance();
            if($balance['success']){
               if($balance['data']['balance'] >= 1){
                 request()->offsetSet('price',intval($balance['data']['balance']));
                 $output = $game->transferTo($this->user);
                 if(is_array($output) && $output['success']){
                    cache()->forget($transferKey);
                 }
               } else {
                 cache()->forget($transferKey);
               }
            }
        }
    }
}
