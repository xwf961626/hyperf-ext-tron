<?php

namespace William\HyperfExtTron\Apis;

use GuzzleHttp\Client;
use William\HyperfExtTron\Helper\GuzzleClient;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\EnergyLog;
use William\HyperfExtTron\Tron\Energy\Apis\AbstractApi;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;

#[EnergyApi(name: Trongas::API_NAME)]
class Trongas extends AbstractApi
{
    const API_NAME = 'trongas';
    protected string $apiKey = '';
    protected Client $client;

    public function init($configs)
    {
        $this->apiKey = $configs['apiKey'];
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
        Logger::debug('参数：' . json_encode($params));
        $resp = $this->client->post('/api/pay',  $params);
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
}