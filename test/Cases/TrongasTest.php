<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Apis\CatFee;
use William\HyperfExtTron\Apis\Trongas;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;

class TrongasTest extends TestCase
{
    public function testApi()
    {
        /** @var EnergyApiFactory $factory */
        $factory = $this->container->get(EnergyApiFactory::class);
        /** @var Trongas $api */
        $api = $factory->get(Trongas::class);
        $api->delegate(
            'TDDDDDD3ptnAHT5zFNux5ETTq2CodURqNT',
            65000,
            '1h',
        );
    }
}