<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Monitor\DefaultMonitorAdapter;
use William\HyperfExtTron\Monitor\MonitorAdapterInterface;
use William\HyperfExtTron\Monitor\TronMonitorProcess;
use function Hyperf\Support\make;

class MonitorTest extends TestCase
{
    public function testMonitor()
    {
        $this->container->set(MonitorAdapterInterface::class, make(DefaultMonitorAdapter::class));
        $process = make(TronMonitorProcess::class);
        $process->handle();
    }
}