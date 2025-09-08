<?php

namespace William\HyperfExtTron;

use William\HyperfExtTron\Monitor\MonitorAdapterInterface;
use William\HyperfExtTron\Service\MonitorService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                MonitorAdapterInterface::class => MonitorService::class,
                \Psr\SimpleCache\CacheInterface::class => \Hyperf\Cache\Cache::class,
            ],
            'commands' => [
            ],
            'listeners' => [
                RegisterRoutesListener::class,
            ],
            // 合并到  config/autoload/annotations.php 文件
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'tron',
                    'description' => 'tron',
                    'source' => __DIR__ . '/../publish/tron.php',
                    'destination' => BASE_PATH . '/config/autoload/tron.php',
                ],
                [
                    'id' => 'migrations',
                    'description' => 'tron migrations',
                    'source' => __DIR__ . '/Database/Migrations/',
                    'destination' => BASE_PATH . '/migrations/',
                ],
            ]
        ];
    }
}