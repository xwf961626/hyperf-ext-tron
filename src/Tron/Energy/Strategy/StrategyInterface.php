<?php

namespace William\HyperfExtTron\Tron\Energy\Strategy;

use William\HyperfExtTron\Tron\Energy\Apis\ApiInterface;

interface StrategyInterface
{
    public function get(mixed $configs, string $rentalName): ?ApiInterface;
}
