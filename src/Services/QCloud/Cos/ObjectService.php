<?php

namespace WeSoonNet\LaravelPlus\Services\QCloud\Cos;


use Exception;
use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\ServiceResponseException;

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
     * @return object
     * @throws Exception
     */
    public static function upload($secretId, $secretKey, $bucket, $key, $body, $options = [], $region = 'ap-chengdu') {
        try {
            $cosClient = new Client(
                [
                    'region'      => $region,
                    'schema'      => 'https',
                    'signHost'    => true,
                    'credentials' => [
                        'secretId'  => $secretId,
                        'secretKey' => $secretKey,
                    ],
                ]);

            return $cosClient->upload($bucket, $key, $body, $options);
        } catch (ServiceResponseException $e) {
            throw  new \Exception($e->getMessage());
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
     * @throws Exception
     */
    public static function download($secretId, $secretKey, $bucket, $key, $saveAs, $options = [], $region = 'ap-chengdu') {
        try {
            $cosClient = new Client(
                [
                    'region'      => $region,
                    'schema'      => 'https',
                    'signHost'    => true,
                    'credentials' => [
                        'secretId'  => $secretId,
                        'secretKey' => $secretKey,
                    ],
                ]);

            return $cosClient->download($bucket, $key, $saveAs, $options);
        } catch (ServiceResponseException $e) {
            throw  new \Exception($e->getMessage());
        }
    }

    /**
     * 删除文件
     *
     * @param          $secretId
     * @param          $secretKey
     * @param          $bucket
     * @param          $key
     * @param  string  $region
     *
     * @throws Exception
     */
    public static function delete($secretId, $secretKey, $bucket, $key, $region = 'ap-chengdu') {
        try {
            $cosClient = new Client(
                [
                    'region'      => $region,
                    'schema'      => 'https',
                    'signHost'    => true,
                    'credentials' => [
                        'secretId'  => $secretId,
                        'secretKey' => $secretKey,
                    ],
                ]);

            return $cosClient->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $key,
            ]);
        } catch (ServiceResponseException $e) {
            throw  new \Exception($e->getMessage());
        }
    }


    /**
     * 移动文件
     *
     * @param          $secretId
     * @param          $secretKey
     * @param          $bucket
     * @param          $targetKey
     * @param          $sourceKey
     * @param  bool    $deleteSource
     * @param  string  $region
     *
     * @return Exception
     * @throws Exception
     */
    public static function copy($secretId, $secretKey, $bucket, $targetKey, $sourceKey, $deleteSource = false, $region = 'ap-chengdu') {
        try {
            $cosClient = new Client(
                [
                    'region'      => $region,
                    'schema'      => 'https',
                    'signHost'    => true,
                    'credentials' => [
                        'secretId'  => $secretId,
                        'secretKey' => $secretKey,
                    ],
                ]
            );

            $result = $cosClient->copy($bucket, $targetKey, [
                'Region' => $region,
                'Bucket' => $bucket,
                'Key'    => $sourceKey,
            ]);

            if ($deleteSource) {
                $cosClient->deleteObject(
                    [
                        'Bucket' => $bucket,
                        'Key'    => $sourceKey,
                    ]
                );
            }

            return $result;
        } catch (ServiceResponseException $e) {
            throw  new \Exception($e->getMessage());
        }
    }
}
