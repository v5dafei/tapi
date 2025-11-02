<?php

//define('ROOT_PATH', dirname(__DIR__));
//define('APP_PATH',ROOT_PATH . '/app');
//define('LIB_PATH',APP_PATH. '/Lib');

ini_set("memory_limit",	"2048M");
if ( !defined('ROOT_PATH') ) {
    define('ROOT_PATH', dirname(__DIR__));
}

if(!defined('APP_PATH')){
   define('APP_PATH',ROOT_PATH . '/app');
}

if(!defined('LIB_PATH')){
    define('LIB_PATH',APP_PATH. '/Lib');
}

if(!defined('LOG_PATH')){
    define('LOG_PATH', ROOT_PATH . '/customise/logs/');
}

if(!defined('DS')){
    define('DS', DIRECTORY_SEPARATOR);
}

/**
 * @title  写入日志
 * @param        $content
 * @param        $type
 * @param string $level
 * @author benjamin
 */
if (!function_exists('writeErrLog')) {
    function writeErrLog ( $content, $type, $level = 'error' ) {
        $logDir = LOG_PATH . 'error' . DS . date('Y-m') . DS . date('m-d') . DS;
        if ( !is_dir($logDir) ) {
            mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . 'Uncaught_' . $type . '.log';
        $content = date('Y-m-d H:i:s') . "\t【{$level}】\t{$content}\r\n";
        $res     = file_put_contents($logFile, $content, FILE_APPEND);
    }
}

/**
 * @title  捕获 fatal error 级别错误
 * @tips   可以捕获，但是不能继续执行、只能在报错后日志记录
 * @author benjamin
 */
if (!function_exists('fatal_handler')) {
    function fatal_handler ( $params = [] ) {
        $appMode = !empty($params['appMode']) ? $params['appMode'] : 'http';

        $E_FATAL = [ E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING ];
        $error   = error_get_last();
        $msg     = 'Fatal error：' . $error["message"] . ' in ' . $error["file"] . ' on ' . $error["line"] . ' ALL_INFO：' . json_encode($error);

        if ( $error && (in_array($error["type"], $E_FATAL)) ) {
            writeErrLog($msg, 'E_FATAL');


            # PHP5下无法捕捉
    //      throw new Exception('E_FATAL：' . $error["message"] . ' in ' . $error["file"] . ' on ' . $error["line"]);
    //      error_handler($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }
}

register_shutdown_function("fatal_handler");

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
