<?php

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
            'send_api' => env('WEIDU_SEND_API', ''),
            'api_key' => env('WEIDU_API_KEY', ''),
            'api_secret' => env('WEIDU_API_SECRET', ''),
            'get_result_api' => env('WEIDU_GET_RESULT_API', ''),
        ]
    ],
];