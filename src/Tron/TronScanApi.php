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

namespace William\HyperfExtTron\Tron;

use William\HyperfExtTron\Helper\GuzzleClient;
use William\HyperfExtTron\Helper\Logger;
use GuzzleHttp\Client;

class TronScanApi
{
    protected Client $http;
    protected Client $tronscanapi;

    public function __construct(protected TronService $service)
    {
        $this->tronscanapi = GuzzleClient::coroutineClient(['base_uri' => 'https://apilist.tronscanapi.com']);
    }

    public function balance(mixed $address): array
    {
        $trxBalance = 0;
        $usdtBalance = 0;
        $totalFrozenV2 = 0;
        $energyRemaining = 0;
        $energyLimit = 0;
        try {
            static $tronScanApiLastIndex = -1; // 静态变量，跨调用保存上次位置

            $keys = $this->service->getCacheApiKeys(TronService::TYPE_SCAN_KEY);
            $count = count($keys);

            if ($count === 0) {
                throw new \RuntimeException('No TRON_PRO_API_KEY configured.');
            }

            // 顺序取 key
            $tronScanApiLastIndex = ($tronScanApiLastIndex + 1) % $count;
            $currentKey = trim($keys[$tronScanApiLastIndex]);
            $res = $this->tronscanapi->get("/api/accountv2", [
                'query' => [
                    'address' => $address
                ],
                'headers' => [
                    'TRON-PRO-API-KEY' => $currentKey,
                ]
            ]);
            $json = json_decode($res->getBody()->getContents(), true);
            if (isset($json['withPriceTokens'])) {
                foreach ($json['withPriceTokens'] as $token) {
                    if ($token['tokenAbbr'] === 'trx') {
                        $trxBalance = $token['amount'];
                    }
                    if ($token['tokenAbbr'] === 'USDT') {
                        $usdtBalance = round($token['balance'] / 1_000_000, $token['tokenDecimal']);
                    }
                }
            }

            if(isset($json['bandwidth'])) {
                $bandwidth = $json['bandwidth'];
                $energyRemaining = $bandwidth['energyRemaining']??0;
                $energyLimit = $bandwidth['energyLimit']??0;
            }

        } catch (\Throwable $e) {
            Logger::error("查询余额失败：{$e->getMessage()}");
        }
        return compact('trxBalance', 'usdtBalance', 'totalFrozenV2', 'energyRemaining', 'energyLimit');
    }
}
