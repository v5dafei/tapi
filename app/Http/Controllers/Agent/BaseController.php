<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Lib\Cache\CarrierCache;

class BaseController extends Controller
{
    protected $agent    = null;
    protected $carrier  = null;
    protected $domain   = null;
    protected $prefix   = null;
    protected $language = null;
    protected $currency = null;

    public function __construct () 
    {

        $url                = request()->header('Host');
        $url                = str_replace("http://", "", trim($url));
        $explodeArray       = explode('.', $url);
        $explodeheaderArray = explode('-', $explodeArray[0]);
        $topDomain          = $explodeArray[1].'.'.$explodeArray[2];

        $carrierId = CarrierCache::getCarrierByDomain($topDomain);

        if(!$carrierId) {
            return response()->json(['success'=>false,'message' => '对不起, 请求的域名不正确2!','data'=>[],'code'=>401],401)->send();
        }

        $this->carrier = Carrier::where('id',$carrierId)->first();
        if(!isset($this->carrier->id)){
            \Log::info('获取的URL是'.$url.'carrerId是'.$carrierId.'查出来的carrier的值是',['ddd'=>$this->carrier]);
        }
        $this->prefix   = CarrierCache::getPreFixByDomain($topDomain);
        $this->language = CarrierCache::getLanguageByDomain($topDomain);
        $this->currency = CarrierCache::getCurrencyByDomain($topDomain);
        $this->agent    = auth("agent")->user();
    }
}