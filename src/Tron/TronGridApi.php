<?php

namespace William\HyperfExtTron\Tron;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Guzzle\CoroutineHandler;
use function Hyperf\Config\config;
use function Hyperf\Support\env;

class TronGridApi
{
    use HttpTrait;

    protected Client $client;

    public function __construct(ClientFactory $clientFactory)
    {
        $fullNodeBaseURL = 'https://api.trongrid.io';
        $options = [
            'handler' => HandlerStack::create(new CoroutineHandler()),
            'timeout' => 5,
            'swoole' => [
                'timeout' => 10,
                'socket_buffer_size' => 1024 * 1024 * 2,
            ],
            'debug' => true,
        ];
        $this->client = $clientFactory->create(array_merge($options, ['base_uri' => $fullNodeBaseURL . '/wallet/']));
    }


}