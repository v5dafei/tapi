<?php

/**
 * 展示给用户的异常：能被程序正常捕捉、控制
 */

namespace App\Exceptions;

use \Exception;
use App\Services\Context;

final class ErrMsg extends Exception
{

    use Context;

    /**
     * 状态码
     * @var int|mixed
     */
    public $code = 0;

    /**
     * 错误具体信息
     * @var mixed|string
     */
    public $message = '参数错误';


    public function __construct($message, $code = 1, $previous = null) {

        $this->code = $code;
        $this->message = $message;

        return $this->error($this->message);
    }

    public function report(Exception $exception)
    {
        //\Log::info('异常信息是'.$this->message);
        // 只处理自定义的APIException异常
        //parent::report($exception);
    }

}