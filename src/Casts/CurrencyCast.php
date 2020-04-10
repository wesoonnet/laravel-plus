<?php

namespace WeSoonNet\LaravelPlus\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class CurrencyCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model    $model
     * @param  string   $key
     * @param  integer  $value
     * @param  array    $attributes
     *
     * @return float
     */
    public function get($model, $key, $value, $attributes)
    {
        return $value / 100;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model    $model
     * @param  string   $key
     * @param  integer  $value
     * @param  array    $attributes
     *
     * @return integer
     */
    public function set($model, $key, $value, $attributes)
    {
        return round($value, 2) * 100;
    }
}
