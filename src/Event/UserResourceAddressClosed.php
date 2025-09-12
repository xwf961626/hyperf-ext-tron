<?php

namespace William\HyperfExtTron\Event;

use William\HyperfExtTron\Model\UserResourceAddress;

class UserResourceAddressClosed
{
    public UserResourceAddress $address;

    public function __construct(UserResourceAddress $address)
    {
        $this->address = $address;
    }
}