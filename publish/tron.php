<?php

use William\HyperfExtTron\Apis\CatFee;
use William\HyperfExtTron\Apis\Trongas;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;

return [
    'private_key' => env('TRON_PRIVATE_KEY', ''),
    'monitor' => [
        'start_block' => 75528207,
        'addresses' => [],
    ],
    'endpoint' => [
        'full_node' => env('TRON_FULL_NODE', 'https://api.trongrid.io'),
        'solidity_node' => env('TRON_FULL_NODE_SOLIDITY', 'https://api.trongrid.io'),
    ],
    "apis" => [
        // 自有能量池
        EnergyApi::API_POOL => [

        ],
        // 维度接口
        EnergyApi::API_WEIDU => [
            'send_api' => env('WEIDU_SEND_API', 'https://weidubot.cc/api/v2/'),
            'api_key' => env('WEIDU_API_KEY', ''),
            'api_secret' => env('WEIDU_API_SECRET', ''),
            'get_result_api' => env('WEIDU_GET_RESULT_API', ''),
        ],
        CatFee::API_NAME => [
            'baseUrl' => env('API_CAT_FEE', 'https://api.catfee.io'),
            'apiKey' => env('CAT_FEE_API_KEY', ''),
            'apiSecret' => env('CAT_FEE_API_SECRET', ''),
        ],
        Trongas::API_NAME => [
            'baseUrl' => env('TRONGAS_API', 'https://trongas.io'),
            'apiKey' => env('TRONGAS_API_KEY', ''),
        ],
    ],
];