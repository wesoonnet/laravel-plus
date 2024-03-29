<?php

namespace WeSoonNet\LaravelPlus\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class UrlCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model  $model
     * @param string $key
     * @param string $value
     * @param array  $attributes
     *
     * @return float
     */
    public function get($model, $key, $value, $attributes)
    {
        return (is_string($value) && strlen($value)) ? url($value, [], !config('app.debug', true)) : $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model  $model
     * @param string $key
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        return (is_string($value) && strlen($value)) ? str_replace(url('', [], !config('app.debug', true)), '', $value) : '';
    }
}
