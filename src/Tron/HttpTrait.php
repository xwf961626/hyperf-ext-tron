<?php

namespace William\HyperfExtTron\Tron;

use GuzzleHttp\Exception\GuzzleException;
use function Hyperf\Support\env;

trait HttpTrait
{
    /**
     * @throws GuzzleException
     */
    public function post($uri, $data, $keys = []): \Psr\Http\Message\ResponseInterface
    {
        static $lastIndex = -1; // 静态变量，跨调用保存上次位置

        $count = count($keys);

        if ($count === 0) {
            throw new \RuntimeException('No TRON_PRO_API_KEY configured.');
        }

        // 顺序取 key
        $lastIndex = ($lastIndex + 1) % $count;
        $currentKey = trim($keys[$lastIndex]);

        return $this->client->post($uri, [
            'json' => $data,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'TRON-PRO-API-KEY' => $currentKey,
            ]
        ]);
    }

    public function get($uri, $query=[], $keys = []): \Psr\Http\Message\ResponseInterface
    {
        static $lastIndex = -1; // 静态变量，跨调用保存上次位置

        $count = count($keys);

        if ($count === 0) {
            throw new \RuntimeException('No TRON_PRO_API_KEY configured.');
        }

        // 顺序取 key
        $lastIndex = ($lastIndex + 1) % $count;
        $currentKey = trim($keys[$lastIndex]);

        return $this->client->get($uri, [
            'query' => $query,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'TRON-PRO-API-KEY' => $currentKey,
            ]
        ]);
    }
}