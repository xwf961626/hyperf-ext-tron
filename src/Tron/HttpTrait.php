<?php

namespace William\HyperfExtTron\Tron;

use GuzzleHttp\Exception\GuzzleException;
use William\HyperfExtTron\Helper\Logger;
use function Hyperf\Config\config;

trait HttpTrait
{
    protected function getXHeaders($keys = [])
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
//        Logger::debug("是否使用api-key：".config('tron.endpoint.no_api_key'));
        if(!config('tron.endpoint.no_api_key')){
            static $lastIndex = -1; // 静态变量，跨调用保存上次位置

            $count = count($keys);

            if ($count === 0) {
                throw new \RuntimeException('No TRON_PRO_API_KEY configured.');
            }

            // 顺序取 key
            $lastIndex = ($lastIndex + 1) % $count;
            $currentKey = trim($keys[$lastIndex]);
            $headers = array_merge($headers, [
                'TRON-PRO-API-KEY' => $currentKey,
            ]);
        }
        return $headers;
    }
    /**
     * @throws GuzzleException
     */
    public function post($uri, $data, $keys = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->post($uri, [
            'json' => $data,
            'headers' => $this->getXHeaders($keys)
        ]);
    }

    public function get($uri, $query=[], $keys = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->get($uri, [
            'query' => $query,
            'headers' => $this->getXHeaders($keys)
        ]);
    }
}