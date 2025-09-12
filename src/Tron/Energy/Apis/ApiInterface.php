<?php

namespace William\HyperfExtTron\Tron\Energy\Apis;

use Hyperf\HttpServer\Contract\RequestInterface;
use William\HyperfExtTron\Model\ResourceDelegate;

interface ApiInterface
{
    public function init($configs);

    public function validate($params);

    /**
     * 代理资源
     *
     * @param string $toAddress
     * @param int $power
     * @param mixed $time
     * @param int $userId
     * @return ResourceDelegate
     */
    public function delegate(string $toAddress, int $power, mixed $time, int $userId = 0): ResourceDelegate;

    public function recycle(string $toAddress): mixed;

    public function getEnergyLogClass();

    public function name(): string;

    public function getApiKey(): string;

    public function getBaseUrl(): string;

    public function getBalance(): float;

    public function setModel(mixed $api);

    public function callbackUrl(): mixed;
    public function getCallbackHandler(RequestInterface $request): callable|null;
}
