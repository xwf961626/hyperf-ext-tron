<?php

namespace William\HyperfExtTron\Tron\Energy\Rental;


use Hyperf\HttpServer\Contract\RequestInterface;

interface RentalInterface
{
    public function init(array $configs);

    /**
     * @throws \Exception
     */
    public function createOrder(RequestInterface $request, int $userId, array $options): mixed;

    /**
     * @throws \Exception
     */
    public function rent(mixed $order, int $userId = 0): mixed;
}
