<?php

namespace William\HyperfExtTron\Service;

use Hyperf\Cache\Cache;
use Psr\EventDispatcher\EventDispatcherInterface;
use William\HyperfExtTron\Event\LimitAddressClosed;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\LimitResourceAddress;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Model\UserResourceAddress;
use William\HyperfExtTron\Tron\TronApi;

class LimitAddressService
{
    public function __construct(protected TronApi $tronApi, protected Cache $cache, protected EventDispatcherInterface $dispatcher)
    {
    }

    public function closeAddress(LimitResourceAddress $address): void
    {
        $address->send_times = 0;
        $address->status = 0;
        $address->save();

        $this->recycle($address);
        $this->dispatcher->dispatch(new LimitAddressClosed($address));
    }

    public function recycle(LimitResourceAddress $address): void
    {
        $delegates = ResourceDelegate::where('address', $address->address)
            ->where('status', 1)
            ->where('undelegate_status', 0)
            ->get();

        $owners = [];

        /**
         * @throws \Exception
         */
        $getOwner = function (ResourceDelegate $delegate) use (&$owners): ?UserResourceAddress {
            if (!isset($owners[$delegate->from_address])) {
                /** @var UserResourceAddress $owner */
                $owner = UserResourceAddress::where('address', $delegate->from_address)->first();
                if (!$owner) {
                    throw new \Exception("回收资源 {$delegate->resource} owner {$delegate->from_address} 不存在");
                }
                $owners[$delegate->from_address] = $owner;
            }
            return $owners[$delegate->from_address];
        };

        /** @var ResourceDelegate $delegate */
        foreach ($delegates as $delegate) {
            $logPrefix = "回收资源 {$delegate->resource} owner={$delegate->from_address}  receive={$delegate->address} ";
            try {
                $owner = $getOwner($delegate);
                $balance = intval($delegate->lock_amount * 1_000_000);
                Logger::debug("$logPrefix 开始回收: $balance");
                $hash = $this->tronApi->unDelegateResource(
                    $delegate->from_address,
                    $delegate->resource,
                    $delegate->address,
                    $balance,
                    $owner->permission);
                Logger::debug("$logPrefix 回收成功：$hash");
                $delegate->undelegate_hash = $hash;
                $delegate->undelegate_at = date("Y-m-d H:i:s");
                $delegate->undelegate_status = 1;
                $delegate->save();
            } catch (\Exception $e) {
                Logger::error("$logPrefix 回收失败：{$e->getMessage()}");
                $delegate->fail_reason = $e->getMessage();
                $delegate->undelegate_status = -1;
                $delegate->save();
            }
        }
    }

    public function recycleRetry(int $delegateId)
    {
        /** @var ResourceDelegate $order */
        $order = ResourceDelegate::query()->find($delegateId);
        if (!$order) {
            throw new \Exception("订单不存在");
        }
        if ($order->status != 3) {
            throw new \Exception("订单状态不正确");
        }
        $balance = intval($order->lock_amount * 1_000_000);
        $owner = UserResourceAddress::where('address', $order->from_address)->first();
        if (!$owner) {
            throw new \Exception("发送地址不存在：{$order->from_address}");
        }
        try {
            $res = $this->tronApi->unDelegateResource($order->from_address, $order->resource, $order->address, $balance, $owner->permission);
            if ($res) {
                $order->status = 2;
                $order->undelegate_at = date('Y-m-d H:i:s');
                $order->undelegate_hash = $res;
                $order->save();
            }
        } catch (\Exception $e) {
            Logger::error("回收失败{$e->getMessage()}");
            $order->fail_reason = $e->getMessage();
            $order->undelegate_status = -1;
            $order->save();
            throw $e;
        }
    }

    public function updateResources(LimitResourceAddress $addr)
    {
        $stdResource = $this->tronApi->getAccountResources($addr->address);
        Logger::debug("{$addr->address}|currentNet=$stdResource->currentNet|currentEnergy=$stdResource->currentEnergy");

        $addr->fill([
            'total_quantity' => $addr->resource == 'ENERGY' ? $stdResource->totalEnergy : $stdResource->totalNet,
            'current_quantity' => $addr->resource == 'ENERGY' ? $stdResource->currentEnergy : $stdResource->currentNet,
        ])->save();  // 内存和数据库同步
    }


    public function getLimitList($model): array
    {
        $key = "limit_list:" . md5($model);
        if ($cache = $this->cache->get($key)) {
            return json_decode($cache, true);
        } else {
            $all = $model::query()->where('status', 1)->get()->toArray();
            $this->cache->set($key, json_encode($all));
            return $all;
        }
    }

    public function clearLimitList($model)
    {
        $key = "limit_list:" . md5($model);
        if ($this->cache->get($key)) {
            $this->cache->delete($key);
        }
    }
}