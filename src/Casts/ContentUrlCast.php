<?php

namespace WeSoonNet\LaravelPlus\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ContentUrlCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return str_replace('uploadfiles', url('uploadfiles'), $value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return str_replace(url('').'/', '', $value);
    }
}
