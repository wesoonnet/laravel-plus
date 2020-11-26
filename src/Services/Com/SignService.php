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
        if (isset($data['sign']))
        {
            unset($data['sign']);
        }

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
        return isset($data['sign']) ? ($data['sign'] === self::generate($data, $key)) : false;
    }
}
