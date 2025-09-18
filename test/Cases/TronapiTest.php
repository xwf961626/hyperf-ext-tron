<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Tron\TronApi;
use function Hyperf\Support\make;

class TronapiTest extends TestCase
{
    public function testGetprice()
    {
        /** @var TronApi $tron */
        $tron = make(TronApi::class);
        $addr = 'TCW1KoSRMXyvr41azdDiAdGmTxMZNj4Bhf';
        $counts = $tron->getTodayTotal($addr);
        $usdt = $tron->usdtBalance($addr);
        $trx = $tron->trxBalance($addr);
        var_dump(compact('counts','usdt', 'trx'));
    }
}