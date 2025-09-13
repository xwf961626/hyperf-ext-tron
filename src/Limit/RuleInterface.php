<?php

namespace William\HyperfExtTron\Limit;

use William\HyperfExtTron\Model\ResourceAddress;

interface RuleInterface
{
    public function check(ResourceAddress $model): bool;
}