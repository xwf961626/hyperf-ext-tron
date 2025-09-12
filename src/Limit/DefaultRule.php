<?php

namespace William\HyperfExtTron\Limit;

use Hyperf\Database\Model\Model;

class DefaultRule implements RuleInterface
{

    public function check(Model $model): bool
    {
        return true;
    }
}