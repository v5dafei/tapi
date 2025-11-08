<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Log\PlayerBetFlow;
use App\Lib\Cache\CarrierCache;
use App\Lib\Cache\PlayerCache;
use App\Lib\Cache\GameCache;
use App\Models\PlayerTransfer;
use App\Models\Carrier;
use App\Models\Player;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\Log\PlayerMiddleReturnWater;
use App\Lib\Clog;


class MiddleToPlayerStatDayCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'middleToPlayerStatDayCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'middleToPlayerStatDayCommand';

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
        $deletePlayerIds        = PlayerCache::getMaterialPlayerIds();
        $playerBetFlowMiddleIds = PlayerBetFlowMiddle::where('stat_time',0)->limit(2000)->pluck('id')->toArray();
        $playerBetFlowMiddleIds = array_diff($playerBetFlowMiddleIds,$deletePlayerIds);
        $playerBetFlowMiddle    = PlayerBetFlowMiddle::select('carrier_id','rid','player_id','day','game_category','prefix',\DB::raw('sum(bet_amount) as bet_amount'),\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'),\DB::raw('sum(process_available_bet_amount) as process_available_bet_amount'),'main_game_plat_id','prefix','carrier_id')->whereIn('id',$playerBetFlowMiddleIds)->groupBy(['day','player_id','game_category'])->get(); 

        foreach ($playerBetFlowMiddle as $k => $v) {

            $currUserName            = PlayerCache::getPlayerUserName($v->player_id);
            $materialIds    = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'materialIds',$v->prefix);
            if(empty($materialIds)){
                $materialIdsArr = [];
            } else{
                $materialIdsArr = explode(',',$materialIds);
            }

            if(in_array($v->prefix,config('main')['nostatsite'][$v->carrier_id]) || in_array($v->player_id,$materialIdsArr)){
                continue;
            }
            $maxBetTime                 = PlayerBetFlowMiddle::where('player_id',$v->player_id)->where('day',$v->day)->where('game_category',$v->game_category)->whereIn('id',$playerBetFlowMiddleIds)->max('bet_time');
            $playerRid                  = PlayerCache::getPlayerRid($v->carrier_id,$v->player_id);
            $playerLevel                = PlayerCache::getPlayerLevel($v->carrier_id,$v->player_id);
            $playerSetting              = PlayerCache::getPlayerSetting($v->player_id);
            $defaultAgentMember         = CarrierCache::getDefaultAgent($v->carrier_id);                     
            $parentArr                  = explode('|',$playerRid);
            $isBetFlowConvert           = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'is_bet_flow_convert',$v->prefix);
            $enableBetGradientRebate    = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'enable_bet_gradient_rebate',$v->prefix);

            try {
                \DB::beginTransaction();
                
                $time                = time();    
                $amount              = $v->bet_amount*10000;
                $update              = [];

                $reportPlayerStatDay = ReportPlayerStatDay::where('player_id',$v->player_id)->where('day',$v->day)->lockForUpdate()->first(); 

                 if(!$reportPlayerStatDay){
                    $currPlayer                                         = Player::where('player_id',$v->player_id)->first();
                    $reportPlayerStatDay                                = new ReportPlayerStatDay();
                    $reportPlayerStatDay->carrier_id                    = $currPlayer->carrier_id;
                    $reportPlayerStatDay->rid                           = $currPlayer->rid;
                    $reportPlayerStatDay->top_id                        = $currPlayer->top_id;
                    $reportPlayerStatDay->parent_id                     = $currPlayer->parent_id;
                    $reportPlayerStatDay->player_id                     = $currPlayer->player_id;
                    $reportPlayerStatDay->is_tester                     = $currPlayer->is_tester;
                    $reportPlayerStatDay->user_name                     = $currPlayer->user_name;
                    $reportPlayerStatDay->level                         = $currPlayer->level;
                    $reportPlayerStatDay->type                          = $currPlayer->type;
                    $reportPlayerStatDay->win_lose_agent                = $currPlayer->win_lose_agent;
                    $reportPlayerStatDay->prefix                        = $currPlayer->prefix;
                    $reportPlayerStatDay->day                           = $v->day;
                    $reportPlayerStatDay->month                         = date('Ym',strtotime($v->day));
                    $reportPlayerStatDay->save();
                }

                //team_available_bets   团队三方有效投注   
                //1=真人，2＝电子，3＝电竞，4＝棋牌，5＝体育，6＝彩票 7=捕鱼
                 switch ($v->game_category) {
                    case '1':
                        $reportPlayerStatDay->casino_available_bets        = $reportPlayerStatDay->casino_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_casino_available_bets   = $reportPlayerStatDay->team_casino_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->available_bets               = $reportPlayerStatDay->available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_available_bets          = $reportPlayerStatDay->team_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->casino_winorloss             = $reportPlayerStatDay->casino_winorloss  - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_casino_winorloss        = $reportPlayerStatDay->team_casino_winorloss - $v->company_win_amount*10000;
                        $reportPlayerStatDay->win_amount                   = $reportPlayerStatDay->win_amount - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_win_amount              = $reportPlayerStatDay->team_win_amount - $v->company_win_amount*10000;

                        if($reportPlayerStatDay->have_bet != 1){
                            $reportPlayerStatDay->have_bet                 = 1;
                            $reportPlayerStatDay->team_have_bet            = $reportPlayerStatDay->team_have_bet+1;
                            $update['team_have_bet']                       = \DB::raw('team_have_bet + 1');
                        }
                                            
                        $update['team_win_amount']                           = \DB::raw('team_win_amount -'.$v->company_win_amount*10000);
                        $update['team_available_bets']                       = \DB::raw('team_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_casino_available_bets']                = \DB::raw('team_casino_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_casino_winorloss']                     = \DB::raw('team_casino_winorloss -'.$v->company_win_amount*10000);
                        break;
                    case '2':
                        $reportPlayerStatDay->electronic_available_bets        = $reportPlayerStatDay->electronic_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_electronic_available_bets   = $reportPlayerStatDay->team_electronic_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->available_bets                   = $reportPlayerStatDay->available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_available_bets              = $reportPlayerStatDay->team_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->electronic_winorloss             = $reportPlayerStatDay->electronic_winorloss  - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_electronic_winorloss        = $reportPlayerStatDay->team_electronic_winorloss - $v->company_win_amount*10000;
                        $reportPlayerStatDay->win_amount                       = $reportPlayerStatDay->win_amount - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_win_amount                  = $reportPlayerStatDay->team_win_amount - $v->company_win_amount*10000;

                        if($reportPlayerStatDay->have_bet != 1){
                            $reportPlayerStatDay->have_bet                 = 1;
                            $reportPlayerStatDay->team_have_bet            = $reportPlayerStatDay->team_have_bet+1;
                            $update['team_have_bet']                       = \DB::raw('team_have_bet + 1');
                        }

                        $update['team_win_amount']                            = \DB::raw('team_win_amount -'.$v->company_win_amount*10000);
                        $update['team_available_bets']                         = \DB::raw('team_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_electronic_available_bets']              = \DB::raw('team_electronic_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_electronic_winorloss']                   = \DB::raw('team_electronic_winorloss -'.$v->company_win_amount*10000);
                        break;
                    case '3':
                        $reportPlayerStatDay->esport_available_bets            = $reportPlayerStatDay->esport_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_esport_available_bets       = $reportPlayerStatDay->team_esport_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->available_bets                   = $reportPlayerStatDay->available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_available_bets              = $reportPlayerStatDay->team_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->esport_winorloss                 = $reportPlayerStatDay->esport_winorloss  - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_esport_winorloss            = $reportPlayerStatDay->team_esport_winorloss - $v->company_win_amount*10000;
                        $reportPlayerStatDay->win_amount                       = $reportPlayerStatDay->win_amount - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_win_amount                  = $reportPlayerStatDay->team_win_amount - $v->company_win_amount*10000;

                        if($reportPlayerStatDay->have_bet != 1){
                            $reportPlayerStatDay->have_bet                 = 1;
                            $reportPlayerStatDay->team_have_bet            = $reportPlayerStatDay->team_have_bet+1;
                            $update['team_have_bet']                       = \DB::raw('team_have_bet + 1');
                        }

                        $update['team_win_amount']                            = \DB::raw('team_win_amount -'.$v->company_win_amount*10000);
                        $update['team_available_bets']                         = \DB::raw('team_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_esport_available_bets']                  = \DB::raw('team_esport_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_esport_winorloss']                       = \DB::raw('team_esport_winorloss -'.$v->company_win_amount*10000);
                        break;
                    case '4':
                        $reportPlayerStatDay->card_available_bets            = $reportPlayerStatDay->card_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_card_available_bets       = $reportPlayerStatDay->team_card_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->available_bets                 = $reportPlayerStatDay->available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_available_bets            = $reportPlayerStatDay->team_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->card_winorloss                 = $reportPlayerStatDay->card_winorloss  - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_card_winorloss            = $reportPlayerStatDay->team_card_winorloss - $v->company_win_amount*10000;
                        $reportPlayerStatDay->win_amount                     = $reportPlayerStatDay->win_amount - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_win_amount                = $reportPlayerStatDay->team_win_amount - $v->company_win_amount*10000;

                        if($reportPlayerStatDay->have_bet != 1){
                            $reportPlayerStatDay->have_bet                 = 1;
                            $reportPlayerStatDay->team_have_bet            = $reportPlayerStatDay->team_have_bet+1;
                            $update['team_have_bet']                       = \DB::raw('team_have_bet + 1');
                        }

                        $update['team_win_amount']                            = \DB::raw('team_win_amount -'.$v->company_win_amount*10000);
                        $update['team_available_bets']                       = \DB::raw('team_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_card_available_bets']                  = \DB::raw('team_card_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_card_winorloss']                       = \DB::raw('team_card_winorloss -'.$v->company_win_amount*10000);
                        break;
                    case '5':
                        $reportPlayerStatDay->sport_available_bets            = $reportPlayerStatDay->sport_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_sport_available_bets       = $reportPlayerStatDay->team_sport_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->available_bets                  = $reportPlayerStatDay->available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_available_bets             = $reportPlayerStatDay->team_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->sport_winorloss                 = $reportPlayerStatDay->sport_winorloss  - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_sport_winorloss            = $reportPlayerStatDay->team_sport_winorloss - $v->company_win_amount*10000;
                        $reportPlayerStatDay->win_amount                      = $reportPlayerStatDay->win_amount - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_win_amount                 = $reportPlayerStatDay->team_win_amount - $v->company_win_amount*10000;

                        if($reportPlayerStatDay->have_bet != 1){
                            $reportPlayerStatDay->have_bet                 = 1;
                            $reportPlayerStatDay->team_have_bet            = $reportPlayerStatDay->team_have_bet+1;
                            $update['team_have_bet']                       = \DB::raw('team_have_bet + 1');
                        }

                        $update['team_win_amount']                            = \DB::raw('team_win_amount -'.$v->company_win_amount*10000);
                        $update['team_available_bets']                        = \DB::raw('team_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_sport_available_bets']                  = \DB::raw('team_sport_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_sport_winorloss']                       = \DB::raw('team_sport_winorloss -'.$v->company_win_amount*10000);
                        break;
                    case '6':
                        $reportPlayerStatDay->lottery_available_bets            = $reportPlayerStatDay->lottery_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_lottery_available_bets       = $reportPlayerStatDay->team_lottery_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->lottery_winorloss                 = $reportPlayerStatDay->lottery_winorloss  - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_lottery_winorloss            = $reportPlayerStatDay->team_lottery_winorloss - $v->company_win_amount*10000;

                        if($reportPlayerStatDay->have_bet != 1){
                            $reportPlayerStatDay->have_bet                 = 1;
                            $reportPlayerStatDay->team_have_bet            = $reportPlayerStatDay->team_have_bet+1;
                            $update['team_have_bet']                       = \DB::raw('team_have_bet + 1');
                        }

                        $update['team_lottery_available_bets']                  = \DB::raw('team_lottery_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_lottery_winorloss']                       = \DB::raw('team_lottery_winorloss -'.$v->company_win_amount*10000);
                        break;
                    case '7':
                        $reportPlayerStatDay->fish_available_bets            = $reportPlayerStatDay->fish_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_fish_available_bets       = $reportPlayerStatDay->team_fish_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->available_bets                 = $reportPlayerStatDay->available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->team_available_bets            = $reportPlayerStatDay->team_available_bets + $v->available_bet_amount*10000;
                        $reportPlayerStatDay->fish_winorloss                 = $reportPlayerStatDay->fish_winorloss  - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_fish_winorloss            = $reportPlayerStatDay->team_fish_winorloss - $v->company_win_amount*10000;
                        $reportPlayerStatDay->win_amount                     = $reportPlayerStatDay->win_amount - $v->company_win_amount*10000;
                        $reportPlayerStatDay->team_win_amount                = $reportPlayerStatDay->team_win_amount - $v->company_win_amount*10000;

                        if($reportPlayerStatDay->have_bet != 1){
                            $reportPlayerStatDay->have_bet                 = 1;
                            $reportPlayerStatDay->team_have_bet            = $reportPlayerStatDay->team_have_bet+1;
                            $update['team_have_bet']                       = \DB::raw('team_have_bet + 1');
                        }

                        $update['team_win_amount']                           = \DB::raw('team_win_amount -'.$v->company_win_amount*10000);
                        $update['team_available_bets']                       = \DB::raw('team_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_fish_available_bets']                  = \DB::raw('team_fish_available_bets +'.$v->available_bet_amount*10000);
                        $update['team_fish_winorloss']                       = \DB::raw('team_fish_winorloss -'.$v->company_win_amount*10000);
                        break;
                    default:
                        break;
                    }

                $reportPlayerStatDay->save();
        
                //查询上级统计报表是否存在
                if(count($parentArr)){
                    foreach ($parentArr as $key => $value) {
                        PlayerCache::createPlayerStatDay($value,$v->day);
                    }
                }
        
                //更站上级
                ReportPlayerStatDay::whereIn('player_id',$parentArr)->where('day',$v->day)->where('level','<',$playerLevel)->update($update);

                //开始帐变计算返水
                $arr             = [];

                foreach ($parentArr as  $value) {
                    $playerSettingTep   = PlayerCache::getPlayerSetting($value);
                    $arr[$value]        = ['casino_returnwater'=>0,'electron_returnwater'=>0,'electron_sport_returnwater'=>0,'fish_returnwater'=>0,'card_returnwater'=>0,'sport_returnwater'=>0,'lott_returnwater'=>0,'amount'=>0];
                }

                //获取返水当日返水标准
                $videoBetGradientRebate     = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'video_bet_gradient_rebate',$v->prefix);
                $eleBetGradientRebate       = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'ele_bet_gradient_rebate',$v->prefix);
                $esportBetGradientRebate    = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'esport_bet_gradient_rebate',$v->prefix);
                $cardBetGradientRebate      = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'card_bet_gradient_rebate',$v->prefix);
                $sportBetGradientRebate     = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'sport_bet_gradient_rebate',$v->prefix);
                $fishBetGradientRebate      = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'fish_bet_gradient_rebate',$v->prefix);
                $lottBetGradientRebate      = CarrierCache::getCarrierMultipleConfigure($v->carrier_id,'lott_bet_gradient_rebate',$v->prefix);
                    
                $videoBetGradientRebate     = json_decode($videoBetGradientRebate,true);
                $eleBetGradientRebate       = json_decode($eleBetGradientRebate,true);
                $esportBetGradientRebate    = json_decode($esportBetGradientRebate,true);
                $cardBetGradientRebate      = json_decode($cardBetGradientRebate,true);
                $sportBetGradientRebate     = json_decode($sportBetGradientRebate,true);
                $fishBetGradientRebate      = json_decode($fishBetGradientRebate,true);
                $lottBetGradientRebate      = json_decode($lottBetGradientRebate,true);

                $arr[$v->player_id]['casino_returnwater']         = $videoBetGradientRebate[0]['bonus'];
                $arr[$v->player_id]['electron_returnwater']       = $eleBetGradientRebate[0]['bonus'];
                $arr[$v->player_id]['electron_sport_returnwater'] = $esportBetGradientRebate[0]['bonus'];
                $arr[$v->player_id]['fish_returnwater']           = $fishBetGradientRebate[0]['bonus'];
                $arr[$v->player_id]['card_returnwater']           = $cardBetGradientRebate[0]['bonus'];
                $arr[$v->player_id]['sport_returnwater']          = $sportBetGradientRebate[0]['bonus'];
                $arr[$v->player_id]['lott_returnwater']           = $lottBetGradientRebate[0]['bonus'];

                //获取用户当天有效投注额
                $playerDayBetFlowMiddles    = PlayerBetFlowMiddle::select(\DB::raw('sum(available_bet_amount) as available_bet_amount'),\DB::raw('sum(process_available_bet_amount) as process_available_bet_amount'),'game_category')->where('player_id',$v->player_id)->where('day',$v->day)->groupBy('game_category')->get();

                foreach ($playerDayBetFlowMiddles as $key => $value) {
                    switch ($value->game_category) {
                        case '1':
                            foreach ($videoBetGradientRebate as $k1 => $v1) {
                                if($value->process_available_bet_amount >$v1['probability']){
                                    $arr[$v->player_id]['casino_returnwater']         = $v1['bonus'];
                                }
                            }
                            break;
                        case '2':
                            foreach ($eleBetGradientRebate as $k1 => $v1) {
                                if($value->process_available_bet_amount >$v1['probability']){
                                    $arr[$v->player_id]['electron_returnwater']         = $v1['bonus'];
                                }
                            }
                            break;
                        case '3':
                            foreach ($esportBetGradientRebate as $k1 => $v1) {
                                if($value->process_available_bet_amount >$v1['probability']){
                                    $arr[$v->player_id]['electron_sport_returnwater']         = $v1['bonus'];
                                }
                            }
                            break;
                        case '4':
                            foreach ($cardBetGradientRebate as $k1 => $v1) {
                                if($value->process_available_bet_amount >$v1['probability']){
                                    $arr[$v->player_id]['card_returnwater']         = $v1['bonus'];
                                }
                            }
                            break;
                        case '5':
                            foreach ($sportBetGradientRebate as $k1 => $v1) {
                                if($value->process_available_bet_amount >$v1['probability']){
                                    $arr[$v->player_id]['sport_returnwater']         = $v1['bonus'];
                                }
                            }
                            break;
                        case '6':
                            foreach ($lottBetGradientRebate as $k1 => $v1) {
                                if($value->process_available_bet_amount >$v1['probability']){
                                    $arr[$v->player_id]['lott_returnwater']         = $v1['bonus'];
                                }
                            }
                            break;
                        case '7':
                            foreach ($fishBetGradientRebate as $k1 => $v1) {
                                if($value->process_available_bet_amount >$v1['probability']){
                                    $arr[$v->player_id]['fish_returnwater']         = $v1['bonus'];
                                }
                            }
                            break;
                        default:
                            break;
                        }
                    }

                $return = '';
                switch ($v->game_category) {
                    case '1':
                        $return = 'casino_returnwater';
                        break;
                    case '2':
                        $return = 'electron_returnwater';
                        break;
                    case '3':
                        $return = 'electron_sport_returnwater';
                        break;
                    case '4':
                        $return = 'card_returnwater';
                        break;
                    case '5':
                        $return = 'sport_returnwater';
                        break;
                    case '6':
                        $return = 'lott_returnwater';
                        break;
                    case '7':
                        $return = 'fish_returnwater';
                        break;
                    default:
                        break;
                }

                //获取自已的洗码
                if($enableBetGradientRebate){
                    $arr[$v->player_id]['amount'] = $arr[$v->player_id]['amount']+ $v->process_available_bet_amount*$arr[$v->player_id][$return]*100;
                } else{
                    $arr[$v->player_id]['amount'] = 0;
                }

                $amount                       = $v->process_available_bet_amount*10000;

                //流水限制
                $playWithdrawFlowLimits       = PlayerWithdrawFlowLimit::where('player_id',$v->player_id)->where('is_finished',0)->orderBy('id','asc')->get();
                $diyAmount                   = $amount;

                foreach ($playWithdrawFlowLimits as $key1 => $value1) {
                    //投注先于流水限制中止
                    if($maxBetTime < strtotime($value1->created_at)){
                        break;
                    }
                    
                    //流水限制处理
                    if(!empty($value1->betflow_limit_main_game_plat_id)){
                        $betflowLimitMainGamePlatIds = explode(',',$value1->betflow_limit_main_game_plat_id);
                        if(in_array($v->main_game_plat_id,$betflowLimitMainGamePlatIds)){
                            if(!empty($value1->betflow_limit_category)){
                                $gameCategorys = explode(',',$value1->betflow_limit_category);
                                if(in_array($v->game_category,$gameCategorys)){
                                    $diff = $value1->limit_amount - $value1->complete_limit_amount;
                                    if($diyAmount >= $diff) {
                                        $value1->complete_limit_amount = $value1->limit_amount;
                                        $value1->is_finished           = 1;
                                        $value1->save();
                                    } else {
                                        $value1->complete_limit_amount = $value1->complete_limit_amount+$diyAmount;
                                        $value1->save();
                                        break;
                                    }
                                } else{
                                    break;
                                }
                            } else{
                               //没有限制分类
                                $diff = $value1->limit_amount - $value1->complete_limit_amount;
                                if($diyAmount >= $diff) {
                                    $value1->complete_limit_amount = $value1->limit_amount;
                                    $value1->is_finished           = 1;
                                    $value1->save();
                                } else {
                                    $value1->complete_limit_amount = $value1->complete_limit_amount+$diyAmount;
                                    $value1->save();
                                    break;
                                } 
                            }
                        } else{
                            break;
                        }
                    } else {
                        //没有限制平台
                        if(!empty($value1->betflow_limit_category)){
                            //有限制分类
                            $gameCategorys = explode(',',$value1->betflow_limit_category);
                            if(in_array($v->game_category,$gameCategorys)){
                                $diff = $value1->limit_amount - $value1->complete_limit_amount;
                                if($diyAmount >= $diff) {
                                    $value1->complete_limit_amount = $value1->limit_amount;
                                    $value1->is_finished           = 1;
                                    $value1->save();
                                } else {
                                    $value1->complete_limit_amount = $value1->complete_limit_amount+$diyAmount;
                                    $value1->save();
                                    break;
                                }
                            } else{
                                break;
                            }
                        } else{
                            //没有限制分类
                            $diff = $value1->limit_amount - $value1->complete_limit_amount;
                            if($diyAmount >= $diff) {
                                $value1->complete_limit_amount = $value1->limit_amount;
                                $value1->is_finished           = 1;
                                $value1->save();
                            } else {
                                $value1->complete_limit_amount = $value1->complete_limit_amount+$diyAmount;
                                $value1->save();
                                break;
                            }
                        }
                    }
                }

                foreach ($arr as $key => $value) {
                    //查询金额

                    if($value['amount'] == 0){
                        continue;
                    }

                    $playerMiddleReturnWater                       = new PlayerMiddleReturnWater();
                    $playerMiddleReturnWater->carrier_id           = $v->carrier_id;
                    $playerMiddleReturnWater->player_id            = $v->player_id;
                    $playerMiddleReturnWater->rid                  = $v->rid;
                    $playerMiddleReturnWater->amount               = intval($value['amount']);
                    $playerMiddleReturnWater->game_category        = $v->game_category;
                        
                    if($playerMiddleReturnWater->amount!=0){
                        $playerMiddleReturnWater->save();
                    }
                }

               \DB::commit();
            } catch (\Exception $e) {
                \DB::rollback();
                Clog::recordabnormal('返水计入统计表操作异常：'.$e->getMessage());
            } 
        }

        PlayerBetFlowMiddle::whereIn('id',$playerBetFlowMiddleIds)->update(['stat_time'=>time()]);
    }

}