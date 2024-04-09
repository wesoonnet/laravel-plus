<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use Illuminate\Http\JsonResponse;
use WeSoonNet\LaravelPlus\Controllers\RootController;

class Controller extends RootController
{
    /**
     * 返回成功消息
     *
     * @param string $message
     * @param string $code
     *
     * @return JsonResponse
     */
    public function success($message = 'ok', $code = 10000): JsonResponse
    {
        return parent::success($message, $code);
    }

    /**
     * 返回失败消息
     *
     * @param string $message
     * @param string $code
     * @param int    $status
     *
     * @return JsonResponse
     */
    public function failure($message = '', $code = 20000, $status = 200): JsonResponse
    {
        if (is_object($message) && get_class($message) === BusinessException::class)
        {
            $message = $message->getMessage();
        }
        else if (is_object($message) && method_exists($message, 'getMessage'))
        {
            report($message);

            $message = config('app.debug') ? $message->getMessage() : 'server.inner_error';
        }

        $_code = __("{$message}_code") != "{$message}_code" ? __("{$message}_code") : $code;

        return parent::failure(__($message), (int)$_code, $status);
    }
}
