<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use StephenHill\Base58;
use William\HyperfExtTron\Monitor\DefaultMonitorAdapter;
use William\HyperfExtTron\Monitor\MonitorAdapterInterface;
use William\HyperfExtTron\Monitor\TronMonitorProcess;
use William\HyperfExtTron\Process\SimpleMonitorProcess;
use function Hyperf\Support\make;

function base58Check(string $hex): string
{
    $bin = hex2bin($hex);
    if ($bin === false) {
        return '';
    }

    $hash0 = hash('sha256', $bin, true);
    $hash1 = hash('sha256', $hash0, true);
    $checksum = substr($hash1, 0, 4);

    $payload = $bin . $checksum;

    return (new Base58())->encode($payload); // 确保 Base58 encode 接受二进制
}


class MonitorTest extends TestCase
{
    public function testMonitor()
    {
//        $dataHex = "a9059cbb0000000000000000000000417e950c912d88d0566331aec126cd102d0fa2a7ce00000000000000000000000000000000000000000000000000000002540be400";
//        $addressWord = substr($dataHex, 8, 64); // methodId 后的 64 hex
//        $addressHex20 = substr($addressWord, 24, 40); // 取最后 20 bytes (40 hex)
//        $toHex = '41' . $addressHex20;
//        $addr = base58Check($toHex);
//        var_dump($toHex);
//        var_dump($addr);
//        $this->assertTrue($addr == "TMWWhLnfTLzXdfjqvRUBim4HmaoZmd97Qk");
        $this->container->set(MonitorAdapterInterface::class, make(DefaultMonitorAdapter::class));
        $process = make(TronMonitorProcess::class);
        $process->handle();
    }
}