<?php

namespace App\Http\Controllers\Web;

use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Utils\Arr\ArrHelper;
use App\Lib\Cache\CarrierCache;

class BaseController extends Controller
{
    protected $user         = null;
    protected $authUser     = null;
    protected $carrier      = null;
    protected $merchant     = null;
    protected $prefix       = null;
    protected $language     = null;
    protected $smsPassageId = null;
    protected $currency     = null;
    protected $authToken    = null;

    protected $domain       = null;

    public function __construct () {

        define('ERR_MSG', '可能服务器繁忙,请稍后重试！');
        define('MER_ID', 'carrier_id');
        define('INIT_TIME', microtime(true));

        # 调试模式相关处理
        if ( $this->isDebugMode() ) {
            # 开启SQL执行监听
            \DB::enableQueryLog();
        }

        $tokenHeader = request()->header('Authorization');
        if(!empty($tokenHeader) && (strpos($tokenHeader,'bearer')!==false) || strpos($tokenHeader,'Bearer')!==false) {
            $authInfo = explode(' ', $tokenHeader);
            $this->authToken = count($authInfo) > 1 ? $authInfo[1] : $authInfo[0];
        }

        $url                = request()->header('Host');
        $url                = str_replace("http://", "", trim($url));
        $explodeArray       = explode('.', $url);
        if(count($explodeArray)!=3){
            return response()->json(['success'=>false,'message' => config('language')[$this->language]['error61'],'data'=>[],'code'=>401],401)->send();
        }
        $topDomain          = $explodeArray[1].'.'.$explodeArray[2];
        $explodeheaderArray = explode('-', $explodeArray[0]);
        $carrierId           = CarrierCache::getCarrierByDomain($topDomain);
        $this->carrier       = $currCarrier = Carrier::where('id',$carrierId)->first();
        $this->prefix        = CarrierCache::getPreFixByDomain($topDomain);
        $this->language      = CarrierCache::getLanguageByDomain($topDomain);
        $this->currency      = CarrierCache::getCurrencyByDomain($topDomain);
        $this->smsPassageId  = CarrierCache::getSmsPassageIdByDomain($topDomain);
        
        try {
            $this->user          = auth("api")->user();
        } catch (JWTException $e) {
            return response()->json(['success'=>false,'message' => config('language')[$this->language]['error284'],'data'=>[],'code'=>401],401)->send();
        }
        

        if(!empty($this->user)) {
            $this->authUser = ArrHelper::objToArr($this->user);
            $this->authUser['isTest'] = 0;
            $this->authUser['uid']    = $this->authUser['player_id'];
        }

        $this->merchant = ArrHelper::objToArr($this->carrier);

        request()->offsetSet('prefix',$this->prefix);
        request()->attributes->add([ 'merchant' => ArrHelper::objToArr($this->carrier) ]);
    }
}