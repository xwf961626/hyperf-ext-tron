<?php

namespace William\HyperfExtTron\Limit;

use Hyperf\Database\Model\Model;

interface LimitHandlerInterface
{
    public function handle(Model $model);
}