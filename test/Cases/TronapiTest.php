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
        $hash = '4c0e55e57cae1333191b43f6f9cd2cd10084bcb822b80c24ea3facfaf5d00773';
        $tx = $tron->getTransactionById($hash);
        if (isset($tx['raw_data']) && !empty($tx['raw_data']['contract'])) {
            $contract = $tx['raw_data']['contract'][0];
            if (isset($contract['parameter']) && isset($contract['parameter']['value'])) {
                $from = $contract['parameter']['value']['owner_address'] ?? '';
                if ($from) {
                    var_dump($from);
                }
            }
        }
    }
}