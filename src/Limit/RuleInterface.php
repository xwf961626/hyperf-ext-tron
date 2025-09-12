<?php

namespace William\HyperfExtTron\Limit;

use Hyperf\Database\Model\Model;

interface RuleInterface
{
    public function check(Model $model): bool;
}