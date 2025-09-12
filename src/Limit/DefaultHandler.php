<?php

namespace William\HyperfExtTron\Limit;


use Hyperf\Database\Model\Model;
use William\HyperfExtTron\Helper\Logger;

class DefaultHandler implements LimitHandlerInterface
{
    public function handle(Model $model): void
    {
        Logger::debug("检测到地址达到阈值：".json_encode($model));
    }
}