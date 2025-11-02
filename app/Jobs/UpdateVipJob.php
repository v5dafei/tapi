<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\CarrierPlayerGrade;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Models\Player;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerLevelUpdate;
use App\Models\PlayerReceiveGiftCenter;
use App\Lib\Clog;


class UpdateVipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userIds      = null;

    public function __construct($userIds) {
        $this->userIds      = $userIds;
    }

    public function handle()
    {
        $this->updateVip();
    }

    public function updateVip()
    {
        foreach ($this->userIds as $key => $value) {
            $user                  = Player::where('player_id',$value)->first();
            if(!$user){
                \Log::info('用户升级'.$value.'不存在');
            }
            if(!$user || $user->is_tester>0){
                //试玩帐号与带玩帐号与负盈利玩家不进行升级
                continue;
            }
            $reportPlayerStatDay     = ReportPlayerStatDay::select(\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'))->where('player_id',$value)->first();

            $currLevel               = CarrierPlayerGrade::where('id',$user->player_level_id)->first();
            $allCarrierPlayerLevel   = CarrierPlayerGrade::where('carrier_id',$user->carrier_id)->where('prefix',$user->prefix)->where('sort','>',$currLevel->sort)->orderBy('sort','desc')->get();

            if(count($allCarrierPlayerLevel)){
                foreach ($allCarrierPlayerLevel as $k => $v) {
                    $upgradeRule    = unserialize($v->upgrade_rule);
                    $available_bets = $reportPlayerStatDay->available_bets + $reportPlayerStatDay->lottery_available_bets ;
                    
                   if($available_bets >= $upgradeRule['availablebet']*10000 + $user->overweight*10000){
                        try {
                            \DB::beginTransaction();

                            $user->player_level_id = $v->id;
                            $user->save();

                            $playerLevelUpdate             = new PlayerLevelUpdate();
                            $playerLevelUpdate->carrier_id = $user->carrier_id;
                            $playerLevelUpdate->player_id  = $user->player_id;
                            $playerLevelUpdate->user_name  = $user->user_name;
                            $playerLevelUpdate->type       = 1;
                            $playerLevelUpdate->time       = time();
                            $playerLevelUpdate->day        = date('Ymd');
                            $playerLevelUpdate->save();

                            if($v->updategift==0){
                                return;
                            }

                            $existPlayerTransfer   = PlayerReceiveGiftCenter::where('player_id',$user->player_id)->where('type',37)->where('remark',$v->id)->first();
                            if(!$existPlayerTransfer){
                                $playerReceiveGiftCenter                     = new PlayerReceiveGiftCenter();
                                $playerReceiveGiftCenter->orderid            = 'LJ'.$user->player_id.time().rand('1','99');
                                $playerReceiveGiftCenter->carrier_id         = $user->carrier_id;
                                $playerReceiveGiftCenter->player_id          = $user->player_id;
                                $playerReceiveGiftCenter->user_name          = $user->user_name;
                                $playerReceiveGiftCenter->top_id             = $user->top_id;
                                $playerReceiveGiftCenter->parent_id          = $user->parent_id;
                                $playerReceiveGiftCenter->rid                = $user->rid;
                                $playerReceiveGiftCenter->type               = 37;
                                $playerReceiveGiftCenter->amount             = $v->updategift*10000;
                                $playerReceiveGiftCenter->invalidtime        = time()+31536000;
                                $playerReceiveGiftCenter->limitbetflow       = $v->updategift*10000*$v->turnover_multiple;
                                $playerReceiveGiftCenter->remark             = $v->id;
                                $playerReceiveGiftCenter->save();        
                            }
                            \DB::commit();
                        } catch (\Exception $e) {
                            \DB::rollback();
                            Clog::recordabnormal('会员升级发放礼金异常:'.$e->getMessage());  
                            return;
                        }
                        //一次升多个级别时处理
                        $currCarrierPlayerGrade = CarrierPlayerGrade::where('carrier_id',$user->carrier_id)->where('id',$user->player_level_id)->first();
                        foreach($allCarrierPlayerLevel as $k1 => $v1){
                            if($v1->sort < $currCarrierPlayerGrade->sort){
                                if(!$v1->is_default){
                                    $cyclePlayerReceiveGiftCenter                = PlayerReceiveGiftCenter::where('player_id',$user->player_id)->where('type',37)->where('remark',$v1->id)->first();

                                    $playerReceiveGiftCenter                     = new PlayerReceiveGiftCenter();
                                    $playerReceiveGiftCenter->orderid            = 'LJ'.$user->player_id.time().rand('1','99');
                                    $playerReceiveGiftCenter->carrier_id         = $user->carrier_id;
                                    $playerReceiveGiftCenter->player_id          = $user->player_id;
                                    $playerReceiveGiftCenter->user_name          = $user->user_name;
                                    $playerReceiveGiftCenter->top_id             = $user->top_id;
                                    $playerReceiveGiftCenter->parent_id          = $user->parent_id;
                                    $playerReceiveGiftCenter->rid                = $user->rid;
                                    $playerReceiveGiftCenter->type               = 37;
                                    $playerReceiveGiftCenter->amount             = $v1->updategift*10000;
                                    $playerReceiveGiftCenter->invalidtime        = time()+31536000;
                                    $playerReceiveGiftCenter->limitbetflow       = $v1->updategift*10000*$v1->turnover_multiple;
                                    $playerReceiveGiftCenter->remark             = $v1->id;
                                    $playerReceiveGiftCenter->save();     
                                }
                            }
                        }

                        //一次升多个级别时处理结束

                        return;
                   }
                }
            }
        }
    }
}
