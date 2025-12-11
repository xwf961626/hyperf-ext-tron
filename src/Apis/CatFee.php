<?php

namespace William\HyperfExtTron\Apis;

use Exception;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Tron\Energy\Apis\AbstractApi;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;
use function Hyperf\Config\config;

#[EnergyApi(name: CatFee::API_NAME)]
class CatFee extends AbstractApi
{
    const API_NAME = 'cateFee';
    protected string $baseUrl = 'https://api.catfee.io';
    private mixed $delegateResponseData;
    private ResourceDelegate $order;


    public function init($configs)
    {
        $configs = config('tron.apis')[self::API_NAME];
        $this->apiKey = $this->model->api_key ?? $configs['apiKey'];
        $this->apiSecret = $this->model->api_secret ?? $configs['apiSecret'];
        $this->baseUrl = $configs['baseUrl'];
    }

    public function validate($params)
    {
        // TODO: Implement validate() method.
    }

    // 生成当前的时间戳（ISO 8601格式）
    private function generateTimestamp(): string
    {
        return gmdate("Y-m-d\TH:i:s.000\Z");
    }

// 构建请求路径，包括查询参数
    private function buildRequestPath($path, $queryParams): string
    {
        if (empty($queryParams)) {
            return $path;
        }
        $queryString = http_build_query($queryParams);
        return $path . '?' . $queryString;
    }

// 使用 HMAC-SHA256 算法生成签名
    private function generateSignature($timestamp, $method, $requestPath): string
    {
        $signString = $timestamp . $method . $requestPath;
        return base64_encode(hash_hmac('sha256', $signString, $this->apiSecret, true));
    }

    public function parseTime(mixed $time): array
    {
        $lockDuration = 0;
        if (str_contains($time, 'min')) {
            $lockDuration = intval($time);
        }

        if (str_contains($time, 'day')) {
            $lockDuration = intval($time) * 60 * 24;
        }

        if (str_contains($time, 'h')) {
            $lockDuration = intval($time) * 60;
        }

        if (ctype_digit($time)) {
            $time = $time . 'day';
            $lockDuration = (int)$time * 60 * 24;
        }
        return [$time, $lockDuration];
    }

    public function delegateHandler(ResourceDelegate $delegate): string
    {
        Logger::debug("EnergyApi#CatFee 代理资源参数：" . json_encode($delegate));

        $path = "/v1/order";

        // 示例：创建订单
        $queryParams = [
            "quantity" => $delegate->quantity,
            "receiver" => $delegate->address,
            "duration" => $delegate->time,
        ];


        $data = $this->post($path, $queryParams);
        Logger::debug("返回参数：" . json_encode($data));
        if (!$data) {
            Logger::error("EnergyApi#CatFee 代理失败");
            throw new Exception("代理失败失败");
        }
        // 成功示例： {"code":0,"data":{"id":"2671ba7b-a323-49e6-b4a4-19adca27ebbf","resource_type":"ENERGY",
        //"billing_type":"API","source_type":"API","pay_timestamp":1757410028796,"receiver":"THH5zsXVQ8dSy7FNg1putmh6cR4Eeu5kix",
        //"pay_amount_sun":1105000,"quantity":65000,"duration":60,"status":"PAYMENT_SUCCESS","activate_status":"ALREADY_ACTIVATED",
        //"confirm_status":"UNCONFIRMED","balance":84595000}}
        $this->order = $delegate;
        if ($data['status'] != 'DELEGATE_SUCCESS') {
            $data = $this->getOrderDetail($data['id']);
        }
        if (!$data) {
            Logger::error("EnergyApi#CatFee 代理失败或订单查询失败");
            throw new Exception("代理失败或订单查询失败");
        }
        $this->delegateResponseData = $data;
        return $data['delegate_hash'];
    }

    function getOrderDetail($id): ?array
    {
        try {
            Logger::debug("EnergyApi#CatFee 查询订单：$id");
            $path = "/v1/order/{$id}";
            $data = $this->get($path);
            Logger::debug("查询结果：" . json_encode($data));
            if (!$data) {
                throw new Exception("EnergyApi#CatFee $path 接口返回空异常, 重试查询");
            }
            if ($data['status'] != 'DELEGATE_SUCCESS' && $data['status'] != 'PAYMENT_SUCCESS') {
                return null;
            }
            if ($data['status'] == 'DELEGATE_SUCCESS') {
                return $data;
            }
            sleep(3);
            return $this->getOrderDetail($id);
        } catch (Exception $e) {
            sleep(3);
            return $this->getOrderDetail($id);
        }
    }

    // 创建 HTTP 请求

    /**
     * @throws Exception
     */
    function post($path, $queryParams = [])
    {
        Logger::debug("发送参数：" . json_encode($queryParams));
        return $this->createRequest($path, 'POST', $queryParams);
    }

    /**
     * @throws Exception
     */
    function get($path, $queryParams = [])
    {
        return $this->createRequest($path, 'GET', $queryParams);
    }

    private function createRequest($path, $method, $queryParams)
    {
        // 生成请求头
        $timestamp = $this->generateTimestamp();

        $requestPath = $this->buildRequestPath($path, $queryParams);

        $signature = $this->generateSignature($timestamp, $method, $requestPath);
        // 创建请求 URL
        $url = $this->baseUrl . $requestPath;

        $headers = [
            "Content-Type: application/json",
            "CF-ACCESS-KEY: " . $this->apiKey,
            "CF-ACCESS-SIGN: " . $signature,
            "CF-ACCESS-TIMESTAMP: " . $timestamp
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        switch (strtoupper($method)) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                throw new Exception("Unsupported HTTP method: $method");
        }

        $response = curl_exec($ch);

        // 检查是否请求成功
        if (curl_errno($ch)) {
            throw new Exception("CatFee cURL error: " . curl_error($ch));
        }

        curl_close($ch);
        Logger::info("CatFee $path Response  $response");
        $result = json_decode($response, true);
        if ($result['code'] === 0) {
            return $result['data'];
        }
        return null;
    }


    public function recycle(string $toAddress): mixed
    {
        return null;
    }

    public function getEnergyLogClass()
    {
        // TODO: Implement getEnergyLogClass() method.
    }

    public function name(): string
    {
        return self::API_NAME;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getBalance(): float
    {
        try {
            $data = $this->get('/v1/account');
            return round($data['balance'] / 1_000_000, 6);
//            return $data['balance_usdt'];
        } catch (Exception $e) {
            Logger::error("CatFee /v1/account fail: " . $e->getMessage());
            return 0;
        }
    }

    protected function afterDelegateSuccess(): void
    {
        $this->model->balance = round($this->delegateResponseData['balance'] / 1_000_000, 6);
        $price = round($this->delegateResponseData['pay_amount_sun'] / $this->delegateResponseData['quantity'], 2);
        $this->model->price = $price;
        $this->model->save();

        $this->order->price = round($this->delegateResponseData['pay_amount_sun'] / 1_000_000, 2);
        $this->order->save();
    }
}