<?php

namespace William\HyperfExtTron\Limit;

use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\ResourceAddress;
use William\HyperfExtTron\Model\UserResourceAddress;

/**
 * @property \Closure(ResourceAddress):UserResourceAddress $getOwner 回调函数，返回 string
 */
class DefaultHandler implements LimitHandlerInterface
{
    private \Closure $getOwner;

    /**
     * @param \Closure(ResourceAddress):UserResourceAddress $getOwner 回调函数，返回值是 string
     */
    public function __construct(\Closure $getOwner)
    {
        $this->getOwner = $getOwner;
    }

    /**
     * @param ResourceAddress $model
     * @return void
     */
    public function handle(ResourceAddress $model)
    {
        Logger::debug("📊 地址{$model->address}达到阈值{$model->min_quantity}，发送{$model->resource}: {$model->send_quantity}");

        /** @var UserResourceAddress $owner */
        $owner = call_user_func($this->getOwner, $model);

        if (!$owner) {
            Logger::error("❌ 代理资源失败：owner {$owner->address} 不存在");
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