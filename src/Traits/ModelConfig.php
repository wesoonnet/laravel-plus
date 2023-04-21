<?php

namespace WeSoonNet\LaravelPlus\Traits;


use Illuminate\Database\Eloquent\Model;

/**
 * 模型常用配置
 *
 * @package WeSoonNet\LaravelPlus\Traits
 */
trait ModelConfig
{
    public function initializeModelConfig()
    {
        $guarded       = $this->timestamps ? ['created_at', 'updated_at'] : ['deleted_at'];
        $this->guarded = (property_exists($this, 'guarded')) ? array_merge($this->guarded, $guarded) : $guarded;

        $hidden       = ['pivot'];
        $this->hidden = (property_exists($this, 'hidden')) ? array_merge($this->hidden, $hidden) : $hidden;

        $casts       = [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'deleted_at' => 'datetime:Y-m-d H:i:s',
        ];
        $this->casts = (property_exists($this, 'casts')) ? array_merge($this->casts, $casts) : $casts;
    }
}
