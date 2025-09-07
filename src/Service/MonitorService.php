<?php

namespace William\HyperfExtTron\Service;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use William\HyperfExtTron\Constant\TronConstant;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Monitor\MonitorAdapterInterface;
use William\HyperfExtTron\Tron\Transaction;
use William\HyperfExtTron\Tron\TronApi;

class MonitorService implements MonitorAdapterInterface
{
    protected mixed $config;
    protected array $addresses;
    protected int $currentBlock;
    /**
     * @var mixed|TronApi
     */
    protected mixed $api;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class)->get(TronConstant::CONFIG_NAME, []);
        $this->addresses = $this->config['monitor']['addresses'];
        $api = $container->get(TronApi::class);
        $this->currentBlock = $this->config['monitor']['start_block'] ?? $api->getLatestBlockNumber();
    }

    public function isMonitorAddress(string $address): bool
    {
        return in_array($address, $this->addresses);
    }

    public function onTransaction(Transaction $tx): void
    {
        Logger::debug("TronMonitor [{$tx->currency}] {$tx->from} -> {$tx->to} | {$tx->amount} | {$tx->tx_id}");
    }

    function getCurrentBlock(): int
    {
        return $this->currentBlock;
    }

    function updateBlockNum(int $blockNum): void
    {
        $this->currentBlock = $blockNum;
    }
}