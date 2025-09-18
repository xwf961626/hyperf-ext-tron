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

use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use William\HyperfExtTron\Helper\GuzzleClient;
use William\HyperfExtTron\Helper\Logger;
use Elliptic\EC;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use kornrunner\Secp256k1;
use kornrunner\Serializer\HexSignatureSerializer;
use StephenHill\Base58;
use William\HyperfExtTron\Helper\RedisLock;
use function Hyperf\Config\config;

use function Hyperf\Support\make;

class TronApi
{
    protected Client $http;

    protected int $lastScannedBlock;
    protected EC $ec;
    protected string $privateKey;
    protected FullNodeHttpApi $wallet;
    protected FullNodeSolidityHTTPAPI $walletSolidity;
    protected Redis $redis;
    protected TronGridApi $tronGrid;
    private $cachePrefix = 'ext-tron-';
    protected RedisLock $lock;

    public function __construct(protected TronService $service, RedisFactory $redisFactory)
    {
        $this->privateKey = config('tron.private_key', '');
        $startBlock = 0;
        $this->http = GuzzleClient::coroutineClient();
        $this->lastScannedBlock = $startBlock;
        $this->ec = new EC('secp256k1');
        $this->wallet = make(FullNodeHttpApi::class);
        $this->walletSolidity = make(FullNodeSolidityHTTPAPI::class);
        $this->tronGrid = make(TronGridApi::class);
        $this->redis = $redisFactory->get('default');
        $this->lock = new RedisLock($this->redis, $this->cachePrefix . 'api-lock');
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
        Logger::debug("⚡ 代理资源参数 => " . json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $res = $this->wallet->post("/wallet/delegateresource", $params, $this->service->getCacheApiKeys());
        $content = $res->getBody()->getContents();
        Logger::info("📨 TronApi#delegateResource 响应 => {$content}");

        $tx = json_decode($content, true);
        if (isset($tx['txID'])) {
            Logger::info("✅ 代理资源成功 | TXID={$tx['txID']}");
            return $this->broadcastTransaction($tx);
        } else {
            Logger::error("❌ TronApi#delegateResource 失败 | 响应: {$content}");
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
    public function unDelegateResource(
        string $ownerAddress,
        string $resource,
        string $receiverAddress,
        float  $balance,
        int    $permissionId
    ): string
    {
        $params = [
            'owner_address' => $ownerAddress,
            'resource' => $resource,
            'receiver_address' => $receiverAddress,
            'balance' => $balance,
            'visible' => true,
            'Permission_id' => $permissionId,
        ];

        Logger::debug("♻️ 回收资源参数 => " . json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $res = $this->wallet->post("/wallet/undelegateresource", $params, $this->service->getCacheApiKeys());
        $content = $res->getBody()->getContents();

        Logger::info("📨 TronApi#unDelegateResource 响应 => {$content}");

        $tx = json_decode($content, true);
        if (isset($tx['txID'])) {
            Logger::info("✅ 回收资源成功 | TXID={$tx['txID']}");
            return $this->broadcastTransaction($tx);
        } else {
            Logger::error("❌ TronApi#unDelegateResource 失败 | 响应: {$content}");
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
        Logger::info("🚀 广播交易 => " . json_encode($tx));
        $res = $this->wallet->post("/wallet/broadcasttransaction", $tx, $this->service->getCacheApiKeys());
        $content = $res->getBody()->getContents();
        Logger::info("📡 TronApi#broadcasttransaction => {$content}");
        $result = json_decode($content, true);
        if (isset($result['result']) && $result['result'] === true) {
            Logger::info("✅ 广播成功 | TXID={$result['txid']}");
            return $result['txid'];
        } else {
            Logger::error("❌ 广播失败 | 响应: {$content}");
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
        Logger::debug("🔍 查询资源 | 地址: {$address}");
        $resp = $this->wallet->post('/wallet/getaccountresource', [
            'address' => $address,
            'visible' => true,
        ], $this->service->getCacheApiKeys());

        if ($resp->getStatusCode() == 200) {
            $result = json_decode($resp->getBody()->getContents());
            Logger::info("📥 资源返回 => " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            return AccountResource::of($result);
        } else {
            $content = $resp->getBody()->getContents();
            Logger::error("❌ 查询资源失败 | {$content}");
            throw new \Exception($content);
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
            $price = $data->totalEnergyWeight / $data->totalEnergyLimit; //1个单位资源的价格
            return $price;
        }
        if ($resource === 'BANDWIDTH' && $data->totalNet > 0) {
            $price = $data->totalNetWeight / $data->totalNetLimit; //1个单位资源的价格
            return $price;
        }
        throw new \Exception('不支持的来源类型：' . $resource);
    }

    private function lockGetTodayTotal($address, \Closure $handle)
    {
        // 尝试获取锁
        if ($this->lock->acquire()) {
            try {
                Logger::info("成功获取到锁");
                $result = $handle($address);
                return $result;
            } finally {
                // 执行完操作后释放锁
                $this->lock->release();
                Logger::info("锁已释放");
            }
        } else {
            // 如果获取锁失败，处理无法获取锁的逻辑
            Logger::info("未能获取到锁，稍后再试");
        }
        return null;
    }

    public function getTodayTotal($address, $al = null)
    {
        return $this->lockGetTodayTotal($address, function () use ($address) {
            $stats = [
                'totalPay' => 0,
                'totalPayCount' => 0,
                'totalIncome' => 0,
                'totalIncomeCount' => 0,
                'totalProfit' => 0,
                'lastTransaction' => null,
            ];
            try {
                $td = date('Ymd');
                $cacheKey = "{$this->cachePrefix}tron:trans:$td:today:{$address}";
                $startTimeKey = "{$this->cachePrefix}tron:trans:$td:start:{$address}";
                $startTime = $this->redis->get($startTimeKey);
                $expire = strtotime('tomorrow') - time();
                if (!$startTime) {
                    $startTime = strtotime(date('Y-m-d 00:00:00')) * 1000;
                    $this->redis->setex($startTimeKey, $expire, $startTime);
                }

                $limit = 100;
                while ($response = $this->getTransactions($address, $startTime, $limit)) {
                    $data = $response->data;
                    foreach ($data as $transfer) {
                        $this->redis->setex($startTimeKey, $expire, $transfer->block_timestamp);
                        $startTime = $transfer->block_timestamp;
                        $tx = $this->redis->hGet($cacheKey, $transfer->transaction_id);
                        if (!$tx) {
                            $cacheNotExists = !$this->redis->exists($cacheKey);
                            $this->redis->hSet($cacheKey, $transfer->transaction_id, json_encode($transfer));
                            if ($cacheNotExists) {
                                $this->redis->expire($cacheKey, $expire);
                            }
                        }
                    }
                    if (count($data) < $limit) {
                        Logger::debug("结束遍历");
                        break;
                    }
                    sleep(1);
                }


                $txs = $this->redis->hGetAll($cacheKey);
                $count = 0;
                $latestTran = null;
                foreach ($txs as $txid => $txc) {
                    $transfer = json_decode($txc);
                    $amount = $transfer->value / 1_000_000;
                    if ($transfer->to == $address) {
                        $stats['totalIncome'] += $amount;
                        $stats['totalIncomeCount']++;
                    } else {
                        $stats['totalPay'] += $amount;
                        $stats['totalPayCount']++;
                    }
                    if (!$latestTran) $latestTran = $transfer;
                    if ($transfer->block_timestamp > $latestTran->block_timestamp) {
                        $latestTran = $transfer;
                    }
                    $count++;
                }

                // 保存最新交易
                if ($count) {
                    $stats['lastTransaction'] = $latestTran;
                    $stats['totalProfit'] = $stats['totalIncome'] - $stats['totalPay'];
                    $stats['lastTime'] = date('Y-m-d H:i:s', intval($latestTran->block_timestamp / 1000));
                }
            } catch (\Exception $e) {
                Logger::error("统计失败：{$e->getMessage()} {$e->getTraceAsString()}");
            }
            return $stats;
        });
    }

    public function getTransactions($address, $startTime, $limit = 200)
    {
        $req = new TransactionRequest();
        $req->limit = $limit;
        $req->min_timestamp = (int)$startTime;
        $req->order_by = 'block_timestamp,asc';

        return $this->getTransaction($address, $req);
    }

    public function getTransaction($address, TransactionRequest $req)
    {
        $query = $req->getSdkResult();
        Logger::info("参数: " . json_encode($query));

        try {
// 'https://api.trongrid.io/v1/accounts/'.trim($text).'/transactions/trc20?limit=15&contract_address=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'
            $uri = '/v1/accounts/' . trim($address) . '/transactions/trc20';
            Logger::info("查询交易记录：$uri " . json_encode($query));
            $response = $this->tronGrid->get($uri, $query, $this->service->getCacheApiKeys());
            if ($response->getStatusCode() == 200) {
                $body = $response->getBody()->getContents();
                return json_decode($body);
            }

            return null;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return null;
        }
    }

    function toAddressFormat(string $address): string
    {
        // 如果是 Base58 地址（T 开头的 Tron 地址），需要转 Hex
        if ($address[0] === 'T') {
            $decoded = $this->base58checkDecode($address);
            // Tron 地址前缀 0x41 占 1 字节，取后 20 字节
            $hex = substr(bin2hex($decoded), 2);
        } else {
            // 认为是 Hex 地址，去掉可能的 0x
            $hex = strtolower($address);
            if (substr($hex, 0, 2) === '0x') {
                $hex = substr($hex, 2);
            }
        }

        // 校验是否为 40 长度 hex
        if (!preg_match('/^[0-9a-f]{40}$/', $hex)) {
            throw new \Exception("Invalid address format: {$address}");
        }

        // 补足 64 位（ABI 编码要求）
        return str_pad($hex, 64, '0', STR_PAD_LEFT);
    }

    /**
     * Base58Check 解码 (Tron 地址用的)
     */
    function base58checkDecode(string $input): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base58 = array_flip(str_split($alphabet));
        $num = gmp_init(0);
        foreach (str_split($input) as $char) {
            if (!isset($base58[$char])) {
                throw new \Exception("Invalid Base58 character: {$char}");
            }
            $num = gmp_add(gmp_mul($num, 58), $base58[$char]);
        }
        $hex = gmp_strval($num, 16);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }
        $bin = hex2bin($hex);

        // 前缀 41 + 20 字节地址 + 4 字节校验和
        return substr($bin, 0, -4);
    }

    public function usdtBalance(string $address): float
    {
        $balance = 0;
        try {
            $params = [
                'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                'function_selector' => 'balanceOf(address)',
                'parameter' => $this->toAddressFormat($address),
                'owner_address' => $address,
                "visible" => true,
            ];
            Logger::debug("params => " . json_encode($params));

            $res = $this->wallet->post('/wallet/triggersmartcontract', $params, $this->service->getCacheApiKeys());
            $contents = $res->getBody()->getContents();
            Logger::debug("📥 查询usdt余额返回 => " . $contents);
            $json = json_decode($contents, true);
            if (!empty($json['constant_result'])) {
                $balance = hexdec($json['constant_result'][0]);
            }
        } catch (\Throwable $e) {
            Logger::error("查询余额失败：{$e->getMessage()}");
        }
        return $balance;
    }

    public function getAccount(string $address): ?Account
    {
        try {
            $res = $this->wallet->post('getaccount', [
                'address' => $address,
                'visible' => true
            ], $this->service->getCacheApiKeys());
            $arr = json_decode($res->getBody()->getContents(), true);
            return new Account($arr);
        } catch (\Throwable $e) {
            Logger::error("查询余额失败：{$e->getMessage()}");
        }
        return null;
    }

    public function trxBalance(string $address): float
    {
        $trxBalance = 0;
        try {
            $res = $this->walletSolidity->post('walletsolidity/getaccount', [
                'address' => $address,
                'visible' => true
            ], $this->service->getCacheApiKeys());
            $json = json_decode($res->getBody()->getContents(), true);
            if (isset($json['balance'])) {
                $trxBalance = $json['balance'];
            }
        } catch (\Throwable $e) {
            Logger::error("查询余额失败：{$e->getMessage()}");
        }
        return $trxBalance;
    }
}
