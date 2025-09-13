<?php

namespace William\HyperfExtTron\Service;

use William\HyperfExtTron\Model\UserResourceAddress;
use William\HyperfExtTron\Tron\TronApi;

class UserResourceAddressService
{
    public function __construct(protected TronApi $tronApi)
    {
    }

    public function updateResources(UserResourceAddress $address)
    {
        $accounts = $this->tronApi->getAccounts($address->address);
        $permissionId = $this->tronApi->getPermissionIdByAccounts($address->operate_address, $accounts);
        if ($permissionId) {
            $address->permission = $permissionId;
            $balance = $accounts['balance']/1000000;
            $address->balance = $balance;
            $resource = $this->tronApi->getAccountResources($address->address);
            $address->energy_limit = $resource->totalEnergy;
            $address->energy = $resource->currentEnergy;
            $address->bandwidth_limit = $resource->totalNet;
            $address->bandwidth = $resource->currentNet;
            $address->save();
        }
        return $address;
    }
}