<?php

namespace William\HyperfExtTron\Process;

use Exception;
use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Service\MonitorService;

class SimpleMonitorProcess extends AbstractProcess
{
    protected MonitorService $monitorService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->monitorService = $container->get(MonitorService::class);
    }

    public function handle(): void
    {
        while (true) {
            Logger::debug("Monitor getUpdates");
            try {
                $this->monitorService->getUpdates();
            } catch (Exception $e) {
                Logger::error($e->getMessage());
            }
            sleep(3);
        }
    }
}