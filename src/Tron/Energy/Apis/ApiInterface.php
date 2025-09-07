<?php

namespace William\HyperfExtTron\Tron\Energy\Apis;

use GuzzleHttp\Exception\GuzzleException;

interface ApiInterface
{
    public function init($configs);

    public function validate($params);

    /**
     * 购买能量
     *
     * @param string $toAddress
     * @param int $power
     * @param mixed $time
     * @param int $userId
     * @throws GuzzleException
     */
    public function send(string $toAddress, int $power, mixed $time, int $userId = 0): mixed;

    public function recycle(string $toAddress): mixed;

    public function getEnergyLogClass();

    public function name(): string;
}
