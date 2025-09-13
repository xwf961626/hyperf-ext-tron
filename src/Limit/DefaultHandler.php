<?php

namespace William\HyperfExtTron\Limit;


use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Database\Model\Model;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\ResourceAddress;
use William\HyperfExtTron\Model\ResourceDelegate;
use William\HyperfExtTron\Model\UserResourceAddress;

class DefaultHandler implements LimitHandlerInterface
{
    /**
     * @param ResourceAddress $model
     * @return void
     */
    public function handle(ResourceAddress $model)
    {
        Logger::debug("📊 地址{$model->address}达到阈值{$model->min_quantity}，发送{$model->resource}: {$model->send_quantity}");

        $ownerAddress = env('BANDWIDTH_ADDR');
        /** @var UserResourceAddress $owner */
        $owner = UserResourceAddress::where('address', $ownerAddress)->first();

        if (!$owner) {
            Logger::error("❌ 代理资源失败：owner {$ownerAddress} 不存在");
            return;
        }

        try {
            $model->recycle($owner);
            $model->delegate($owner);
        } catch (\Exception $e) {
            Logger::error("❌ 代理资源失败：{$e->getMessage()} | 代理信息：" . json_encode($model));
        }
    }
}