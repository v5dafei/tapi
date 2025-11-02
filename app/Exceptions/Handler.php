<?php

namespace App\Exceptions;

use App\Services\Context;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Illuminate\Database\QueryException;

class Handler extends ExceptionHandler
{

    use Context;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Exception $exception)
    {
        // 只处理自定义的APIException异常
        if($exception instanceof ErrMsg) {
            
        } else {
            parent::report($exception);
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Exception $e)
    {

//        var_dump($e->getMessage(), $this->isHttpException($e), $e instanceof FatalThrowableError);die;

        if ($this->isHttpException($e)) {

            $code = $e->getStatusCode();
            $msg = $e->getMessage();

            if ( $this->isDebugMode() ) {
                $debug = [ 'file' => $e->getFile(), 'line' => $e->getLine(), 'code' => $e->getCode() ];
            } else {
                $errInfo = pathinfo($e->getFile());
                $debug = [ 'file' => $errInfo['filename'], 'line' => $e->getLine() ];
            }

            if ( empty($msg) ) {
                if ( $code === 404 ) $msg = '访问资源不存在';
            }

            ErrInfoHandler2($e, 'Exception', [ 'isInit' => $this->isInit(), 'debug' => $debug ]);

            $this->error($msg, [ 'debug' => $debug ], 1, $code);

//            return response()->json(compact('code', 'message'), $code);
        }

        if ($e instanceof ErrMsg || $e instanceof Exception) {
            if ( $this->isDebugMode() ) {
                $debug = [ 'file' => $e->getFile(), 'line' => $e->getLine(), 'code' => $e->getCode() ];
            } else {
                $errInfo = pathinfo($e->getFile());
                $debug = [ 'file' => $errInfo['filename'], 'line' => $e->getLine() ];
            }
            $msg = $this->isDebugMode() ? $e->getMessage() : 'server FatalThrowableError';

//            ErrInfoHandler2($e, 'Throwable', ['isInit' => $this->isInit(), 'debug' => $debug]);
            $this->error($msg, [ 'debug' => $debug ], 1, 500);

        }

        if ($e instanceof FatalThrowableError || $e instanceof QueryException || $e instanceof ErrorException ) {
//            echo $e;die;
//            var_dump($e); die;
            $code = 500;
            $message = 'server FatalThrowableError';

            $debug = [];
            if ( $this->isDebugMode() ) {
                $debug = [ 'file' => $e->getFile(), 'line' => $e->getLine(), 'code' => $e->getCode() ];
            } else {
                $errInfo = pathinfo($e->getFile());
                $debug = [ 'file' => $errInfo['filename'], 'line' => $e->getLine() ];
            }

            $msg = $this->isDebugMode() ? $e->getMessage() : 'server FatalThrowableError';

            ErrInfoHandler2($e, 'Throwable', ['isInit' => $this->isInit(), 'debug' => $debug]);


            $this->error($msg, [ 'debug' => $debug ], 1, 500);

        }

        return parent::render($request, $e);
    }
}
