<?php

namespace William\HyperfExtTron\Tron\Energy\Apis;

use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;
use William\HyperfExtTron\Tron\Energy\Model\WeiduEnergyLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

#[EnergyApi(name: 'weidu')]
class Weidubot extends AbstractApi
{
    protected string $apiKey;
    protected string $sendApi;
    protected string $getResultApi;

    protected Client $client;
    protected string $apiSecret;


    public function send(string $toAddress, int $power, mixed $time, int $userId = 0): WeiduEnergyLog
    {

        // 兼容闪租设置
        if ($time == '30min' || $time == '5min') {
            $time = '1h';
        }
        if (!$power || !$time) {
            Logger::error('EnergyAPI#Weidu 参数错误');
            throw new \Exception('能量数、时长未定义');
        }
        if (ctype_digit($time)) {
            $time = $time . 'day';
        }
        Logger::debug('时长：' . $time);
        $params = [
            'count' => $power, //委托的能量点数 (最低 30000)
            'period' => $time,
            'address' => $toAddress,
        ];

        $this->validate($params);

        $orderLog = new WeiduEnergyLog();
        $orderLog->power = $power;
        $orderLog->period = $time;
        $orderLog->to_address = $toAddress;
        $orderLog->user_id = $userId;
        $orderLog->save();

        if (!env('WEIDU_ON', false)) {
            return $orderLog;
        }
        try {
            $result = $this->buyEnergy($params);
            $data = $result['data'];
            $orderSn = $data['order_sn'];
            $price = $data['price'];
            $fee = $data['fee'];
            $amount = $data['amount'];
            $balance = $data['balance'];
            $orderLog->order_sn = $orderSn;
            $orderLog->price = $price;
            $orderLog->fee = $fee;
            $orderLog->amount = $amount;
            $orderLog->balance = $balance;
            $orderLog->save();
        } catch (GuzzleException $e) {
            $orderLog->order_status = "error";
            $orderLog->response_json = json_encode(['err' => $e->getMessage()]);
            $orderLog->fail_reason = $e->getMessage();
            $orderLog->save();
            throw $e;
        }

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
                        $orderLog->order_status = 'success';
                        $orderLog->tx_id = $txID;
                        $orderLog->from_address = $fromAddress;
                        $orderLog->energy_count = $count;
                        $orderLog->response_json = json_encode($detailData);
                        $orderLog->save();
                        return $orderLog;
                    }
                }
            } catch (\Exception $e) {
                Logger::debug('开始查询订单结果 #' . $attempt . '  异常：' . $e->getMessage());
                $orderLog->order_status = "error";
                $orderLog->response_json = json_encode(['err' => $e->getMessage()]);
                $orderLog->save();
            }
            $attempt++;
            sleep(1);
        }
        return $orderLog;
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
        Logger::debug('headers: ' . json_encode($headers));
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
        return $this->post($this->getResultApi, ['order_sn' => $orderSN]);
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

    public function recycle(string $toAddress): mixed
    {
        // TODO: Implement recycle() method.
        return null;
    }

    public function init($configs)
    {
        $this->apiKey = $configs['api_key'];
        $this->sendApi = $configs['send_api'];
        $this->getResultApi = $configs['get_result_api'];
        $this->apiSecret = $configs['api_secret'];
        $this->client = new Client();
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function post(string $url, array $params)
    {
        $body = json_encode($params);
        $response = $this->client->request('POST', $url, [
            'body' => $body,
            'headers' => $this->getHeaders(),
            'http_errors' => false
        ]);
        $contents = $response->getBody()->getContents();
        if ($response->getStatusCode() !== 200) {
            Logger::debug('EnergyAPI#Weidu ' . $url . ' POST  => ' . $body);
            Logger::debug('接口请求错误：' . $contents);
            throw new \Exception('接口请求错误: ' . $contents);
        } else {
            Logger::debug("CardApi#QBit 接口返回成功 $contents");
            return json_decode($contents, true);
        }
    }

    /**
     * @throws GuzzleException
     */
    private function buyEnergy(array $params): ?array
    {
        return $this->post($this->sendApi, $params);
    }

    public function getEnergyLogClass(): string
    {
        return WeiduEnergyLog::class;
    }

    public function name(): string
    {
        return EnergyApi::API_WEIDU;
    }
}
