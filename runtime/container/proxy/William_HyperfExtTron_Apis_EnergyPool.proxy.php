<?php

namespace William\HyperfExtTron\Apis;

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
    use \Hyperf\Di\Aop\ProxyTrait;
    use \Hyperf\Di\Aop\PropertyHandlerTrait;
    function __construct()
    {
        if (method_exists(parent::class, '__construct')) {
            parent::__construct(...func_get_args());
        }
        $this->__handlePropertyHandler(__CLASS__);
    }
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
    public function send(string $toAddress, int $power, mixed $time, int $userId = 0) : ResourceDelegate
    {
        $powerCount = $power;
        Logger::debug("EnergyApi#EnergyPool 代理资源参数：{$toAddress} => power = {$powerCount}, time={$time}, user_id= {$userId}");
        $resourceAddress = $this->getUserResourceAddress($powerCount);
        if (!$resourceAddress) {
            throw new \Exception('未找到合适的能量池地址');
        }
        Logger::debug('EnergyApi#EnergyPool 使用地址：' . json_encode($resourceAddress));
        $price = $this->tron->getResourcePrice(self::SOURCE_ENERGY);
        Logger::debug('EnergyApi#EnergyPool 查询能量价格：' . $price);
        $amount = intval($powerCount * $price);
        Logger::debug('EnergyApi#EnergyPool 查询能量总金额：' . $amount);
        try {
            if (!($hash = $this->tron->delegateResource($resourceAddress, $toAddress, self::SOURCE_ENERGY, $amount, 0))) {
                Logger::error("EnergyApi#EnergyPool 发送能量异常失败");
                throw new \Exception('代理资源失败');
            }
            Logger::debug('EnergyApi#EnergyPool 发送成功：' . $hash);
        } catch (\Exception $e) {
            Logger::debug('发送失败：' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
        try {
            $this->tronupdateUserResourceAddress($resourceAddress);
        } catch (\Exception $e) {
            Logger::debug("更新地址资源失败" . $e->getMessage());
        }
        return $hash;
    }
    public function recycle(string $toAddress) : mixed
    {
        $logs = ResourceDelegate::where('receive_address', $toAddress)->where('status', '1')->get();
        /** @var ResourceDelegate $log */
        foreach ($logs as $log) {
            $addr = UserResourceAddress::find($log->resource_address_id);
            if ($addr) {
                $this->tron->unDelegateResource($addr->address, 'ENERGY', $log->receive_address, $log->amount * 1000000);
            }
        }
        return null;
    }
    /**
     * @param $count
     * @return ?UserResourceAddress
     * @throws \Exception
     */
    private function getUserResourceAddress($count) : ?UserResourceAddress
    {
        try {
            $addrs = UserResourceAddress::query()->where('status', "1")->whereNotNull('permission')->orderBy('sort_num', 'desc')->get();
            Logger::debug('自有能量池：' . json_encode($addrs));
            foreach ($addrs as $addr) {
                $data = $this->trongetAccountResources($addr->address);
                Logger::debug('查询自有地址能量：' . json_encode($data));
                $energyUsed = $data['EnergyUsed'] ?? 0;
                $energyLimit = $data['EnergyLimit'] ?? 0;
                $currentEnergy = $energyLimit - $energyUsed;
                //(数量）
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
    public function getEnergyLogClass() : string
    {
        return EnergyLog::class;
    }
    public function name() : string
    {
        return EnergyApi::API_POOL;
    }
}