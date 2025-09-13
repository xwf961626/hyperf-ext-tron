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
        $price = $tron->getResourcePrice('BANDWIDTH');
        var_dump($price);
    }
}