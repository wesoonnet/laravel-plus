<?php

namespace WeSoonNet\LaravelPlus\Services\Com;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class LogService
{
    public static function error($e)
    {
        if ($e instanceof \Exception)
        {
            $e = $e->getMessage().' in '.$e->getFile().':'.$e->getLine();
        }

        Log::error($e);
    }


    /**
     * 写日志
     *
     * @param  string       $action  动作（C创建、U更新、D删除、O其他、S系统）
     * @param  bool         $status  状态
     * @param  string|null  $desc    描述
     * @param  string|null  $remark  备注
     */
    public static function write(string $action, bool $status = true, string $desc = null, string $remark = null)
    {
        \App\Models\Sys\Log::create([
            'user_id' => ($user = Auth::user()) ? $user->id : null,
            'path'    => app()->runningInConsole() ? 'console' : Request::route()->uri(),
            'action'  => $action,
            'desc'    => $desc,
            'ip'      => Request::ip(),
            'client'  => Request::server('HTTP_USER_AGENT'),
            'status'  => $status,
            'remark'  => $remark,
        ]);
    }
}
