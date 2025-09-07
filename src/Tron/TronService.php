<?php

namespace William\HyperfExtTron\Tron;

use William\HyperfExtTron\Helper\Logger;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Router;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;

class TronService
{
    const TYPE_NODE_KEY = 'node';
    const TYPE_SCAN_KEY = 'scan';
    const API_KEY_CACHE_KEY = 'api_key_set';

    protected Redis $redis;

    public function __construct(RedisFactory $redisFactory)
    {
        $this->redis = $redisFactory->get('default');
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
        $this->redis->del($type . self::API_KEY_CACHE_KEY);
        return TronApiKey::insert($data);
    }

    public function getCacheApiKeys(string $type = self::TYPE_NODE_KEY): array
    {
        try {
            if (!$this->redis->exists($type . self::API_KEY_CACHE_KEY)) {
                $keys = TronApiKey::where('type', $type)->where('status', 'active')->pluck('api_key')->toArray();
                foreach ($keys as $key) {
                    $this->redis->sAdd($type . self::API_KEY_CACHE_KEY, $key);
                }
            }
            return $this->redis->sMembers($type . self::API_KEY_CACHE_KEY);
        } catch (\Exception $e) {
            Logger::error("查询APIKEY失败：{$e->getMessage()} {$e->getTraceAsString()}");
        }
        return [];
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

}