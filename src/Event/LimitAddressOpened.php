<?php

namespace William\HyperfExtTron\Event;

use William\HyperfExtTron\Model\LimitResourceAddress;

class LimitAddressOpened
{
    public function __construct(public LimitResourceAddress $address)
    {
    }
}