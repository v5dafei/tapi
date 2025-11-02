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
use App\Lib\Cache\PlayerCache;
use App\Game\Game;
use App\Models\Carrier;
use App\Models\PlayerAccount;
use App\Models\Map\CarrierGamePlat;

class AutoTransferToGameAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $playerGameAccounts      = null;

    public function __construct($playerGameAccounts) {
        $this->playerGameAccounts   = $playerGameAccounts;
    }

    public function handle()
    {
        $this->autoTransferToGameAccount();
    }

    public function autoTransferToGameAccount()
    {
        $playerIds  = [];
        foreach ($this->playerGameAccounts as $key => $value) {
            //////////
            $transferKey        = 'gametranfer_'.$value->player_id;
            $cacheTransferKey   = cache()->get($transferKey);
            if(cache()->has($transferKey)){
                //判断转出平台是否维护
                $carrierGamePlat = CarrierGamePlat::where('carrier_id',$value->carrier_id)->where('game_plat_id',$value->main_game_plat_id)->first();
                if($carrierGamePlat && $carrierGamePlat->status==1){
                    $carrier = CarrierCache::getCarrierById($value->carrier_id);
                    $user    = PlayerCache::getPlayerSetting($value->player_id);
                    //转帐操作
                    if($value->is_locked ==0 && $value->is_need_repair==0){
                        request()->offsetSet('accountUserName',$value->account_user_name);
                        request()->offsetSet('password',$value->password);
                        request()->offsetSet('mainGamePlatCode',$value->main_game_plat_code);

                        $transferoutGame    = new Game($carrier,$cacheTransferKey);        
                        $transferoutBalance = $transferoutGame->getBalance();

                        if(is_array($transferoutBalance) && $transferoutBalance['success']){
                           if($transferoutBalance['data']['balance'] >= 1){
                             request()->offsetSet('price',intval($transferoutBalance['data']['balance']));
                             $output = $transferoutGame->transferTo($user);
                             if(is_array($output) && $output['success']){
                                cache()->forget($transferKey);
                             } 
                           } else{
                              cache()->forget($transferKey);
                           }
                        }
                    } 
                }
            }
        }
    }
}
