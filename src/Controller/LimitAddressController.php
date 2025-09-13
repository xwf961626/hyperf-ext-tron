<?php

namespace William\HyperfExtTron\Controller;


use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Logger\LoggerFactory;
use Phper666\JWTAuth\JWT;
use William\HyperfExtTron\Model\LimitResourceAddress;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Model\UserResourceAddress;
use function Hyperf\Support\env;

class LimitAddressController extends BaseController
{
    public function __construct(JWT                                   $jwt,
                                LoggerFactory                         $loggerFactory,
                                RequestInterface                      $request,
                                ResponseInterface                     $response,
                                protected LimitResourceAddressService $service
    )
    {
        parent::__construct($jwt, $loggerFactory, $request, $response);
    }

    public function addressList()
    {
        $q = LimitResourceAddress::query()->where('resource', 'BANDWIDTH');
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
        if (!$min_bandwidth = $this->request->input('min_bandwidth')) {
            return $this->error('请输入最小带宽阈值');
        }
        if (!$send_bandwidth = $this->request->input('send_bandwidth')) {
            return $this->error('请输入发送带宽的值');
        }
        $remark = $this->request->input('remark', '');
        LimitResourceAddress::query()->create([
            'address' => $address,
            'min_bandwidth' => $min_bandwidth,
            'send_bandwidth' => $send_bandwidth,
            'status' => 0,
            'remark' => $remark,
            'max_times' => $this->request->input('max_times', 0)
        ]);
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
        if ($min_bandwidth = $this->request->input('min_bandwidth')) {
            $addr->min_quantity = $min_bandwidth;
        }
        if ($send_bandwidth = $this->request->input('send_bandwidth')) {
            $addr->send_quantity = $send_bandwidth;
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

        try {
            if ($status === 0 && $oldStatus == 1) {
                $this->service->recycle($addr);
                $addr->send_times = 0;
                $addr->save();
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
        $addr->delete();
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
            $userAddress = UserResourceAddress::where('address', env('BANDWIDTH_ADDR'))->first();
            $this->service->recycleRetry($id, $userAddress);
            return $this->success();
        } catch (\Exception $e) {
            return $this->error('回收带宽失败：' . $e->getMessage());
        }
    }
}
