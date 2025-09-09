<?php

namespace William\HyperfExtTron\Apis;

use Carbon\Carbon;
use GuzzleHttp\Client;
use William\HyperfExtTron\Helper\GuzzleClient;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\EnergyLog;
use William\HyperfExtTron\Tron\Energy\Apis\AbstractApi;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;

/**
 * https://trongas.io/pay/interface
 */
#[EnergyApi(name: Trongas::API_NAME)]
class Trongas extends AbstractApi
{
    const API_NAME = 'trongas';
    protected string $apiKey = '';
    protected Client $client;
    protected string $baseUrl;
    protected string $username;

    public function init($configs)
    {
        $this->apiKey = $configs['apiKey'];
        $this->baseUrl = $configs['baseUrl'];
        $this->username = $configs['username'];
        $this->client = GuzzleClient::coroutineClient(['base_uri' => $configs['baseUrl']]);
    }

    public function validate($params)
    {
    }

    public function send(string $toAddress, int $power, mixed $time, int $userId = 0): EnergyLog
    {
        $params = [
            'apiKey' => $this->apiKey,
            'resType' => 'ENERGY',
            'payNums' => $power,
            'rentTime' => intval($time), // 最大委托笔数，当为购买笔数时作为购买笔数的数量
            'receiveAddress' => $toAddress,
            'resLock' => 0,
        ];
        $orderLog = new EnergyLog();
        $orderLog->power_count = $power;
        $orderLog->time = $time;
        $orderLog->address = $toAddress;
        $orderLog->user_id = $userId;
        $orderLog->energy_policy = $this->name();
//        $orderLog->lock_duration = $lockDuration;
//        if ($lockDuration > 0) {
//            $orderLog->expired_dt = Carbon::now()->addMinutes($lockDuration);
//        }
        $orderLog->save();

        Logger::debug('参数：' . json_encode($params));
        try {
            $data = $this->post('/api/pay', $params);
            $orderLog->response_text = json_encode($data);
            $orderLog->tx_id = $data['delegate_hash'][0];
            $orderLog->from_address = $data['sendAddressList'][0];
            $orderLog->status = 1;
            $orderLog->save();
        } catch (\Exception $e) {
            $orderLog->status = -1;
            $orderLog->fail_reason = $e->getMessage();
            $orderLog->save();
        }

        return $orderLog;
    }

    private function post($url, array $params = [])
    {
        $resp = $this->client->post($url, ['json'=>$params]);
        $statusCode = $resp->getStatusCode();
        Logger::debug('status=>' . $statusCode);
        Logger::debug("response:" . $resp->getBody());
        if ($statusCode == 200) {
            $body = json_decode($resp->getBody(), true);
            if ($body['code'] == '10000') {
                Logger::debug('请求成功');
                return $body['data'];
            } else {
                Logger::debug('请求失败：' . $body['msg']);
                throw new \Exception('/api/auto/add 接口错误：' . $body['msg']);
            }
        } else {
            throw new \Exception('/api/auto/add 接口异常：' . $resp->getBody());
        }
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
            $data = $this->post('/api/userInfo', ["username" => $this->username]);
            return $data['balance'];
        } catch (\Exception $e) {
            error_log($e->getMessage().$e->getTraceAsString());
            return 0;
        }
    }
}