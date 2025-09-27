<?php

namespace William\HyperfExtTron\Monitor;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use William\HyperfExtTron\Constant\TronConstant;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Tron\Transaction;
use William\HyperfExtTron\Tron\TronApi;

class DefaultMonitorAdapter implements MonitorAdapterInterface
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
        $this->addresses = explode(',', $this->config['monitor']['addresses']);
        $this->currentBlock = $this->config['monitor']['start_block'] ?? 0;
    }

    public function isMonitorAddress(string $address): bool
    {
        return in_array($address, $this->addresses);
    }

    public function onTransaction(Transaction $tx): void
    {
        Logger::debug("TronMonitor [{$tx->currency}] {$tx->from} -> {$tx->to} | {$tx->amount} | {$tx->tx_id}");
    }

    public function onNotify(array $notifyData): void
    {
        Logger::debug("TronMonitor on notify <= " . json_encode($notifyData));
    }
}