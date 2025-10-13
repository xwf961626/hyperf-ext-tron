<?php

namespace William\HyperfExtTron;

use William\HyperfExtTron\Command\TronCommand;
use William\HyperfExtTron\Listener\ClearStartupFileListener;
use William\HyperfExtTron\Monitor\DefaultMonitorAdapter;
use William\HyperfExtTron\Monitor\MonitorAdapterInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                MonitorAdapterInterface::class => DefaultMonitorAdapter::class,
            ],
            'commands' => [
                TronCommand::class,
            ],
            'listeners' => [
                ClearStartupFileListener::class,
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
                    'source' => __DIR__ . '/../migrations/',
                    'destination' => BASE_PATH . '/migrations/',
                ],
            ]
        ];
    }
}