<?php

namespace William\HyperfExtTron\Event;

use William\HyperfExtTron\Model\LimitResourceAddress;

class LimitAddressCreated
{
    public function __construct(public LimitResourceAddress $address)
    {
    }
}