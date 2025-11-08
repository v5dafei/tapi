<?php

namespace App\Http\Controllers\Web;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\BaseController;
use Illuminate\Support\Facades\Redis as Redis;
use App\Models\Log\PlayerLogin;
use App\Lib\Cache\CarrierCache;
use App\Models\Player;
use App\Jobs\SignInJob;
use App\Models\Def\SmsPassage;
use App\Models\Log\CarrierSms;
use App\Sms\Sms;
use App\Utils\Client\IP;
use App\Models\Log\PlayerToken;
use App\Models\CarrierPlayerGrade;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerBetFlowMiddle;
use App\Models\Conf\PlayerSetting;
use App\Models\PlayerInviteCode;
use App\Models\Area;
use App\Lib\DevidendMode2;
use App\Models\PlayerAccount;
use App\Models\Map\CarrierGamePlat;
use App\Models\Def\MainGamePlat;
use App\Models\PlayerGameAccount;
use App\Lib\Cache\GameCache;
use App\Game\Game;
use App\Models\Conf\CarrierMultipleFront;
use App\Lib\Cache\PlayerCache;
use App\Lib\Cache\Lock;
use App\Lib\Clog;
use App\Lib\Behavioralcaptcha;

class AuthController extends BaseController
{
    use Authenticatable;

    public function register()
    {
        $isAllowPlayerRegister = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_allow_player_register',$this->prefix);

        if($isAllowPlayerRegister!=1) {
            return $this->returnApiJson(config('language')[$this->language]['error1'], 0);
        }

        $ipBlacklist                       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'ip_blacklist',$this->prefix);

        if(!empty($ipBlacklist)){
            $ipBlacklists = explode(',',$ipBlacklist);
            if(in_array(real_ip(),$ipBlacklists)){
                return $this->returnApiJson(config('language')[$this->language]['error1'], 0);
            }
        }

        $res = Player::register($this->carrier,$this->prefix);
        if($res === true) {
            return $this->returnApiJson(config('language')[$this->language]['error261'], 1);
        } else if($res == 400 ) {
            return $this->returnApiJson(config('language')[$this->language]['error262'], 1,['checkcode'=>400]);
        }else {
            return $this->returnApiJson($res, 0);
        }
    }

    public function forumLogin()
    {
        $input                  = request()->all();
        $isAllowPlayerRegister  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'is_allow_player_register',$this->prefix);
        if(!$isAllowPlayerRegister){
            return $this->returnApiJson(config('language')[$this->language]['error2'], 0);
        }

        $enableRegisterBehavioralVerification = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_login_behavior_verification',$this->prefix);
        if($enableRegisterBehavioralVerification){

            if(!isset($input['dataInfo'])){
                return '对不起,行为验证码参数不正确';
            }

            $behavioral = Behavioralcaptcha::captcha($input);
            $bizCode    = null;

            if(isset($behavioral->Code)){
                $bizCode = $behavioral->Code;
            }

            if(isset($behavioral->BizCode)){
                $bizCode = $behavioral->BizCode;
            }

            if(!isset($bizCode) || $bizCode=='800' || $bizCode=='900'){
                return $this->returnApiJson(config('language')[$this->language]['error541'], 0);
            } else if($bizCode=='400'){
                return $this->returnApiJson(config('language')[$this->language]['error542'], 0);
            }
        }

        
        $loginPlayer        = false;
        $token              = false; 
        $ip                 = real_ip();
        $isexistPlayer      = false;
        $ipBlacklist                       = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'ip_blacklist',$this->prefix);

        if(!empty($ipBlacklist)){
            $ipBlacklists = explode(',',$ipBlacklist);
            if(in_array($ip,$ipBlacklists)){
                return $this->returnApiJson(config('language')[$this->language]['error263'], 0);
            }
        }

        if(!isset($input['user_name']) && empty($input['user_name'])){
            return $this->returnApiJson(config('language')[$this->language]['error84'], 0);
        }

        if(!isset($input['password']) && empty($input['password'])){
            return $this->returnApiJson(config('language')[$this->language]['error85'], 0);
        }

        if(!isset($input['amount']) || !is_numeric($input['amount'])){
            return $this->returnApiJson(config('language')[$this->language]['error264'], 0);
        }

        if((intval($input['amount']) != $input['amount']) || $input['amount']<1){
            return $this->returnApiJson(config('language')[$this->language]['error265'], 0);
        }

        if(!empty($this->user->forum_username) && $this->user->forum_username != $input['user_name']){
            return $this->returnApiJson(config('language')[$this->language]['error266'], 0);
        }

        $existForumUserName = Player::where('forum_username',$input['user_name'])->first();
        if($existForumUserName && $existForumUserName->player_id != $this->user->player_id){
            return $this->returnApiJson(config('language')[$this->language]['error267'], 0);
        }

        //开始调用三方接口
        $url   =  config('main')['forum']['forum1']['url'];
        $param =[
            'token'       => config('main')['forum']['forum1']['token'],
            'username'    => $input['user_name'],
            'password'    => $input['password'],
            'goldNumber'  => $input['amount']*config('main')['forum']['forum1']['upscore'],
        ];

        $ch = curl_init($url); //请求的URL地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param)); //$post_data JSON类型字符串
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
        $output    = curl_exec($ch);
        $error     = curl_error($ch);

        curl_close($ch);

        if (!empty($error)) {
            return $this->returnApiJson(config('language')[$this->language]['error268'].$error, 0);
        } else  {
           $output = json_decode($output,true);
        }

        if($output['code']!=0){
            return $this->returnApiJson(config('language')[$this->language]['error268'].$output['msg'], 0);
        }

        $cacheKey = "player_" .$this->user->player_id;
        $redisLock = Lock::addLock($cacheKey,10);

        if (!$redisLock) {
            return $this->returnApiJson(config('language')[$this->language]['error20'], 0);
        } else {
            try{
                \DB::beginTransaction();
                $this->user->is_forum_user  = 1;
                $this->user->forum_username = $input['user_name'];
                $this->user->save();

                $playerAccount                                  = PlayerAccount::where('player_id',$this->user->player_id)->lockForUpdate()->first();

                $playerTransfer                                  = new PlayerTransfer();
                $playerTransfer->prefix                          = $this->prefix;
                $playerTransfer->carrier_id                      = $playerAccount->carrier_id;
                $playerTransfer->rid                             = $playerAccount->rid;
                $playerTransfer->top_id                          = $playerAccount->top_id;
                $playerTransfer->parent_id                       = $playerAccount->parent_id;
                $playerTransfer->player_id                       = $playerAccount->player_id;
                $playerTransfer->is_tester                       = $playerAccount->is_tester;
                $playerTransfer->level                           = $playerAccount->level;
                $playerTransfer->user_name                       = $playerAccount->user_name;
                $playerTransfer->mode                            = 1;
                $playerTransfer->day_m                           = date('Ym',time());
                $playerTransfer->day                             = date('Ymd',time());
                $playerTransfer->amount                          = $input['amount']*10000;
                $playerTransfer->before_balance                  = $playerAccount->balance;
                $playerTransfer->balance                         = $playerAccount->balance + $playerTransfer->amount;
                $playerTransfer->before_frozen_balance           = $playerAccount->frozen;
                $playerTransfer->frozen_balance                  = $playerAccount->frozen;
                $playerTransfer->before_agent_balance            = $playerAccount->agentbalance;
                $playerTransfer->agent_balance                   = $playerAccount->agentbalance;
                $playerTransfer->before_agent_frozen_balance     = $playerAccount->agentfrozen;
                $playerTransfer->agent_frozen_balance            = $playerAccount->agentfrozen;
                $playerTransfer->type                            = 'forum_up_score';
                $playerTransfer->type_name                       = '论坛上分';
                $playerTransfer->save();

                $playerAccount->balance                          = $playerTransfer->balance;
                $playerAccount->save();
                \DB::commit();
                Lock::release($redisLock);
                return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
            }catch (\Exception $e) {
                \DB::rollBack();
                Lock::release($redisLock);
                Clog::recordabnormal('用户论坛上分异常'.$e->getMessage());
                return $this->returnApiJson(config('language')[$this->language]['error269'].$e->getMessage(), 0);
            }
        }
    }

    public function login()
    {
        $input                     = request()->all();
        $loginPlayer               = false;
        $token                     = false; 
        $ip                        = real_ip();
        $ipBlacklist               = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'ip_blacklist',$this->prefix);
        $loginImgVerification      = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_login_img_verification',$this->prefix);        //


        if(!empty($ipBlacklist)){
            $ipBlacklists = explode(',',$ipBlacklist);
            if(in_array($ip,$ipBlacklists)){
                return $this->returnApiJson(config('language')[$this->language]['error263'], 0);
            }
        }

        if(isset($input['user_name']) && !empty($input['user_name'])){
            $input['user_name_or_mobile'] = $input['user_name'].'_'.$this->prefix;

        } else if(isset($input['mobile']) && !empty($input['mobile'])){
            //手机号解密
            if(!is_numeric($input['mobile'])){
                $code                     = md5('mobile');
                $iv                       = substr($code,0,16);
                $key                      = substr($code,16);
                $input['mobile']          =  openssl_decrypt(base64_decode($input['mobile']), 'AES-128-CBC', $key,1,$iv);
            }
            $input['user_name_or_mobile'] = $input['mobile'];
        } else{
            return $this->returnApiJson(config('language')[$this->language]['error270'], 0);
        }

        $cacheKey   = "player_login_error_" .$input['user_name_or_mobile'];

        if(cache()->get($cacheKey,0)==5){
            return $this->returnApiJson(config('language')[$this->language]['error5'], 0);
        }

        if (!isset($input['user_name_or_mobile']) || empty($input['user_name_or_mobile'])) {
            return $this->returnApiJson(config('language')[$this->language]['error271'], 0);
        }

        if (!isset($input['password']) || empty($input['password'])) {
            return $this->returnApiJson(config('language')[$this->language]['error4'], 0);
        }

         //启用了图形验证码
        if($loginImgVerification==1){
            if(!isset($input['captcha']) && empty($input['captcha'])){

                return $this->returnApiJson('对不起, 验证码不能为空！', 0);
            }

            $ip              = real_ip();
            $captchaKey      = cache()->get(md5($ip));
               
            if(strtolower($input["captcha"]) != strtolower($captchaKey)){
                return $this->returnApiJson('对不起, 验证码不正确！', 0);
            }
        }


        $enableRegisterBehavioralVerification = CarrierCache::getCarrierMultipleConfigure($this->carrier->id, 'enable_login_behavior_verification',$this->prefix);
        if($enableRegisterBehavioralVerification){

            if(!isset($input['dataInfo'])){
                return '对不起,行为验证码参数不正确';
            }

            $behavioral = Behavioralcaptcha::captcha($input);
            $bizCode    = null;

            if(isset($behavioral->Code)){
                $bizCode = $behavioral->Code;
            }

            if(isset($behavioral->BizCode)){
                $bizCode = $behavioral->BizCode;
            }

            if(!isset($bizCode) || $bizCode=='800' || $bizCode=='900'){
                return $this->returnApiJson(config('language')[$this->language]['error541'], 0);
            } else if($bizCode=='400'){
                return $this->returnApiJson(config('language')[$this->language]['error542'], 0);
            }
        }
        
        $captchaKey      = cache()->get(md5($ip));

        if(is_numeric($input['user_name_or_mobile'])){
            $loginPlayer = Player::where('carrier_id',$this->carrier->id)->where('mobile',$input['user_name_or_mobile'])->where('prefix',$this->prefix)->first();
            if($loginPlayer){
                if(\Hash::check($input['password'],$loginPlayer->password)){
                    $token =auth('api')->login($loginPlayer);
                }
            }
        }

        if(!$token){
            $loginPlayer = Player::where('carrier_id',$this->carrier->id)->where('user_name',$input['user_name_or_mobile'])->where('prefix',$this->prefix)->first();
            if($loginPlayer){
                if(\Hash::check($input['password'],$loginPlayer->password)){
                    $token =auth('api')->login($loginPlayer);
                } else{
                    if(cache()->get($cacheKey,0)==0){
                        cache()->put($cacheKey, 1, now()->addMinutes(3));
                    } else {
                        cache()->put($cacheKey,cache()->get($cacheKey)+1, now()->addMinutes(3));
                    }

                    return $this->returnApiJson(config('language')[$this->language]['error272'], 0);
                }
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error272'], 0);
            }
        }

        //是否禁止登录
        $user                  = auth('api')->user();
        $agentSingleBackground = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'agent_single_background',$this->prefix);
        if($agentSingleBackground && $user->win_lose_agent){
            return $this->returnApiJson(config('language')[$this->language]['error273'], 0);
        }

        if($user->frozen_status==4){
            return $this->returnApiJson(config('language')[$this->language]['error130'], 0);
        }

        $existplayerLogin = null;

        if(isset($input['osName']) && !empty($input['osName'])){
            $existplayerLogin = PlayerLogin::where('player_id',$user->player_id)->where('osName',$input['osName'])->orderBy('id','desc')->first();
        }
        
        $domain                = request()->header('Origin');
        $domain                = str_replace("https://", "", trim($domain));
        $domain                = str_replace("http://", "", trim($domain));

        //写入登录信息
        $user->is_online      = 1;
        $user->login_ip       = real_ip();
        $user->login_domain   = $domain;
        $user->login_at       = date('Y-m-d H:i:s');
        $user->requesttime    = time();
        $user->save();

        $minTraninGameplatAmount      = CarrierCache::getCarrierConfigure($this->carrier->id,'min_tranin_gameplat_amount');

        $data = [
            'token'             => $token,
            'token_type'        => 'bearer',
            'expires_in'        => auth('api')->factory()->getTTL() * 60,
            'ws_token'          => md5($token),
            'ws_url'            => \Yaconf::get(YACONF_PRO_SWOOLE.'.chat.wsServerUrl', ''),
            'id'                => $user->id,
            'username'          => $user->user_name,
            'is_tester'         => $user->is_tester,
            'is_sex'            => $this->carrier->is_sex,
            'is_notransfer_wallet'       => $user->is_notransfer,
            'min_tranin_gameplat_amount' => $minTraninGameplatAmount,
            'second_verification'        => false
        ];

        cache()->put($user->player_id.'_login',$token,now()->addSeconds($data['expires_in']));

        $playerToken                = new PlayerToken();
        $playerToken->carrier_id    = $user->carrier_id;
        $playerToken->player_id     = $user->player_id;
        $playerToken->user_name     = $user->user_name;
        $playerToken->token         = $token;
        $playerToken->effectiveTime = time() + $data['expires_in'];
        $playerToken->save();


        //查找info信息
        $currVipLevel            = CarrierPlayerGrade::select('level_name','withdrawcount','sort')->where('id',$user->player_level_id)->first();
        
        $rechargeAmountAdd       = PlayerTransfer::where('player_id',$user->player_id)->where('type','recharge')->sum('amount');
        $availableBetAmount      = PlayerBetFlowMiddle::where('player_id',$user->player_id)->sum('process_available_bet_amount');
        $selfPlayerSetting       = PlayerCache::getPlayerSetting($user->player_id);
        $loginAt                 = PlayerLogin::where('player_id',$user->player_id)->orderBy('login_time','desc')->skip(1)->take(1)->first();
        $d                       = [];

        $playerInviteCode        = PlayerInviteCode::where('player_id',$user->player_id)->orderBy('id','asc')->first();
        $area                    = Area::where('id',$user->area)->first();

        if($area){
            $province                = Area::where('id',$user->province)->first();
            $d['provinceid']         = $user->province;
            $d['province']           = $province->name;
            $d['area']               = $area->id;
            $d['city']               = $area->name;

        } else {
            $d['provinceid']      = 0;
            $d['province']        = '';
            $d['area']            = 0;
            $d['city']            = '';
        }

        $d['sex']             = $user->sex;
        $d['depositamount']   = bcdiv($rechargeAmountAdd,10000,2);
        $d['availablebet']    = bcdiv($availableBetAmount,1,2);
        $d['nick_name']       = $user->nick_name;
        $d['promotecode']     = !empty($playerInviteCode) ? $playerInviteCode->code : '';
        $d['user_name']       = rtrim($user->user_name,'_'.$user->prefix);
        $d['real_name']       = $user->real_name;
        $d['parent_id']       = $user->parent_id;
        $d['curr_level']      = $currVipLevel->level_name;
        $d['withdrawcount']   = $currVipLevel->withdrawcount;
        $d['updategift']      = bcdiv($currVipLevel->updategift,1,2);
        $d['birthgift']       = bcdiv($currVipLevel->birthgift,1,2);
        $d['mobile']          = empty($user->mobile) ? '' : $user->mobile;
        $d['email']           = $user->email;
        $d['type']            = $user->type;
        $d['score']           = '' ;
        $d['qq_account']      = is_null($user->qq_account)?'':$user->qq_account;
        $d['wechat']          = $user->wechat;
        $d['birthday']        = is_null($user->birthday)?'':$user->birthday;
        $d['login_at']        =  $loginAt?date('Y-m-d H:i:s',$loginAt->login_time):'';
        $d['player_id']       = $user->player_id;
        $d['nick_name']       = $user->nick_name;
        $d['bankcardname']    = $user->bankcardname;
        $d['is_sex']          = $this->carrier->is_sex;
        $d['day']             = $user->day;
        $d['login_at']        = $user->login_at;
        $d['is_notransfer']   = $user->is_notransfer;
        $d['win_lose_agent']  = $user->win_lose_agent;
        $d['guaranteed']      = $selfPlayerSetting->guaranteed;
        $d['earnings']        = $selfPlayerSetting->earnings;
        $d['created_at']      = date('Y-m-d H:i:s',strtotime($user->created_at));
        $d['extend_id']       = $user->extend_id;
        $d['parent_extend_id'] = PlayerCache::getExtendIdByplayerId($user->carrier_id,$user->parent_id);

        //注册天数
        $d['diffday']         =  round((time()-strtotime($user->created_at)) / (60 * 60 * 24));

        $weekTime                = getWeekStartEnd();
        $monthTime               = getMonthStartEnd();

        //注册彩金特殊处理
        $newPlayerTransfer = PlayerTransfer::where('player_id',$user->player_id)->where('type','recharge')->orderBy('id','desc')->first(); 
        $isRegistergift    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'is_registergift',$user->prefix);
        if($isRegistergift){
            $d['enable_lott']     = 1;
        }

        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'player_dividends_method',$user->prefix);
        //if($playerDividendsMethod==2){
            $output       = DevidendMode2::promoteAndMakeMoney($input,$user);
            $d['links'] = $output['links'];
        //}
            $data['info'] = $d;

        /////////////////
        $playerAccount     = PlayerAccount::select('balance','frozen','agentbalance','agentfrozen')->where('player_id',$user->player_id)->first();
        $mainGamePlatCodes = CarrierGamePlat::select('def_main_game_plats.main_game_plat_code')
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','map_carrier_game_plats.game_plat_id')
            ->where('carrier_id',$user->carrier_id)
            ->where('map_carrier_game_plats.status',1)
            ->pluck('def_main_game_plats.main_game_plat_code')
            ->toArray();

        $allGamePlats        =  MainGamePlat::all();
        $playerGameAccounts  =  PlayerGameAccount::where('player_id',$user->player_id)->get();

        if ( !empty($playerAccount) ) {
            $d1 = [
                'balance'      => $playerAccount->balance > 0 ? bcdiv($playerAccount->balance, 10000, 2) : '0.00',
                'frozen'       => $playerAccount->frozen > 0 ? bcdiv($playerAccount->frozen, 10000, 2) : '0.00',
                'agentbalance' => $playerAccount->agentbalance > 0 ? bcdiv($playerAccount->agentbalance, 10000, 2) : '0.00',
                'agentfrozen'  => $playerAccount->agentfrozen > 0 ? bcdiv($playerAccount->agentfrozen, 10000, 2) : '0.00',
            ];
        } else {
            $d1 = [
                'balance' => '0.00',
                'frozen'  => '0.00',
                'agentbalance' => '0.00',
                'agentfrozen'  => '0.00',
            ];
        }

        $transferKey        ='gametranfer_'.$user->player_id;
        if($user->is_notransfer && cache()->has($transferKey)){
            $is_maintain = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',GameCache::getGamePlatId(cache()->get($transferKey)))->first();
            if($is_maintain && $is_maintain->status==1){
                 //转帐操作
                $playerGameAccount  = PlayerGameAccount::where('player_id',$user->player_id)->where('main_game_plat_code',cache()->get($transferKey))->first();
                if($playerGameAccount && $playerGameAccount->is_need_repair==0){
                    request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
                    request()->offsetSet('password',$playerGameAccount->password);
                    request()->offsetSet('mainGamePlatCode',cache()->get($transferKey));

                    $game    = new Game($this->carrier,cache()->get($transferKey));        
                    $balance = $game->getBalance();
                    if(is_array($balance) && $balance['success']){
                       if($balance['data']['balance'] >= 1 && $playerGameAccount->is_locked==0){
                         request()->offsetSet('price',intval($balance['data']['balance']));
                         $output = $game->transferTo($user);
                         if(is_array($output) && $output['success']){
                            cache()->forget($transferKey);
                         } else{
                            if(cache()->get($transferKey) =='ky1'){
                                return $this->returnApiJson(config('language')[$this->language]['error276'].'1', 0);
                            } elseif(cache()->get($transferKey) =='cq95'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error465'].'5', 0);
                            } elseif(cache()->get($transferKey) =='jdb5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error466'].'5', 0);
                            } elseif(cache()->get($transferKey) =='fc5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error467'].'5', 0);
                            } elseif(cache()->get($transferKey) =='pp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'5', 0);
                            } elseif(cache()->get($transferKey) =='pp6'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'6', 0);
                            } elseif(cache()->get($transferKey) =='jp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'5', 0);
                            } elseif(cache()->get($transferKey) =='jp6'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'6', 0);
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
                            } else{
                                return $this->returnApiJson(config('language')[$this->language]['error277'].cache()->get($transferKey).config('language')[$this->language]['error278'], 0);
                            }
                         }
                       } else{
                          cache()->forget($transferKey);
                       }
                    } else{
                        if(cache()->get($transferKey) =='ky1'){
                            return $this->returnApiJson(config('language')[$this->language]['error281'].'1', 0);
                        } elseif(cache()->get($transferKey) =='cq95'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error475'].'5', 0);
                        } elseif(cache()->get($transferKey) =='jdb5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error476'].'5', 0);
                        } elseif(cache()->get($transferKey) =='fc5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error477'].'5', 0);
                        } elseif(cache()->get($transferKey) =='pp5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'5', 0);
                        } elseif(cache()->get($transferKey) =='pp6'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'6', 0);
                        } elseif(cache()->get($transferKey) =='jp5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error279'].'5', 0);
                        } elseif(cache()->get($transferKey) =='jp6'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error279'].'6', 0);
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

        $d1['plats']=$plats;

        $data['balance'] = $d1;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    public function tokenlogin()
    {
        $input           = request()->all();

        if(!isset($input['player_token']) || empty($input['player_token'])){
            return $this->returnApiJson(config('language')[$this->language]['error283'], 0);
        }

        request()->headers->set('Authorization','bearer '.$input['player_token']);

        $user = auth("api")->user();

        if(!$user || $user->carrier_id != $this->carrier->id){
            return response()->json(['success'=>false,'message' => config('language')[$this->language]['error110'],'data'=>[],'code'=>401],401)->send();
        }

        $minTraninGameplatAmount      = CarrierCache::getCarrierConfigure($this->carrier->id,'min_tranin_gameplat_amount');

        $data = [
            'token'             => $input['player_token'],
            'token_type'        => 'bearer',
            'expires_in'        => auth('api')->factory()->getTTL() * 60,
            'ws_token'          => md5($input['player_token']),
            'ws_url'            => \Yaconf::get(YACONF_PRO_SWOOLE.'.chat.wsServerUrl', ''),
            'id'                => $user->id,
            'username'          => $user->user_name,
            'is_tester'         => $user->is_tester,
            'is_sex'            => $this->carrier->is_sex,
            'is_notransfer_wallet'       => $user->is_notransfer,
            'min_tranin_gameplat_amount' => $minTraninGameplatAmount,
            'second_verification'        => false
        ];

        //查找info信息
        $currVipLevel            = CarrierPlayerGrade::select('level_name','withdrawcount','sort')->where('id',$user->player_level_id)->first();
        
        $rechargeAmountAdd       = PlayerTransfer::where('player_id',$user->player_id)->where('type','recharge')->sum('amount');
        $availableBetAmount      = PlayerBetFlowMiddle::where('player_id',$user->player_id)->sum('process_available_bet_amount');
        $selfPlayerSetting       = PlayerCache::getPlayerSetting($user->player_id);

        $loginAt                 = PlayerLogin::where('player_id',$user->player_id)->orderBy('login_time','desc')->skip(1)->take(1)->first();
        $d                       = [];

        $playerInviteCode        = PlayerInviteCode::where('player_id',$user->player_id)->orderBy('id','asc')->first();
        $area                    = Area::where('id',$user->area)->first();

        if($area){
            $province                = Area::where('id',$user->province)->first();
            $d['provinceid']         = $user->province;
            $d['province']           = $province->name;
            $d['area']               = $area->id;
            $d['city']               = $area->name;

        } else {
            $d['provinceid']      = 0;
            $d['province']        = '';
            $d['area']            = 0;
            $d['city']            = '';
        }

        $d['sex']             = $user->sex;
        $d['depositamount']   = bcdiv($rechargeAmountAdd,10000,2);
        $d['availablebet']    = bcdiv($availableBetAmount,1,2);
        $d['nick_name']       = $user->nick_name;
        $d['promotecode']     = !empty($playerInviteCode) ? $playerInviteCode->code : '';
        $d['user_name']       = rtrim($user->user_name,'_'.$user->prefix);
        $d['real_name']       = $user->real_name;
        $d['parent_id']       = $user->parent_id;
        $d['curr_level']      = $currVipLevel->level_name;
        $d['withdrawcount']   = $currVipLevel->withdrawcount;
        $d['updategift']      = bcdiv($currVipLevel->updategift,1,2);
        $d['birthgift']       = bcdiv($currVipLevel->birthgift,1,2);
        $d['mobile']          = empty($user->mobile) ? '' : $user->mobile;
        $d['email']           = $user->email;
        $d['type']            = $user->type;
        $d['score']           = '' ;
        $d['qq_account']      = is_null($user->qq_account)?'':$user->qq_account;
        $d['wechat']          = $user->wechat;
        $d['birthday']        = is_null($user->birthday)?'':$user->birthday;
        $d['login_at']        =  $loginAt?date('Y-m-d H:i:s',$loginAt->login_time):'';
        $d['player_id']       = $user->player_id;
        $d['nick_name']       = $user->nick_name;
        $d['bankcardname']    = $user->bankcardname;
        $d['is_sex']          = $this->carrier->is_sex;
        $d['day']             = $user->day;
        $d['login_at']        = $user->login_at;
        $d['is_notransfer']   = $user->is_notransfer;
        $d['win_lose_agent']  = $user->win_lose_agent;
        $d['guaranteed']      = $selfPlayerSetting->guaranteed;
        $d['earnings']        = $selfPlayerSetting->earnings;
        $d['created_at']      = date('Y-m-d H:i:s',strtotime($user->created_at));
        $d['extend_id']       = $user->extend_id;
        $d['parent_extend_id'] = PlayerCache::getExtendIdByplayerId($user->carrier_id,$user->parent_id);

        //注册天数
        $d['diffday']         =  round((time()-strtotime($user->created_at)) / (60 * 60 * 24));

        $weekTime                = getWeekStartEnd();
        $monthTime               = getMonthStartEnd();

        //注册彩金特殊处理
        $newPlayerTransfer = PlayerTransfer::where('player_id',$user->player_id)->where('type','recharge')->orderBy('id','desc')->first(); 
        $isRegistergift    = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'is_registergift',$user->prefix);
        if($isRegistergift){
            $d['enable_lott']     = 1;
        }

        $playerDividendsMethod = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'player_dividends_method',$user->prefix);
        //if($playerDividendsMethod==2){
            $output       = DevidendMode2::promoteAndMakeMoney($input,$user);
            $d['links'] = $output['links'];
        //}
            $data['info'] = $d;

        /////////////////
        $playerAccount     = PlayerAccount::select('balance','frozen','agentbalance','agentfrozen')->where('player_id',$user->player_id)->first();
        $mainGamePlatCodes = CarrierGamePlat::select('def_main_game_plats.main_game_plat_code')
            ->leftJoin('def_main_game_plats','def_main_game_plats.main_game_plat_id','=','map_carrier_game_plats.game_plat_id')
            ->where('carrier_id',$user->carrier_id)
            ->where('map_carrier_game_plats.status',1)
            ->pluck('def_main_game_plats.main_game_plat_code')
            ->toArray();

        $allGamePlats        =  MainGamePlat::all();
        $playerGameAccounts  =  PlayerGameAccount::where('player_id',$user->player_id)->get();

        if ( !empty($playerAccount) ) {
            $d1 = [
                'balance'      => $playerAccount->balance > 0 ? bcdiv($playerAccount->balance, 10000, 2) : '0.00',
                'frozen'       => $playerAccount->frozen > 0 ? bcdiv($playerAccount->frozen, 10000, 2) : '0.00',
                'agentbalance' => $playerAccount->agentbalance > 0 ? bcdiv($playerAccount->agentbalance, 10000, 2) : '0.00',
                'agentfrozen'  => $playerAccount->agentfrozen > 0 ? bcdiv($playerAccount->agentfrozen, 10000, 2) : '0.00',
            ];
        } else {
            $d1 = [
                'balance' => '0.00',
                'frozen'  => '0.00',
                'agentbalance' => '0.00',
                'agentfrozen'  => '0.00',
            ];
        }

        $transferKey        ='gametranfer_'.$user->player_id;
        if($user->is_notransfer && cache()->has($transferKey)){
            $is_maintain = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',GameCache::getGamePlatId(cache()->get($transferKey)))->first();
            if($is_maintain && $is_maintain->status==1){
                 //转帐操作
                $playerGameAccount  = PlayerGameAccount::where('player_id',$user->player_id)->where('main_game_plat_code',cache()->get($transferKey))->first();
                if($playerGameAccount && $playerGameAccount->is_need_repair==0){
                    request()->offsetSet('accountUserName',$playerGameAccount->account_user_name);
                    request()->offsetSet('password',$playerGameAccount->password);
                    request()->offsetSet('mainGamePlatCode',cache()->get($transferKey));

                    $game    = new Game($this->carrier,cache()->get($transferKey));        
                    $balance = $game->getBalance();
                    if(is_array($balance) && $balance['success']){
                       if($balance['data']['balance'] >= 1 && $playerGameAccount->is_locked==0){
                         request()->offsetSet('price',intval($balance['data']['balance']));
                         $output = $game->transferTo($user);
                         if(is_array($output) && $output['success']){
                            cache()->forget($transferKey);
                         } else{
                            if(cache()->get($transferKey) =='ky1'){
                                return $this->returnApiJson(config('language')[$this->language]['error276'].'1', 0);
                            } elseif(cache()->get($transferKey) =='cq95'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error465'].'5', 0);
                            } elseif(cache()->get($transferKey) =='jdb5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error466'].'5', 0);
                            } elseif(cache()->get($transferKey) =='fc5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error467'].'5', 0);
                            } elseif(cache()->get($transferKey) =='pp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'5', 0);
                            } elseif(cache()->get($transferKey) =='pp6'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error275'].'6', 0);
                            } elseif(cache()->get($transferKey) =='jp5'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'5', 0);
                            } elseif(cache()->get($transferKey) =='jp6'){    //////////
                                return $this->returnApiJson(config('language')[$this->language]['error274'].'6', 0);
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
                            } else{
                                return $this->returnApiJson(config('language')[$this->language]['error277'].cache()->get($transferKey).config('language')[$this->language]['error278'], 0);
                            }
                         }
                       } else{
                          cache()->forget($transferKey);
                       }
                    } else{
                        if(cache()->get($transferKey) =='ky1'){
                            return $this->returnApiJson(config('language')[$this->language]['error281'].'1', 0);
                        } elseif(cache()->get($transferKey) =='cq95'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error475'].'5', 0);
                        } elseif(cache()->get($transferKey) =='jdb5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error476'].'5', 0);
                        } elseif(cache()->get($transferKey) =='fc5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error477'].'5', 0);
                        } elseif(cache()->get($transferKey) =='pp5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'5', 0);
                        } elseif(cache()->get($transferKey) =='pp6'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error280'].'6', 0);
                        } elseif(cache()->get($transferKey) =='jp5'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error279'].'5', 0);
                        } elseif(cache()->get($transferKey) =='jp6'){    //////////
                            return $this->returnApiJson(config('language')[$this->language]['error279'].'6', 0);
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

        $d1['plats']=$plats;

        $data['balance'] = $d1;

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }


    public function sendSms()
    {
        $input           = request()->all();
        
        if(empty($this->smsPassageId) || is_null($this->smsPassageId)){
            return $this->returnApiJson(config('language')[$this->language]['error208'], 0);
        }

        $smspassage = SmsPassage::where('id',$this->smsPassageId)->first();
        if(!$smspassage){
            return $this->returnApiJson(config('language')[$this->language]['error209'], 0);
        }

        if(!$smspassage->status){
            return $this->returnApiJson(config('language')[$this->language]['error210'], 0);
        }

        if(!isset($input['mobile']) || empty($input['mobile'])){
            return $this->returnApiJson(config('language')[$this->language]['error86'], 0);
        }

         //手机号解密
        if(!is_numeric($input['mobile'])){
            $code                     = md5('mobile');
            $iv                       = substr($code,0,16);
            $key                      = substr($code,16);
            $input['mobile']          =  openssl_decrypt(base64_decode($input['mobile']), 'AES-128-CBC', $key,1,$iv);
        }

        if(cache()->get('sendsms_'.$input['mobile'])){
            return $this->returnApiJson(config('language')[$this->language]['error212'], 0);
        }

        $disablePhoneNumberSegment = CarrierCache::getCarrierConfigure($this->carrier->id,'disable_phone_number_segment');

        $mobileStartNumber = substr($input['mobile'],2,3);
        if(!empty($disablePhoneNumberSegment)){
            $startNumber = explode(',',$disablePhoneNumberSegment);
            if(in_array($mobileStartNumber,$startNumber)){
                return $this->returnApiJson(config('language')[$this->language]['text65'], 1);
            }
        }

        $siteTitle                  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'site_title',$this->prefix);
        $prefixLanguage             = CarrierCache::getLanguageByPrefix($this->prefix);


        $sms                        = new Sms($smspassage);
        $res                        = $sms->sendData($input['mobile'],$this->carrier,$prefixLanguage,$siteTitle);

        $carrierSms                 = new CarrierSms();
        $carrierSms->carrier_id     = $this->carrier->id;
        $carrierSms->prefix         = $this->prefix;
        $carrierSms->sms_passage_id = $smspassage->id;
        $carrierSms->mobile         = $input['mobile'];
        $carrierSms->uniquire_id    = isset($res['uniquire_id']) ? $res['uniquire_id']:'';
        $carrierSms->ip             = real_ip();

        if($res['success']===true){
            $carrierSms->status         = 1;
            $carrierSms->save();

            cache()->put('sendsms_'.$input['mobile'],1,now()->addMinutes(2));

            return $this->returnApiJson(config('language')[$this->language]['text65'], 1);
        } else {
            $carrierSms->status         = 0;
            $carrierSms->save();

            return $this->returnApiJson(config('language')[$this->language]['error211'], 0);
        }
    }

    public function retrievePassordForPhone()
    {
        $input           = request()->all();
        
        if(empty($this->smsPassageId) || is_null($this->smsPassageId)){
            return $this->returnApiJson(config('language')[$this->language]['error208'], 0);
        }

        $smspassage = SmsPassage::where('id',$this->smsPassageId)->first();
        if(!$smspassage){
            return $this->returnApiJson(config('language')[$this->language]['error209'], 0);
        }

        if(!$smspassage->status){
            return $this->returnApiJson(config('language')[$this->language]['error210'], 0);
        }

        if(!isset($input['mobile']) || empty($input['mobile'])){
            return $this->returnApiJson(config('language')[$this->language]['error86'], 0);
        }

         //手机号解密
        if(!is_numeric($input['mobile'])){
            $code                     = md5('mobile');
            $iv                       = substr($code,0,16);
            $key                      = substr($code,16);
            $input['mobile']          =  openssl_decrypt(base64_decode($input['mobile']), 'AES-128-CBC', $key,1,$iv);
        }

        //判断手机号是否存在
        $existPlayer = Player::where('prefix',$this->prefix)->where('mobile',$input['mobile'])->first();
        if(!$existPlayer){
            return $this->returnApiJson(config('language')[$this->language]['error464'], 0);
        }

        if(cache()->get('sendsms_'.$input['mobile'])){
            return $this->returnApiJson(config('language')[$this->language]['error212'], 0);
        }

        $disablePhoneNumberSegment = CarrierCache::getCarrierConfigure($this->carrier->id,'disable_phone_number_segment');

        $mobileStartNumber = substr($input['mobile'],2,3);
        if(!empty($disablePhoneNumberSegment)){
            $startNumber = explode(',',$disablePhoneNumberSegment);
            if(in_array($mobileStartNumber,$startNumber)){
                return $this->returnApiJson(config('language')[$this->language]['text65'], 1);
            }
        }

        $siteTitle                  = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'site_title',$this->prefix);
        $prefixLanguage             = CarrierCache::getLanguageByPrefix($this->prefix);


        $sms                        = new Sms($smspassage);
        $res                        = $sms->sendData($input['mobile'],$this->carrier,$prefixLanguage,$siteTitle);

        $carrierSms                 = new CarrierSms();
        $carrierSms->carrier_id     = $this->carrier->id;
        $carrierSms->prefix         = $this->prefix;
        $carrierSms->sms_passage_id = $smspassage->id;
        $carrierSms->mobile         = $input['mobile'];
        $carrierSms->uniquire_id    = isset($res['uniquire_id']) ? $res['uniquire_id']:'';
        $carrierSms->ip             = real_ip();

        if($res['success']===true){
            $carrierSms->status         = 1;
            $carrierSms->save();

            cache()->put('sendsms_'.$input['mobile'],1,now()->addMinutes(2));

            return $this->returnApiJson(config('language')[$this->language]['text65'], 1);
        } else {
            $carrierSms->status         = 0;
            $carrierSms->save();

            return $this->returnApiJson(config('language')[$this->language]['error211'], 0);
        }
    }

    // 登出
    public function logout()
    {
        $user = auth("api")->user();
        $user->is_online =0;
        $user->save();

        auth()->guard("api")->logout();
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    protected function guard()
    {
        return Auth::guard('api');
    }

    public function username()
    {
        return 'user_name';
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }
}