<?php

namespace WeSoonNet\LaravelPlus\Services\Com;


class SignService
{
    /**
     * 创建签名
     *
     * @param  array   $data
     * @param  string  $key
     *
     * @return string
     */
    public static function generate(array $data, string $key)
    {
        isset($data['sing']) && unset($data['sing']);

        ksort($data);

        return md5(sha1(json_encode($data) . $key));
    }

    /**
     * 验证签名
     *
     * @param  array   $data
     * @param  string  $key
     *
     * @return bool
     */
    public static function verify(array $data, string $key)
    {
        return isset($data['sing']) ? ($data['sing'] === self::generate($data, $key)) : false;
    }
}
