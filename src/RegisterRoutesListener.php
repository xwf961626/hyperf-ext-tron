<?php

namespace William\HyperfExtTron;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;

class RegisterRoutesListener implements ListenerInterface
{
    public function listen(): array
    {
        return [AfterWorkerStart::class,];
    }

    public function process(object $event): void
    {
        $routesPath = __DIR__ . '/routes.php';
        if (file_exists($routesPath)) {
            require $routesPath;
        }
    }
}