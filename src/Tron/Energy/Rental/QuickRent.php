<?php

namespace William\HyperfExtTron\Tron\Energy\Rental;


use William\HyperfExtTron\Tron\Energy\Apis\ApiInterface;
use William\HyperfExtTron\Tron\Energy\Attributes\Rental;
use William\HyperfExtTron\Tron\Energy\Model\QuickrentOrder;
use William\HyperfExtTron\Tron\Energy\Utils;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Rental(name: Rental::QUICK_RENT)]
class QuickRent extends AbstractRentalService
{
    protected string $name = Rental::QUICK_RENT;
    public function createOrder(RequestInterface $request, int $userId, array $options): ?QuickRentOrder
    {
        $exchange_usdt_address = $this->settingService->get('quickrent_trx_address');
        $this->log->info("Rental#QuickRent 开始创建闪租订单:" . json_encode($options) . ', 收款地址:' . $exchange_usdt_address);
        $hash = $options['hash'];
        $amount = $options['amount'];
        $amount = Utils::formatAmount($amount);
        $from_address = $options['from_address'] ?? '';
        $hashCount = QuickrentOrder::query()->where('transfer_hash', $hash)->count();
        if ($hashCount > 0) {
            throw new \Exception("hashCount = {$hashCount} > 0");
        }
        if ($amount <= 0) {
            throw new \Exception("amount = {$amount} <= 0");
        }

        $energy = 0;
        $receive_price = 0;
        $time = 0;
        /** @var QuickrentOrder $order */
        $order = QuickrentOrder::query()->create([
            'id' => Utils::makeOrderNo(),
            'quickrent_address' => $exchange_usdt_address,
            'user_address' => $from_address,
            'receive_price' => $receive_price,
            'transfer_hash' => $hash,
            'status' => 0,
            'notify_date' => json_encode($options),
            'energy_num' => $energy,
            'energy_period' => $time,
            'energy_policy' => $this->api->name(),
        ]);
        return $order;

    }

    /**
     * @throws GuzzleException
     */
    public function rent(mixed $order, int $userId = 0): mixed
    {
        if ($order instanceof QuickRentOrder) {
            /** @var ApiInterface $api */
            $api = $this->api;
            try {
                $energyLog = $api->delegate($order->user_address, $order->energy_num, $order->energy_period, $userId);
                $this->log->info('EnergyService#QuickRent 发送能量成功: energy_log => ' . json_encode($energyLog));
                $order->energy_log_id = $energyLog->id;
                $order->energy_policy = $this->api->name();
                $order->save();
            } catch (GuzzleException $e) {
                $this->log->info('EnergyService#QuickRent 发送能量失败：' . $e->getMessage() . $e->getTraceAsString());
                $order->status = QuickrentOrder::STATUS_ERROR;
                $order->fail_reason = substr($e->getMessage() . $e->getTraceAsString(), 0, 100);
                $order->save();
                throw $e;
            }
        }
        throw new \Exception('订单类型错误:不是闪租订单:' . get_class($order));
    }
}
