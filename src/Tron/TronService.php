<?php

namespace William\HyperfExtTron\Tron;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Router;
use Psr\SimpleCache\CacheInterface;
use William\HyperfExtTron\Helper\Logger;

class TronService
{
    const string TYPE_NODE_KEY = 'node';
    const string TYPE_SCAN_KEY = 'scan';
    const string API_KEY_CACHE_KEY = 'api_key_set';

    public function __construct(private CacheInterface $cache)
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

    public static function registerAdminRoutes(): void
    {
        Router::get('/tron_api_keys', 'William\HyperfExtTron\Tron\AdminController@getTronApiKeyList');
        Router::post('/tron_api_keys', 'William\HyperfExtTron\Tron\AdminController@addApiKey');
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

}