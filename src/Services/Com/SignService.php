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
    public static function generate(array $data, string $key): string
    {
        if (isset($data['sign']))
        {
            unset($data['sign']);
        }

        ksort($data);

        foreach ($data as $k => $v)
        {
            $data[$k] = (string) $v;
        }

        return md5(sha1(json_encode($data, JSON_UNESCAPED_SLASHES) . $key));
    }

    /**
     * 验证签名
     *
     * @param  array   $data
     * @param  string  $key
     *
     * @return bool
     */
    public static function verify(array $data, string $key): bool
    {
        return isset($data['sign']) && ($data['sign'] === self::generate($data, $key));
    }
}
