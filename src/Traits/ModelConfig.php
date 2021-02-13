<?php

namespace WeSoonNet\LaravelPlus\Traits;


/**
 * 模型常用配置
 *
 * @package WeSoonNet\LaravelPlus\Traits
 */
trait ModelConfig
{
    protected $guarded = ['created_at', 'updated_at'];
    protected $hidden  = ['pivot'];
    protected $casts   = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];
}
