<?php

namespace William\HyperfExtTron\Tron;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Router;

class TronService
{
    const string TYPE_NODE_KEY = 'node';
    const string TYPE_SCAN_KEY = 'scan';
    const string API_KEY_CACHE_KEY = 'api_key_set';

    public function __construct()
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

}