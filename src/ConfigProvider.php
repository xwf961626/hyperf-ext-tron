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
            ],
            'commands' => [
            ],
            'listeners' => [],
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
                    'id' => 'jwt',
                    'description' => 'jwt-auth',
                    'source' => __DIR__ . '/../publish/tron.php',
                    'destination' => BASE_PATH . '/config/autoload/tron.php',
                ],
            ],
            'routes' => function () {
                $routesPath = __DIR__ . '/routes.php';
                if (file_exists($routesPath)) {
                    require $routesPath;
                }
            }
        ];
    }
}