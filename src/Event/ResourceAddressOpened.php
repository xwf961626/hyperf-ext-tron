<?php

namespace William\HyperfExtTron\Event;

use William\HyperfExtTron\Model\ResourceAddress;

class ResourceAddressOpened
{
    public function __construct(public ResourceAddress $class)
    {
    }
}