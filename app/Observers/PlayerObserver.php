<?php
namespace App\Observers;

use App\Models\Report\ReportPlayerStatDay;
use App\Models\Conf\PlayerSetting;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Models\PlayerInviteCode;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\Player;
use App\Models\Carrier;
use App\Models\CarrierBankCard;
use App\Models\CarrierPlayerGrade;
use App\Models\CarrierActivityGiftCode;
use App\Models\Log\PlayerGiftCode;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\CarrierActivity;
use App\Lib\Clog;


class PlayerObserver
{
    public function created(Player $player)
    {
        $defaultGroupId = PlayerCache::getDefalutGroupId($player->carrier_id,$player->prefix);

        //判断用户rid是否有值
        if(is_null($player->rid)){
            if($player->parent_id){
                $parent          = Player::where('player_id',$player->parent_id)->first();
                $player->rid     = $parent->rid.'|'.$player->player_id;
            } else {
                $player->rid     = $player->player_id;
                $player->top_id  = $player->player_id;
            }
        }

        $player->player_group_id   = $defaultGroupId;
        $existCarrierMultipleFront =  CarrierMultipleFront::where('prefix',$player->prefix)->first();
        
        if($existCarrierMultipleFront){
            $idLength                = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'id_length',$player->prefix);
        } else{
            $idLength                = 5;
        }
        
        if($idLength==5){
            $startPlayerId           = 11111;
            $endPlayerId             = 99999;
        } elseif($idLength==6) {
            $startPlayerId           = 111111;
            $endPlayerId             = 999999;
        } elseif($idLength==7){
            $startPlayerId           = 1111111;
            $endPlayerId             = 9999999;
        }

        do {
            $extendId      = mt_rand($startPlayerId,$endPlayerId);
            $existExtendId = Player::where('prefix',$player->prefix)->where('extend_id',$extendId)->first();

        } while ($existExtendId);

        $player->extend_id = $extendId;

        //扩展id
        $player->save();

    	//创建帐号相关
        $playerAccount              = new PlayerAccount();
        $playerAccount->player_id   = $player->player_id;
        $playerAccount->carrier_id  = $player->carrier_id;
        $playerAccount->prefix      = $player->prefix;
        $playerAccount->top_id      = $player->top_id;
        $playerAccount->parent_id   = $player->parent_id;
        $playerAccount->rid         = $player->rid;
        $playerAccount->level       = $player->level;
        $playerAccount->user_name   = $player->user_name;
        $playerAccount->is_tester   = $player->is_tester;
        $playerAccount->balance     = 0;
        $playerAccount->frozen      = 0;
        $playerAccount->save();

    	//创建自已的返佣规则
    	if($player->user_name ==CarrierCache::getCarrierConfigure($player->carrier_id,'default_user_name')) {

            $playerSetting                              = new PlayerSetting();
                
            //正式直属   
            $playerSetting->player_id                   = $player->player_id;
            $playerSetting->carrier_id                  = $player->carrier_id;
            $playerSetting->top_id                      = $player->top_id;
            $playerSetting->parent_id                   = $player->parent_id;
            $playerSetting->rid                         = $player->rid;
            $playerSetting->level                       = $player->level;
            $playerSetting->is_tester                   = $player->is_tester;
            $playerSetting->user_name                   = $player->user_name;
            $playerSetting->prefix                      = $player->prefix;
            $playerSetting->lottoadds                   = CarrierCache::getCarrierConfigure($player->carrier_id,'default_lottery_odds');
            $playerSetting->guaranteed                  = 500;
            $playerSetting->earnings                    = 0; 
            $playerSetting->save();

            $playerInviteCode                              = new PlayerInviteCode();
            $playerInviteCode->carrier_id                  = $player->carrier_id;
            $playerInviteCode->player_id                   = $player->player_id;
            $playerInviteCode->username                    = $player->user_name;
            $playerInviteCode->is_tester                   = $player->is_tester;
            $playerInviteCode->rid                         = $player->rid;
            $playerInviteCode->prefix                      = $player->prefix;
            $playerInviteCode->type                        = 2;
            $playerInviteCode->lottoadds                   = CarrierCache::getCarrierConfigure($player->carrier_id,'default_lottery_odds');
            $playerInviteCode->earnings                    = 0;
            $playerInviteCode->code                        = 'www';
            $playerInviteCode->save();

            $todayPlayerStatDay                                = new ReportPlayerStatDay();
            $todayPlayerStatDay->carrier_id                    = $player->carrier_id;
            $todayPlayerStatDay->rid                           = $player->rid;
            $todayPlayerStatDay->top_id                        = $player->top_id;
            $todayPlayerStatDay->parent_id                     = $player->parent_id;
            $todayPlayerStatDay->player_id                     = $player->player_id;
            $todayPlayerStatDay->is_tester                     = $player->is_tester;
            $todayPlayerStatDay->user_name                     = $player->user_name;
            $todayPlayerStatDay->level                         = $player->level;
            $todayPlayerStatDay->type                          = $player->type;
            $todayPlayerStatDay->prefix                        = $player->prefix;
            $todayPlayerStatDay->win_lose_agent                = PlayerCache::getisWinLoseAgent($player->player_id);
            $todayPlayerStatDay->day                           = date('Ymd');
            $todayPlayerStatDay->month                         = bcdiv($todayPlayerStatDay->day,100,0);
            $todayPlayerStatDay->save();

            $tomorrowPlayerStatDay                                = new ReportPlayerStatDay();
            $tomorrowPlayerStatDay->carrier_id                    = $player->carrier_id;
            $tomorrowPlayerStatDay->rid                           = $player->rid;
            $tomorrowPlayerStatDay->top_id                        = $player->top_id;
            $tomorrowPlayerStatDay->parent_id                     = $player->parent_id;
            $tomorrowPlayerStatDay->player_id                     = $player->player_id;
            $tomorrowPlayerStatDay->is_tester                     = $player->is_tester;
            $tomorrowPlayerStatDay->user_name                     = $player->user_name;
            $tomorrowPlayerStatDay->level                         = $player->level;
            $tomorrowPlayerStatDay->type                          = $player->type;
            $tomorrowPlayerStatDay->prefix                        = $player->prefix;
            $tomorrowPlayerStatDay->win_lose_agent                = PlayerCache::getisWinLoseAgent($player->player_id);
            $tomorrowPlayerStatDay->day                           = date('Ymd',strtotime('+1 day'));
            $tomorrowPlayerStatDay->month                         = bcdiv($tomorrowPlayerStatDay->day,100,0);
            $tomorrowPlayerStatDay->save();

            //防批量注册
            if($existCarrierMultipleFront){
                $enableBatchRegisterFroze = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'enable_batch_register_froze',$player->prefix);
                if($enableBatchRegisterFroze && !empty($player->register_ip)){
                    $batchRegisterIpNumber = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'batch_register_ip_number',$player->prefix);
                    $sameIpNumberPlayerIds = Player::where('prefix',$player->prefix)->where('register_ip',$player->register_ip)->pluck('player_id')->toArray();
                    if(count($sameIpNumberPlayerIds) >= $batchRegisterIpNumber){
                        Player::whereIn('player_id',$player->player_id)->update(['frozen_status'=>4,'remark'=>'同IP多号注册']);
                    }
                }
            }
    	} else {
            //试玩不写入报表
            if($player->is_tester==1 || $player->is_tester==2){
                return ;
            }

            //生成报表
            $playerIds = explode('|',$player->rid);
            foreach ($playerIds as $key => $value) {
                PlayerCache::createPlayerStatDay($value,date('Ymd'),true);
            }

            //更新下级数量
            if($player->parent_id != 0) {
                $carrier = Carrier::where('id',$player->carrier_id)->first();

                Player::where('player_id',$player->parent_id)->update(['soncount'=>\DB::raw('soncount + 1')]);
                
                $parentArr = explode('|',$player->rid);

                Player::whereIn('player_id',$parentArr)->where('level','<',$player->level)->update(['descendantscount'=>\DB::raw('descendantscount + 1')]);

                if($player->win_lose_agent){
                    //更新日报表
                    ReportPlayerStatDay::where('player_id',$player->parent_id)->where('day',date('Ymd'))->update(['team_first_register'=>\DB::raw('team_first_register + 1')]);
                } else{
                    ReportPlayerStatDay::where('player_id',$player->parent_id)->where('day',date('Ymd'))->update(['team_first_register'=>\DB::raw('team_first_register + 1'),'team_member_first_register'=>\DB::raw('team_member_first_register + 1')]);
                }

                if($player->level > 2) {
                    $ridArr = explode('|',$player->rid);
                    if($player->win_lose_agent){
                        ReportPlayerStatDay::whereIn('player_id',$ridArr)->where('level','<',$player->level-1)->where('day',date('Ymd'))->update(['team_first_register'=>\DB::raw('team_first_register + 1')]);
                    } else{
                        ReportPlayerStatDay::whereIn('player_id',$ridArr)->where('level','<',$player->level-1)->where('day',date('Ymd'))->update(['team_first_register'=>\DB::raw('team_first_register + 1'),'team_member_first_register'=>\DB::raw('team_member_first_register + 1')]);
                    }   
                }
            }
        }

        if($existCarrierMultipleFront){
            $isRegistergift                   = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'is_registergift',$player->prefix);
            $isBindBankCard                   = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'is_bindbankcardorthirdwallet',$player->prefix);
            $registerProbability              = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'register_probability',$player->prefix);

            $giftmultiple                     = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'giftmultiple',$player->prefix);
            $registergiftLimitDayNumber       = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'registergift_limit_day_number',$player->prefix);
            $registergiftLimitCycle           = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'registergift_limit_cycle',$player->prefix);
            $agentSingleBackground            = CarrierCache::getCarrierMultipleConfigure($player->carrier_id,'agent_single_background',$player->prefix);

            if($agentSingleBackground==0 ||($agentSingleBackground==1 && $player->win_lose_agent==0)){
                //注册送    
                if($player->is_tester ==0 && $player->type==2 && $isRegistergift && $isBindBankCard==0){

                    $seedProbability                 = rand(1,100);
                    $registerProbabilityArr          = json_decode($registerProbability,true);
                    $map                             = [];
                    $preProbabilityArr               = 0;

                    foreach ($registerProbabilityArr as $key => &$value) {
                        $preProbabilityArr                  = $value['register_gift_probability']+$preProbabilityArr;
                        $value['register_gift_probability'] = $preProbabilityArr;
                    }

                    foreach ($registerProbabilityArr as $key1 => $value1) {
                        if($seedProbability<=$value1['register_gift_probability']){
                            $giftAmount = rand($value1['giftamount'],$value1['giftmaxamount']);
                            break;
                        }
                    }
                    if($registergiftLimitCycle==1){
                        $sendRegisterGiftNumber =  PlayerTransfer::where('type','register_gift')->where('prefix',$player->prefix)->where('day',date('Ymd'))->count();
                    } else{
                        $sendRegisterGiftNumber =  PlayerTransfer::where('type','register_gift')->where('prefix',$player->prefix)->count();
                    }
                    

                    if($registergiftLimitDayNumber > $sendRegisterGiftNumber && isset($giftAmount)){
                            //帐变记录
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
                                $playerTransfer->type                            = 'register_gift';
                                $playerTransfer->type_name                       = '注册礼金';
                                $playerTransfer->day_m                           = date('Ym',time());
                                $playerTransfer->day                             = date('Ymd',time());
                                $playerTransfer->amount                          = $giftAmount*10000;

                                $playerTransfer->before_balance                  = $playerAccount->balance;
                                $playerTransfer->balance                         = $playerAccount->balance + $giftAmount*10000;
                                $playerTransfer->before_frozen_balance           = 0;
                                $playerTransfer->frozen_balance                  = 0;

                                $playerTransfer->before_agent_balance         = $playerAccount->agentbalance;
                                $playerTransfer->agent_balance                = $playerAccount->agentbalance;
                                $playerTransfer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                                $playerTransfer->agent_frozen_balance         = $playerAccount->agentfrozen;

                                $playerTransfer->save();

                                $playerWithdrawFlowLimit                                  = new PlayerWithdrawFlowLimit();
                                $playerWithdrawFlowLimit->carrier_id                      = $playerAccount->carrier_id;
                                $playerWithdrawFlowLimit->top_id                          = $playerAccount->top_id;
                                $playerWithdrawFlowLimit->parent_id                       = $playerAccount->parent_id;
                                $playerWithdrawFlowLimit->rid                             = $playerAccount->rid;
                                $playerWithdrawFlowLimit->player_id                       = $playerAccount->player_id;
                                $playerWithdrawFlowLimit->user_name                       = $playerAccount->user_name;
                                $playerWithdrawFlowLimit->limit_amount                    = $giftAmount*$giftmultiple*10000;
                                $playerWithdrawFlowLimit->limit_type                      = 16;
                                $playerWithdrawFlowLimit->save();

                                $playerReceiveGiftCenter                     = new PlayerReceiveGiftCenter();
                                $playerReceiveGiftCenter->orderid            = 'LJ'.$playerAccount->player_id.time().rand('1','99');
                                $playerReceiveGiftCenter->carrier_id         = $playerAccount->carrier_id;
                                $playerReceiveGiftCenter->player_id          = $playerAccount->player_id;
                                $playerReceiveGiftCenter->user_name          = $playerAccount->user_name;
                                $playerReceiveGiftCenter->top_id             = $playerAccount->top_id;
                                $playerReceiveGiftCenter->parent_id          = $playerAccount->parent_id;
                                $playerReceiveGiftCenter->rid                = $playerAccount->rid;
                                $playerReceiveGiftCenter->type               = 20;
                                $playerReceiveGiftCenter->amount             = $giftAmount*10000;
                                $playerReceiveGiftCenter->invalidtime        = time()+31536000;
                                $playerReceiveGiftCenter->limitbetflow       = $giftAmount*$giftmultiple*10000;
                                $playerReceiveGiftCenter->status             = 1;
                                $playerReceiveGiftCenter->receivetime        = time();
                                $playerReceiveGiftCenter->save();

                                $playerAccount->balance                      = $playerAccount->balance+$giftAmount*10000;
                                $playerAccount->save();

                                //加入注册送统计
                                $statCarrierActivity =  CarrierActivity::where('carrier_id',$playerAccount->carrier_id)->where('prefix',$player->prefix)->where('name','like','%注册%')->first();
                                if($statCarrierActivity){
                                    $statCarrierActivity->person_account = $statCarrierActivity->person_account +1;
                                    $statCarrierActivity->account        = $statCarrierActivity->account +1;
                                    $statCarrierActivity->gift_amount    = $statCarrierActivity->gift_amount +$playerTransfer->amount;
                                    $statCarrierActivity->save();
                                }

                                \DB::commit();
                            } catch (\Exception $e) {
                                \DB::rollback();
                                Clog::recordabnormal('用户'.$playerAccount->player_id.'注册赠送彩金发放失败'.$e->getMessage()); 
                            }
                    }     
                }
                
            }
        }
    }

    public function updated(Player $player)
    {
        PlayerCache::forgetPlayerRid($player->carrier_id,$player->player_id);
        PlayerCache::forgetPlayerTester($player->player_id);
        PlayerCache::forgetPlayerLevel($player->carrier_id,$player->player_id);
    }

    public function deleted(Player $player)
    {
        PlayerCache::forgetPlayerRid($player->carrier_id,$player->player_id);
        PlayerCache::forgetPlayerTester($player->player_id);
        PlayerCache::forgetPlayerLevel($player->carrier_id,$player->player_id);
        PlayerCache::forgetPlayerId($player->carrier_id,$player->player_id);
        PlayerCache::forgetPlayerType($player->carrier_id,$player->player_id);
        PlayerCache::forgetPlayerUserName($player->player_id);
        PlayerCache::forgetCarrierId($player->player_id);
    }
}

