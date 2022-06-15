<?php

namespace WeSoonNet\LaravelPlus\Traits;


use Illuminate\Support\Carbon;

trait Timezone
{
    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Shanghai')
            ->toDateTimeString() : $value;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Shanghai')
            ->toDateTimeString() : $value;
    }

    public function getDeletedAtAttribute($value)
    {
        return $value ? Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Shanghai')
            ->toDateTimeString() : $value;
    }
}
