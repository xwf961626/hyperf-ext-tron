<?php

namespace William\HyperfExtTron\Tron;

use Hyperf\Cache\Cache;
use William\HyperfExtTron\Helper\Logger;
use function Hyperf\Config\config;
use function Hyperf\Support\make;

trait ApiKeyTrait
{
    const string TYPE_NODE_KEY = 'node';
    const string API_KEY_CACHE_KEY = 'api_key_set';

    public function getCacheApiKeys(string $type = self::TYPE_NODE_KEY): array
    {
        $cache = make(Cache::class);
//        Logger::debug("是否使用api-key：".config('tron.endpoint.no_api_key'));
        if (config('tron.endpoint.no_api_key')) {
            return [];
        }
        $cacheKey = $type . self::API_KEY_CACHE_KEY;

        try {
            // 尝试从缓存获取
            $keys = $cache->get($cacheKey, []);

            if (empty($keys)) {
                // 缓存为空，从数据库拉取
                $keys = TronApiKey::where('type', $type)
                    ->where('status', 'active')
                    ->pluck('api_key')
                    ->toArray();

                // 存入缓存，设置过期时间 1 小时
                $cache->set($cacheKey, $keys, 3600);
            }

            return $keys;
        } catch (\Throwable $e) {
            Logger::error("查询APIKEY失败：{$e->getMessage()} {$e->getTraceAsString()}");
            return [];
        }
    }
}