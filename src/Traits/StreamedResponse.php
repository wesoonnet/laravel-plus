<?php

namespace WeSoonNet\LaravelPlus\Traits;


use Symfony\Component\HttpFoundation\StreamedResponse as SR;

trait StreamedResponse
{
    /**
     * 发送事件流
     *
     * @param callable $callback
     * @param string   $format
     *
     * @return SR
     */
    public static function sendStream(callable $callback, string $format = 'event-stream'): SR
    {
        $response = new SR($callback);

        if ('chunked' == $format)
        {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Transfer-Encoding', 'chunked');
            $response->headers->set('Cache-Control', 'no-cache');
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-$response->headers->sets', 'Content-Type');
            $response->headers->set('Connection', 'keep-alive');
            $response->headers->set('X-Accel-Buffering', 'no');
        }
        else
        {
            $response->headers->set('Content-Type', 'text/event-stream');
            $response->headers->set('X-Accel-Buffering', 'no');
            $response->headers->set('Cache-Control', 'no-cache');
        }

        return $response;
    }

    /**
     * 输出到缓冲区
     *
     * @param string           $event
     * @param mixed            $requestId
     * @param string|int|array $content
     * @param string           $format
     *
     * @return void
     */
    public static function writeStream(string $event, mixed $requestId, string|int|array $content = " ", string $format = 'event-stream'): void
    {
        if ('chunked' == $format)
        {
            $msg = json_encode([
                'id'      => $requestId,
                'event'   => $event,
                'content' => $content,
            ]);

            echo $msg . "\r\n";

            if (ob_get_level())
            {
                ob_flush();
            }
            flush();

            if ('DONE' == strtoupper($event) || 'ERROR' == strtoupper($event))
            {
                echo "\r\n\r\n";

                if (ob_get_level())
                {
                    ob_flush();
                }
                flush();
            }
        }
        else
        {
            echo "event: $event\n";
            echo "data: " . json_encode([
                    'id'      => $requestId,
                    'content' => $content,
                ]) . "\n\n";

            if (ob_get_level())
            {
                ob_flush();
            }
            flush();
        }
    }
}
