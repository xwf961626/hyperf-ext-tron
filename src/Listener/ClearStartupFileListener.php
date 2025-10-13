<?php

namespace William\HyperfExtTron\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeServerStart;
use William\HyperfExtTron\Helper\Logger;

class ClearStartupFileListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BeforeServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        Logger::debug("服务器启动之前删除 /tmp/startup-tron.done");
        $startupFile = '/tmp/startup-tron.done';
        if (file_exists($startupFile)) {
            unlink($startupFile);
        }
    }
}