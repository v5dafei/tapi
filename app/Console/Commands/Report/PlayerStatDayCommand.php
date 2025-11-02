<?php

namespace App\Console\Commands\Report;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerStatDay;
use App\Lib\Cache\PlayerCache;
use App\Models\PlayerTransfer;
use App\Models\Player;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\Lock;
use App\Lib\Clog;

class PlayerStatDayCommand extends Command {
  
    protected $signature          = 'playerStatDay';

    protected $description        = 'Player Stat Day';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $time                    = time();
        $maxid                   = PlayerTransfer::where('stat_time',0)->where('is_tester',0)->orderBy('created_at','asc')->max('id');
        
        if($maxid){
            $cacheKey = "playerStatDay";
            $redisLock = Lock::addLock($cacheKey,10);

            if (!$redisLock) {
                \Log::info('用户playerStatDay统计已加锁，不能重复加锁');
                return false;
            } else {
                try {
                    \DB::beginTransaction();
                    $maxlevel             = PlayerTransfer::where('stat_time',0)->where('is_tester',0)->orderBy('created_at','asc')->orderBy('id','asc')->max('level');
                    do{
                        $playerAccountChanges = PlayerTransfer::where('stat_time',0)->where('is_tester',0)->where('level',$maxlevel)->orderBy('created_at','asc')->orderBy('id','asc')->get();
                        foreach ($playerAccountChanges as $key => $value) {
                            //判断创建日报表
                            $reportPlayerStatDay = ReportPlayerStatDay::where('day',date('Ymd', strtotime($value->created_at)))->where('player_id',$value->player_id)->first();

                            $currPlayer          = Player::select('prefix','is_live_streaming_account','win_lose_agent','carrier_id')->where('player_id',$value->player_id)->first();

                            if(!$reportPlayerStatDay){
                                $reportPlayerStatDay                                = new ReportPlayerStatDay();
                                $reportPlayerStatDay->carrier_id                    = $value->carrier_id;
                                $reportPlayerStatDay->rid                           = $value->rid;
                                $reportPlayerStatDay->top_id                        = $value->top_id;
                                $reportPlayerStatDay->parent_id                     = $value->parent_id;
                                $reportPlayerStatDay->player_id                     = $value->player_id;
                                $reportPlayerStatDay->is_tester                     = $value->is_tester;
                                $reportPlayerStatDay->user_name                     = $value->user_name;
                                $reportPlayerStatDay->level                         = $value->level;
                                $reportPlayerStatDay->prefix                        = $currPlayer->prefix;
                                $reportPlayerStatDay->win_lose_agent                = PlayerCache::getisWinLoseAgent($value->player_id);
                                $reportPlayerStatDay->type                          = PlayerCache::getPlayerType($value->carrier_id,$value->player_id);
                                $reportPlayerStatDay->day                           = date('Ymd', strtotime($value->created_at));
                                $reportPlayerStatDay->month                         = date('Ym', strtotime($value->created_at));
                                $reportPlayerStatDay->save();

                                $currRid                                            = PlayerCache::getPlayerRid($value->carrier_id,$value->player_id);
                                $playerIds                                          = explode('|',$currRid);

                                foreach ($playerIds as $k => $v) {
                                    if($v != $value->player_id ){
                                        $reportPlayerStatDay1 = ReportPlayerStatDay::where('day',date('Ymd', strtotime($value->created_at)))->where('player_id',$v)->first();
                                        if(!$reportPlayerStatDay1){
                                            $player1                                             = Player::where('player_id',$v)->first();
                                            $reportPlayerStatDay1                                = new ReportPlayerStatDay();
                                            $reportPlayerStatDay1->carrier_id                    = $player1->carrier_id;
                                            $reportPlayerStatDay1->rid                           = $player1->rid;
                                            $reportPlayerStatDay1->top_id                        = $player1->top_id;
                                            $reportPlayerStatDay1->parent_id                     = $player1->parent_id;
                                            $reportPlayerStatDay1->player_id                     = $player1->player_id;
                                            $reportPlayerStatDay1->is_tester                     = $player1->is_tester;
                                            $reportPlayerStatDay1->user_name                     = $player1->user_name;
                                            $reportPlayerStatDay1->level                         = $player1->level;
                                            $reportPlayerStatDay1->type                          = $player1->type;
                                            $reportPlayerStatDay1->prefix                        = $player1->prefix;
                                            $reportPlayerStatDay1->win_lose_agent                = PlayerCache::getisWinLoseAgent($player1->player_id);
                                            $reportPlayerStatDay1->day                           = date('Ymd', strtotime($value->created_at));
                                            $reportPlayerStatDay1->month                         = date('Ym', strtotime($value->created_at));
                                            $reportPlayerStatDay1->save();
                                        }
                                    }
                                }
                            }

                            //开始处理
                            $parentArr = explode('|',$value->rid);
                            
                            //线下充值 或 管理后台充值 
                            if($value->type=='recharge'){
                                $reportPlayerStatDay->recharge_amount      =  $value->amount+$reportPlayerStatDay->recharge_amount;
                                $reportPlayerStatDay->recharge_count       =  $reportPlayerStatDay->recharge_count + 1;
                                $reportPlayerStatDay->team_recharge_amount =  $value->amount+$reportPlayerStatDay->team_recharge_amount;
                                $reportPlayerStatDay->team_recharge_count  =  $reportPlayerStatDay->team_recharge_count + 1;

                                //判断之前是否有充过值
                                if(!$reportPlayerStatDay->recharge_count){
                                    ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_recharge_person_number'=>\DB::raw('team_recharge_person_number + 1')]);
                                }

                                //判断日首充复充
                                $existDayFirstRecharge = PlayerTransfer::where('player_id',$value->player_id)->where('is_tester',0)->where('type','recharge')->where('day',date('Ymd',strtotime($value->created_at)))->orderBy('id','asc')->first();
                                if($existDayFirstRecharge->id == $value->id){

                                    $existFirstRecharge = PlayerTransfer::where('player_id',$value->player_id)->where('is_tester',0)->where('type','recharge')->orderBy('id','asc')->first();
                                    //判断首充复充
                                    if($existFirstRecharge->id == $value->id){
                                        $reportPlayerStatDay->first_recharge_count =  1;
                                        $reportPlayerStatDay->team_first_recharge_count =  $reportPlayerStatDay->team_first_recharge_count+1;
                                        $reportPlayerStatDay->first_recharge_amount =  $value->amount;
                                        $reportPlayerStatDay->team_first_recharge_amount =  $reportPlayerStatDay->team_first_recharge_amount+$value->amount;

                                        ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_recharge_amount'=>\DB::raw('team_recharge_amount +'.$value->amount),'team_recharge_count'=>\DB::raw('team_recharge_count + 1'),'team_first_recharge_count'=>\DB::raw('team_first_recharge_count + 1'),'team_first_recharge_amount'=>\DB::raw('team_first_recharge_amount + '.$value->amount)]);

                                    } else{
                                        ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_recharge_amount'=>\DB::raw('team_recharge_amount +'.$value->amount),'team_recharge_count'=>\DB::raw('team_recharge_count + 1')]);
                                    }

                                } else{
                                        
                                    ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_recharge_amount'=>\DB::raw('team_recharge_amount +'.$value->amount),'team_recharge_count'=>\DB::raw('team_recharge_count + 1')]);
                                }
                                if($value->type=='recharge'){
                                    if(!empty($value->remark1)){
                                        $value->remark1 = intval($value->remark1);
                                        $reportPlayerStatDay->page_recharge_amount+=$value->remark1;
                                        $reportPlayerStatDay->page_team_recharge_amount+=$value->remark1;
                                        ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('level','<',$maxlevel)->where('day',date('Ymd',strtotime($value->created_at)))->update(['page_team_recharge_amount'=>\DB::raw('page_team_recharge_amount +'.$value->remark1)]);
                                    }   
                                }
                                $reportPlayerStatDay->save();
                            }

                            $materialIds             = CarrierCache::getCarrierMultipleConfigure($value->carrier_id,'materialIds',$value->prefix);
                            $materialIdsArr          = explode(',',$materialIds);

                            //活动礼金,幸运轮盘奖金,升级礼金，生日礼金,签到礼金,注册礼金
                            if(in_array($value->type,config('main')['giftadd'])){

                                $prefixs                 = config('main')['nostatsite'][$value->carrier_id];
                                
                                if(!in_array($value->prefix, $prefixs) && !in_array($value->player_id,$materialIdsArr)){
                                    $reportPlayerStatDay->gift                          =  $value->amount+$reportPlayerStatDay->gift;
                                    $reportPlayerStatDay->team_gift                     =  $value->amount+$reportPlayerStatDay->team_gift;
                                    $reportPlayerStatDay->save();
                                    ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_gift'=>\DB::raw('team_gift +'.$value->amount)]);
                                }
                            }

                            //活动扣减
                            if($value->type=='gift_transfer_reduce' || $value->type == 'inside_transfer_to'){
                                $prefixs = config('main')['nostatsite'][$value->carrier_id];
                                if(!in_array($value->prefix, $prefixs) && !in_array($value->player_id,$materialIdsArr)){

                                    $reportPlayerStatDay->gift                 =  $reportPlayerStatDay->gift - $value->amount;
                                    $reportPlayerStatDay->team_gift            =  $reportPlayerStatDay->team_gift - $value->amount;
                                    $reportPlayerStatDay->save();

                                    //更站上级
                                    ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_gift'=>\DB::raw('team_gift -'.$value->amount)]);
                                }
                            }

                            //直播号不统计提现
                            
                            $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($value->carrier_id,'player_dividends_method',$currPlayer->prefix);
                            if($playerDividendsMethod ==2 && $currPlayer->win_lose_agent==1){
                            } else{
                                //提现成功 或 管理后台提现
                                if($value->type=='withdraw_finish'){
                                    $reportPlayerStatDay->withdraw_amount      =  $reportPlayerStatDay->withdraw_amount + $value->amount;
                                    $reportPlayerStatDay->team_withdraw_amount =  $reportPlayerStatDay->team_withdraw_amount +$value->amount;
                                    $reportPlayerStatDay->save();

                                    //判断之前是否有提现
                                    if(!$reportPlayerStatDay->withdraw_amount){
                                        ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_withdraw_person_number'=>\DB::raw('team_withdraw_person_number + 1')]);
                                    }

                                    //更站上级
                                    ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_withdraw_amount'=>\DB::raw('team_withdraw_amount +'.$value->amount)]);
                                }
                            }
                            

                            //保底
                            if($value->type=='commission_from_child'){
                                $reportPlayerStatDay->commission                            =  $reportPlayerStatDay->commission + $value->amount;
                                $reportPlayerStatDay->team_commission                       =  $reportPlayerStatDay->team_commission + $value->amount;
                                $reportPlayerStatDay->save();

                                //更站上级
                                ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_commission'=>\DB::raw('team_commission +'.$value->amount)]);
                            }

                            //上级分红
                            if($value->type=='dividend_from_parent'){
                                $reportPlayerStatDay->dividend                            =  $reportPlayerStatDay->dividend + $value->amount;
                                $reportPlayerStatDay->team_dividend                       =  $reportPlayerStatDay->team_dividend + $value->amount;
                                $reportPlayerStatDay->save();

                                //更站上级
                                ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',date('Ymd',strtotime($value->created_at)))->where('level','<',$maxlevel)->update(['team_dividend'=>\DB::raw('team_dividend +'.$value->amount)]);
                            }

                            //负赢利代理相关统计
                            if($value->type=='agent_recharge'){
                                $reportPlayerStatDay->agent_recharge_amount      =  $value->amount+$reportPlayerStatDay->agent_recharge_amount;
                                $reportPlayerStatDay->team_agent_recharge_amount =  $value->amount+$reportPlayerStatDay->team_agent_recharge_amount;
                            }

                            PlayerTransfer::where('id',$value->id)->update(['stat_time'=>time()]);
                        }
                    $maxlevel--;
                    } while ($maxlevel);

                    \DB::commit();
                    Lock::release($redisLock);
                    return true;
                } catch (\Exception $e) {
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('报表统计操作异常：'.$e->getMessage());
                    return false;
                }
            }
        }
    }
}