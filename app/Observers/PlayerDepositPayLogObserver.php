<?php
namespace App\Observers;

use App\Lib\Cache\PlayerCache;
use App\Models\Log\PlayerDepositPayLog;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;

class PlayerDepositPayLogObserver
{
    public function created(PlayerDepositPayLog $playerDepositPayLog)
    {
        $continuousUnpaidFroze              = CarrierCache::getCarrierConfigure($playerDepositPayLog->carrier_id,'continuous_unpaid_froze');        
        if($continuousUnpaidFroze){
            $checkPlayerDepositPayLogs      = PlayerDepositPayLog::where('player_id',$playerDepositPayLog->player_id)->orderBy('id','desc')->take($continuousUnpaidFroze)->get();

            if($checkPlayerDepositPayLogs && count($checkPlayerDepositPayLogs) == $continuousUnpaidFroze){
                $i = 0;
                foreach ($checkPlayerDepositPayLogs as $k => $v) {
                    if($v->status==1){
                        $i++;
                    }
                }

                if(!$i){
                    Player::where('player_id',$playerDepositPayLog->player_id)->update(['frozen_status'=>4,'remark'=>'频繁刷单,系统自动冻结']);
                }
            }
        }
    }

    public function updated(PlayerDepositPayLog $playerDepositPayLog)
    {
        
    }

    public function deleted(PlayerDepositPayLog $playerDepositPayLog)
    {

    }
}