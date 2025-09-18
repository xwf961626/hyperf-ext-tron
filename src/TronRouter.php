<?php

namespace William\HyperfExtTron;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Router\Router;
use William\HyperfExtTron\Controller\LimitAddressController;
use William\HyperfExtTron\Controller\UserResourceAddressController;
use William\HyperfExtTron\Tron\ApiController;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;
use function Hyperf\Support\make;

class TronRouter
{
    public static function addAdminRoutes(): void
    {
        Router::get('tron_api_keys', [ApiController::class, 'getTronApiKeyList']);
        Router::post('tron_api_keys', [ApiController::class, 'addApiKey']);
        Router::get('apis', [ApiController::class, 'getApiList']);
        Router::put('apis', [ApiController::class, 'editApi']);

        // 能量地址池管理
        Router::post('energy/address', [UserResourceAddressController::class, 'getAddress']);
        Router::post('energy/add', [UserResourceAddressController::class, 'addAddress']);
        Router::post('energy/open', [UserResourceAddressController::class, 'switchOpen']);

        Router::get('delegate/logs', [LimitAddressController::class, 'getLogs']);
        Router::put('delegate/logs/{id}/retry_recycle', [LimitAddressController::class, 'retryRecycle']);
    }

    public static function addWebhookRoute(): void
    {
        Router::addRoute(['GET', 'POST'], '/api/callback/{name}', function (string $name, RequestInterface $request, ResponseInterface $response) {
            return make(EnergyApiFactory::class)->handleApiCallback($name, $request, $response);
        });
    }
    
    public static function addLimitAddressRoutes($controller): void
    {
        Router::get('limit/addresses', [$controller, 'addressList']);
        Router::post('limit/addresses', [$controller, 'addAddress']);
        Router::put('limit/addresses/{id}', [$controller, 'editAddress']);
        Router::delete('limit/addresses/{id}', [$controller, 'deleteAddress']);
    }
}