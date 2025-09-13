<?php

namespace William\HyperfExtTron\Controller;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;
use William\HyperfExtTron\Event\UserResourceAddressClosed;
use William\HyperfExtTron\Event\UserResourceAddressCreated;
use William\HyperfExtTron\Event\UserResourceAddressOpened;
use William\HyperfExtTron\Model\UserResourceAddress;
use William\HyperfExtTron\Service\UserResourceAddressService;
use William\HyperfExtTron\Tron\TronApi;

class UserResourceAddressController extends BaseController
{
    #[Inject]
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(ContainerInterface $container, protected TronApi $tronApi,protected UserResourceAddressService $service)
    {
        parent::__construct($container);
    }

// 添加能量地址
    public function addAddress()
    {
        $address = $this->request->input("address");
        $name = $this->request->input("name");
        $type = $this->request->input("type");
        $operate = $this->request->input("operate_address");
        if (!$operate) return $this->error("操作地址必填");
        if (!$type) return $this->error("资源类型必填");
        if ($type !== 'BANDWIDTH' && $type !== 'ENERGY') {
            return $this->error("资源类型只能是: ENERGY或BANDWIDTH");
        }
        $exists = UserResourceAddress::query()->where('address', $address)->first();
        if ($exists) {
            return $this->success("此地址已存在");
        }
        try {
            $resourceAddress = new UserResourceAddress();
            $resourceAddress->address = $address;
            $resourceAddress->name = $name;
            $resourceAddress->operate_address = $operate;
            $permission = $this->tronApi->getPermissionId($address, $operate);
            $resourceAddress->permission = $permission;
            $resourceAddress->type = $type;
            $resourceAddress->save();
            $this->service->updateResources($resourceAddress);
            $this->eventDispatcher->dispatch(new UserResourceAddressCreated($resourceAddress));
        } catch (\Exception $e) {
            return $this->error("添加能量地址失败：" . $e->getMessage());
        }
        return $this->success($resourceAddress);
    }

    public function getAddress()
    {
        $page_size = $this->request->input('page_size', 20);
        $keyword = $this->request->input("keyword");
        $builder = UserResourceAddress::query();
        if ($keyword) {
            $builder->where("name", 'like', '%' . $keyword . '%');
        }
        $builder = $builder->orderByRaw('id desc');
        $list = $builder->paginate($page_size);
        return $this->success(compact('list'));
    }

    // 修改能量地址
    public function switchOpen()
    {
        $id = $this->request->input('id');
        $isOpen = $this->request->input('open');
        /** @var UserResourceAddress $resourceAddress */
        $resourceAddress = UserResourceAddress::query()->find($id);
        if (!$resourceAddress) {
            return $this->error("能量地址不存在");
        }
        try {
            if ($resourceAddress->status != $isOpen) {
                if (!$isOpen) {
                    $resourceAddress->status = 0;
                    $resourceAddress->save();
                    $this->eventDispatcher->dispatch(new UserResourceAddressClosed($resourceAddress));
                } else {
                    $resourceAddress->status = 1;
                    $resourceAddress->save();
                    $this->eventDispatcher->dispatch(new UserResourceAddressOpened($resourceAddress));
                }
            }
            return $this->success($resourceAddress);
        } catch (\Exception $e) {
            return $this->error("操作失败：" . $e->getMessage());
        }
    }
}