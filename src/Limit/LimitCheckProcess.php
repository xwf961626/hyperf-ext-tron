<?php

namespace William\HyperfExtTron\Limit;

use Hyperf\Coroutine\Coroutine;
use Hyperf\Database\Model\Model;
use Hyperf\Process\AbstractProcess;
use Psr\Container\ContainerInterface;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Service\LimitAddressService;
use function Hyperf\Config\config;

class LimitCheckProcess extends AbstractProcess
{
    protected array $configs;

    public function __construct(ContainerInterface $container, protected LimitAddressService $service)
    {
        parent::__construct($container);
        $this->configs = config('tron.address_limit');
    }

    public function handle(): void
    {
        if ($this->configs['enable']) {
            /** @var LimitCheck $check */
            foreach ($this->configs['check'] as $check) {
                $this->startCheck($check);
            }
        }
    }

    public function startCheck(LimitCheck $check): void
    {
        Coroutine::create(function () use ($check) {
            Logger::debug("启动LimitChecker => {$check->getName()}");
            $model = $check->getModel();
            Logger::debug("模型：$model");
            $interval = $check->getInterval();
            Logger::debug("时间间隔：$interval");
            /** @var Model $model */
            if (!class_exists($model)) {
                Logger::error("检查的模型不存在：$model");
                return;
            }
            while (true) {
                try {
                    $all = $this->service->getLimitList($model);
                    if (empty($all)) {
                        Logger::debug("无地址需检测");
                        Coroutine::sleep(0.5); // 避免空转
                        continue;
                    }
                    foreach ($all as $item) {
                        try {
                            // 检查是否达到阈值
                            $entity = new $model($item);
                            if ($check->getRule()->check($entity)) {
                                $check->getCallback()->handle($entity);
                            }
                        } catch (\Exception $e) {
                            Logger::error("检查地址".json_encode($item)."失败：{$e->getMessage()}");
                        }
                    }
                } catch (\Throwable $e) {
                    Logger::debug("Check limit error: " . $e->getMessage());
                } finally {
                    Coroutine::sleep($interval);
                }
            }
        });
    }
}