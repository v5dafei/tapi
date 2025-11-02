<?php

namespace App\Http\Controllers\Agent;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Utils\Validator;
use App\Models\CarrierPlayerGrade;
use App\Models\PlayerInviteCode;
use App\Models\Conf\PlayerSetting;
use App\Http\Controllers\Agent\BaseController;
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
use App\Lib\Clog;

class AuthController extends BaseController
{
    use Authenticatable;

    public function login()
    {
        $input              = request()->all();
        $loginAgent         = false;
        $token              = false; 

        $agentSingleBackground = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'agent_single_background',$this->prefix);
     
        if(!$agentSingleBackground){
            return $this->returnApiJson(config('language')[$this->language]['error426'], 0);
        }

        if(!isset($input['user_name']) || empty($input['user_name'])){
            return $this->returnApiJson(config('language')[$this->language]['error427'], 0);
        }

        $input['user_name'] = $input['user_name'].'_'.$this->prefix;

        $cacheKey           = "agent_login_error_" .$input['user_name'];

        if(cache()->get($cacheKey,0)==5){
            return $this->returnApiJson(config('language')[$this->language]['error5'], 0);
        }

        if (!isset($input['password']) || empty($input['password'])) {
            return $this->returnApiJson(config('language')[$this->language]['error4'], 0);
        }


        $ip              = real_ip();
        $captchaKey      = cache()->get(md5($ip));

        if(!$token){
            $loginAgent = Player::where('carrier_id',$this->carrier->id)->where('user_name',$input['user_name'])->where('prefix',$this->prefix)->where('win_lose_agent',1)->first();
            if($loginAgent){
                if(\Hash::check($input['password'],$loginAgent->password)){
                    $token =auth('agent')->login($loginAgent);
                } else{
                    if(cache()->get($cacheKey,0)==0){
                        cache()->put($cacheKey, 1, now()->addMinutes(3));
                    } else {
                        cache()->put($cacheKey,cache()->get($cacheKey)+1, now()->addMinutes(3));
                    }

                    return $this->returnApiJson(config('language')[$this->language]['error272'], 0);
                }
            } else{
                return $this->returnApiJson(config('language')[$this->language]['error428'], 0);
            }
        }

        //是否禁止登录
        $agent                 = auth('agent')->user();
        if($agent->frozen_status==4){
            return $this->returnApiJson(config('language')[$this->language]['error429'], 0);
        }

         // 如果没有绑定  不用验证谷歌验证
        if ($agent->bind_google_status == 1) {
            $oneCode = trim(request("code"));
            if (empty($oneCode)) {
                return returnApiJson(config('language')[$this->language]['error430'], 0);
            }

            $ga = new \PHPGangsta_GoogleAuthenticator();
            $checkResult = $ga->verifyCode($agent->remember_token, $oneCode, 1); // 2 = 2 * 30秒时钟容差
            if (!$checkResult) {
                return returnApiJson(config('language')[$this->language]['error431'], 0);
            }
        }

        $domain                = request()->header('Origin');
        $domain                = str_replace("https://", "", trim($domain));
        $domain                = str_replace("http://", "", trim($domain));

        //写入登录信息
        $agent->is_online      = 1;
        $agent->login_ip       = real_ip();
        $agent->login_domain   = $domain;
        $agent->login_at       = date('Y-m-d H:i:s');
        $agent->requesttime    = time();
        $agent->save();

        $agentLogin                 = new PlayerLogin();
        $agentLogin->login_location = IP::ipLocation(real_ip());
        $agentLogin->login_time     = time();
        $agentLogin->login_domain   = $domain;
        $agentLogin->login_ip       = real_ip();
        $agentLogin->carrier_id     = $agent->carrier_id;
        $agentLogin->player_id      = $agent->player_id;
        $agentLogin->user_name      = $agent->user_name;

        if(isset($input['fingerprint']) && !empty($input['fingerprint'])){
            $agentLogin->fingerprint      = $input['fingerprint'];
        }

        if(isset($input['osName']) && !empty($input['osName'])){
            $agentLogin->osName      = $input['osName'];
        }
        $agentLogin->save();

        $minTraninGameplatAmount      = CarrierCache::getCarrierConfigure($this->carrier->id,'min_tranin_gameplat_amount');

        $playerInviteCode       = PlayerInviteCode::where('player_id',$agent->player_id)->first();

        $playerSetting     = PlayerSetting::where('player_id',$agent->player_id)->first();

        $data = [
            'token'             => $token,
            'token_type'        => 'bearer',
            'expires_in'        => auth('api')->factory()->getTTL() * 60,
            'id'                => $agent->id,
            'extend_id'         => $agent->extend_id,
            'username'          => $agent->user_name,
            'resourceurl'       => config('main')['alicloudstore'],
            'code'              => $playerInviteCode->code,
            'earnings'          => $playerSetting->earnings
        ];

        $playerToken                = new PlayerToken();
        $playerToken->carrier_id    = $agent->carrier_id;
        $playerToken->player_id     = $agent->player_id;
        $playerToken->user_name     = $agent->user_name;
        $playerToken->token         = $token;
        $playerToken->effectiveTime = time() + $data['expires_in'];
        $playerToken->save();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }


    public function frontLogin()
    {
        $input                 = request()->all();
        $agentSingleBackground = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'agent_single_background',$this->prefix);
       

        if(!$agentSingleBackground){
            return $this->returnApiJson(config('language')[$this->language]['error432'], 0);
        }

        if(!isset($input['token']) || empty($input['token'])){
            return $this->returnApiJson(config('language')[$this->language]['error433'], 0);
        }

        $playerToken = PlayerToken::where('token',$input['token'])->first();
        if(!$playerToken){
            return $this->returnApiJson(config('language')[$this->language]['error434'], 0);
        }

        if($playerToken->effectiveTime>time()){
            return $this->returnApiJson(config('language')[$this->language]['error435'], 0);
        }

        $loginPlayer = Player::where('player_id',$playerToken->player_id)->first();

        $token       = auth('agent')->login($loginPlayer);
       

        //是否禁止登录
        $agent                 = auth('agent')->user();
        if($agent->frozen_status==4){
            return $this->returnApiJson(config('language')[$this->language]['error429'], 0);
        }

        $playerInviteCode       = PlayerInviteCode::where('player_id',$agent->player_id)->first();
        $data = [
            'token'             => $token,
            'token_type'        => 'bearer',
            'expires_in'        => auth('api')->factory()->getTTL() * 60,
            'id'                => $agent->id,
            'extend_id'         => $agent->extend_id,
            'username'          => $agent->user_name,
            'resourceurl'       => config('main')['alicloudstore'],
            'code'              => $playerInviteCode->code
        ];

        $playerToken                = new PlayerToken();
        $playerToken->carrier_id    = $agent->carrier_id;
        $playerToken->player_id     = $agent->player_id;
        $playerToken->user_name     = $agent->user_name;
        $playerToken->token         = $token;
        $playerToken->effectiveTime = time() + $data['expires_in'];
        $playerToken->save();

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1,$data);
    }

    // 登出
    public function logout()
    {
        
        $user                  = auth("agent")->user();
        $user->is_online       = 0;
        $user->save();

        auth()->guard("agent")->logout();
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    protected function guard()
    {
        return Auth::guard('agent');
    }

    public function username()
    {
        return 'user_name';
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('agent')->refresh());
    }

     // 获取谷歌验证
    public function getGoogle()
    {
        $agent = auth('agent')->user();
        if (!empty($agent->google_img) && !empty($agent->remember_token)) {
            return returnApiJson(config('language')[$this->language]['success1'], 1, ['image_url' => $agent->google_img, 'secret' => $agent->remember_token,'bind_google_status'=>$agent->bind_google_status]);
        }
        // 谷歌验证
        $ga          = new \PHPGangsta_GoogleAuthenticator();
        $secret      = $ga->createSecret();
        $checkResult = $ga->getQRCodeGoogleUrl($agent->user_name, $secret, 'googleVerify'); // 2 = 2 * 30秒时钟容差

        Player::where('player_id',$agent->player_id)->update(['remember_token' => $secret, 'google_img' => $checkResult]);

        return $this->returnApiJson(config('language')[$this->language]['success1'], 1, ['image_url' => $checkResult, 'secret' => $secret,'bind_google_status'=>$agent->bind_google_status]);
    }

    public function bindGoogle()
    {
        $input              = request()->all();

        if(!isset($input['password']) || empty($input['password']) ){
            return $this->returnApiJson(config('language')[$this->language]['error85'], 0);
        }

         //修改资金密码
        if(!\Hash::check($input['password'], $this->agent->password)) {
            return $this->returnApiJson(config('language')[$this->language]['error436'], 0);
        }
        
        $oneCode            = trim(request("code"));
        if (empty($oneCode)) {
            return $this->returnApiJson(config('language')[$this->language]['error430'], 0);
        }

        $agent              = auth('agent')->user();

        if (empty($agent->google_img) || empty($agent->remember_token)) {
            return $this->returnApiJson(config('language')[$this->language]['error437'], 0);
        }

        $ga          = new \PHPGangsta_GoogleAuthenticator();
        $checkResult = $ga->verifyCode($agent->remember_token, $oneCode, 2); // 2 = 2 * 30秒时钟容差
        if (!$checkResult) {
            return $this->returnApiJson(config('language')[$this->language]['error431'], 0);
        }

        Player::where('player_id',$agent->player_id)->update(['bind_google_status' => 1]);
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);

    }

    // 关闭按钮
    public function closeGoogle()
    {

        $input              = request()->all();

        if(!isset($input['password']) || empty($input['password']) ){
            return $this->returnApiJson(config('language')[$this->language]['error85'], 0);
        }

         //修改资金密码
        if(!\Hash::check($input['password'], $this->agent->password)) {
            return $this->returnApiJson(config('language')[$this->language]['error436'], 0);
        }

        $oneCode            = trim(request("code"));
        if (empty($oneCode)) {
            return $this->returnApiJson(config('language')[$this->language]['error430'], 0);
        }

        $agent              = auth('agent')->user();

        $ga          = new \PHPGangsta_GoogleAuthenticator();
        $checkResult = $ga->verifyCode($agent->remember_token, $oneCode, 2); // 2 = 2 * 30秒时钟容差
        if (!$checkResult) {
            return $this->returnApiJson(config('language')[$this->language]['error431'], 0);
        }
        
        $data = [
            'remember_token' => '',
            'google_img'      => '',
            'bind_google_status' => 0,
        ];

        Player::where('player_id', $agent->player_id)->where('win_lose_agent')->update($data);
        
        return $this->returnApiJson(config('language')[$this->language]['success1'], 1);
    }

    public function register()
    {
        $input                = request()->all();
        $isAllowGeneralAgent = CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'is_allow_general_agent',$this->prefix);
        
        if(!$isAllowGeneralAgent){
            return $this->returnApiJson(config('language')[$this->language]['error438'],0);
        }

        //限制当前IP注册数量
        $registerNum = Player::where('carrier_id',$this->carrier->id)->where('is_tester',0)->where('prefix',$this->prefix)->where('register_ip',real_ip())->count();

        if($registerNum >= CarrierCache::getCarrierMultipleConfigure($this->carrier->id,'player_max_register_one_ip_minute',$this->prefix)) {
            return $this->returnApiJson(config('language')[$this->language]['error108'],0);
        }

        if ( !Validator::isUsr($input['user_name'], [ 'min' => 5, 'max' => 36, 'checkUpper' => true ]) ) {
                return $this->returnApiJson(config('language')[$this->language]['error439'],0);
        }

        if(!isset($input['captcha']) && empty($input['captcha'])){
            return $this->returnApiJson(config('language')[$this->language]['error227'], 0);
        }

        $ip              = real_ip();
        $captchaKey      = cache()->get(md5($ip));
           
        if(strtolower($input["captcha"]) != strtolower($captchaKey)){
            return $this->returnApiJson(config('language')[$this->language]['error226'], 0);
        }

        $input['user_name']    = $input['user_name'].'_'.$this->prefix;
        $existuserName         = Player::where('carrier_id',$this->carrier->id)->where('user_name',$input['user_name'])->where('prefix',$this->prefix)->first();
        if($existuserName){
            return $this->returnApiJson(config('language')[$this->language]['error440'],0);
        }

        if(!isset($input['password']) || empty(trim($input['password']))) {
            return $this->returnApiJson(config('language')[$this->language]['error85'],0);
        }

        $carrierPlayerLevel          = CarrierPlayerGrade::where('carrier_id',$this->carrier->id)->where('is_default',1)->where('prefix',$this->prefix)->first();
        $defaultAgent                = Player::where('user_name',CarrierCache::getCarrierConfigure($this->carrier->id,'default_user_name'))->where('carrier_id',$this->carrier->id)->first();
        try {
            \DB::beginTransaction();

            $player                            = new Player();
            $player->top_id                    = $defaultAgent->top_id;
            $player->parent_id                 = $defaultAgent->player_id;
            $player->is_auto_register          = 0;
            $player->is_tester                 = 0;
            $player->mobile                    = '';
            $player->user_name                 = $input['user_name'];
            $player->password                  = bcrypt($input['password']);
            $player->paypassword               = null;
            $player->carrier_id                = $this->carrier->id;
            $player->player_level_id           = $carrierPlayerLevel->id;
            $player->register_ip               = real_ip();
            $player->level                     = $defaultAgent->level+1;
            $player->type                      = 2;
            $player->win_lose_agent            = 1;
            $player->prefix                    = $this->prefix;
            $player->save();

            if(is_null($player->rid)){
                $player->rid     = $defaultAgent->rid.'|'.$player->player_id;
                $player->save();
            }

            $playerSetting                              = new PlayerSetting();
            $playerSetting->player_id                   = $player->player_id;
            $playerSetting->carrier_id                  = $player->carrier_id;
            $playerSetting->top_id                      = $player->top_id;
            $playerSetting->parent_id                   = $player->parent_id;
            $playerSetting->rid                         = $player->rid;
            $playerSetting->is_tester                   = $player->is_tester;
            $playerSetting->user_name                   = $player->user_name;
            $playerSetting->prefix                      = $player->prefix;
            $playerSetting->level                       = $player->level;
            $playerSetting->guaranteed                  = 0;
            $playerSetting->lottoadds                   = CarrierCache::getCarrierConfigure($this->carrier->id, 'default_lottery_odds');
            $playerSetting->save();

            $selfInviteCode                              = new PlayerInviteCode();
            $selfInviteCode->carrier_id                  = $player->carrier_id;
            $selfInviteCode->player_id                   = $player->player_id;
            $selfInviteCode->rid                         = $player->rid;
            $selfInviteCode->username                    = $player->user_name;
            $selfInviteCode->type                        = 2;
            $selfInviteCode->lottoadds                   = $playerSetting->lottoadds;
            $selfInviteCode->is_tester                   = $player->is_tester;
            $selfInviteCode->prefix                      = $player->prefix;
            $selfInviteCode->code                        = $player->extend_id;
            $selfInviteCode->save();

            \DB::commit();

            return $this->returnApiJson(config('language')[$this->language]['success1'],1);
        } catch (\Exception $e) {
            \DB::rollback();
            Clog::recordabnormal('代理注册异常:'.$e->getMessage());   
            return $this->returnApiJson(config('language')[$this->language]['error441'].$e->getMessage(), 0);
        }
    }
}