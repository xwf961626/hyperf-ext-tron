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
        $api->delegate(
            'TDDDDDD3ptnAHT5zFNux5ETTq2CodURqNT',
            65000,
            '1h',
        );
//        $data = $api->getOrderDetail('8a5de52a-60c6-47e2-9f9d-222a09eecc8c');
//        var_dump($data);
    }
}