<?php

namespace App\Game;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Def\Game as Games;
use App\Models\Def\MainGamePlat;
use App\Models\Map\CarrierGamePlat;
use App\Models\Log\PlayerTransferCasino;
use App\Models\Log\RemainQuota;
use App\Models\Log\PlayerBetFlow;
use App\Lib\Cache\SystemCache;
use App\Lib\Cache\GameCache;
use App\Lib\Cache\PlayerCache;
use App\Lib\Cache\CarrierCache;
use App\Models\PlayerGameAccount;
use App\Models\Player;
use App\Models\PlayerAccount;
use App\Models\PlayerTransfer;
use App\Models\Log\PlayerWithdrawFlowLimit;
use App\Models\Carrier;
use App\Models\PlayerRecent;
use App\Lib\Clog;
use App\Lib\Cache\Lock;
use App\Jobs\SynGameAccountJob;


class Game
{
    public $plat;
    public $carrier;

    const LOGIN                                 = 'login';
    const CREATEMEMBER                          = 'createMember';
    const GETBALANCE                            = 'getBalance';
    const TRANSFERIN                            = 'transferIn';
    const TRANSFERTO                            = 'transferTo';
    const CHECKTRANSFER                         = 'checkTransfer';
    const KICK                                  = 'kick';
    const JOINGAME                              = 'joinGame';
    const JOINMOBILEGAME                        = 'joinMobileGame';
    const JOINPGMOBILEGAME                      = 'joinPgMobileGame';
    const JOINGAMELOTTERYLOBBY                  = 'joinGameLotteryLobby';
    const JOINMOBILEGAMELOTTERYLOBBY            = 'joinMobileGameLotteryLobby';
    const GETRECORD                             = 'getRecord';
    const GETLOTTERYCODE                        = 'getlotterycode';
    const SYNCGAME                              = 'syncGame';
    const LATESTRESULTS                         = 'latestresults';
    const UPDATEMEMBER                          = 'updateMember';

    public function __construct($carrier='',$plat = '')
    {
        $this->plat          = $plat;
        $this->carrier       = $carrier;
    }

    public function auth(){
        $url   =  config('game')['pub']['gameurl'].'/api/'.self::LOGIN;
        $param =[
            'username' => $this->carrier->apiusername,
            'password' => $this->carrier->apipassword,
            'key'      => $this->carrier->apikey,
        ];

        $ch = curl_init($url); //请求的URL地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param); //$post_data JSON类型字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch,CURLOPT_INTERFACE,config('game')['pub']['AddressIp']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
        $output    = curl_exec($ch);
        $error     = curl_error($ch);

        curl_close($ch);

        if (!empty($error)) {
             \Log::info('错误信息是'.$error);
            return false;
        } else  {
           $output = json_decode($output,true);
           if(isset($output['success']) && $output['success'] == true){
                GameCache::setPlatToken('carrier_'.$this->carrier->id,$output['data']);
                return  $output['data']['token'];
           } else {
                \Log::info('连接游戏接口服务器鉴权失败url是'.$url.'参数是',$param);
                return false;
           }
        }
    }

    public function request($url, $param=array(),$header=[])
    {

        $tokenTime = GameCache::getPlatToken('carrier_'.$this->carrier->id);

        if(!$tokenTime) {
            
            $token =$this->auth();
            if(!$token) {  
                return false;
            }
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Accept: application/json';
        } else {
            $explode = explode('____',$tokenTime);
            if(time()>$explode[1]) {
                $token =$this->auth();
                if(!$token){
                    return false;
                } 
            } else {
                $header[] = 'Authorization: Bearer ' . $explode[0];
                $header[] = 'Accept: application/json';
            }
        }

        $ch = curl_init($url); //请求的URL地址
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param); //$post_data JSON类型字符串
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch,CURLOPT_INTERFACE,config('game')['pub']['AddressIp']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if(count($header))
        {
          curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $output    = curl_exec($ch);
        $error     = curl_error($ch);
        curl_close($ch);
        
        if (!empty($error)) {
            \Log::info('错误信息是'.$error);
            return false;
        } else {
           $output = json_decode($output,true);
           return $output;
        }
    }

    // 获取ae 最新一次奖期
    public function getLatestresults($gameId)
    {
        $output = $this->request(config('game')['pub']['gameurl'].'/api/'.self::LATESTRESULTS,['gameId' => $gameId]);
        return $output;
    }

    //创建用户
    public function createMember($user)
    {
        $username          = request()->get('username');
        $mainGamePlatCode  = request()->get('mainGamePlatCode');
        $input             = request()->all();
        $param = [
            'mainGamePlatCode' => $mainGamePlatCode,
            'username'         => $username,
            'language'         => CarrierCache::getLanguageByPrefix($user->prefix),
            'currency'         => CarrierCache::getCurrencyByPrefix($user->prefix),
            'is_tester'        => $user->is_tester
        ];

        if(isset($input['odds'])) {
            $param['odds']    = $input['odds'];
        }
        $output = $this->request(config('game')['pub']['gameurl'].'/api/'.self::CREATEMEMBER,$param);

        if(isset($output['success'])){

            if($output['success']){
            $playerGameAccount = PlayerGameAccount::where('main_game_plat_code',$mainGamePlatCode)->where('player_id',$user->player_id)->first();
                if($playerGameAccount){
                    return ['success' => false, 'data' => [], 'message' => '对不起, 您的游戏平台帐户已存在!','code'=>200];
                } else {

                    $playerGameAccount                     = new PlayerGameAccount();
                    $playerGameAccount->main_game_plat_id  = GameCache::getGamePlatId($mainGamePlatCode);
                    $playerGameAccount->main_game_plat_code= $mainGamePlatCode;
                    $playerGameAccount->carrier_id         = $user->carrier_id;
                    $playerGameAccount->player_id          = $user->player_id;
                    $playerGameAccount->rid                = $user->rid;
                    $playerGameAccount->prefix             = $user->prefix;
                    $playerGameAccount->account_user_name  = $output['data']['accountUserName'];
                    $playerGameAccount->password           = $output['data']['password'];
                    $playerGameAccount->save();
                }
            } 
            return $output;
        } else{
            return ['success' => false, 'data' => [], 'message' => '创建用户失败','code'=>200];
        }
    }

    //创建用户
    public function updateMember($user)
    {
        $language          = CarrierCache::getLanguageByPrefix($user->prefix);
        $mainGamePlatCode  = request()->get('mainGamePlatCode');
        $accountUserName   = request()->get('accountUserName');
        $password          = request()->get('password');
        $odds              = request()->get('odds');
        $param = [
            'mainGamePlatCode' => $mainGamePlatCode,
            'accountUserName'  => $accountUserName,
            'password'         => $password,
            'odds'             => $odds,
            'language'         => $language,
            'currency'         => CarrierCache::getCurrencyByPrefix($user->prefix),
        ];

        $output = $this->request(config('game')['pub']['gameurl'].'/api/'.self::UPDATEMEMBER,$param);
        if($output['success']){
            return $output;
        } else{
            return returnApiJson(config('language')[$language]['error64'], 0);
        }
    }

    public function getBalance($data=null)
    {
        if(is_null($data)){
            $mainGamePlatCode  = request()->get('mainGamePlatCode');
            $accountUserName   = request()->get('accountUserName');
            $password          = request()->get('password');
        } else {
            $mainGamePlatCode  = $data['mainGamePlatCode'];
            $accountUserName   = $data['accountUserName'];
            $password          = $data['password'];
        }

        $prefix     =  CarrierCache::getPrefixByGameAcoount($mainGamePlatCode,$accountUserName,$password);

        $param = [
            'mainGamePlatCode' => $mainGamePlatCode,
            'accountUserName'  => $accountUserName,
            'password'         => $password,
            'language'         => CarrierCache::getLanguageByPrefix($prefix),
            'currency'         => CarrierCache::getCurrencyByPrefix($prefix),
        ];
        
        $output = $this->request(config('game')['pub']['gameurl'].'/api/'.self::GETBALANCE,$param);

        //更新余额
        if(isset($output['success']) && $output['success']===true){
            $playerGameAccount                 = PlayerGameAccount::where('account_user_name',$accountUserName)->where('main_game_plat_code',$mainGamePlatCode)->first();
            if($playerGameAccount){
                $playerGameAccount->balance        = $output['data']['balance'];
                $playerGameAccount->save();
            }
            if(is_string($output['data']['balance'])){
                $output['data']['balance'] = floatval($output['data']['balance']);
            }   
        } else{
            \Log::info('查询余额的参数值是',['aaa'=>$param]);
            \Log::info('查询余额的返回值是',['bbb'=>$output]);
        }

        return $output;
    }

    public function transferIn($user)
    {
        $input           = request()->all();
        $language        = CarrierCache::getLanguageByPrefix($user->prefix);

        //开启免转处理
        if($user->is_notransfer){
            $transferKey ='gametranfer_'.$user->player_id;
            cache()->put($transferKey,$input['mainGamePlatCode']);
        }
        
        $cacheKey   = "transfer_" .$input['accountUserName'];

        while(!Lock::addLock($cacheKey,3)){
            sleep(1);
        };
        
        $carrierGamePlat  = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',GameCache::getGamePlatId($input['mainGamePlatCode']))->first();
        $transferid       = time() . mt_rand(10000000, 99999999);

        $playerTransferCasion                           = new PlayerTransferCasino();
        //更新余额写日志
            try {
                \DB::beginTransaction();
                $playAccount             = PlayerAccount::where('player_id',$user->player_id)->lockForUpdate()->first();
                $minTraninGameplatAmount = CarrierCache::getCarrierConfigure($user->carrier_id,'min_tranin_gameplat_amount');

                if($input['price']<$minTraninGameplatAmount){
                    return returnApiJson(config('language')[$language]['error198'], 0);
                }

                if($input['price']%$minTraninGameplatAmount){
                    return returnApiJson(config('language')[$language]['error201'], 0);
                }

                if($input['price']*10000>$playAccount->balance){
                    return returnApiJson(config('language')[$language]['error58'], 0);
                }

                //佣金检测
                $playerTransfer                 = PlayerTransfer::where('player_id',$user->player_id)->orderBy('id','desc')->first();
                if($playerTransfer && $playerTransfer->type == 'commission_from_child'){
                    $playerWithdrawFlowLimit                         = new PlayerWithdrawFlowLimit();
                    $playerWithdrawFlowLimit->carrier_id             = $user->carrier_id;
                    $playerWithdrawFlowLimit->top_id                 = $user->top_id;
                    $playerWithdrawFlowLimit->parent_id              = $user->parent_id;
                    $playerWithdrawFlowLimit->rid                    = $user->rid;
                    $playerWithdrawFlowLimit->player_id              = $user->player_id;
                    $playerWithdrawFlowLimit->user_name              = $user->user_name;
                    $playerWithdrawFlowLimit->limit_amount           = $playerTransfer->amount;
                    $playerWithdrawFlowLimit->limit_type             = 49;
                    $playerWithdrawFlowLimit->save();
                }

                $playerTransferCasion->player_id            = $user->player_id;
                $playerTransferCasion->carrier_id           = $user->carrier_id;
                $playerTransferCasion->user_name            = $user->user_name;
                $playerTransferCasion->account_user_name    = $input['accountUserName'];
                $playerTransferCasion->main_game_plat_id    = GameCache::getGamePlatId($input['mainGamePlatCode']);
                $playerTransferCasion->main_game_plat_code  = $input['mainGamePlatCode'];
                $playerTransferCasion->type                 = 1;
                $playerTransferCasion->price                = $input['price'];
                $playerTransferCasion->transferid           = $transferid;
                $playerTransferCasion->status               = 0;
                $playerTransferCasion->save();

                $playerTransefer                            = new PlayerTransfer();
                $playerTransefer->prefix                    = $user->prefix;
                $playerTransefer->carrier_id                = $user->carrier_id;
                $playerTransefer->rid                       = $user->rid;
                $playerTransefer->top_id                    = $user->top_id;
                $playerTransefer->parent_id                 = $user->parent_id;
                $playerTransefer->player_id                 = $user->player_id;
                $playerTransefer->is_tester                 = $user->is_tester;
                $playerTransefer->user_name                 = $user->user_name;
                $playerTransefer->level                     = $user->level;
                $playerTransefer->platform_id               = GameCache::getGamePlatId($input['mainGamePlatCode']);
                $playerTransefer->mode                      = 2;
                $playerTransefer->type                      = 'casino_transfer_out';
                $playerTransefer->type_name                 = '转出中心钱包';
                $playerTransefer->project_id                = $transferid;
                $playerTransefer->day_m                     = date('Ym');
                $playerTransefer->day                       = date('Ymd');
                $playerTransefer->amount                    = $input['price']*10000;
                $playerTransefer->before_balance            = $playAccount->balance;
                $playerTransefer->balance                   = $playAccount->balance - $input['price']*10000;
                $playerTransefer->before_frozen_balance     = $playAccount->frozen;
                $playerTransefer->frozen_balance            = $playAccount->frozen;

                $playerTransefer->before_agent_balance         = $playAccount->agentbalance;
                $playerTransefer->agent_balance                = $playAccount->agentbalance;
                $playerTransefer->before_agent_frozen_balance  = $playAccount->agentfrozen;
                $playerTransefer->agent_frozen_balance         = $playAccount->agentfrozen;
                $playerTransefer->save();

                $playAccount->balance                       = $playerTransefer->balance;
                $playAccount->save();

                PlayerGameAccount::where('player_id',$user->player_id)->where('account_user_name',$input['accountUserName'])->update(['exist_transfer'=>1]);
            
                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollback();
                Clog::recordabnormal('转帐异常：'.$e->getMessage());
                return returnApiJson($e->getMessage(), 0);
            }

            $param = [
                'transferid'       => $transferid,
                'accountUserName'  => $input['accountUserName'],
                'price'            => $input['price'],
                'password'         => $input['password'],
                'mainGamePlatCode' => $input['mainGamePlatCode'],
                'language'         => $language,
                'currency'         => CarrierCache::getCurrencyByPrefix($user->prefix),
            ];
               
            $output = $this->request(config('game')['pub']['gameurl'].'/api/'.self::TRANSFERIN,$param);
        
            try {
                \DB::beginTransaction();

                if($output['success']){
                    $playerTransferCasion->status =1;
                    $playerTransferCasion->save();

                    //更新三方钱包的值
                    $playerGameAccount                          = PlayerGameAccount::where('player_id',$user->player_id)->where('account_user_name',$input['accountUserName'])->first();
                    $playerGameAccount->balance                 = $input['price'];
                    $playerGameAccount->save();

                    \DB::commit();
                    return  $output;
                } elseif($output['data']['transfer_state']==0){

                    $playAccount  = PlayerAccount::where('player_id',$user->player_id)->lockForUpdate()->first();
                    $playerTransferCasion->status               = 2;
                    $playerTransferCasion->save();

                    $playerTransefer                            = new PlayerTransfer();
                    $playerTransefer->prefix                    = $user->prefix;
                    $playerTransefer->carrier_id                = $user->carrier_id;
                    $playerTransefer->rid                       = $user->rid;
                    $playerTransefer->top_id                    = $user->top_id;
                    $playerTransefer->parent_id                 = $user->parent_id;
                    $playerTransefer->player_id                 = $user->player_id;
                    $playerTransefer->is_tester                 = $user->is_tester;
                    $playerTransefer->user_name                 = $user->user_name;
                    $playerTransefer->level                     = $user->level;
                    $playerTransefer->platform_id               = GameCache::getGamePlatId($input['mainGamePlatCode']);
                    $playerTransefer->mode                      = 1;
                    $playerTransefer->type                      = 'casino_transfer_out_error';
                    $playerTransefer->type_name                 = '转出中心钱包失败';
                    $playerTransefer->project_id                = $transferid;
                    $playerTransefer->day_m                     = date('Ym');
                    $playerTransefer->day                       = date('Ymd');
                    $playerTransefer->amount                    = $input['price']*10000;
                    $playerTransefer->before_balance            = $playAccount->balance;
                    $playerTransefer->balance                   = $playAccount->balance + $input['price']*10000;
                    $playerTransefer->before_frozen_balance     = $playAccount->frozen;
                    $playerTransefer->frozen_balance            = $playAccount->frozen;

                    $playerTransefer->before_agent_balance         = $playAccount->agentbalance;
                    $playerTransefer->agent_balance                = $playAccount->agentbalance;
                    $playerTransefer->before_agent_frozen_balance  = $playAccount->agentfrozen;
                    $playerTransefer->agent_frozen_balance         = $playAccount->agentfrozen;
                    $playerTransefer->save();
                                
                    $playAccount->balance                       = $playerTransefer->balance;
                    $playAccount->save();

                    \DB::commit();
                    return returnApiJson(config('language')[$language]['error66'], 0);       
                } else{
                    if(isset($output['data']['transferid']) && $output['data']['transferid'] != $param['transferid']){
                        $playerTransferCasion->transferid = $output['data']['transferid'];
                        $playerTransferCasion->save();

                        $playerTransefer->project_id = $output['data']['transferid'];
                        $playerTransefer->save();
                    } else{
                        return returnApiJson('对不起,转帐状态未知', 0);
                    }
                }

            } catch (\Exception $e) {
                \DB::rollback();
                \Log::info('转帐的返回值是',['ccc'=>$output]);
                Clog::recordabnormal('转帐失败异常：'.$e->getMessage());
                return returnApiJson($e->getMessage(), 0);
            }
    }

    public function transferTo($user)
    {
        $input      = request()->all();
        $transferid = time() . mt_rand(10000000, 99999999);
        $cacheKey   = "transfer_" .$input['accountUserName'];
        $redisLock = Lock::addLock($cacheKey,3);
        while(!$redisLock){
            sleep(3);
        };

        $playerTransferCasion                       = new PlayerTransferCasino();
        $playerTransferCasion->account_user_name    = $input['accountUserName'];
        $playerTransferCasion->player_id            = $user->player_id;
        $playerTransferCasion->carrier_id           = $user->carrier_id;
        $playerTransferCasion->user_name            = $user->user_name;
        $playerTransferCasion->main_game_plat_id    = GameCache::getGamePlatId($input['mainGamePlatCode']);
        $playerTransferCasion->main_game_plat_code  = $input['mainGamePlatCode'];
        $playerTransferCasion->type                 = 2;
        $playerTransferCasion->price                = $input['price'];
        $playerTransferCasion->transferid           = $transferid;
        $playerTransferCasion->status               = 0;
        $playerTransferCasion->save();

        if($user->prefix=='')
        {
            \Log::info('用户取不到前辍的值是',['aaa'=>$user]);
        }

        $param = [
            'transferid'       => $transferid,
            'accountUserName'  => $input['accountUserName'],
            'price'            => $input['price'],
            'password'         => $input['password'],
            'mainGamePlatCode' => $input['mainGamePlatCode'],
            'language'         => CarrierCache::getLanguageByPrefix($user->prefix),
            'currency'         => CarrierCache::getCurrencyByPrefix($user->prefix),
        ];

        $output     = $this->request(config('game')['pub']['gameurl'].'/api/'.self::TRANSFERTO,$param);
   
        try {       
            if($output['success']) {
                \DB::beginTransaction();
                
                $playAccount                                = PlayerAccount::where('player_id',$user->player_id)->lockForUpdate()->first();
                $playerTransferCasion->status               = 1;
                $playerTransferCasion->save();

                $playerTransefer                            = new PlayerTransfer();
                $playerTransefer->carrier_id                = $user->carrier_id;
                $playerTransefer->prefix                    = $user->prefix;
                $playerTransefer->rid                       = $user->rid;
                $playerTransefer->top_id                    = $user->top_id;
                $playerTransefer->parent_id                 = $user->parent_id;
                $playerTransefer->player_id                 = $user->player_id;
                $playerTransefer->is_tester                 = $user->is_tester;
                $playerTransefer->user_name                 = $user->user_name;
                $playerTransefer->level                     = $user->level;
                $playerTransefer->platform_id               = GameCache::getGamePlatId($input['mainGamePlatCode']);
                $playerTransefer->mode                      = 1;
                $playerTransefer->type                      = 'casino_transfer_in';
                $playerTransefer->type_name                 = '转入中心钱包';
                $playerTransefer->project_id                = $transferid;
                $playerTransefer->day_m                     = date('Ym');
                $playerTransefer->day                       = date('Ymd');
                $playerTransefer->amount                    = $input['price']*10000;
                $playerTransefer->before_balance            = $playAccount->balance;
                $playerTransefer->balance                   = $playAccount->balance + $input['price']*10000;
                $playerTransefer->before_frozen_balance     = $playAccount->frozen;
                $playerTransefer->frozen_balance            = $playAccount->frozen;
                $playerTransefer->before_agent_balance         = $playAccount->agentbalance;
                $playerTransefer->agent_balance                = $playAccount->agentbalance;
                $playerTransefer->before_agent_frozen_balance  = $playAccount->agentfrozen;
                $playerTransefer->agent_frozen_balance         = $playAccount->agentfrozen;
                $playerTransefer->save();

                $playAccount->balance                       = $playerTransefer->balance;
                $playAccount->save();

                //更新三方钱包的值
                $playerGameAccount                          = PlayerGameAccount::where('player_id',$user->player_id)->where('account_user_name',$input['accountUserName'])->first();
                $playerGameAccount->balance                 = 0;
                $playerGameAccount->save();

            } elseif($output['data']['transfer_state']==0){
                $playerTransferCasion->status =2;
                $playerTransferCasion->save();
            } else {
                if(isset($output['data']['transferid']) && $output['data']['transferid'] != $param['transferid']){
                    $playerTransferCasion->transferid = $output['data']['transferid'];
                    $playerTransferCasion->save();

                    $playerTransefer->project_id = $output['data']['transferid'];
                    $playerTransefer->save();
                } else{
                    return returnApiJson('对不起,转帐状态未知', 0);
                }
            }
            \DB::commit();
            Lock::release($redisLock);
            return $output;
        } catch (\Exception $e) {
            \DB::rollback();
            Lock::release($redisLock);
            
            Clog::recordabnormal('平台'.$input['mainGamePlatCode'].'转帐失败异常：'.$e->getMessage(),['a'=>$output]);
            return returnApiJson($e->getMessage(), 0);
        }
    }

    public function checkTransfer($playerTransferCasion)
    {
        $input               = request()->all();
        $prefix              =  CarrierCache::getPrefixByGameAcoount($input['mainGamePlatCode'],$input['accountUserName'],$input['password']);

        $param = [
            'transferId'       => $input['transferId'],
            'accountUserName'  => $input['accountUserName'],
            'password'         => $input['password'],
            'direction'        => $input['direction'],
            'mainGamePlatCode' => $input['mainGamePlatCode'],
            'language'         => CarrierCache::getLanguageByPrefix($prefix),
            'currency'         => CarrierCache::getCurrencyByPrefix($prefix),
        ];
           
        $output           = $this->request(config('game')['pub']['gameurl'].'/api/'.self::CHECKTRANSFER,$param);
        
        if($output['success']){
            if($input['direction']==1){
                $playerTransferCasion->status =1;
                $playerTransferCasion->save();
            } else {
                $carrierGamePlat = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',$playerTransferCasion->main_game_plat_id)->first();
                try {
                    \DB::beginTransaction();

                    $currCarrier                                = Carrier::where('id',$this->carrier->id)->lockForUpdate()->first();
                    $playAccount                                = PlayerAccount::where('player_id',$playerTransferCasion->player_id)->lockForUpdate()->first();
                    $player                                     = Player::where('player_id',$playerTransferCasion->player_id)->first();
                    $playerTransferCasion->status               = 1;
                    $playerTransferCasion->save();

                    $playerTransefer                            = new PlayerTransfer();
                    $playerTransefer->prefix                    = $player->prefix;
                    $playerTransefer->carrier_id                = $playAccount->carrier_id;
                    $playerTransefer->rid                       = $playAccount->rid;
                    $playerTransefer->top_id                    = $playAccount->top_id;
                    $playerTransefer->parent_id                 = $playAccount->parent_id;
                    $playerTransefer->player_id                 = $playAccount->player_id;
                    $playerTransefer->is_tester                 = $playAccount->is_tester;
                    $playerTransefer->user_name                 = $playAccount->user_name;
                    $playerTransefer->level                     = $playAccount->level;
                    $playerTransefer->platform_id               = GameCache::getGamePlatId($input['mainGamePlatCode']);
                    $playerTransefer->mode                      = 1;
                    $playerTransefer->type                      = 'casino_transfer_in';
                    $playerTransefer->type_name                 = '转入中心钱包';
                    $playerTransefer->project_id                = $playerTransferCasion->transferid;
                    $playerTransefer->day_m                     = date('Ym');
                    $playerTransefer->day                       = date('Ymd');
                    $playerTransefer->amount                    = $playerTransferCasion->price*10000;
                    $playerTransefer->before_balance            = $playAccount->balance;
                    $playerTransefer->balance                   = $playAccount->balance + $playerTransferCasion->price*10000;
                    $playerTransefer->before_frozen_balance     = $playAccount->frozen;
                    $playerTransefer->frozen_balance            = $playAccount->frozen;
                    $playerTransefer->before_agent_balance         = $playAccount->agentbalance;
                    $playerTransefer->agent_balance                = $playAccount->agentbalance;
                    $playerTransefer->before_agent_frozen_balance  = $playAccount->agentfrozen;
                    $playerTransefer->agent_frozen_balance         = $playAccount->agentfrozen;

                    $playerTransefer->save();

                    $playAccount->balance                       = $playerTransefer->balance;
                    $playAccount->save();

                    $remainQuota                                 = new RemainQuota();
                    $remainQuota->carrier_id                     = $this->carrier->id;
                    $remainQuota->amount                         = bcdiv(bcmul($playerTransferCasion->price,$carrierGamePlat->point,6),100,4);
                    $remainQuota->direction                      = 5;
                    $remainQuota->mark                           = '转入中心钱包'.'|'.$input['mainGamePlatCode'];
                    $remainQuota->game_account                   = $input['accountUserName'];
                    $remainQuota->before_remainquota             = $currCarrier->remain_quota;
                    $remainQuota->remainquota                    = bcadd($remainQuota->before_remainquota,$remainQuota->amount,4);
                    $remainQuota->save();

                    $currCarrier->remain_quota                 = $remainQuota->remainquota;
                    $currCarrier->save();

                    \DB::commit();
                } catch (\Exception $e) {
                    \DB::rollback(); 
                    Clog::recordabnormal('查询订单错误'.$e->getMessage());
                    return returnApiJson($e->getMessage(), 0);
                }
            }
            return returnApiJson("操作成功", 1,['status'=>1]);
        } else if($output['success']==false && $output['data']['transfer_state']==0){
            if($input['direction']==1){
                $carrierGamePlat = CarrierGamePlat::where('carrier_id',$this->carrier->id)->where('game_plat_id',$playerTransferCasion->main_game_plat_id)->first();
                try {
                    \DB::beginTransaction();

                    $playAccount                                = PlayerAccount::where('player_id',$playerTransferCasion->player_id)->lockForUpdate()->first();
                    $player                                     = Player::where('player_id',$playerTransferCasion->player_id)->first();
                    $currCarrier                                = Carrier::where('id',$this->carrier->id)->lockForUpdate()->first();
                    $playerTransferCasion->status               = 2;
                    $playerTransferCasion->save();

                    $playerTransefer                            = new PlayerTransfer();
                    $playerTransefer->prefix                    = $player->prefix;
                    $playerTransefer->carrier_id                = $playAccount->carrier_id;
                    $playerTransefer->rid                       = $playAccount->rid;
                    $playerTransefer->top_id                    = $playAccount->top_id;
                    $playerTransefer->parent_id                 = $playAccount->parent_id;
                    $playerTransefer->player_id                 = $playAccount->player_id;
                    $playerTransefer->is_tester                 = $playAccount->is_tester;
                    $playerTransefer->user_name                 = $playAccount->user_name;
                    $playerTransefer->level                     = $playAccount->level;
                    $playerTransefer->platform_id               = GameCache::getGamePlatId($input['mainGamePlatCode']);
                    $playerTransefer->mode                      = 1;
                    $playerTransefer->type                      = 'casino_transfer_out_error';
                    $playerTransefer->type_name                 = '转出中心钱包失败';
                    $playerTransefer->project_id                = $playerTransferCasion->transferid;
                    $playerTransefer->day_m                     = date('Ym');
                    $playerTransefer->day                       = date('Ymd');
                    $playerTransefer->amount                    = $playerTransferCasion->price*10000;
                    $playerTransefer->before_balance            = $playAccount->balance;
                    $playerTransefer->balance                   = $playAccount->balance + $playerTransferCasion->price*10000;
                    $playerTransefer->before_frozen_balance     = $playAccount->frozen;
                    $playerTransefer->frozen_balance            = $playAccount->frozen;
                    $playerTransefer->before_agent_balance         = $playAccount->agentbalance;
                    $playerTransefer->agent_balance                = $playAccount->agentbalance;
                    $playerTransefer->before_agent_frozen_balance  = $playAccount->agentfrozen;
                    $playerTransefer->agent_frozen_balance         = $playAccount->agentfrozen;
                    $playerTransefer->save();
                                    
                    $playAccount->balance                       = $playerTransefer->balance;
                    $playAccount->save();

                    $remainQuota                                 = new RemainQuota();
                    $remainQuota->carrier_id                     = $this->carrier->id;
                    $remainQuota->amount                         = bcdiv(bcmul($playerTransferCasion->price,$carrierGamePlat->point,6),100,4);
                    $remainQuota->direction                      = 9;
                    $remainQuota->game_account                   = $input['accountUserName'];
                    $remainQuota->before_remainquota             = $currCarrier->remain_quota;
                    $remainQuota->mark                           = '转出中心钱包失败'.'|'.$input['mainGamePlatCode'];
                    $remainQuota->remainquota                   = bcadd($remainQuota->before_remainquota,$remainQuota->amount,4);
                    $remainQuota->save();

                    $currCarrier->remain_quota                = $remainQuota->remainquota;
                    $currCarrier->save();

                    \DB::commit();
                } catch (\Exception $e) {
                    \DB::rollback(); 

                    Clog::recordabnormal('转出中心钱包失败异常:'.$e->getMessage());   
                    return returnApiJson($e->getMessage(), 0);
                }
                
            } else {
                $playerTransferCasion->status =2;
                $playerTransferCasion->save();
            }
            return returnApiJson("操作成功", 1,['status'=>0]);
        } else {
            return returnApiJson("操作成功", 1,['status'=>-1]);
        }
    }


    public function kick()
    {
        $input      = request()->all();

        $param = [
            'mainGamePlatCode' => $input['mainGamePlatCode'],
            'accountUserName'  => $input['accountUserName'],
            'password'         => $input['password']
        ];
           
        return $this->request(config('game')['pub']['gameurl'].'/api/'.self::KICK,$param);
    }
   

    public function joinGame()
    {
        $input      = request()->all();
        $frontUrl   = CarrierCache::getCarrierConfigure($this->carrier->id,'h5url');
        $prefix     =  CarrierCache::getPrefixByGameAcoount($input['mainGamePlatCode'],$input['accountUserName'],$input['password']);
        $param = [
            'mainGamePlatCode' => $input['mainGamePlatCode'],
            'gameCode'         => $input['gameCode'],
            'accountUserName'  => $input['accountUserName'],
            'password'         => $input['password'],
            'language'         => CarrierCache::getLanguageByPrefix($prefix),
            'currency'         => CarrierCache::getCurrencyByPrefix($prefix),
            'frontUrl'         => $frontUrl,
            'ip'               => real_ip()
        ];

        if(isset($input['odds'])){
            $param['odds'] = $input['odds'];
        } else if(isset($input['dm'])){
            $param['dm'] = $input['dm'];
        }

        $result                          = $this->request(config('game')['pub']['gameurl'].'/api/'.self::JOINGAME,$param);
        $games                           = Games::where('main_game_plat_code',$input['mainGamePlatCode'])->where('game_code',$input['gameCode'])->first();
        $playerGameAccount               = PlayerGameAccount::select('inf_player.player_id','inf_player.carrier_id')->leftJoin('inf_player','inf_player.player_id','=','inf_player_game_account.player_id')->where('inf_player_game_account.main_game_plat_code',$input['mainGamePlatCode'])->where('inf_player_game_account.account_user_name',$input['accountUserName'])->first();

        $playerRecent                    = new PlayerRecent();
        $playerRecent->carrier_id        = $playerGameAccount->carrier_id;
        $playerRecent->player_id         = $playerGameAccount->player_id;
        $playerRecent->game_id           = $games->game_id;
        $playerRecent->main_game_plat_id = $games->main_game_plat_id;
        $playerRecent->game_category     = $games->game_category;
        $playerRecent->game_code         = $games->game_code;
        $playerRecent->game_moblie_code  = $games->game_moblie_code;
        $playerRecent->save();

        cache()->put('joingame_'.$playerGameAccount->player_id,time());
        return  $result;
    }

    public function joinMobileGame()
    {
        $input      = request()->all();
        $frontUrl   = CarrierCache::getCarrierConfigure($this->carrier->id,'h5url');
        $prefix     =  CarrierCache::getPrefixByGameAcoount($input['mainGamePlatCode'],$input['accountUserName'],$input['password']);
        $param = [
            'mainGamePlatCode' => $input['mainGamePlatCode'],
            'gameCode'         => $input['gameCode'],
            'accountUserName'  => $input['accountUserName'],
            'password'         => $input['password'],
            'language'         => CarrierCache::getLanguageByPrefix($prefix),
            'currency'         => CarrierCache::getCurrencyByPrefix($prefix),
            'frontUrl'         => $frontUrl,
            'ip'               => real_ip()
        ];

        if(isset($input['odds'])){
            $param['odds'] = $input['odds'];
        } else if(isset($input['dm'])){
            $param['dm'] = $input['dm'];
        }

        $result                          = $this->request(config('game')['pub']['gameurl'].'/api/'.self::JOINMOBILEGAME,$param);
        $games                           = Games::where('main_game_plat_code',$input['mainGamePlatCode'])->where('game_code',$input['gameCode'])->first();
        $playerGameAccount               = PlayerGameAccount::select('inf_player.player_id','inf_player.carrier_id')->leftJoin('inf_player','inf_player.player_id','=','inf_player_game_account.player_id')->where('inf_player_game_account.main_game_plat_code',$input['mainGamePlatCode'])->where('inf_player_game_account.account_user_name',$input['accountUserName'])->first();

        $playerRecent                    = new PlayerRecent();
        $playerRecent->carrier_id        = $playerGameAccount->carrier_id;
        $playerRecent->player_id         = $playerGameAccount->player_id;
        $playerRecent->game_id           = $games->game_id;
        $playerRecent->main_game_plat_id = $games->main_game_plat_id;
        $playerRecent->game_category     = $games->game_category;
        $playerRecent->game_code         = $games->game_code;
        $playerRecent->game_moblie_code  = $games->game_moblie_code;
        
        cache()->put('joingame_'.$playerGameAccount->player_id,time());
        
        $playerRecent->save();

        return $result;
    }

    public function getBetRecord($carriers,$otherMainPlatCode=false)
    {
        $param = [
            'carrier' => json_encode($carriers)
        ];

        if($otherMainPlatCode){
            $param['mainGamePlatCode'] =config('game')['other']['mainGamePlatCode'][0];
        }

        $output                 = $this->request(config('game')['pub']['gameurl'].'/api/'.self::GETRECORD,$param);

        $insertPlayerBetFlowArr = [];
        $updatePlayerBetFlowArr = [];
        $accountUserNames       = [];
        
        if(isset($output['success'])){

            foreach ($output['data'] as $key => $value) {
                $playerId           = PlayerCache::getPlayerIdforPlatCode($value['main_game_plat_code'],$value['account_user_name']);
                $userName           = PlayerCache::getPlayerUserName($playerId);
                $isTester           = PlayerCache::getPlayerTester($playerId);
                $accountUserNames[] = $value['account_user_name'];

                //特殊处理假PG
                $value['is_material']           = 0;
                if($value['main_game_plat_code']=='cq95'){       ///
                    $value['main_game_plat_code'] = 'cq9';
                    $value['game_flow_code']      = '181232-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jdb5'){       ///
                    $value['main_game_plat_code'] = 'jdb';
                    $value['game_flow_code']      = '181233-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='fc5'){       ///
                    $value['main_game_plat_code'] = 'fc';
                    $value['game_flow_code']      = '181234-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='habanero5'){       ///
                    $value['main_game_plat_code'] = 'habanero';
                    $value['game_flow_code']      = '181236-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jili5'){       ///
                    $value['main_game_plat_code'] = 'jili';
                    $value['game_flow_code']      = '181240-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='pp5'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181243-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp5'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181244-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp6'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181245-'.$value['game_flow_code'];
                    $value['is_material']         = 1;
                } elseif($value['main_game_plat_code']=='pp6'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181246-'.$value['game_flow_code'];
                    $value['is_material']         = 1;
                } elseif($value['main_game_plat_code']=='cq97'){       ///
                    $value['main_game_plat_code'] = 'cq9';
                    $value['game_flow_code']      = '181247-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='cq98'){       ///
                    $value['main_game_plat_code'] = 'cq9';
                    $value['game_flow_code']      = '181258-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='cq99'){       ///
                    $value['main_game_plat_code'] = 'cq9';
                    $value['game_flow_code']      = '181259-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='pp7'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181248-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='pp8'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181260-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='pp9'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181261-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp7'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181249-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp8'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181262-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp9'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181263-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='habanero7'){       ///
                    $value['main_game_plat_code'] = 'habanero';
                    $value['game_flow_code']      = '181250-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='habanero8'){       ///
                    $value['main_game_plat_code'] = 'habanero';
                    $value['game_flow_code']      = '181264-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='habanero9'){       ///
                    $value['main_game_plat_code'] = 'habanero';
                    $value['game_flow_code']      = '181265-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='fc7'){       ///
                    $value['main_game_plat_code'] = 'fc';
                    $value['game_flow_code']      = '181255-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='fc8'){       ///
                    $value['main_game_plat_code'] = 'fc';
                    $value['game_flow_code']      = '181266-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='fc9'){       ///
                    $value['main_game_plat_code'] = 'fc';
                    $value['game_flow_code']      = '181267-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jdb7'){       ///
                    $value['main_game_plat_code'] = 'jdb';
                    $value['game_flow_code']      = '181256-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jdb8'){       ///
                    $value['main_game_plat_code'] = 'jdb';
                    $value['game_flow_code']      = '181268-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jdb9'){       ///
                    $value['main_game_plat_code'] = 'jdb';
                    $value['game_flow_code']      = '181269-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jili7'){       ///
                    $value['main_game_plat_code'] = 'jili';
                    $value['game_flow_code']      = '181257-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jili8'){       ///
                    $value['main_game_plat_code'] = 'jili';
                    $value['game_flow_code']      = '181270-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jili9'){       ///
                    $value['main_game_plat_code'] = 'jili';
                    $value['game_flow_code']      = '181271-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='ky1'){
                    $value['main_game_plat_code'] = 'ky';
                    $preGameFlowCode = explode('-', $value['game_flow_code']);
                    $index = count($preGameFlowCode);
                    if(isset($preGameFlowCode[1])){
                        $preGameFlowCode[1] = $preGameFlowCode[1].'1';
                    }
                    $value['game_flow_code']      = implode('-',$preGameFlowCode);
                }

                $mainGamePlat  = SystemCache::getMainGamePlat($value['main_game_plat_code']);

                if($playerId){
                    $playBetFlow = PlayerBetFlow::where(['player_id'=> $playerId,'main_game_plat_code'=>$value['main_game_plat_code'],'game_flow_code'=> $value['game_flow_code'],'bet_time'=> $value['bet_time']])->first();

                    if($playBetFlow){
                        if($playBetFlow->game_status != $value['game_status']){
                            $updeteRow                             = [];
                            $updeteRow['id']                       = $playBetFlow->id;
                            $updeteRow['game_status']              = $value['game_status'];
                            $updeteRow['bet_amount']               = $value['bet_amount'];
                            $updeteRow['available_bet_amount']     = $value['available_bet_amount'];
                            $updeteRow['bet_flow_available']       = $value['bet_flow_available'];
                            $updeteRow['company_win_amount']       = $value['company_win_amount'];
                            $updeteRow['api_data']                 = $value['api_data'];
                            $updeteRow['updated_at']               = date('Y-m-d H:i:s');
                            if($value['company_win_amount']>0){
                                $updeteRow['is_loss']               = 1;
                            } elseif($value['company_win_amount'] < 0){
                                $updeteRow['is_loss']               = 2;
                            } else{
                                $updeteRow['is_loss']               = 0;
                            }
                            $updatePlayerBetFlowArr[]              = $updeteRow;
                        }
                        
                    } else {
                        $insertRow                            = [];
                        $insertRow['player_id']               = $playerId;
                        $insertRow['is_tester']               = $isTester;
                        $insertRow['user_name']               = $userName;
                        $insertRow['carrier_id']              = PlayerCache::getCarrierId($playerId);
                        $insertRow['prefix']                  = PlayerCache::getPrefix($playerId);
                        $insertRow['game_id']                 = $value['game_id'];
                        $insertRow['game_name']               = $value['game_name'];
                        $insertRow['main_game_plat_id']       = $mainGamePlat->main_game_plat_id;
                        $insertRow['game_flow_code']          = $value['game_flow_code'];
                        $insertRow['game_status']             = $value['game_status'];
                        $insertRow['game_category']           = $value['game_category'];
                        $insertRow['main_game_plat_code']     = $value['main_game_plat_code'];
                        $insertRow['bet_amount']              = $value['bet_amount'];
                        $insertRow['available_bet_amount']    = $value['available_bet_amount'];
                        $insertRow['company_win_amount']      = $value['company_win_amount'];
                        $insertRow['bet_info']                = $value['bet_info'];
                        $insertRow['bet_flow_available']      = $value['bet_flow_available'];
                        $insertRow['day']                     = $value['day'];
                        $insertRow['bet_time']                = $value['bet_time'];
                        $insertRow['api_data']                = $value['api_data'];
                        $insertRow['isFeatureBuy']            = $value['isFeatureBuy'];
                        $insertRow['multi_spin_game']         = $value['multi_spin_game'];
                        $insertRow['account_user_name']       = $value['account_user_name'];
                        $insertRow['is_material']             = $value['is_material'];
                        $insertRow['whether_recharge']        = PlayerCache::getIswhetherRecharge($playerId);
                        $insertRow['created_at']              = date('Y-m-d H:i:s');
                        $insertRow['updated_at']              = date('Y-m-d H:i:s');
                        $insertRow['is_trygame']              = isset($value['is_trygame'])? 1:0;

                        if($value['company_win_amount']>0){
                            $insertRow['is_loss']               = 1;
                        } elseif($value['company_win_amount'] < 0){
                            $insertRow['is_loss']               = 2;
                        } else{
                            $insertRow['is_loss']               = 0;
                        }

                        $insertPlayerBetFlowArr[]            = $insertRow;

                        if(count($insertPlayerBetFlowArr)==1000){
                            //是最终状态
                            \DB::table('log_player_bet_flow')->insertOrIgnore($insertPlayerBetFlowArr);
                            $insertPlayerBetFlowArr = [];
                        }
                    }
                }
            }
            //批处理
            if(count($insertPlayerBetFlowArr)){
                \DB::table('log_player_bet_flow')->insertOrIgnore($insertPlayerBetFlowArr);
            }

            if(count($updatePlayerBetFlowArr)){
                $this->updateBatch($updatePlayerBetFlowArr);
            }

            $accountUserNames             = array_unique($accountUserNames);
            $playerGameAccounts           = PlayerGameAccount::whereIn('account_user_name',$accountUserNames)->get();

            dispatch(new SynGameAccountJob($playerGameAccounts));
        }
    }

    public function getBetTimeRecord($carriers,$startBetTime)
    {
        $param = [
            'carrier'     => json_encode($carriers),
            'startBetTime'=> $startBetTime
        ];
        
        $output                 = $this->request(config('game')['pub']['gameurl'].'/api/getRecordByBetTime',$param);
        $insertPlayerBetFlowArr = [];
        $updatePlayerBetFlowArr = [];
        
        if(isset($output['success'])){
            foreach ($output['data'] as $key => $value) {
                $playerId           = PlayerCache::getPlayerIdforPlatCode($value['main_game_plat_code'],$value['account_user_name']);
                $accountUserNames[] = $value['account_user_name'];
                
                //特殊处理假PG
                $value['is_material']           = 0;
                if($value['main_game_plat_code']=='cq95'){       ///
                    $value['main_game_plat_code'] = 'cq9';
                    $value['game_flow_code']      = '181232-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jdb5'){       ///
                    $value['main_game_plat_code'] = 'jdb';
                    $value['game_flow_code']      = '181233-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='fc5'){       ///
                    $value['main_game_plat_code'] = 'fc';
                    $value['game_flow_code']      = '181234-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='habanero5'){       ///
                    $value['main_game_plat_code'] = 'habanero';
                    $value['game_flow_code']      = '181236-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jili5'){       ///
                    $value['main_game_plat_code'] = 'jili';
                    $value['game_flow_code']      = '181240-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='pp5'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181243-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp5'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181244-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp6'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181245-'.$value['game_flow_code'];
                    $value['is_material']         = 1;
                } elseif($value['main_game_plat_code']=='pp6'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181246-'.$value['game_flow_code'];
                    $value['is_material']         = 1;
                } elseif($value['main_game_plat_code']=='cq97'){       ///
                    $value['main_game_plat_code'] = 'cq9';
                    $value['game_flow_code']      = '181247-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='cq98'){       ///
                    $value['main_game_plat_code'] = 'cq9';
                    $value['game_flow_code']      = '181258-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='cq99'){       ///
                    $value['main_game_plat_code'] = 'cq9';
                    $value['game_flow_code']      = '181259-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='pp7'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181248-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='pp8'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181260-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='pp9'){       ///
                    $value['main_game_plat_code'] = 'pp';
                    $value['game_flow_code']      = '181261-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp7'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181249-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp8'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181262-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jp9'){       ///
                    $value['main_game_plat_code'] = 'pg';
                    $value['game_flow_code']      = '181263-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='habanero7'){       ///
                    $value['main_game_plat_code'] = 'habanero';
                    $value['game_flow_code']      = '181250-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='habanero8'){       ///
                    $value['main_game_plat_code'] = 'habanero';
                    $value['game_flow_code']      = '181264-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='habanero9'){       ///
                    $value['main_game_plat_code'] = 'habanero';
                    $value['game_flow_code']      = '181265-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='fc7'){       ///
                    $value['main_game_plat_code'] = 'fc';
                    $value['game_flow_code']      = '181255-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='fc8'){       ///
                    $value['main_game_plat_code'] = 'fc';
                    $value['game_flow_code']      = '181266-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='fc9'){       ///
                    $value['main_game_plat_code'] = 'fc';
                    $value['game_flow_code']      = '181267-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jdb7'){       ///
                    $value['main_game_plat_code'] = 'jdb';
                    $value['game_flow_code']      = '181256-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jdb8'){       ///
                    $value['main_game_plat_code'] = 'jdb';
                    $value['game_flow_code']      = '181268-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jdb9'){       ///
                    $value['main_game_plat_code'] = 'jdb';
                    $value['game_flow_code']      = '181269-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jili7'){       ///
                    $value['main_game_plat_code'] = 'jili';
                    $value['game_flow_code']      = '181257-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jili8'){       ///
                    $value['main_game_plat_code'] = 'jili';
                    $value['game_flow_code']      = '181270-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='jili9'){       ///
                    $value['main_game_plat_code'] = 'jili';
                    $value['game_flow_code']      = '181271-'.$value['game_flow_code'];
                } elseif($value['main_game_plat_code']=='ky1'){
                    $value['main_game_plat_code'] = 'ky';
                    $preGameFlowCode = explode('-', $value['game_flow_code']);
                    $index = count($preGameFlowCode);
                    if(isset($preGameFlowCode[1])){
                        $preGameFlowCode[1] = $preGameFlowCode[1].'1';
                    }
                    $value['game_flow_code']      = implode('-',$preGameFlowCode);
                }

                $userName      = PlayerCache::getPlayerUserName($playerId);
                $isTester      = PlayerCache::getPlayerTester($playerId);
                $mainGamePlat  = SystemCache::getMainGamePlat($value['main_game_plat_code']);

                if($playerId){
                    $playBetFlow = PlayerBetFlow::where(['player_id'=> $playerId,'main_game_plat_code'=>$value['main_game_plat_code'],'game_flow_code'=> $value['game_flow_code'],'bet_time'=> $value['bet_time']])->first();

                    if($playBetFlow){
                        if($playBetFlow->game_status != $value['game_status']){
                            $updeteRow                             = [];
                            $updeteRow['id']                       = $playBetFlow->id;
                            $updeteRow['game_status']              = $value['game_status'];
                            $updeteRow['bet_amount']               = $value['bet_amount'];
                            $updeteRow['available_bet_amount']     = $value['available_bet_amount'];
                            $updeteRow['bet_flow_available']       = $value['bet_flow_available'];
                            $updeteRow['company_win_amount']       = $value['company_win_amount'];
                            $updeteRow['api_data']                 = $value['api_data'];
                            $updeteRow['updated_at']               = date('Y-m-d H:i:s');

                            if($value['company_win_amount']>0){
                                $updeteRow['is_loss']               = 1;
                            } elseif($value['company_win_amount'] < 0){
                                $updeteRow['is_loss']               = 2;
                            } else{
                                $updeteRow['is_loss']               = 0;
                            }

                            $updatePlayerBetFlowArr[]              = $updeteRow;
                        }
                        
                    } else {
                        $insertRow                            = [];
                        $insertRow['player_id']               = $playerId;
                        $insertRow['is_tester']               = $isTester;
                        $insertRow['user_name']               = $userName;
                        $insertRow['carrier_id']              = PlayerCache::getCarrierId($playerId);
                        $insertRow['prefix']                  = PlayerCache::getPrefix($playerId);
                        $insertRow['game_id']                 = $value['game_id'];
                        $insertRow['game_name']               = $value['game_name'];
                        $insertRow['main_game_plat_id']       = $mainGamePlat->main_game_plat_id;
                        $insertRow['game_flow_code']          = $value['game_flow_code'];
                        $insertRow['game_status']             = $value['game_status'];
                        $insertRow['game_category']           = $value['game_category'];
                        $insertRow['main_game_plat_code']     = $value['main_game_plat_code'];
                        $insertRow['bet_amount']              = $value['bet_amount'];
                        $insertRow['available_bet_amount']    = $value['available_bet_amount'];
                        $insertRow['company_win_amount']      = $value['company_win_amount'];
                        $insertRow['bet_info']                = $value['bet_info'];
                        $insertRow['bet_flow_available']      = $value['bet_flow_available'];
                        $insertRow['day']                     = $value['day'];
                        $insertRow['bet_time']                = $value['bet_time'];
                        $insertRow['api_data']                = $value['api_data'];
                        $insertRow['isFeatureBuy']            = $value['isFeatureBuy'];
                        $insertRow['multi_spin_game']         = $value['multi_spin_game'];
                        $insertRow['is_material']             = $value['is_material'];
                        $insertRow['whether_recharge']        = PlayerCache::getIswhetherRecharge($playerId);
                        $insertRow['created_at']              = date('Y-m-d H:i:s');
                        $insertRow['updated_at']              = date('Y-m-d H:i:s');

                        if($value['company_win_amount']>0){
                            $insertRow['is_loss']               = 1;
                        } elseif($value['company_win_amount'] < 0){
                            $insertRow['is_loss']               = 2;
                        } else{
                            $insertRow['is_loss']               = 0;
                        }

                        $insertPlayerBetFlowArr[]            = $insertRow;

                        if(count($insertPlayerBetFlowArr)==1000){
                            //是最终状态
                            \DB::table('log_player_bet_flow')->insertOrIgnore($insertPlayerBetFlowArr);
                            $insertPlayerBetFlowArr = [];
                        }
                    }
                }
            }

            //批处理
            if(count($insertPlayerBetFlowArr)){
                \DB::table('log_player_bet_flow')->insertOrIgnore($insertPlayerBetFlowArr);
            }

            if(count($updatePlayerBetFlowArr)){
                $this->updateBatch($updatePlayerBetFlowArr);
            }
        }

        return true;
    }

    public function updateBatch($multipleData = [])
    {
        $updateColumn    = ['id','game_status','bet_amount','available_bet_amount','bet_flow_available','company_win_amount','api_data','updated_at'];
        $referenceColumn = 'id';
        $updateSql       = "UPDATE log_player_bet_flow SET ";
        $sets            = [];
        $bindings        = [];
        foreach ($updateColumn as $uColumn) {
            $setSql = "`" . $uColumn . "` = CASE ";
            foreach ($multipleData as $data) {
                $setSql .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                $bindings[] = $data[$referenceColumn];
                $bindings[] = $data[$uColumn];
            }
            $setSql .= "ELSE `" . $uColumn . "` END ";
            $sets[] = $setSql;
        }
        $updateSql .= implode(', ', $sets);
        $whereIn   = collect($multipleData)->pluck($referenceColumn)->values()->all();
        $bindings  = array_merge($bindings, $whereIn);
        $whereIn   = rtrim(str_repeat('?,', count($whereIn)), ',');
        $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $referenceColumn . "` IN (" . $whereIn . ")";  
        \DB::update($updateSql, $bindings);
    }

    public function getLotteryCode()
    {
        $input      = request()->all();
        $param = [
            'mainGamePlatCode' => $this->plat
        ];

        return $this->request(config('game')['pub']['gameurl'].'/api/'.self::GETLOTTERYCODE,$param);
    }

    public function syncGame()
    {
         $param =[
            'type' => 'gameplat'
         ];
         $output = $this->request(config('game')['pub']['gameurl'].'/api/'.self::SYNCGAME,$param);
         Clog::writeLog('Gameplat', '平台数据', $output);
         if(isset($output['success'])){
            foreach ($output['data'] as $key => $value) {
                $mainGamePlat = MainGamePlat::where('main_game_plat_id',$value['main_game_plat_id'])->first();
                if(!$mainGamePlat){
                    $gamePlat                       = new MainGamePlat();
                    $gamePlat->main_game_plat_id    = $value['main_game_plat_id'];
                    $gamePlat->main_game_plat_code  = $value['main_game_plat_code'];
                    $gamePlat->status               = $value['status'];
                    $gamePlat->sort                 = $value['sort'];
                    $gamePlat->alias                = $value['alias'];
                    $gamePlat->save();
                }
            }
         }
        $param['type'] = 'game';
        $output = $this->request(config('game')['pub']['gameurl'].'/api/'.self::SYNCGAME,$param);
        Clog::writeLog('syncGame', '游戏数据', $output);
        if(isset($output['success'])){
            foreach ($output['data'] as $key => $value) {
                $game = Games::where('game_id',$value['game_id'])->first();
                if(!$game){
                    $game                              = new Games();
                    $game->game_id                 = $value['game_id'];
                    $game->main_game_plat_id       = $value['main_game_plat_id'];
                    $game->game_category           = $value['game_category'];
                    $game->main_game_plat_code     = $value['main_game_plat_code'];
                    $game->game_name               = $value['game_name'];
                    $game->game_code               = $value['game_code'];
                    $game->game_moblie_code        = $value['game_moblie_code'];
                    $game->status                  = $value['status'];
                    $game->pageview                = $value['pageview'];
                    $game->is_recommend            = $value['is_recommend'];
                    $game->is_hot                  = $value['is_hot'];
                    $game->is_pool                 = $value['is_pool'];
                    $game->sort                    = $value['sort'];
                    $game->record_match_code       = $value['record_match_code'];
                    $game->save();
                }
            }
         }
         return true;
    }

    /**
     * gameapi
     * 2018/9/24 10:55
     * Administrator
     * xmlToArray
     * xml格式转数组
     * @param $xml
     * @return mixed
     */
    public function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }
}