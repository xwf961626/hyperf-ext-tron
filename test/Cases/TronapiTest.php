<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Tron\Account;
use William\HyperfExtTron\Tron\TronApi;
use function Hyperf\Support\make;

class TronapiTest extends TestCase
{
    public function testGetprice()
    {
        /** @var TronApi $tron */
        $tron = make(TronApi::class);
        $addr = 'TDDDDDD3ptnAHT5zFNux5ETTq2CodURqNT';
        $counts = $tron->usdtBalance($addr);
//        $usdt = $tron->usdtBalance($addr);
//        $account = new Account($tron->getAccount($addr));
//        $balance = $account->balance;
        // totalFrozenV2 = frozenForEnergyV2 + frozenForBandWidthV2 + delegatedFrozenV2BalanceForEnergy + delegatedFrozenV2BalanceForBandwidth

//        $this->assertTrue($account->isStake());
var_dump($counts);
    }
}