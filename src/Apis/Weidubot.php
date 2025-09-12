<?php

namespace William\HyperfExtTron\Apis;

use Carbon\Carbon;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Model\EnergyLog;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Tron\Energy\Apis\AbstractApi;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;
use William\HyperfExtTron\Tron\Energy\Model\WeiduEnergyLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

#[EnergyApi(name: 'weidu')]
class Weidubot extends AbstractApi
{
    const API_NAME = 'weidu';
    protected string $apiKey = '';
    protected string $baseUrl = 'https://weidubot.cc';
    protected string $apiSecret;


    public function delegateHandler(ResourceDelegate $delegate): string
    {

        $params = [
            'count' => $delegate->quantity, //委托的能量点数 (最低 30000)
            'period' => $delegate->time,
            'address' => $delegate->address,
        ];

        $this->validate($params);

        if (!env('WEIDU_ON', false)) {
            return "weidu off";
        }

        $result = $this->buyEnergy($params);
        $data = $result['data'];
        $orderSn = $data['order_sn'];
        $price = $data['price'];
        $fee = $data['fee'];
        $amount = $data['amount'];
        $balance = $data['balance'];


        Logger::debug('开始查询订单结果：' . $orderSn);
        $maxRetry = 5;
        $attempt = 0;
        while ($attempt < $maxRetry) {
            try {
                Logger::debug('开始查询订单结果 #' . $attempt . '  ' . $orderSn);
                $detail = $this->getOrderDetail($orderSn);
                if (!empty($detail)) {
                    $detailData = $detail['data'];
                    $orderStatus = $detailData['status']; // paid, waiting, success
                    if ($orderStatus == 'success' && !empty($detailData['orders'])) {
                        $info = $detailData['orders'][0];
                        Logger::debug('查询成功： #' . $attempt . '  ' . json_encode($info));
                        $txID = $info['tx_id'];
                        $fromAddress = $info['from_address'];
                        $count = $info['count'];
                        $delegate->from_address = $fromAddress;
                        return $txID;
                    }
                }
            } catch (\Exception $e) {
                Logger::debug('开始查询订单结果 #' . $attempt . '  异常：' . $e->getMessage());
            }
            $attempt++;
            sleep(1);
        }
        throw new \Exception("查询{$maxRetry}次无果");
    }

    private function getHeaders(): array
    {
        $timestamp = time();
        $data = $this->apiKey . $timestamp;
        $binary = hash_hmac('sha256', $data, $this->apiSecret, true);
        $hex = bin2hex($binary);
        $sign = strtolower($hex);
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'x-timestamp' => $timestamp,
            'x-signature' => $sign
        ];
        error_log('headers: ' . json_encode($headers));
        return $headers;
    }

    /**
     * {"code":1,"msg":"查询成功","time":"1754649589","data":{"order_sn":"f7671844549348846ef4f485e0e3f430","amount":"3.0000",
     * "price":30,"fee":"0.0000","period":"1h","status":"success",
     * "orders":[{"from_address":"TEeAxSRZP6nEcFfENGzc3CgBRJTQci2wp4",
     * "tx_id":"86a02dbdeee1321d4bae913c392fa375a2ecbf5f8c76fb4ca5a0773751ce0acf","count":100010}]}}
     * @throws GuzzleException
     *
     */
    public function getOrderDetail(string $orderSN): array
    {
        return $this->post('/api/v2/query_result', ['order_sn' => $orderSN]);
    }

    /**
     * @throws \Exception
     */
    public function validate($params): void
    {
        $allowTimes = ['1h', '6h', '1day', '3day', '7day', '14day', '30day'];
        if (!in_array($params['period'], $allowTimes)) {
            throw new \Exception('时长错误 ' . $params['period'] . '：只允许' . json_encode($allowTimes));
        }
    }


    public function init($configs)
    {
        $this->apiKey = $this->model->api_key ?? $configs['api_key'];
        $this->baseUrl = $configs['base_url'];
        $this->apiSecret = $this->model->api_secret ?? $configs['api_secret'];
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function post(string $url, array $params)
    {
        $body = json_encode($params);
        $response = $this->_post($url, $params);
        $contents = $response->getBody()->getContents();
        if ($response->getStatusCode() !== 200) {
            Logger::debug('Weidu ' . $url . ' POST  => ' . $body);
            Logger::debug('接口请求错误：' . $contents);
            throw new \Exception('接口请求错误: ' . $contents);
        } else {
            Logger::debug("接口返回成功 $contents");
            return json_decode($contents, true);
        }
    }

    /**
     * @throws GuzzleException
     */
    private function buyEnergy(array $params): ?array
    {
        return $this->post('/api/v2/buy_energy', $params);
    }

    public function getEnergyLogClass(): string
    {
        return WeiduEnergyLog::class;
    }

    public function name(): string
    {
        return EnergyApi::API_WEIDU;
    }

    public function getBalance(): float
    {
        $response = $this->client->get('/api/v2/user_info', ['headers' => $this->getHeaders(), 'http_errors' => false]);
        $contents = $response->getBody()->getContents();
        if ($response->getStatusCode() !== 200) {
            Logger::debug('接口请求错误：' . $contents);
            if ($response->getStatusCode() == 403) {
                return 0;
            }
            throw new \Exception('接口请求错误: ' . $contents);
        } else {
            Logger::debug("接口返回成功 $contents");
            $result = json_decode($contents, true);
            return $result['data']['balance_trx'] ?? 0;
//            return $result['data']['balance_usdt']??0;
        }
    }

    function parseTime(mixed $time)
    {
        $lockDuration = 0;
        // 兼容闪租设置
        if ($time == '30min' || $time == '5min') {
            $time = '1h';
            $lockDuration = 60;
        }

        if (ctype_digit($time)) {
            $time = $time . 'day';
            $lockDuration = (int)$time * 60 * 24;
        }
        Logger::debug('时长：' . $time);
        return [$time, $lockDuration];
    }
}
