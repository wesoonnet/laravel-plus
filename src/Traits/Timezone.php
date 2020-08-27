<?php

namespace WeSoonNet\LaravelPlus\Traits;


use Illuminate\Support\Carbon;

trait Timezone
{
    public function getCreatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Shanghai')
            ->toDateTimeString();
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Shanghai')
            ->toDateTimeString();
    }

    public function getDeletedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Shanghai')
            ->toDateTimeString();
    }
}
