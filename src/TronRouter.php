<?php

namespace William\HyperfExtTron;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpServer\Router\Router;
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


    }

    public static function addWebhookRoute(): void
    {
        Router::addRoute(['GET', 'POST'], '/api/callback/{name}', function (string $name, RequestInterface $request, ResponseInterface $response) {
            return make(EnergyApiFactory::class)->handleApiCallback($name, $request, $response);
        });
    }
}