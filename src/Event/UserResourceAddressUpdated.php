<?php

namespace William\HyperfExtTron\Event;

use William\HyperfExtTron\Model\UserResourceAddress;

class UserResourceAddressUpdated
{
    public UserResourceAddress $address;

    public function __construct(UserResourceAddress $address)
    {
        $this->address = $address;
    }
}