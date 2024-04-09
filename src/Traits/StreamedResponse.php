<?php

namespace WeSoonNet\LaravelPlus\Traits;


use Symfony\Component\HttpFoundation\StreamedResponse as SR;

trait StreamedResponse
{
    /**
     * 发送事件流
     *
     * @param callable $callback
     *
     * @return SR
     */
    public static function sendStream(callable $callback): SR
    {
        $response = new SR($callback);
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    /**
     * 输出到缓冲区
     *
     * @param string $event
     * @param mixed  $requestId
     * @param string $content
     *
     * @return void
     */
    public static function writeStream(string $event, mixed $requestId, string $content = " "): void
    {
        echo "event: $event\n";
        echo "data: " . json_encode([
                'id'      => $requestId,
                'content' => $content,
            ]) . "\n\n";

        ob_flush();
        flush();
    }
}
