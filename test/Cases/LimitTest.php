<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Limit\LimitCheckProcess;
use function Hyperf\Support\make;

class LimitTest extends TestCase
{
    public function testLimit()
    {
        /** @var LimitCheckProcess $process */
        $process = make(LimitCheckProcess::class);
        $process->handle();
    }
}