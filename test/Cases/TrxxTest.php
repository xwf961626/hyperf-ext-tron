<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Apis\CatFee;
use William\HyperfExtTron\Apis\Trxx;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;

class TrxxTest extends TestCase
{
    public function testApi()
    {
        /** @var EnergyApiFactory $factory */
        $factory = $this->container->get(EnergyApiFactory::class);
        /** @var Trxx $api */
        $api = $factory->get(Trxx::class);
        $name = $api->name();
        $this->assertEquals(Trxx::API_NAME, $name);
        $balance = $api->getBalance();
        var_dump($balance);
    }
}