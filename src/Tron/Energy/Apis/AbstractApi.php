<?php

namespace William\HyperfExtTron\Tron\Energy\Apis;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Hyperf\HttpServer\Contract\RequestInterface;
use William\HyperfExtTron\Helper\GuzzleClient;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Model\EnergyLog;
use William\HyperfExtTron\Tron\Energy\Utils;
use function Hyperf\Config\config;

/**
 * @property Api $model
 */
abstract class AbstractApi implements ApiInterface
{
    public ?Api $model;

    protected string $apiKey = '';
    protected string $baseUrl = '';

    protected string $apiSecret = '';

    protected ?string $callbackUrl = null;

    public function callbackUrl(): mixed
    {
        return $this->callbackUrl;
    }

    public function getCallbackHandler(RequestInterface $request): callable|null
    {
        return null;
    }

    public function setModel(mixed $api)
    {
        $this->model = $api;
    }

    protected function _post($path, $params = [], $headers = [])
    {
        $client = GuzzleClient::coroutineClient(['base_uri' => $this->getBaseUrl()]);
        return $client->post($path, [
            'json' => $params,
            'headers' => $headers,
            'http_errors' => false
        ]);
    }

    protected function createOrder($power, $time, $toAddress, $userId, $lockDuration): EnergyLog
    {
        $orderLog = new EnergyLog();
        $orderLog->id = Utils::makeOrderNo();
        $orderLog->power_count = $power;
        $orderLog->time = $time;
        $orderLog->address = $toAddress;
        $orderLog->user_id = $userId;
        $orderLog->energy_policy = $this->name();
        $orderLog->lock_duration = $lockDuration;
        if ($lockDuration > 0) {
            $orderLog->expired_dt = Carbon::now()->addMinutes($lockDuration);
        }
        $orderLog->save();
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function init($configs)
    {
        $this->apiKey = $this->model->api_key ?? $configs['apiKey'];
        $this->apiSecret = $this->model->api_secret ?? $configs['apiSecret'];
        $this->baseUrl = $configs['baseUrl'];
    }

    public function validate($params)
    {

    }

    public function recycle(string $toAddress): mixed
    {
        return null;
    }

    public function getEnergyLogClass()
    {

    }


    public function getBalance(): float
    {
        return 0;
    }
}
