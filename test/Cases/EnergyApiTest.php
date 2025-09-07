<?php
namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase;
use William\HyperfExtTron\Apis\Weidubot;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;

/**
 * @internal
 * @coversNothing
 */
class EnergyApiTest extends TestCase
{
    public function testWeiduapi()
    {
        /** @var EnergyApiFactory $factory */
        $factory = $this->container->get(EnergyApiFactory::class);
        /** @var Weidubot $api */
        $api = $factory->get(EnergyApi::API_WEIDU);
        $name = $api->name();
        $this->assertEquals('weidu', $name);
    }
}