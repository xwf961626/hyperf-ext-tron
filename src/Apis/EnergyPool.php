<?php

namespace William\HyperfExtTron\Apis;

use Carbon\Carbon;
use William\HyperfExtTron\Model\EnergyLog;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Model\UserResourceAddress;
use William\HyperfExtTron\Tron\Energy\Apis\AbstractApi;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;
use William\HyperfExtTron\Tron\TronApi;
use Hyperf\Di\Annotation\Inject;

#[EnergyApi(name: 'pool')]
class EnergyPool extends AbstractApi
{
    #[Inject]
    protected TronApi $tron;

    const SOURCE_ENERGY = 'ENERGY';
    const SOURCE_BANDWIDTH = 'BANDWIDTH';

    public function init($configs)
    {
        // TODO: Implement init() method.
    }

    public function validate($params)
    {
        // TODO: Implement validate() method.
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

        $resourceAddress = $this->getUserResourceAddress($powerCount);

        if (!$resourceAddress) {
            throw new \Exception('未找到合适的能量池地址');
        }
        Logger::debug('EnergyApi#EnergyPool 使用地址：' . json_encode($resourceAddress));

        $orderLog = new EnergyLog();
        $orderLog->power_count = $power;
        $orderLog->time = $time;
        $orderLog->address = $toAddress;
        $orderLog->user_id = $userId;
        $orderLog->energy_policy = $this->name();
        $orderLog->from_address = $resourceAddress->address;
        $orderLog->lock_duration = $lockDuration;
        if ($lockDuration > 0) {
            $orderLog->expired_dt = Carbon::now()->addMinutes($lockDuration);
        }

        $price = $this->tron->getResourcePrice(self::SOURCE_ENERGY);
        Logger::debug('EnergyApi#EnergyPool 查询能量价格：' . $price);
        $amount = intval($powerCount * $price);
        Logger::debug('EnergyApi#EnergyPool 查询能量总金额：' . $amount);
        try {
            if (!$txid = $this->tron->delegateResource($resourceAddress,
                $toAddress,
                self::SOURCE_ENERGY,
                $amount,
                0)) {
                Logger::error("EnergyApi#EnergyPool 发送能量异常失败");
                throw new \Exception('代理资源失败');
            }
            Logger::debug('EnergyApi#EnergyPool 发送成功：' . $txid);
            $orderLog->tx_id = $txid;
            $orderLog->status = 1;
            $orderLog->save();
        } catch (\Exception $e) {
            Logger::debug('发送失败：' . $e->getMessage());
            $orderLog->status = -1;
            $orderLog->fail_reason = $e->getMessage();
            $orderLog->save();
            throw new \Exception($e->getMessage());
        }
        try {
//            $this->updateUserResourceAddress($resourceAddress);
        } catch (\Exception $e) {
            Logger::debug("更新地址资源失败" . $e->getMessage());
        }

        return $orderLog;
    }

    public function recycle(string $toAddress): mixed
    {
        $logs = ResourceDelegate::where('receive_address', $toAddress)
            ->where('status', '1')
            ->get();
        /** @var ResourceDelegate $log */
        foreach ($logs as $log) {
            $addr = UserResourceAddress::find($log->resource_address_id);
            if ($addr) {
                $this->tron->unDelegateResource($addr->address, 'ENERGY', $log->receive_address,
                    $log->amount * 1_000_000, $addr->permission);
            }
        }
        return null;
    }

    /**
     * @param $count
     * @return ?UserResourceAddress
     * @throws \Exception
     */
    private function getUserResourceAddress($count): ?UserResourceAddress
    {
        try {
            $addrs = UserResourceAddress::query()
                ->where('status', "1")
                ->whereNotNull('permission')
                ->orderBy('sort_num', 'desc')
                ->get();
            Logger::debug('自有能量池：' . json_encode($addrs));
            foreach ($addrs as $addr) {
                $data = $this->tron->getAccountResources($addr->address);
                Logger::debug('查询自有地址能量：' . json_encode($data));
                $currentEnergy = $data->currentEnergy;//(数量）
                if ($currentEnergy >= $count) {
                    $addr->max_delegate_energy = $currentEnergy;
                    Logger::debug('找到能量足够的地址：' . $addr->address);
                    return $addr;
                }
            }
            Logger::debug('EnergyApi#EnergyPool 没有足够能量的地址可用');
            throw new \Exception('没有足够能量的地址可用');
        } catch (\Exception $e) {
            Logger::debug('EnergyApi#EnergyPool 查询可用资源异常:' . $e->getMessage());
            throw new \Exception("查询可用资源异常：" . $e->getMessage());
        }
    }

    public function getEnergyLogClass(): string
    {
        return EnergyLog::class;
    }

    public function name(): string
    {
        return EnergyApi::API_POOL;
    }
}
