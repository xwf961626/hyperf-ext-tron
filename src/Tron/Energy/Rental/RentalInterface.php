<?php

namespace William\HyperfExtTron\Tron\Energy\Rental;


use William\HyperfExtTronModel\User;
use Hyperf\HttpServer\Contract\RequestInterface;

interface RentalInterface
{
    public function init(array $configs);

    /**
     * @throws \Exception
     */
    public function createOrder(RequestInterface $request, User $user, array $options): mixed;

    /**
     * @throws \Exception
     */
    public function rent(mixed $order, ?User $user = null): mixed;
}
