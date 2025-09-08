<?php

namespace William\HyperfExtTron;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;
use William\HyperfExtTron\Helper\Logger;

class RegisterRoutesListener implements ListenerInterface
{
    public function listen(): array
    {
        return [AfterWorkerStart::class,];
    }

    public function process(object $event): void
    {
        Logger::debug("[hyperf-ext-tron] 注册路由");
        $routesPath = __DIR__ . '/routes.php';
        if (file_exists($routesPath)) {
            require $routesPath;
        } else {
            Logger::error("[hyperf-ext-tron] 注册路由失败：$routesPath 不存在");
        }
    }
}