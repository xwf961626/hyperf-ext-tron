<?php

namespace William\HyperfExtTron\Limit;

use GuzzleHttp\Exception\GuzzleException;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\ResourceAddress;

class DefaultRule implements RuleInterface
{

    public function check(ResourceAddress $model): bool
    {
        try {
            $model->updateResources();
            Logger::debug("检查地址{$model->address}是否达到阈值{$model->min_quantity}");
            if ($model->send_times >= $model->max_times) {
                Logger::debug("地址{$model->address}发送次数达到阈值{$model->max_times}关闭地址");
                $model->closeAddress();
                return false;
            }
            $model->updateResources();
            return $model->current_quantity < $model->min_quantity;
        } catch (GuzzleException $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }
}