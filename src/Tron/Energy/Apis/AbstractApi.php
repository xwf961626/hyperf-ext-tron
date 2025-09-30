<?php

namespace William\HyperfExtTron\Tron\Energy\Apis;

use Carbon\Carbon;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use William\HyperfExtTron\Helper\GuzzleClient;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Tron\Energy\Utils;

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

    public function __construct(protected ResponseInterface $response)
    {
    }

    public function delegate(string $toAddress, int $power, mixed $time, int $userId = 0, string $resource = 'ENERGY'): ResourceDelegate
    {
        if (!$power || !$time) {
            Logger::error('参数错误: power, time');
            throw new \Exception('能量数、时长未定义');
        }
        [$time, $lockDuration] = $this->parseTime($time);
        $delegate = new ResourceDelegate();
        $delegate->quantity = $power;
        $delegate->resource = $resource;
        $delegate->time = $time;
        $delegate->address = $toAddress;
        $delegate->user_id = $userId;
        $delegate->api = $this->name();
        $delegate->lock_duration = $lockDuration;
        if ($lockDuration > 0) {
            $delegate->expired_dt = Carbon::now()->addMinutes($lockDuration);
        }
        $delegate->save();
        try {

            $hash = $this->delegateHandler($delegate);

            $delegate->tx_id = $hash;
            $delegate->status = 1;
            $delegate->save();
            try {
                $this->afterDelegateSuccess();
            } catch (\Exception $e) {
                Logger::error("代理成功后处理失败：{$e->getMessage()} {$e->getTraceAsString()}");
            }

            $this->updateApiBalance();

        } catch (\Exception $e) {
            Logger::error(get_class($this) . " delegate err: " . $e->getMessage() . $e->getTraceAsString());
            $delegate->fail_reason = $e->getMessage();
            $delegate->status = -1;
            $delegate->save();
        }
        return $delegate;
    }

    public function responseJson($data, $code = 200): \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface
    {
        return $this->response
            ->withStatus($code) // 设置状态码
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new \Hyperf\HttpMessage\Stream\SwooleStream(json_encode($data)));
    }

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

    protected function _get($path, $params = [], $headers = [])
    {
        $client = GuzzleClient::coroutineClient(['base_uri' => $this->getBaseUrl()]);
        return $client->get($path, [
            'query' => $params,
            'headers' => $headers,
            'http_errors' => false
        ]);
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
        $this->apiKey = $this->model->api_key ?: $configs['apiKey'] ?? "";
        $this->apiSecret = $this->model->api_secret ?: $configs['apiSecret'] ?? "";
        $this->baseUrl = $configs['baseUrl'];
    }

    protected function afterDelegateSuccess(): void
    {

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

    abstract function parseTime(mixed $time);

    abstract function delegateHandler(ResourceDelegate $delegate): string;

    protected function updateApiBalance(): void
    {
        try {
            Logger::debug("{$this->name()} 更新余额...");
            $balance = $this->getBalance();
            $this->model->balance = $balance;
            $this->model->save();
            Logger::debug("{$this->name()} 更新余额成功：{$balance}");
        } catch (\Exception $e) {
            Logger::error("更新API余额失败：{$e->getMessage()} {$e->getTraceAsString()}");
        }
    }
}
