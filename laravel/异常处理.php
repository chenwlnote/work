<?php
/**
 * heybo 异常处理
 * User: Dave
 * Date: 2017/4/18
 * Time: 14:48
 */

namespace App\Services;


use Throwable;

class HeyBoException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if (!is_string($message))
        {
            $message = json_encode($message,JSON_UNESCAPED_UNICODE);
        }
        if (!$message)
        {
            $message = $previous?$previous->getMessage():null;
        }
        parent::__construct($message, $code, $previous);
    }
}