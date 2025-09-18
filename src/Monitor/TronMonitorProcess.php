<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace William\HyperfExtTron\Monitor;

use Hyperf\Cache\Cache;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use William\HyperfExtTron\Constant\TronConstant;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Tron\Transaction;
use William\HyperfExtTron\Tron\TronApi;
use Hyperf\Process\AbstractProcess;
use Hyperf\Redis\Redis;
use RedisException;
use StephenHill\Base58;
use Swoole\Coroutine\Channel;
use Throwable;
use function Hyperf\Config\config;

class TronMonitorProcess extends AbstractProcess
{
    protected TronApi $scanner;

    protected Redis $redis;
    protected MonitorAdapterInterface $monitorAdapter;

    public function __construct(ContainerInterface $container, private Cache $cache)
    {
        parent::__construct($container);
        $this->scanner = $container->get(TronApi::class);
        $this->monitorAdapter = $container->get(MonitorAdapterInterface::class);
    }

    /**
     * @throws RedisException
     */
    public function handle(): void
    {
        Logger::info('TronMonitorProcess::handle');

        $maxConcurrent = 10; // 最多并发10个协程
        $chan = new Channel($maxConcurrent);

        $startBlock = $this->getStartBlock();
        $this->cache->set(TronConstant::CACHE_SCAN_CURRENT_BLOCK, $startBlock);

        while (true) {
//            Logger::debug('扫块...');
            $currentBlock = $this->getCurrentBlock();
            try {
                $latestBlock = $this->scanner->getLatestBlockNumber();
            } catch (Throwable $e) {
                Logger::error($e->getMessage());
                sleep(1);
                continue;
            }

            if ($currentBlock > $latestBlock) {
                sleep(1);
                continue;
            }

            $endBlock = min($currentBlock + $maxConcurrent - 1, $latestBlock);

            // 循环内只处理，不更新 Redis
            for ($blockNum = $currentBlock; $blockNum <= $endBlock; ++$blockNum) {
                $chan->push(1);
                \Hyperf\Coroutine\go(function () use ($blockNum, $chan) {
                    try {
//                        Logger::debug("TronMonitorProcess#Scan block $blockNum");
                        $this->scanner->getBlockByNumber($blockNum, function ($block) use ($blockNum) {
                            $this->handleBlock($blockNum, $block, function (Transaction $tx) {
                                $this->monitorAdapter->onTransaction($tx);
                            });
                        });
                    } catch (Throwable $e) {
                        Logger::error("Error scanning block {$blockNum}: " . $e->getMessage());
                        Logger::error("Error scanning block {$blockNum}: " . $e->getTraceAsString());
                    } finally {
                        $chan->pop();
                    }
                });
            }

            // 等待所有协程完成
            while ($chan->length() > 0) {
                usleep(10000);
            }

            $this->cache->set(TronConstant::CACHE_SCAN_CURRENT_BLOCK, $endBlock + 1);
        }
    }

    protected function handleBlock(int $blockNum, array $block, callable $onTransaction): void
    {
        $txList = $block['transactions'] ?? [];
        $utcTimestampMs = $block['block_header']['raw_data']['timestamp'] ?? 0; // 毫秒
        // 统一获取交易时间，单位秒
        $txTime = intval($utcTimestampMs / 1000) + 8 * 3600; // 秒数 +8小时
        foreach ($txList as $tx) {
            $txID = $tx['txID'] ?? '';
            $ret = $tx['ret'][0]['contractRet'] ?? 'FAILED'; // 成功/失败
            if ($ret !== 'SUCCESS') {
                continue; // 只处理成功的交易
            }

            $contract = $tx['raw_data']['contract'][0] ?? [];
            $type = $contract['type'] ?? '';
            $parameter = $contract['parameter']['value'] ?? [];

            // TRX 转账
            if ($type === 'TransferContract') {
                $to = $parameter['to_address'];
                if (!$this->monitorAdapter->isMonitorAddress($to) && !$this->monitorAdapter->isMonitorAddress($parameter['owner_address'])) {
                    continue;
                }
                $onTransaction(Transaction::of([
                    'tx_id' => $txID,
                    'currency' => 'TRX',
                    'from' => $parameter['owner_address'],
                    'to' => $to,
                    'amount' => $parameter['amount'] / 1_000_000, // 转成 TRX 单位
                    'type' => $type,
                    'timestamp' => $txTime,
                ]));
            }

            // USDT 转账（TRC20）
            if ($type === 'TriggerSmartContract') {
                $contractAddress = $contract['parameter']['value']['contract_address'] ?? '';

                // 判断是否为 USDT 合约地址（Hex）
                if (strtolower($contractAddress) === 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t') {
                    $dataHex = $contract['parameter']['value']['data'] ?? '';
                    $methodId = substr($dataHex, 0, 8);
                    if ($methodId === 'a9059cbb') {
                        $toHex = '41' . substr($dataHex, 8, 40); // 地址 20 bytes
                        $amountHex = substr($dataHex, 48);   // 剩下的是金额
                        $amount = hexdec($amountHex) / 1_000_000;

                        $to = $this->base58Check($toHex);
                        if (!$this->monitorAdapter->isMonitorAddress($to) && !$this->monitorAdapter->isMonitorAddress($parameter['owner_address'])) {
                            continue;
                        }

                        $onTransaction(Transaction::of([
                            'tx_id' => $txID,
                            'currency' => 'USDT',
                            'from' => $parameter['owner_address'],
                            'to' => $to,
                            'amount' => $amount,
                            'type' => $type,
                            'timestamp' => $txTime,
                        ]));
                    }
                }
            }
        }
    }

    protected function base58Check(string $hex): string
    {
        $bin = hex2bin($hex);
        if ($bin === false) {
            return '';
        }
        return (new Base58())->encode($bin);
    }

    private function getCurrentBlock(): int
    {
        $block = $this->cache->get(TronConstant::CACHE_SCAN_CURRENT_BLOCK) ?? $this->scanner->getLatestBlockNumber();
        if ($block) return (int)$block;
        return $this->scanner->getLatestBlockNumber();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getStartBlock(): int
    {
        Logger::debug("TronMonitorProcess::handle 获取开始块");
        $startBlockMode = config('tron.monitor.start_block_mode');
        Logger::debug("TronMonitorProcess::handle startBlockMode:{$startBlockMode}");
        if (is_int($startBlockMode)) {
            return $startBlockMode;
        }
        if ($startBlockMode === 'cache') {
            $cache = (int)$this->cache->get(TronConstant::CACHE_SCAN_CURRENT_BLOCK);
            if ($cache === 0) {
                return $this->scanner->getLatestBlockNumber();
            }
            return $cache;
        }
        if ($startBlockMode === 'latest') {
            return $this->scanner->getLatestBlockNumber();
        }
        throw new \Exception('未找到开始块');
    }
}
