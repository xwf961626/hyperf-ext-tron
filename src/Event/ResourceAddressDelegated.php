<?php

namespace William\HyperfExtTron\Event;

use William\HyperfExtTron\Model\ResourceAddress;

class ResourceAddressDelegated
{
    public function __construct(public ResourceAddress $data)
    {
    }
}