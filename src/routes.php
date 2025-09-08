<?php

use Hyperf\HttpServer\Router\Router;

Router::addGroup('/admin/tron/', function () {
    Router::get('api_keys', 'William\HyperfExtTron\Tron\AdminController@getTronApiKeyList');
    Router::post('api_keys', 'William\HyperfExtTron\Tron\AdminController@addApiKey');
}, ['middleware' => [Phper666\JWTAuth\Middleware\JWTAuthDefaultSceneMiddleware::class]]);