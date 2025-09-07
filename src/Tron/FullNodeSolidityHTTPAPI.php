<?php

namespace William\HyperfExtTron\Tron;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Guzzle\CoroutineHandler;
use function Hyperf\Config\config;

class FullNodeSolidityHTTPAPI
{
    use HttpTrait;
    protected Client $client;

    public function __construct(ClientFactory $clientFactory)
    {
        $fullNodeBaseURL = config('tron.endpoint.solidity_node', 'https://api.trongrid.io');
        $options = [
            'handler' => HandlerStack::create(new CoroutineHandler()),
            'timeout' => 5,
            'swoole' => [
                'timeout' => 10,
                'socket_buffer_size' => 1024 * 1024 * 2,
            ],
            'debug' => true,
        ];
        $this->client = $clientFactory->create(array_merge($options, ['base_uri' => $fullNodeBaseURL . '/walletsolidity']));
    }
}