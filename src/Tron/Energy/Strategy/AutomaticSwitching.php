<?php

namespace William\HyperfExtTron\Tron\Energy\Strategy;

use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Tron\Energy\Apis\ApiInterface;
use William\HyperfExtTron\Tron\Energy\Attributes\Strategy;
use William\HyperfExtTron\Tron\Energy\EnergyApiFactory;

#[Strategy(name: 'automaticSwitching')]
class AutomaticSwitching implements StrategyInterface
{
    protected EnergyApiFactory $apiFactory;

    public function __construct(EnergyApiFactory $apiFactory)
    {
        $this->apiFactory = $apiFactory;
    }

    public function get(mixed $configs, string $rentalName): ?ApiInterface
    {
        $now = time();
        $timeSource = $configs['timeSource'] ?? [];
        if (empty($timeSource)) {
            Logger::info('时间区间未设置');
            throw new \Exception('AutomaticSwitching timeSource 不能为空');
        }
        // 获取当前小时（0-23）
        $currentHour = (int)date('G'); // G 返回 0-23，无前导零

        $matchSource = null;
        foreach ($timeSource as $item) {
            $start = $item['duration']['start'];
            $end = $item['duration']['end'];

            // 判断是否在区间
            if ($currentHour >= $start && $currentHour < $end) {
                $matchSource = $item['source'];
                break;
            }
        }
        Logger::info("当前小时：{$currentHour}，所属 source：{$matchSource}");
        return $this->apiFactory->get($matchSource);
    }
}
