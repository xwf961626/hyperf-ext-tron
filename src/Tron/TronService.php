<?php

namespace William\HyperfExtTron\Tron;

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Router;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use William\HyperfExtTron\Controller\UserResourceAddressController;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;
use function Hyperf\Config\config;
use function Hyperf\Support\make;

class TronService
{
    const string TYPE_NODE_KEY = 'node';
    const string TYPE_SCAN_KEY = 'scan';
    const string API_KEY_CACHE_KEY = 'api_key_set';
    const API_LIST_CACHE_KEY = 'api_list';

    public function __construct(private CacheInterface $cache, protected EnergyApiFactory $apiFactory)
    {
    }

    public function addTronApiKey(RequestInterface $request): bool
    {
        $type = $request->input('type', self::TYPE_NODE_KEY);
        if (!$apiKeys = $request->input('api_keys')) {
            throw new \Exception("apiKeys必填");
        }
        $keys = explode("\n", $apiKeys);
        $data = [];
        foreach ($keys as $key) {
            $data[] = array_merge(['type' => $type], ['api_key' => $key]);
        }
        $cacheKey = $type . self::API_KEY_CACHE_KEY;
        $this->cache->clear($cacheKey);
        return TronApiKey::insert($data);
    }



    public function getTronApiKeyList(mixed $request): \Hyperf\Contract\LengthAwarePaginatorInterface
    {
        $query = TronApiKey::query();
        if ($keywords = $request->query('keywords')) {
            $query = $query->where('api_key', 'like', '%' . $keywords . '%');
        }
        if ($type = $request->query('type')) {
            $query = $query->where('type', $type);
        }
        return $query->paginate((int)$request->query('limit', 15));
    }

    public function getCacheApiKeys(string $type = self::TYPE_NODE_KEY): array
    {
        $cacheKey = $type . self::API_KEY_CACHE_KEY;

        try {
            // 尝试从缓存获取
            $keys = $this->cache->get($cacheKey, []);

            if (empty($keys)) {
                // 缓存为空，从数据库拉取
                $keys = TronApiKey::where('type', $type)
                    ->where('status', 'active')
                    ->pluck('api_key')
                    ->toArray();

                // 存入缓存，设置过期时间 1 小时
                $this->cache->set($cacheKey, $keys, 3600);
            }

            return $keys;
        } catch (\Throwable $e) {
            Logger::error("查询APIKEY失败：{$e->getMessage()} {$e->getTraceAsString()}");
            return [];
        }
    }

    public function getApiList(?RequestInterface $request = null): \Hyperf\Collection\Collection|array
    {
        $cache = $this->cache->get(self::API_LIST_CACHE_KEY);
        if ($cache) {
            return json_decode($cache, true);
        } else {
            $results = Api::orderBy('weight', 'desc')->get()->toArray();
            $this->cache->set(self::API_LIST_CACHE_KEY, json_encode($results));
            return $results;
        }
    }

    public function editApi(RequestInterface $request)
    {
        $id = $request->input('id');
        $api = Api::find($id);
        if (!$api) throw new \Exception("api未找到");
        $updates = [];
        if ($baseUrl = $request->input('base_url')) {
            $updates['url'] = $baseUrl;
        }
        if ($callback_url = $request->input('callback_url')) {
            $updates['callback_url'] = $callback_url;
        }
        if ($status = $request->input('status')) {
            $updates['status'] = $status;
        }
        if ($weight = $request->input('weight')) {
            $updates['weight'] = $weight;
        }
        if ($apiKey = $request->input('api_key')) {
            $updates['api_key'] = $apiKey;
        }
        if ($apiSecret = $request->input('api_secret')) {
            $updates['api_secret'] = $apiSecret;
        }
        $this->cache->delete(self::API_LIST_CACHE_KEY);
        return $api->update($updates);
    }

}