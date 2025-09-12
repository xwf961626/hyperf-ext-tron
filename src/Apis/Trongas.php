<?php

namespace William\HyperfExtTron\Apis;

use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Tron\Energy\Apis\AbstractApi;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;

/**
 * https://trongas.io/pay/interface
 */
#[EnergyApi(name: Trongas::API_NAME)]
class Trongas extends AbstractApi
{
    const API_NAME = 'trongas';
    protected string $baseUrl = "https://trongas.io";
    protected string $username = '';

    public function init($configs)
    {
        $this->apiKey = $this->model->api_key ?? $configs['apiKey'];
        $this->baseUrl = $configs['baseUrl'];
        $this->username = $this->model->api_secret ?? $configs['username'];
    }

    public function delegateHandler(ResourceDelegate $delegate): string
    {
        $params = [
            'apiKey' => $this->apiKey,
            'resType' => 'ENERGY',
            'payNums' => $delegate->quantity,
            'rentTime' => $delegate->time, // 单位小时，只能1时或1到30天按天租用其中不能租用2天
            'receiveAddress' => $delegate->address,
            'resLock' => 0,
        ];
        Logger::debug('参数：' . json_encode($params));
        $data = $this->post('/api/pay', $params);
        return implode(',', $data['delegate_hash']);
    }

    private function post($url, array $params = [])
    {
        $resp = $this->_post($url, $params);
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


    public function name(): string
    {
        return self::API_NAME;
    }

    public function getBalance(): float
    {
        try {
            $data = $this->post('/api/userInfo', ["username" => $this->username]);
            return $data['balance'];
        } catch (\Exception $e) {
            error_log($e->getMessage() . $e->getTraceAsString());
            return 0;
        }
    }

    function parseTime(mixed $time)
    {
        // TODO: Implement parseTime() method.
    }
}