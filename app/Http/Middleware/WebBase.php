<?php

namespace App\Http\Middleware;

use App\Exceptions\ErrMsg;
use App\Services\Context;
use App\Utils\Client\IP;
use App\Utils\Enum\RedisKeyEnum;
use App\Utils\Validator;
use App\Models\PlayerIpBlack;
use Closure;
use App\Models\Carrier;
use App\Lib\Cache\PlayerCache;
use App\Lib\Cache\CarrierCache;
use App\Jobs\PlayerCheckAndTransferOutJob;
use App\Models\CarrierPreFixDomain;
use App\Models\Log\PlayerSoftWareLogin;
use App\Models\Conf\CarrierMultipleFront;
use App\Models\Log\PlayerFingerprint;
use App\Models\Log\PlayerLogin;
use App\Lib\Cache\Lock;

class WebBase
{

    use Context;

    /**
     * Handle an incoming request.
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle ( $request, Closure $next ) 
    {
        $routeName = request()->route()->getName();
        $key       = 'ban_'.md5(real_ip());
        if(cache()->has($key)){
            return response()->json(['success'=>false,'message' => '对不起, 您请求太频繁请稍后再试!','data'=>[],'code'=>401],401)->send();
        }

        $params    = $request->all();

        $tokenHeader = request()->header('Authorization');
        if(!empty($tokenHeader) && (strpos($tokenHeader,'bearer')!==false || strpos($tokenHeader,'Bearer')!==false )) {
            $tokenHeaderArr = explode(' ', $tokenHeader);
            if(isset($tokenHeaderArr[1]) && $tokenHeaderArr[1] != 'undefined'){
                $authToken = $tokenHeaderArr[1];
            } else{
                $authToken = '';
            }
        } else{
            $authToken = '';
        }

        # 参数检查并告警
        self::checkParamsAndAlert($params);

        # API文档配置检查
        $this->checkApiConfig($routeName, $request);
        $url           = request()->header('Host');
        $url           = str_replace("http://", "", trim($url));
        $explodeArray  = explode('.',$url);

        if(count($explodeArray) != 3) {
            return response()->json(['success'=>false,'message' => '对不起, 请求的域名不正确1!','data'=>[],'code'=>401],401)->send();
        }

        $topDomain = $explodeArray[1].'.'.$explodeArray[2];
        $carrierId = CarrierCache::getCarrierByDomain($topDomain);

        if(!$carrierId) {
            return response()->json(['success'=>false,'message' => '对不起, 请求的域名不正确2!','data'=>[],'code'=>401],401)->send();
        }

        $currCarrier = Carrier::where('id',$carrierId)->first();

        if(!$currCarrier) {
            return response()->json(['success'=>false,'message' => '对不起, 商户不存在!','data'=>[],'code'=>401],401)->send();
        }

        if(config('main')['enable_limit_api']){
            #限制高频访问
            $key    = md5(real_ip()).'_key_'.$routeName;
            $number = md5(real_ip()).'_'.$routeName;

            if(cache()->has($key)){
                cache()->put($number,cache()->get($number)+1);
            } else {
                cache()->put($key,1,now()->addMinutes(1));
                cache()->put($number,1);
            }
            
            #如果高频直接禁用
            \Log::info('ip是'.real_ip().'请求路由'.$routeName.'次数是'.cache()->get($key));
            $unlimitKeys = array_keys(config('main')['unlimit']);
            if(in_array($routeName,$unlimitKeys)){
                $multiple = config('main')['unlimit'][$routeName];
                \Log::info('倍数的值是'.$multiple);

                if(cache()->get($number) > config('main')['limit_frequency']*$multiple){
                    $key = 'ban_'.md5(real_ip());
                    cache()->put($key,1,now()->addMinutes(10));
                }
            } elseif(cache()->get($number) > config('main')['limit_frequency']){
                $key = 'ban_'.md5(real_ip());
                cache()->put($key,1,now()->addMinutes(10));
            }
        }
        //执行提线
        $user = auth("api")->user();

        if($user && $user->carrier_id != $currCarrier->id) {
            return response()->json(['success'=>false,'message' => '对不起, 此用户不存在!','data'=>[],'code'=>401],401)->send();
        }

        if ( $user && $user->frozen_status==4) {
            $user->is_online = 0;
            $user->save();

            auth()->guard("api")->logout();
        }

        if($user && cache()->get($user->player_id.'_login') != $authToken ){
            auth()->guard("api")->logout();
        }

        if ( !in_array($routeName, config('guest')['web']) && !$this->isFreeAccess($routeName)) {

            if ( !auth("api")->user() ) {
                return response()->json([ 'success' => false, 'message' => '对不起, 用户未登录!', 'data' => [], 'code' => 401 ], 401)->send();
            }
        }

        $playerIpBlacks   = PlayerIpBlack::select('ips')->where('carrier_id',$currCarrier->id)->first();
        $memberIp         = getRealIP();

        if(!empty($playerIpBlacks->ips)){
            $ipblackArr  = explode(',',$playerIpBlacks->ips);
            if(in_array($memberIp, $ipblackArr)){

                return response()->json([ 'success' => false, 'message' => '对不起, 您的IP已被禁用请联系客服!', 'data' => [], 'code' => 401 ], 401)->send();
            }
        }

        $user = auth("api")->user();

        if($user) {
            $allUrl          = $request->url();
            $allUrlArr       = explode('/api/', $allUrl);

            if($allUrlArr[1]=='player/balance' || $allUrlArr[1] == 'deposit' || $allUrlArr[1] == 'player/bankcardadd'){
                $t1              = request()->header('T1');
                $t2              = request()->header('T2');
                $t3              = request()->header('T3');
                if(!is_numeric($t3)){
                    return response()->json([ 'success' => false, 'message' => '对不起, 请求数据异常!', 'data' => [], 'code' => 401 ], 401)->send();
                }
            
                $userNamelength  = strlen($user->user_name);
                $tempUserName    = substr($user->user_name, 0,$userNamelength-2);
                $mult3           = $t3*13;
                $selfT1          = md5($mult3.$tempUserName);
                
                $apiRouteName    = '/api/'.$allUrlArr[1];
                $selfT2          = md5($t1.$apiRouteName);
                $diffTime        = time()-bcdiv($t3, 1000,0);

                if($user->has_software_login != 1){
                    //确定是机器人。直接冻结
                    if($selfT1 != $t1 || $selfT2 !=$t2){
                        //冻结刷单用户
                        $user->frozen_status = 4;
                        if($allUrlArr[1] == 'deposit'){
                            $user->remark        = '软件刷单，系统自动冻结';
                        } else{
                            $user->remark        = '软件登录，系统自动冻结';
                        }
                        $user->has_software_login = 1;
                        $user->save();

                        //拉黑所有相关IP
                        $ipBlackList    = CarrierCache::getCarrierMultipleConfigure($user->carrier_id,'ip_blacklist',$user->prefix);
                        if(empty($ipBlackList)){
                            $ipBlackListArr = [];
                        } else{
                            $ipBlackListArr = explode(',',$ipBlackList);
                        }

                        if(!empty($user->register_ip)){
                            $ipBlackListArr[] = $user->register_ip;
                        }

                        if(!empty($user->login_ip)){
                            $ipBlackListArr[] = $user->login_ip;
                        }

                        $ipBlackListArr = array_unique($ipBlackListArr);
                        $ipBlackList    = implode(',', $ipBlackListArr);

                        CarrierMultipleFront::where('carrier_id',$user->carrier_id)->where('prefix',$user->prefix)->where('sign','ip_blacklist')->update(['value'=>$ipBlackList]);
                        CarrierCache::flushCarrierMultipleConfigure($user->carrier_id,$user->prefix); 

                        return response()->json([ 'success' => false, 'message' => '对不起，此帐号已被限制', 'data' => [], 'code' => 401 ], 401)->send();
                    } elseif(abs($diffTime) >180){
                        $playerSoftWareLogin = PlayerSoftWareLogin::where('player_id',$user->player_id)->first();
                        if(!$playerSoftWareLogin){
                            $playerSoftWareLogin                = new PlayerSoftWareLogin();
                            $playerSoftWareLogin->carrier_id    = $user->carrier_id;
                            $playerSoftWareLogin->prefix        = $user->prefix;
                            $playerSoftWareLogin->player_id     = $user->player_id;
                            $playerSoftWareLogin->user_name     = $user->user_name;
                            $playerSoftWareLogin->difftime      = $diffTime;
                            $playerSoftWareLogin->save();
                        } else{
                            if($diffTime > $playerSoftWareLogin->difftime+3){
                                $user->frozen_status = 4;
                                if($allUrlArr[1] == 'deposit'){
                                    $user->remark        = '软件刷单，系统自动冻结';
                                } else{
                                    $user->remark        = '软件登录，系统自动冻结';
                                }
                                $user->has_software_login = 1;
                                $user->save();

                                $playerSoftWareLogin->delete();

                                return response()->json([ 'success' => false, 'message' => '对不起，此帐号已被限制', 'data' => [], 'code' => 401 ], 401)->send();
                            }
                        }
                    }
                }
 
            }

            $cacheKey = "requesttime_" .$user->player_id;
            $redisLock = Lock::addLock($cacheKey,10);

            if ($redisLock) {
                try{
                    $fingerprint           = request()->header('fingerprint');
                    $osname                = request()->header('osname');

                    if($user->is_online == 0){
                        cache()->put('requesttime_'.$user->player_id,1,now()->addSeconds(10));
                        $user->is_online   = 1;
                        $user->requesttime = time();
                        $user->save();

                        //监控用户访问IP
                        $historyFingerprints = PlayerCache::getFingerprint($user->prefix,$user->player_id);
                        if(!in_array($fingerprint,$historyFingerprints) && !is_null($fingerprint) && !empty($fingerprint)){
                            $playerFingerprint             = new  PlayerFingerprint();
                            $playerFingerprint->carrier_id = $user->carrier_id;
                            $playerFingerprint->player_id  = $user->player_id;
                            $playerFingerprint->fingerprint= $fingerprint;
                            $playerFingerprint->prefix     = $user->prefix;
                            $playerFingerprint->save();

                            PlayerCache::forgetFingerprint($user->prefix,$user->player_id);
                        }

                        $historyIps   = PlayerCache::getIps($user->prefix,$user->player_id);
                        $loginIp      = real_ip();
                        if(!in_array($loginIp,$historyIps) && !is_null($loginIp) && !empty($loginIp)){
                            $domain                = request()->header('Origin');
                            $domain                = str_replace("https://", "", trim($domain));
                            $domain                = str_replace("http://", "", trim($domain));
                            $loginLocation         = IP::ipLocation(real_ip());

                            $playerLogin                   = new PlayerLogin();
                            $playerLogin->player_id        = $user->player_id;
                            $playerLogin->user_name        = $user->user_name;
                            $playerLogin->carrier_id       = $user->carrier_id;
                            $playerLogin->login_ip         = $loginIp;
                            $playerLogin->login_domain     = $domain;
                            $playerLogin->login_time       = time();
                            $playerLogin->login_location   = $loginLocation;
                            $playerLogin->osName           = isset($osname) ? $osname:'';
                            $playerLogin->fingerprint      = isset($fingerprint) ? $fingerprint:'';
                            $playerLogin->prefix           = $user->prefix;
                            $playerLogin->save();

                            PlayerCache::forgetIps($user->prefix,$user->player_id);
                        }  
                    } else{
                        if(!cache()->get('requesttime_'.$user->player_id)){
                            cache()->put('requesttime_'.$user->player_id,1,now()->addSeconds(10));
                            $user->requesttime = time();
                            $user->save();
                            //监控用户访问IP
                            $historyFingerprints = PlayerCache::getFingerprint($user->prefix,$user->player_id);
                            if(!in_array($fingerprint,$historyFingerprints) && !is_null($fingerprint) && !empty($fingerprint)){
                                $playerFingerprint             = new  PlayerFingerprint();
                                $playerFingerprint->carrier_id = $user->carrier_id;
                                $playerFingerprint->player_id  = $user->player_id;
                                $playerFingerprint->fingerprint= $fingerprint;
                                $playerFingerprint->prefix     = $user->prefix;
                                $playerFingerprint->save();

                                PlayerCache::forgetFingerprint($user->prefix,$user->player_id);
                            }

                            $historyIps   = PlayerCache::getIps($user->prefix,$user->player_id);
                            $loginIp      = real_ip();
                            if(!in_array($loginIp,$historyIps) && !is_null($loginIp) && !empty($loginIp)){
                                $domain                = request()->header('Origin');
                                $domain                = str_replace("https://", "", trim($domain));
                                $domain                = str_replace("http://", "", trim($domain));
                                $loginLocation         = IP::ipLocation(real_ip());

                                $playerLogin                   = new PlayerLogin();
                                $playerLogin->player_id        = $user->player_id;
                                $playerLogin->user_name        = $user->user_name;
                                $playerLogin->carrier_id       = $user->carrier_id;
                                $playerLogin->login_ip         = $loginIp;
                                $playerLogin->login_domain     = $domain;
                                $playerLogin->login_time       = time();
                                $playerLogin->login_location   = $loginLocation;
                                $playerLogin->osName           = isset($osname) ? $osname:'';
                                $playerLogin->fingerprint      = isset($fingerprint) ? $fingerprint:'';
                                $playerLogin->prefix           = $user->prefix;
                                $playerLogin->save();

                                PlayerCache::forgetIps($user->prefix,$user->player_id);
                            }  
                        } 
                    }

                    Lock::release($redisLock);
                }catch (\Exception $e) {
                    Lock::release($redisLock);
                    \Log::info('登录中间件异常'.$e->getMessage());
                }
            } 
        }

        # 使变量可全局调用
        $request->offsetSet('merchant',$currCarrier->toArray());
        $request->offsetSet('carrier',$currCarrier->toArray());

        return $next($request);
    }


    /**
     * API文档+参数配置统一校验
     * @param                          $curRoute
     * @param \Illuminate\Http\Request $request
     * @return bool|\Illuminate\Http\JsonResponse
     */
    private function checkApiConfig ( $curRoute, $request ) {

        $params    = $request->all();
        $apiConfig = config('api-web');
        $apiList   = $apiConfig['apiList'];
        $reqPath   = str_replace('/', '_', $curRoute);

        # 获取当前API配置
        if ( empty($apiList[$reqPath]) ) {
            return true;
        }
        $curApiConfig = $apiList[$reqPath];

        # 检查接口是否启用
        if ( isset($curApiConfig['enable']) && $curApiConfig['enable'] === false ) {
            return response()->json([ 'success' => false, 'message' => '对不起, 待访问资源已被停用!', 'data' => [], 'code' => 404 ], 404)->send();
        }

        # 检查HTTP-METHOD
        if ( strtoupper($curApiConfig['http_method']) !== strtoupper(request()->method()) ) {
            return response()->json([ 'success' => false, 'message' => '对不起,不被允许的资源请求方式!', 'data' => [], 'code' => 405 ], 405)->send();
        }

        # 数据验证规则
        $dataRule = !empty($curApiConfig['data_rules']) ? $curApiConfig['data_rules'] : [];
        if ( !empty($dataRule) ) {
            $this->checkParams($params, $dataRule);
        }

        # 接口访问规则
        $accessRule = [
            'title'       => $curApiConfig['title'],
            'req_path'    => $reqPath,
            'is_free'     => isset($curApiConfig['is_free']) ? (bool)$curApiConfig['is_free'] : false,
            'guest_visit' => isset($curApiConfig['guest_visit']) ? (bool)$curApiConfig['guest_visit'] : false,
            'check_sign'  => isset($curApiConfig['check_sign']) ? (bool)$curApiConfig['check_sign'] : true, // 默认开启
            'http_method' => strtoupper($curApiConfig['http_method']),
        ];

        # 多语言设置
        $langRule = [ 'is_translate' => true ];
        if ( !empty($curApiConfig['lang_config']) ) {
            $langRule = array_merge($langRule, $curApiConfig['lang_config']);
        }

        # 使变量可全局调用
        $request->attributes->add([ 'curApi' => $curApiConfig ]);
        $request->attributes->add([ 'accessRule' => $accessRule ]);
        $request->attributes->add([ 'langRule' => $langRule ]);
        $request->attributes->add([ 'safeParams' => Validator::getCheckedParams() ]);
        $request->attributes->add([ 'searchRule' => Validator::getSearchRules() ]);
    }

    /**
     * 是否公共访问资源
     * @param $curRoute
     * @return bool
     */
    private function isFreeAccess ( $curRoute ) {
        $curApiCfg = request()->get('curApi');

        if ( empty($curApiCfg) ) return false;
        if ( !isset($curApiCfg['is_free']) ) return false;

        return (bool)$curApiCfg['is_free'];
    }

    /**
     * 参数统一校验
     * @param $data
     * @param $dataRule
     * @return \Illuminate\Http\JsonResponse
     */
    private function checkParams ( $data, $dataRule ) {
        # 参数校验
        if ( !Validator::validate($data, $dataRule) ) {
//            throw new ErrParams(Validator::getErrMsg());
            return response()->json([ 'success' => false, 'message' => Validator::getErrMsg(), 'data' => [
                'params' => $data,
                'rules'  => $dataRule
            ], 'code' => 200 ], 200)->send();
        }
    }
}
