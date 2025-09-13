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
use Elliptic\EC;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use kornrunner\Secp256k1;
use kornrunner\Serializer\HexSignatureSerializer;
use StephenHill\Base58;
use function Hyperf\Config\config;
use function Hyperf\Support\env;
use function Hyperf\Support\make;

class TronApi
{
    protected Client $http;

    protected int $lastScannedBlock;
    protected EC $ec;
    protected string $privateKey;
    protected FullNodeHttpApi $wallet;
    protected FullNodeSolidityHTTPAPI $walletSolidity;

    public function __construct(protected TronService $service)
    {
        $this->privateKey = config('tron.private_key', '');
        $startBlock = 0;
        $this->http = GuzzleClient::coroutineClient();
        $this->lastScannedBlock = $startBlock;
        $this->ec = new EC('secp256k1');
        $this->wallet = make(FullNodeHttpApi::class);
        $this->walletSolidity = make(FullNodeSolidityHTTPAPI::class);
    }

    public function getTrx2UsdtRate()
    {
        $url = "https://api.binance.com/api/v3/ticker/price?symbol=TRXUSDT";
        $options = [
            'verify' => false,
        ];
        $client = new Client($options);
        $resp = $client->get($url);
        $result = $resp->getBody()->getContents();
        $retArr = json_decode($result, true);
        return $retArr['price'];
    }

    /**
     * 获取最新块高度.
     */
    public function getLatestBlockNumber(): int
    {
        $res = $this->wallet->get("/wallet/getnowblock", [], $this->service->getCacheApiKeys());
        $data = json_decode($res->getBody()->getContents(), true);
        return $data['block_header']['raw_data']['number'] ?? 0;
    }

    /**
     * 根据块号获取块信息.
     */
    public function getBlockByNumber(int $blockNumber, callable $onBlock): bool
    {
        $res = $this->wallet->post("/wallet/getblockbynum", ['num' => $blockNumber, 'visible' => true], $this->service->getCacheApiKeys());
        $block = json_decode($res->getBody()->getContents(), true);
        if (!isset($block['transactions'])) {
            return false;
        }
        $onBlock($block);
        return true;
    }

    /**
     * 代理资源
     * @param string $ownerAddress 发起地址
     * @param string $resource 资源类型 ENERGY-能量 BANDWIDTH-带宽
     * @param string $receiverAddress 接收地址
     * @param int $balance 代理金额
     * @param int $permissionId 授权ID
     * @param bool $lock 是否锁定
     * @param int $lockPeriod 锁定时间
     * @return string 哈希
     * @throws GuzzleException
     */
    public function delegateResource(string $ownerAddress,
                                     string $resource,
                                     string $receiverAddress,
                                     int    $balance,
                                     int    $permissionId,
                                     bool   $lock = false,
                                     int    $lockPeriod = 0): string
    {
        $params = [
            'owner_address' => $ownerAddress,
            'resource' => $resource,
            'receiver_address' => $receiverAddress,
            'balance' => $balance,
            "visible" => true,
            "lock" => $lock,
            'lock_period' => $lockPeriod,
            'Permission_id' => $permissionId,
        ];
        $res = $this->wallet->post("/wallet/delegateresource", $params, $this->service->getCacheApiKeys());
        $content = $res->getBody()->getContents();
        Logger::info('TronApi#delegateResource => ' . $content);
        $tx = json_decode($content, true);
        if (isset($tx['txID'])) {
            return $this->broadcastTransaction($tx);
        } else {
            throw new \RuntimeException('TronApi#DelegateResource failed. API Response:' . $content);
        }
    }

    /**
     * 回收资源
     * @param string $ownerAddress 发起地址
     * @param string $resource 资源类型 ENERGY-能量 BANDWIDTH-带宽
     * @param string $receiverAddress 接收地址
     * @param float $balance 回收金额
     * @param int $permissionId 授权ID
     * @return string 哈希
     * @throws GuzzleException
     */
    public function unDelegateResource(string $ownerAddress, string $resource, string $receiverAddress, float $balance, int $permissionId): string
    {
        $params = [
            'owner_address' => $ownerAddress,
            'resource' => $resource,
            'receiver_address' => $receiverAddress,
            'balance' => $balance,
            "visible" => true,
            'Permission_id' => $permissionId,
        ];
        $res = $this->wallet->post("/wallet/undelegateresource", $params, $this->service->getCacheApiKeys());
        $content = $res->getBody()->getContents();
        Logger::info('TronApi#UndelegateResource => ' . $content);
        $tx = json_decode($content, true);
        if (isset($tx['txID'])) {
            return $this->broadcastTransaction($tx);
        } else {
            throw new \RuntimeException('TronApi#unDelegateResource failed. API Response:' . $content);
        }
    }

    /**
     * 广播交易
     * @param array $tx 交易
     * @return string 交易哈希
     * @throws GuzzleException
     */
    public function broadcastTransaction($tx): string
    {
        $tx['signature'] = [$this->sign($tx['txID'], $this->privateKey)];
        Logger::info('TronApi#broadcastTransaction => ' . json_encode($tx));
        $res = $this->wallet->post("/wallet/broadcasttransaction", $tx, $this->service->getCacheApiKeys());
        Logger::info('TronApi#broadcasttransaction => ' . $res->getBody()->getContents());
        $content = $res->getBody()->getContents();
        Logger::info('TronApi#UndelegateResource => ' . $content);
        $result = json_decode($content, true);
        if (isset($result['result']) && $result['result'] === true) {
            return $result['txid'];
        } else {
            throw new \RuntimeException('TronApi#broadcasttransaction failed. API Response:' . $content);
        }
    }

    /**
     * 交易本地签名
     * @param string $txID
     * @param string $privateKeyHex
     * @return string
     */
    public function sign(string $txID, string $privateKeyHex): string
    {
        $secp256k1 = new Secp256k1();
        $signature = $secp256k1->sign($txID, $privateKeyHex);
        $r = $signature->getR();
        $s = $signature->getS();
        $v = $signature->getRecoveryParam();
        $serializer = new HexSignatureSerializer();
        return $serializer->serialize($signature) . str_pad(dechex($v), 2, '0', STR_PAD_LEFT);
    }

    /**
     * 验证地址格式是否正确
     * @param string $address
     * @return bool
     */
    public function isAddress(string $address): bool
    {
        // Base58 地址长度一般在 34~36
        if (!str_starts_with($address, 'T') || strlen($address) < 34 || strlen($address) > 36) {
            return false;
        }

        $base58 = new Base58();

        try {
            $decoded = $base58->decode($address);
            // TRON Base58 地址解码后长度必须是 25
            if (strlen($decoded) !== 25) {
                return false;
            }
            // TRON 地址前缀字节必须是 0x41
            return ord($decoded[0]) === 0x41;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 是否已激活
     * @param string $address
     * @return bool
     */
    public function isActive(string $address): bool
    {
        try {
            $accounts = $this->getAccounts($address);
            return !empty($accounts);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取地址账号信息
     * @param string $address
     * @return array
     */
    public function getAccounts(string $address): array
    {
        $resp = $this->wallet->post('/wallet/getaccount', ['address' => $address, 'visible' => true], $this->service->getCacheApiKeys());
        if ($resp->getStatusCode() !== 200) {
            throw new \RuntimeException('TronApi#getAccounts failed: ' . $resp->getBody()->getContents());
        }
        $accounts = $resp->getBody()->getContents();
        return json_decode($accounts, true);
    }

    /**
     * 获取授权ID
     * @param string $address
     * @param string $operationAddress
     * @return int
     */
    public function getPermissionId(string $address, string $operationAddress): int
    {
        $accounts = $this->getAccounts($address);
        return $this->getPermissionIdByAccounts($operationAddress, $accounts);
    }

    public function getPermissionIdByAccounts($operationAddress, array $accounts): int
    {
        if (!empty($accounts['active_permission'])) {
            foreach ($accounts['active_permission'] as $activePermission) {
                $keys = $activePermission['keys'] ?? [];
                foreach ($keys as $key) {
                    if ($key['address'] === $operationAddress) {
                        return $activePermission['id'];
                    }
                }
            }
        }
        return 0;
    }

    /**
     * 查询地址资源
     * @param string $address
     * @return AccountResource
     * @throws GuzzleException
     */
    public function getAccountResources(string $address): AccountResource
    {
        $resp = $this->wallet->post('/wallet/getaccountresource', [
            'address' => $address,
            'visible' => true,
        ], $this->service->getCacheApiKeys());
        if ($resp->getStatusCode() == 200) {
            $result = json_decode($resp->getBody()->getContents());
            return AccountResource::of($result);
        } else {
            throw new \Exception($resp->getBody()->getContents());
        }
    }

    /**
     * 查询指定资源的 TRX 价格。
     *
     * @param string $resource 资源类型，可选值：
     *     - ENERGY    能量
     *     - BANDWIDTH 带宽
     *
     * @return float 返回对应资源的 TRX 价格
     *
     * @throws GuzzleException 当请求失败时抛出
     */
    public function getResourcePrice(string $resource): float
    {
        $resource = strtoupper($resource);
        $data = $this->getAccountResources('T9ya3Pck5BoPHfdHvSSPfDnZ5x2BDeEvvV');
        if ($resource === 'ENERGY' && $data->totalEnergy > 0) {
            $price = $data->totalEnergyWeight / $data->totalEnergy; //1个单位资源的价格
            return $price;
        }
        if ($resource === 'BANDWIDTH' && $data->totalNet > 0) {
            $price = $data->totalNetWeight / $data->totalNet; //1个单位资源的价格
            return $price;
        }
        throw new \Exception('不支持的来源类型：' . $resource);
    }
}
