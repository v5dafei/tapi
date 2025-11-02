<?php
namespace App\Observers;

use App\Models\PlayerTransfer;
use App\Models\PlayerAccount;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Jobs\ClearBetFlowLimitJob;
use App\Models\Log\PlayerBetFlow;
use App\Models\PlayerGameAccount;
use App\Models\Carrier;
use App\Game\Game;

class PlayerTransferObserver
{
    public function created(PlayerTransfer $playerTransfer)
    {

        if($playerTransfer->type=='recharge' && PlayerCache::getIswhetherRecharge($playerTransfer->player_id) == 0){
            PlayerCache::flushIswhetherRecharge($playerTransfer->player_id);
        }

        if($playerTransfer->type=='recharge' || $playerTransfer->type == 'commission_from_child' ||  $playerTransfer->type == 'dividend_from_parent' || $playerTransfer->type == 'commission_from_self'){
            $playerBetFlow = PlayerBetFlow::where('player_id',$playerTransfer->player_id)->orderBy('id','desc')->first();
            if($playerBetFlow){
                $playerGameAccount = PlayerGameAccount::where('player_id',$playerTransfer->player_id)->where('main_game_plat_id',$playerBetFlow->main_game_plat_id)->first();
                if($playerGameAccount){
                    $data =[
                        'mainGamePlatCode' => $playerGameAccount->main_game_plat_code,
                        'accountUserName'  => $playerGameAccount->account_user_name,
                        'password'         => $playerGameAccount->password
                    ];

                    $carrier = Carrier::where('id',$playerGameAccount->carrier_id)->first();
                    $game    = new Game($carrier,$data['mainGamePlatCode']);
                    $output  = $game->getBalance($data);

                    if(isset($output['success']) && $output['success']===true){
                        $playerTransfer->remark2 = $output['data']['balance'];
                        $playerTransfer->save();
                    } else{
                        \Log::info('转帐查余额失败，值是',['aaa'=>$output]);
                    }
                }
            }
        }
    }

    public function updated(PlayerTransfer $playerTransfer)
    {
    
    }

    public function deleted(PlayerTransfer $playerTransfer)
    {

    }
}

