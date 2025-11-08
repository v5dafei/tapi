<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\CarrierIps;
use App\Models\Carrier;
use App\Utils\Arr\ArrHelper;

class BaseController extends Controller
{
    protected $carrierUser = null;
    protected $carrier     = null;
    protected $authToken   = null;

    public function __construct () {

        define('ERR_MSG', '可能服务器繁忙,请稍后重试！');
        define('MER_ID', 'carrier_id');
        define('INIT_TIME', microtime(true));

        # 后台不走翻译
        $this->isTranslate = false;

        # 调试模式相关处理
        if($this->isDebugMode()) {
            # 开启SQL执行监听
            \DB::enableQueryLog();
        }

        $tokenHeader = request()->header('Authorization');
        if(!empty($tokenHeader) && strpos($tokenHeader,'bearer')!==false) {
            $this->authToken = explode(' ', $tokenHeader)[1];
        }

        $routeName          = request()->route()->getName();
        $url                = request()->header('Host');
        $url                = str_replace("http://", "", trim($url));
        $explodeArray       = explode('.', $url);
        $explodeheaderArray = explode('-', $explodeArray[0]);
        $currCarrier        = Carrier::where('sign', strtoupper($explodeheaderArray[1]))->first();

        $this->carrier     = $currCarrier;
        $this->carrierUser = auth("carrier")->user();

        request()->attributes->add([ 'merchant' => ArrHelper::objToArr($this->carrier) ]);
        request()->attributes->add([ 'merchantAdmin' => ArrHelper::objToArr($this->carrierUser) ]);
    }
}
