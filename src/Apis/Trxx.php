<?php

namespace William\HyperfExtTron\Apis;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\EnergyLog;
use William\HyperfExtTron\Tron\Energy\Apis\AbstractApi;
use William\HyperfExtTron\Tron\Energy\Utils;
use function Hyperf\Config\config;

class Trxx extends AbstractApi
{
    const API_NAME = 'trxx';
    protected string $baseUrl = 'https://trxx.io';


    public function init($configs)
    {
        parent::init($configs);
        $this->callbackUrl = $this->model->callback_url ?? $configs['callback_url'];
    }

    /**
     * @param string $toAddress
     * @param int $power
     * @param mixed $time
     * @param int $userId
     * @return EnergyLog
     */
    public function send(string $toAddress, int $power, mixed $time, int $userId = 0): EnergyLog
    {
        $lockDuration = 0;
        Logger::debug("EnergyApi#Trxx 代理资源参数：$toAddress => power = $power, time=$time, user_id= $userId");
        if (str_contains($time, 'min')) {
            $lockDuration = 60;
            $time = '1H';
        }

        if (str_contains($time, 'day') || ctype_digit($time)) {
            $lockDuration = intval($time) * 60 * 24;
            $time = intval($time) . 'D';
        }

        if (str_contains($time, 'h')) {
            $lockDuration = intval($time) * 60;
            $time = intval($time) . 'H';
        }

        //租赁周期,1H/1D/3D/30D
        //H表示1小时，D表示天
        $orderLog = $this->createOrder($power, $time, $toAddress, $userId, $lockDuration);

        try {
            $params = [
                'energy_amount' => $power,
                'period' => $time,
                'receive_address' => $toAddress,
                'out_trade_no' => $orderLog->id,
            ];
            if ($callbackUrl = $this->callbackUrl()) {
                $params['callback_url'] = $callbackUrl;
            }
            $resp = $this->post('/api/v1/frontend/order', $params);
            if ($resp->getStatusCode() != 200) {
                throw new \Exception($resp->getBody()->getContents());
            }
            $result = json_decode($resp->getBody()->getContents(), true);
            if ($result['errno']) {
                throw new \Exception($result['message']);
            }
            $orderLog->response_text = json_encode($result); // 内部订单号
            $orderLog->save();
            $this->model->balance = round($result['balance'] / 1_000_000, 6);
            $this->model->save();

            if (!$this->callbackUrl()) {
                Logger::debug("未设置回调地址，主动查询订单结果");
                $hashes = $this->orderQuery($result['serial']);
                if (!empty($hashes)) {
                    Logger::debug("主动查询订单结果成功：" . json_encode($hashes));
                    $orderLog->tx_id = implode(',', $hashes);
                    $orderLog->save();
                } else {
                    Logger::debug("主动查询订单结果失败");
                    throw new Exception("查询失败");
                }
            }
        } catch (\Exception $e) {
            Logger::error("TRXX 发送能量失败：{$e->getMessage()}");
            $orderLog->status = -1;
            $orderLog->fail_reason = $e->getMessage();
            $orderLog->save();
        }
        return $orderLog;
    }


    public function name(): string
    {
        return self::API_NAME;
    }

    /**
     * 主动查询订单
     *
     * @param $tradeNo
     * @return array
     */
    public function orderQuery($tradeNo): array
    {
        $max = 100;
        $attempts = 0;
        while ($attempts < $max) {
            $attempts++;
            $logPrefix = "Trxx#orderQuery 第{$attempts}/{$max}尝试查询订单{$tradeNo}";
            Logger::debug("{$logPrefix}...");
            try {
                $URL = "{$this->baseUrl}/api/v1/frontend/order/query";
                $params = array('serial' => $tradeNo);
                $context = stream_context_create(array(
                    'http' => array(
                        'method' => 'GET',
                        'header' => "API-KEY: " . $this->apiKey,
                    )
                ));
                $response = file_get_contents($URL . "?" . http_build_query($params), false, $context);
                Logger::debug("{$logPrefix} <= response:{$response}");
                $result = json_decode($response, true);


                [$continue, $hashes] = $this->handleOrderResult($result);
                if ($continue) {
                    throw new \Exception("订单尚未完成，继续查询");
                } else {
                    return $hashes;
                }
            } catch (\Exception $e) {
                Logger::error("{$logPrefix} 失败 {$e->getMessage()}");
                sleep(1);
            }
        }
        return [];
    }

    /**
     * @param $result
     * @return array [是否重试，哈希数组]
     * @throws \Exception
     */
    protected function handleOrderResult($result): array
    {
        if ($result['errno']) {
            return [true, []];
        }
        /** @var int $status 订单状态
         * (0, '超时关闭'),
         * (10, '等待支付'),
         * (20, '已支付'),
         * (30, '委托准备中'),
         * (31, '部分委托'),
         * (32, '异常重试中'),
         * (40, '正常完成'),
         * (41, '退款终止'),
         * (43, '异常终止'), */
        $status = $result['status'];
        switch ($status) {
            case 0:
            case 41:
            case 43:
                return [false, []];
            case 40:
                $hashes = [];
                if (!empty($result['details'])) {
                    foreach ($result['details'] as $detail) {
                        $hashes[] = $detail['delegate_hash'];
                    }
                }
                return [false, $hashes];
            default:
                return [true, []];
        }
    }

    public function getCallbackHandler(RequestInterface $request): callable|null
    {
        return function ($result) use ($request): mixed {
            $timestamp = $request->getHeader('TIMESTAMP')[0] ?? null;
            $signature = $request->getHeader('SIGNATURE')[0] ?? null;
            if (!$timestamp || !$signature) {
                return $this->responseJson([
                    'error' => 'TIMESTAMP or SIGNATURE not found'
                ], 403);
            }
            $body = $request->all();
            ksort($body);
            $json_data = json_encode($body, JSON_UNESCAPED_SLASHES);

            $message = $timestamp . '&' . $json_data;
            $expected_signature = hash_hmac('sha256', $message, $this->apiSecret);

            if (!hash_equals($signature, $expected_signature)) {
                return $this->responseJson([
                    'error' => 'Signature is invalid.'
                ], 403);
            }

            Logger::debug("Trxx 订单结果回调处理：" . json_encode($result));
            /** @var EnergyLog $orderLog */
            $orderLog = EnergyLog::query()->where('id', $result['order_no'])->first();
            if (!$orderLog) {
                Logger::error("订单{$result['order_no']}不存在");
                return $this->responseJson([
                    'error' => "订单{$result['order_no']}不存在"
                ], 404);
            }
            [$continue, $hashes] = $this->handleOrderResult($result);
            if (!$continue && !empty($hashes)) {
                $orderLog->tx_id = implode(',', $hashes);
                $orderLog->save();
            }
            return $this->responseJson([
                'success' => true
            ]);
        };
    }

    private function post(string $path, array $data)
    {
        $timestamp = time();
        $json_data = json_encode($data, JSON_UNESCAPED_SLASHES);
        $message = $timestamp . '&' . $json_data;

        $signature = hash_hmac('sha256', $message, $this->apiSecret);
        $headers = [
            "Content-Type: application/json",
            "API-KEY: $this->apiKey",
            "TIMESTAMP: $timestamp",
            "SIGNATURE: $signature"
        ];
        return $this->_post($path, $data, $headers);
    }
}