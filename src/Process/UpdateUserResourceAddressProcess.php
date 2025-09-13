<?php

namespace William\HyperfExtTron\Process;

use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\UserResourceAddress;
use William\HyperfExtTron\Service\UserResourceAddressService;
use function Hyperf\Config\config;

class UpdateUserResourceAddressProcess extends AbstractProcess
{

    public function __construct(ContainerInterface $container, protected UserResourceAddressService $service)
    {
        parent::__construct($container);
    }

    public function handle(): void
    {
        while (true) {
            try {
                $addressList = UserResourceAddress::where('status', 1)->get();
                if (!$addressList->isEmpty()) {
                    /** @var UserResourceAddress $address */
                    foreach ($addressList as $address) {
                        Logger::debug("更新地址池地址：{$address->address}");
                        $this->service->updateResources($address);
                    }
                }
            } catch (\Exception $e) {
                Logger::error("更新地址池地址：{$address->address} 失败：{$e->getMessage()}");
            } finally {
                sleep(intval(config('tron.pool.update_interval')));
            }
        }
    }
}