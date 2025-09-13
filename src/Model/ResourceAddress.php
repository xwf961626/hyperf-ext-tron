<?php

namespace William\HyperfExtTron\Model;

use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Database\Model\Model;
use Psr\EventDispatcher\EventDispatcherInterface;
use William\HyperfExtTron\Event\ResourceAddressClosed;
use William\HyperfExtTron\Event\ResourceAddressDelegated;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Tron\AccountResource;
use William\HyperfExtTron\Tron\TronApi;
use function Hyperf\Support\make;

/**
 * @property string $address
 * @property int $id
 * @property string $resource
 * @property int $min_quantity
 * @property int $send_times
 * @property int $max_times
 * @property int $current_quantity
 * @property int $total_quantity
 * @property int $status
 * @property int $send_quantity
 * @property string|null $name
 */
class ResourceAddress
{
    private array $data;
    private int $id;
    private TronApi $tron;
    private EventDispatcherInterface $eventDispatcher;
    /**
     * @var mixed|null
     */
    private mixed $class;

    private function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->data = $data;
        $this->tron = make(TronApi::class);
        $this->eventDispatcher = make(EventDispatcherInterface::class);
    }

    public static function make($class, array $data): ResourceAddress
    {
        $self = new self($data);
        $self->class = $class;
        return $self;
    }

    /**
     * @return AccountResource
     * @throws GuzzleException
     */
    public function updateResources(): AccountResource
    {
        $stdResource = $this->tron->getAccountResources($this->address);
        Logger::debug("{$this->address}|currentNet=$stdResource->currentNet|currentEnergy=$stdResource->currentEnergy");
        $total = $this->resource == 'ENERGY' ? $stdResource->totalEnergy : $stdResource->totalNet;
        $current = $this->resource == 'ENERGY' ? $stdResource->currentEnergy : $stdResource->currentNet;
        $this->update([
            'total_quantity' => $total,
            'current_quantity' => $current,
        ]);
        $this->total_quantity = $total;
        $this->current_quantity = $current;
        return $stdResource;
    }

    public function closeAddress(): void
    {
        $this->status = 0;
        $this->update(['status' => 0]);
        $this->eventDispatcher->dispatch(new ResourceAddressClosed($this));
    }

    public function update(array $updates)
    {
        return $this->class::query()->where('id', $this->id)->update($updates);
    }

    public function recycle(UserResourceAddress $owner): void
    {
        Logger::debug("发送前先回收资源:{$this->address}");
        $delegates = ResourceDelegate::where('address', $this->address)
            ->where('status', 1)
            ->where('undelegate_status', 0)
            ->get();
        /** @var ResourceDelegate $dg */
        foreach ($delegates as $dg) {
            try {
                Logger::debug("发送前回收订单{$dg->id} amount={$dg->lock_amount} owner={$owner->address} receiver={$this->address}");
                $balance = intval($dg->lock_amount * 1_000_000);
                $dh = $this->tron->unDelegateResource(
                    $owner->address,
                    $this->resource,
                    $this->address,
                    $balance,
                    $owner->permission,
                );
                Logger::debug("发送前回收成功：$dh");
                $dg->undelegate_status = 1;
                $dg->undelegate_at = date('Y-m-d H:i:s');
                $dg->undelegate_hash = $dh;
                $dg->save();
            } catch (\Exception $e) {
                Logger::debug("发送前回收失败：{$e->getMessage()}");
                $dg->undelegate_status = -1;
                $dg->undelegate_at = date('Y-m-d H:i:s');
                $dg->fail_reason = $e->getMessage();
                $dg->save();
            }
        }
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function delegate(UserResourceAddress $owner): void
    {
        // 获取资源价格
        $price = $this->tron->getResourcePrice(strtoupper($this->resource));
        if (!$price) {
            throw new \Exception("❌ 代理资源失败：查询资源价格失败");
        }
        Logger::debug("💰 资源价格：{$this->resource} = {$price}");

        $lockAmount = $price * $this->send_quantity;

        // 创建资源代理记录
        $delegate = new ResourceDelegate();
        $delegate->lock_amount = $lockAmount;
        $delegate->address = $this->address;
        $delegate->resource = $this->resource;
        $delegate->price = $price;
        $delegate->quantity = $this->send_quantity;
        $delegate->from_address = $owner->address;
        $delegate->save();
        Logger::debug("📝 资源代理记录保存成功");

        // 计算冻结的 TRX
        $balance = intval($lockAmount * 1_000_000);

        try {
            // 代理资源
            Logger::debug("⚡ 开始代理资源...");
            $hash = $this->tron->delegateResource(
                $owner->address,
                $this->resource,
                $this->address,
                $balance,
                $owner->permission,
            );
            Logger::info("✅ 代理资源成功：tx_id=" . json_encode($hash));

            // 更新代理记录
            $delegate->tx_id = $hash;
            $delegate->status = 1;
            $delegate->delegate_at = date("Y-m-d H:i:s");
            $delegate->save();
            Logger::debug("🗓️ 代理记录更新成功");

            // 增加发送次数
            Logger::debug("📈 增加发送次数+1");
            $this->eventDispatcher->dispatch(new ResourceAddressDelegated($this));
        } catch (\Exception $e) {
            $delegate->fail_reason = $e->getMessage();
            $delegate->status = -1;
            $delegate->save();
            throw $e;
        }
    }

    public function getClass()
    {
        return $this->class;
    }


    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, $value)
    {
        $this->data[$name] = $value;
    }

    public function save()
    {
        $this->update($this->data);
    }

}