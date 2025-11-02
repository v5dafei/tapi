<?php

namespace App\Http\Controllers;

use App\Utils\Enum\ClientEnum;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Services\Context;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, Context;

    /**
     * 获取商户号
     * @return mixed
     */
    public static function getMerId () {
        $merchant = (array)request()->get('merchant');
        return !empty($merchant['id']) ? $merchant['id'] : 0;
        return 0; // todo
    }

    /**
     * 获取商户账户
     * @return mixed
     */
    public static function getMerUsr () {
        $merchant = (array)request()->get('merchantAdmin');
        return !empty($merchant['username']) ? $merchant['username'] : '';
    }

    /**
     * @title  通用请求数据获取（已验证数据、支持：GET/POST/PUT/DELETE）
     * @return array
     * @author benjamin
     */
    public function getSafeParams ( $options = [] ) {
        $data = request()->get('safeParams');

        # 授权token排除
        if ( !empty($data['token']) ) {
            unset($data['token']);
        }

        # 主动增加商户参数
        if ( !empty($options['addMer']) && empty($data['carrier_id'])) {
            $data = array_merge([ 'carrier_id' => $this->getMerId() ], $data);
        }

        return $data;
    }

    /**
     * @title  获取某条请求数据
     * @param null $key
     * @param null $default
     * @return array|string|null
     * @author benjamin
     */
    public function getSafeParaByKey ( $key = null, $default = null ) {
        $data = $this->getSafeParams(['addMer' => true]);
        if ( !empty($key) ) {
            return !empty($data[$key]) ? $data[$key] : $default;
        }
        return $data;
    }

    /**
     * todo 待完善
     * @title  获取客户端类型
     * @return string pc|wap|app
     * @author benjamin
     */
    public function getClientType ( $ua = null ) {
        return ClientEnum::CLIENT_TYPE_APP;

        if ( empty($ua) ) {
            $uaParser = $this->uaParser;
        } else {
            $uaParser = (new \WhichBrowser\Parser($ua));
        }

        $isMobile = $uaParser->isMobile();
        $browser  = $uaParser->browser->getName();
        $os       = $uaParser->os->getName();
        $osVer    = $uaParser->os->getVersion();

        # 桌面：desktop | mobile | media | tablet
        $deviceType = $uaParser->device->type;

        # Browser 组件
//        if ( $os === 'Android' && $browser === $os ) {
//            $clientType = self::CLIENT_TYPE_APP;
//        } else if ( $os === 'iPhone' && $browser === $os ) {
//            $clientType = self::CLIENT_TYPE_APP;
//        } else if ( ($os === 'Android' || $os === 'iPhone') && !empty($browser) && $browser !== $os ) {
//            $clientType = self::CLIENT_TYPE_WAP;
//        } else if ( $os === 'Windows' && !$uaParser->isMobile() ) {
//            $clientType = self::CLIENT_TYPE_PC;
//        } else {
//            $clientType = self::CLIENT_TYPE_APP;
//        }

        # WhichBrowser 组件
        if ( $isMobile && in_array($os, [ 'Android', 'iOS' ]) ) {
            $clientType = self::CLIENT_TYPE_APP;
        } else if ( $isMobile && 1 != 1 ) {
            $clientType = self::CLIENT_TYPE_WAP;
        } else if ( !$isMobile && in_array($os, [ 'Windows', 'OS X', 'Mac OS X' ]) ) {
            $clientType = self::CLIENT_TYPE_PC;
        } else {
            $clientType = self::CLIENT_TYPE_APP;
        }

        return $clientType;
    }
}
