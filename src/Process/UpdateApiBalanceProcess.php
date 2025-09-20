<?php

namespace William\HyperfExtTron\Process;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Process\AbstractProcess;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;
use William\HyperfExtTron\Tron\TronService;
use function Hyperf\Config\config;

class UpdateApiBalanceProcess extends AbstractProcess
{
    public function __construct(ContainerInterface $container, protected EnergyApiFactory $factory, protected TronService $tronService)
    {
        parent::__construct($container);
    }


    public function handle(): void
    {
        Logger::debug("开始更新API余额");
        while (true) {
            $apis = Api::query()->get();
            /** @var Api $api */
            foreach ($apis as $api) {
                $instance = $this->factory->get(config('tron.apis')[$api['name']]['class']);
                try {
                    $balance = $instance->getBalance();
                    $api->balance = $balance;
                    $api->save();
                    Logger::error("{$instance->name()} 更新余额成功：{$balance}");
                } catch (\Exception $e) {
                    Logger::error("{$instance->name()} 更新余额失败：{$e->getMessage()}");
                }
            }
            sleep(10);
        }
    }
}