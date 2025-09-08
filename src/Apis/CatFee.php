<?php

namespace William\HyperfExtTron\Apis;

use Carbon\Carbon;
use Exception;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\EnergyLog;
use William\HyperfExtTron\Tron\Energy\Apis\AbstractApi;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;

#[EnergyApi(name: CatFee::API_NAME)]
class CatFee extends AbstractApi
{

    const API_NAME = 'cateFee';
    protected string $apiKey = '';
    protected string $apiSecret = '';
    protected string $baseUrl = 'https://api.catfee.io';

    public function init($configs)
    {
        $this->apiKey = $configs['apiKey'];
        $this->apiSecret = $configs['apiSecret'];
        $this->baseUrl = $configs['baseUrl'];
    }

    public function validate($params)
    {
        // TODO: Implement validate() method.
    }

    // 生成当前的时间戳（ISO 8601格式）
    function generateTimestamp()
    {
        return gmdate("Y-m-d\TH:i:s.000\Z");
    }

// 构建请求路径，包括查询参数
    function buildRequestPath($path, $queryParams)
    {
        if (empty($queryParams)) {
            return $path;
        }
        $queryString = http_build_query($queryParams);
        return $path . '?' . $queryString;
    }

// 使用 HMAC-SHA256 算法生成签名
    function generateSignature($timestamp, $method, $requestPath)
    {
        $signString = $timestamp . $method . $requestPath;
        return base64_encode(hash_hmac('sha256', $signString, $this->apiSecret, true));
    }


    public function send(string $toAddress, int $power, mixed $time, int $userId = 0): EnergyLog
    {
        $powerCount = $power;
        $lockDuration = 0;
        Logger::debug("EnergyApi#EnergyPool 代理资源参数：$toAddress => power = $powerCount, time=$time, user_id= $userId");
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

        $orderLog = new EnergyLog();
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

        $method = "POST";  // 可以修改为 "GET", "PUT", "DELETE" 等方法
        $path = "/v1/order";

        // 示例：创建订单
        $queryParams = [
            "quantity" => $power,
            "receiver" => $toAddress,
            "duration" => $time,
        ];

        // 生成请求头
        $timestamp = $this->generateTimestamp();
        $requestPath = $this->buildRequestPath($path, $queryParams);
        $signature = $this->generateSignature($timestamp, $method, $requestPath);

        // 创建请求 URL
        $url = $this->baseUrl . $requestPath;

        // 发送请求
        try {
            $response = $this->createRequest($url, $method, $timestamp, $signature);
            Logger::debug("CatFee /v1/order Response Code: 200");
            Logger::debug("CatFee /v1/order Response Body: $response");
            $result = json_decode($response, true);
            if ($result['code'] === 0) {
                $data = $result['data'];
                $orderLog->response_text = $response;
                $orderLog->tx_id = $data['delegate_hash'];
                $orderLog->status = 1;
                $orderLog->save();
            } else {
                throw new Exception("CatFee /v1/order fail: $response");
            }
        } catch (Exception $e) {
            Logger::error("CatFee /v1/order Error: " . $e->getMessage());
            $orderLog->fail_reason = $e->getMessage();
            $orderLog->status = -1;
            $orderLog->save();
        }
        return $orderLog;
    }

    // 创建 HTTP 请求
    function createRequest($url, $method, $timestamp, $signature)
    {
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
            throw new Exception("cURL error: " . curl_error($ch));
        }

        curl_close($ch);

        return $response;
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
}