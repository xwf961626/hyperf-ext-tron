<?php

use Hyperf\HttpServer\Router\Router;
use William\HyperfExtTron\Tron\TronService;

Router::addGroup('/admin', function () {
    TronService::registerAdminRoutes();
}, ['middleware' => [Phper666\JWTAuth\Middleware\JWTAuthDefaultSceneMiddleware::class]]);