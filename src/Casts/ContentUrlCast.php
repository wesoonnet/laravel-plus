<?php

namespace WeSoonNet\LaravelPlus\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use WeSoonNet\LaravelPlus\Services\Com\UtilService;

class ContentUrlCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return UtilService::htmlRelToAbsPath($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return UtilService::htmlAbsToRelPath($value);
    }
}
