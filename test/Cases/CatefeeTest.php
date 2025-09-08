<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Apis\CatFee;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;

class CatefeeTest extends TestCase
{
    public function testApi()
    {
        /** @var EnergyApiFactory $factory */
        $factory = $this->container->get(EnergyApiFactory::class);
        /** @var CatFee $api */
        $api = $factory->get(CatFee::class);
        $name = $api->name();
        $this->assertEquals(CatFee::API_NAME, $name);
        $api->send('TY21hRktYANtc92m1eGpQsoCsUXguP5sLq', 65000, '1h', 1);
    }
}