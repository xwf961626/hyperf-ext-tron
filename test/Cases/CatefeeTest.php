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
//        /** @var CatFee $api */
        $api = $factory->get(CatFee::class);
        $name = $api->name();
//        $this->assertEquals(CatFee::API_NAME, $name);
//        $api->delegate(
//            'TDDDDDD3ptnAHT5zFNux5ETTq2CodURqNT',
//            65000,
//            '1h',
//        );
        $data = $api->getOrderDetail('2c42ac28-a88d-472d-8b3b-de286532f452');
        var_dump($data);
    }
}