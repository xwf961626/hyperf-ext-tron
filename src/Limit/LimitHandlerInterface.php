<?php

namespace William\HyperfExtTron\Limit;

use William\HyperfExtTron\Model\ResourceAddress;

interface LimitHandlerInterface
{
    public function handle(ResourceAddress $model);
}