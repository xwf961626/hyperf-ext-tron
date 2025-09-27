<?php

namespace William\HyperfExtTron\Service;

use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\Transaction;
use William\HyperfExtTron\Monitor\MonitorAdapterInterface;
use William\HyperfExtTron\Tron\ApiKeyTrait;
use William\HyperfExtTron\Tron\TronGridApi;

class MonitorService
{
    use ApiKeyTrait;

    const LIMIT = 200;
    protected Redis $redis;

    public function __construct(RedisFactory $redisFactory, protected TronGridApi $api, protected MonitorAdapterInterface $monitorAdapter)
    {
        $this->redis = $redisFactory->get('default');
    }

    /**
     * @throws \RedisException
     */
    public function getUpdates()
    {
        $addressList = $this->getMonitorList();
        Logger::debug('MonitorList => ' . json_encode($addressList));
        foreach ($addressList as $address) {
            $this->getAddressTransactions($address);
        }
    }

    /**
     * @throws \RedisException
     */
    public function getAddressTransactions($address): void
    {
        $lastTime = $this->getLastTime($address);
        $result = $this->getTransactions($address, $lastTime);
        $trans = $result['data'];
        $currentTime = $lastTime;
        foreach ($trans as $tx) {
            $blockTime = $tx['block_timestamp'];
            if ($blockTime > $currentTime) {
                $currentTime = $blockTime;
            }
            $contract = $tx['raw_data']['contract'][0]['parameter']['value'];
            $type = $tx['raw_data']['contract'][0]['type'];
            $hash = $tx['txID'];
            if (Transaction::where('hash', $hash)->exists()) {
                continue;
            }
            Logger::debug("txID => $hash");
            $success = $tx['ret'][0]['contractRet'] === 'SUCCESS';
            if (!$success) {
                continue;
            }
            $notifyData = [
                'hash' => $hash,
                'type' => $type,
                'block_id' => $tx['blockNumber'],
                'contract' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                'transacted_at' => date('Y-m-d H:i:s', intval($blockTime / 1000)),
                'transacted_time' => intval($blockTime / 1000),
                'transacted_amount_decimals' => 6,
                'client_id' => 'hyperf-monitor',
                'result' => true,
            ];
            if ($type === 'TransferContract') {
                $amountSun = $contract['amount']; // 金额 (单位 SUN)
                $ownerHex = $contract['owner_address'];
                $toHex = $contract['to_address'];
                // 转换地址
                $ownerBase58 = $this->tronHexToBase58($ownerHex);
                $toBase58 = $this->tronHexToBase58($toHex);

                // 转换金额（1 TRX = 1,000,000 SUN）
                $amountTRX = $amountSun / 1000000;
                $notifyData = array_merge($notifyData, [
                    'amount' => $amountTRX,
                    'transacted_amount' => $amountTRX,
                    'from' => $ownerBase58,
                    'to' => $toBase58,
                    'coin_name' => 'TRX',
                    'text' => 'TRX转账',
                ]);
            } else if ($type === 'TriggerSmartContract') {
                $ownerHex = $contract['owner_address'];
                $ownerBase58 = $this->tronHexToBase58($ownerHex);

                $contractAddressHex = $contract['contract_address'];
                $contractBase58 = $this->tronHexToBase58($contractAddressHex);

                $dataHex = $contract['data'];

                $method = substr($dataHex, 0, 8); // a9059cbb
                $toHex = '41' . substr($dataHex, 32 + 24, 40); // 接收方地址 (加41)
                $toBase58 = $this->tronHexToBase58($toHex);

                $amountHex = substr($dataHex, 72); // 后 32 字节
                $amount = hexdec($amountHex); // 单位是 6 位小数
                $amountUSDT = $amount / 1000000;
                // 示例：{"to":"TX8YRN98NLo1NTLxBzWBZyYMwcipAs7fhp","from":"TDPMantWH9QskpsWdYjQHnWLhecHh845Qr",
                //"hash":"2d465b3868472b85574df45336d67640cb1fb8b867d90d00952f86c666252c38","text":"TRX转账交易",
                //"type":"TransferContract","chain":"tron","result":true,"block_id":75949896,"coin_name":"trx",
                //"transacted_at":"2025-09-22 10:55:55","transacted_time":1758509755,"transacted_amount":"0.4",
                //"transacted_amount_decimals":6}
                $notifyData = array_merge($notifyData, [
                    'transacted_amount' => $amountUSDT,
                    'amount' => $amountUSDT,
                    'from' => $ownerBase58,
                    'to' => $toBase58,
                    'coin_name' => 'USDT',
                    'text' => 'USDT转账',
                    'contract_address' => $contractBase58,
                    'method' => $method,
                ]);
            }
            if (isset($notifyData['to'])) {
                Logger::debug('Notify data => ' . json_encode($notifyData));
                Transaction::create($notifyData);
                $this->monitorAdapter->onNotify($notifyData);
            }
        }
        if ($currentTime > $lastTime) {
            $this->updateLastTime($address, $currentTime);
            if (count($trans) == self::LIMIT) {
                $this->getAddressTransactions($address);
            }
        }
    }


    public function getMonitorList(): array
    {
        return explode(',', \Hyperf\Support\env('MONITOR_ADDRESS'));
    }

    public function getLastTime(string $address): mixed
    {
        $lastTimeStr = $this->redis->hget('monitor:last_times', $address);
        if (!$lastTimeStr) {
            $lastTime = intval(microtime(true) * 1000);
            $this->redis->hset('monitor:last_times', $address, (string)$lastTime);
            return $lastTime;
        }
        return intval($lastTimeStr);
    }

    /**
     * @throws \RedisException
     */
    public function updateLastTime($address, $lastTime): void
    {
        $this->redis->hset('monitor:last_times', $address, (string)$lastTime);
    }

    public function getTransactions($address, $lastTime): array
    {
        $url = "/v1/accounts/{$address}/transactions";
        $query = [
            'min_timestamp' => $lastTime,
            'limit' => self::LIMIT,
            'order_by' => 'block_timestamp,asc',
        ];
        Logger::debug("HTTP GET => $url query:".json_encode($query));
        $resp = $this->api->get($url, $query, $this->getCacheApiKeys());
        if($resp->getStatusCode() !== 200){
            Logger::error("TRON GET $url 失败：{$resp->getStatusCode()}:{$resp->getBody()->getContents()}");
            return [];
        } else {
            return json_decode($resp->getBody()->getContents(), true);
        }
    }

    /**
     * Hex 转 Base58 Tron 地址
     */
    public function tronHexToBase58($hexAddress)
    {
        // hex 转二进制
        $addressBin = hex2bin($hexAddress);

        // 计算校验码：sha256 两次取前 4 字节
        $hash = hash('sha256', $addressBin, true);
        $hash = hash('sha256', $hash, true);
        $checksum = substr($hash, 0, 4);

        // 拼接地址和校验码
        $addressWithChecksum = $addressBin . $checksum;

        // Base58 编码
        return $this->base58Encode($addressWithChecksum);
    }

    /**
     * Base58 编码
     */
    public function base58Encode($input)
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        $num = gmp_init(bin2hex($input), 16);
        $encoded = '';

        while (gmp_cmp($num, 0) > 0) {
            list($num, $rem) = gmp_div_qr($num, $base);
            $encoded = $alphabet[gmp_intval($rem)] . $encoded;
        }

        // 处理前导零
        $pad = '';
        foreach (str_split($input) as $char) {
            if ($char === "\x00") {
                $pad .= '1';
            } else {
                break;
            }
        }

        return $pad . $encoded;
    }
}