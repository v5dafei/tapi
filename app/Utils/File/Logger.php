<?php

namespace App\Utils\File;

use App\Exceptions\ErrMsg;
use App\Services\Context;
use App\Utils\Validator;

/**
 * 文件日志
 */
class Logger
{

    use Context;

    static $logInstance = [];

    const LEVEL_DEBUG = 'debug'; # 调试
    const LEVEL_INFO  = 'info';  # 普通
    const LEVEL_WARN  = 'warn';  # 警告
    const LEVEL_ERR   = 'error'; # 错误

    static $LOG_LEVEL = [
        self::LEVEL_DEBUG,
        self::LEVEL_INFO,
        self::LEVEL_WARN,
        self::LEVEL_ERR,
    ];

    private $logFile    = null;
    private $fileHandle = null;

    static $LOG_CONTENT = '';

    /**
     * Logger constructor.
     * @param $fileName
     */
    protected function __construct ( $fileName ) {
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(dirname(__DIR__)));
        $this->logFile = $rootPath . '/customise/logs' . $fileName . date('_Y_m_d') . '.log';
    }

    /**
     * 日志调度
     * @param        $content
     * @param string $fileName
     * @param string $level
     * @param bool   $isAlert
     * @return mixed
     * @throws \App\Exceptions\ErrMsg
     */
    static public function write ( $content, $fileName = 'default', $level = self::LEVEL_INFO, $isAlert = true ) {
        # 重新写入时重置
        self::$LOG_CONTENT = '';

        $data = [
            'data'     => $content,
            'fileName' => $fileName,
            'level'    => $level
        ];

        if ( empty($content) ) {
            throw new ErrMsg('日志内容不能为空！');
        }
        if ( !Validator::validate($data, [
//            'data'     => [ 'check' => 'required', 'msgPrefix' => '日志内容' ],
            'fileName' => [ 'check' => 'isIdStr', 'connector' => "\/_-", 'msgPrefix' => '日志文件名' ],
            'level'    => [ 'check' => 'isAlnum|inArray', 'in' => self::$LOG_LEVEL, 'msgPrefix' => '日志等级' ]
        ]) ) {
            throw new ErrMsg(Validator::getErrMsg());
        }

        if ( !isset(Logger::$logInstance[$fileName]) ) {
            Logger::$logInstance[$fileName] = new self($fileName);
        }

        return Logger::$logInstance[$fileName]->log($content, $level, $fileName, $isAlert);
    }


    /**
     * 日志生成
     * @param $content
     * @param $level
     * @return bool
     * @throws ErrMsg
     * @author benjamin
     */
    protected function log ( $content, $level, $fileName, $isAlert ) {
//        $isAlert = false; // todo todo

        if ( is_array($content) ) {
            $content = var_export($content, true);
        }

        #if ( empty($website) ) $isAlert = false;

        # 基于命令行的文件日志
        if ( defined('CLI_MODE') && CLI_MODE ) {
            $website = $GLOBALS['APP_CONFIG']['mysql']['usr'];
            
            # 错误概览 --- BEGIN - INFO - [Date：2019-08-04 14:45:17, IP：10.88.130.52, PATH：Test/Utils/index, USR：test001] ---
            $info    = "--- BEGIN - {$level} - {$website} - [";
            $info    .= 'Date：' . date('Y-m-d H:i:s');
            $info    .= ', IP：' . getCliIP();
            $info    .= ', PID：' . posix_getppid() . '-' . getmypid();
            $info    .= "] --- \r\n";
            $content = "{$info} {$content}\r\n"; // \t 制表符

            $this->teleAppAlert($content, $level, $fileName, $isAlert);

            fwrite($this->getFileHandle(), $content);
            return true;
        }

        # 基于命令行的文件日志
        if ( defined('LARAVEL_START') && LARAVEL_START ) {
            $website =  self::getWebSite();
            # 错误概览 --- BEGIN - INFO - [Date：2019-08-04 14:45:17, IP：10.88.130.52, PATH：Test/Utils/index, USR：test001] ---
            $info    = "--- BEGIN - {$level} - {$website} - [";
            $info    .= 'Date：' . date('Y-m-d H:i:s');
            $info    .= ', IP：' . self::ip();

            $info    .= ', PID：' . posix_getppid() . '-' . getmypid();
            $info    .= "] --- \r\n";
            $content = "{$info} {$content}\r\n"; // \t 制表符

            $this->teleAppAlert($content, $level, $fileName, $isAlert);

            fwrite($this->getFileHandle(), $content);
            return true;
        }


        $website =  self::getWebSite();


        # 基于PC|H5|ADMIN的文件日志
        if ( (defined('IN_FHPT_WEB') && IN_FHPT_WEB) || (defined('IN_FHPT_ADMIN') && IN_FHPT_ADMIN) ) {
            $request = (new \Core\Http\Request([
                'pathInfoType' => 'pathInfo'
            ]));

            $httpMethod = $request->getHttpMethod();
            $params     = $request->getParams();
            $reqPath    = $request->getPathInfo();
            $userAgent  = $request->getUserAgent();
            $ip         = $request->getClientRealIp();
            $user       = [];
            $level      = strtoupper($level);

            # 错误概览 --- BEGIN - INFO - [Date：2019-08-04 14:45:17, IP：10.88.130.52, PATH：Test/Utils/index, USR：test001] ---
            $info = "--- BEGIN - {$level} - {$website} - [";
            $info .= 'Date：' . date('Y-m-d H:i:s');
            $info .= ', IP：' . $ip;
            $info .= ', PATH：' . $reqPath;
            $info .= ', USR：' . (!empty($user) ? $user['usr'] : '');
            $info .= "] --- \r\n";

            # 请求参数 --- GET - PARAMS - [{"c":"real","a":"gameUrl","id":"67","game":"","token":"5KCesuwisd3dgZF5SGFfK3Ig"}] ---
            $info .= "--- {$httpMethod} - PARAMS - [";
            $info .= json_encode($params, JSON_UNESCAPED_UNICODE);
            $info .= "] --- \r\n";

            # UserAgent --- User - Agent - [Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36] ---
            $info .= "--- User - Agent - [";
            $info .= $userAgent;
            $info .= "] --- \r\n";

            $content = "{$info} {$content}\r\n"; // \t 制表符

            $this->teleAppAlert($content, $level, $fileName, $isAlert);

            fwrite($this->getFileHandle(), $content);
        }

        # 基于APP的文件日志
        if ( (defined('IN_FHPT_APP') && IN_FHPT_APP) ) {

            $accessRule = $this->permission->getAccessRule();
            $params     = $this->permission->getSafeParams();
            $host       = $this->request->getHttpHost();
            $user       = $this->session->authUser;
            $level      = strtoupper($level);

            # 错误概览 --- BEGIN - INFO - [Date：2019-08-04 14:45:17, IP：10.88.130.52, PATH：Test/Utils/index, USR：test001] ---
            $info = "--- BEGIN - {$level} - {$website} - [";
            $info .= 'Date：' . date('Y-m-d H:i:s');
            $info .= ', IP：' . $this->IP();
            $info .= ', PATH：' . $this->router->getReqPath();
            $info .= ', USR：' . (!empty($user) ? $user['usr'] : '');
            $info .= ', HOST：' .$host;
            $info .= "] --- \r\n";

            # 请求参数 --- GET - PARAMS - [{"c":"real","a":"gameUrl","id":"67","game":"","token":"5KCesuwisd3dgZF5SGFfK3Ig"}] ---
            $info .= "--- {$accessRule['http_method']} - PARAMS - [";
            $info .= json_encode($params, JSON_UNESCAPED_UNICODE);
            $info .= "] --- \r\n";

            # UserAgent --- User - Agent - [Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36] ---
            $info .= "--- User - Agent - [";
            $info .= $this->request->getUserAgent();
            $info .= "] --- \r\n";

            $content = "{$info} {$content}\r\n"; // \t 制表符

            $this->teleAppAlert($content, $level, $fileName, $isAlert);

            fwrite($this->getFileHandle(), $content);
        }

        return true;
    }

    /**
     * 获取文件流
     * @return bool|resource
     * @throws ErrMsg
     * @author benjamin
     */
    protected function getFileHandle () {
        if ( null === $this->fileHandle ) {

            if ( empty($this->logFile) ) {
                throw new ErrMsg("请指定日志存放文件!");
            }
            $logDir = dirname($this->logFile);

            if ( !is_dir($logDir) ) {
                mkdir($logDir, 0777, true);
            }

//            if (!file_exists($this->logFile)) {
//                if (!is_writable($this->logFile)) chmod($this->logFile, 0775);
//            }

            $this->fileHandle = fopen($this->logFile, "a+");
        }
        return $this->fileHandle;
    }


    /**
     * Telegram 自动报警
     * @param $content
     * @param $level
     * @param $path
     * @return mixed
     */
    private function teleAppAlert ( $content, $level, $path = '', $isAlert = true ) {

        # 配置了不报警
        if ( !$isAlert ) return true;

        # 开发环境不报警
        if ( defined('DEV_MODE') && DEV_MODE !== 3 ) return true;

        # 是否在上报白名单
        $whitePath = [
            'errNode',              // Redis连接异常
            'ErrorGrabRedBagBoot',  // 机器人抢包失败
            'RedBagTimerCmd',       // 定时红包异常
        ];
        foreach ( $whitePath as $white ) {
            if ( strpos($path, $white) !== false ) {
                return true;
            }
        }

        # 全部项目错误上报
        if ( strtolower($level) === self::LEVEL_ERR ) {
            $website = self::getWebSite();

            # 不监听CLI项目错误
//            if ( self::isCliMode() ) return true;

            # 不监听测试环境项目
//            if ( strpos($website, 't') === 0 ) return true;
//            if ( strpos($website, 'aabb') === 0 ) return true;
//
//            # 不监听CDN环境项目
//            if ( strpos($website, 'y') === 0 ) return true;

            $this->sendTeleAppAlert($content);
        }

    }


    public function sendTeleAppAlert ( $text, $type = 'app' ) {
        $chatId = $botKey = '';

        switch ( $type ) {
            case 'app':
            case 'chat':
            default:
                # 频道：@V5-APP-告警
                $chatId = \Yaconf::get(YACONF_PRO_ENV.'.channel_alarm_id', -895372826);

                # 机器人：@v5_alert_bot
                $botKey = \Yaconf::get(YACONF_PRO_ENV.'.web_send_boot_token', '5875571599:AAFnTrJQojsmz8RpX5gOpghN3MoV3078H44');
                break;
        }

        if ( empty($chatId) || empty($botKey) ) return false;

        try {
            $post_url = "https://api.telegram.org/bot{$botKey}/sendMessage";
            $this->curlMethod($post_url, [
                'chat_id' => $chatId, // curl post 带@会报错
                'text'    => $text
            ], 'POST', 3);
        } catch (\Exception $e) {
            # 异常上报失败不能影响流程 直接返回
            return false;
        }
    }

    /**
     * 远程请求封装
     * @param        $url
     * @param array  $params
     * @param string $method
     * @param null   $header
     * @return bool|string
     */
    private function curlMethod ( $url, $params = [], $method = 'POST', $timeOut = 3, $header = null ) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ( $header ) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
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
        $tmpInfo = curl_exec($ch);
        if ( curl_errno($ch) ) {
            return curl_error($ch);
        }
        return $tmpInfo;
    }
}