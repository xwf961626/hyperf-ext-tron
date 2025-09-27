<?php

namespace William\HyperfExtTron\Tron;

use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\SimpleCache\CacheInterface;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;

class TronService
{
    use ApiKeyTrait;
    const string TYPE_SCAN_KEY = 'scan';
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

    public function getApiList(?RequestInterface $request = null): \Hyperf\Collection\Collection|array
    {
        $cache = $this->cache->get(self::API_LIST_CACHE_KEY);
        if ($cache) {
            return json_decode($cache, true);
        } else {
            $results = Api::orderBy('weight', 'desc')->where('status', 'active')->get()->toArray();
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
        if ($aliasName = $request->input('alias_name')) {
            $updates['alias_name'] = $aliasName;
        }
        return $api->update($updates);
    }

    public function deleteApiCache()
    {
        $this->cache->delete(self::API_LIST_CACHE_KEY);
    }

}