<?php
/**
 * 工具助手类
 */

namespace App\Utils;

use Core\Plugin;
use App\Utils\Enum\SignTypeEnum;
use App\Utils\Hash\HashHelper;

class Helper
{
    private static $instance = [];

    /**
     * @title  单例通用插件
     * @return mixed
     * @author benjamin
     */
    public static function getInstance () {
        $class = static::class;
        $k     = md5($class);
        if ( empty(self::$instance[$k]) ) {
            self::$instance[$k] = new $class();
        }
        return self::$instance[$k];
    }

    /**
     * @title null合并运算符（php7 -> ??）
     * @return mixed
     * @link  php5替代版
     * @demo <p>
     *        Helper::valueIsset($_GET, 'a', 'dudu');
     *        </p>
     */
    public static function valueIsset ( $arr, $k, $def = '' ) {
        return valueIsset($arr, $k, $def);
    }

    /**
     * @title  调试打印
     * @param $data
     * @author benjamin
     */
    public static function echo2 ( $data ) {
        echo '<pre>';
        var_dump($data);
        echo '<pre>';
    }

    /**
     * @title  请求执行时间
     * @param null $startMt
     * @param null $endMt
     * @return float
     * @author benjamin
     */
    public static function runtime ( $startMt = null, $endMt = null ) {
        $startMt = !empty($startMt) ? $startMt : LARAVEL_START;
        $endMt   = !empty($endMt) ? $endMt : microtime(true);
        $diff    = ($endMt - $startMt) * 1000; // ms
        return number_format($diff, 2, '.', '');
    }

    /**
     * @title  图片上传资源服务器
     */
    public static function uploadToServer ( $domain, $filePath ) {
        # 获取文件信息
        $pathInfo = pathinfo($filePath);
        $fileName = $pathInfo['basename'];

        # 文件处理以及加密
        $data           = bin2hex(gzcompress(file_get_contents(ROOT_PATH . $filePath)));
        $params         = [
            'siteCode' => Plugin::getConfig('merchant'),
            'fileName' => $filePath,
            'data'     => $data,
            'action'   => 'upload',
        ];
        $signAry        = [
            'uid'          => $params['siteCode'],
            'username'     => $params['fileName'],
            'name'         => strlen($params['data']),
            'password'     => $params['action'],
            'coinPassword' => '123',
            'is_tester'    => '123',
        ];
        $params['sign'] = HashHelper::dataSign(SignTypeEnum::UPLOAD_IMAGE, $signAry);

        # 发起请求
        $response = self::curlPost($domain, $params);
        $res      = json_decode($response, true);

        if ( is_array($res) && isset($res['error']) && $res['error'] == '0' ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @title 第三方请求
     * @param $data
     * @param $url
     * @return bool|string
     */
    public static function curlPost ( $url, $data ) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * 远程请求封装
     *
     * @param        $url
     * @param array  $params
     * @param string $method
     * @param null   $header
     * @return bool|string
     */
    public static function curlMethod ( $url, $params = [], $method = 'POST', $timeOut = 3, $header = null ) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ( $header ) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $method = strtoupper($method);
        switch ( $method ) {
            case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        # 外部错误判断条件：!empty($res['error'])
        if ( $errno ) {
            if ( in_array($errno, [ 6, 7, 35, 55 ]) || ($errno == 28 && preg_match("/^connect\(\) timed out|Connection timed out after/i", $error)) || in_array($httpCode, [ 403, 404 ]) ) {
                $res['curl_error'] = -1;        //-1为确定没有到达第三方游戏的服务器
            } else {
                $res['curl_error'] = 1;
            }

            $res['error_msg'] = '无法连接服务器，curl_errno：' . $errno . '，curl_error：' . $error;

        } else {
//            $res = json_decode($response, true);
            $res = $response;
        }

        return $res;
    }

    /**
     * @title  参数过滤
     * @author benjamin
     */
    public static function paramsFilter ( $str ) {
//        return true;
//        return param_filter($params);

        $filter = array(
            'select([\s\S]+)concat',
            'include([\s\S]+)php',
            'select([\s\S]+)from',
            'select([\s\S]+)sleep',
            'union([\s\S]+)select',
            'insert([\s\S]+)into',
            'update([\s\S]+)set',
            'delete([\s\S]+)from',
            'ssc_([\s\S]+)',
            'create([\s\S]+)table',
            'drop([\s\S]+)table',
            'truncate',
            'show([\s\S]+)table',
            'union([\s\S]+)select',
            'replace([\s\S]+)into',
            'information_schema([\s\S]+)',
            'bin([\s\S]+)bash',
            'union([\s\S]+)select',
            'load_file([\s\S]+)',
            'outfile([\s\S]+)',
            'infile([\s\S]+)',
            'replace([\s\S]+)into',
            'unhex([\s\S]+)',
            'name_const([\s\S]+)',
            'fwrite([\s\S]+)',
            'fputs([\s\S]+)',
            'eval([\s\S]+)',
        );
        #意义不明，遂注释，徒增消耗
//    $filter_lower = array();
//    foreach ($filter as $f){
//        $filter_lower[] = strtolower($f);
//    }

        $isAlert = false;
        if (is_array($str)){
            foreach ($str as $key=>$val){
                $newkey = param_filter($key);
                $val = param_filter($val);
                unset($str[$key]);
                $str[$newkey] = $val;
            }
        }else{
            if (preg_match("/".implode('|', $filter)."/i", urldecode($str))){
                $str = preg_replace("/".implode('|', $filter)."/i", "***", urldecode($str));
            }else{
                $str = preg_replace("/".implode('|', $filter)."/i", "***", $str);
            }
            if ((!defined('CLI_MODE') || !CLI_MODE) && preg_match("/mobile\/userrech\/onlinePay.do\/notify/i", $_SERVER['REQUEST_URI'])){
                $str = addslashes($str);
            }else{
                $str = addslashes(htmlspecialchars($str));
            }
        }
        return $str;
    }

    public static function strFilter ( $str ) {

//        return true;

        if ( empty($GLOBALS['currency']) || !in_array($GLOBALS['currency'], [ 'VND' ]) ) {
            $str = str_replace(' ', '', $str);
        }

        $str = str_replace('`', '', $str);
        $str = str_replace('·', '', $str);
        $str = str_replace('~', '', $str);
        $str = str_replace('!', '', $str);
        $str = str_replace('！', '', $str);
        $str = str_replace('@', '', $str);
        $str = str_replace('#', '', $str);
        $str = str_replace('$', '', $str);
        $str = str_replace('￥', '', $str);
        $str = str_replace('%', '', $str);
        $str = str_replace(`^_^ _^`, '', $str);
        $str = str_replace('……', '', $str);
        $str = str_replace('&', '', $str);
        $str = str_replace('*', '', $str);
        $str = str_replace('(', '', $str);
        $str = str_replace(')', '', $str);
        $str = str_replace('（', '', $str);
        $str = str_replace('）', '', $str);
        $str = str_replace('-', '', $str);
        $str = str_replace('_', '', $str);
        $str = str_replace('——', '', $str);
        $str = str_replace('+', '', $str);
        $str = str_replace('=', '', $str);
        $str = str_replace('|', '', $str);
        $str = str_replace('\\', '', $str);
        $str = str_replace('[', '', $str);
        $str = str_replace(']', '', $str);
        $str = str_replace('【', '', $str);
        $str = str_replace('】', '', $str);
        $str = str_replace('{', '', $str);
        $str = str_replace('}', '', $str);
        $str = str_replace(';', '', $str);
        $str = str_replace('；', '', $str);
        $str = str_replace(':', '', $str);
        $str = str_replace('：', '', $str);
        $str = str_replace('\'', '', $str);
        $str = str_replace('"', '', $str);
        $str = str_replace('“', '', $str);
        $str = str_replace('”', '', $str);
        $str = str_replace(',', '', $str);
        $str = str_replace('，', '', $str);
        $str = str_replace('<', '', $str);
        $str = str_replace('>', '', $str);
        $str = str_replace('《', '', $str);
        $str = str_replace('》', '', $str);
        $str = str_replace('.', '', $str);
        $str = str_replace('。', '', $str);
        $str = str_replace('/', '', $str);
        $str = str_replace('、', '', $str);
        $str = str_replace('?', '', $str);
        $str = str_replace('？', '', $str);
        return trim($str);
    }

    /**
     * 根据货币格式化金额显示
     *
     * @param $money
     * @return string|string[]
     */
    public static function currencyMoney ( $money, $isChange = 1 ) {
        $currency = \Boot::$di['settings']['currency'];

        $shortenBigAmount = function ( $amount, $precision = 3 ) {
            $amount = str_replace(',', '', $amount);
            if ( $amount >= 1e+3 ) {
                $newAmount = number_format($amount / 1e+3, $precision);
                $newAmount = floatval(str_replace(',', '', $newAmount)) . 'k';
                return $newAmount;
            } elseif ( $amount < 0 && abs($amount) >= 1e+3 ) {
                $amount    = abs($amount);
                $newAmount = number_format($amount / 1e+3, $precision);
                $newAmount = floatval(str_replace(',', '', $newAmount)) . 'k';
                return '-' . $newAmount;
            } else {
                return $amount;
            }
        };

        if ( $currency != 'CNY' ) {
            return $isChange ? $shortenBigAmount($money) : $money . "k";
        }

        return $money;
    }
}