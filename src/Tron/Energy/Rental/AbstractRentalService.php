<?php

namespace William\HyperfExtTron\Tron\Energy\Rental;

use William\HyperfExtTronBot\BotError;
use William\HyperfExtTronBot\Core\RuntimeError;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTronService\SettingService;
use William\HyperfExtTron\Tron\Energy\Apis\ApiInterface;
use William\HyperfExtTron\Tron\Energy\Strategy\StrategyFactory;
use William\HyperfExtTron\Tron\TronApi;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractRentalService implements RentalInterface
{
    #[Inject]
    protected TronApi $tronApi;

    protected string $name = 'default';
    protected ApiInterface $api;

    protected StrategyFactory $strategyFactory;
    /**
     * @var LoggerInterface
     */
    protected mixed $log;

    public function __construct(ContainerInterface $container, StrategyFactory $strategyFactory,
                                protected SettingService $settingService)
    {
        $this->strategyFactory = $strategyFactory;
        $this->log = $container->get(StdoutLoggerInterface::class);
    }

    public function init(array $configs): void
    {
        if ($mode = $configs['energyMode']) {
            $this->api = $this->strategyFactory->get($mode)->get($configs, $this->name);
        } else {
            Logger::error('能量来源系统设置有误');
            throw new RuntimeError(BotError::SystemError);
        }
    }
}
