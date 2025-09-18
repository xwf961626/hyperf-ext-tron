<?php

namespace William\HyperfExtTron\Event;

use William\HyperfExtTron\Model\ResourceAddress;

class ResourceAddressClosed
{
    public function __construct(public ResourceAddress $class)
    {
    }
}