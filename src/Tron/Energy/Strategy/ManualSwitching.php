<?php

namespace William\HyperfExtTron\Tron\Energy\Strategy;

use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Tron\Energy\Apis\ApiInterface;
use William\HyperfExtTron\Tron\Energy\Attributes\Strategy;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;

#[Strategy(name: 'manualSwitching')]
class ManualSwitching implements StrategyInterface
{
    protected EnergyApiFactory $apiFactory;

    public function __construct(EnergyApiFactory $apiFactory)
    {
        $this->apiFactory = $apiFactory;
    }

    public function get(mixed $configs, string $rentalName): ?ApiInterface
    {
        Logger::info("ManualSwitching#$rentalName 获取来源, 配置：" . json_encode($configs));
        if ($c = $configs[$rentalName]) {
            return $this->apiFactory->get($c['api']);
        }
        return null;
    }
}
