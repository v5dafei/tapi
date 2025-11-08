<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Web\BaseController;
use App\Lib\Cache\CarrierCache;
use App\Models\Map\CarrierPlayerLevelBankCardMap;
use App\Models\Map\CarrierPlayerLevelBankCard;
use App\Models\Map\CarrierGame;
use App\Models\Report\ReportPlayerEarnings;
use App\Models\Report\ReportPlayerStatDay;
use App\Models\Report\ReportPlayerStatBetFlow;
use App\Models\Log\PlayerDepositPayLog;
use App\Models\Conf\PlayerSetting;
use App\Models\Conf\CarrierThirdPartPay;
use App\Models\Def\MainGamePlat;
use App\Models\Def\Development;
use App\Models\Map\CarrierGamePlat;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Log\PlayerLogin;
use App\Models\Log\PlayerBetFlow;
use App\Models\Log\PlayerOperate;
use App\Models\Log\PlayerMiddleReturnWater;
use App\Models\Conf\CarrierPayChannel;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\CarrierBankCard;
use App\Models\PlayerGameAccount;
use App\Models\PlayerTransfer;
use App\Models\PlayerBankCard;
use App\Models\PlayerInviteCode;
use App\Models\PlayerAccount;
use App\Models\CarrierPlayerGrade;
use App\Models\CarrierActivityLuckDraw;
use App\Models\CarrierActivityPlayerLuckDraw;
use App\Models\PlayerDigitalAddress;
use App\Models\PlayerMessage;
use App\Models\CarrierDigitalAddress;
use App\Models\PayChannelGroup;
use App\Models\CarrierBankCardType;
use App\Models\PlayerGameCollect;
use App\Models\Player;
use App\Models\Carrier;
use App\Models\Def\ThirdWallet;
use App\Models\CarrierFeedback;
use App\Models\Area;
use App\Game\Game;
use App\Lib\S3;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\PlayerCache;
use App\Models\PlayerReceiveGiftCenter;
use App\Models\PlayerCommission;
use App\Models\Log\RankingList;
use App\Models\CarrierCapitationFeeSetting;
use App\Models\Log\PlayerCapitationFee;
use App\Lib\DevidendMode1;
use App\Lib\DevidendMode2;
use App\Lib\DevidendMode3;
use App\Lib\DevidendMode5;
use App\Lib\DevidendMode4;
use App\Lib\Cache\Lock;
use App\Models\Map\CarrierPreFixGamePlat;
use App\Models\PlayerRealCommission;
use App\Models\PlayerHoldGiftCode;
use App\Models\Log\PlayerRealDividendTongbao;
use App\Models\Report\ReportRealPlayerEarnings;
use App\Lib\Clog;
use App\Models\Log\PlayerWithdraw;
use App\Models\PlayerAlipay;

class PlayerController extends BaseController
{

    public function getVip()
    {
        $carrierPlayerLevel = CarrierPlayerGrade::select('level_name','id')->where('id',$this->user->player_level_id)->first();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$carrierPlayerLevel);
    }

    public function getBalance()
    {   
        $playerAccount     = PlayerAccount::select('balance','frozen','agentbalance','agentfrozen')->where('player_id',$this->user->player_id)->first();
        $mainGamePlatCodes = CarrierGamePlat::select('def_main_game_plats.main_game_plat_code')
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','map_carrier_game_plats.game_plat_id')
            ->where('carrier_id',$this->user->carrier_id)
            ->where('map_carrier_game_plats.status',1)
            ->pluck('def_main_game_plats.main_game_plat_code')
            ->toArray();

        $allGamePlats        =  MainGamePlat::all();
        $playerGameAccounts  =  PlayerGameAccount::where('player_id',$this->user->player_id)->get();

        if ( !empty($playerAccount) ) {
            $data = [
                'balance'      => $playerAccount->balance > 0 ? bcdiv($playerAccount->balance, 10000, 2) : '0.00',
                'frozen'       => $playerAccount->frozen > 0 ? bcdiv($playerAccount->frozen, 10000, 2) : '0.00',
                'agentbalance' => $playerAccount->agentbalance > 0 ? bcdiv($playerAccount->agentbalance, 10000, 2) : '0.00',
                'agentfrozen'  => $playerAccount->agentfrozen > 0 ? bcdiv($playerAccount->agentfrozen, 10000, 2) : '0.00',
            ];
        } else {
            $data = [
                'balance' => '0.00',
                'frozen'  => '0.00',
                'agentbalance' => '0.00',
                'agentfrozen'  => '0.00',
            ];
        }

        $transferKey        ='gametranfer_'.$this->user->player_id;
        if($this->user->is_notransfer && cache()->has($transferKey)){
            $is_maintain = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',GameCache::getGamePlatId(cache()->get($transferKey)))->first();
            if($is_maintain && $is_maintain->status==1){
                 //转帐操作
                $playerGameAccount  = PlayerGameAccount::where('player_id',$this->user->player_id)->where('main_game_plat_code',cache()->get($transferKey))->first();
                if($playerGameAccount && $playerGameAccount->is_need_repair==0){
                    request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
                    request()->offsetSet('password',$playerGameAccount->password);
                    request()->offsetSet('mainGamePlatCode',cache()->get($transferKey));

                    $game    = new Game($this->carrier,cache()->get($transferKey));        
                    $balance = $game->getBalance();
                    if(is_array($balance) && $balance['success']){
                       if($balance['data']['balance'] >= 1 && $playerGameAccount->is_locked==0 && $playerGameAccount->is_need_repair==0){
                         request()->offsetSet('price',intval($balance['data']['balance']));
                         $output = $game->transferTo($this->user);
                         if(is_array($output) && $output['success']){
                            cache()->forget($transferKey);
                         } else{
                            if(cache()->get($transferKey)=='jp6'){
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'6', 0);
                            } elseif(cache()->get($transferKey)=='pp6'){
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'6', 0);
                            } elseif(cache()->get($transferKey)=='ky1'){
                                return $this->returnApiJson(config('language')[$this->language]['error276'].'1', 0);
                            } elseif(cache()->get($transferKey) =='cq95'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error465'].'5', 0);
                            } elseif(cache()->get($transferKey) =='jdb5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error466'].'5', 0);
                            } elseif(cache()->get($transferKey) =='fc5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error467'].'5', 0);
                            } elseif(cache()->get($transferKey) =='pp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'5', 0);
                            } elseif(cache()->get($transferKey) =='jp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'5', 0);
                            } elseif(cache()->get($transferKey) =='habanero5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error469'].'5', 0);
                            } elseif(cache()->get($transferKey) =='jili5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error473'].'5', 0);
                            } elseif(cache()->get($transferKey) =='cq97'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error465'].'7', 0);
                            } elseif(cache()->get($transferKey) =='cq98'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error465'].'8', 0);
                            } elseif(cache()->get($transferKey) =='cq99'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error465'].'9', 0);
                            } elseif(cache()->get($transferKey) =='pp7'){
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'7', 0);
                            } elseif(cache()->get($transferKey) =='pp8'){
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'8', 0);
                            } elseif(cache()->get($transferKey) =='pp9'){
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'9', 0);
                            } elseif(cache()->get($transferKey) =='jp7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'7', 0);
                            } elseif(cache()->get($transferKey) =='jp8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'8', 0);
                            } elseif(cache()->get($transferKey) =='jp9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'9', 0);
                            } elseif(cache()->get($transferKey) =='habanero7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error469'].'7', 0);
                            } elseif(cache()->get($transferKey) =='habanero8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error469'].'8', 0);
                            } elseif(cache()->get($transferKey) =='habanero9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error469'].'9', 0);
                            } elseif(cache()->get($transferKey) =='fc7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error467'].'7', 0);
                            } elseif(cache()->get($transferKey) =='fc8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error467'].'8', 0);
                            } elseif(cache()->get($transferKey) =='fc9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error467'].'9', 0);
                            } elseif(cache()->get($transferKey) =='jdb7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error466'].'7', 0);
                            } elseif(cache()->get($transferKey) =='jdb8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error466'].'8', 0);
                            } elseif(cache()->get($transferKey) =='jdb9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error466'].'9', 0);
                            } elseif(cache()->get($transferKey) =='jili7'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error473'].'7', 0);
                            } elseif(cache()->get($transferKey) =='jili8'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error473'].'8', 0);
                            } elseif(cache()->get($transferKey) =='jili9'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error473'].'9', 0);
                            }else{
                                return $this->returnApiJson(config('language')[$this->language]['error277'].cache()->get($transferKey).config('language')[$this->language]['error278'], 0);
                            }
                         }
                       } else{
                          cache()->forget($transferKey);
                       }
                    } else{
                        if(cache()->get($transferKey) =='pp6'){
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'6', 0);
                        } elseif(cache()->get($transferKey) =='ky1'){
                            return $this->returnApiJson(config('language')[$this->language]['error281'].'1', 0);
                        } elseif(cache()->get($transferKey) =='cq95'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error475'].'5', 0);
                        } elseif(cache()->get($transferKey) =='jdb5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error476'].'5', 0);
                        } elseif(cache()->get($transferKey) =='fc5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error477'].'5', 0);
                        } elseif(cache()->get($transferKey) =='pp5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'5', 0);
                        } elseif(cache()->get($transferKey) =='jp5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error279'].'5', 0);
                        } elseif(cache()->get($transferKey) =='habanero5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error479'].'5', 0);
                        } elseif(cache()->get($transferKey) =='jili5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error483'].'5', 0);
                        } elseif(cache()->get($transferKey) =='cq97'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error475'].'7', 0);
                        } elseif(cache()->get($transferKey) =='cq98'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error475'].'8', 0);
                        } elseif(cache()->get($transferKey) =='cq99'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error475'].'9', 0);
                        } elseif(cache()->get($transferKey) =='pp7'){
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'7', 0);
                        } elseif(cache()->get($transferKey) =='pp8'){
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'8', 0);
                        } elseif(cache()->get($transferKey) =='pp9'){
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'9', 0);
                        } elseif(cache()->get($transferKey) =='jp7'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error279'].'7', 0);
                        } elseif(cache()->get($transferKey) =='jp8'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error279'].'8', 0);
                        } elseif(cache()->get($transferKey) =='jp9'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error279'].'9', 0);
                        } elseif(cache()->get($transferKey) =='habanero7'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error479'].'7', 0);
                        } elseif(cache()->get($transferKey) =='habanero8'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error479'].'8', 0);
                        } elseif(cache()->get($transferKey) =='habanero9'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error479'].'9', 0);
                        } elseif(cache()->get($transferKey) =='fc7'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error477'].'7', 0);
                        } elseif(cache()->get($transferKey) =='fc8'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error477'].'8', 0);
                        } elseif(cache()->get($transferKey) =='fc9'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error477'].'9', 0);
                        } elseif(cache()->get($transferKey) =='jdb7'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error476'].'7', 0);
                        } elseif(cache()->get($transferKey) =='jdb8'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error476'].'8', 0);
                        } elseif(cache()->get($transferKey) =='jdb9'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error476'].'9', 0);
                        } elseif(cache()->get($transferKey) =='jili7'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error483'].'7', 0);
                        } elseif(cache()->get($transferKey) =='jili8'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error483'].'8', 0);
                        } elseif(cache()->get($transferKey) =='jili9'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error483'].'9', 0);
                        } else{
                            return $this->returnApiJson(config('language')[$this->language]['error278'].cache()->get($transferKey).config('language')[$this->language]['error282'], 0);
                        }

                    }
                }
            //转出操作
            }
        }

        $plats = [];
        $temp  = [];
        $platforms = [];
        foreach ($playerGameAccounts as $key => $value) {
            $temp[$value->main_game_plat_code] = $value->balance;
            $platforms[] = 'app.game.platform.' . $value->main_game_plat_code;
        }

        foreach ($mainGamePlatCodes as $key => $value) {
            foreach ($allGamePlats as $k => $v) {
                if($v->main_game_plat_code == $value){
                    if(!isset($temp[$value])) {
                        $v->balance = '0.00';
                    } else {
                        $v->balance = $temp[$value];
                    }

                    # 多语言处理
                    $plat = $v->toArray();
                    $plats[]=$plat;
                }
            }
        }

        $data['plats']=$plats;
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function digitaltype()
    {
        $thirdWalletIds    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'third_wallet',$this->prefix);
        $thirdWalletIds    = json_decode($thirdWalletIds,true);
        $thirdWallets      = ThirdWallet::whereIn('id',$thirdWalletIds)->get();
        $data              = [];

        foreach ($thirdWallets as $key => $value) {
            $row          = [];
            $row['label'] = $value->name;
            $row['value'] = $value->id;
            $data[]       = $row;
        }

        return returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function getSetOptions()
    {
        $playerSettings = PlayerCache::getPlayerSetting($this->user->player_id);
        $data           = [];
        $data[]         = 'is_createagent';
        if($playerSettings->earnings>0){
            $data[]      = 'earnings';
        }

        return  $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function getInviteOptions()
    {
        $playerSettings = PlayerInviteCode::where('player_id',$this->user->player_id)->first();
        $data           = [];
        if($playerSettings->earnings>0){
            $data[]      = 'earnings';
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function getoptions()
    {
        $playerSettings = PlayerCache::getPlayerSetting($this->user->player_id);
        $data           = [];
        if($playerSettings->earnings){
            $row             = [];
            $row['label']    = config('language')[$this->language]['error331'];
            $row['value']    = $playerSettings->earnings;
            $row['key']      = 'earnings';
            $data[]          = $row;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function authStatus()
    {
        $data = [];

        if(empty($this->user->real_name)) {
            $data['real_name'] = 0;
        } else {
            $data['real_name'] = 1;
        }

        if(empty($this->user->email)) {
            $data['email']     = 0;
        } else {
            $data['email']     = 1;
        }

        $data['mobile']        = 1;
        $playerBankCard        = PlayerBankCard::where('player_id',$this->user->player_id)->first();

        if($playerBankCard) {
            $data['playerCard']        = 1;
        } else {
            $data['playerCard']        = 0;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function getHaveMessage()
    {
        $total = PlayerMessage::where('is_read',0)->where('player_id',$this->user->player_id)->count();

        if($total) {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['is_reader'=>1,'count'=>$total]);
        } else {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['is_reader'=>0]);
        }
    }

    public function info()
    {
        $input                   = request()->all();
        $currVipLevel            = CarrierPlayerGrade::select('level_name','withdrawcount','sort','weekly_salary','monthly_salary')->where('id',$this->user->player_level_id)->first();
        $nextVipLevel            = CarrierPlayerGrade::where('prefix',$this->user->prefix)->where('sort','>',$currVipLevel->sort)->orderBy('sort','asc')->first();

        $nextAvailablebet        = 0;

        if($nextVipLevel){
            $upgradeRule       = unserialize($nextVipLevel->upgrade_rule);
            $nextAvailablebet  = $upgradeRule['availablebet'];
        }

        if($nextVipLevel){
            $allVipLevels            = CarrierPlayerGrade::where('prefix',$this->user->prefix)->where('sort','<=',$nextVipLevel->sort)->get();
        } else{
            $allVipLevels            = CarrierPlayerGrade::where('prefix',$this->user->prefix)->where('sort',$currVipLevel->sort)->get();
        }
        
        $allVipLevelAvailablebet = 0;

        foreach ($allVipLevels as $key => $value) {
            $upgradeRule               = unserialize($value->upgrade_rule);
            $allVipLevelAvailablebet  += $upgradeRule['availablebet'];
        }
        
        $rechargeAmountAdd       = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','recharge')->sum('amount');
        $availableBetAmount      = PlayerBetFlowMiddle::where('player_id',$this->user->player_id)->sum('process_available_bet_amount');
        $selfPlayerSetting       = PlayerCache::getPlayerSetting($this->user->player_id);

        $loginAt                 = PlayerLogin::where('player_id',$this->user->player_id)->orderBy('login_time','desc')->skip(1)->take(1)->first();
        $data                    = [];

        $playerInviteCode        = PlayerInviteCode::where('player_id',$this->user->player_id)->orderBy('id','asc')->first();
        $area                    = Area::where('id',$this->user->area)->first();

        if($area){
            $province                = Area::where('id',$this->user->province)->first();
            $data['provinceid']      = $this->user->province;
            $data['province']        = $province->name;
            $data['area']            = $area->id;
            $data['city']            = $area->name;

        } else {
            $data['provinceid']      = 0;
            $data['province']        = '';
            $data['area']            = 0;
            $data['city']            = '';
        }

        $enabeleSettingDividends  = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'enabele_setting_dividends',$this->user->prefix);
        $enabeleSettingGuaranteed = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'enabele_setting_guaranteed',$this->user->prefix);

        $data['sex']             = $this->user->sex;
        $data['extend_id']       = $this->user->extend_id;
        $data['parent_extend_id']= $this->user->parent_extend_id;
        $data['depositamount']   = bcdiv($rechargeAmountAdd,10000,2);
        $data['availablebet']    = bcdiv($availableBetAmount,1,2);
        $data['nick_name']       = $this->user->nick_name;
        $data['promotecode']     = !empty($playerInviteCode) ? $playerInviteCode->code : '';
        $data['user_name']       = rtrim($this->user->user_name,'_'.$this->user->prefix);
        $data['real_name']       = $this->user->real_name;
        $data['parent_id']       = $this->user->parent_id;
        $data['curr_level']      = $currVipLevel->level_name;
        $data['withdrawcount']   = $currVipLevel->withdrawcount;
        $data['next_level']      = $nextVipLevel ? $nextVipLevel->level_name : $currVipLevel->level_name;
        $data['next_availablebet']      = $nextAvailablebet;
        $data['complete_availablebet']  = $availableBetAmount + $nextAvailablebet -$allVipLevelAvailablebet;
        $data['withdrawcount']   = $currVipLevel->withdrawcount;
        $data['updategift']      = bcdiv($currVipLevel->updategift,1,2);
        $data['birthgift']       = bcdiv($currVipLevel->birthgift,1,2);
        $data['mobile']          = empty($this->user->mobile) ? '' : $this->user->mobile;
        $data['email']           = $this->user->email;
        $data['type']            = $this->user->type;
        $data['score']           = '' ;
        $data['qq_account']      = is_null($this->user->qq_account)?'':$this->user->qq_account;
        $data['wechat']          = $this->user->wechat;
        $data['birthday']        = is_null($this->user->birthday)?'':$this->user->birthday;
        $data['login_at']        =  $loginAt?date('Y-m-d H:i:s',$loginAt->login_time):'';
        $data['player_id']       = $this->user->player_id;
        $data['nick_name']       = $this->user->nick_name;
        $data['bankcardname']    = $this->user->bankcardname;
        $data['is_sex']          = $this->carrier->is_sex;
        $data['day']             = $this->user->day;
        $data['avatar']          = $this->user->avatar;
        $data['login_at']        = $this->user->login_at;
        $data['is_notransfer']   = $this->user->is_notransfer;
        $data['win_lose_agent']  = $this->user->win_lose_agent;
        $data['guaranteed']      = $selfPlayerSetting->guaranteed;
        $data['enabele_setting_dividends']   = $enabeleSettingDividends;
        $data['enabele_setting_guaranteed']  = $enabeleSettingGuaranteed;
        $data['created_at']      = date('Y-m-d H:i:s',strtotime($this->user->created_at));
        $data['monthly_salary']  = $currVipLevel->monthly_salary;
        $data['weekly_salary']   = $currVipLevel->weekly_salary;

        //注册天数
        $data['diffday']         =  round((time()-strtotime($this->user->created_at)) / (60 * 60 * 24));

        $weekTime                = getWeekStartEnd();
        $monthTime               = getMonthStartEnd();

        //注册彩金特殊处理
        $newPlayerTransfer = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','recharge')->orderBy('id','desc')->first(); 
        $isRegistergift    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'is_registergift',$this->user->prefix);
        if($isRegistergift){
            $data['enable_lott']     = 1;
        }

        //推广链接
        $playerInviteCode              = PlayerInviteCode::where('player_id',$this->user->player_id)->first();

        $h5url                         = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'h5url',$this->user->prefix);

        $h5urlArr                      = explode(',',$h5url);

        if(!empty($playerInviteCode->domain)){
            $links                         = $playerInviteCode->domain.',';
        } else{
            $links                         = '';
        }

        foreach ($h5urlArr as $key => $value) {
            $links.='https://'.$playerInviteCode->code.'.'.$value.',';
            
        }
        $links = rtrim($links,',');
        //推广链接
        $data['links'] = $links;

        return  $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function level()
    {
        $carrierPlayerLevels = CarrierPlayerGrade::where('carrier_id',$this->user->carrier_id)->where('prefix',$this->user->prefix)->orderBy('sort','asc')->get();

        return  returnApiJson(config('language')[$this->language]['success1'], 1, $carrierPlayerLevels);
    }

    public function playerChangePassword()
    {
        $data = $this->user->playerChangePassword($this->carrier);

        if($data === true) {
            $playerOperate                                    = new PlayerOperate();
            $playerOperate->carrier_id                        = $this->user->carrier_id;
            $playerOperate->player_id                         = $this->user->player_id;
            $playerOperate->user_name                         = $this->user->user_name;
            $playerOperate->type                              = 3;
            $playerOperate->desc                              = '';
            $playerOperate->ip                                = ip2long(real_ip());
            $playerOperate->save();

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } else {
            return $this->returnApiJson($data, 0);
        }
    }

    public function playerChangePayPassword()
    {
        $data = $this->user->playerChangePayPassword($this->carrier);

        if($data === true) {
            $playerOperate                                    = new PlayerOperate();
            $playerOperate->carrier_id                        = $this->user->carrier_id;
            $playerOperate->player_id                         = $this->user->player_id;
            $playerOperate->user_name                         = $this->user->user_name;
            $playerOperate->type                              = 4;
            $playerOperate->desc                              = '';
            $playerOperate->ip                                = ip2long(real_ip());
            $playerOperate->save();
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } else {
            return $this->returnApiJson($data, 0);
        }
    }

    public function playerCheckPayPassword()
    {
        if(!is_null($this->user->paypassword)) {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['status'=>1]);
        } else {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['status'=>0]);
        }
    }

    public function bankcardList()
    {
        $data = $this->user->bankcardList();
       
        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function alipayList()
    {
        $data = $this->user->alipayList();
       
        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function withdrawMethodList()
    {
        $withdrawMethodList   = [];
        $playerDigitalAddress = PlayerDigitalAddress::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->get()->toArray();

        foreach($playerDigitalAddress  as $key => &$value){
            switch ($value['type']) {
                case '1':
                    $value['type_name'] ='Trc20';
                    $value['img_url']   ='0/third_wallet_icon/Trc20.png';
                    break;
                case '2':
                    $value['type_name'] ='Erc20';
                    $value['img_url']   ='0/third_wallet_icon/Erc20.png';
                    break;
                case '3':
                    $value['type_name'] ='Okpay';
                    $value['img_url']   ='0/third_wallet_icon/Okpay.png';
                    break;
                case '4':
                    $value['type_name'] ='Gopay';
                    $value['img_url']   ='0/third_wallet_icon/Gopay.png';
                    break;
                case '5':
                    $value['type_name'] ='Gcash';
                    $value['img_url']   ='0/third_wallet_icon/Gcash.png';
                    break;
                case '6':
                    $value['type_name'] ='Topay';
                    $value['img_url']   ='0/third_wallet_icon/Topay.png';
                    break;
                case '7':
                    $value['type_name'] ='Ebpay';
                    $value['img_url']   ='0/third_wallet_icon/Ebpay.png';
                    break;
                case '8':
                    $value['type_name'] ='Wanb';
                    $value['img_url']   ='0/third_wallet_icon/Wanbpay.png';
                    break;
                case '9':
                    $value['type_name'] ='Jdpay';
                    $value['img_url']   ='0/third_wallet_icon/Jdpay.png';
                    break;
                case '10':
                    $value['type_name'] ='Kdpay';
                    $value['img_url']   ='0/third_wallet_icon/Kdpay.png';
                    break;
                case '11':
                    $value['type_name'] ='Nopay';
                    $value['img_url']   ='0/third_wallet_icon/Nopay.png';
                    break;
                case '12':
                    $value['type_name'] ='Bobipay';
                    $value['img_url']   ='0/third_wallet_icon/Bobipay.png';
                    break;
                
                default:
                    $value['type_name'] = '';
                    break;
            }
        }

        $cardsArrays = PlayerBankCard::select('inf_player_bank_cards.*','inf_carrier_bank_type.bank_name','inf_carrier_bank_type.bank_name','inf_carrier_bank_type.bank_background_url')
            ->leftJoin('inf_carrier_bank_type','inf_carrier_bank_type.id','=','inf_player_bank_cards.bank_Id')
            ->where('inf_player_bank_cards.player_id',$this->user->player_id)
            ->where('inf_carrier_bank_type.carrier_id',$this->carrier->id)
            ->where('inf_player_bank_cards.status',1)
            ->orderBy('inf_player_bank_cards.id','desc')
            ->get()->toArray();

        $data = [];
        foreach ($playerDigitalAddress as $key => $value) {
            $row           = [];
            $row['name']   = $value['type_name'];
            $row['img']    = $value['img_url'];
            $row['value']  = $value;
            $data[]        = $row;
        }

        foreach ($cardsArrays as $key => $value) {
            $row           = [];
            $row['name']   = $value['bank_name'];
            $row['img']    = $value['bank_background_url'];
            $row['value']  = $value;
            $data[]        = $row;
        }

        return $this->returnApiJson('操作成功', 1,$data);
    }

    public function bankcardAdd($playerBankCard_Id=0)
    {
        $isShowFrontExchange = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'is_show_front_exchange',$this->user->prefix);
        $existCodeGift       = PlayerTransfer::where('type','code_gift')->where('player_id',$this->user->player_id)->first();

        //有领取体验券银行卡无法编辑
        if($isShowFrontExchange && $playerBankCard_Id!=0 && $existCodeGift){
            return $this->returnApiJson(config('language')[$this->language]['error332'],0);
        }

        //有开启兑换仅能绑一张银行卡
        if($isShowFrontExchange && $playerBankCard_Id==0){
            $existPlayerBankCard = PlayerBankCard::where('player_id',$this->user->player_id)->first();
            if($existPlayerBankCard){
                return $this->returnApiJson(config('language')[$this->language]['error333'],0);
            }
        }

        $input              = request()->all();
        if($playerBankCard_Id) {
            $playerBankCard = PlayerBankCard::where('id',$playerBankCard_Id)->where('player_id',$this->user->player_id)->first();
            if(!$playerBankCard) {
                return $this->returnApiJson(config('language')[$this->language]['error52'], 0);
            }
        } else {
            if(!isset($input['bank_Id']) || trim($input['bank_Id']) == '') {
                return config('language')[$this->language]['error21'];
            } 

            $bnks  = CarrierBankCardType::where('id',$input['bank_Id'])->where('carrier_id',$this->carrier->id)->first();

            if(!$bnks) {
                return config('language')[$this->language]['error21'];
            }

            if(!isset($input['card_owner_name'])) {
                return config('language')[$this->language]['error21'];
            }

            if(!isset($input['card_account']) || empty(trim($input['card_account']))) {
                return config('language')[$this->language]['error21'];
            }

            $playerBankCard = PlayerBankCard::where('player_id',$this->user->player_id)->where('card_account',$input['card_account'])->where('card_owner_name',$input['card_owner_name'])->where('bank_Id',$input['bank_Id'])->first();

            if(!$playerBankCard){
                $playerBankCard = new PlayerBankCard();
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error334'],0);
            }
        }

        $cacheKey = 'bankcardadd_'.$this->user->player_id;
        if(cache()->get($cacheKey,0)==0){
            cache()->put($cacheKey, 1, now()->addSeconds(3));
        } else {
            return $this->returnApiJson(config('language')[$this->language]['error335'],0);
        }

        $data = $playerBankCard->bankcardAdd($this->user,$this->carrier,$this->prefix);

        if($data===true) {
            $playerOperate                                    = new PlayerOperate();
            $playerOperate->carrier_id                        = $this->carrier->id;
            $playerOperate->player_id                         = $this->user->player_id;
            $playerOperate->user_name                         = $this->user->user_name;
            $playerOperate->type                              = 2;
            $playerOperate->desc                              = '绑定银行卡：姓名'.$input['card_owner_name'].',卡号'.$input['card_account'];
            $playerOperate->ip                                = ip2long(real_ip());
            $playerOperate->save();

            return $this->returnApiJson(config('language')[$this->language]['success1'],1);
         } else{
            return $this->returnApiJson($data,0);
         }
    }

    public function bankcardDel($id)
    {
         $isShowFrontExchange = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'is_show_front_exchange',$this->user->prefix);
         if($isShowFrontExchange){
            return $this->returnApiJson(config('language')[$this->language]['error336'],0);
         }

         $data = $this->user->bankcardDel($id,$this->carrier);

         if($data === true) {
             return $this->returnApiJson(config('language')[$this->language]['success1'],1);
         } else{
            return $this->returnApiJson($data,0);
         }
    }

    public function alipayDel($id)
    {
         $isShowFrontExchange = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'is_show_front_exchange',$this->user->prefix);
         if($isShowFrontExchange){
            return $this->returnApiJson(config('language')[$this->language]['error524'],0);
         }

         $data = $this->user->alipayDel($id,$this->carrier);

         if($data === true) {
             return $this->returnApiJson(config('language')[$this->language]['success1'],1);
         } else{
            return $this->returnApiJson($data,0);
         }
    }

    public function alipayAdd($playerAlipayId=0)
    {
        $isShowFrontExchange = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'is_show_front_exchange',$this->user->prefix);
        $existCodeGift       = PlayerTransfer::where('type','code_gift')->where('player_id',$this->user->player_id)->first();

        //有领取体验券支付宝无法编辑
        if($isShowFrontExchange && $playerAlipayId!=0 && $existCodeGift){
            return $this->returnApiJson(config('language')[$this->language]['error526'],0);
        }

        //有开启兑换仅能绑一个支付宝
        if($isShowFrontExchange && $playerAlipayId==0){
            $existPlayerAlipay = PlayerAlipay::where('player_id',$this->user->player_id)->first();
            if($existPlayerAlipay){
                return $this->returnApiJson(config('language')[$this->language]['error525'],0);
            }
        }

        $input              = request()->all();
        if($playerAlipayId) {
            $playerAlipay = PlayerAlipay::where('id',$playerAlipayId)->where('player_id',$this->user->player_id)->first();
            if(!$playerAlipay) {
                return $this->returnApiJson(config('language')[$this->language]['error527'], 0);
            }
        } else {
            if(!isset($input['real_name'])) {
                return config('language')[$this->language]['error21'];
            }

            if(!isset($input['account']) || empty(trim($input['account']))) {
                return config('language')[$this->language]['error21'];
            }

            $playerAlipay = PlayerAlipay::where('player_id',$this->user->player_id)->where('account',$input['account'])->where('real_name',$input['real_name'])->first();

            if(!$playerAlipay){
                $playerAlipay = new PlayerAlipay();
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error528'],0);
            }
        }

        $cacheKey = 'playeralipayadd_'.$this->user->player_id;
        if(cache()->get($cacheKey,0)==0){
            cache()->put($cacheKey, 1, now()->addSeconds(3));
        } else {
            return $this->returnApiJson(config('language')[$this->language]['error335'],0);
        }

        $data = $playerAlipay->alipayAdd($this->user,$this->carrier,$this->prefix);

        if($data===true) {
            $playerOperate                                    = new PlayerOperate();
            $playerOperate->carrier_id                        = $this->carrier->id;
            $playerOperate->player_id                         = $this->user->player_id;
            $playerOperate->user_name                         = $this->user->user_name;
            $playerOperate->type                              = 2;
            $playerOperate->desc                              = '绑定支付宝：姓名'.$input['real_name'].',帐号'.$input['account'];
            $playerOperate->ip                                = ip2long(real_ip());
            $playerOperate->save();

            return $this->returnApiJson(config('language')[$this->language]['success1'],1);
         } else{
            return $this->returnApiJson($data,0);
         }
    }

    public function messageList()
    {
        $data = $this->user->messageList();
         
        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function unReadMessageNumber()
    {
        $data['number'] = PlayerCache::getUnreadMessageNumber($this->carrier->id,$this->user->player_id);
         
        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function messageDelete()
    {
        $data = $this->user->messageDelete();

        if($data === true) {
            return $this->returnApiJson(config('language')[$this->language]['success1'],1);
        } else {
            return $this->returnApiJson($data,0);
        }
    }

    public function messageChangeStatus($id)
    {
        $data = $this->user->messageChangeStatus($id,$this->carrier);

        if($data === true) {
            return $this->returnApiJson(config('language')[$this->language]['success1'],1);
        } else {
            return $this->returnApiJson($data,0);
        }
    }

    public function platList()
    {
        $data = $this->user->platList();

        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function refreshplat($platcode='')
    {
       $data = $this->user->refreshplat($platcode);

        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function betflowListStat()
    {
        $data = $this->user->betflowListStat();

        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function betflowList()
    {
        $data = $this->user->betflowList();

        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function depositPayList()
    {
        $data = $this->user->depositPayList();

        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function withdrawApply()
    {
        $res = $this->user->withdrawApply($this->carrier);

        if (is_array($res)) {
            return $this->returnApiJson(config('language')[$this->language]['success1'],1,$res);
        }else{
            return $this->returnApiJson($res,0);
        }
    }

    public function alipayWithdrawApply()
    {
        $res = $this->user->alipayWithdrawApply($this->carrier);

        if (is_array($res)) {
            return $this->returnApiJson(config('language')[$this->language]['success1'],1,$res);
        }else{
            return $this->returnApiJson($res,0);
        }
    }

    public function digitalWithdrawApply()
    {
        $res = $this->user->digitalWithdrawApply($this->carrier);

        if (is_array($res)) {
            return $this->returnApiJson(config('language')[$this->language]['success1'],1,$res);
        }else{
            return $this->returnApiJson($res,0);
        }
    }

    public function transferList()
    {
        $data = $this->user->playerTransferList();
        if(is_array($data)){
            return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
        } else {
            return $this->returnApiJson($data,0);
        }
        
    }

    public function withdrawList()
    {
        $data = $this->user->withdrawList();

        return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
    }

    public function rechargeGroupChannellist()
    {
         $input          = request()->all();
         $enableRecharge = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'enable_recharge',$this->prefix);
         $currency       = CarrierCache::getCurrencyByPrefix($this->user->prefix);
         if(!$enableRecharge){
            return $this->returnApiJson('操作成功',1,[]);
         }

        //在线支付
        $query = CarrierPlayerLevelBankCardMap::select('map_carrier_player_level_pay_channel.carrier_channle_id','conf_carrier_third_part_pay.startTime','conf_carrier_third_part_pay.endTime','inf_carrier_pay_channel.id as carrierpaychannelid','inf_carrier_pay_channel.sort')
            ->leftJoin('inf_carrier_pay_channel','inf_carrier_pay_channel.id','=','map_carrier_player_level_pay_channel.carrier_channle_id')
            ->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')
            ->where('map_carrier_player_level_pay_channel.player_level_id',$this->user->player_group_id)
            ->where('inf_carrier_pay_channel.prefix',$this->user->prefix)
            ->where('def_pay_channel_list.type',1)
            ->where('inf_carrier_pay_channel.status',1);
            

        $unpayFrequencyHidden   = CarrierCache::getCarrierConfigure($this->carrier->id, 'unpay_frequency_hidden');
        $lastSuccessOrder       = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->first();
        $carrierPayChannelIds   = CarrierPayChannel::leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')
            ->where('inf_carrier_pay_channel.carrier_id',$this->carrier->id)
            ->where('inf_carrier_pay_channel.prefix',$this->user->prefix)
            ->where('conf_carrier_third_part_pay.is_anti_complaint',0)
            ->pluck('inf_carrier_pay_channel.id')
            ->toArray();

        if($lastSuccessOrder){
            $unpayAntiComplaint = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('id','>',$lastSuccessOrder->id)->whereIn('carrier_pay_channel',$carrierPayChannelIds)->get();

        } else {
            $unpayAntiComplaint = PlayerDepositPayLog::where('player_id',$this->user->player_id)->whereIn('carrier_pay_channel',$carrierPayChannelIds)->get();
        }

        if(count($unpayAntiComplaint) >= $unpayFrequencyHidden){
            //隐藏通道
            $query->where('conf_carrier_third_part_pay.is_anti_complaint',1);
        }

        if(!isset($input['is_mobile']) || !in_array($input['is_mobile'], [0,1])){
            return $this->returnApiJson(config('language')[$this->language]['error21'],0);
        }

        if($input['is_mobile']==0) {
            $query->whereIn('inf_carrier_pay_channel.show',[1,3]);
        } else {
            if(isset($input['device']) && in_array($input['device'],[4,5])){
                $query->where(function($query1) use($input){
                    $query1->whereIn('inf_carrier_pay_channel.show',[2,3])->orWhere('inf_carrier_pay_channel.show',$input['device']);
                });
            } else{
                $query->whereIn('inf_carrier_pay_channel.show',[2,3]);
            }
        }



        $middleVariable                 = $query->orderBy('inf_carrier_pay_channel.sort','desc')->get();
        $carrierPlayerLevelBankCardMaps = [];

        $today = date('Y-m-d');
        $time  = time();
        $allCarrierPaychannelId = [];
        foreach ($middleVariable as $key => $value) {
             $startTime = strtotime($today.' '.$value->startTime);
             $endTime   = strtotime($today.' '.$value->endTime);
             if($time >= $startTime && $time <= $endTime) {
                $carrierPlayerLevelBankCardMaps[$value->carrierpaychannelid] = $value;
                $allCarrierPaychannelId[]                                    = $value->carrierpaychannelid;
             }
        }

        $payChannelGroups     = PayChannelGroup::where('carrier_id',$this->user->carrier_id)->where('status',1)->orderBy('sort','desc')->get();
        $carrierPayChannelIds = [];
        $payChannelGroupIds   = [];

        foreach ($payChannelGroups as $key => $value) {
            if(!empty($value->carrier_pay_channel_ids)){
                $ids = explode(',',$value->carrier_pay_channel_ids);
                $intersect                =  array_intersect($ids,$allCarrierPaychannelId);
                if(count($intersect)){
                    $carrierPayChannelIds = array_merge($carrierPayChannelIds, $intersect);
                    $payChannelGroupIds[] = $value->id;
                }
            }
        }

        $playerDepositPayLog        = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('status',1)->first();
        $financeMinRecharge         = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'finance_min_recharge',$this->user->prefix);
        $financeMaxRecharge         = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'finance_max_recharge',$this->user->prefix);
        $digitalFinanceMinRecharge  = CarrierCache::getCarrierConfigure($this->carrier->id, 'digital_finance_min_recharge');
        $digitalFinanceMaxRecharge  = CarrierCache::getCarrierConfigure($this->carrier->id, 'digital_finance_max_recharge');
        
        $payChannelGroups        = PayChannelGroup::select('name','img','carrier_pay_channel_ids','currency')->whereIn('id',$payChannelGroupIds)->orderBy('sort','desc')->get()->toArray();

        foreach ($payChannelGroups as $key => &$value) {
            $ids           = explode(',',$value['carrier_pay_channel_ids']);
            $intersects    = array_intersect($ids,$carrierPayChannelIds);

            if($value['currency']=='USD'){
               $value['type'] = 4;
            } else {
               $value['type'] = 3; 
            }
            
            unset($value['carrier_pay_channel_ids']);

            $carrierPayChannelArr = [];
            foreach ($intersects as $k => $v) {
                $payChannelInfo = CarrierPayChannel::select('def_pay_channel_list.is_show_enter','conf_carrier_third_part_pay.remark','def_pay_channel_list.min','def_pay_channel_list.max','def_pay_channel_list.enum','def_pay_channel_list.has_realname','inf_carrier_pay_channel.id','inf_carrier_pay_channel.gift_ratio','inf_carrier_pay_channel.show_name','conf_carrier_third_part_pay.id as tid','conf_carrier_third_part_pay.startTime','conf_carrier_third_part_pay.endTime','def_pay_channel_list.channel_code','inf_carrier_pay_channel.img','inf_carrier_pay_channel.video_url','inf_carrier_pay_channel.is_recommend','inf_carrier_pay_channel.sort')->leftJoin('conf_carrier_third_part_pay','conf_carrier_third_part_pay.id','=','inf_carrier_pay_channel.binded_third_part_pay_id')->leftJoin('def_pay_channel_list','def_pay_channel_list.id','=','conf_carrier_third_part_pay.def_pay_channel_id')->where('inf_carrier_pay_channel.id',$v)->where('inf_carrier_pay_channel.status',1)->first();

                $startTime  = strtotime(date('Y-m-d').' '.$payChannelInfo->startTime);
                $endTime    = strtotime(date('Y-m-d').' '.$payChannelInfo->endTime);

                if(time()<$startTime || time()>$endTime){
                    continue;
                }

                $item                  = [];
                $item['payChanneEnum'] = [];
                $payChanneEnum         = [];
                //
                if(!empty(trim($payChannelInfo->enum))){
                    $enums         = explode(',',$payChannelInfo->enum);
                    foreach ($enums as $k1 => $v1) {
                        if($value['currency']=='USD'){
                            if($v1 >= $digitalFinanceMinRecharge && $v1 <= $digitalFinanceMaxRecharge){
                                array_push($payChanneEnum, $v1);
                            } 
                        } else {
                            if($v1 >= $financeMinRecharge && $v1 <= $financeMaxRecharge){
                                array_push($payChanneEnum, $v1);
                            } 
                        }
                    }
                    if(count($payChanneEnum)){
                        $item['payChanneEnum'] = $payChanneEnum;
                        $item['min']           = $payChanneEnum[0];
                        $item['max']           = end($payChanneEnum);
                        $item['showEdit']      = $payChannelInfo->is_show_enter;
                    } else {
                        continue;
                    }
                    
                } else {

                    if($value['currency']=='USD'){
                        if($payChannelInfo->min < $digitalFinanceMinRecharge){
                            $item['min']       = $digitalFinanceMinRecharge;
                        } else {
                            $item['min']           = $payChannelInfo->min;
                        }

                        if($payChannelInfo->max > $digitalFinanceMaxRecharge){
                            $item['max']       = $digitalFinanceMaxRecharge;
                        } else {
                            $item['max']       = $payChannelInfo->max;
                        }
                    } else {
                        if($payChannelInfo->min < $financeMinRecharge){
                            $item['min']       = $financeMinRecharge;
                        } else {
                            $item['min']           = $payChannelInfo->min;
                        }

                        if($payChannelInfo->max > $financeMaxRecharge){
                            $item['max']       = $financeMaxRecharge;
                        } else {
                            $item['max']       = $payChannelInfo->max;
                        }
                    }
                    
                    $item['showEdit']      = $payChannelInfo->is_show_enter;
                }

               if(!empty($payChannelInfo->channel_code) && !is_null($payChannelInfo->channel_code)){
                    $bankCodes      = explode(',', $payChannelInfo->channel_code);
                    if(count($bankCodes)>1){
                        $item['bankCode'] = $bankCodes;
                    }
                }
                $item['img']            = $payChannelInfo->img;
                $item['video_url']      = $payChannelInfo->video_url;
                $item['id']             = $payChannelInfo->id;
                $item['remark']         = $payChannelInfo->remark;
                $item['name']           = $payChannelInfo->show_name;
                $item['is_recommend']   = $payChannelInfo->is_recommend;
                $item['sort']           = $payChannelInfo->sort;
                if(!empty($this->user->real_name)){
                    $item['has_realname']   = 0;
                } else{
                    $item['has_realname']   = $payChannelInfo->has_realname;
                }

                if($this->user->self_deductions_method==2 && $payChannelInfo->gift_ratio < 0){
                    $item['gift_ratio']     = 0;
                } else{
                    $item['gift_ratio']     = $payChannelInfo->gift_ratio;
                }
                
                $carrierPayChannelArr[] = $item;
            }
            $value['items'] = $carrierPayChannelArr;
        }

        //商户平台只有虚拟币处理
        $carrierUsdtGift               = CarrierCache::getCarrierConfigure($this->carrier->id,'carrier_usdt_gift');
        $digitalFinanceMinRecharge     = CarrierCache::getCarrierConfigure($this->carrier->id,'digital_finance_min_recharge');
        $carrierBankGift               = CarrierCache::getCarrierConfigure($this->carrier->id,'carrier_bank_gift');
        $carrierDigitalAddresses       = CarrierDigitalAddress::where('carrier_id',$this->carrier->id)->where('status',1)->get();

        $data                 = [];
        $carrierPayChannelArr = [];
        if(count($carrierDigitalAddresses)){
            foreach ($carrierDigitalAddresses as $k => $v) {
                $item                  = [];
                $item['min']           = $digitalFinanceMinRecharge;
                $item['max']           = $digitalFinanceMaxRecharge;
                $item['showEdit']      = 1;
                $item['payChanneEnum'] = [];
                if($this->user->self_deductions_method==2 && $carrierUsdtGift < 0){
                    $item['gift_ratio']    = 0;
                } else{
                    $item['gift_ratio']    = $carrierUsdtGift;
                }
                
                $item['address']       = $v->address;
                $item['remark']        = '';
                $item['category']      = $v->type;
                $item['id']            = $v->id;
                $item['img']           = '0/bankicon/usdt.png';
                $item['video_url']     = '';
                $item['sort']          = 101;
                $carrierPayChannelArr[]= $item;
            }
        }

        foreach ($payChannelGroups as $k => $v) {
            $data[] = $v; 
        }

        if(count($carrierPayChannelArr)){
            //平台有自用数字币收款
            $row             = [];
            $row['name']     = config('language')[$this->language]['error337'];
            $row['img']      = '0/bankicon/usdt.png';
            $row['currency'] = 'USD';
            $row['type']     = 1;
            $row['items']    = $carrierPayChannelArr;
            $data[]          = $row;            
        }

        //商户平台自有银行卡处理
        $carrierPayChannelArr = [];
        $carrierBankCards     = CarrierBankCard::where('carrier_id',$this->carrier->id)->where('status',1)->get();
        if(count($carrierBankCards)){
            foreach ($carrierBankCards as $k => $v) {

                $carrierBankCardType   =  CarrierBankCardType::where('carrier_id',$this->carrier->id)->where('id',$v->bank_id)->first();
                $item                  = [];
                $item['min']           = $financeMinRecharge;
                $item['max']           = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'finance_max_recharge',$this->prefix);
                $item['showEdit']      = 1;
                $item['payChanneEnum'] = [];

                if($this->user->self_deductions_method==2 && $carrierBankGift < 0){
                    $item['gift_ratio']    = 0;
                } else{
                    $item['gift_ratio']    = $carrierBankGift;
                }

                $item['banknamne']     = $carrierBankCardType->bank_name;
                $item['username']      = $v->bank_username;
                $item['bankaccount']   = $v->bank_account;
                $item['img']           = $carrierBankCardType->bank_background_url;
                $item['video_url']     = '';
                $item['remark']        = '';
                $item['id']            = $v->id;
                $item['sort']          = 100;
                $carrierPayChannelArr[]= $item;
            }
        }

        if(count($carrierPayChannelArr)){
            //平台有自用数字币收款
            $row             = [];
            $row['name']     = config('language')[$this->language]['error338'];
            $row['img']      = '0/bankicon/'.strtolower($currency).'_bank.png';
            $row['currency'] = $currency;
            $row['type']     = 2;
            $row['items']    = $carrierPayChannelArr;
            $data[]          = $row;            
        }
        
        //$input['type'] =1;
        if(count($data) && isset($input['type']) && $input['type']==1){
            $dataArr       = [];
            foreach ($data as $key => $value) {
                foreach ($value['items'] as $k => $v) {
                    $v['currency'] = $value['currency'];
                    $v['type']     = $value['type'];
                    $dataArr[] = $v;
                }
            }

            $flag = [];
            
            $player = Player::where('player_id',$this->user->player_id)->first();

            foreach ($dataArr as $key => &$value) {
                $flag[] = $value['sort']; 
                if(!empty($player->real_name)){
                   $value['real_name'] = 0;
                }
            }
            array_multisort($flag, SORT_DESC, $dataArr);

            return $this->returnApiJson(config('language')[$this->language]['success1'],1,$dataArr);
        } else{
            return $this->returnApiJson(config('language')[$this->language]['success1'],1,$data);
        }
        
    }

    public function updateinfo()
    {
        $input = request()->all();

        $enableEidtTelehoneVerification = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_eidt_telehone_verification',$this->prefix);
        
        if(isset($input['real_name']) && !empty($input['real_name']) && empty($this->user->real_name)) {
            $this->user->real_name      = $this->match_chinese($input['real_name']);
        }

        if(isset($input['email']) && !empty($input['email'])) {
            if(preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$input['email'])) {
               $this->user->email          = $input['email'];
            }
        }
        
        if(isset($input['qq_account']) && !empty($input['qq_account'])) {
            $this->user->qq_account     = $this->match_chinese($input['qq_account']);
        }

        if(isset($input['wechat']) && !empty($input['wechat'])) {
            $this->user->wechat         = $this->match_chinese($input['wechat']);
        }

        if(isset($input['avatar']) && !empty($input['avatar']) && is_numeric($input['avatar'])) {
            $this->user->avatar         = $input['avatar'];
        }

        if(isset($input['nick_name']) && !empty($input['nick_name'])) {
            $this->user->nick_name     = $this->match_chinese($input['nick_name']);
        }

        if(isset($input['birthday']) && !empty($input['birthday']) && is_null($this->user->birthday) && strtotime($input['birthday'])) {
            $this->user->birthday         = $input['birthday'];
        }
        
        if(isset($input['sex']) && in_array($input['sex'], [1,2])) {
            $this->user->sex         = $input['sex'];
        } else {
            $this->user->sex         = null;
        }

        if(isset($input['area']) && !empty($input['area'])) {
            $area = Area::where('id',$input['area'])->where('type',3)->first();
            if($area){
                $this->user->area         = $input['area'];
            }
        }

        if(isset($input['province']) && !empty($input['province'])) {
            $this->user->province         = $input['province'];
        }

       if(isset($input['is_notransfer']) && in_array($input['is_notransfer'],[0,1])) {
            $this->user->is_notransfer         = $input['is_notransfer'];
        }

        if(isset($input['mobile']) && !empty($input['mobile'])){
             //手机号解密
            if(!is_numeric($input['mobile'])){
                $code                     = md5('mobile');
                $iv                       = substr($code,0,16);
                $key                      = substr($code,16);
                $input['mobile']          = openssl_decrypt(base64_decode($input['mobile']), 'AES-128-CBC', $key,1,$iv);
            }

            if($enableEidtTelehoneVerification==0){
                if(is_numeric($input['mobile'])){
                    $this->user->mobile = $input['mobile'];
                }
            } 
        }

        $this->user->save();

        return $this->returnApiJson(config('language')[$this->language]['success1'],1);
    }

    public function updateMoblie()
    {
        $enableEidtTelehoneVerification = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_eidt_telehone_verification',$this->prefix);
        if(!isset($input['mobile']) || empty($input['mobile'])){
            $this->returnApiJson(config('language')[$this->language]['error86'],0);
        }

        if($enableEidtTelehoneVerification==1){
            if(!isset($input['smscode']) || empty($input['smscode'])){
                return $this->returnApiJson(config('language')[$this->language]['error229'],0);
            }

            $shortmobile = cache()->get('short_mobile_'.$this->user->mobile);
            if($shortmobile!=$input['smscode']){
                return $this->returnApiJson(config('language')[$language]['error531'],0);
            }
        } 

        if(!is_numeric($input['mobile'])){
            $code                     = md5('mobile');
            $iv                       = substr($code,0,16);
            $key                      = substr($code,16);
            $input['mobile']          = openssl_decrypt(base64_decode($input['mobile']), 'AES-128-CBC', $key,1,$iv);
        }

        if(!is_numeric($input['mobile'])){
            return $this->returnApiJson(config('language')[$language]['error547'],0);
        }

        $this->user->mobile = $input['mobile'];
        $this->user->save();

        return $this->returnApiJson(config('language')[$this->language]['success1'],1);
    }


    private function match_chinese($chars,$encoding='utf8')
    {
        $pattern = ($encoding=='utf8')?'/[\x{4e00}-\x{9fa5}a-zA-Z0-9_]/u':'/[\x80-\xFF]/';
        preg_match_all($pattern,$chars,$result);
        $temp = join('',$result[0]);
        return $temp;
    }

    public function playerEarnings()
    {
        $ReportPlayerEarningsModel = new ReportPlayerEarnings();
        $res                       = $ReportPlayerEarningsModel->playerEarnings($this->user);

        if(is_array($res)) {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function createMember()
    {
       $flag    = Player::createChild($this->carrier,$this->user);

       if($flag === true) {
            return $this->returnApiJson(config('language')[$this->language]['success1'],1);
       } else {
            return $this->returnApiJson($flag,0);
       }
    }

    public function createRegisterLink()
    {
       $flag    = PlayerInviteCode::createRegisterLink($this->carrier, $this->user);

       if($flag === true) {
            return $this->returnApiJson(config('language')[$this->language]['success1'],1);
       } else {
            return $this->returnApiJson($flag,0);
       }
    }

    public function playerInvitecodeList()
    {
        $data = $this->user->playerInvitecodeList($this->carrier,$this->user->player_id);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function getRegisterLinks($id)
    {
        $data = PlayerInviteCode::where(['carrier_id' => $this->user->carrier_id,'id' => $id,])->first();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function deleteRegisterLinks()
    {
        $input = request()->all();

        if(!isset($input['id']) || empty(trim($input['id']))) {
             return $this->returnApiJson(config('language')[$this->language]['error21'],0);
        }

        $playerInviteCode = PlayerInviteCode::where('id',$input['id'])->first();

        if(!$playerInviteCode) {
            return $this->returnApiJson(config('language')[$this->language]['error56'],0);
        }

        if($playerInviteCode->player_id != $this->user->player_id) {
            return $this->returnApiJson(config('language')[$this->language]['error55'],0);
        }

        $playerInviteCode->delete();

        return $this->returnApiJson(config('language')[$this->language]['success1'],1);
    }

    // 团队管理
    public function teamInfo()
    {
        $data = $this->user->teamInfo($this->carrier);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function directlyunderInfo()
    {
        $data = $this->user->directlyunderInfo($this->carrier);

        if(is_array($data)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
        } else{
            return $this->returnApiJson($data, 0);
        }
    }

    // 团队管理设置赔率返水
    public function setBonus()
    {   
        $res = $this->user->setBonus($this->carrier);

        if ($res === true) {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function winAndLoseList()
    {
        if($this->user->type == 3) {
            return $this->returnApiJson(config('language')[$this->language]['error55'], 0);
        }

       $res = $this->user->winAndLoseList();

       return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $res);
    }

    public function noWithdrawList()
    {
        $playerTransfer = new PlayerTransfer();
        $res            = $playerTransfer->noWithdrawList($this->user);
        if(is_array($res)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function transferTypeList()
    {
        $developments = Development::select('sign','name')->get();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $developments);
    }

    public function withdrawNotice()
    {
        $playerAccount       = PlayerAccount::where('player_id',$this->user->player_id)->first();
        $completeAmount      = PlayerWithdrawFlowLimit::where('player_id',$this->user->player_id)->sum('complete_limit_amount');
        $totalAmount         = PlayerWithdrawFlowLimit::where('player_id',$this->user->player_id)->sum('limit_amount');

        $playerTransfer      = PlayerTransfer::where('player_id',$this->user->player_id)->orderBy('id','desc')->first();
        if($playerTransfer && in_array($playerTransfer->type,['dividend_from_parent','commission_from_child'])){
            $uncompleteAmount = bcdiv(0,10000,2);
        } else{
            $uncompleteAmount = bcdiv($totalAmount - $completeAmount,10000,2);
        }

        $data   = [
            'balance'        => bcdiv($playerAccount->balance,10000,2),
            'completeAmount' => bcdiv($completeAmount,10000,2),
            'uncompleteAmount' => $uncompleteAmount,
            'minWithdrawalUsdt'=> CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'min_withdrawal_usdt',$this->user->prefix),
            'downscore'        => config('main')['forum']['forum1']['downscore']
            ];

        $data['minWithdrawAmount']   = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'finance_min_withdraw',$this->user->prefix);
        $data['isWithdrawMobile']    = $this->user->is_withdraw_mobile;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function withdrawLimit()
    {
        $playerBankCard      = PlayerBankCard::where('player_id',$this->user->player_id)->where('status',1)->first();
        if(!$playerBankCard){
             return $this->returnApiJson(config('language')[$this->language]['error136'], 0,[],200,1);
        }

        if(is_null($this->user->paypassword) || empty($this->user->paypassword)){
            return $this->returnApiJson(config('language')[$this->language]['error137'], 0,[],200,2);
        }

        $playerAccount       = PlayerAccount::where('player_id',$this->user->player_id)->first();
        $completeAmount      = PlayerWithdrawFlowLimit::select(\DB::raw('sum(limit_amount) as limit_amount'),\DB::raw('sum(complete_limit_amount) as complete_limit_amount'))->where('player_id',$this->user->player_id)->first();

        $data   = [
            'balance'        => bcdiv($playerAccount->balance,10000,2),
            'completeAmount' => bcdiv($completeAmount->complete_limit_amount,10000,2),
            'uncompleteAmount' => bcdiv($completeAmount->limit_amount - $completeAmount->complete_limit_amount,10000,2)
            ];

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function earningsDateList()
    {
        $times = ReportPlayerEarnings::where('carrier_id',$this->carrier->id)->groupBy('init_time')->limit(10)->orderBy('id','desc')->pluck('init_time')->toArray();
        $datas = [];
        foreach ($times as $key => $value) {
            $datas[] = date('Y-m-d',$value);
        }
        unset($datas[0]);
        $datas = array_values($datas);
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $datas);
    }

    public function getStyle()
    {
        $datas['style'] = $this->user->style;
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $datas);
    }

    public function setStyle()
    {
        $style  = request()->get('style');
        if(in_array($style, [1,0])) {
            $this->user->style = $style;
            $this->user->save();

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } else {
            return $this->returnApiJson(config('language')[$this->language]['error130'], 0);
        }  
    }

    public function activitiesLuckDrawInfo() 
    {
        $carrierActivityLuckDraw = CarrierActivityLuckDraw::select('name','startTime','endTime','content','number','prize_json','signup_type','number_luck_draw_json','game_category')->where('carrier_id',$this->carrier->id)->where('status',1)->first();

        if(!$carrierActivityLuckDraw){
            return $this->returnApiJson(config('language')[$this->language]['error152'], 0);
        } else {
            $time                        = time();
            $data                        = [];
            $prizeJsons                  = [];
            $prizeJsonArrs               = json_decode($carrierActivityLuckDraw->prize_json,true);
            $carrierActivityLuckDrawArrs = json_decode($carrierActivityLuckDraw->number_luck_draw_json,true);

            foreach ($prizeJsonArrs as $key => $value) {
                $prizeJsons[] = $value['bonus'];
            }

            $amountNumber  = [];
            foreach ($carrierActivityLuckDrawArrs as $key => $value) {
                $amountNumber[$value['amount']] = $value['number'];
            }

            krsort($amountNumber);

            $data['name']       = $carrierActivityLuckDraw->name;
            $data['startTime']  = date('Y-m-d',$carrierActivityLuckDraw->startTime);
            $data['endTime']    = date('Y-m-d',$carrierActivityLuckDraw->endTime);
            $data['content']    = $carrierActivityLuckDraw->content;
            $data['number']     = $carrierActivityLuckDraw->number;
            $data['prize_json'] = $prizeJsons;

            if(empty($this->user) || is_null($this->user)){
                $data['extractnumber'] = 0; 
            } else {
                $startTime  = date('Y-m-d',$time).' 00:00:00';
                $endTime    = date('Y-m-d',$time).' 23:59:59';
                $changeCount = CarrierActivityPlayerLuckDraw::where('player_id',$this->user->player_id)->where('created_at','>=',$startTime)->where('created_at','<=',$endTime)->count();

                if($carrierActivityLuckDraw->signup_type==1){
                    $totalCount = 0;
                    $reportPlayerStatDay = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->first();
                    foreach ($amountNumber as $key => $value) {
                       if($reportPlayerStatDay  && $reportPlayerStatDay->recharge_amount>=$key*10000){
                            $totalCount = $value;
                            break;
                       }
                    }
                    $data['existCount'] = $totalCount - $changeCount;
                } else {
                    $totalCount = 0;
                    if($carrierActivityLuckDraw->game_category) {
                        $availableBetAmount = PlayerBetFlow::where('player_id',$this->user->player_id)->where('game_category',$carrierActivityLuckDraw->game_category)->where('game_status',1)->where('bet_flow_available',1)->where('bet_time','>=',strtotime($startTime))->where('bet_time','<=',strtotime($endTime))->sum('available_bet_amount');
                    } else {
                        $availableBetAmount = PlayerBetFlow::where('player_id',$this->user->player_id)->where('game_status',1)->where('bet_flow_available',1)->where('bet_time','>=',strtotime($startTime))->where('bet_time','<=',strtotime($endTime))->sum('available_bet_amount');
                    }

                    foreach ($amountNumber as $key => $value) {
                       if($availableBetAmount>=$key){
                            $totalCount = $value;
                            break;
                       }
                    }
                    $data['existCount'] = $totalCount - $changeCount;
                }
            }

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
        }
    }

    public function luckDrawExtract()
    {
        $time                        = time();
        $carrierActivityLuckDraw     = CarrierActivityLuckDraw::where('carrier_id',$this->carrier->id)->where('status',1)->first();
        $prizeJsonArrs               = json_decode($carrierActivityLuckDraw->prize_json,true);
        $carrierActivityLuckDrawArrs = json_decode($carrierActivityLuckDraw->number_luck_draw_json,true);

        if($carrierActivityLuckDraw->startTime > $time || $carrierActivityLuckDraw->endTime < $time){
            return $this->returnApiJson(config('language')[$this->language]['error155'], 0);
        }

        $amountNumber  = [];
        foreach ($carrierActivityLuckDrawArrs as $key => $value) {
            $amountNumber[$value['amount']] = $value['number'];
        }

        $probability = 0;
        $prizeJsons  = [];
        
        foreach ($prizeJsonArrs as $key => $value) {
            if(!$probability){
                $prizeJsons[$value['probability']] = $value['bonus'];
                $probability                       = $value['probability'];
            } else {
                $probability              = $value['probability']+$probability;
                $prizeJsons[$probability] = $value['bonus'];
            }    
        }
        
        ksort($prizeJsons);
        krsort($amountNumber);

        $startTime  = date('Y-m-d',$time).' 00:00:00';
        $endTime    = date('Y-m-d',$time).' 23:59:59';

        $changeCount = CarrierActivityPlayerLuckDraw::where('player_id',$this->user->player_id)->where('created_at','>=',$startTime)->where('created_at','<=',$endTime)->count();
        $totalCount  = 0;
        if($carrierActivityLuckDraw->signup_type==1){
            
            $reportPlayerStatDay = ReportPlayerStatDay::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->first();
            foreach ($amountNumber as $key => $value) {
                if($reportPlayerStatDay->recharge_amount>=$key*10000){
                    $totalCount = $value;
                    break;
                }
            }
            $existCount = $totalCount - $changeCount;
        } else {    
            if($carrierActivityLuckDraw->game_category) {
                $availableBetAmount = PlayerBetFlow::where('player_id',$this->user->player_id)->where('game_category',$carrierActivityLuckDraw->game_category)->where('game_status',1)->where('bet_flow_available',1)->where('bet_time','>=',strtotime($startTime))->where('bet_time','<=',strtotime($endTime))->sum('available_bet_amount');
            } else {
                $availableBetAmount = PlayerBetFlow::where('player_id',$this->user->player_id)->where('game_status',1)->where('bet_flow_available',1)->where('bet_time','>=',strtotime($startTime))->where('bet_time','<=',strtotime($endTime))->sum('available_bet_amount');
            }

            foreach ($amountNumber as $key => $value) {
                if($availableBetAmount>=$key){
                    $totalCount = $value;
                    break;
                }
            }
            $existCount = $totalCount - $changeCount;
        }

        if($existCount<1){
            return $this->returnApiJson(config('language')[$this->language]['error156'], 0);
        }

        $rand = mt_rand(1,1000);

        $bonus = 0;
        //开始抽奖
        foreach ($prizeJsons as $key => $value) {
            if($rand <= $key){
                $bonus = $value;
                break;
            }
        }

        try {
            \DB::beginTransaction();
            $carrierActivityPlayerLuckDraw               = new CarrierActivityPlayerLuckDraw();
            $carrierActivityPlayerLuckDraw->carrier_id   = $this->user->carrier_id;
            $carrierActivityPlayerLuckDraw->player_id    = $this->user->player_id;
            $carrierActivityPlayerLuckDraw->user_name    = $this->user->user_name;
            $carrierActivityPlayerLuckDraw->luck_draw_id = $carrierActivityLuckDraw->id;
            $carrierActivityPlayerLuckDraw->money        = $bonus*10000;
            $carrierActivityPlayerLuckDraw->save();

            $carrierActivityLuckDraw->payout             = $carrierActivityLuckDraw->payout + $bonus*10000;
            $carrierActivityLuckDraw->person_account     = $carrierActivityLuckDraw->person_account + 1;
            $carrierActivityLuckDraw->save();

            $playerReceiveGiftCenter                     = new PlayerReceiveGiftCenter();
            $playerReceiveGiftCenter->orderid            = 'LJ'.$this->user->player_id.time().rand('1','99');
            $playerReceiveGiftCenter->carrier_id         = $this->user->carrier_id;
            $playerReceiveGiftCenter->player_id          = $this->user->player_id;
            $playerReceiveGiftCenter->user_name          = $this->user->user_name;
            $playerReceiveGiftCenter->top_id             = $this->user->top_id;
            $playerReceiveGiftCenter->parent_id          = $this->user->parent_id;
            $playerReceiveGiftCenter->rid                = $this->user->rid;
            $playerReceiveGiftCenter->type               = 21;
            $playerReceiveGiftCenter->amount             = $carrierActivityPlayerLuckDraw->money;
            $playerReceiveGiftCenter->invalidtime        = time()+31536000;
            $playerReceiveGiftCenter->limitbetflow       = $carrierActivityPlayerLuckDraw->money;
            $playerReceiveGiftCenter->save();

            \DB::commit();
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['bonus'=>$bonus,'number'=>$existCount-1]);
        } catch (\Exception $e) {
            \DB::rollback();
            Clog::recordabnormal('参与幸运轮盘异常:'.$e->getMessage());   
            return $this->returnApiJson(config('language')[$this->language]['error20'],0);
        }
    }

    public function fileUpload($directory) 
    {
        $input        = request()->all();
        $directoryArr = ['avatar','depositimg'];

        if(!in_array($directory, $directoryArr)) {
            return $this->returnApiJson(config('language')[$this->language]['error153'], 0);
        }

        if(!array_key_exists('file', $input) || empty($input['file'])){
            return $this->returnApiJson(config('language')[$this->language]['error154'], 0);
        }

        $arr = [
            'carrier_id'=>$this->carrier->id,
            'directory'=>$directory
        ];

        $res = S3::uploadImage($input['file'], $arr);
        if(is_array($res)) {
            if($directory=='avatar'){
                $this->user->avatar = $res['path'];
                $this->user->save();
            }

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$res);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function existLuckDrawExtract()
    {
        $carrierActivityLuckDraw = CarrierActivityLuckDraw::where('carrier_id',$this->carrier->id)->where('status',1)->first();
        if($carrierActivityLuckDraw){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['exist'=>1]);
        } else {
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['exist'=>0]);
        }
    }

    public function promoteStat()
    {
        $promoteStatCount = Player::where('rid','like',$this->user->rid.'|%')->count();
        $playerAccount    = PlayerAccount::where('player_id',$this->user->player_id)->first();
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['coin'=>bcdiv($playerAccount->balance,10000,2),'promote'=>$promoteStatCount]);
    }

    public function digitalAdd($digitalId=0)
    {
        $input              = request()->all();

        if($digitalId){
            $playerDigitalAddress = PlayerDigitalAddress::where('carrier_id',$this->carrier->id)->where('id',$digitalId)->first();
        } else {
            $playerDigitalAddress = new PlayerDigitalAddress();
        }

        $cacheKey = 'digitaladd_'.$this->user->player_id;
        if(cache()->get($cacheKey,0)==0){
            cache()->put($cacheKey, 1, now()->addSeconds(3));
        } else {
            return $this->returnApiJson(config('language')[$this->language]['error335'],0);
        }

        $res = $playerDigitalAddress->digitalAdd($this->user,$this->carrier);

        if($res===true){
            if($input['type']==1){
                $msg = 'Trc20地址';
            } else if($input['type']==2){
                $msg = 'Erc20地址';
            } else if($input['type']==3){
                $msg = 'Okpay地址';
            } else if($input['type']==4){
                $msg = 'Gopay地址';
            } else if($input['type']==6){
                $msg = 'Topay地址';
            } else if($input['type']==7){
                $msg = 'Ebpay地址';
            } elseif($input['type']==8){
                $msg = 'Wanb地址';
            } elseif($input['type']==9){
                $msg = 'Jdpay地址';
            } elseif($input['type']==10){
                $msg = 'Kdpay地址';
            } elseif($input['type']==11){
                $msg = 'Nopay地址';
            } elseif($input['type']==12){
                $msg = 'Bobipay地址';
            }

            $playerOperate                                    = new PlayerOperate();
            $playerOperate->carrier_id                        = $this->carrier->id;
            $playerOperate->player_id                         = $this->user->player_id;
            $playerOperate->user_name                         = $this->user->user_name;
            $playerOperate->type                              = 2;
            $playerOperate->desc                              = '绑定'.$msg.':'.$input['address'];
            $playerOperate->ip                                = ip2long(real_ip());
            $playerOperate->save();

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function digitalDelete($digitalId=0)
    {
        if($digitalId){
            $playerDigitalAddress = PlayerDigitalAddress::where('player_id',$this->user->player_id)->where('id',$digitalId)->first();
            if($playerDigitalAddress){
                $playerDigitalAddress->delete();
                return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
            } else {
                return $this->returnApiJson(config('language')[$this->language]['error191'], 0);
            }
        } else {
            return $this->returnApiJson(config('language')[$this->language]['error191'], 0);
        }
    }

    public function digitalList()
    {
        $playerDigitalAddress = PlayerDigitalAddress::where('player_id',$this->user->player_id)->where('status',1)->orderBy('id','desc')->get();

        foreach($playerDigitalAddress  as $key => &$value){
            switch ($value->type) {
                case '1':
                    $value->type_name ='Trc20';
                    $value->img_url   ='0/third_wallet_icon/Trc20.png';
                    break;
                case '2':
                    $value->type_name ='Erc20';
                    $value->img_url   ='0/third_wallet_icon/Erc20.png';
                    break;
                case '3':
                    $value->type_name ='Okpay';
                    $value->img_url   ='0/third_wallet_icon/Okpay.png';
                    break;
                case '4':
                    $value->type_name ='Gopay';
                    $value->img_url   ='0/third_wallet_icon/Gopay.png';
                    break;
                case '5':
                    $value->type_name ='Gcash';
                    $value->img_url   ='0/third_wallet_icon/Gcash.png';
                    break;
                case '6':
                    $value->type_name ='Topay';
                    $value->img_url   ='0/third_wallet_icon/Topay.png';
                    break;
                case '7':
                    $value->type_name ='Ebpay';
                    $value->img_url   ='0/third_wallet_icon/Ebpay.png';
                    break;
                case '8':
                    $value->type_name ='Wanbpay';
                    $value->img_url   ='0/third_wallet_icon/Wanbpay.png';
                    break;
                case '9':
                    $value->type_name ='Jdpay';
                    $value->img_url   ='0/third_wallet_icon/Jdpay.png';
                    break;
                case '10':
                    $value->type_name ='Kdpay';
                    $value->img_url   ='0/third_wallet_icon/Kdpay.png';
                    break;
                case '11':
                    $value->type_name ='Nopay';
                    $value->img_url   ='0/third_wallet_icon/Nopay.png';
                    break;
                case '12':
                    $value->type_name ='Bobipay';
                    $value->img_url   ='0/third_wallet_icon/Bobipay.png';
                    break;
                default:
                    $value->type_name = '';
                    break;
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$playerDigitalAddress);
    }

    public function digitalExtra()
    {
        $data['digital_rate']          = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'digital_rate',$this->user->prefix);
        $withdrawDigitalRate           = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'withdraw_digital_rate',$this->user->prefix);
        $inROutU                       = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id, 'in_r_out_u',$this->user->prefix);
        $inTOutU                       = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id, 'in_t_out_u',$this->user->prefix);

        //查询最近一次充值
        $latePlayerDepositPayLog      = PlayerDepositPayLog::where('player_id',$this->user->player_id)->where('is_agent',0)->where('status',1)->orderBy('id','desc')->first();
        $cnyThirdWalletArr            = config('main')['digitalpay']['CNY'];
        if($latePlayerDepositPayLog){
            if(strpos($latePlayerDepositPayLog->pay,'USDT') === false){
                $withdrawDigitalRate = $inROutU;
            } else{
                foreach ($cnyThirdWalletArr as $key => $value) {
                    if(stristr($latePlayerDepositPayLog->collection, $value)!==false){
                        $withdrawDigitalRate = $inTOutU;
                        break;
                    }
                }
            }
        }

        $data['withdraw_digital_rate'] = $withdrawDigitalRate;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    //获取是否有可领的周薪与月薪
    public function vipSalary()
    {
        $data                = [];
        $carrierPlayerGrade  = CarrierPlayerGrade::where('id',$this->user->player_level_id)->first();
        $defaultDate         = date('Y-m-d');
        $w                   = date('w',strtotime($defaultDate));
        $weekStart           = date('Ymd',strtotime("$defaultDate -".($w?$w-1:6).' days'));
        $weekEnd             = date('Ymd',strtotime("$weekStart + 6 days"));
        $monthStart          = date('Ym01',time());

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function getBetFlowStat()
    {
        $input = request()->all();
        $query = ReportPlayerStatDay::select(\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(gift) as gift'))->where('player_id',$this->user->player_id);

        $query1 = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','recharge');
        $query2 = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','withdraw_finish');

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query1->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query2->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        } else{
            $query->where('day','>=',date('Ymd'));
            $query1->where('day','>=',date('Ymd'));
            $query2->where('day','>=',date('Ymd'));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query1->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query2->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $rechargeAmount = $query1->sum('amount');
        $withdrawAmount = $query2->sum('amount');
        $betflowstat = $query->first();

        $data = [];
        if($betflowstat){
            $data['available_bets'] = bcdiv($betflowstat->available_bets + $betflowstat->lottery_available_bets,10000,2);
            $data['win_amount']     = bcdiv($betflowstat->win_amount + $betflowstat->lottery_winorloss,10000,2);
            $data['gift']           = bcdiv($betflowstat->gift,10000,2);
            $data['rechargeAmount'] = bcdiv($rechargeAmount,10000,2);
            $data['withdrawAmount'] = bcdiv($withdrawAmount,10000,2);
        } else {
            $data['available_bets'] = 0.00;
            $data['win_amount']     = 0.00;
            $data['gift']           = 0.00;
            $data['rechargeAmount'] = bcdiv($rechargeAmount,10000,2);
            $data['withdrawAmount'] = bcdiv($withdrawAmount,10000,2);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function achievement()
    {
        $input                      = request()->all();
        $startDate                  = null;
        $endDate                    = null;

        //1=今天，2=昨天，3=本周，4=上周，5=本月
        if(!isset($input['type']) || !in_array($input['type'], [1,2,3,4,5])){
            return $this->returnApiJson(config('language')[$this->language]['error247'], 0);
        }

        switch ($input['type']) {
            case '1':
                $startDate   = date('Ymd');
                $endDate     = date('Ymd');
                break;
            case '2':
                $startDate   = date("Ymd",strtotime("-1 day"));
                $endDate     = date("Ymd",strtotime("-1 day"));
                break;
            case '3':
                $weekTime    = getWeekStartEnd();
                $startDate   = $weekTime[2];
                $endDate     = $weekTime[3];
                break;
            case '4':
                if(date('w')==1){
                    $startDate = date('Ymd', strtotime('last monday'));
                } else{
                    $startDate = date('Ymd', strtotime('-1 week last monday'));
                }
                $endDate        = date('Ymd', strtotime($startDate)+518400);
                break;
            case '5':
                $monthTime   = getMonthStartEnd();
                $startDate   = $monthTime[0];
                $endDate     = $monthTime[1];
                break;
            default:
                break;
        }

        $directlyUnderPlayerIds = Player::where('parent_id',$this->user->player_id)->pluck('player_id')->toArray();
        $teamPlayerIds          = Player::whereNotIn('player_id',$directlyUnderPlayerIds)->where('rid','like',$this->user->rid.'%')->pluck('player_id')->toArray();
        $teamStat               = PlayerBetFlowMiddle::select('agent_process_available_bet_amount','game_category')->whereIn('player_id',$teamPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->groupby('game_category')->get();
        $directlyUnderStat      = PlayerBetFlowMiddle::select('agent_process_available_bet_amount','game_category')->whereIn('player_id',$directlyUnderPlayerIds)->where('day','>=',$startDate)->where('day','<=',$endDate)->groupby('game_category')->get();
        $data =[
            'team_casino_achievement'                => 0,
            'team_electronic_achievement'            => 0,
            'team_esport_achievement'                => 0,
            'team_fish_achievement'                  => 0,
            'team_card_achievement'                  => 0,
            'team_lottery_achievement'               => 0,
            'team_sport_achievement'                 => 0,
            'directlyUnder_casino_achievement'       => 0,
            'directlyUnder_electronic_achievement'   => 0,
            'directlyUnder_esport_achievement'       => 0,
            'directlyUnder_fish_achievement'         => 0,
            'directlyUnder_card_achievement'         => 0,
            'directlyUnder_lottery_achievement'      => 0,
            'directlyUnder_sport_achievement'        => 0,
            'casino_achievement_commission_rate'     => 0,
            'electronic_achievement_commission_rate' => 0,
            'esport_achievement_commission_rate'     => 0,
            'card_achievement_commission_rate'       => 0,
            'sport_achievement_commission_rate'      => 0,
            'lottery_achievement_commission_rate'    => 0,
            'fish_achievement_commission_rate'       => 0,
        ];

        foreach ($teamStat as $key => $value) {
            switch ($value->game_category) {
                case 1:
                    $data['team_casino_achievement'] = $value->process_available_bet_amount;
                    break;
                case 2:
                    $data['team_electronic_achievement'] = $value->process_available_bet_amount;
                    break;
                case 3:
                    $data['team_esport_achievement'] = $value->process_available_bet_amount;
                    break;
                case 4:
                    $data['team_card_achievement'] = $value->process_available_bet_amount;
                    break;
                case 5:
                    $data['team_sport_achievement'] = $value->process_available_bet_amount;
                    break;
                case 6:
                    $data['team_lottery_achievement'] = $value->process_available_bet_amount;
                    break;
                case 7:
                    $data['team_fish_achievement'] = $value->process_available_bet_amount;
                    break;
                default:
                    break;
            }
        }

        foreach ($directlyUnderStat as $key => $value) {
            switch ($value->game_category) {
                case 1:
                    $data['directlyUnder_casino_achievement'] = $value->process_available_bet_amount;
                    break;
                case 2:
                    $data['directlyUnder_electronic_achievement'] = $value->process_available_bet_amount;
                    break;
                case 3:
                    $data['directlyUnder_esport_achievement'] = $value->process_available_bet_amount;
                    break;
                case 4:
                    $data['directlyUnder_card_achievement'] = $value->process_available_bet_amount;
                    break;
                case 5:
                    $data['directlyUnder_sport_achievement'] = $value->process_available_bet_amount;
                    break;
                case 6:
                    $data['directlyUnder_lottery_achievement'] = $value->process_available_bet_amount;
                    break;
                case 7:
                    $data['directlyUnder_fish_achievement'] = $value->process_available_bet_amount;
                    break;
                
                default:
                    // code...
                    break;
            }
        }

        $videoInviteGradientRebate  = CarrierCache::getCarrierConfigure($this->user->carrier_id,'video_invite_gradient_rebate');
        $eleInviteGradientRebate    = CarrierCache::getCarrierConfigure($this->user->carrier_id,'ele_invite_gradient_rebate');
        $esportInviteGradientRebate = CarrierCache::getCarrierConfigure($this->user->carrier_id,'esport_invite_gradient_rebate');
        $cardInviteGradientRebate   = CarrierCache::getCarrierConfigure($this->user->carrier_id,'card_invite_gradient_rebate');
        $sportInviteGradientRebate  = CarrierCache::getCarrierConfigure($this->user->carrier_id,'sport_invite_gradient_rebate');
        $fishInviteGradientRebate   = CarrierCache::getCarrierConfigure($this->user->carrier_id,'fish_invite_gradient_rebate');
        $lottInviteGradientRebate   = CarrierCache::getCarrierConfigure($this->user->carrier_id,'lott_invite_gradient_rebate');

        $videoInviteGradientRebate  = json_decode($videoInviteGradientRebate,true);
        $eleInviteGradientRebate    = json_decode($eleInviteGradientRebate,true);
        $esportInviteGradientRebate = json_decode($esportInviteGradientRebate,true);
        $cardInviteGradientRebate   = json_decode($cardInviteGradientRebate,true);
        $sportInviteGradientRebate  = json_decode($sportInviteGradientRebate,true);
        $fishInviteGradientRebate   = json_decode($fishInviteGradientRebate,true);
        $lottInviteGradientRebate   = json_decode($lottInviteGradientRebate,true);

        foreach ($videoInviteGradientRebate as $k2 => $v2) {
            if($data['team_casino_achievement'] >$v2['probability']){
                $data['casino_achievement_commission_rate']  = $v2['bonus'];
            }
        }

        foreach ($eleInviteGradientRebate as $k2 => $v2) {
            if($data['team_electronic_achievement'] >$v2['probability']){
            $data['electronic_achievement_commission_rate']  = $v2['bonus'];
            }
        }

        foreach ($esportInviteGradientRebate as $k2 => $v2) {
            if($data['team_esport_achievement'] >$v2['probability']){
                $data['esport_achievement_commission_rate']  = $v2['bonus'];
            }
        }

        foreach ($cardInviteGradientRebate as $k2 => $v2) {
            if($data['team_card_achievement'] >$v2['probability']){
                $data['card_achievement_commission_rate']  = $v2['bonus'];
            }
        }

        foreach ($sportInviteGradientRebate as $k2 => $v2) {
            if($data['team_sport_achievement'] >$v2['probability']){
                $data['sport_achievement_commission_rate']  = $v2['bonus'];
            }
        }

        foreach ($fishInviteGradientRebate as $k2 => $v2) {
            if($data['team_fish_achievement'] >$v2['probability']){
                $data['fish_achievement_commission_rate']  = $v2['bonus'];
            }
        }

        foreach ($lottInviteGradientRebate as $k2 => $v2) {
            if($data['team_lottery_achievement'] >$v2['probability']){
                $data['lottery_achievement_commission_rate']  = $v2['bonus'];
            }
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function playerinvitecodesave($playerInvitecodeId)
    {
        $input            = request()->all();
        $playerInviteCode = PlayerInviteCode::where('player_id',$this->user->player_id)->where('id',$playerInvitecodeId)->first();
        $playerSettings   = PlayerCache::getPlayerSetting($this->user->player_id);

        if(!$playerInviteCode){
            return $this->returnApiJson(config('language')[$this->language]['error339'], 0);
        }

        if(isset($input['earnings'])){
            if(!is_numeric($input['earnings']) || $input['earnings'] < 0 ){
                return $this->returnApiJson(config('language')[$this->language]['error340'], 0);
            } elseif($playerSettings->earnings < $input['earnings']){
                return $this->returnApiJson(config('language')[$this->language]['error341'], 0);
            } else{
                $playerInviteCode->earnings = $input['earnings'];
            }
        }

        $playerInviteCode->save();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function gameCollectStatusChange($id)
    {
        $input       = request()->all();

        if(!isset($input['lott_id']) || empty($input['lott_id'])){
            $carrierGame = CarrierGame::where('carrier_id',$this->carrier->id)->where('game_id',$id)->first();

            if(!$carrierGame){
                return $this->returnApiJson(config('language')[$this->language]['error30'], 0);
            }
        }

        if(!isset($input['lott_id']) || empty($input['lott_id'])){
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('game_id',$id)->where('is_self',0)->first();
        } else{
            $playerGameCollect = PlayerGameCollect::where('player_id',$this->user->player_id)->where('game_id',$input['lott_id'])->where('is_self',1)->first();
        }
        
        if($playerGameCollect){
            PlayerGameCollect::where('player_id',$this->user->player_id)->where('game_id',$id)->delete();
        } else{
            $playerGameCollect                = new PlayerGameCollect();
            $playerGameCollect->player_id     = $this->user->player_id;
            $playerGameCollect->user_name     = $this->user->user_name;
            $playerGameCollect->top_id        = $this->user->top_id;
            $playerGameCollect->parent_id     = $this->user->parent_id;
            $playerGameCollect->rid           = $this->user->rid;
            $playerGameCollect->carrier_id    = $this->user->carrier_id;
            $playerGameCollect->game_id       = $id;
            
            if(!isset($input['lott_id']) || empty($input['lott_id'])){
                $playerGameCollect->game_category = $carrierGame->game_category;
                
            } else{
                $playerGameCollect->game_category = 6;
                $playerGameCollect->is_self       = 1;
            }
            $playerGameCollect->save();
        }
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function gameCollectList()
    {
        $data           = [];
        $gameCategories = PlayerGameCollect::where('player_id',$this->user->player_id)->groupBy('game_category')->pluck('game_category')->toArray();

        foreach ($gameCategories as $key => $value) {
            $i     = 1;
            $row   = [];
            $games =PlayerGameCollect::select('def_games.*','def_main_game_plats.main_game_plat_code')->leftJoin('def_games','def_games.game_id','=','inf_player_game_collect.game_id')->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','def_games.main_game_plat_id')->where('inf_player_game_collect.player_id',$this->user->player_id)->where('inf_player_game_collect.game_category',$value)->where('inf_player_game_collect.is_self',0)->orderBy('inf_player_game_collect.id','desc')->get()->toArray();
                        
            $row['category'] = $value;
            foreach ($games as $k => &$v) {
                $v['is_collect'] =1;

                switch ($value) {
                case 1:
                    $v['template_moblie_game_icon_path'] = '/game/live/'.$i.'.png';
                    $v['template_game_icon_path'] = '/game/live/'.$i.'.png';
                    $v['game_icon_path'] = '/game/live/'.$v['main_game_plat_code'].'.png';
                    $v['game_icon_path_moblie'] = '/game/live/'.$v['main_game_plat_code'].'.png';
                    break;
                case 3:
                    $v['template_moblie_game_icon_path'] = '/game/esport/'.$i.'.png';
                    $v['template_game_icon_path'] = '/game/esport/'.$i.'.png';
                    $v['game_icon_path'] = '/game/esport/'.$v['main_game_plat_code'].'.png';
                    $v['game_icon_path_moblie'] = '/game/esport/'.$v['main_game_plat_code'].'.png';
                    break;
                case 5:
                    $v['template_moblie_game_icon_path'] = '/game/sport/'.$i.'.png';
                    $v['template_game_icon_path'] = '/game/sport/'.$i.'.png';
                    $v['game_icon_path'] = '/game/sport/'.$v['main_game_plat_code'].'.png';
                    $v['game_icon_path_moblie'] = '/game/sport/'.$v['main_game_plat_code'].'.png';
                    break;
                
                default:
                    // code...
                    break;
                }
            }

            $row['list']     = $games;
            $row['url']      = config('main')['alicloudstore'].'0/template/';
            $row['mobileurl']= config('main')['alicloudstore'].'0/mobiletemplate/'; 
            $data[]          = $row;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function getPlayerSetting()
    {
        $playerSettings = PlayerSetting::where('player_id',$this->user->player_id)->first()->toArray();
        $data = [];
        $keys = ['earnings'];
        foreach ($playerSettings as $key => $value) {
            if(in_array($key,$keys) && $value>0){
                $row          = [];
                $row['key']   = $key;
                $row['value'] = $value;
                $row['label'] = config('language')[$this->language]['error331'];
                $data[]       = $row; 
            }      
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function subordinateFinanceStat()
    {
        $input = request()->all();

        if(!isset($input['startDate']) || !strtotime($input['startDate']) || !isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error342'], 0); 
        }

        $reportPlayerStatDay = ReportPlayerStatDay::select(\DB::raw('sum(team_win_amount) as team_win_amount'),\DB::raw('sum(team_lottery_winorloss) as team_lottery_winorloss'),\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(rebate) as rebate'),\DB::raw('sum(team_recharge_amount) as team_recharge_amount'),\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(team_withdraw_amount) as team_withdraw_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(team_gift) as team_gift'),\DB::raw('sum(gift) as gift'),\DB::raw('sum(team_available_bets) as team_available_bets'),\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(team_lottery_available_bets) as team_lottery_available_bets'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'),\DB::raw('sum(team_first_recharge_count) as team_first_recharge_count'),\DB::raw('sum(first_recharge_count) as first_recharge_count'),\DB::raw('sum(team_have_bet) as team_have_bet'),\DB::raw('sum(have_bet) as have_bet'))
            ->where('player_id',$this->user->player_id)
            ->where('day','>=',date('Ymd',strtotime($input['startDate'])))
            ->where('day','<=',date('Ymd',strtotime($input['endDate'])))
            ->first();

        $childPlayerIds         = Player::where('parent_id',$this->user->player_id)->pluck('player_id')->toArray();
        $reportPlayerStatDaySon = ReportPlayerStatDay::select(\DB::raw('sum(win_amount) as win_amount'),\DB::raw('sum(lottery_winorloss) as lottery_winorloss'),\DB::raw('sum(recharge_amount) as recharge_amount'),\DB::raw('sum(withdraw_amount) as withdraw_amount'),\DB::raw('sum(gift) as gift'),\DB::raw('sum(available_bets) as available_bets'),\DB::raw('sum(lottery_available_bets) as lottery_available_bets'),\DB::raw('sum(first_recharge_count) as first_recharge_count'),\DB::raw('sum(have_bet) as have_bet'))
            ->whereIn('player_id',$childPlayerIds)
            ->where('day','>=',date('Ymd',strtotime($input['startDate'])))
            ->where('day','<=',date('Ymd',strtotime($input['endDate'])))
            ->first();

        $data = [];
        if($reportPlayerStatDay){
            $data['team_win_amount']                = bcdiv($reportPlayerStatDay->team_win_amount + $reportPlayerStatDay->team_lottery_winorloss - $reportPlayerStatDay->win_amount - $reportPlayerStatDay->lottery_winorloss,10000,2);
            $data['team_first_recharge_count']      = $reportPlayerStatDay->team_first_recharge_count -$reportPlayerStatDay->first_recharge_count;
            $data['team_have_bet']                  = $reportPlayerStatDay->team_have_bet - $reportPlayerStatDay->have_bet;
            $data['team_available_bets']            = bcdiv($reportPlayerStatDay->team_available_bets + $reportPlayerStatDay->team_lottery_available_bets - $reportPlayerStatDay->available_bets - $reportPlayerStatDay->lottery_available_bets,10000,2);
            $data['team_gift']                      = bcdiv($reportPlayerStatDay->team_gift - $reportPlayerStatDay->gift,10000,2);
            $data['team_recharge_amount']           = bcdiv($reportPlayerStatDay->team_recharge_amount - $reportPlayerStatDay->recharge_amount,10000,2);
            $data['team_withdraw_amount']           = bcdiv($reportPlayerStatDay->team_withdraw_amount - $reportPlayerStatDay->withdraw_amount,10000,2);
        } else{
            $data['team_win_amount']                = 0;
            $data['team_first_recharge_count']      = 0;
            $data['team_have_bet']                  = 0;
            $data['team_available_bets']            = 0;
            $data['team_gift']                      = 0;
            $data['team_recharge_amount']           = 0;
        }


        if($reportPlayerStatDaySon){
            $data['win_amount']                = bcdiv($reportPlayerStatDaySon->win_amount + $reportPlayerStatDaySon->lottery_winorloss,10000,2);
            $data['first_recharge_count']      = $reportPlayerStatDaySon->first_recharge_count;
            $data['have_bet']                  = $reportPlayerStatDaySon->have_bet;
            $data['available_bets']            = bcdiv($reportPlayerStatDaySon->available_bets + $reportPlayerStatDaySon->lottery_available_bets,10000,2);
            $data['gift']                      = bcdiv($reportPlayerStatDaySon->gift,10000,2);
            $data['recharge_amount']           = bcdiv($reportPlayerStatDaySon->recharge_amount,10000,2);
            $data['withdraw_amount']           = bcdiv($reportPlayerStatDaySon->withdraw_amount,10000,2);
        } else{
            $data['win_amount']                = 0;
            $data['first_recharge_count']      = 0;
            $data['have_bet']                  = 0;
            $data['available_bets']            = 0;
            $data['gift']                      = 0;
            $data['recharge_amount']           = 0;
        }

        //新增用户
        $addPlayerCount              = Player::where('rid','like',$this->user->rid.'|%')->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00')->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59')->count();

        //直属新增
        $addsonPlayerCount           = Player::where('parent_id',$this->user->player_id)->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00')->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59')->count();
        $data['team_addPlayerCount'] = $addPlayerCount;
        $data['addPlayerCount']      = $addsonPlayerCount;

        $data['team_allPlayerCount'] = $this->user->descendantscount;
        $data['allPlayerCount']      = $this->user->soncount; 

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function subordinateBetflowStat()
    {
        $input = request()->all();

        if(!isset($input['startDate']) || !strtotime($input['startDate']) || !isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error342'], 0); 
        }

        $playerIds           = Player::where('rid','like',$this->user->rid.'|%')->where('created_at','>=',date('Y-m-d',strtotime($input['startDate'])).' 00:00:00')->where('created_at','<=',date('Y-m-d',strtotime($input['endDate'])).' 23:59:59')->pluck('player_id')->toArray();
        $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(bet_amount) as bet_amount'),\DB::raw('sum(company_win_amount) as company_win_amount'),'game_category')->whereIn('player_id',$playerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('game_category')->get();

        $data = [];

        if(count($playerBetFlowMiddle)){
            $data = $playerBetFlowMiddle->toArray();
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function feedbackSave()
    {
        $input     = request()->all();
        $questions = config('main')['questiondesc'];
        $keys      = [];

        foreach ($questions as $key => $value) {
            $keys[] = $key;
        }

        if(!isset($input['type']) || !in_array($input['type'],$keys)){
            return $this->returnApiJson(config('language')[$this->language]['error343'], 0);
        }

        if(!isset($input['title']) || empty($input['title'])){
            return $this->returnApiJson(config('language')[$this->language]['error344'], 0);
        }

        if(!isset($input['content']) || empty($input['content'])){
            return $this->returnApiJson(config('language')[$this->language]['error345'], 0);
        }

        $carrierFeedback             = new CarrierFeedback();
        $carrierFeedback->carrier_id = $this->carrier->id;
        $carrierFeedback->type       = $input['type'];
        $carrierFeedback->title      = $input['title'];
        $carrierFeedback->content    = $input['content'];
        $carrierFeedback->player_id  = $this->user->player_id;

        if(isset($input['img_url']) && !empty($input['img_url'])){
            $carrierFeedback->img_url    = $input['img_url'];
        }

        $carrierFeedback->save();
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function mutualTransfer()
    {
        $input = request()->all();

        if(!isset($input['type']) || !in_array($input['type'], [1,2])){
            return $this->returnApiJson(config('language')[$this->language]['error346'], 0);
        }

        if(!isset($input['amount']) || !is_numeric($input['amount']) || $input['amount']<=0 ){
            return $this->returnApiJson(config('language')[$this->language]['error347'], 0);
        }

        //钱包转入保险箱
        if($input['type']==1){
            $playerWithdrawFlowLimit = PlayerWithdrawFlowLimit::select(\DB::raw('sum(limit_amount) as limit_amount'),\DB::raw('sum(complete_limit_amount) as complete_limit_amount'))->where('player_id',$this->user->player_id)->where('is_finished',0)->first();

            if($playerWithdrawFlowLimit && !is_null($playerWithdrawFlowLimit->limit_amount)){
                $limitFlowLimit = bcdiv($playerWithdrawFlowLimit->limit_amount - $playerWithdrawFlowLimit->complete_limit_amount,10000,2);

                return $this->returnApiJson(config('language')[$this->language]['error349'].$limitFlowLimit.config('language')[$this->language]['error350'], 0);
            }

            try {
                \DB::beginTransaction();
                $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();
                $player        = Player::where('player_id',$this->user->player_id)->first();

                if($playerAccount->balance<$input['amount']*10000){
                    return $this->returnApiJson(config('language')[$this->language]['error348'], 0);
                }

                $playerTransefer                            = new PlayerTransfer();
                $playerTransefer->prefix                    = $player->prefix;
                $playerTransefer->carrier_id                = $playerAccount->carrier_id;
                $playerTransefer->rid                       = $playerAccount->rid;
                $playerTransefer->top_id                    = $playerAccount->top_id;
                $playerTransefer->parent_id                 = $playerAccount->parent_id;
                $playerTransefer->player_id                 = $playerAccount->player_id;
                $playerTransefer->is_tester                 = $playerAccount->is_tester;
                $playerTransefer->user_name                 = $playerAccount->user_name;
                $playerTransefer->level                     = $playerAccount->level;
                $playerTransefer->mode                      = 3;
                $playerTransefer->type                      = 'transfer_in_safe';
                $playerTransefer->type_name                 = config('language')['zh']['text143'];
                $playerTransefer->en_type_name              = config('language')['en']['text143'];
                $playerTransefer->day_m                     = date('Ym');
                $playerTransefer->day                       = date('Ymd');
                $playerTransefer->amount                    = $input['amount']*10000;
                $playerTransefer->before_balance            = $playerAccount->balance;
                $playerTransefer->balance                   = $playerAccount->balance - $playerTransefer->amount;
                $playerTransefer->before_frozen_balance     = $playerAccount->frozen;
                $playerTransefer->frozen_balance            = $playerAccount->frozen;
                $playerTransefer->before_agent_balance         = $playerAccount->agentbalance;
                $playerTransefer->agent_balance                = $playerAccount->agentbalance + $playerTransefer->amount;
                $playerTransefer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                $playerTransefer->agent_frozen_balance         = $playerAccount->agentfrozen;
                $playerTransefer->save();

                $playerAccount->balance      = $playerAccount->balance - $input['amount']*10000;
                $playerAccount->agentbalance = $playerAccount->agentbalance + $input['amount']*10000;
                $playerAccount->save();

                if($playerAccount->balance<0){
                    \Clog::payMsg('钱包转入保险箱请求的参数是', $input);
                }

                \DB::commit();
                return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($playerAccount->balance,10000,2),'agentbalance'=>bcdiv($playerAccount->agentbalance,10000,2)]);
            } catch (\Exception $e) {
                \DB::rollback(); 
                Clog::recordabnormal('钱包互转异常:'.$e->getMessage());  
                return $this->returnApiJson(config('language')[$this->language]['error351'].'changeBalance：'.$e->getMessage(), 0);
            }     
        } elseif($input['type']==2){
             try {
                \DB::beginTransaction();
                $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();

                if($playerAccount->agentbalance<$input['amount']*10000){
                    return $this->returnApiJson(config('language')[$this->language]['error352'], 0);
                }

                $nearPlayerTransfer =  PlayerTransfer::where('player_id',$this->user->player_id)->where('type','!=','withdraw_apply')->orderBy('id','desc')->first();

                $playerTransefer                            = new PlayerTransfer();
                $playerTransefer->prefix                    = $this->user->prefix;
                $playerTransefer->carrier_id                = $playerAccount->carrier_id;
                $playerTransefer->rid                       = $playerAccount->rid;
                $playerTransefer->top_id                    = $playerAccount->top_id;
                $playerTransefer->parent_id                 = $playerAccount->parent_id;
                $playerTransefer->player_id                 = $playerAccount->player_id;
                $playerTransefer->is_tester                 = $playerAccount->is_tester;
                $playerTransefer->user_name                 = $playerAccount->user_name;
                $playerTransefer->level                     = $playerAccount->level;
                $playerTransefer->mode                      = 3;
                $playerTransefer->type                      = 'transfer_in_wallet';
                $playerTransefer->type_name                 = config('language')['zh']['text142'];
                $playerTransefer->en_type_name              = config('language')['en']['text142'];
                $playerTransefer->day_m                     = date('Ym');
                $playerTransefer->day                       = date('Ymd');
                $playerTransefer->amount                    = $input['amount']*10000;
                $playerTransefer->before_balance            = $playerAccount->balance;
                $playerTransefer->balance                   = $playerAccount->balance + $playerTransefer->amount;
                $playerTransefer->before_frozen_balance     = $playerAccount->frozen;
                $playerTransefer->frozen_balance            = $playerAccount->frozen;
                $playerTransefer->before_agent_balance         = $playerAccount->agentbalance;
                $playerTransefer->agent_balance                = $playerAccount->agentbalance - $playerTransefer->amount;
                $playerTransefer->before_agent_frozen_balance  = $playerAccount->agentfrozen;
                $playerTransefer->agent_frozen_balance         = $playerAccount->agentfrozen;
                $playerTransefer->save();

                $playerAccount->balance      = $playerAccount->balance + $input['amount']*10000;
                $playerAccount->agentbalance = $playerAccount->agentbalance - $input['amount']*10000;
                $playerAccount->save();

                \DB::commit();
                return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($playerAccount->balance,10000,2),'agentbalance'=>bcdiv($playerAccount->agentbalance,10000,2)]);
            } catch (\Exception $e) {
                \DB::rollback(); 
                Clog::recordabnormal('钱包互转异常:'.$e->getMessage());   
                return $this->returnApiJson(config('language')[$this->language]['error351'].'changeBalance：'.$e->getMessage(), 0);
            }     
        }
    }

    public function withdrawalChannel()
    {
        //银行卡
        $carrierBankCardTypes = CarrierBankCardType::where('carrier_id',$this->carrier->id)->get();
        $playerBankCards      = PlayerBankCard::where('player_id',$this->user->player_id)->where('status',1)->get();
        $data                 = [];
        $bankCardTypesArr     = [];
        $bankCardIconArr      = [];
        $thirdWalletsTypesArr = [];
        $thirdWalletsIconArr  = [];
        $thirdWalletsIdsArr   = [];

        foreach ($carrierBankCardTypes as $key => $value) {
            $bankCardTypesArr[$value->id] = $value->bank_name;
            $bankCardIconArr[$value->id]  = $value->bank_background_url;
        }

        foreach ($playerBankCards as $key => $value) {
            $rows                    = [];
            $rows['bank_id']         = $value->id;
            $rows['bank_name']       = $bankCardTypesArr[$value->bank_Id];
            $rows['icon']            = $bankCardIconArr[$value->bank_Id];
            $rows['card_account']    = $value->card_account;
            $data[]                  = $rows;
        }

        //数字币
        $playerDigitalAddress = PlayerDigitalAddress::where('player_id',$this->user->player_id)->where('status',1)->get();
        $thirdWallets         = ThirdWallet::all();

        foreach ($thirdWallets as $key => $value) {
            $thirdWalletsTypesArr[$value->id] = $value->name;
            $thirdWalletsIdsArr[$value->id]   = $value->id;
        }

        foreach ($playerDigitalAddress as $key => $value) {
            $rows                       = [];
            $rows['type']               = $thirdWalletsIdsArr[$value->type];
            $rows['address_id']         = $value->id;
            $rows['thirdwallets_name']  = $thirdWalletsTypesArr[$value->type];
            $rows['icon']               = '';
            $rows['address']            = $value->address;
            $data[]                     = $rows;
        }

        //支付宝
        $playerAlipays = PlayerAlipay::where('player_id',$this->user->player_id)->where('status',1)->get();
        foreach ($playerAlipays as $key => $value) {
            $rows                       = [];
            $rows['alipay_id']          = $value->id;
            $rows['real_name']          = $value->real_name;
            $rows['account']            = $value->account;
            $data[]                     = $rows;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function promoteAndMakeMoney()
    {
        $input                 = request()->all();
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        $data                = null;
        switch ($playerDividendsMethod) {
            case 1:
                $data = DevidendMode1::promoteAndMakeMoney($input,$this->user);
                break;
            case 3:
                $data = DevidendMode3::promoteAndMakeMoney($input,$this->user);
                break;
            case 5:
                $data = DevidendMode5::promoteAndMakeMoney($input,$this->user);
                break;
            case 4:
                $data = DevidendMode4::promoteAndMakeMoney($input,$this->user);
                break;
            
            default:
                $data = DevidendMode2::promoteAndMakeMoney($input,$this->user);
                break;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function getCommission()
    {
        $returncommissKey ='returncommiss_'.date('Ymd');
        if(cache()->has($returncommissKey)){
            return $this->returnApiJson(config('language')[$this->language]['error353'], 0);
        }

        $playerCommission = PlayerCommission::select(\DB::raw('sum(amount) as amount'),\DB::raw('sum(team_casino_commission) as team_casino_commission'),\DB::raw('sum(team_electronic_commission) as team_electronic_commission'),\DB::raw('sum(team_esport_commission) as team_esport_commission'),\DB::raw('sum(team_card_commission) as team_card_commission'),\DB::raw('sum(team_sport_commission) as team_sport_commission'),\DB::raw('sum(team_lottery_commission) as team_lottery_commission'),\DB::raw('sum(team_fish_commission) as team_fish_commission'),\DB::raw('sum(directlyunder_casino_commission) as directlyunder_casino_commission'),\DB::raw('sum(directlyunder_electronic_commission) as directlyunder_electronic_commission'),\DB::raw('sum(directlyunder_esport_commission) as directlyunder_esport_commission'),\DB::raw('sum(directlyunder_card_commission) as directlyunder_card_commission'),\DB::raw('sum(directlyunder_sport_commission) as directlyunder_sport_commission'),\DB::raw('sum(directlyunder_lottery_commission) as directlyunder_lottery_commission'),\DB::raw('sum(directlyunder_fish_commission) as directlyunder_fish_commission'))->where('player_id',$this->user->player_id)->where('status',0)->first();

        $amount = 0;
        if($playerCommission){
            $amount = $playerCommission->amount;
        }

        if($amount==0){
            return $this->returnApiJson(config('language')[$this->language]['error354'], 0);
        }

        $cacheKey              = "player_" .$this->user->player_id;
        $redisLock              = Lock::addLock($cacheKey,60);
        if (!$redisLock) {
            $this->returnApiJson(config('language')[$this->language]['error20'],0);
        } else {
            try {
                \DB::beginTransaction();
                $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();

                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->user->prefix;
                $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                $playerTransfer->rid                             = $playerAccount->rid;
                $playerTransfer->top_id                          = $playerAccount->top_id;
                $playerTransfer->parent_id                       = $playerAccount->parent_id;
                $playerTransfer->player_id                       = $playerAccount->player_id;
                $playerTransfer->is_tester                       = $playerAccount->is_tester;
                $playerTransfer->level                           = $playerAccount->level;
                $playerTransfer->user_name                       = $playerAccount->user_name;
                $playerTransfer->mode                            = 1;
                $playerTransfer->type                            = 'commission_from_child';
                $playerTransfer->type_name                       = config('language')['zh']['text103'];
                $playerTransfer->en_type_name                    = config('language')['en']['text103'];
                $playerTransfer->day_m                           = date('Ym',time());
                $playerTransfer->day                             = date('Ymd',time());
                $playerTransfer->amount                          = $amount;
                $playerTransfer->before_balance                  = $playerAccount->balance;
                $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                $playerTransfer->frozen_balance                  = $playerAccount->frozen;

                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;

                $playerTransfer->save();

                $playerAccount->balance                          = $playerTransfer->balance;
                $playerAccount->save();


                //写入统计表
                $reportPlayerStatDay                         = ReportPlayerStatDay::where('carrier_id',$this->user->carrier_id)->where('player_id',$this->user->player_id)->where('day',date('Ymd'))->first();
                $reportPlayerStatDay->casino_commission      = $playerCommission->team_casino_commission + $playerCommission->directlyunder_casino_commission;
                $reportPlayerStatDay->electronic_commission  = $playerCommission->team_electronic_commission + $playerCommission->directlyunder_electronic_commission;
                $reportPlayerStatDay->esport_commission      = $playerCommission->team_esport_commission + $playerCommission->directlyunder_esport_commission;
                $reportPlayerStatDay->fish_commission        = $playerCommission->team_fish_commission + $playerCommission->directlyunder_fish_commission;
                $reportPlayerStatDay->card_commission        = $playerCommission->team_card_commission + $playerCommission->directlyunder_card_commission;
                $reportPlayerStatDay->sport_commission       = $playerCommission->team_sport_commission + $playerCommission->directlyunder_sport_commission;
                $reportPlayerStatDay->lottery_commission     = $playerCommission->team_lottery_commission + $playerCommission->directlyunder_lottery_commission;
                $reportPlayerStatDay->commission             = $playerCommission->amount;
                $reportPlayerStatDay->save();


                //更新团队统计
                $update['team_casino_commission']            = \DB::raw('team_casino_commission +'.$reportPlayerStatDay->casino_commission);
                $update['team_electronic_commission']        = \DB::raw('team_electronic_commission +'.$reportPlayerStatDay->electronic_commission);
                $update['team_esport_commission']            = \DB::raw('team_esport_commission +'.$reportPlayerStatDay->esport_commission);
                $update['team_fish_commission']              = \DB::raw('team_fish_commission +'.$reportPlayerStatDay->fish_commission);
                $update['team_card_commission']              = \DB::raw('team_card_commission +'.$reportPlayerStatDay->card_commission);
                $update['team_sport_commission']             = \DB::raw('team_sport_commission +'.$reportPlayerStatDay->sport_commission);
                $update['team_lottery_commission']           = \DB::raw('team_lottery_commission +'.$reportPlayerStatDay->lottery_commission);
                $update['team_commission']                   = \DB::raw('team_commission +'.$reportPlayerStatDay->commission);

                $rids                                        = explode('|',$this->user->rid);

                ReportPlayerStatDay::whereIn('rid',$rids)->update($update);
                PlayerCommission::where('player_id',$this->user->player_id)->where('status',0)->update(['send_time'=>time(),'status'=>1]);

                //添加流水限制
                $playerWithdrawFlowLimit               = new PlayerWithdrawFlowLimit();
                $playerWithdrawFlowLimit->carrier_id   = $reportPlayerStatDay->carrier_id;
                $playerWithdrawFlowLimit->top_id       = $reportPlayerStatDay->top_id;
                $playerWithdrawFlowLimit->parent_id    = $reportPlayerStatDay->parent_id;
                $playerWithdrawFlowLimit->rid          = $reportPlayerStatDay->rid;
                $playerWithdrawFlowLimit->player_id    = $reportPlayerStatDay->player_id;
                $playerWithdrawFlowLimit->user_name    = $reportPlayerStatDay->user_name;
                $playerWithdrawFlowLimit->limit_amount = $playerTransfer->amount;
                $playerWithdrawFlowLimit->limit_type   = 49;
                $playerWithdrawFlowLimit->operator_id  = 0;
                $playerWithdrawFlowLimit->save();

                \DB::commit();
                Lock::release($redisLock);
                return $this->returnApiJson(config('language')[$this->language]['error355'], 1,['balance'=>bcdiv($playerAccount->balance,10000,2)]);
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal(config('language')[$this->language]['error356'].$e->getMessage());   
                return $this->returnApiJson($e->getMessage(), 0);
            }
        }
    }

    public function performanceinQuiry()
    {
        $input                 = request()->all();
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        $data                = null;
        switch ($playerDividendsMethod) {
            case 1:
                $data = DevidendMode1::performanceinQuiry($input,$this->user);
                break;
            case 3:
                $data = DevidendMode3::performanceinQuiry($input,$this->user);
                break;
            case 5:
                $data = DevidendMode5::performanceinQuiry($input,$this->user);
                break;
            case 4:
                $data = DevidendMode4::performanceinQuiry($input,$this->user);
                break;
            
            default:
                // code...
                break;
        }

        if(is_array($data)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
        } else{
            return $this->returnApiJson($data, 0);
        } 
    }

    public function newMyDirectlyunder()
    {
        $input                 = request()->all();
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        $data                = null;
        switch ($playerDividendsMethod) {
            case 1:
                $data = DevidendMode1::newMyDirectlyunder($input,$this->user);
                break;
            case 3:
                $data = DevidendMode3::newMyDirectlyunder($input,$this->user);
                break;
            case 5:
                $data = DevidendMode5::newMyDirectlyunder($input,$this->user);
                break;
            case 4:
                $data = DevidendMode4::newMyDirectlyunder($input,$this->user);
                break;
            
            default:
                // code...
                break;
        }
       
        if(is_array($data)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
        } else{
            return $this->returnApiJson($data, 0);
        } 
    }

    public function myDirectlyunder()
    {   
        $input                 = request()->all();
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        $data                = null;
        switch ($playerDividendsMethod) {
            case 1:
                $data = DevidendMode1::myDirectlyunder($input,$this->user);
                break;
            case 3:
                $data = DevidendMode3::myDirectlyunder($input,$this->user);
                break;
            case 5:
                $data = DevidendMode5::myDirectlyunder($input,$this->user);
                break;
            case 4:
                $data = DevidendMode4::myDirectlyunder($input,$this->user);
                break;
            
            default:
                // code...
                break;
        }
       
        if(is_array($data)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
        } else{
            return $this->returnApiJson($data, 0);
        } 
    }

    public function myTeam()
    {
        $input                 = request()->all();
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        $data                  = null;
        switch ($playerDividendsMethod) {
            case 1:
                $data = DevidendMode1::myTeam($input,$this->user);
                break;
            case 3:
                $data = DevidendMode3::myTeam($input,$this->user);
                break;
            case 5:
                $data = DevidendMode5::myTeam($input,$this->user);
                break;
            case 4:
                $data = DevidendMode4::myTeam($input,$this->user);
                break;
            
            default:
                // code...
                break;
        }
       
        if(is_array($data)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
        } else{
            return $this->returnApiJson($data, 0);
        } 
    }

    public function setGuaranteed()
    {
        $input = request()->all();
        $guaranteedLevelDifference = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'guaranteed_level_difference',$this->user->prefix);
        $limitHighestGuaranteed    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'limit_highest_guaranteed',$this->user->prefix);

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson(config('language')[$this->language]['error357'], 0);
        }

        if(!isset($input['guaranteed']) || !is_numeric($input['guaranteed']) || $input['guaranteed']<0 ){
            return $this->returnApiJson(config('language')[$this->language]['error358'], 0);
        }

        $selfPlayerSetting = PlayerCache::getPlayerSetting($this->user->player_id);

        if($input['guaranteed'] >= $limitHighestGuaranteed){
            return $this->returnApiJson(config('language')[$this->language]['error359'], 0);
        }

        if($selfPlayerSetting->guaranteed < $input['guaranteed']){
            return $this->returnApiJson(config('language')[$this->language]['error360'], 0);
        }

        if($selfPlayerSetting->guaranteed < $input['guaranteed'] + $guaranteedLevelDifference){
            if($selfPlayerSetting->guaranteed == $input['guaranteed']){
                return $this->returnApiJson(config('language')[$this->language]['error361'], 0);
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error362'].$guaranteedLevelDifference, 0);
            }
        }
        if(strlen($input['player_id'])== 8){
            $playerSetting  = PlayerCache::getPlayerSetting($input['player_id']);
        } else{
            $player = Player::where('prefix',$this->user->prefix)->where('extend_id',$input['player_id'])->first();
            if(!$player){
                return $this->returnApiJson(config('language')[$this->language]['error110'], 0);
            }

            $playerSetting  = PlayerCache::getPlayerSetting($player->player_id);
        }
        
        if($playerSetting && $playerSetting->parent_id == $this->user->player_id){
            if($input['guaranteed']<$playerSetting->guaranteed){
                return $this->returnApiJson(config('language')[$this->language]['error363'], 0);
            }

            $playerSetting->guaranteed = $input['guaranteed'];
            $playerSetting->save();

            PlayerCache::forgetPlayerSetting($input['player_id']);

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        }

        return $this->returnApiJson(config('language')[$this->language]['error110'], 0);
    }

    public function setEarning()
    {
        $input = request()->all();

        $dividendLevelDifference = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'dividend_level_difference',$this->user->prefix);
        $limitHighestDividend    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'limit_highest_dividend',$this->user->prefix);
        $dividendEnumerate       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'dividend_enumerate',$this->user->prefix);

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson(config('language')[$this->language]['error357'], 0);
        }

        if(!isset($input['earnings']) || !is_numeric($input['earnings']) || $input['earnings'] <= 0 ){
            return $this->returnApiJson(config('language')[$this->language]['error364'], 0);
        }

        if(!empty($dividendEnumerate)){
            $dividendEnumerateArr = explode(',', $dividendEnumerate);
            if(!in_array($input['earnings'],$dividendEnumerateArr)){
                return $this->returnApiJson(config('language')[$this->language]['error365'], 0);
            }
        }

        $selfPlayerSetting = PlayerSetting::where('player_id',$this->user->player_id)->first();

        if($input['earnings'] >= $limitHighestDividend){
            return $this->returnApiJson(config('language')[$this->language]['error366'], 0);
        }

        if($selfPlayerSetting->earnings < $input['earnings']){
            return $this->returnApiJson(config('language')[$this->language]['error367'], 0);
        }

        if($selfPlayerSetting->earnings < $input['earnings'] + $dividendLevelDifference){
            if($selfPlayerSetting->earnings == $input['earnings']){
                return $this->returnApiJson(config('language')[$this->language]['error368'], 0);
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error369'].$dividendLevelDifference, 0);
            }
        }

        if(strlen($input['player_id'])==8){
            $playerSetting     = PlayerCache::getPlayerSetting($input['player_id']);
        } else{
            $setPlayer = Player::where('prefix',$this->user->prefix)->where('extend_id',$input['player_id'])->first();
            if(!$setPlayer){
                return $this->returnApiJson(config('language')[$this->language]['error110'], 0);
            }

            $playerSetting      = PlayerCache::getPlayerSetting($setPlayer->player_id);
            $input['player_id'] =  $setPlayer->player_id;
        }

        if(!$playerSetting){
            return $this->returnApiJson(config('language')[$this->language]['error110'], 0);
        }

        if($playerSetting->parent_id == $this->user->player_id){
            if($playerSetting->earnings >0){
                return $this->returnApiJson(config('language')[$this->language]['error370'], 0);
            }

            $playerSetting->earnings = $input['earnings'];
            $playerSetting->save();

            PlayerCache::forgetPlayerSetting($input['player_id']);

            PlayerDigitalAddress::where('player_id',$input['player_id'])->update(['win_lose_agent'=>1]);
            ReportPlayerStatDay::where('player_id',$input['player_id'])->update(['win_lose_agent'=>1]);
            Player::where('player_id',$input['player_id'])->update(['win_lose_agent'=>1]);
            PlayerBetFlowMiddle::where('player_id',$input['player_id'])->update(['win_lose_agent'=>1]);
            PlayerDepositPayLog::where('player_id',$input['player_id'])->update(['is_agent'=>1]);

            PlayerCache::forgetisWinLoseAgent($input['player_id']);

            return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
        } else{
            return $this->returnApiJson(config('language')[$this->language]['error371'], 0);
        }
    }

    public function agencyJoinTventure()
    {
        $input                             = request()->all();
        $playerRealtimeDividendsStartDay   = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_realtime_dividends_start_day',$this->user->prefix);
        $playerDividendsMethod             = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        $selfPlayerSetting                 = PlayerCache::getPlayerSetting($this->user->player_id);
        $data                              = [];

        if(!isset($input['type']) || !in_array($input['type'],[0,1,2,3])){
            return $this->returnApiJson(config('language')[$this->language]['error21'], 0);
        }

        $data['earnings']   = $selfPlayerSetting->earnings ;
        $data['guaranteed'] = $selfPlayerSetting->guaranteed ;

        if($input['type']==0){
            switch ($playerDividendsMethod) {
            case 1:
                $calculateArr = DevidendMode1::calculateDividend($this->user,null,null,1);
                break;
            case 2:
                $calculateArr = DevidendMode2::calculateDividend($this->user);
                break;
            case 3:
                $calculateArr = DevidendMode3::calculateDividend($this->user);
                break;
            case 5:
                $calculateArr = DevidendMode5::calculateDividend($this->user);
                break;
            case 4:
                $calculateArr = DevidendMode4::calculateDividend($this->user);
                break;
            
            default:
                // code...
                break;
            }
            
            $data['directlyunderRecharge'] = $calculateArr['directlyunderRecharge'] ;
            $data['directlyunderWithdraw'] = $calculateArr['directlyunderWithdraw'] ;
            $data['teamRecharge']          = $calculateArr['teamRecharge'] ;
            $data['teamWithdraw']          = $calculateArr['teamWithdraw'] ;
            $data['teamDiff']              = $data['teamRecharge'] - $data['teamWithdraw'];
            $data['directlyunderDiff']     = $data['directlyunderRecharge'] - $data['directlyunderWithdraw'];
            $data['totalCommission']       = $calculateArr['totalCommission'];
            $data['teamDividend']          = isset($calculateArr['teamDividend'])?$calculateArr['teamDividend']:0;
            $data['directlyunderDividend'] = isset($calculateArr['directlyunderDividend'])?$calculateArr['directlyunderDividend']:0;
            
            if(isset($calculateArr['teamStock'])){
                $data['teamStock'] = $calculateArr['teamStock'];
            } elseif(isset($calculateArr['team_stock'])){
                $data['teamStock'] = $calculateArr['team_stock'];
            } else{
                $data['teamStock'] = 0;
            }

            if(isset($calculateArr['directlyunderStock'])){
                $data['directlyunderStock'] = $calculateArr['directlyunderStock'];
            } elseif(isset($calculateArr['directlyunder_stock'])){
                $data['directlyunderStock'] = $calculateArr['directlyunder_stock'];
            } else{
                $data['directlyunderStock'] = 0;
            }
    
            $data['lastaccumulation']      = isset($calculateArr['lastaccumulation']) ? $calculateArr['lastaccumulation'] : 0;
            $data['lastAccumulation']      = isset($data['lastaccumulation'])?$data['lastaccumulation']:0;

            $team_stock_change            = 0;
            if(isset($calculateArr['team_stock_change'])){
                $team_stock_change = $calculateArr['team_stock_change'];
            }

            if(isset($calculateArr['teamStockChange'])){
                $team_stock_change = $calculateArr['teamStockChange'];
            }

            $data['team_stock_change']          = $team_stock_change;
            $data['teamStockChange']            = $data['team_stock_change'] ;

            $directlyunder_stock_change            = 0;
            if(isset($calculateArr['directlyunder_stock_change'])){
                $directlyunder_stock_change = $calculateArr['directlyunder_stock_change'];
            }

            if(isset($calculateArr['directlyunderStockChange'])){
                $directlyunder_stock_change = $calculateArr['directlyunderStockChange'];
            }

            $data['directlyunder_stock_change'] = $directlyunder_stock_change;
            $data['directlyunderStockChange']   = $data['directlyunder_stock_change'];
            $data['allCommission']              = isset($calculateArr['allCommission']) ? $calculateArr['allCommission']:0;
            $data['selfRecharge']               = isset($calculateArr['selfRecharge']) ? $calculateArr['selfRecharge']:0;
            $data['selfWithdraw']               = isset($calculateArr['selfWithdraw']) ? $calculateArr['selfWithdraw']:0;
            $data['selfStockChange']            = isset($calculateArr['selfStockChange']) ? $calculateArr['selfStockChange']:0;
            $data['earnings']                   = $selfPlayerSetting->earnings;

            //获取流水佣金
            $venueFeeAmount                = PlayerCommission::where('player_id',$this->user->player_id)->where('day','>=',date('Ymd',strtotime($playerRealtimeDividendsStartDay)))->sum('amount');

            //计算实时流水佣金
            $realVenueFeeAmount            = PlayerRealCommission::where('player_id',$this->user->player_id)->where('day',date('Ymd'))->sum('amount');

            $data['venue_fee']             = is_null($venueFeeAmount) ? 0:$venueFeeAmount;
            if(!is_null($realVenueFeeAmount)){
                $data['venue_fee'] += $realVenueFeeAmount;
            }
            $data['venueFee']              = $data['venue_fee'];

            //模式8参数处理
            $data['directlyunderDiscountFee']      = isset($calculateArr['directlyunderDiscountFee']) ? $calculateArr['directlyunderDiscountFee']:0;
            $data['directlyunderVenuesFee']        = isset($calculateArr['directlyunderVenuesFee']) ? $calculateArr['directlyunderVenuesFee']:0;
            $data['teamDiscountFee']               = isset($calculateArr['teamDiscountFee']) ? $calculateArr['teamDiscountFee']:0;
            $data['teamVenuesFee']                 = isset($calculateArr['teamVenuesFee']) ? $calculateArr['teamVenuesFee']:0;
            $data['directWinloss']                 = isset($calculateArr['direct_winloss'])?$calculateArr['direct_winloss']:0;
            $data['teamWinloss']                   = isset($calculateArr['team_winloss'])?$calculateArr['team_winloss']:0;
            $data['teamWinloss']                   = isset($calculateArr['team_winloss'])?$calculateArr['team_winloss']:0;
            $data['team_revenue']                  = isset($calculateArr['team_revenue'])?$calculateArr['team_revenue']:0;
            $data['revenue']                       = isset($calculateArr['revenue'])?$calculateArr['revenue']:0;

            $reportPlayerEarnings                  = ReportPlayerEarnings::where('player_id',$this->user->player_id)->orderBy('id','desc')->first();
            if($reportPlayerEarnings){
                $data['availableCommission']       = $reportPlayerEarnings->accumulation + $data['totalCommission'] >0 ? $reportPlayerEarnings->accumulation + $data['totalCommission'] :0;
            } else{
                $data['availableCommission']       = $data['totalCommission'] >0 ?  $data['totalCommission'] :0;
            }

            $data['tongbao_dividends']  = 0;
            $playerRealDividendTongbao = ReportRealPlayerEarnings::where('player_id',$this->user->player_id)->sum('tongbao_dividends');
            if(!is_null($playerRealDividendTongbao)){
                $data['tongbao_dividends']       = $playerRealDividendTongbao;
            }

            $data['totalCommission'] += $data['tongbao_dividends'];

            //获取本周返佣
            $startOneTime = strtotime('this week Monday');
            $monday       = date('Y-m-d',$startOneTime);
            $sunday       = date('Y-m-d',$startOneTime+518400);

            $oneweekCommission         = PlayerCommission::where('player_id',$this->user->player_id)->where('day','>=',$monday)->where('day','<=',$sunday)->sum('amount');
            $data['oneweekCommission'] = $oneweekCommission ? $oneweekCommission:0;

            //获取累积分红
            $accumulationDividend         = ReportPlayerEarnings::where('player_id',$this->user->player_id)->where('amount','>=',0)->sum('amount');
            $data['accumulationDividend'] = $accumulationDividend ? $accumulationDividend:0;

            return returnApiJson('操作成功', 1,$data);
        }

       $reportPlayerEarnings = ReportPlayerEarnings::where('player_id',$this->user->player_id)->orderBy('id','desc')->limit(3)->get()->toArray();

       if($input['type']==1){
            if(!count($reportPlayerEarnings)){
              $data['directlyunderDiff']     = 0;
              $data['directlyunderRecharge'] = 0;
              $data['directlyunderWithdraw'] = 0;
              $data['directlyunderDividend'] = 0;
              $data['teamDiff']              = 0;
              $data['teamRecharge']          = 0;
              $data['teamWithdraw']          = 0;
              $data['totalCommission']       = 0;
              $data['availableCommission']   = 0;
              $data['venue_fee']             = 0;
              $data['venueFee']              = 0;
              $data['directlyunder_stock_change'] = 0;
              $data['team_stock_change']          = 0;
              $data['directlyunderDividend']      = 0;
              $data['teamDividend']               = 0;
              $data['teamStockChange']            = 0 ;
              $data['directlyunderStockChange']   = 0;
              $data['directlyunderVenuesFee']     = 0;
              $data['teamVenuesFee']              = 0;
              $data['teamStock']                  = 0 ;
              $data['directlyunderStock']         = 0;
              $data['lastaccumulation']           = 0;
              $data['lastAccumulation']           = 0;
              $data['tongbao_dividends']          = 0;
              $data['directWinloss']              = 0;
              $data['teamWinloss']                = 0;
              $data['directWinloss']              = 0;
              $data['teamWinloss']                = 0;
            } else{
              $temp                          = $reportPlayerEarnings[0];
              $data['directlyunderDiff']     = $temp['directlyunder_recharge_amount']  - $temp['directlyunder_withdraw_amount'];
              $data['directlyunderRecharge'] = $temp['directlyunder_recharge_amount'] ;
              $data['directlyunderWithdraw'] = $temp['directlyunder_withdraw_amount'] ;
              $data['teamDiff']              = $temp['team_recharge_amount'] - $temp['team_withdraw_amount'];
              $data['teamRecharge']          = $temp['team_recharge_amount'] ;
              $data['teamWithdraw']          = $temp['team_withdraw_amount'] ;
              $data['totalCommission']       = $temp['real_amount'];
              $data['availableCommission']   = $temp['real_amount'];
              $data['venue_fee']             = $temp['venue_fee'];
              $data['venueFee']              = $data['venue_fee'];
              $data['directlyunder_stock_change'] = $temp['directlyunder_stock_change'];
              $data['team_stock_change']          = $temp['team_stock_change'];
              $data['directlyunderDividend']      = $temp['team_commission'];
              $data['teamDividend']               = $temp['direct_commission'];
              $data['teamStockChange']            = $data['team_stock_change'] ;
              $data['directlyunderStockChange']   = $data['directlyunder_stock_change'];
              $data['directlyunderVenuesFee']     = $temp['directlyunderVenuesFee'];
              $data['teamVenuesFee']              = $temp['teamVenuesFee'];
              $data['teamStock']                  = $temp['team_stock'] ;
              $data['directlyunderStock']         = $temp['directlyunder_stock'];
              $data['lastaccumulation']           = $temp['lastaccumulation'];
              $data['lastAccumulation']           = $temp['lastaccumulation'];
              $data['tongbao_dividends']          = $temp['tongbao_dividends'];
              $data['directWinloss']              = $temp['direct_winloss'];
              $data['teamWinloss']                = $temp['team_winloss'];
            }
       }

       if($input['type']==2){
            if(count($reportPlayerEarnings) < 2){
              $data['directlyunderDiff']     = 0;
              $data['directlyunderRecharge'] = 0;
              $data['directlyunderWithdraw'] = 0;
              $data['teamDiff']              = 0;
              $data['teamRecharge']          = 0;
              $data['teamWithdraw']          = 0;
              $data['totalCommission']       = 0;
              $data['availableCommission']   = 0;
              $data['venue_fee']             = 0;
              $data['venueFee']              = 0;
              $data['directlyunder_stock_change'] = 0;
              $data['team_stock_change']          = 0;
              $data['directlyunderDividend']      = 0;
              $data['teamDividend']               = 0;
              $data['teamStockChange']            = 0 ;
              $data['directlyunderStockChange']   = 0;
              $data['directlyunderVenuesFee']     = 0;
              $data['teamVenuesFee']              = 0;
              $data['teamStock']                  = 0 ;
              $data['directlyunderStock']         = 0;
              $data['lastaccumulation']           = 0;
              $data['lastAccumulation']           = 0;
              $data['tongbao_dividends']          = 0;
              $data['directWinloss']              = 0;
              $data['teamWinloss']                = 0;
           } else{
              $temp                          = $reportPlayerEarnings[1];
              $data['directlyunderDiff']     = $temp['directlyunder_recharge_amount']  - $temp['directlyunder_withdraw_amount'] ;
              $data['directlyunderRecharge'] = $temp['directlyunder_recharge_amount'] ;
              $data['directlyunderWithdraw'] = $temp['directlyunder_withdraw_amount'] ;
              $data['teamDiff']              = $temp['team_recharge_amount'] - $temp['team_withdraw_amount'] ;
              $data['teamRecharge']          = $temp['team_recharge_amount'] ;
              $data['teamWithdraw']          = $temp['team_withdraw_amount'] ;
              $data['totalCommission']       = $temp['real_amount'] ;
              $data['availableCommission']   = $temp['real_amount'];
              $data['venue_fee']             = $temp['venue_fee'];
              $data['venueFee']              = $data['venue_fee'];
              $data['directlyunder_stock_change'] = $temp['directlyunder_stock_change'];
              $data['team_stock_change']          = $temp['team_stock_change'];
              $data['directlyunderDividend']      = $temp['team_commission'];
              $data['teamDividend']               = $temp['direct_commission'];
              $data['teamStockChange']            = $data['team_stock_change'] ;
              $data['directlyunderStockChange']   = $data['directlyunder_stock_change'];
              $data['directlyunderVenuesFee']     = $temp['directlyunderVenuesFee'];
              $data['teamVenuesFee']              = $temp['teamVenuesFee'];
              $data['teamStock']                  = $temp['team_stock'] ;
              $data['directlyunderStock']         = $temp['directlyunder_stock'];
              $data['lastaccumulation']           = $temp['lastaccumulation'];
              $data['lastAccumulation']           = $temp['lastaccumulation'];
              $data['tongbao_dividends']          = $temp['tongbao_dividends'];
              $data['directWinloss']              = $temp['direct_winloss'];
              $data['teamWinloss']                = $temp['team_winloss'];
           }
       }

       if($input['type']==3){
            if(count($reportPlayerEarnings) < 3){
              $data['directlyunderDiff']     = 0;
              $data['directlyunderRecharge'] = 0;
              $data['directlyunderWithdraw'] = 0;
              $data['teamDiff']              = 0;
              $data['teamRecharge']          = 0;
              $data['teamWithdraw']          = 0;
              $data['totalCommission']       = 0;
              $data['availableCommission']   = 0;
              $data['venue_fee']             = 0;
              $data['venueFee']              = 0;
              $data['directlyunder_stock_change'] = 0;
              $data['team_stock_change']          = 0;
              $data['directlyunderDividend']      = 0;
              $data['teamDividend']               = 0;
              $data['teamStockChange']            = 0 ;
              $data['directlyunderStockChange']   = 0;
              $data['directlyunderVenuesFee']     = 0;
              $data['teamVenuesFee']              = 0;
              $data['teamStock']                  = 0 ;
              $data['directlyunderStock']         = 0;
              $data['lastaccumulation']           = 0;
              $data['lastAccumulation']           = 0;
              $data['tongbao_dividends']          = 0;
              $data['directWinloss']              = 0;
              $data['teamWinloss']                = 0;
           } else{
              $temp                          = $reportPlayerEarnings[2];
              $data['directlyunderDiff']     = $temp['directlyunder_recharge_amount'] - $temp['directlyunder_withdraw_amount'];
              $data['directlyunderRecharge'] = $temp['directlyunder_recharge_amount'] ;
              $data['directlyunderWithdraw'] = $temp['directlyunder_withdraw_amount'] ;
              $data['teamDiff']              = $temp['team_recharge_amount'] - $temp['team_withdraw_amount'] ;
              $data['teamRecharge']          = $temp['team_recharge_amount'] ;
              $data['teamWithdraw']          = $temp['team_withdraw_amount'] ;
              $data['totalCommission']       = $temp['real_amount'] ;
              $data['availableCommission']   = $temp['real_amount'];
              $data['venue_fee']             = $temp['venue_fee'];
              $data['venueFee']              = $data['venue_fee'];
              $data['directlyunder_stock_change'] = $temp['directlyunder_stock_change'];
              $data['team_stock_change']          = $temp['team_stock_change'];
              $data['directlyunderDividend']      = $temp['team_commission'];
              $data['teamDividend']               = $temp['direct_commission'];
              $data['teamStockChange']            = $data['team_stock_change'] ;
              $data['directlyunderStockChange']   = $data['directlyunder_stock_change'];
              $data['directlyunderVenuesFee']     = $temp['directlyunderVenuesFee'];
              $data['teamVenuesFee']              = $temp['teamVenuesFee'];
              $data['teamStock']                  = $temp['team_stock'] ;
              $data['directlyunderStock']         = $temp['directlyunder_stock'];
              $data['lastaccumulation']           = $temp['lastaccumulation'];
              $data['lastAccumulation']           = $temp['lastaccumulation'];
              $data['tongbao_dividends']          = $temp['tongbao_dividends'];
              $data['directWinloss']              = $temp['direct_winloss'];
              $data['teamWinloss']                = $temp['team_winloss'];
           }
       }
        
        return returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function commissionLog()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $query           = PlayerTransfer::select('created_at','amount')->where('player_id',$this->user->player_id)->where('type','commission_from_child')->orderBy('id','desc');

        $total          = $query->count();
        $items          = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->amount = bcdiv($value->amount,10000,2);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['item' => $items, 'total' => $total ,'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function personExpand()
    {
        $data                 = [];
        $playerSetting        = PlayerCache::getPlayerSetting($this->user->player_id);
        $data['guaranteed']   = $playerSetting->guaranteed;
        $week                 = getWeekStartEnd();
        $startDate            = $week[2];
        $endDate              = $week[3];

        $playerBetFlowMiddle = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'))->where('rid','like',$this->user->rid.'|%')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if(is_null($playerBetFlowMiddle->process_available_bet_amount)){
            $data['performance']   = 0;
        } else{
            $data['performance']   = bcdiv($playerBetFlowMiddle->process_available_bet_amount,1,2);
        }

        $rechargePlayerTransfer = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('type','recharge')->where('rid','like',$this->user->rid.'|%')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if(is_null($rechargePlayerTransfer->amount)){
            $data['rechargeAmount']   = 0;
        } else{
            $data['rechargeAmount']   = bcdiv($rechargePlayerTransfer->amount,10000,2);
        }

        $withdrawPlayerTransfer = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('type','withdraw_finish')->where('rid','like',$this->user->rid.'|%')->where('day','>=',$startDate)->where('day','<=',$endDate)->first();

        if(is_null($withdrawPlayerTransfer->amount)){
            $data['withdrawAmount']  = 0;
        } else{
            $data['withdrawAmount']  = bcdiv($rechargePlayerTransfer->amount,10000,2);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function rankHistoryList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $data           = [];

        //$query          = PlayerTransfer::select('created_at','amount')->where('type','rank_list_gift')->where('player_id',$this->user->player_id)->orderBy('id','asc');
        $query          = PlayerTransfer::select('created_at','amount')->where('type','rank_list_gift')->orderBy('id','asc');
        $total          = $query->count();
        $data['rows']   = $query->skip($offset)->take($pageSize)->get();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['item' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function rankList()
    {
        $input       = request()->all();

        //type=1 今天  type=-1 昨天
        if(!isset($input['type']) || !in_array($input['type'],[1,-1])){
            return $this->returnApiJson(config('language')[$this->language]['error247'], 0);
        }

        $performance = 0;
        $startDay    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_realtime_dividends_start_day',$this->prefix);
        if($input['type']==1){
            $rankingList = RankingList::select('content')->where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->where('day',date('Ymd',strtotime($startDay)))->first();
            $performance = PlayerBetFlowMiddle::where('day',date('Ymd'))->where('player_id',$this->user->player_id)->sum('process_available_bet_amount');

        } else{
            $rankingList = RankingList::select('content')->where('carrier_id',$this->carrier->id)->where('prefix',$this->prefix)->where('day','!=',date('Ymd',strtotime($startDay)))->orderBy('created_at','desc')->first();
            $performance = PlayerBetFlowMiddle::where('day',date('Ymd',strtotime('-1 day')))->where('player_id',$this->user->player_id)->sum('process_available_bet_amount');
        }
        
        $ranking     = 0;
        $bonus       = 0;
        $content     = [];

        if($rankingList && $this->user){
            
            $rankingListArr = json_decode($rankingList->content,true);
            foreach($rankingListArr as $key => $value){
                $row = [];
                //分割字符串
                if($this->user->user_name == $value['user_name']){
                    $ranking  = $value['ranking'];
                    $bonus    = $value['bonus'];
                }
                $currUserNameArr = explode('_',$value['user_name']);
                $value['user_name'] = $currUserNameArr[0];
                $content[] = $value;
            }

            $rankingList->content = $content;
        }

        $data['rankingList']     = count($content) ? $content:[];
        $data['ranking']         = $ranking;
        $data['performance']     = $performance;
        $data['bonus']           = $bonus;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function performanceBriefing()
    {
        $input                 = request()->all();
        $weekArr               = getWeekStartEnd();
        $startDay              = $weekArr[2];
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        $operatingExpenses     = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'operating_expenses',$this->user->prefix);
        $operatingExpenses     = bcdiv(100-$operatingExpenses,100,2);

        $rechargeplayerTransfer = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('type','recharge')->where('rid','like',$this->user->rid.'|%')->where('day','>=',$startDay)->first();
        $withdrawplayerTransfer = PlayerTransfer::select(\DB::raw('sum(amount) as amount'))->where('type','withdraw_finish')->where('rid','like',$this->user->rid.'|%')->where('day','>=',$startDay)->first();
        $playerBetFlowMiddle    = PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'))->where('rid','like',$this->user->rid.'|%')->where('day','>=',$startDay)->first();
        $playerSetting          = PlayerCache::getPlayerSetting($this->user->player_id);
        $data                   = [];
        $data['rechargeAmount'] = 0;
        $data['withdrawAmount'] = 0;
        $data['performance']    = 0;

        if($playerDividendsMethod==2){
            $rechargeplayerTransfer1 = PlayerTransfer::select(\DB::raw('sum(remark1) as amount'))->where('type','recharge')->where('rid','like',$this->user->rid.'|%')->where('day','>=',$startDay)->first();

            if($rechargeplayerTransfer1 && !is_null($rechargeplayerTransfer1->amount)){
                $data['rechargeAmount'] += $rechargeplayerTransfer1->amount*$operatingExpenses;
            }
        }else{
            if($rechargeplayerTransfer && !is_null($rechargeplayerTransfer->amount)){
                $data['rechargeAmount'] = $rechargeplayerTransfer->amount;
            } 
        }
        

        if($withdrawplayerTransfer && !is_null($withdrawplayerTransfer->amount)){
            $data['withdrawAmount'] = $withdrawplayerTransfer->amount;
        }

        if($playerBetFlowMiddle && !is_null($playerBetFlowMiddle->process_available_bet_amount)){
            $data['performance'] = $playerBetFlowMiddle->process_available_bet_amount;
        }

        $data['guaranteed']  = $playerSetting->guaranteed;
        $data['rechargeAmount'] = bcdiv($data['rechargeAmount'],10000,2);
        $data['withdrawAmount'] = bcdiv($data['withdrawAmount'],10000,2);
        $data['performance']    = bcdiv($data['performance'],1,2);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);
    }

    public function enableBindThirdwalletList()
    {
        $enableThirdWallet    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'third_wallet',$this->prefix);
        $enableThirdWallet    = json_decode($enableThirdWallet,true);
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $enableThirdWallet);
    }

    public function newPerformanceinQuiry()
    {
        $input                 = request()->all();
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);

        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return $this->returnApiJson('对不起，开始日期取值不正常',0);
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson('对不起，结束日期取值不正常',0);
        }

        $totalcasinocommission     = 0;
        $totalelectroniccommission = 0;
        $totalesportcommission     = 0;
        $totalfishcommission       = 0;
        $totalcardcommission       = 0;
        $totalsportcommission      = 0;
        $totallotterycommission    = 0;
        $totalcommission           = 0;
        $startDate                 = date('Ymd',strtotime($input['startDate']));
        $endDate                   = date('Ymd',strtotime($input['endDate']));

        $reportPlayerStatDays     =  ReportPlayerStatDay::select('day')->where('day','>=',$startDate)->where('day','<=',$endDate)->groupBy('day')->orderBy('day','desc')->get();
        
        foreach ($reportPlayerStatDays as $key1 => &$value1) {
            $playerCommission =  PlayerCommission::where('player_id',$this->user->player_id)->where('day',$value1->day)->orderBy('day','desc')->first();
            if($playerCommission){
                $value1->casino_commission     = $playerCommission->team_casino_commission + $playerCommission->directlyunder_casino_commission + $playerCommission->self_casino_commission;
                $value1->electronic_commission = $playerCommission->team_electronic_commission + $playerCommission->directlyunder_electronic_commission + $playerCommission->self_electronic_commission;
                $value1->esport_commission     = $playerCommission->team_esport_commission + $playerCommission->directlyunder_esport_commission + $playerCommission->self_esport_commission;
                $value1->fish_commission       = $playerCommission->team_fish_commission + $playerCommission->directlyunder_fish_commission + $playerCommission->self_fish_commission;
                $value1->card_commission       = $playerCommission->team_card_commission + $playerCommission->directlyunder_card_commission + $playerCommission->self_card_commission;
                $value1->sport_commission      = $playerCommission->team_sport_commission + $playerCommission->directlyunder_sport_commission + $playerCommission->self_sport_commission;
                $value1->lottery_commission    = $playerCommission->team_lottery_commission + $playerCommission->directlyunder_lottery_commission + $playerCommission->self_lottery_commission;
                $value1->commission            = $value1->casino_commission + $value1->electronic_commission + $value1->esport_commission + $value1->fish_commission + $value1->card_commission + $value1->sport_commission + $value1->lottery_commission;
            } else{
                $value1->casino_commission     = 0;
                $value1->electronic_commission = 0;
                $value1->esport_commission     = 0;
                $value1->fish_commission       = 0;
                $value1->card_commission       = 0;
                $value1->sport_commission      = 0;
                $value1->lottery_commission    = 0;
                $value1->commission            = 0;
            }

            $totalcasinocommission     += $value1->casino_commission;
            $totalelectroniccommission += $value1->electronic_commission;
            $totalesportcommission     += $value1->esport_commission;
            $totalfishcommission       += $value1->fish_commission;
            $totalcardcommission       += $value1->card_commission;
            $totalsportcommission      += $value1->sport_commission;
            $totallotterycommission    += $value1->lottery_commission;
            $totalcommission           += $value1->commission;
            $value1->day               = date('Y-m-d',strtotime($value1->day));
        }
        $data                              = [];
        
        $data['totalcasinocommission']     = $totalcasinocommission;
        $data['totalelectroniccommission'] = $totalelectroniccommission;
        $data['totalesportcommission']     = $totalesportcommission;
        $data['totalfishcommission']       = $totalfishcommission;
        $data['totalcardcommission']       = $totalcardcommission;
        $data['totalsportcommission']      = $totalsportcommission;
        $data['totallotterycommission']    = $totallotterycommission;
        $data['totalcommission']           = $totalcommission;

        foreach ($reportPlayerStatDays as $k => &$v) {
            if($v->day == date('Y-m-d')){
                if($playerDividendsMethod == 1){
                    $result                   = DevidendMode1::realtimePerformance($this->user,date('Ymd'));
                }
                
                $v->casino_commission     += $result['team_casino_commission'] + $result['directlyunder_casino_commission'] + $result['self_casino_commission'];
                $v->electronic_commission += $result['team_electronic_commission'] + $result['directlyunder_electronic_commission'] + $result['self_electronic_commission'];
                $v->esport_commission     += $result['team_esport_commission'] + $result['directlyunder_esport_commission'] + $result['self_esport_commission'];
                $v->fish_commission       += $result['team_fish_commission'] + $result['directlyunder_fish_commission'] + $result['self_fish_commission'];
                $v->card_commission       += $result['team_card_commission'] + $result['directlyunder_card_commission'] + $result['self_card_commission'];
                $v->sport_commission      += $result['team_sport_commission'] + $result['directlyunder_sport_commission'] + $result['self_sport_commission'];
                $v->lottery_commission    += $result['team_lottery_commission'] + $result['directlyunder_lottery_commission'] + $result['self_lottery_commission'];
                $v->commission            += $v->casino_commission + $v->electronic_commission + $v->esport_commission + $v->fish_commission + $v->card_commission + $v->sport_commission + $v->lottery_commission;

                $data['totalcasinocommission']     += $v->casino_commission;
                $data['totalelectroniccommission'] += $v->electronic_commission;
                $data['totalesportcommission']     += $v->esport_commission;
                $data['totalfishcommission']       += $v->fish_commission;
                $data['totalcardcommission']       += $v->card_commission;
                $data['totalsportcommission']      += $v->sport_commission;
                $data['totallotterycommission']    += $v->lottery_commission;
                $data['totalcommission']           += $v->commission;
            }
        }

        $data['item']                      = $reportPlayerStatDays;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function newPerformanceinDesc()
    {
        $input                 = request()->all();
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        
        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error380'],0);
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error381'],0);
        }

        $playerCommission = PlayerCommission::select(
            \DB::raw('sum(directlyunder_casino_performance) as directlyunder_casino_performance'),
            \DB::raw('sum(directlyunder_electronic_performance) as directlyunder_electronic_performance'),
            \DB::raw('sum(directlyunder_esport_performance) as directlyunder_esport_performance'),
            \DB::raw('sum(directlyunder_fish_performance) as directlyunder_fish_performance'),
            \DB::raw('sum(directlyunder_card_performance) as directlyunder_card_performance'),
            \DB::raw('sum(directlyunder_sport_performance) as directlyunder_sport_performance'),
            \DB::raw('sum(directlyunder_lottery_performance) as directlyunder_lottery_performance'),
            \DB::raw('sum(team_casino_performance) as team_casino_performance'),
            \DB::raw('sum(team_electronic_performance) as team_electronic_performance'),
            \DB::raw('sum(team_esport_performance) as team_esport_performance'),
            \DB::raw('sum(team_fish_performance) as team_fish_performance'),
            \DB::raw('sum(team_card_performance) as team_card_performance'),
            \DB::raw('sum(team_sport_performance) as team_sport_performance'),
            \DB::raw('sum(team_lottery_performance) as team_lottery_performance'),
            \DB::raw('sum(self_casino_performance) as self_casino_performance'),
            \DB::raw('sum(self_electronic_performance) as self_electronic_performance'),
            \DB::raw('sum(self_esport_performance) as self_esport_performance'),
            \DB::raw('sum(self_fish_performance) as self_fish_performance'),
            \DB::raw('sum(self_card_performance) as self_card_performance'),
            \DB::raw('sum(self_sport_performance) as self_sport_performance'),
            \DB::raw('sum(self_lottery_performance) as self_lottery_performance'),
            \DB::raw('sum(directlyunder_casino_commission) as directlyunder_casino_commission'),
            \DB::raw('sum(directlyunder_electronic_commission) as directlyunder_electronic_commission'),
            \DB::raw('sum(directlyunder_esport_commission) as directlyunder_esport_commission'),
            \DB::raw('sum(directlyunder_fish_commission) as directlyunder_fish_commission'),
            \DB::raw('sum(directlyunder_card_commission) as directlyunder_card_commission'),
            \DB::raw('sum(directlyunder_sport_commission) as directlyunder_sport_commission'),
            \DB::raw('sum(directlyunder_lottery_commission) as directlyunder_lottery_commission'),
            \DB::raw('sum(team_casino_commission) as team_casino_commission'),
            \DB::raw('sum(team_electronic_commission) as team_electronic_commission'),
            \DB::raw('sum(team_esport_commission) as team_esport_commission'),
            \DB::raw('sum(team_fish_commission) as team_fish_commission'),
            \DB::raw('sum(team_card_commission) as team_card_commission'),
            \DB::raw('sum(team_sport_commission) as team_sport_commission'),
            \DB::raw('sum(team_lottery_commission) as team_lottery_commission'),
            \DB::raw('sum(self_casino_commission) as self_casino_commission'),
            \DB::raw('sum(self_electronic_commission) as self_electronic_commission'),
            \DB::raw('sum(self_esport_commission) as self_esport_commission'),
            \DB::raw('sum(self_fish_commission) as self_fish_commission'),
            \DB::raw('sum(self_card_commission) as self_card_commission'),
            \DB::raw('sum(self_sport_commission) as self_sport_commission'),
            \DB::raw('sum(self_lottery_commission) as self_lottery_commission'),
            )->where('player_id',$this->user->player_id)
            ->where('day','>=',date('Ymd',strtotime($input['startDate'])))
            ->where('day','<=',date('Ymd',strtotime($input['endDate'])))->first();

        $row['total_casino_commission']              = 0;
        $row['total_electronic_commission']          = 0;
        $row['total_esport_commission']              = 0;
        $row['total_fish_commission']                = 0;
        $row['total_card_commission']                = 0;
        $row['total_sport_commission']               = 0;
        $row['total_lottery_commission']             = 0;
        $row['directlyunder_casino_performance']     = 0;
        $row['directlyunder_electronic_performance'] = 0;
        $row['directlyunder_esport_performance']     = 0;
        $row['directlyunder_fish_performance']       = 0;
        $row['directlyunder_card_performance']       = 0;
        $row['directlyunder_sport_performance']      = 0;
        $row['directlyunder_lottery_performance']    = 0;
        $row['team_casino_performance']              = 0;
        $row['team_electronic_performance']          = 0;
        $row['team_esport_performance']              = 0;
        $row['team_fish_performance']                = 0;
        $row['team_card_performance']                = 0;
        $row['team_sport_performance']               = 0;
        $row['team_lottery_performance']             = 0;
        $row['self_casino_performance']              = 0;
        $row['self_electronic_performance']          = 0;
        $row['self_esport_performance']              = 0;
        $row['self_fish_performance']                = 0;
        $row['self_card_performance']                = 0;
        $row['self_sport_performance']               = 0;
        $row['self_lottery_performance']             = 0;
        $row['total_casino_performance']             = 0;
        $row['total_electronic_performance']         = 0;
        $row['total_esport_performance']             = 0;
        $row['total_fish_performance']               = 0;
        $row['total_card_performance']               = 0;
        $row['total_sport_performance']              = 0;
        $row['total_lottery_performance']            = 0;
        $totalPerformance                            = 0;
        $totalCommission                             = 0;
        $selfPerformance                             = 0;

        if($playerCommission){
            $row['directlyunder_casino_performance']     = $playerCommission->directlyunder_casino_performance;
            $row['directlyunder_electronic_performance'] = $playerCommission->directlyunder_electronic_performance;
            $row['directlyunder_esport_performance']     = $playerCommission->directlyunder_esport_performance;
            $row['directlyunder_fish_performance']       = $playerCommission->directlyunder_fish_performance;
            $row['directlyunder_card_performance']       = $playerCommission->directlyunder_card_performance;
            $row['directlyunder_sport_performance']      = $playerCommission->directlyunder_sport_performance;
            $row['directlyunder_lottery_performance']    = $playerCommission->directlyunder_lottery_performance;
            $row['team_casino_performance']              = $playerCommission->team_casino_performance;
            $row['team_electronic_performance']          = $playerCommission->team_electronic_performance;
            $row['team_esport_performance']              = $playerCommission->team_esport_performance;
            $row['team_fish_performance']                = $playerCommission->team_fish_performance;
            $row['team_card_performance']                = $playerCommission->team_card_performance;
            $row['team_sport_performance']               = $playerCommission->team_sport_performance;
            $row['team_lottery_performance']             = $playerCommission->team_lottery_performance;
            $row['self_casino_performance']              = $playerCommission->self_casino_performance;
            $row['self_electronic_performance']          = $playerCommission->self_electronic_performance;
            $row['self_esport_performance']              = $playerCommission->self_esport_performance;
            $row['self_fish_performance']                = $playerCommission->self_fish_performance;
            $row['self_card_performance']                = $playerCommission->self_card_performance;
            $row['self_sport_performance']               = $playerCommission->self_sport_performance;
            $row['self_lottery_performance']             = $playerCommission->self_lottery_performance;
            $row['total_casino_performance']             = $playerCommission->directlyunder_casino_performance + $playerCommission->team_casino_performance + $playerCommission->self_casino_performance;
            $row['total_electronic_performance']         = $playerCommission->directlyunder_electronic_performance + $playerCommission->team_electronic_performance + $playerCommission->self_electronic_performance;
            $row['total_esport_performance']             = $playerCommission->directlyunder_esport_performance + $playerCommission->team_esport_performance + $playerCommission->self_esport_performance;
            $row['total_fish_performance']               = $playerCommission->directlyunder_fish_performance + $playerCommission->team_fish_performance + $playerCommission->self_fish_performance;
            $row['total_card_performance']               = $playerCommission->directlyunder_card_performance + $playerCommission->team_card_performance + $playerCommission->self_card_performance;
            $row['total_sport_performance']              = $playerCommission->directlyunder_sport_performance + $playerCommission->team_sport_performance + $playerCommission->self_sport_performance;
            $row['total_lottery_performance']            = $playerCommission->directlyunder_lottery_performance + $playerCommission->team_lottery_performance + $playerCommission->self_lottery_performance;
            $row['total_casino_commission']              = $playerCommission->directlyunder_casino_commission + $playerCommission->team_casino_commission + $playerCommission->self_casino_commission;
            $row['total_electronic_commission']          = $playerCommission->directlyunder_electronic_commission + $playerCommission->team_electronic_commission + $playerCommission->self_electronic_commission;
            $row['total_esport_commission']              = $playerCommission->directlyunder_esport_commission + $playerCommission->team_esport_commission + $playerCommission->self_esport_commission;
            $row['total_fish_commission']                = $playerCommission->directlyunder_fish_commission + $playerCommission->team_fish_commission + $playerCommission->self_fish_commission;
            $row['total_card_commission']                = $playerCommission->directlyunder_card_commission + $playerCommission->team_card_commission + $playerCommission->self_card_commission;
            $row['total_sport_commission']               = $playerCommission->directlyunder_sport_commission + $playerCommission->team_sport_commission + $playerCommission->self_sport_commission;
            $row['total_lottery_commission']             = $playerCommission->directlyunder_lottery_commission + $playerCommission->team_lottery_commission + $playerCommission->self_lottery_commission;

            $selfPerformance                             = $playerCommission->self_casino_performance + $playerCommission->self_electronic_performance + $playerCommission->self_esport_performance + $playerCommission->self_fish_performance + $playerCommission->self_card_performance + $playerCommission->self_sport_performance + $playerCommission->self_lottery_performance;

            $totalPerformance                            = $row['total_casino_performance'] + $row['total_electronic_performance'] + $row['total_esport_performance'] + $row['total_fish_performance'] + $row['total_card_performance'] + $row['total_sport_performance'] + $row['total_lottery_performance'];

            $totalCommission                             = $row['total_casino_commission'] + $row['total_electronic_commission'] + $row['total_esport_commission'] + $row['total_fish_commission'] + $row['total_card_commission'] + $row['total_sport_commission'] + $row['total_lottery_commission'];
        }

        $userNameArr    = [];
        $guaranteedArr  = [];
        $allSonSettings = PlayerSetting::where('parent_id',$this->user->player_id)->get();
        foreach ($allSonSettings as $key => $value) {
            $userNameArr[$value->player_id]   = $value->user_name;
            $guaranteedArr[$value->player_id] = $value->guaranteed;
        }

        $directlyunderPlayerRids   = PlayerSetting::where('parent_id',$this->user->player_id)->where('guaranteed',0)->pluck('rid')->toArray();

        $underPlayerIds            = PlayerSetting::where('parent_id',$this->user->player_id)->where('guaranteed',0)->pluck('player_id')->toArray();
        $underPlayerRIds           = PlayerSetting::where('rid','like',$this->user->rid.'|%')->pluck('rid')->toArray();


        //直属及直属团队用户ID
        $directlyunderPlayerIds    = [];

        foreach ($underPlayerIds as $key7 => $value7) {
            foreach ($underPlayerRIds as $key8 => $value8) {
                if(strpos($value8,strval($value7)) !== false){
                    $arr = explode('|',$value8);
                    $directlyunderPlayerIds[] = intval(end($arr));
                }
            }
        }

        $teamPlayerRids   = PlayerSetting::where('parent_id',$this->user->player_id)->where('guaranteed','>',0)->pluck('rid')->toArray();
        $teamPlayerIds1   = PlayerSetting::where('parent_id',$this->user->player_id)->where('guaranteed','>',0)->pluck('player_id')->toArray();

        //直属团队用户ID
        $teamPlayerIds    = [];

        foreach ($teamPlayerIds1 as $key7 => $value7) {
            foreach ($underPlayerRIds as $key8 => $value8) {
                if(strpos($value8,strval($value7)) !== false){
                    $arr = explode('|',$value8);
                    $teamPlayerIds[] = intval(end($arr));
                }
            }
        }

        $directlyunderPersonAdd   = Player::whereIn('player_id',$directlyunderPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->count();
        $teamPersonAdd            = Player::whereIn('player_id',$teamPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->count();

        $data                           = [];
        $data['item']                   = $row;
        $data['totalPerformance']       = $totalPerformance;
        $data['totalselfPerformance']   = $selfPerformance;
        $data['totalCommission']        = $totalCommission;
        $data['directlyunderPersonAdd'] = $directlyunderPersonAdd;
        $data['directlyunderPerson']    = count($directlyunderPlayerIds);
        $data['teamPerson']             = count($teamPlayerIds);
        $data['teamPersonAdd']          = $teamPersonAdd;
        $data['startDate']              = $input['startDate'];
        $data['endDate']                = $input['endDate'];

        //直属详情
        $directlyunderPlayerBetFlowMiddles =  PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),'rid')->whereIn('player_id',$directlyunderPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('rid')->get();

        $directlyunderItem = [];

        foreach ($directlyunderPlayerBetFlowMiddles as $key => $value) {
            foreach ($directlyunderPlayerRids as $key1 => $value1) {
                if(strpos($value->rid, strval($value1)) !== false){
                    $ridArr   = explode('|',$value1);
                    $playerId = end($ridArr);
                    if(isset($directlyunderItem[$playerId])){
                        $directlyunderItem[$playerId]['personnumber'] = $directlyunderItem[$playerId]['personnumber'] +1;
                        $directlyunderItem[$playerId]['performance']  = $directlyunderItem[$playerId]['performance'] +$value->process_available_bet_amount;
                    } else{
                        $directlyunderItem[$playerId]['personnumber'] = 1;
                        $directlyunderItem[$playerId]['performance']  = $value->process_available_bet_amount;
                        $directlyunderItem[$playerId]['player_id']    = $playerId;
                        $userNameArrs                                 = explode('_',$userNameArr[$playerId]);
                        $directlyunderItem[$playerId]['user_name']    = $userNameArrs[0];
                    }
                }
            }
        }

        foreach ($directlyunderItem as $key7 => &$value7) {
            $currPlayer             = Player::where('player_id',$value7['player_id'])->first();
            $value7['personnumber'] = Player::where('rid','like',$currPlayer->rid.'%')->count();
        }
        
        $data['directlyunderItem'] = $directlyunderItem;

        //团队详情
        $teamPlayerBetFlowMiddles =  PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),'rid')->whereIn('player_id',$teamPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('rid')->get();

        $teamItem = [];

        foreach ($teamPlayerBetFlowMiddles as $key => $value) {
            foreach ($teamPlayerRids as $key1 => $value1) {
                if(strpos($value->rid, $value1) !== false){
                    $ridArr   = explode('|',$value1);
                    $playerId = end($ridArr);
                    if(isset($teamItem[$playerId])){
                        $teamItem[$playerId]['personnumber'] = $teamItem[$playerId]['personnumber'] +1;
                        $teamItem[$playerId]['performance']  = $teamItem[$playerId]['performance'] +$value->process_available_bet_amount;
                    } else{
                        $teamItem[$playerId]['personnumber'] = 1;
                        $teamItem[$playerId]['performance']  = $value->process_available_bet_amount;
                        $teamItem[$playerId]['player_id']    = $playerId;
                        $userNameArrs                        = explode('_',$userNameArr[$playerId]);
                        $teamItem[$playerId]['user_name']    = $userNameArrs[0];
                        $teamItem[$playerId]['guaranteed']   = $guaranteedArr[$playerId];
                    }
                }
            }
        }

        foreach ($teamItem as $key8 => &$value8) {
            $currplayer               = Player::where('player_id',$value8['player_id'])->first();
            $value8['personnumber']   = Player::where('rid','like',$currplayer->rid.'%')->count();
        }

        $data['teamItem'] = $teamItem;

        if(date('Ymd')>=date('Ymd',strtotime($input['startDate'])) && date('Ymd') <= date('Ymd',strtotime($input['endDate']))){
            if($playerDividendsMethod ==1){
                $res              = DevidendMode1::realtimePerformanceDesc($this->user,date('Ymd'));
            }
            
            //累加业绩详情
            $row['directlyunder_casino_performance']     = $row['directlyunder_casino_performance'] + $res['directlyunder_casino_performance'];
            $row['self_casino_performance']              = $row['self_casino_performance'] + $res['self_casino_performance'];
            $row['team_casino_performance']              = $row['team_casino_performance'] + $res['team_casino_performance'];
            $row['total_casino_performance']             = $row['directlyunder_casino_performance'] + $row['self_casino_performance'] + $row['team_casino_performance'];
            $row['total_casino_commission']              = $row['total_casino_commission'] + $res['directlyunder_casino_commission'] + $res['self_casino_commission'] + $res['team_casino_commission'];

            $row['directlyunder_electronic_performance'] = $row['directlyunder_electronic_performance'] + $res['directlyunder_electronic_performance'];
            $row['team_electronic_performance']          = $row['team_electronic_performance'] + $res['team_electronic_performance'];
            $row['self_electronic_performance']          = $row['self_electronic_performance'] + $res['self_electronic_performance'];
            $row['total_electronic_performance']         = $row['directlyunder_electronic_performance'] + $row['team_electronic_performance'] + $row['self_electronic_performance'];
            $row['total_electronic_commission']          = $row['total_electronic_commission'] + $res['directlyunder_electronic_commission'] + $res['team_electronic_commission'] + $res['self_electronic_commission'];

            $row['directlyunder_esport_performance']     = $row['directlyunder_esport_performance'] + $res['directlyunder_esport_performance'];
            $row['team_esport_performance']              = $row['team_esport_performance'] + $res['team_esport_performance'];
            $row['self_esport_performance']              = $row['self_esport_performance'] + $res['self_esport_performance'];
            $row['total_esport_performance']             = $row['directlyunder_esport_performance'] + $row['team_esport_performance'] + $row['self_esport_performance'];
            $row['total_esport_commission']              = $row['total_esport_commission'] + $res['directlyunder_esport_commission'] + $res['team_esport_commission'] + $res['self_esport_commission'];

            $row['directlyunder_fish_performance']       = $row['directlyunder_fish_performance'] + $res['directlyunder_fish_performance'];
            $row['team_fish_performance']                = $row['team_fish_performance'] + $res['team_fish_performance'];
            $row['self_fish_performance']                = $row['self_fish_performance'] + $res['self_fish_performance'];
            $row['total_fish_performance']               = $row['directlyunder_fish_performance'] + $row['team_fish_performance'] + $row['self_fish_performance'];
            $row['total_fish_commission']                = $row['total_fish_commission'] + $res['directlyunder_fish_commission'] + $res['team_fish_commission'] + $res['self_fish_commission'];

            $row['directlyunder_card_performance']       = $row['directlyunder_card_performance'] + $res['directlyunder_card_performance'];
            $row['team_card_performance']                = $row['team_card_performance'] + $res['team_card_performance'];
            $row['self_card_performance']                = $row['self_card_performance'] + $res['self_card_performance'];
            $row['total_card_performance']               = $row['directlyunder_card_performance'] + $row['team_card_performance'] + $row['self_card_performance'];
            $row['total_card_commission']                = $row['total_card_commission'] + $res['directlyunder_card_commission'] + $res['team_card_commission'] + $res['self_card_commission'];

            $row['directlyunder_sport_performance']      = $row['directlyunder_sport_performance'] + $res['directlyunder_sport_performance'];
            $row['team_sport_performance']               = $row['team_sport_performance'] + $res['team_sport_performance'];
            $row['self_sport_performance']               = $row['self_sport_performance'] + $res['self_sport_performance'];
            $row['total_sport_performance']              = $row['directlyunder_sport_performance'] + $row['team_sport_performance'] + $row['self_sport_performance'];
            $row['total_sport_commission']               = $row['total_sport_commission'] + $res['directlyunder_sport_commission'] + $res['team_sport_commission'] + $res['self_sport_commission'];
            
            $row['directlyunder_lottery_performance']    = $row['directlyunder_lottery_performance'] + $res['directlyunder_lottery_performance'];
            $row['team_lottery_performance']             = $row['team_lottery_performance'] + $res['team_lottery_performance'];
            $row['self_lottery_performance']             = $row['self_lottery_performance'] + $res['self_lottery_performance'];
            $row['total_lottery_performance']            = $row['directlyunder_lottery_performance'] + $row['team_lottery_performance'] + $row['self_lottery_performance'];
            $row['total_lottery_commission']             = $row['total_lottery_commission'] + $res['directlyunder_lottery_commission'] + $res['team_lottery_commission'] + $res['self_lottery_commission'];

            //累加自营业绩
            $data['totalselfPerformance'] = $row['self_casino_performance'] + $row['self_electronic_performance'] + $row['self_esport_performance'] + $row['self_fish_performance'] + $row['self_card_performance'] + $row['self_sport_performance'] + $row['self_lottery_performance'];

            //累加总业绩
            $data['totalPerformance']     = $row['total_casino_performance'] + $row['total_electronic_performance'] + $row['total_esport_performance'] + $row['total_fish_performance'] + $row['total_card_performance'] + $row['total_sport_performance'] + $row['total_lottery_performance'];

            //累加总佣金
            $data['totalCommission']  = $row['total_casino_commission'] + $row['total_electronic_commission'] + $row['total_esport_commission'] + $row['total_fish_commission'] + $row['total_card_commission'] + $row['total_sport_commission'] + $row['total_lottery_commission'];


            $data['item']                   = $row;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function newPerformanceinDesc1()
    {
        $input                 = request()->all();
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);
        
        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error380'],0);
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error381'],0);
        }

        $playerCommission = PlayerCommission::select(
            \DB::raw('sum(directlyunder_casino_performance) as directlyunder_casino_performance'),
            \DB::raw('sum(directlyunder_electronic_performance) as directlyunder_electronic_performance'),
            \DB::raw('sum(directlyunder_esport_performance) as directlyunder_esport_performance'),
            \DB::raw('sum(directlyunder_fish_performance) as directlyunder_fish_performance'),
            \DB::raw('sum(directlyunder_card_performance) as directlyunder_card_performance'),
            \DB::raw('sum(directlyunder_sport_performance) as directlyunder_sport_performance'),
            \DB::raw('sum(directlyunder_lottery_performance) as directlyunder_lottery_performance'),
            \DB::raw('sum(team_casino_performance) as team_casino_performance'),
            \DB::raw('sum(team_electronic_performance) as team_electronic_performance'),
            \DB::raw('sum(team_esport_performance) as team_esport_performance'),
            \DB::raw('sum(team_fish_performance) as team_fish_performance'),
            \DB::raw('sum(team_card_performance) as team_card_performance'),
            \DB::raw('sum(team_sport_performance) as team_sport_performance'),
            \DB::raw('sum(team_lottery_performance) as team_lottery_performance'),
            \DB::raw('sum(self_casino_performance) as self_casino_performance'),
            \DB::raw('sum(self_electronic_performance) as self_electronic_performance'),
            \DB::raw('sum(self_esport_performance) as self_esport_performance'),
            \DB::raw('sum(self_fish_performance) as self_fish_performance'),
            \DB::raw('sum(self_card_performance) as self_card_performance'),
            \DB::raw('sum(self_sport_performance) as self_sport_performance'),
            \DB::raw('sum(self_lottery_performance) as self_lottery_performance'),
            \DB::raw('sum(directlyunder_casino_commission) as directlyunder_casino_commission'),
            \DB::raw('sum(directlyunder_electronic_commission) as directlyunder_electronic_commission'),
            \DB::raw('sum(directlyunder_esport_commission) as directlyunder_esport_commission'),
            \DB::raw('sum(directlyunder_fish_commission) as directlyunder_fish_commission'),
            \DB::raw('sum(directlyunder_card_commission) as directlyunder_card_commission'),
            \DB::raw('sum(directlyunder_sport_commission) as directlyunder_sport_commission'),
            \DB::raw('sum(directlyunder_lottery_commission) as directlyunder_lottery_commission'),
            \DB::raw('sum(team_casino_commission) as team_casino_commission'),
            \DB::raw('sum(team_electronic_commission) as team_electronic_commission'),
            \DB::raw('sum(team_esport_commission) as team_esport_commission'),
            \DB::raw('sum(team_fish_commission) as team_fish_commission'),
            \DB::raw('sum(team_card_commission) as team_card_commission'),
            \DB::raw('sum(team_sport_commission) as team_sport_commission'),
            \DB::raw('sum(team_lottery_commission) as team_lottery_commission'),
            \DB::raw('sum(self_casino_commission) as self_casino_commission'),
            \DB::raw('sum(self_electronic_commission) as self_electronic_commission'),
            \DB::raw('sum(self_esport_commission) as self_esport_commission'),
            \DB::raw('sum(self_fish_commission) as self_fish_commission'),
            \DB::raw('sum(self_card_commission) as self_card_commission'),
            \DB::raw('sum(self_sport_commission) as self_sport_commission'),
            \DB::raw('sum(self_lottery_commission) as self_lottery_commission'),
            )->where('player_id',$this->user->player_id)
            ->where('day','>=',date('Ymd',strtotime($input['startDate'])))
            ->where('day','<=',date('Ymd',strtotime($input['endDate'])))->first();

        $row['total_casino_commission']              = 0;
        $row['total_electronic_commission']          = 0;
        $row['total_esport_commission']              = 0;
        $row['total_fish_commission']                = 0;
        $row['total_card_commission']                = 0;
        $row['total_sport_commission']               = 0;
        $row['total_lottery_commission']             = 0;
        $row['directlyunder_casino_performance']     = 0;
        $row['directlyunder_electronic_performance'] = 0;
        $row['directlyunder_esport_performance']     = 0;
        $row['directlyunder_fish_performance']       = 0;
        $row['directlyunder_card_performance']       = 0;
        $row['directlyunder_sport_performance']      = 0;
        $row['directlyunder_lottery_performance']    = 0;
        $row['team_casino_performance']              = 0;
        $row['team_electronic_performance']          = 0;
        $row['team_esport_performance']              = 0;
        $row['team_fish_performance']                = 0;
        $row['team_card_performance']                = 0;
        $row['team_sport_performance']               = 0;
        $row['team_lottery_performance']             = 0;
        $row['self_casino_performance']              = 0;
        $row['self_electronic_performance']          = 0;
        $row['self_esport_performance']              = 0;
        $row['self_fish_performance']                = 0;
        $row['self_card_performance']                = 0;
        $row['self_sport_performance']               = 0;
        $row['self_lottery_performance']             = 0;
        $row['total_casino_performance']             = 0;
        $row['total_electronic_performance']         = 0;
        $row['total_esport_performance']             = 0;
        $row['total_fish_performance']               = 0;
        $row['total_card_performance']               = 0;
        $row['total_sport_performance']              = 0;
        $row['total_lottery_performance']            = 0;
        $totalPerformance                            = 0;
        $totalCommission                             = 0;
        $selfPerformance                             = 0;

        if($playerCommission){
            $row['directlyunder_casino_performance']     = $playerCommission->directlyunder_casino_performance;
            $row['directlyunder_electronic_performance'] = $playerCommission->directlyunder_electronic_performance;
            $row['directlyunder_esport_performance']     = $playerCommission->directlyunder_esport_performance;
            $row['directlyunder_fish_performance']       = $playerCommission->directlyunder_fish_performance;
            $row['directlyunder_card_performance']       = $playerCommission->directlyunder_card_performance;
            $row['directlyunder_sport_performance']      = $playerCommission->directlyunder_sport_performance;
            $row['directlyunder_lottery_performance']    = $playerCommission->directlyunder_lottery_performance;
            $row['team_casino_performance']              = $playerCommission->team_casino_performance;
            $row['team_electronic_performance']          = $playerCommission->team_electronic_performance;
            $row['team_esport_performance']              = $playerCommission->team_esport_performance;
            $row['team_fish_performance']                = $playerCommission->team_fish_performance;
            $row['team_card_performance']                = $playerCommission->team_card_performance;
            $row['team_sport_performance']               = $playerCommission->team_sport_performance;
            $row['team_lottery_performance']             = $playerCommission->team_lottery_performance;
            $row['self_casino_performance']              = $playerCommission->self_casino_performance;
            $row['self_electronic_performance']          = $playerCommission->self_electronic_performance;
            $row['self_esport_performance']              = $playerCommission->self_esport_performance;
            $row['self_fish_performance']                = $playerCommission->self_fish_performance;
            $row['self_card_performance']                = $playerCommission->self_card_performance;
            $row['self_sport_performance']               = $playerCommission->self_sport_performance;
            $row['self_lottery_performance']             = $playerCommission->self_lottery_performance;
            $row['total_casino_performance']             = $playerCommission->directlyunder_casino_performance + $playerCommission->team_casino_performance + $playerCommission->self_casino_performance;
            $row['total_electronic_performance']         = $playerCommission->directlyunder_electronic_performance + $playerCommission->team_electronic_performance + $playerCommission->self_electronic_performance;
            $row['total_esport_performance']             = $playerCommission->directlyunder_esport_performance + $playerCommission->team_esport_performance + $playerCommission->self_esport_performance;
            $row['total_fish_performance']               = $playerCommission->directlyunder_fish_performance + $playerCommission->team_fish_performance + $playerCommission->self_fish_performance;
            $row['total_card_performance']               = $playerCommission->directlyunder_card_performance + $playerCommission->team_card_performance + $playerCommission->self_card_performance;
            $row['total_sport_performance']              = $playerCommission->directlyunder_sport_performance + $playerCommission->team_sport_performance + $playerCommission->self_sport_performance;
            $row['total_lottery_performance']            = $playerCommission->directlyunder_lottery_performance + $playerCommission->team_lottery_performance + $playerCommission->self_lottery_performance;
            $row['total_casino_commission']              = $playerCommission->directlyunder_casino_commission + $playerCommission->team_casino_commission + $playerCommission->self_casino_commission;
            $row['total_electronic_commission']          = $playerCommission->directlyunder_electronic_commission + $playerCommission->team_electronic_commission + $playerCommission->self_electronic_commission;
            $row['total_esport_commission']              = $playerCommission->directlyunder_esport_commission + $playerCommission->team_esport_commission + $playerCommission->self_esport_commission;
            $row['total_fish_commission']                = $playerCommission->directlyunder_fish_commission + $playerCommission->team_fish_commission + $playerCommission->self_fish_commission;
            $row['total_card_commission']                = $playerCommission->directlyunder_card_commission + $playerCommission->team_card_commission + $playerCommission->self_card_commission;
            $row['total_sport_commission']               = $playerCommission->directlyunder_sport_commission + $playerCommission->team_sport_commission + $playerCommission->self_sport_commission;
            $row['total_lottery_commission']             = $playerCommission->directlyunder_lottery_commission + $playerCommission->team_lottery_commission + $playerCommission->self_lottery_commission;

            $selfPerformance                             = $playerCommission->self_casino_performance + $playerCommission->self_electronic_performance + $playerCommission->self_esport_performance + $playerCommission->self_fish_performance + $playerCommission->self_card_performance + $playerCommission->self_sport_performance + $playerCommission->self_lottery_performance;

            $totalPerformance                            = $row['total_casino_performance'] + $row['total_electronic_performance'] + $row['total_esport_performance'] + $row['total_fish_performance'] + $row['total_card_performance'] + $row['total_sport_performance'] + $row['total_lottery_performance'];

            $totalCommission                             = $row['total_casino_commission'] + $row['total_electronic_commission'] + $row['total_esport_commission'] + $row['total_fish_commission'] + $row['total_card_commission'] + $row['total_sport_commission'] + $row['total_lottery_commission'];
        }

        $userNameArr    = [];
        $guaranteedArr  = [];
        $allSonSettings = PlayerSetting::where('rid','like',$this->user->rid.'|%')->get();
        foreach ($allSonSettings as $key => $value) {
            $userNameArr[$value->player_id]   = $value->user_name;
            $guaranteedArr[$value->player_id] = $value->guaranteed;
        }

        //直属及直属团队用户ID
        $directlyunderPlayerIds  = PlayerSetting::where('parent_id',$this->user->player_id)->pluck('player_id')->toArray();
        $directlyunderPlayerRids = PlayerSetting::where('parent_id',$this->user->player_id)->pluck('rid')->toArray();

        //团队玩家
        $teamPlayerIds          = PlayerSetting::where('parent_id','!=',$this->user->player_id)->where('rid','like',$this->user->rid.'|%')->pluck('player_id')->toArray();
        $teamPlayerRids         = PlayerSetting::where('parent_id','!=',$this->user->player_id)->where('rid','like',$this->user->rid.'|%')->pluck('rid')->toArray();

        $directlyunderPersonAdd   = Player::whereIn('player_id',$directlyunderPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->count();
        $teamPersonAdd            = Player::whereIn('player_id',$teamPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->count();

        $data                           = [];
        $data['item']                   = $row;
        $data['totalPerformance']       = $totalPerformance;
        $data['totalselfPerformance']   = $selfPerformance;
        $data['totalCommission']        = $totalCommission;
        $data['directlyunderPersonAdd'] = $directlyunderPersonAdd;
        $data['directlyunderPerson']    = count($directlyunderPlayerIds);
        $data['teamPerson']             = count($teamPlayerIds);
        $data['teamPersonAdd']          = $teamPersonAdd;
        $data['startDate']              = $input['startDate'];
        $data['endDate']                = $input['endDate'];

        //直属详情
        $directlyunderPlayerBetFlowMiddles =  PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),'rid','player_id')->whereIn('player_id',$directlyunderPlayerIds)->where('whether_recharge',1)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('rid')->get();

        $directlyunderItem = [];

        foreach ($directlyunderPlayerBetFlowMiddles as $key => $value) {
           if(isset($directlyunderItem[$value->player_id])){
                $directlyunderItem[$value->player_id]['performance']  = $directlyunderItem[$value->player_id]['performance'] +$value->process_available_bet_amount;
            } else{
                $directlyunderItem[$value->player_id]['performance']  = $value->process_available_bet_amount;
                $directlyunderItem[$value->player_id]['player_id']    = $value->player_id;
                $userNameArrs                                         = explode('_',$userNameArr[$value->player_id]);
                $directlyunderItem[$value->player_id]['user_name']    = $userNameArrs[0];
            }
        }

        foreach ($directlyunderItem as $key7 => &$value7) {
            $currPlayer             = Player::where('player_id',$value7['player_id'])->first();
            $value7['personnumber'] = Player::where('rid','like',$currPlayer->rid.'|%')->count();
        }
        
        $data['directlyunderItem'] = $directlyunderItem;

        //团队详情
        $directlyunderPlayers     = PlayerSetting::where('parent_id',$this->user->player_id)->get();
        $teamPlayerBetFlowMiddles =  PlayerBetFlowMiddle::select(\DB::raw('sum(agent_process_available_bet_amount) as process_available_bet_amount'),'rid')->whereIn('player_id',$teamPlayerIds)->where('whether_recharge',1)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('rid')->get();

        $teamItem = [];

        foreach ($teamPlayerBetFlowMiddles as $key => $value) {
            foreach ($directlyunderPlayers as $key1 => $value1) {
                if(strpos($value->rid, $value1->rid) !== false){
                    if(isset($teamItem[$value1->player_id])){
                        $teamItem[$value1->player_id]['performance']  = $teamItem[$value1->player_id]['performance'] +$value->process_available_bet_amount;
                    } else{
                        $teamItem[$value1->player_id]['performance']  = $value->process_available_bet_amount;
                        $teamItem[$value1->player_id]['player_id']    = $value1->player_id;
                        $userNameArrs                        = explode('_',$value1->user_name);
                        $teamItem[$value1->player_id]['user_name']    = $userNameArrs[0];
                        $teamItem[$value1->player_id]['guaranteed']   = $value1->guaranteed;
                    }
                }
            }
        }

        foreach ($teamItem as $key8 => &$value8) {
            $currplayer               = Player::where('player_id',$value8['player_id'])->first();
            $value8['personnumber']   = Player::where('rid','like',$currplayer->rid.'|%')->count();
        }

        $data['teamItem']             = $teamItem;

        if(date('Ymd')>=date('Ymd',strtotime($input['startDate'])) && date('Ymd') <= date('Ymd',strtotime($input['endDate']))){

            if($playerDividendsMethod ==1){
                $res              = DevidendMode1::realtimePerformanceDesc($this->user,date('Ymd'));
            }
            
            //累加业绩详情
            $row['directlyunder_casino_performance']     = $row['directlyunder_casino_performance'] + $res['directlyunder_casino_performance'];
            $row['self_casino_performance']              = $row['self_casino_performance'] + $res['self_casino_performance'];
            $row['team_casino_performance']              = $row['team_casino_performance'] + $res['team_casino_performance'];
            $row['total_casino_performance']             = $row['directlyunder_casino_performance'] + $row['self_casino_performance'] + $row['team_casino_performance'];
            $row['total_casino_commission']              = $row['total_casino_commission'] + $res['directlyunder_casino_commission'] + $res['self_casino_commission'] + $res['team_casino_commission'];

            $row['directlyunder_electronic_performance'] = $row['directlyunder_electronic_performance'] + $res['directlyunder_electronic_performance'];
            $row['team_electronic_performance']          = $row['team_electronic_performance'] + $res['team_electronic_performance'];
            $row['self_electronic_performance']          = $row['self_electronic_performance'] + $res['self_electronic_performance'];
            $row['total_electronic_performance']         = $row['directlyunder_electronic_performance'] + $row['team_electronic_performance'] + $row['self_electronic_performance'];
            $row['total_electronic_commission']          = $row['total_electronic_commission'] + $res['directlyunder_electronic_commission'] + $res['team_electronic_commission'] + $res['self_electronic_commission'];

            $row['directlyunder_esport_performance']     = $row['directlyunder_esport_performance'] + $res['directlyunder_esport_performance'];
            $row['team_esport_performance']              = $row['team_esport_performance'] + $res['team_esport_performance'];
            $row['self_esport_performance']              = $row['self_esport_performance'] + $res['self_esport_performance'];
            $row['total_esport_performance']             = $row['directlyunder_esport_performance'] + $row['team_esport_performance'] + $row['self_esport_performance'];
            $row['total_esport_commission']              = $row['total_esport_commission'] + $res['directlyunder_esport_commission'] + $res['team_esport_commission'] + $res['self_esport_commission'];

            $row['directlyunder_fish_performance']       = $row['directlyunder_fish_performance'] + $res['directlyunder_fish_performance'];
            $row['team_fish_performance']                = $row['team_fish_performance'] + $res['team_fish_performance'];
            $row['self_fish_performance']                = $row['self_fish_performance'] + $res['self_fish_performance'];
            $row['total_fish_performance']               = $row['directlyunder_fish_performance'] + $row['team_fish_performance'] + $row['self_fish_performance'];
            $row['total_fish_commission']                = $row['total_fish_commission'] + $res['directlyunder_fish_commission'] + $res['team_fish_commission'] + $res['self_fish_commission'];

            $row['directlyunder_card_performance']       = $row['directlyunder_card_performance'] + $res['directlyunder_card_performance'];
            $row['team_card_performance']                = $row['team_card_performance'] + $res['team_card_performance'];
            $row['self_card_performance']                = $row['self_card_performance'] + $res['self_card_performance'];
            $row['total_card_performance']               = $row['directlyunder_card_performance'] + $row['team_card_performance'] + $row['self_card_performance'];
            $row['total_card_commission']                = $row['total_card_commission'] + $res['directlyunder_card_commission'] + $res['team_card_commission'] + $res['self_card_commission'];

            $row['directlyunder_sport_performance']      = $row['directlyunder_sport_performance'] + $res['directlyunder_sport_performance'];
            $row['team_sport_performance']               = $row['team_sport_performance'] + $res['team_sport_performance'];
            $row['self_sport_performance']               = $row['self_sport_performance'] + $res['self_sport_performance'];
            $row['total_sport_performance']              = $row['directlyunder_sport_performance'] + $row['team_sport_performance'] + $row['self_sport_performance'];
            $row['total_sport_commission']               = $row['total_sport_commission'] + $res['directlyunder_sport_commission'] + $res['team_sport_commission'] + $res['self_sport_commission'];
            
            $row['directlyunder_lottery_performance']    = $row['directlyunder_lottery_performance'] + $res['directlyunder_lottery_performance'];
            $row['team_lottery_performance']             = $row['team_lottery_performance'] + $res['team_lottery_performance'];
            $row['self_lottery_performance']             = $row['self_lottery_performance'] + $res['self_lottery_performance'];
            $row['total_lottery_performance']            = $row['directlyunder_lottery_performance'] + $row['team_lottery_performance'] + $row['self_lottery_performance'];
            $row['total_lottery_commission']             = $row['total_lottery_commission'] + $res['directlyunder_lottery_commission'] + $res['team_lottery_commission'] + $res['self_lottery_commission'];

            //累加自营业绩
            $data['totalselfPerformance'] = $row['self_casino_performance'] + $row['self_electronic_performance'] + $row['self_esport_performance'] + $row['self_fish_performance'] + $row['self_card_performance'] + $row['self_sport_performance'] + $row['self_lottery_performance'];

            //累加总业绩
            $data['totalPerformance']     = $row['total_casino_performance'] + $row['total_electronic_performance'] + $row['total_esport_performance'] + $row['total_fish_performance'] + $row['total_card_performance'] + $row['total_sport_performance'] + $row['total_lottery_performance'];

            //累加总佣金
            $data['totalCommission']  = $row['total_casino_commission'] + $row['total_electronic_commission'] + $row['total_esport_commission'] + $row['total_fish_commission'] + $row['total_card_commission'] + $row['total_sport_commission'] + $row['total_lottery_commission'];


            $data['item']                   = $row;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function insideTransfer()
    {
        $input              = request()->all();
        $siteTransferMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'site_transfer_method',$this->user->prefix);

        if(!$siteTransferMethod){
            return $this->returnApiJson(config('language')[$this->language]['error382'],0);
        }

        if(!isset($input['player_id']) || empty($input['player_id'])){
            return $this->returnApiJson(config('language')[$this->language]['error357'],0);
        }

        $inPlayer = Player::where('player_id',$input['player_id'])->first();
        if(!$inPlayer){
            return $this->returnApiJson(config('language')[$this->language]['error384'],0);
        }

        if(!isset($input['amount']) || !is_numeric($input['amount']) || $input['amount']<=0){
            return $this->returnApiJson(config('language')[$this->language]['error112'],0);
        }

        if(!isset($input['paypassword']) || empty($input['paypassword'])){
            return $this->returnApiJson(config('language')[$this->language]['error385'],0);
        }

        if(!\Hash::check($input['paypassword'], $this->user->paypassword)) {
            return $this->returnApiJson(config('language')[$this->language]['error385'],0);
        }

        $playerAccount = PlayerAccount::where('player_id',$this->user->player_id)->first();
        if($playerAccount->balance < $input['amount']*10000){
            return $this->returnApiJson(config('language')[$this->language]['error348'],0);
        }

        $playerWithdrawFlowLimit = PlayerWithdrawFlowLimit::where('player_id',$this->user->player_id)->where('is_finished',0)->first();
        if($playerWithdrawFlowLimit){
            return $this->returnApiJson(config('language')[$this->language]['error386'],0);
        }

        $playerIds            = explode('|',$this->user->rid);
        $subordinatePlayerIds = Player::where('rid','like',$this->user->rid.'|%')->pluck('player_id')->toArray();
        if($input['player_id'] == $this->user->player_id){
            return $this->returnApiJson(config('language')[$this->language]['error387'],0);
        }
        
        $cacheKey           = "player_" .$this->user->player_id;
        $cacheKey1          = "player_" .$input['player_id'];
        $redisLock          = Lock::addLock($cacheKey,60);
        $redisLock1         = Lock::addLock($cacheKey1,60);
  
        if (!$redisLock || !$redisLock1) {
            return $this->returnApiJson(config('language')[$this->language]['error20'], 0);
        } else {
            try{
                \DB::beginTransaction();
                $selfPlayerAccount                               = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();
                $inPlayerAccount                                 = PlayerAccount::where('player_id',$input['player_id'])->lockForUpdate()->first();
    
                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->user->prefix;
                $playerTransfer->carrier_id                      = $selfPlayerAccount->carrier_id;
                $playerTransfer->rid                             = $selfPlayerAccount->rid;
                $playerTransfer->top_id                          = $selfPlayerAccount->top_id;
                $playerTransfer->parent_id                       = $selfPlayerAccount->parent_id;
                $playerTransfer->player_id                       = $selfPlayerAccount->player_id;
                $playerTransfer->is_tester                       = $selfPlayerAccount->is_tester;
                $playerTransfer->level                           = $selfPlayerAccount->level;
                $playerTransfer->user_name                       = $selfPlayerAccount->user_name;
                $playerTransfer->mode                            = 2;
                $playerTransfer->type                            = 'inside_transfer_to';
                $playerTransfer->type_name                       = config('language')['zh']['text149'];
                $playerTransfer->en_type_name                    = config('language')['en']['text149'];
                $playerTransfer->day_m                           = date('Ym',time());
                $playerTransfer->day                             = date('Ymd',time());
                $playerTransfer->amount                          = $input['amount']*10000;
                $playerTransfer->before_balance                  = $selfPlayerAccount->balance;
                $playerTransfer->balance                         = $selfPlayerAccount->balance - $playerTransfer->amount;
                $playerTransfer->before_frozen_balance           = $selfPlayerAccount->frozen;
                $playerTransfer->frozen_balance                  = $selfPlayerAccount->frozen;

                $playerTransfer->before_agent_balance         = $selfPlayerAccount->agentbalance;
                $playerTransfer->agent_balance                = $selfPlayerAccount->agentbalance;
                $playerTransfer->before_agent_frozen_balance  = $selfPlayerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance         = $selfPlayerAccount->agentfrozen;
                $playerTransfer->save();

                $selfPlayerAccount->balance                   = $playerTransfer->balance;
                $selfPlayerAccount->save();

                $inplayerTransfer                                  = new PlayerTransfer();
                $inplayerTransfer->prefix                          = $this->user->prefix;
                $inplayerTransfer->carrier_id                      = $inPlayerAccount->carrier_id;
                $inplayerTransfer->rid                             = $inPlayerAccount->rid;
                $inplayerTransfer->top_id                          = $inPlayerAccount->top_id;
                $inplayerTransfer->parent_id                       = $inPlayerAccount->parent_id;
                $inplayerTransfer->player_id                       = $inPlayerAccount->player_id;
                $inplayerTransfer->is_tester                       = $inPlayerAccount->is_tester;
                $inplayerTransfer->level                           = $inPlayerAccount->level;
                $inplayerTransfer->user_name                       = $inPlayerAccount->user_name;
                $inplayerTransfer->mode                            = 1;
                $inplayerTransfer->type                            = 'inside_transfer_in';
                $inplayerTransfer->type_name                       = config('language')['zh']['text134'];
                $inplayerTransfer->en_type_name                    = config('language')['en']['text134'];
                $inplayerTransfer->day_m                           = date('Ym',time());
                $inplayerTransfer->day                             = date('Ymd',time());
                $inplayerTransfer->amount                          = $input['amount']*10000;
                $inplayerTransfer->before_balance                  = $inPlayerAccount->balance;
                $inplayerTransfer->balance                         = $inPlayerAccount->balance + $inplayerTransfer->amount;
                $inplayerTransfer->before_frozen_balance           = $inPlayerAccount->frozen;
                $inplayerTransfer->frozen_balance                  = $inPlayerAccount->frozen;
                $inplayerTransfer->before_agent_balance            = $inPlayerAccount->agentbalance;
                $inplayerTransfer->agent_balance                   = $inPlayerAccount->agentbalance;
                $inplayerTransfer->before_agent_frozen_balance     = $inPlayerAccount->agentfrozen;
                $inplayerTransfer->agent_frozen_balance            = $inPlayerAccount->agentfrozen;
                $inplayerTransfer->save();

                $inPlayerAccount->balance                          = $inplayerTransfer->balance;
                $inPlayerAccount->save();

                $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                $playerWithdrawFlowLimit->carrier_id             = $inPlayerAccount->carrier_id;
                $playerWithdrawFlowLimit->top_id                 = $inPlayerAccount->top_id;
                $playerWithdrawFlowLimit->parent_id              = $inPlayerAccount->parent_id;
                $playerWithdrawFlowLimit->rid                    = $inPlayerAccount->rid;
                $playerWithdrawFlowLimit->player_id              = $inPlayerAccount->player_id;
                $playerWithdrawFlowLimit->user_name              = $inPlayerAccount->user_name;
                $playerWithdrawFlowLimit->limit_type             = 48;
                $playerWithdrawFlowLimit->limit_amount           = $input['amount']*10000;
                $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                $playerWithdrawFlowLimit->is_finished            = 0;
                $playerWithdrawFlowLimit->operator_id            = 0;
                $playerWithdrawFlowLimit->save();

                \DB::commit();
                Lock::release($redisLock);
                Lock::release($redisLock1);
                return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=>bcdiv($selfPlayerAccount->balance,10000,2)]);
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Lock::release($redisLock1);
                Clog::recordabnormal('站内转帐异常:'.$e->getMessage());
                return $this->returnApiJson(config('language')[$this->language]['error388'].$e->getMessage(), 0);
            }
        }

    }

    public function receiveRechargeDividends()
    {
        $playerSetting                    = PlayerSetting::where('player_id',$this->user->player_id)->first();
        if($playerSetting->guaranteed==0){
            return $this->returnApiJson(config('language')[$this->language]['error389'], 0);
        }

        $thirdWalletRechargeDividendsRate = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'third_wallet_recharge_dividends_rate',$this->prefix);

        $latelyDividend                   = PlayerTransfer::where('type','dividend_from_recharge')->where('player_id',$this->user->player_id)->orderBy('id','desc')->first();
        $availableAmount                  = 0;
        $availableDividendAmount          = 0;

        $underDirectPlayerIds             = PlayerSetting::where('parent_id',$this->user->player_id)->where('guaranteed',0)->pluck('player_id')->toArray();

        if($latelyDividend){
            $availableAmount = PlayerDepositPayLog::whereIn('player_id',$underDirectPlayerIds)->where('status',1)->where('day','<',date('Ymd'))->where('day','>=',date('Ymd',strtotime($latelyDividend->created_at)))->where('is_wallet_recharge',1)->sum('amount');
        } else{
            $availableAmount = PlayerDepositPayLog::whereIn('player_id',$underDirectPlayerIds)->where('status',1)->where('day','<',date('Ymd'))->where('is_wallet_recharge',1)->sum('amount');
        }

        if($availableAmount){
            $availableDividendAmount = bcdiv($availableAmount*$thirdWalletRechargeDividendsRate,100,0);
        } else{
            return $this->returnApiJson(config('language')[$this->language]['error354'],0);
        }

        $cacheKey           = "player_" .$this->user->player_id;
        $redisLock          = Lock::addLock($cacheKey,60);
  
        if (!$redisLock) {
                return $this->returnApiJson(config('language')[$this->language]['error20'], 0);
        } else {
            try{
                \DB::beginTransaction();
                $selfPlayerAccount                               = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();
    
                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->user->prefix;
                $playerTransfer->carrier_id                      = $selfPlayerAccount->carrier_id;
                $playerTransfer->rid                             = $selfPlayerAccount->rid;
                $playerTransfer->top_id                          = $selfPlayerAccount->top_id;
                $playerTransfer->parent_id                       = $selfPlayerAccount->parent_id;
                $playerTransfer->player_id                       = $selfPlayerAccount->player_id;
                $playerTransfer->is_tester                       = $selfPlayerAccount->is_tester;
                $playerTransfer->level                           = $selfPlayerAccount->level;
                $playerTransfer->user_name                       = $selfPlayerAccount->user_name;
                $playerTransfer->mode                            = 1;
                $playerTransfer->type                            = 'dividend_from_recharge';
                $playerTransfer->type_name                       = config('language')['zh']['error108'];
                $playerTransfer->en_type_name                    = config('language')['en']['error108'];
                $playerTransfer->day_m                           = date('Ym',time());
                $playerTransfer->day                             = date('Ymd',time());
                $playerTransfer->amount                          = $availableDividendAmount;
                $playerTransfer->before_balance                  = $selfPlayerAccount->balance;
                $playerTransfer->balance                         = $selfPlayerAccount->balance + $playerTransfer->amount;
                $playerTransfer->before_frozen_balance           = $selfPlayerAccount->frozen;
                $playerTransfer->frozen_balance                  = $selfPlayerAccount->frozen;

                $playerTransfer->before_agent_balance         = $selfPlayerAccount->agentbalance;
                $playerTransfer->agent_balance                = $selfPlayerAccount->agentbalance;
                $playerTransfer->before_agent_frozen_balance  = $selfPlayerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance         = $selfPlayerAccount->agentfrozen;
                $playerTransfer->save();

                $selfPlayerAccount->balance                      = $playerTransfer->balance;
                $selfPlayerAccount->save();

                \DB::commit();
                Lock::release($redisLock);
                return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
            } catch (\Exception $e) {
                \DB::rollback();
                Lock::release($redisLock);
                Clog::recordabnormal('领取充值分红时数据异常:'.$e->getMessage());
                return $this->returnApiJson(config('language')[$this->language]['error390'].$e->getMessage(), 0);
            }
        }
    }

    public function capitationFeeLevelsList()
    {
        $carrierCapitationFeeSettings = CarrierCapitationFeeSetting::where('carrier_id',$this->user->carrier_id)->where('prefix',$this->user->prefix)->orderBy('sort','asc')->get();
        $capitationFeeCount           = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','capitation_fee_add')->count();
        $playerCapitationFeeCount     = PlayerCapitationFee::where('parent_id',$this->user->player_id)->whereIn('status',[1,2])->count();

        //0=未完成，1=已完成，2=已领取
        foreach ($carrierCapitationFeeSettings as $key => &$value) {
            if($value->sort <= $capitationFeeCount ){
                $value->status = 2;
            } else{
                if($playerCapitationFeeCount>=$value->sort){
                    $value->status = 1;
                } else{
                    $value->status = 0;
                }
            }
            $value->inviteNumber = $playerCapitationFeeCount;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$carrierCapitationFeeSettings);
    }

    public function receiveCapitationFeeLevels($id)
    {
        $carrierCapitationFeeSetting = CarrierCapitationFeeSetting::where('carrier_id',$this->user->carrier_id)->where('prefix',$this->user->prefix)->where('sort',$id)->first();
        $playerCapitationFeeCount    = PlayerCapitationFee::where('parent_id',$this->user->player_id)->whereIn('status',[1,2])->count();
        $enableCapitationFee         = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'enable_capitation_fee',$this->user->prefix);

        if(!$enableCapitationFee){
            return $this->returnApiJson(config('language')[$this->language]['error392'], 0);
        }

        if(!$carrierCapitationFeeSetting){
            return $this->returnApiJson(config('language')[$this->language]['error339'], 0);
        }

        $playerCapitationFee                             = PlayerCapitationFee::where('parent_id',$this->user->player_id)->where('status',1)->first();

        if(!$playerCapitationFee){
            return $this->returnApiJson(config('language')[$this->language]['error393'], 0);
        }

        if($carrierCapitationFeeSetting->sort <= $playerCapitationFeeCount){
            $cacheKey = "player_" .$this->user->player_id;
            $redisLock = Lock::addLock($cacheKey,10);

            if (!$redisLock) {
            } else {
                try {
                    \DB::beginTransaction();

                    $playerAccount                                   = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();                    
                    $playerTransfer                                  = new PlayerTransfer();
                    $playerTransfer->prefix                          = $this->user->prefix;
                    $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                    $playerTransfer->rid                             = $playerAccount->rid;
                    $playerTransfer->top_id                          = $playerAccount->top_id;
                    $playerTransfer->parent_id                       = $playerAccount->parent_id;
                    $playerTransfer->player_id                       = $playerAccount->player_id;
                    $playerTransfer->is_tester                       = $playerAccount->is_tester;
                    $playerTransfer->level                           = $playerAccount->level;
                    $playerTransfer->user_name                       = $playerAccount->user_name;
                    $playerTransfer->mode                            = 1;
                    $playerTransfer->type                            = 'capitation_fee_add';
                    $playerTransfer->type_name                       = config('language')['zh']['text135'];
                    $playerTransfer->en_type_name                    = config('language')['en']['text135'];
                    $playerTransfer->day_m                           = date('Ym',time());
                    $playerTransfer->day                             = date('Ymd',time());
                    $playerTransfer->amount                          = $carrierCapitationFeeSetting->amount*10000;
                    $playerTransfer->before_balance                  = $playerAccount->balance;
                    $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                    $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                    $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                    $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                    $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                    $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                    $playerTransfer->save();           

                    //人头费流水
                    $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                    $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                    $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                    $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                    $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                    $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;
                    $playerWithdrawFlowLimit->limit_type             = 53;
                    $playerWithdrawFlowLimit->limit_amount           = $carrierCapitationFeeSetting->amount*10000;
                    $playerWithdrawFlowLimit->complete_limit_amount  = 0;
                    $playerWithdrawFlowLimit->is_finished            = 0;
                    $playerWithdrawFlowLimit->operator_id            = 0;
                    $playerWithdrawFlowLimit->save();


                    $playerCapitationFee->status                     = 2;
                    $playerCapitationFee->save();

                    $playerAccount->balance                          = $playerTransfer->balance;
                    $playerAccount->save();

                    \DB::commit();
                    Lock::release($redisLock);

                    return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['balance'=> bcdiv($playerAccount->balance,10000,2)]);
                } catch (\Exception $e) {
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('领取人头费金异常:'.$e->getMessage());
                    return $this->returnApiJson(config('language')[$this->language]['error395'].$e->getMessage(), 0);
                }
            }
        } else{
            return $this->returnApiJson(config('language')[$this->language]['error394'], 0);
        }
    }

    public function venueFeesList()
    {
        $input = request()->all();

        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error380'], 0);
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error381'], 0);
        }

        $data                     = [];

        $mainGamePlats            = MainGamePlat::all();
        $gamePlatNames            = [];

        foreach ($mainGamePlats as $key => $value) {
            $gamePlatNames[$value->main_game_plat_id] = $value->alias;
        }

        $carrierGamePlats         = CarrierPreFixGamePlat::where('carrier_id',$this->user->carrier_id)->where('prefix',$this->user->prefix)->get();;
        $gamePlatPoints           = [];

        foreach ($carrierGamePlats as $key => $value) {
            $gamePlatPoints[$value->game_plat_id] = $value->point;
        }
        $user =  $this->user;

        $allPlayerIds      = Player::where('parent_id',$this->user->player_id)->where('win_lose_agent',0)->pluck('player_id')->toArray();
        $allPlayerOne      = Player::where('rid','like',$this->user->rid.'|%')->get();
        $allPlayerTwoIds      = [];
        foreach ($allPlayerOne as $key => $value) {
            foreach ($allPlayerIds as $key1 => $value1) {
                if(strstr($value->rid,strval($value1)) && $value->player_id!=$value1){
                    $allPlayerTwoIds[] = $value->player_id;
                }
            }
        }
        $allPlayerIds[] = $this->user->player_id;
        $allPlayerIds   = array_merge($allPlayerIds,$allPlayerTwoIds);

        $playerBetFlowMiddles     = PlayerBetFlowMiddle::select('game_category','main_game_plat_id',\DB::raw('sum(company_win_amount) as company_win_amount'))->whereIn('player_id',$allPlayerIds)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->groupBy('main_game_plat_id','game_category')->get(); 

        foreach ($playerBetFlowMiddles as $key => $value) {
            $row        = [];
            switch ($value->game_category) {
                case '1':
                    $row['gameCategory'] = config('language')[$this->language]['text15'];
                    break;
                case '2':
                    $row['gameCategory'] = config('language')[$this->language]['text16'];
                    break;
                case '3':
                    $row['gameCategory'] = config('language')[$this->language]['text17'];
                    break;
                case '4':
                    $row['gameCategory'] = config('language')[$this->language]['text19'];
                    break;
                case '5':
                    $row['gameCategory'] = config('language')[$this->language]['text20'];
                    break;
                case '6':
                    $row['gameCategory'] = config('language')[$this->language]['text14'];
                    break;
                case '7':
                    $row['gameCategory'] = config('language')[$this->language]['text18'];
                    break;
                
                default:
                    // code...
                    break;
            }

            $row['platformName']  = $gamePlatNames[$value->main_game_plat_id];
            $row['winloss']       = $value->company_win_amount;
            $row['gamePlatPoint'] = $gamePlatPoints[$value->main_game_plat_id];

            $row['fee']           = $row['winloss'] >0 ? bcdiv($row['winloss']*$row['gamePlatPoint'],100,2):0;
            $data[]               = $row;
        } 

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, $data);      
    }

    public function directFeesList()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;
        $bonusRate      = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'bonus_rate',$this->user->prefix);

        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error380'], 0);
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            return $this->returnApiJson(config('language')[$this->language]['error381'], 0);
        }

        $query            = PlayerTransfer::where('parent_id',$this->user->player_id)->whereIn('type',config('main')['giftdeduction'])->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])));

        if(isset($input['type']) && in_array($input['type'],config('main')['giftdeduction'])){
            $query->where('type',$input['type']);
        }

        $total            = $query->count();
        $data             = $query->skip($offset)->take($pageSize)->get();

        foreach ($data as $key => &$value) {
            $value->day    = date('Y-m-d',strtotime($value->day));
            $value->amount = bcdiv($value->amount,10000,2);
            $value->rate   = $bonusRate;
            $value->fee    = bcdiv($value->amount*$value->rate,100,2);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);

    }

    public function mydata()

    {
        $input = request()->all();
        if(!isset($input['startDate']) || !strtotime($input['startDate'])){
            $input['startDate'] = date('Y-m-d');
        }

        if(!isset($input['endDate']) || !strtotime($input['endDate'])){
            $input['endDate'] = date('Y-m-d');
        }
        $noWalletPassageRate   = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'no_wallet_passage_rate',$this->user->prefix);
        $walletPassageRate     = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'wallet_passage_rate',$this->user->prefix);
        $addDirectNumber       = Player::where('parent_id',$this->user->player_id)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->count();
        $firstRechargeNubmer   = PlayerDepositPayLog::where('rid','like',$this->user->rid.'|%')->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->where('is_first_recharge',1)->where('status',1)->count();
        $rechargeNubmer        = PlayerDepositPayLog::where('rid','like',$this->user->rid.'|%')->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->where('status',1)->count();
        $arrivedAmount         = PlayerDepositPayLog::where('rid','like',$this->user->rid.'|%')->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->where('status',1)->sum('arrivedamount');

        $noWalletChargeAmount  = PlayerDepositPayLog::where('is_wallet_recharge',0)->where('status',1)->where('rid','like',$this->user->rid.'|%')->where('review_time','>=',strtotime($input['startDate']))->where('review_time','<',strtotime($input['endDate'])+86400)->sum('amount');
        $walletChargeAmount    = PlayerDepositPayLog::where('is_wallet_recharge',1)->where('status',1)->where('rid','like',$this->user->rid.'|%')->where('review_time','>=',strtotime($input['startDate']))->where('review_time','<',strtotime($input['endDate'])+86400)->sum('amount');

        if($noWalletChargeAmount){
            $arrivedAmount -= bcdiv($noWalletChargeAmount*$noWalletPassageRate,100,0);
        }

        if($walletChargeAmount){
            $arrivedAmount -= bcdiv($walletChargeAmount*$walletPassageRate,100,0);
        }

        $playerDepositPerson        = PlayerDepositPayLog::where('status',1)->where('rid','like',$this->user->rid.'|%')->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->pluck('player_id')->toArray();
        $arrivedFirstAmount         = PlayerDepositPayLog::where('is_first_recharge',1)->where('rid','like',$this->user->rid.'|%')->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->where('status',1)->sum('arrivedamount');
        $noWalletChargeFirstAmount  = PlayerDepositPayLog::where('is_wallet_recharge',0)->where('is_first_recharge',1)->where('status',1)->where('rid','like',$this->user->rid.'|%')->where('review_time','>=',strtotime($input['startDate']))->where('review_time','<',strtotime($input['endDate'])+86400)->sum('amount');
        $walletChargeFirstAmount    = PlayerDepositPayLog::where('is_wallet_recharge',1)->where('is_first_recharge',1)->where('status',1)->where('rid','like',$this->user->rid.'|%')->where('review_time','>=',strtotime($input['startDate']))->where('review_time','<',strtotime($input['endDate'])+86400)->sum('amount');

        if($noWalletChargeFirstAmount){
            $arrivedFirstAmount -= bcdiv($noWalletChargeFirstAmount*$noWalletPassageRate,100,0);
        }

        if($walletChargeFirstAmount){
            $arrivedFirstAmount -= bcdiv($walletChargeFirstAmount*$walletPassageRate,100,0);
        }

        //提现
        $playerWithdrawAmount = PlayerWithdraw::whereIn('status',[1,2])->where('rid','like',$this->user->rid.'|%')->where('updated_at','>=',$input['startDate'].' 00:00:00')->where('updated_at','<=',$input['endDate'].' 23:59:59')->sum('amount');

        $performance          = PlayerBetFlowMiddle::where('prefix',$this->user->prefix)->where('whether_recharge',1)->where('rid','like',$this->user->rid.'|%')->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->sum('agent_process_available_bet_amount');

        //直属业绩
        $directPerformance          = PlayerBetFlowMiddle::where('prefix',$this->user->prefix)->where('whether_recharge',1)->where('parent_id',$this->user->player_id)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->sum('agent_process_available_bet_amount');

        //有效投注
        $selfPerformance = PlayerBetFlowMiddle::where('prefix',$this->user->prefix)->where('whether_recharge',1)->where('player_id',$this->user->player_id)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->sum('process_available_bet_amount');

        if(date('Ymd') == date('Ymd',strtotime($input['endDate']))){
            $notIssuedPlayerCommission =  PlayerRealCommission::where('player_id',$this->user->player_id)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->first();

            if(isset($notIssuedPlayerCommission)){
                //我的佣金
                $myCommission              = $notIssuedPlayerCommission->amount;
                $directCommission          = $notIssuedPlayerCommission->directlyunder_casino_commission + $notIssuedPlayerCommission->directlyunder_electronic_commission+ $notIssuedPlayerCommission->directlyunder_esport_commission+ $notIssuedPlayerCommission->directlyunder_fish_commission+ $notIssuedPlayerCommission->directlyunder_card_commission+ $notIssuedPlayerCommission->directlyunder_sport_commission+ $notIssuedPlayerCommission->directlyunder_lottery_commission;
            } else{
                $myCommission              = 0;
                $directCommission          = 0;
            }

        } else{
            $issuedPlayerCommission    =  PlayerCommission::where('player_id',$this->user->player_id)->where('day','>=',date('Ymd',strtotime($input['startDate'])))->where('day','<=',date('Ymd',strtotime($input['endDate'])))->first();

            if(isset($issuedPlayerCommission)){
                //我的佣金
                $myCommission              = $issuedPlayerCommission->amount;
                $directCommission          = $issuedPlayerCommission->directlyunder_casino_commission + $issuedPlayerCommission->directlyunder_electronic_commission+ $issuedPlayerCommission->directlyunder_esport_commission+ $issuedPlayerCommission->directlyunder_fish_commission+ $issuedPlayerCommission->directlyunder_card_commission+ $issuedPlayerCommission->directlyunder_sport_commission+ $issuedPlayerCommission->directlyunder_lottery_commission;
            } else{
                $myCommission              = 0;
                $directCommission          = 0;
            }
        }

        $data                               = [];
        $data['activepersonnumber']         = count($playerDepositPerson);
        $data['addDirectNumber']            = $addDirectNumber;
        $data['firstRechargeNubmer']        = $firstRechargeNubmer;
        $data['rechargeNubmer']             = $rechargeNubmer;
        $data['playerWithdrawAmount']       = $playerWithdrawAmount;
        $data['arrivedAmount']              = $arrivedAmount;
        $data['arrivedFirstAmount']         = $arrivedFirstAmount;
        $data['performance']                = $performance;
        $data['selfPerformance']            = $selfPerformance;
        $data['myCommission']               = $myCommission;
        $data['directPerformance']          = $directPerformance;
        $data['directCommission']           = $directCommission;
        $data['teamPerformance']            = $performance -  $directPerformance;
        $data['teamCommission']             = $myCommission - $directCommission ;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function overview()
    {
        //我的团队
        $totalPersons          = Player::where('rid','like',$this->user->rid.'|%')->count();
        $directPersons         = Player::where('parent_id',$this->user->player_id)->count();
        $directPersonPlayerIds = Player::where('parent_id',$this->user->player_id)->pluck('player_id')->toArray();
        $otherPersons          = $totalPersons - $directPersons;

        //我的业绩
        $totalPerformance   = PlayerBetFlowMiddle::where('prefix',$this->user->prefix)->where('whether_recharge',1)->where('rid','like',$this->user->rid.'|%')->sum('agent_process_available_bet_amount');
        $directPerformance  = PlayerBetFlowMiddle::where('prefix',$this->user->prefix)->where('whether_recharge',1)->where('parent_id',$this->user->player_id)->sum('agent_process_available_bet_amount');
        $otherPerformance   = $totalPerformance - $directPerformance;

        //我的佣金
        $totalCommission         = PlayerCommission::where('player_id',$this->user->player_id)->sum('amount');
        $directPlayerCommission  = PlayerCommission::select(\DB::raw('sum(directlyunder_casino_commission) as directlyunder_casino_commission'),\DB::raw('sum(directlyunder_electronic_commission) as directlyunder_electronic_commission'),\DB::raw('sum(directlyunder_esport_commission) as directlyunder_esport_commission'),\DB::raw('sum(directlyunder_fish_commission) as directlyunder_fish_commission'),\DB::raw('sum(directlyunder_card_commission) as directlyunder_card_commission'),\DB::raw('sum(directlyunder_sport_commission) as directlyunder_sport_commission'),\DB::raw('sum(directlyunder_lottery_commission) as directlyunder_lottery_commission'))->where('player_id',$this->user->player_id)->first();

        $directCommission  = 0;
        if($directPlayerCommission && $directPlayerCommission->directlyunder_casino_commission){
            $directCommission += $directPlayerCommission->directlyunder_casino_commission;
        }

        if($directPlayerCommission && $directPlayerCommission->directlyunder_electronic_commission){
            $directCommission += $directPlayerCommission->directlyunder_electronic_commission;
        }

        if($directPlayerCommission && $directPlayerCommission->directlyunder_esport_commission){
            $directCommission += $directPlayerCommission->directlyunder_esport_commission;
        }

        if($directPlayerCommission && $directPlayerCommission->directlyunder_fish_commission){
            $directCommission += $directPlayerCommission->directlyunder_fish_commission;
        }

        if($directPlayerCommission && $directPlayerCommission->directlyunder_card_commission){
            $directCommission += $directPlayerCommission->directlyunder_card_commission;
        }

        if($directPlayerCommission && $directPlayerCommission->directlyunder_sport_commission){
            $directCommission += $directPlayerCommission->directlyunder_sport_commission;
        }

        if($directPlayerCommission && $directPlayerCommission->directlyunder_lottery_commission){
            $directCommission += $directPlayerCommission->directlyunder_lottery_commission;
        }

        $otherCommission  = $totalCommission - $directCommission;

        //直属充值金额
        $directRechargeAmount = PlayerTransfer::where('prefix',$this->user->prefix)->where('parent_id',$this->user->player_id)->where('type','recharge')->sum('amount');

        //直属提现金额
        $directWithdrawAmount = PlayerTransfer::where('prefix',$this->user->prefix)->where('parent_id',$this->user->player_id)->where('type','withdraw_finish')->sum('amount');

        //团队人数
        $allPersons           = Player::where('prefix',$this->user->prefix)->where('rid','like',$this->user->rid.'|%')->count();
        $teamPersons          = $allPersons - $directPersons;

        //团队充值金额
        $teamRechargeAmount    = PlayerTransfer::where('prefix',$this->user->prefix)->where('type','recharge')->where('rid','like',$this->user->rid.'|%')->whereNotIn('player_id',$directPersonPlayerIds)->sum('amount');

        //团队提现金额 
        $teamWithdrawAmount    = PlayerTransfer::where('prefix',$this->user->prefix)->where('type','withdraw_finish')->whereNotIn('player_id',$directPersonPlayerIds)->where('rid','like',$this->user->rid.'|%')->sum('amount');

        $data                               = [];
        $data['totalPersons']               = $totalPersons;
        $data['directPersons']              = $directPersons;
        $data['otherPersons']               = $otherPersons;

        $data['totalPerformance']           = $totalPerformance;
        $data['directPerformance']          = $directPerformance;
        $data['otherPerformance']           = $otherPerformance;

        $data['totalCommission']           = $totalCommission;
        $data['directPlayerCommission']    = strval($directCommission);
        $data['otherCommission']           = strval($otherCommission);


        $data['directRechargeAmount']      = $directRechargeAmount;
        $data['directWithdrawAmount']      = $directWithdrawAmount;
        $data['teamPersons']               = $teamPersons;
        $data['teamRechargeAmount']        = $teamRechargeAmount;
        $data['teamWithdrawAmount']        = $teamWithdrawAmount;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function alldata()
    {   
        $input                 = request()->all();
        $data                  = [];
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);

        switch ($playerDividendsMethod) {
            case '2':
                break;
            case '3':
                $data = DevidendMode3::myTeam($input,$this->user);
                break;
            case '4':
                $data = DevidendMode4::myTeam($input,$this->user);
                break;
            case '5':
                $data = DevidendMode5::myTeam($input,$this->user);
                break;
            
            default:
                // code...
                break;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function myPerformance()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query = PlayerBetFlowMiddle::select('day',\DB::raw('sum(agent_process_available_bet_amount) as total_available_bet_amount'))->where('rid','like',$this->user->rid.'|%')->where('whether_recharge',1)->groupBy('day')->orderBy('day','desc');
        $query1 = PlayerBetFlowMiddle::select('day',\DB::raw('sum(agent_process_available_bet_amount) as under_available_bet_amount'))->where('parent_id',$this->user->player_id)->where('whether_recharge',1)->groupBy('day');

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $query1->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $query1->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $items1     = $query1->get();
        $total      = count($query->get());
        $items      = $query->skip($offset)->take($pageSize)->get();

        $dayArr     = [];
        foreach ($items1 as $key => $value) {
            $dayArr[$value->day] = $value->under_available_bet_amount;
        }

        foreach ($items as $k => &$v) {
            if(isset($dayArr[$v->day])){
                $v->under_available_bet_amount = $dayArr[$v->day];
            } else{
                $v->under_available_bet_amount = 0;
            }
            $v->other_available_bet_amount     = $v->total_available_bet_amount - $v->under_available_bet_amount;
            $v->day                            = date('Y-m-d',strtotime($v->day));
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function myCommission()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query = PlayerCommission::select('day',\DB::raw('sum(amount) as amount'),\DB::raw('sum(directlyunder_casino_commission) as directlyunder_casino_commission'),\DB::raw('sum(directlyunder_electronic_commission) as directlyunder_electronic_commission'),\DB::raw('sum(directlyunder_esport_commission) as directlyunder_esport_commission'),\DB::raw('sum(directlyunder_fish_commission) as directlyunder_fish_commission'),\DB::raw('sum(directlyunder_card_commission) as directlyunder_card_commission'),\DB::raw('sum(directlyunder_sport_commission) as directlyunder_sport_commission'),\DB::raw('sum(directlyunder_lottery_commission) as directlyunder_lottery_commission'))->where('player_id',$this->user->player_id)->groupBy('day')->orderBy('day','desc');
        

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total      = count($query->get());
        $items      = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $k => &$v) {
            $v->under_commisson                = $v->directlyunder_casino_commission + $v->directlyunder_electronic_commission+ $v->directlyunder_esport_commission+ $v->directlyunder_fish_commission+ $v->directlyunder_card_commission+ $v->directlyunder_sport_commission+ $v->directlyunder_lottery_commission;
            $v->other_commisson                = $v->amount - $v->under_commisson;
            $v->day                            = date('Y-m-d',strtotime($v->day));
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function underData()
    {
        $input                 = request()->all();
        $data                  = [];
        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'player_dividends_method',$this->user->prefix);

        switch ($playerDividendsMethod) {
            case '2':
                
                break;
            case '3':
                $data = DevidendMode3::myDirectlyunder($input,$this->user);
                break;
            case '4':
                $data = DevidendMode4::myDirectlyunder($input,$this->user);
                break;
            case '5':
                $data = DevidendMode5::myDirectlyunder($input,$this->user);
                break;
            
            default:
                // code...
                break;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function underBetflows()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $sonPlayerIds   = Player::where('parent_id',$this->user->player_id)->pluck('player_id')->toArray();


        $query = PlayerBetFlow::select('player_id',\DB::raw('sum(company_win_amount) as company_win_amount'),\DB::raw('count(game_id) as number'))->whereIn('player_id',$sonPlayerIds)->groupBy('player_id');

        if(isset($input['player_id']) && !empty($input['player_id'])){
            if(strlen($input['player_id'])==8){
                $query->where('player_id',$input['player_id']);
            }else{
                $currPlayer = Player::where('carrier_id',$this->user->carrier_id)->where('prefix',$this->user->prefix)->where('extend_id',$input['player_id'])->first();
                if($currPlayer){
                    $query->where('player_id',$currPlayer->player_id);
                } else{
                    $query->where('player_id','');
                }
            }
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total      = count($query->get());
        $items      = $query->skip($offset)->take($pageSize)->get();

        $playerIds  = [];
        foreach ($items as $key => $value) {
            $playerIds[] = $value->player_id;
        }

        $playerGrades                  = CarrierPlayerGrade::where('carrier_id',$this->user->carrier_id)->orderBy('sort','asc')->get();
        $levelNameArr                  = [];

        foreach ($playerGrades as $key => $value) {
            $levelNameArr[$value->id] = $value->level_name;
        }

        $players   = Player::whereIn('player_id',$playerIds)->get();
        $playerArr = [];
        $playerLevels = [];
        foreach ($players as $key => $value) {
            $playerArr[$value->player_id] = $value->descendantscount;
            $playerLevels[$value->player_id] = $value->player_level_id;
        }

        foreach ($items as $k => &$v) {
            $v->descendantscount = $playerArr[$v->player_id];
            $v->level            = $levelNameArr[$playerLevels[$v->player_id]];
            $v->extend_id        = PlayerCache::getExtendIdByplayerId($this->user->carrier_id,$v->player_id);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function underBetflowsDesc()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $sonPlayerIds   = Player::where('parent_id',$this->user->player_id)->pluck('player_id')->toArray();

        if(!isset($input['player_id']) || empty($input['player_id']) || !in_array($input['player_id'], $sonPlayerIds)){
            return $this->returnApiJson(config('language')[$this->language]['error357'], 0);
        }

        $query          = PlayerBetFlow::select('player_id','available_bet_amount','game_category','main_game_plat_code','game_name','company_win_amount','bet_time')->where('player_id',$input['player_id']);

        if(isset($input['main_game_plat_code']) && !empty($input['main_game_plat_code'])){
            $query->where('main_game_plat_code',$input['main_game_plat_code']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total      = $query->count();
        $items      = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $k => &$v) {
            $v->bet_time = date('Y-m-d H:i:s',$v->bet_time);
        }

        $mainGamePlats  = MainGamePlat::whereNotIn('main_game_plat_code',['ky1','cq95','jdb5','fc5','pp5','jp5','habanero5','jili5','jp6','pp6','cq97','pp7','jp7','habanero7','fc7','jdb7','jili7','cq98','pp8','jp8','habanero8','fc8','jdb8','jili8','cq99','pp9','jp9','habanero9','fc9','jdb9','jili9'])->get();
        $data           = [];

        foreach ($mainGamePlats as $key => $value) {
            $data[$value->main_game_plat_code] = $value->alias;
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['plats'=>$data,'items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function voucherList()
    {
        $res = PlayerHoldGiftCode::voucherList($this->user);
        if(is_array($res)){
            return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$res);
        } else{
            return $this->returnApiJson($res, 0);
        }
    }

    public function underReceive()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $sonPlayerIds   = Player::where('parent_id',$this->user->player_id)->pluck('player_id')->toArray();

        $query          = PlayerTransfer::select('player_id')->whereIn('player_id',$sonPlayerIds)->groupBy('player_id')->orderBy('day','desc');

        if(isset($input['player_id']) && !empty($input['player_id'])){
            if(strlen($input['player_id'])==8){
                $query->where('player_id',$input['player_id']);
            }else{
                $playerId = PlayerCache::getPlayerIdByExtentId($this->user->prefix,$input['player_id']);
                if($playerId){
                    $query->where('player_id',$playerId);
                } else{
                    return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['items' => [], 'total' => 0, 'currentPage' => 1, 'totalPage' => 1]);
                }
            }
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total      = count($query->get());
        $items      = $query->skip($offset)->take($pageSize)->get();

        $playerIds  = [];
        foreach ($items as $key => $value) {
            $playerIds[] = $value->player_id;
        }

        $temPlayers         = Player::whereIn('player_id',$playerIds)->get();
        $playerSubordinates = [];
        foreach ($temPlayers as $key => $value) {
            $playerSubordinates[$value->player_id] = $value->descendantscount;
        }

        $query   = PlayerTransfer::whereIn('player_id',$playerIds)->whereIn('type',config('main')['giftadd']);

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $playerTransfer =$query->get();

        $playerGiftArr       =[];
        $playerCommissionArr =[];

        foreach ($playerTransfer as $key => $value) {
            if($value->type=='commission_from_child'){
                if(isset($playerCommissionArr[$value->player_id])){
                    $playerCommissionArr[$value->player_id] += $value->amount;
                } else{
                    $playerCommissionArr[$value->player_id] = $value->amount;
                }
            } else{
                if(isset($playerGiftArr[$value->player_id])){
                    $playerGiftArr[$value->player_id] += $value->amount;
                } else{
                    $playerGiftArr[$value->player_id] = $value->amount;
                }
            }
        }

        foreach ($items as $k => &$v) {
            if(isset($playerGiftArr[$v->player_id])){
                $v->giftAmount = $playerGiftArr[$v->player_id];
            } else{
                $v->giftAmount = 0;
            }

            if(isset($playerCommissionArr[$v->player_id])){
                $v->commissionAmount = $playerCommissionArr[$v->player_id];
            } else{
                $v->commissionAmount = 0;
            }
            $v->totalAmount      = $v->giftAmount + $v->commissionAmount;
            $v->descendantscount = $playerSubordinates[$v->player_id];
            $v->extend_id        = PlayerCache::getExtendIdByplayerId($this->user->carrier_id,$v->player_id);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function underFinance()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query        = Player::select('player_id','descendantscount')->where('parent_id',$this->user->player_id)->orderBy('player_id','asc');

        if(isset($input['player_id']) && !empty($input['player_id'])){
            if(strlen($input['player_id'])==8){
                $query->where('player_id',$input['player_id']);
            }else{
                $query->where('extend_id',$input['player_id']);
            }
        }

        $total      = $query->count();
        $items      = $query->skip($offset)->take($pageSize)->get();

        $playerIds = [];
        foreach ($items as $key => $value) {
            $playerIds[] = $value->player_id;
        }

        $rechargeQuery = PlayerTransfer::select('player_id',\DB::raw('sum(amount) as amount'))->whereIn('player_id',$playerIds)->where('type','recharge');
        $withdrawQuery = PlayerTransfer::select('player_id',\DB::raw('sum(amount) as amount'))->whereIn('player_id',$playerIds)->where('type','withdraw_finish');
        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $rechargeQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
            $withdrawQuery->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $rechargeQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
            $withdrawQuery->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $recharges       = $rechargeQuery->groupBy('player_id')->get();
        $withdraws       = $withdrawQuery->groupBy('player_id')->get();
        $rechargePlayers = [];

        foreach ($recharges as $key => $value) {
            $rechargePlayers[$value->player_id] = $value->amount;
        }

        $withdrawPlayers = [];
        foreach ($withdraws as $key => $value) {
            $withdrawPlayers[$value->player_id] = $value->amount;
        }

        $balances        = PlayerAccount::whereIn('player_id',$playerIds)->get();
        $balancePlayers  = [];

        foreach ($balances as $key => $value) {
            $balancePlayers[$value->player_id] = $value->frozen + $value->balance;
        }

        foreach ($items as $k => &$v) {
            if(isset($rechargePlayers[$v->player_id])){
                $v->rechargeAmount = $rechargePlayers[$v->player_id];
            } else{
                $v->rechargeAmount = 0;
            }

            if(isset($withdrawPlayers[$v->player_id])){
                $v->withdrawAmount = $withdrawPlayers[$v->player_id];
            } else{
                $v->withdrawAmount = 0;
            }
            $v->balance   = $balancePlayers[$v->player_id];
            $v->extend_id = PlayerCache::getExtendIdByplayerId($this->user->carrier_id,$v->player_id);
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['items' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function selectRebate()
    {
        $enableBetGradientRebate    = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'enable_bet_gradient_rebate',$this->user->prefix);
        if(!$enableBetGradientRebate){
            return $this->returnApiJson(config('language')[$this->language]['error399'], 0);
        }

        $videoBetGradientRebate     = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'video_bet_gradient_rebate',$this->user->prefix);
        $eleBetGradientRebate       = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'ele_bet_gradient_rebate',$this->user->prefix);
        $esportBetGradientRebate    = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'esport_bet_gradient_rebate',$this->user->prefix);
        $cardBetGradientRebate      = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'card_bet_gradient_rebate',$this->user->prefix);
        $sportBetGradientRebate     = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sport_bet_gradient_rebate',$this->user->prefix);
        $fishBetGradientRebate      = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'fish_bet_gradient_rebate',$this->user->prefix);
        $lottBetGradientRebate      = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'lott_bet_gradient_rebate',$this->user->prefix);
        $videoBetGradientRebate     = json_decode($videoBetGradientRebate,true);
        $eleBetGradientRebate       = json_decode($eleBetGradientRebate,true);
        $esportBetGradientRebate    = json_decode($esportBetGradientRebate,true);
        $cardBetGradientRebate      = json_decode($cardBetGradientRebate,true);
        $sportBetGradientRebate     = json_decode($sportBetGradientRebate,true);
        $fishBetGradientRebate      = json_decode($fishBetGradientRebate,true);
        $lottBetGradientRebate      = json_decode($lottBetGradientRebate,true);
            
        $notReturnWater      = [
            '1' => ['game_category'=>1,'bonusRate'=>0,'returnWaterAmount'=>0,'availableBetFlow'=>0],
            '2' => ['game_category'=>2,'bonusRate'=>0,'returnWaterAmount'=>0,'availableBetFlow'=>0],
            '3' => ['game_category'=>3,'bonusRate'=>0,'returnWaterAmount'=>0,'availableBetFlow'=>0],
            '4' => ['game_category'=>4,'bonusRate'=>0,'returnWaterAmount'=>0,'availableBetFlow'=>0],
            '5' => ['game_category'=>5,'bonusRate'=>0,'returnWaterAmount'=>0,'availableBetFlow'=>0],
            '6' => ['game_category'=>6,'bonusRate'=>0,'returnWaterAmount'=>0,'availableBetFlow'=>0],
            '7' => ['game_category'=>7,'bonusRate'=>0,'returnWaterAmount'=>0,'availableBetFlow'=>0]
        ];

        $bonusRate                  = ['1' => 0,'2' => 0,'3' => 0,'4' => 0,'5' => 0,'6' => 0,'7' => 0];
        $availableBetFlow           = ['1' => 0,'2' => 0,'3' => 0,'4' => 0,'5' => 0,'6' => 0,'7' => 0];

        $playerDayBetFlowMiddles = PlayerBetFlowMiddle::select('game_category',\DB::raw('sum(process_available_bet_amount) as process_available_bet_amount'))->where('player_id',$this->user->player_id)->where('day',date('Ymd'))->groupBy('game_category')->get();

        foreach ($playerDayBetFlowMiddles as $key => $value) {
            switch ($value->game_category) {
                case '1':
                    foreach ($videoBetGradientRebate as $k1 => $v1) {
                        if($value->available_bet_amount >$v1['probability']){
                            $bonusRate['1']         = $v1['bonus'];
                            $availableBetFlow['1']  = $value->available_bet_amount;
                        }
                    }
                    break;
                case '2':
                    foreach ($eleBetGradientRebate as $k1 => $v1) {
                        if($value->available_bet_amount >$v1['probability']){
                            $bonusRate['2']         = $v1['bonus'];
                            $availableBetFlow['2']  = $value->available_bet_amount;
                        }
                    }
                    break;
                case '3':
                    foreach ($esportBetGradientRebate as $k1 => $v1) {
                        if($value->available_bet_amount >$v1['probability']){
                            $bonusRate['3']   = $v1['bonus'];
                            $availableBetFlow['3']  = $value->available_bet_amount;
                        }
                    }
                    break;
                case '4':
                    foreach ($cardBetGradientRebate as $k1 => $v1) {
                        if($value->available_bet_amount >$v1['probability']){
                            $bonusRate['4']         = $v1['bonus'];
                            $availableBetFlow['4']  = $value->available_bet_amount;
                        }
                    }
                    break;
                case '5':
                    foreach ($sportBetGradientRebate as $k1 => $v1) {
                        if($value->available_bet_amount >$v1['probability']){
                            $bonusRate['5']         = $v1['bonus'];
                            $availableBetFlow['5']  = $value->available_bet_amount;
                        }
                    }
                    break;
                case '6':
                    foreach ($lottBetGradientRebate as $k1 => $v1) {
                        if($value->available_bet_amount >$v1['probability']){
                            $bonusRate['6']         = $v1['bonus'];
                            $availableBetFlow['6']  = $value->available_bet_amount;
                        }
                    }
                    break;
                case '7':
                    foreach ($fishBetGradientRebate as $k1 => $v1) {
                        if($value->available_bet_amount >$v1['probability']){
                            $bonusRate['7']         = $v1['bonus'];
                            $availableBetFlow['7']  = $value->available_bet_amount;
                        }
                    }
                    break;
                default:
                    break;
            }
        }

        $data['totalamount'] = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','commission_from_self')->sum('amount');

        $categoryAmountLists = PlayerMiddleReturnWater::select(\DB::raw('sum(amount) as amount'),'game_category')->where('player_id',$this->user->player_id)->where('status',0)->groupBy('game_category')->get();
        $data['availableAmount'] = 0;
        foreach ($categoryAmountLists as $key => $value) {
            $notReturnWater[$value->game_category]['returnWaterAmount'] = $value->amount;
            $notReturnWater[$value->game_category]['bonusRate']         = $bonusRate[$value->game_category];
            $notReturnWater[$value->game_category]['availableBetFlow']  = $availableBetFlow[$value->game_category];
            $data['availableAmount'] += $value->amount;
        }

        $data['notReturnWater'] = $notReturnWater;
        $latelyReturnWater      =  PlayerTransfer::where('player_id',$this->user->player_id)->where('type','commission_from_self')->orderBy('id','desc')->first();

        if($latelyReturnWater){
            $data['latelyReturnWater']['date']   = date('Y-m-d',strtotime($latelyReturnWater->created_at));
            $data['latelyReturnWater']['amount'] = $latelyReturnWater->amount;
        } else{
            $data['latelyReturnWater'] = [];
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function rebateProportion()
    {
        $data                            = [];   
        $videoBetGradientRebate          = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'video_bet_gradient_rebate',$this->user->prefix);
        $eleBetGradientRebate            = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'ele_bet_gradient_rebate',$this->user->prefix);
        $esportBetGradientRebate         = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'esport_bet_gradient_rebate',$this->user->prefix);
        $cardBetGradientRebate           = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'card_bet_gradient_rebate',$this->user->prefix);
        $sportBetGradientRebate          = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sport_bet_gradient_rebate',$this->user->prefix);
        $fishBetGradientRebate           = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'fish_bet_gradient_rebate',$this->user->prefix);
        $lottBetGradientRebate           = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'lott_bet_gradient_rebate',$this->user->prefix);

        $data['videoBetGradientRebate']  = json_decode($videoBetGradientRebate,true);
        $data['eleBetGradientRebate']    = json_decode($eleBetGradientRebate,true);
        $data['esportBetGradientRebate'] = json_decode($esportBetGradientRebate,true);
        $data['cardBetGradientRebate']   = json_decode($cardBetGradientRebate,true);
        $data['sportBetGradientRebate']  = json_decode($sportBetGradientRebate,true);
        $data['fishBetGradientRebate']   = json_decode($fishBetGradientRebate,true);
        $data['lottBetGradientRebate']   = json_decode($lottBetGradientRebate,true);
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function rebateHistory()
    {
        $input          = request()->all();
        $currentPage    = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize       = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset         = ($currentPage - 1) * $pageSize;

        $query          = PlayerTransfer::where('player_id',$this->user->player_id)->where('type','commission_from_self')->orderBy('id','desc');

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }        

        $total          = $query->count();
        $data           = $query->skip($offset)->take($pageSize)->get();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['data' => $data, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function getRebate()
    {
        $rebateMethod = CarrierCache::getCarrierConfigure($this->user->carrier_id,'rebate_method',$this->user->prefix);
        if($rebateMethod){
            return $this->returnApiJson(config('language')[$this->language]['error400'], 0);
        }
        $time       = time();
        $v          = $this->user->player_id;
        $cacheKey   = "player_" .$v;
        $redisLock = Lock::addLock($cacheKey,60);

        if (!$redisLock) {
            $this->returnApiJson(config('language')[$this->language]['error20'],0);
        } else {
            $playerTypes = PlayerMiddleReturnWater::select(\DB::raw('sum(amount) as amount'),'game_category')->where('player_id',$v)->where('created_at','<=',date('Y-m-d H:i:s',$time))->where('status',0)->groupBy('game_category')->get();

            if(!count($playerTypes)){
                return $this->returnApiJson(config('language')[$this->language]['error221'], 0);
            }

            foreach ($playerTypes as $t) {
                try {
                    \DB::beginTransaction();

                    $playerAccount                            = PlayerAccount::where('player_id',$v)->lockForUpdate()->first();
                    $playerTransfer                           = new PlayerTransfer();
                    $playerTransfer->type                     = 'commission_from_self';
                    $playerTransfer->type_name                = config('language')['zh']['text184'];
                    $playerTransfer->en_type_name             = config('language')['en']['text184'];
                    $playerTransfer->carrier_id               = $this->user->carrier_id;
                    $playerTransfer->rid                      = $this->user->rid;
                    $playerTransfer->top_id                   = $this->user->top_id;
                    $playerTransfer->parent_id                = $this->user->parent_id;
                    $playerTransfer->player_id                = $this->user->player_id;
                    $playerTransfer->is_tester                = $this->user->is_tester;
                    $playerTransfer->level                    = $this->user->level;
                    $playerTransfer->user_name                = $this->user->user_name;
                    $playerTransfer->mode                     = 1;
                    $playerTransfer->day_m                    = date('Ym',$time);
                    $playerTransfer->day                      = date('Ymd',$time);
                    $playerTransfer->amount                   = $t->amount;
                    $playerTransfer->before_balance           = $playerAccount->balance;
                    $playerTransfer->balance                  = $playerAccount->balance + $t->amount;
                    $playerTransfer->before_frozen_balance    = $playerAccount->frozen;
                    $playerTransfer->frozen_balance           = $playerAccount->frozen;
                    $playerTransfer->game_category            = $t->game_category;
                    $playerTransfer->prefix                   = $this->user->prefix;
                    $playerTransfer->save();

                    //福利中心生成数据
                    $playerReceiveGiftCenter                     = new PlayerReceiveGiftCenter();
                    $playerReceiveGiftCenter->orderid            = 'LJ'.$this->user->player_id.time().rand('1','99');
                    $playerReceiveGiftCenter->carrier_id         = $this->user->carrier_id;
                    $playerReceiveGiftCenter->player_id          = $this->user->player_id;
                    $playerReceiveGiftCenter->user_name          = $this->user->user_name;
                    $playerReceiveGiftCenter->top_id             = $this->user->top_id;
                    $playerReceiveGiftCenter->parent_id          = $this->user->parent_id;
                    $playerReceiveGiftCenter->rid                = $this->user->rid;
                    $playerReceiveGiftCenter->type               = 44;
                    $playerReceiveGiftCenter->amount             = $t->amount;
                    $playerReceiveGiftCenter->invalidtime        = time()+31536000;
                    $playerReceiveGiftCenter->limitbetflow       = $t->amount;
                    $playerReceiveGiftCenter->remark             = $t->game_category;
                    $playerReceiveGiftCenter->status             = 1;
                    $playerReceiveGiftCenter->save();

                    $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id             = $playerAccount->carrier_id;
                    $playerWithdrawFlowLimit->top_id                 = $playerAccount->top_id;
                    $playerWithdrawFlowLimit->parent_id              = $playerAccount->parent_id;
                    $playerWithdrawFlowLimit->rid                    = $playerAccount->rid;
                    $playerWithdrawFlowLimit->player_id              = $playerAccount->player_id;
                    $playerWithdrawFlowLimit->user_name              = $playerAccount->user_name;
                    $playerWithdrawFlowLimit->limit_amount           = $t->amount;
                    $playerWithdrawFlowLimit->limit_type             = 44;
                    $playerWithdrawFlowLimit->save();

                    $playerAccount->balance                   = $playerAccount->balance + $t->amount;
                    $playerAccount->save();

                    PlayerMiddleReturnWater::where('player_id',$v)->where('created_at','<=',date('Y-m-d H:i:s',$time))->where('status',0)->update(['status'=>1]);
                    \DB::commit();
                    Lock::release($redisLock);
                } catch (\Exception $e) {
                    \DB::rollback();
                    Lock::release($redisLock);
                    Clog::recordabnormal('领取返水异常:'.$e->getMessage()); 
                    return $this->returnApiJson(config('language')[$this->language]['error401'], 0);
                }
            }
        }
    }

    public function rebateRatio()
    {
        $enableInviteGradientRebate = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'enable_invite_gradient_rebate',$this->user->prefix);
        $videoInviteGradientRebate  = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'video_invite_gradient_rebate',$this->user->prefix);
        $eleInviteGradientRebate    = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'ele_invite_gradient_rebate',$this->user->prefix);
        $esportInviteGradientRebate = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'esport_invite_gradient_rebate',$this->user->prefix);
        $cardInviteGradientRebate   = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'card_invite_gradient_rebate',$this->user->prefix);
        $sportInviteGradientRebate  = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'sport_invite_gradient_rebate',$this->user->prefix);
        $fishInviteGradientRebate   = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'fish_invite_gradient_rebate',$this->user->prefix);

        if(!$enableInviteGradientRebate){
            return $this->returnApiJson(config('language')[$this->language]['error402'], 0);
        }

        $data                               = [];
        $data['videoInviteGradientRebate']  = json_decode($videoInviteGradientRebate,true);
        $data['eleInviteGradientRebate']    = json_decode($eleInviteGradientRebate,true);
        $data['esportInviteGradientRebate'] = json_decode($esportInviteGradientRebate,true);
        $data['cardInviteGradientRebate']   = json_decode($cardInviteGradientRebate,true);
        $data['sportInviteGradientRebate']  = json_decode($sportInviteGradientRebate,true);
        $data['fishInviteGradientRebate']   = json_decode($fishInviteGradientRebate,true);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function getRealRebate()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $query = PlayerCommission::select('directlyunder_casino_commission','directlyunder_electronic_commission','directlyunder_esport_commission','directlyunder_fish_commission','directlyunder_card_commission','directlyunder_sport_commission','day','amount')->where('player_id',$this->user->player_id);
        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total               = $query->count();
        $items               = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->day = date('Y-m-d',strtotime($value->day));
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function getInvitePlayer()
    {
        $input           = request()->all();
        $currentPage     = isset($input['page_index']) ? intval($input['page_index']) : 1;
        $pageSize        = isset($input['page_size'])  ? intval($input['page_size'])  : config('main')['page_size'];
        $offset          = ($currentPage - 1) * $pageSize;

        $query = Player::select('created_at','login_at','is_online','extend_id','player_id','frozen_status')->where('parent_id',$this->user->player_id);
        if(isset($input['extend_id']) && !empty($input['extend_id'])){
            $query->where('extend_id',$input['extend_id']);
        }

        if(isset($input['startDate']) && strtotime($input['startDate'])){
            $query->where('day','>=',date('Ymd',strtotime($input['startDate'])));
        }

        if(isset($input['endDate']) && strtotime($input['endDate'])){
            $query->where('day','<=',date('Ymd',strtotime($input['endDate'])));
        }

        $total     = $query->count();
        $items     = $query->skip($offset)->take($pageSize)->get();

        foreach ($items as $key => &$value) {
            $value->register_at = date('Y-m-d',strtotime($value->created_at));
            $value->login_at   = date('Y-m-d',strtotime($value->login_at));
            $value->betAmount  = PlayerBetFlowMiddle::where('player_id',$value->player_id)->sum('agent_process_available_bet_amount');
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['data' => $items, 'total' => $total, 'currentPage' => $currentPage, 'totalPage' => intval(ceil($total / $pageSize))]);
    }

    public function allVip()
    {
        $carrierPlayerGrades = CarrierPlayerGrade::select('withdrawcount','updategift','birthgift','level_name','upgrade_rule','weekly_salary','monthly_salary')->where('prefix',$this->prefix)->orderBy('sort','asc')->get();
        foreach ($carrierPlayerGrades as $key => &$value) {
            $upgradeRule         = unserialize($value->upgrade_rule);
            $value->availablebet = $upgradeRule['availablebet'];
        }

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,['data' => $carrierPlayerGrades]);
    }

    public function  setdeDuctionsMethod()
    {
        $input   = request()->all();

        if(!isset($input['deductions_method']) || !in_array($input['deductions_method'],[1,2])){
            return $this->returnApiJson(config('language')[$this->language]['error549'], 0);
        }

        if($this->user->deductions_method!=0){
            return $this->returnApiJson(config('language')[$this->language]['error548'], 0);
        }

        Player::where('parent_id',$this->user->player_id)->update(['self_deductions_method'=>$input['deductions_method']]);
        $this->user->deductions_method=1;
        $this->user->save();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function capitationFeeList()
    {
        $data                                  = [];
        $capitationFeeRechargeAmount           = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'capitation_fee_recharge_amount',$this->user->prefix);
        $capitationFeeBetFlow                  = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'capitation_fee_bet_flow',$this->user->prefix);
        $capitationFeeDepositDays              = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'capitation_fee_deposit_days',$this->user->prefix);
        $capitationFeeGiftAmount               = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'capitation_fee_gift_amount',$this->user->prefix);
        $capitationFeeSingleRechargeAmount     = CarrierCache::getCarrierMultipleConfigure($this->user->carrier_id,'capitation_fee_single_recharge_amount',$this->user->prefix);


        $data['capitationFeeRechargeAmount']           = $capitationFeeRechargeAmount;
        $data['capitationFeeBetFlow']                  = $capitationFeeBetFlow;
        $data['capitationFeeDepositDays']              = $capitationFeeDepositDays;
        $data['capitationFeeGiftAmount']               = $capitationFeeGiftAmount;
        $data['capitationFeeSingleRechargeAmount']     = $capitationFeeSingleRechargeAmount;
        $data['descendantscount']                      = $this->user->descendantscount;

        $receivedCount  = PlayerCapitationFee::where('prefix',$this->user->prefix)->where('parent_id',$this->user->player_id)->where('status',2)->count();
        $availableCount = PlayerCapitationFee::where('prefix',$this->user->prefix)->where('parent_id',$this->user->player_id)->where('status',1)->count();

        $j              = 1;
        if($receivedCount){
            for ($i=1;$i<=$receivedCount;$i++) {
                $rows           = [];
                $rows['status'] = 2;
                $rows['amount'] = $capitationFeeGiftAmount;
                $rows['number'] = $j;
                $data['items'][]= $rows;
                $j++;
            }
        }

        if($availableCount){
            for ($i=1;$i<=$availableCount;$i++) {
                $rows           = [];
                $rows['status'] = 1;
                $rows['amount'] = $capitationFeeGiftAmount;
                $rows['number'] = $j;
                $data['items'][] = $rows;
                $j++;
            }
        }

        for ($i=$j;$i<=24;$i++) {
            $rows           = [];
            $rows['status'] = 0;
            $rows['amount'] = $capitationFeeGiftAmount;
            $rows['number'] = $j;
            $data['items'][] = $rows;
            $j++;
        }
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function underDesc($playerId)
    {
        $data      = [];
        $underDesc = [];
        $under = Player::where('player_id',$playerId)->first();
        if(!$under){
            return $this->returnApiJson(config('language')[$this->language]['error110'],0);
        }

        if($under->parent_id != $this->user->player_id){
            return $this->returnApiJson(config('language')[$this->language]['error448'],0);
        }

        $userNameArr               = explode('_',$under->user_name);
        $data['user_name']         = $userNameArr[0];
        $data['extend_id']         = $under->extend_id;
        $data['soncount']          = $under->soncount;
        $data['descendantscount']  = $under->descendantscount;
        $data['created_at']        = date('Y-m-d',strtotime($under->created_at)); 
        $data['login_at']          = date('Y-m-d',strtotime($under->login_at));

        $selfRecharge              = PlayerTransfer::where('player_id',$under->player_id)->where('type','recharge')->sum('amount');
        $selfWithdraw              = PlayerTransfer::where('player_id',$under->player_id)->where('type','withdraw_finish')->sum('amount');
        $selfAccount               = PlayerAccount::select('balance','frozen','agentbalance','agentfrozen')->where('player_id',$under->player_id)->first();
        $selfBetflow               = PlayerBetFlowMiddle::where('player_id',$under->player_id)->sum('agent_process_available_bet_amount');

        $data['selfRecharge']      = $selfRecharge ? bcdiv($selfRecharge,10000,2):0.00;
        $data['selfWithdraw']      = $selfWithdraw ? bcdiv($selfWithdraw,10000,2):0.00;
        $data['selfBalance']       = bcdiv($selfAccount->balance + $selfAccount->frozen + $selfAccount->agentbalance + $selfAccount->agentfrozen,10000,2);
        $data['selfBetflow']       = $selfBetflow ? $selfBetflow:0.00;

        $teamRecharge              = PlayerTransfer::where('rid','like',$under->rid.'|%')->where('type','recharge')->sum('amount');
        $teamWithdraw              = PlayerTransfer::where('rid','like',$under->rid.'|%')->where('type','withdraw_finish')->sum('amount');
        $teamAccount               = PlayerAccount::select(\DB::raw('sum(balance) as balance'),\DB::raw('sum(frozen) as frozen'),\DB::raw('sum(agentbalance) as agentbalance'),\DB::raw('sum(agentfrozen) as agentfrozen'))->where('rid','like',$under->rid.'|%')->first();
        $teamBetflow               = PlayerBetFlowMiddle::where('rid','like',$under->rid.'|%')->sum('agent_process_available_bet_amount');

        $data['teamRecharge']      = $teamRecharge ? bcdiv($teamRecharge,10000,2):0.00;
        $data['teamWithdraw']      = $teamWithdraw ? bcdiv($teamWithdraw,10000,2):0.00;
        $data['teamBalance']       = bcdiv($teamAccount->balance + $teamAccount->frozen + $teamAccount->agentbalance + $teamAccount->agentfrozen,10000,2);
        $data['teamBetflow']       = $teamBetflow ? $teamBetflow:0.00;

        $data['totalRecharge']     = $data['selfRecharge'] + $data['teamRecharge'];
        $data['totalithdraw']      = $data['selfWithdraw'] + $data['teamWithdraw'];
        $data['totalBetflow']      = $data['selfBetflow'] + $data['teamBetflow'];

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }
}