<?php

use William\HyperfExtTron\Apis\CatFee;
use William\HyperfExtTron\Apis\Trongas;
use William\HyperfExtTron\Apis\Trxx;
use William\HyperfExtTron\Apis\Weidubot;
use William\HyperfExtTron\Limit\DefaultHandler;
use William\HyperfExtTron\Limit\DefaultRule;
use William\HyperfExtTron\Limit\LimitCheck;
use William\HyperfExtTron\Model\LimitResourceAddress;
use William\HyperfExtTron\Model\UserResourceAddress;
use function Hyperf\Support\env;

return [
    'private_key' => env('TRON_PRIVATE_KEY', ''),
    'monitor' => [
        'start_block_mode' => 'latest', // latest-接口获取最新块，cache-缓存中获取最后一个块
        'addresses' => [],
    ],
    'endpoint' => [
        'no_api_key' => env('TRON_NO_API_KEY', true),
        'api_keys' => [],
        'full_node' => env('TRON_FULL_NODE', 'https://api.trongrid.io'),
        'solidity_node' => env('TRON_FULL_NODE_SOLIDITY', 'https://api.trongrid.io'),
    ],
    /**
     * 需要检测是否达到阈值的模型
     */
    'address_limit' => [
        'enable' => true,
        'check' => [
            (new LimitCheck(LimitResourceAddress::class))->setCallback(new DefaultHandler())->setInterval(30)->setRule(new DefaultRule()),
        ]
    ],
    // 自有能量池
    'pool' => [
        'update_interval' => env('POOL_UPDATE_INTERVAL', 1),
    ],
    "apis" => [
        // 维度接口
        Weidubot::API_NAME => [
            'class' => Weidubot::class,
            'base_url' => env('WEIDU_API', 'https://weidubot.cc'),
            'api_key' => env('WEIDU_API_KEY', ''),
            'api_secret' => env('WEIDU_API_SECRET', ''),
        ],
        CatFee::API_NAME => [
            'class' => CatFee::class,
            'baseUrl' => env('API_CAT_FEE', 'https://api.catfee.io'),
            'apiKey' => env('CAT_FEE_API_KEY', ''),
            'apiSecret' => env('CAT_FEE_API_SECRET', ''),
        ],
        Trongas::API_NAME => [
            'class' => Trongas::class,
            'baseUrl' => env('TRONGAS_API', 'https://trongas.io'),
            'apiKey' => env('TRONGAS_API_KEY', ''),
            'username' => env('TRONGAS_USER', ''),
            'password' => env('TRONGAS_PASSWORD', ''),
        ],
        Trxx::API_NAME => [
            'class' => Trxx::class,
            'baseUrl' => env('TRXX_API', 'https://trxx.io'),
            'apiKey' => env('TRXX_API_KEY', ''),
            'apiSecret' => env('TRXX_API_SECRET', ''),
            'callback_url' => env('TRXX_CALLBACK_URL', ''),
        ],
    ],
];