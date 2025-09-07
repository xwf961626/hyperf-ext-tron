<?php

namespace William\HyperfExtTron\Monitor;

use William\HyperfExtTron\Tron\Transaction;

interface MonitorAdapterInterface
{
    public function onTransaction(Transaction $tx);

    public function isMonitorAddress(string $address): bool;

    function getCurrentBlock(): int;
    function updateBlockNum(int $blockNum): void;
}