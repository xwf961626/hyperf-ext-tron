<?php

namespace William\HyperfExtTron\Event;

use William\HyperfExtTron\Model\LimitResourceAddress;

class LimitAddressClosed
{
    public function __construct(public LimitResourceAddress $address)
    {
    }
}