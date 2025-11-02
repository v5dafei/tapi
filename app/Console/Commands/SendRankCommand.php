<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Models\Log\RankingList;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierPreFixDomain;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Lib\Cache\Lock;
use App\Lib\Clog;

class SendRankCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendrank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sendrank';

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
        $carriers = Carrier::all();
        foreach ($carriers as $key => $value) {
            $carrierPreFixDomains             = CarrierPreFixDomain::all();

            foreach ($carrierPreFixDomains as $key1 => $value1) {
                $enableRankings = CarrierCache::getCarrierMultipleConfigure($value->id,'enable_rankings',$value1->prefix);
                if($enableRankings){
                    $rankingList = RankingList::where('carrier_id',$value->id)->where('endday',date('Ymd',strtotime('-1 day')))->where('prefix',$value1->prefix)->first();
                    if($rankingList){
                        //开始发放排行榜奖金
                        $rankingListArr = json_decode($rankingList->content,true);
                        foreach ($rankingListArr as $k5 => $v5) {
                            if(strpos($v5['user_name'],'_') !== false){
                                //发放排行榜奖金
                                $player   = Player::where('user_name',$v5['user_name'])->first();
                                $cacheKey = "player_" .$player->player_id;

                                $redisLock = Lock::addLock($cacheKey,10);

                                if (!$redisLock) {
                                } else {
                                    try {
                                        \DB::beginTransaction();

                                        $playerAccount                                   = PlayerAccount::where('player_id',$player->player_id)->lockForUpdate()->first();
                                        
                                        $playerTransfer                                  = new PlayerTransfer();
                                        $playerTransfer->prefix                          = $player->prefix;
                                        $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                                        $playerTransfer->rid                             = $playerAccount->rid;
                                        $playerTransfer->top_id                          = $playerAccount->top_id;
                                        $playerTransfer->parent_id                       = $playerAccount->parent_id;
                                        $playerTransfer->player_id                       = $playerAccount->player_id;
                                        $playerTransfer->is_tester                       = $playerAccount->is_tester;
                                        $playerTransfer->level                           = $playerAccount->level;
                                        $playerTransfer->user_name                       = $playerAccount->user_name;
                                        $playerTransfer->mode                            = 1;
                                        $playerTransfer->type                            = 'rank_list_gift';
                                        $playerTransfer->type_name                       = '排行榜礼金';
                                        $playerTransfer->day_m                           = date('Ym',time());
                                        $playerTransfer->day                             = date('Ymd',time());
                                        $playerTransfer->amount                          = $v5['bonus']*10000;
                                        $playerTransfer->before_balance                  = $playerAccount->balance;
                                        $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                                        $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                                        $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                                        $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                                        $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                                        $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;;
                                        $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;;
                                        $playerTransfer->save();

                                        $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                                        $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                                        $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                                        $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                                        $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                                        $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                                        $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;
                                        $playerWithdrawFlowLimit->limit_type             = 46;
                                        $playerWithdrawFlowLimit->limit_amount           = $playerTransfer->amount*3;
                                        $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                                        $playerWithdrawFlowLimit->is_finished            = 0;
                                        $playerWithdrawFlowLimit->operator_id            = 0;
                                        $playerWithdrawFlowLimit->save();

                                        $playerAccount->balance                          = $playerTransfer->balance;
                                        $playerAccount->save();

                                        \DB::commit();
                                        Lock::release($redisLock);
                                    } catch (\Exception $e) {
                                        \DB::rollback();
                                       Lock::release($redisLock);
                                       Clog::recordabnormal('发放排行榜礼金异常:'.$e->getMessage());
                                    }
                                }
                            }
                        }   
                    } 
                }
            }
        }
    }
}