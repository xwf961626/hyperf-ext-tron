<?php

namespace William\HyperfExtTron\Controller;


use Hyperf\Contract\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use William\HyperfExtTron\Event\LimitAddressClosed;
use William\HyperfExtTron\Event\LimitAddressCreated;
use William\HyperfExtTron\Model\LimitResourceAddress;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Service\LimitAddressService;

class LimitAddressController extends BaseController
{
    public function __construct(ContainerInterface                 $container,
                                protected EventDispatcherInterface $eventDispatcher,
                                protected LimitAddressService      $service)
    {
        parent::__construct($container);
    }

    public function addressList()
    {
        $q = LimitResourceAddress::query();
        if ($type = $this->request->query('resource_type')) {
            if (in_array($type, ['ENERGY', 'BANDWIDTH'])) {
                $q = $q->where('resource', $type);
            }
        }
        if ($addr = trim($this->request->query('keyword'))) {
            $q = $q->where('address', $addr);
        }
        $list = $q->get();
        return $this->success(compact('list'));
    }

    public function addAddress()
    {
        if (!$address = $this->request->input('address', '')) {
            return $this->error('请输入地址');
        }
        if (LimitResourceAddress::query()->where('address', $address)->exists()) {
            return $this->error("该地址已存在");
        }
        if (!$min = $this->request->input('min')) {
            return $this->error('请输入最小带宽阈值');
        }
        if (!$send = $this->request->input('send')) {
            return $this->error('请输入发送带宽的值');
        }
        $remark = $this->request->input('remark', '');
        $new = new LimitResourceAddress();
        $new->address = $address;
        $new->resource = $this->request->input('resource_type', 'ENERGY');
        $new->min_quantity = $min;
        $new->send_quantity = $send;
        $new->status = 1;
        $new->name = $remark;
        $new->max_times = $this->request->input('max_times', 0);
        $new->save();
        $this->service->clearLimitList(LimitResourceAddress::class);
        $this->eventDispatcher->dispatch(new LimitAddressCreated($new));
        return $this->success();
    }

    public function editAddress($id)
    {
        /** @var LimitResourceAddress $addr */
        $addr = LimitResourceAddress::query()->find($id);
        if (!$addr) {
            return $this->error('地址不存在');
        }
        if ($address = $this->request->input('address', '')) {
            $addr->address = $address;
        }
        if ($min = $this->request->input('min')) {
            $addr->min_quantity = $min;
        }
        if ($send = $this->request->input('send')) {
            $addr->send_quantity = $send;
        }
        if ($resource = $this->request->input('resource_type')) {
            $addr->resource = $resource;
        }
        if ($send_times = $this->request->input('send_times')) {
            $addr->send_times = $send_times;
        }
        if ($max_times = $this->request->input('max_times')) {
            $addr->max_times = $max_times;
        }
        $status = $this->request->input('status');
        $oldStatus = $addr->status;
        if ($status !== null) {
            $addr->status = $status;
        }
        if ($remark = $this->request->input('remark')) {
            $addr->name = $remark;
        }
        $addr->save();
        $this->service->clearLimitList(LimitResourceAddress::class);
        try {
            if ($status === 0 && $oldStatus == 1) {
                $this->service->closeAddress($addr);
            }
        } catch (\Exception $e) {
            return $this->error('回收带宽失败：' . $e->getMessage());
        }
        return $this->success();
    }

    public function deleteAddress($id)
    {
        $addr = LimitResourceAddress::query()->find($id);
        if (!$addr) {
            return $this->error('地址不存在');
        }
        if ($addr->status === 1) {
            $this->service->closeAddress($addr);
        }
        $addr->delete();
        $this->service->clearLimitList(LimitResourceAddress::class);
        return $this->success();
    }

    public function getLogs()
    {
        $pageSize = $this->request->query('pageSize', 10);
        $builder = ResourceDelegate::query();
        if ($address = $this->request->query('address')) {
            $builder->where('address', $address);
        }
        $list = $builder->orderBy('id', 'desc')->paginate($pageSize);
        return $this->success(compact('list'));
    }

    public function retryRecycle($id)
    {
        try {
            $this->service->recycleRetry($id);
            return $this->success();
        } catch (\Exception $e) {
            return $this->error('回收带宽失败：' . $e->getMessage());
        }
    }
}
