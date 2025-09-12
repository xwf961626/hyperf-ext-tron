<?php

namespace William\HyperfExtTron\Tron\Energy\Rental;

use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTronModel\User;
use William\HyperfExtTron\Tron\Coin;
use William\HyperfExtTron\Tron\Energy\Attributes\Rental;
use William\HyperfExtTron\Tron\Energy\Model\RentEnergyOrder;
use William\HyperfExtTron\Tron\Energy\Utils;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Rental(name: Rental::RENT)]
class Rent extends AbstractRentalService
{
    protected string $name = Rental::RENT;

    public function createOrder(RequestInterface $request, User $user, array $options): array
    {
        Logger::info("能量租赁：" . json_encode($request->all()));
        $time_expire_way = $request->input('time_expire_way', 0);//1:1小时，2:1天，3:3天
        $energy_num = $request->input('energy_num', 0);//能量数量
        $user_address = $request->input('user_address', '');//能量数量
        $time_expire_way_text = RentEnergyOrder::$time_expire_way[$time_expire_way] ?? '';
        if (!$time_expire_way_text) {
            throw new Exception('时间格式错误');
        }
        $energy_num = intval($energy_num);
        if ($energy_num <= 0) {
            throw new Exception('能量数量错误');
        }
        if (!$this->tronApi->isAddress($user_address)) {
            throw new Exception('地址错误！');
        }

        $priceConfig = RentEnergyOrder::getRentConfigPrice($time_expire_way, $energy_num);

        $order_pay_type = $this->settingService->get('order_pay_type');
        [$usdt_price, $trx_price] = Utils::getPrices($priceConfig);
        if (in_array($order_pay_type, [1, 3])) {
            $price = $trx_price;
            $pay_type = Coin::TRX;
        } else {
            $price = $usdt_price;
            $pay_type = Coin::USDT;
        }
        /** @var RentEnergyOrder $rentorder */
        $rentorder = RentEnergyOrder::query()->create([
            'id' => Utils::makeOrderNo(),
            'user_id' => $user->id,
            'time_expire_way' => $time_expire_way,
            'energy_num' => $energy_num,
            'user_address' => $user_address,
            'price' => $price,
            'usdt_price' => $usdt_price,
            'trx_price' => $trx_price,
            'pay_type' => $pay_type,
            'energy_policy' => $this->api->name(),
        ]);

        $order = PayOrderServices::create($rentorder, $pay_type, $price, [
            'subject' => '能量租赁购买',
            'user_id' => $user->id,
        ])->pay();
        $day = 1;
        if ($time_expire_way >= 24) {
            $day = intval($time_expire_way / 24);
        }
        $bishu = ceil($energy_num / RentEnergyOrder::PER_ENERGY_NUM);// 几笔
        $rentorder->per_price = Utils::formatAmount(round($price / $day / $bishu, 3), false);
        return compact('order', 'rentorder');
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function rent(mixed $order, ?User $user = null): ?RentEnergyOrder
    {
        if ($order instanceof RentEnergyOrder) {
            try {
                $time = $order->time_expire_way;
                if ($time == 1) {
                    $time = '1h';
                } else if ($time == 2) {
                    $time = '1day';
                } else if ($time == 3) {
                    $time = '3day';
                }
                $energyLog = $this->api->delegate(
                    $order->user_address,
                    $order->energy_num,
                    $time,
                    $user->id,
                );
                $order->status = RentEnergyOrder::STATUS_SUCCESS;
                $order->energy_log_id = $energyLog->id;
                $order->save();
            } catch (Exception $e) {
                $this->log->error('EnergyService#Energy 发送能量失败:' . $e->getMessage());
                $order->status = RentEnergyOrder::STATUS_ERROR;
                $order->save();
                throw $e;
            }
            return $order;
        } else {
            throw new Exception('订单类型错误：' . get_class($order));
        }
    }
}
