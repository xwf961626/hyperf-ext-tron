<?php

return [
    'monitor' => [
        'start_block' => false,
        'addresses' => [],
    ],
    'endpoint' => [
        'full_node' => env('TRON_FULL_NODE', 'https://api.trongrid.io'),
        'solidity_node' => env('TRON_FULL_NODE_SOLIDITY', 'https://api.trongrid.io'),
    ]
];