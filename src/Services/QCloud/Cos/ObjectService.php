<?php

namespace WeSoonNet\LaravelPlus\Services\QCloud\Cos;


use Qcloud\Cos\Client;

class ObjectService
{
    /**
     * 上传文件
     *
     * @param          $secretId
     * @param          $secretKey
     * @param          $bucket
     * @param          $key
     * @param          $body
     * @param  array   $options
     * @param  string  $region
     *
     * @return bool|\Exception
     */
    public static function upload($secretId, $secretKey, $bucket, $key, $body, $options = [], $region = 'ap-chengdu')
    {
        try
        {
            $cosClient = new Client(
                [
                    'region'      => $region,
                    'schema'      => 'https',
                    'credentials' => [
                        'secretId'  => $secretId,
                        'secretKey' => $secretKey,
                    ],
                ]);

            $cosClient->upload(
                $bucket,
                $key,
                $body,
                $options
            );

            return true;
        }
        catch (\Exception $e)
        {
            return $e;
        }
    }

    /**
     * 下载文件
     *
     * @param          $secretId
     * @param          $secretKey
     * @param          $bucket
     * @param          $key
     * @param          $saveAs
     * @param  array   $options
     * @param  string  $region
     *
     * @return bool|\Exception
     */
    public static function download($secretId, $secretKey, $bucket, $key, $saveAs, $options = [], $region = 'ap-chengdu')
    {
        try
        {
            $cosClient = new Client(
                [
                    'region'      => $region,
                    'schema'      => 'https',
                    'credentials' => [
                        'secretId'  => $secretId,
                        'secretKey' => $secretKey,
                    ],
                ]);

            $cosClient->download(
                $bucket,
                $key,
                $saveAs,
                $options
            );

            return true;
        }
        catch (\Exception $e)
        {
            return $e;
        }
    }

    /**
     * 删除文件
     *
     * @param          $secretId
     * @param          $secretKey
     * @param          $bucket
     * @param          $key
     * @param          $saveAs
     * @param  array   $options
     * @param  string  $region
     *
     * @return bool|\Exception
     */
    public static function delete($secretId, $secretKey, $bucket, $key, $region = 'ap-chengdu')
    {
        try
        {
            $cosClient = new Client(
                [
                    'region'      => $region,
                    'schema'      => 'https',
                    'credentials' => [
                        'secretId'  => $secretId,
                        'secretKey' => $secretKey,
                    ],
                ]);

            $cosClient->deleteObject([
                'Bucket'    => $bucket,
                'Key'       => $key,
            ]);

            return true;
        }
        catch (\Exception $e)
        {
            return $e;
        }
    }
}
