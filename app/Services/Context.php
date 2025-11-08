<?php

namespace App\Services;

use App\Models\Carrier;
use App\Models\Conf\CarrierWebSite;
use App\Utils\Client\IP;
use Illuminate\Support\Facades\Redis;

/**
 * 框架上下文执行环境
 * Class Context
 * @package App\Services
 */
trait Context
{

    public static  $di          = null;
    private static $instance    = [];
    public         $isTranslate = false;

    # 通用响应格式
    public $res = [
        'code'    => 200,
        'success' => true,
        'message' => '',
        'data'    => [],
    ];

    # 通用下载处理
//    public $isDownLoad   = false;
    public $downLoadInfo = [
        'fileName' => 'csv_data',
        'columns'  => '',
        'data'     => []
    ];

    /**
     * 获取商户号
     * @return mixed
     */
    public static function getMerId () {
        $merchant = (array)request()->get('merchant');
        return !empty($merchant['id']) ? $merchant['id'] : 0;
        return 0; // todo
    }

    public static function getWebSite($getMerId = true) {
        if(!defined('YACONF_PRO_ENV')) define('YACONF_PRO_ENV', 'env');
        $website = \Yaconf::get(YACONF_PRO_ENV.'.APP_CODE', 'def_website');
        $merchant = self::getMerId();
        return $getMerId ? $website . '_'.$merchant : $website;
    }

    public function ip($ip2long = false) {
        return IP::getClientIp($ip2long);
    }

    public function returnApiJson($msg, $status = 0, $data = [], $code = 200, $errorcode='') {
        if(!empty($errorcode)){
            $data['errorcode']=$errorcode;
        }

        foreach ($data as $key => &$value) {
            if(is_float($value)){
                $value =(string)$value;
            }
        }

        $data = [
            'success'       => $status ? true :false,
            'message'       => $msg,
            'data'          => $data,
            'code'          => $code
        ];

        return response()->json($data)->setEncodingOptions(JSON_UNESCAPED_UNICODE)->header('X-Frame-Options', 'ALLOW-FROM');
    }

    /**
     * 成功响应内容
     * @param string $msg
     * @param array  $data
     * @param int    $code
     */
    public function success ( $msg = 'success', $data = [], $code = 200 ) {
        $res = [
            'code'    => $code,
            'success' => (bool)$code,
            'message' => $msg,
            'data'    => $data,
        ];

        $res = array_merge($this->res, $res);

        return response()->json($res)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE)
            ->header('X-Frame-Options', 'ALLOW-FROM')
            ->send();
    }

    /**
     * 失败响应内容
     * @param       $msg
     * @param array $data
     * @param int   $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function error ( $msg = 'error', $data = [], $code = 1, $status = 200 ) {

        $res = [
            'success' => false,
            'message' => $msg,
            'data'    => $data,
            'code'    => $code
        ];
        return response()->json($res, $status)
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE)
            ->header('X-Frame-Options', 'ALLOW-FROM')
            ->send();
    }

    public function isInit() {
        if ( !defined('INIT_TIME') || empty(INIT_TIME) ) {
            return false;
        }
        return true;
    }

    public function isDebugMode () {
        return config('app')['debug'];
    }

    /**
     * @title  单例通用插件
     * @return mixed
     * @author benjamin
     */
    public static function getInstance () {
        # 延迟获取当前调用的类
        $class = static::class;
        $k     = md5($class);

        if ( empty(self::$instance[$k]) ) {
            self::$instance[$k] = new $class();
        }

        return self::$instance[$k];
    }

    /**
     * 上下文手动初始化
     * @param $di
     * @return $this
     */
    public function initDi ( $di ) {
        self::$di = $di;
        return $this;
    }

    /**
     * 上下文属性获取
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function __get ( $name ) {
        $service = self::getDi($name);
        if ( $service ) {
            if ( property_exists($service, $name) ) {
                return $service->$name;
            }
        }
        throw new \Exception('调用了不存在的系统服务：' . $name);
    }

    /**
     * 上下文方法调用
     * @param $name
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function __call ( $name, $args ) {
        // TODO: Implement __call() method.
        $service = self::getDi($name);
        if ( $service ) {
            if ( method_exists($service, $name) ) {
                return call_user_func_array([ $service, $name ], $args);
            }
        }
        throw new \Exception('调用了不存在的系统服务：' . $name);
    }

    /**
     * 上下文静态方法调用
     * @param $name
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic ( $name, $args ) {
        $service = self::getDi($name);
        if ( $service ) {
            if ( method_exists($service, $name) ) {
                return call_user_func_array([ $service, $name ], $args);
            }
        }
        throw new \Exception('调用了不存在的系统服务：' . $name);
    }

    /**
     * 获取上下文源引用
     * @param $name
     * @return null
     */
    public static function getDi ( $name = '' ) {
        return self::$di ? self::$di : null;
    }

    /**
     * @title  通用获取配置
     * @param null $cate
     * @param null $name
     * @param null $default
     * @param int  $siteId
     * @return array|string|null
     * @author benjamin
     */
    public static function getConfig ( $cate = null, $name = null, $default = null, $siteId = 1 ) {
        if ( $cate === 'merchant' ) {
            $settings  = (array)request()->get($cate);
        } else if ( $cate === 'system' ) {
            $merchant = (array)request()->get('merchant');
            $merId = !empty($merchant['id']) ? $merchant['id'] : 0 ;

            $settings = CarrierWebSite::getKvList($merId);
        } else {
            $settings = config($cate);
        }

        if ( empty($settings) ) return [];

        # 普通配置获取
        if ( !empty($name) ) {
            return isset($settings[$name]) ? \App\Utils\Filter::trim(($settings[$name])) : $default;
        }

        return $settings;
    }

    /**
     * 是否翻译条件
     * @return bool
     */
    public function isTranslate() 
    {
        if ( !defined('INIT_TIME') || empty(INIT_TIME) ) {
            return false;
        }

        return false;
    }

    public static function checkParamsAndAlert($params) {
        \App\Utils\Filter::param_filter($params);
    }

    public static function checkSqlAndAlert($sql) {
        \App\Utils\Filter::sql_filter($sql);
    }


}
